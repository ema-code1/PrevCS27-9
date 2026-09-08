# sensors.py — lectura de los 3 sensores durante una ventana de medicion.
#
# Conexiones (segun tu armado):
#   SW-420 (vibracion)  DO  -> GPIO34   (solo-entrada, sin pull interno)
#   LM393 RPM           DO  -> GPIO25   (cuenta de pulsos por interrupcion)
#   LM393 RPM           AO  -> GPIO35   (ADC, solo-entrada)
#   DS18B20 (temp)      DATA-> GPIO27   (OneWire, con resistencia 4.7k a 3.3V)
#
# Todos alimentados a 3.3V.
#
# Los numeros de pin viven en pins.py (mapa unico de GPIO de la placa).

import time
from machine import Pin, ADC

import onewire
import ds18x20

from pins import PIN_VIBRACION, PIN_RPM, PIN_RPM_AO, PIN_TEMP

SENSORS_BUILD = "2026-09-04-rpm-gpio25-v4"

# --- Contadores que actualizan las interrupciones (ISR) ---
_vib_count = 0
_rpm_rising_count = 0
_rpm_falling_count = 0

# Handlers de interrupcion: tienen que ser MUY cortos (solo sumar).
def _vib_isr(pin):
    global _vib_count
    _vib_count += 1

def _rpm_isr(pin):
    global _rpm_rising_count, _rpm_falling_count
    if pin.value():
        _rpm_rising_count += 1
    else:
        _rpm_falling_count += 1


class Sensors:
    def __init__(self, pulses_per_rev=1):
        self.pulses_per_rev = max(1, int(pulses_per_rev))
        # Ultimo problema de sensor detectado, en texto corto para el LCD.
        # NO forma parte del payload: solo alimenta la capa de estados.
        self.last_sensor_error = None

        # Entradas digitales. GPIO34/35 son solo-entrada (no admiten pull interno);
        # los modulos SW-420 / LM393 ya entregan una salida activa, asi que va bien.
        self.pin_vib = Pin(PIN_VIBRACION, Pin.IN)
        self.pin_rpm = Pin(PIN_RPM, Pin.IN, Pin.PULL_UP)

        # ADC para el AO del LM393 (rango 0..3.3V -> atenuacion 11dB).
        self.adc = ADC(Pin(PIN_RPM_AO))
        try:
            self.adc.atten(ADC.ATTN_11DB)
        except Exception:
            pass

        # DS18B20 por OneWire. Si no responde, lo marcamos como ausente.
        self.ds = None
        self.ds_rom = None
        try:
            ow = onewire.OneWire(Pin(PIN_TEMP))
            self.ds = ds18x20.DS18X20(ow)
            roms = self.ds.scan()
            if roms:
                self.ds_rom = roms[0]
                print("[sensors] DS18B20 detectado:", _hex(self.ds_rom))
            else:
                print("[sensors] AVISO: no se detecto ningun DS18B20 en GPIO{}.".format(PIN_TEMP))
                self.last_sensor_error = "DS18B20 ausente"
        except Exception as e:
            print("[sensors] AVISO: error iniciando DS18B20:", e)
            self.last_sensor_error = "DS18B20 init"

    def _enable_irqs(self):
        global _vib_count, _rpm_rising_count, _rpm_falling_count
        _vib_count = 0
        _rpm_rising_count = 0
        _rpm_falling_count = 0
        # SW-420: contamos cada flanco de subida como un "evento" de vibracion.
        self.pin_vib.irq(trigger=Pin.IRQ_RISING, handler=_vib_isr)
        # Contamos ambos flancos para no depender de la polaridad del modulo.
        # Una vuelta completa produce un flanco de subida y uno de bajada.
        self.pin_rpm.irq(
            trigger=Pin.IRQ_RISING | Pin.IRQ_FALLING,
            handler=_rpm_isr,
        )

    def _disable_irqs(self):
        self.pin_vib.irq(handler=None)
        self.pin_rpm.irq(handler=None)

    def read_window(self, window_seconds=15):
        """Mide durante 'window_seconds' y devuelve un dict con los 4 valores.

        Devuelve: {
          'vibration': eventos (int),
          'rpm': revoluciones por minuto (float),
          'rpm_analog': promedio ADC 0..4095 (int),
          'temperature': grados C (float) o None si el DS18B20 no respondio
        }
        """
        window_ms = int(window_seconds * 1000)
        # Reevaluamos el diagnostico en cada ventana (solo informativo).
        self.last_sensor_error = None if (self.ds and self.ds_rom) else "DS18B20 ausente"

        # Arrancamos la conversion del DS18B20 al principio de la ventana
        # (tarda ~750ms; al leerlo al final ya esta listo).
        if self.ds and self.ds_rom:
            try:
                self.ds.convert_temp()
            except Exception as e:
                print("[sensors] DS18B20 convert_temp fallo:", e)
                self.last_sensor_error = "DS18B20 convert"

        self._enable_irqs()

        # Durante la ventana muestreamos el AO del LM393 y promediamos.
        adc_sum = 0
        adc_n = 0
        adc_min = 4095
        adc_max = 0
        adc_error = None
        rpm_do_start = self.pin_rpm.value()
        rpm_do_min = rpm_do_start
        rpm_do_max = rpm_do_start
        t0 = time.ticks_ms()
        while time.ticks_diff(time.ticks_ms(), t0) < window_ms:
            try:
                # MicroPython cambia la API de ADC segun version/port.
                try:
                    adc_value = self.adc.read_u16() >> 4
                except AttributeError:
                    adc_value = self.adc.read()
                adc_sum += adc_value
                adc_n += 1
                adc_min = min(adc_min, adc_value)
                adc_max = max(adc_max, adc_value)
            except Exception as e:
                if adc_error is None:
                    adc_error = repr(e)
                    self.last_sensor_error = "LM393 AO"

            rpm_do = self.pin_rpm.value()
            rpm_do_min = min(rpm_do_min, rpm_do)
            rpm_do_max = max(rpm_do_max, rpm_do)
            time.sleep_ms(50)

        self._disable_irqs()

        vib_events = _vib_count
        rpm_rising = _rpm_rising_count
        rpm_falling = _rpm_falling_count
        # Cada pulso completo tiene ambos flancos. max() conserva tambien un
        # pulso que haya quedado cortado por el inicio o final de la ventana.
        rpm_pulses = max(rpm_rising, rpm_falling)

        # RPM = (pulsos / pulsos_por_vuelta) escalado a 1 minuto.
        rpm = 0.0
        if window_seconds > 0:
            rev = rpm_pulses / self.pulses_per_rev
            rpm = rev * (60.0 / window_seconds)

        adc_avg = int(adc_sum / adc_n) if adc_n else 0

        temperature = None
        if self.ds and self.ds_rom:
            try:
                temperature = round(self.ds.read_temp(self.ds_rom), 2)
                # Lectura de error tipica del DS18B20 cuando hay problema de cableado.
                if temperature == 85.0:
                    print("[sensors] AVISO: DS18B20 devolvio 85.0C (revisar 4.7k / cableado).")
            except Exception as e:
                print("[sensors] DS18B20 read_temp fallo:", e)
                self.last_sensor_error = "DS18B20 lectura"
                temperature = None

        return {
            "vibration": vib_events,
            "rpm": round(rpm, 1),
            "rpm_pulses": rpm_pulses,
            "rpm_rising": rpm_rising,
            "rpm_falling": rpm_falling,
            "rpm_do_start": rpm_do_start,
            "rpm_do_end": self.pin_rpm.value(),
            "rpm_do_changed": rpm_do_min != rpm_do_max,
            "rpm_analog": adc_avg,
            "rpm_analog_min": adc_min if adc_n else None,
            "rpm_analog_max": adc_max if adc_n else None,
            "rpm_analog_error": adc_error,
            "temperature": temperature,
        }


def _hex(b):
    try:
        import ubinascii
        return ubinascii.hexlify(b).decode()
    except Exception:
        return str(b)
