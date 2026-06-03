# main.py — programa principal de la ESP32 para PrevCS.
#
# Flujo:
#   1) Si no hay config.json valido  -> arranca el PORTAL de configuracion (modo AP).
#   2) Si hay config                 -> conecta al WiFi y entra al ciclo de medicion:
#        cada 'period_seconds' (60s) mide durante 'window_seconds' (15s),
#        calcula los valores y los envia al backend en un solo paquete JSON.

import time

import config_manager
import wifi_manager
import backend
from sensors import Sensors

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


def ensure_wifi(cfg, intentos=3):
    """Intenta conectar a alguna de las redes WiFi configuradas. True si lo logra."""
    for i in range(1, intentos + 1):
        if wifi_manager.connect_any(cfg["wifi_networks"], timeout=20):
            return True
        print("[main] Reintento de WiFi", i, "de", intentos)
        time.sleep(2)
    return False


def run():
    print("\n" + "=" * 52)
    print("[main] PrevCS ESP32 - arranque")

    cfg = config_manager.load()

    # --- Sin configuracion: modo portal (no retorna; al guardar reinicia) ---
    if cfg is None:
        print("[main] No hay config valida -> abriendo portal de configuracion.")
        wifi_manager.start_config_portal()
        return

    mac = wifi_manager.get_sta_mac()
    print("[main] Placa:", cfg["device_name"])
    print("[main] MAC  :", mac, " (debe estar registrada en la web)")
    print("[main] Redes WiFi:", len(cfg["wifi_networks"]), "| Servidores:", len(cfg["endpoints"]))
    print("=" * 52)

    # --- Conexion WiFi. Si falla de entrada, abrimos el portal para corregir. ---
    if not ensure_wifi(cfg):
        print("[main] No se pudo conectar al WiFi. Abriendo portal para reconfigurar.")
        wifi_manager.start_config_portal()
        return

    sensors = Sensors(pulses_per_rev=cfg["pulses_per_rev"])
    period = int(cfg["period_seconds"])
    window = int(cfg["window_seconds"])

    print("[main] Ciclo: medir {}s cada {}s. Entrando al loop...".format(window, period))

    while True:
        t_cycle = time.ticks_ms()

        # Reconexion si se cayo el WiFi (no bloquea el ciclo indefinidamente).
        if not wifi_manager.is_connected():
            print("[main] WiFi caido. Intentando reconectar...")
            ensure_wifi(cfg, intentos=2)

        print("\n[main] --- Ventana de medicion ({}s) ---".format(window))
        data = sensors.read_window(window_seconds=window)

        # Estado del motor derivado de la RPM real (RPM > 0 = andando).
        motor = "ANDANDO" if data["rpm"] > 0 else "DETENIDO"

        print("[main] Leido -> vibracion:{} eventos | rpm:{} ({}) | temp:{}".format(
            data["vibration"], data["rpm"], motor,
            data["temperature"] if data["temperature"] is not None else "N/D"))
        # Diagnostico (no se envia): nivel AO del LM393, util para ver si el
        # sensor RPM esta bien alineado con la ranura del disco.
        print("[main] (diag) AO LM393:", data["rpm_analog"])

        readings = build_readings(data)

        if wifi_manager.is_connected():
            print("[main] Enviando", len(readings), "lecturas al backend...")
            backend.send_readings(
                endpoints=cfg["endpoints"],
                mac_address=mac,
                readings=readings,
                window_seconds=window,
            )
        else:
            print("[main] Sin WiFi: se descarta este paquete (no se bloquea el ciclo).")

        # Dormir hasta completar el periodo (descontando lo que ya tardo el ciclo).
        elapsed = time.ticks_diff(time.ticks_ms(), t_cycle) / 1000.0
        rest = period - elapsed
        if rest > 0:
            print("[main] Esperando {:.1f}s para el proximo ciclo.".format(rest))
            time.sleep(rest)


# Arranque protegido: si algo explota, lo mostramos en consola en vez de morir en silencio.
try:
    run()
except Exception as e:
    import sys
    print("[main] ERROR no controlado:")
    sys.print_exception(e)
