# sensors.py — lectura de los 3 sensores durante una ventana de medicion.
#
# Conexiones (segun tu armado):
#   SW-420 (vibracion)  DO  -> GPIO34   (solo-entrada, sin pull interno)
#   LM393 RPM           DO  -> GPIO26   (cuenta de pulsos por interrupcion)
#   LM393 RPM           AO  -> GPIO35   (ADC, solo-entrada)
#   DS18B20 (temp)      DATA-> GPIO27   (OneWire, con resistencia 4.7k a 3.3V)
#
# Todos alimentados a 3.3V.

import time
from machine import Pin, ADC

import onewire
import ds18x20

# --- Pines ---
PIN_VIBRACION = 34   # SW-420 DO
PIN_RPM       = 26   # LM393 DO
PIN_RPM_AO    = 35   # LM393 AO
PIN_TEMP      = 27   # DS18B20 DATA

# --- Contadores que actualizan las interrupciones (ISR) ---
_vib_count = 0
_rpm_count = 0

# Handlers de interrupcion: tienen que ser MUY cortos (solo sumar).
def _vib_isr(pin):
    global _vib_count
    _vib_count += 1

def _rpm_isr(pin):
    global _rpm_count
    _rpm_count += 1


class Sensors:
    def __init__(self, pulses_per_rev=1):
        self.pulses_per_rev = max(1, int(pulses_per_rev))

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
                print("[sensors] AVISO: no se detecto ningun DS18B20 en GPIO27.")
        except Exception as e:
            print("[sensors] AVISO: error iniciando DS18B20:", e)

    def _enable_irqs(self):
        global _vib_count, _rpm_count
        _vib_count = 0
        _rpm_count = 0
        # SW-420: contamos cada flanco de subida como un "evento" de vibracion.
        self.pin_vib.irq(trigger=Pin.IRQ_RISING, handler=_vib_isr)
        # LM393: cada pulso del disco encoder.
        self.pin_rpm.irq(trigger=Pin.IRQ_RISING, handler=_rpm_isr)

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

        # Arrancamos la conversion del DS18B20 al principio de la ventana
        # (tarda ~750ms; al leerlo al final ya esta listo).
        if self.ds and self.ds_rom:
            try:
                self.ds.convert_temp()
            except Exception as e:
                print("[sensors] DS18B20 convert_temp fallo:", e)

        self._enable_irqs()

        # Durante la ventana muestreamos el AO del LM393 y promediamos.
        adc_sum = 0
        adc_n = 0
        t0 = time.ticks_ms()
        while time.ticks_diff(time.ticks_ms(), t0) < window_ms:
            try:
                adc_sum += self.adc.read_u16() >> 4   # 16-bit -> 12-bit (0..4095)
                adc_n += 1
            except Exception:
                pass
            time.sleep_ms(50)

        self._disable_irqs()

        vib_events = _vib_count
        rpm_pulses = _rpm_count

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
                temperature = None

        return {
            "vibration": vib_events,
            "rpm": round(rpm, 1),
            "rpm_analog": adc_avg,
            "temperature": temperature,
        }


def _hex(b):
    try:
        import ubinascii
        return ubinascii.hexlify(b).decode()
    except Exception:
        return str(b)
