# main.py — programa principal de la ESP32 para PrevCS.
#
# Flujo:
#   1) Si no hay config.json valido  -> arranca el PORTAL de configuracion (modo AP).
#   2) Si hay config                 -> conecta al WiFi y entra al ciclo de medicion:
#        cada 'period_seconds' (60s) mide durante 'window_seconds' (15s),
#        calcula los valores y los envia al backend en un solo paquete JSON.
#
# El LCD1602 I2C y el buzzer (device_ui.py) solo REFLEJAN ese flujo: no cambian
# lecturas, tiempos ni el payload. Los GPIO estan todos en pins.py.

import time

import config_manager
import wifi_manager
import backend
import device_ui
import pins
import sensors as sensors_module
from sensors import Sensors

_ui = None   # capa LCD+buzzer; global para poder avisar desde el handler de errores

FIRMWARE_BUILD = "2026-09-04-lcd-buzzer-v4"

# Unidad que reporta cada metrica (informativo para el backend).
# Son los 3 sensores que se guardan en sensor_readings.
UNITS = {
    "vibration":   "events",
    "rpm":         "rpm",
    "temperature": "celsius",
}


def build_readings(data):
    """Convierte el dict de sensores en la lista 'readings' del JSON.
    Solo se envian los 3 sensores reales. El AO del LM393 NO se envia: se usa
    al final solo como diagnostico en consola.
    Omite las metricas sin dato (ej: DS18B20 sin responder -> temperature None)."""
    readings = []
    for key in ("vibration", "rpm", "temperature"):
        value = data.get(key)
        if value is None:
            continue
        readings.append({"type": key, "value": value, "unit": UNITS[key]})
    return readings


def ensure_wifi(cfg, intentos=3, ui=None):
    """Intenta conectar a alguna de las redes WiFi configuradas. True si lo logra."""
    for i in range(1, intentos + 1):
        if ui:
            ui.set(device_ui.CONNECTING_WIFI)
        if wifi_manager.connect_any(cfg["wifi_networks"], timeout=20):
            return True
        print("[main] Reintento de WiFi", i, "de", intentos)
        if ui:
            ui.set(device_ui.WIFI_ERROR)
            ui.wait(2)
        else:
            time.sleep(2)
    return False


def run():
    global _ui
    ui = device_ui.DeviceUI()
    _ui = ui
    ui.set(device_ui.BOOTING)

    print("\n" + "=" * 52)
    print("[main] PrevCS ESP32 - arranque")
    print("[main] Firmware build:", FIRMWARE_BUILD)
    print("[main] Sensors build :", getattr(sensors_module, "SENSORS_BUILD", "ANTIGUO/SIN VERSION"))

    cfg = config_manager.load()

    # --- Sin configuracion: modo portal (no retorna; al guardar reinicia) ---
    if cfg is None:
        print("[main] No hay config valida -> abriendo portal de configuracion.")
        ui.message("PrevCS CONFIG", "WiFi 192.168.4.1")
        wifi_manager.start_config_portal()
        return

    mac = wifi_manager.get_sta_mac()
    print("[main] Placa:", cfg["device_name"])
    print("[main] MAC  :", mac, " (debe estar registrada en la web)")
    print("[main] Redes WiFi:", len(cfg["wifi_networks"]), "| Servidores:", len(cfg["endpoints"]))
    print("=" * 52)

    # --- Conexion WiFi. Si falla de entrada, abrimos el portal para corregir. ---
    if not ensure_wifi(cfg, ui=ui):
        print("[main] No se pudo conectar al WiFi. Abriendo portal para reconfigurar.")
        ui.set(device_ui.CRITICAL_ERROR)
        ui.wait(2)
        ui.message("PrevCS CONFIG", "WiFi 192.168.4.1")
        wifi_manager.start_config_portal()
        return

    ui.set(device_ui.CONNECTED)

    sensors = Sensors(pulses_per_rev=cfg["pulses_per_rev"])
    period = int(cfg["period_seconds"])
    window = int(cfg["window_seconds"])
    first_send = True   # el primer POST se muestra como "Conectando API"

    print("[main] Ciclo: medir {}s cada {}s. Entrando al loop...".format(window, period))

    while True:
        t_cycle = time.ticks_ms()

        # Reconexion si se cayo el WiFi (no bloquea el ciclo indefinidamente).
        if not wifi_manager.is_connected():
            print("[main] WiFi caido. Intentando reconectar...")
            ensure_wifi(cfg, intentos=2, ui=ui)

        ui.set(device_ui.MEASURING)
        print("\n[main] --- Ventana de medicion ({}s) ---".format(window))
        data = sensors.read_window(window_seconds=window)

        # Estado del motor derivado de la RPM real (RPM > 0 = andando).
        motor = "ANDANDO" if data["rpm"] > 0 else "DETENIDO"

        print("[main] Leido -> vibracion:{} eventos | rpm:{} ({}) | temp:{}".format(
            data["vibration"], data["rpm"], motor,
            data["temperature"] if data["temperature"] is not None else "N/D"))
        # Diagnostico local: permite distinguir falta de pulsos, DO trabado y
        # errores de ADC. Ninguno de estos campos se envia al backend.
        print("[main] (diag) LM393 -> pulsos:{} (sube:{} baja:{}) | DO:{}->{} cambio:{} | AO:{} ({}..{})".format(
            data["rpm_pulses"],
            data["rpm_rising"],
            data["rpm_falling"],
            data["rpm_do_start"],
            data["rpm_do_end"],
            "SI" if data["rpm_do_changed"] else "NO",
            data["rpm_analog"],
            data["rpm_analog_min"],
            data["rpm_analog_max"],
        ))
        if data["rpm_analog_error"]:
            print("[main] (diag) ERROR leyendo AO LM393:", data["rpm_analog_error"])
        if data["vibration"] > 1000:
            print("[main] AVISO: vibracion excesiva; GPIO{} esta flotante, con ruido o recibe el DO equivocado.".format(pins.PIN_VIBRACION))
        if data["rpm_pulses"] == 0:
            print("[main] AVISO: GPIO{} no recibio ningun flanco del DO del sensor RPM.".format(pins.PIN_RPM))

        # El LCD/buzzer avisan si algun sensor no respondio. El paquete se envia
        # igual: build_readings() ya omite las metricas sin dato, como siempre.
        if sensors.last_sensor_error:
            ui.set(device_ui.SENSOR_ERROR, sensors.last_sensor_error, hold_ms=2500)
        else:
            ui.sensors_ok()

        readings = build_readings(data)

        if wifi_manager.is_connected():
            print("[main] Enviando", len(readings), "lecturas al backend...")
            ui.set(device_ui.CONNECTING_API if first_send else device_ui.SENDING_DATA)
            enviado = backend.send_readings(
                endpoints=cfg["endpoints"],
                mac_address=mac,
                readings=readings,
                window_seconds=window,
            )
            first_send = False
            ui.set(device_ui.DATA_SENT if enviado else device_ui.API_ERROR)
        else:
            print("[main] Sin WiFi: se descarta este paquete (no se bloquea el ciclo).")
            ui.set(device_ui.WIFI_ERROR)

        # Dormir hasta completar el periodo (descontando lo que ya tardo el ciclo).
        elapsed = time.ticks_diff(time.ticks_ms(), t_cycle) / 1000.0
        rest = period - elapsed
        if rest > 0:
            print("[main] Esperando {:.1f}s para el proximo ciclo.".format(rest))
            ui.wait(rest)


# Arranque protegido: si algo explota, lo mostramos en consola en vez de morir en silencio.
try:
    run()
except Exception as e:
    import sys
    print("[main] ERROR no controlado:")
    sys.print_exception(e)
    if _ui is not None:
        # Beep largo + cartel: la placa quedo parada y necesita atencion.
        _ui.set(device_ui.CRITICAL_ERROR)
        _ui.wait(2)
