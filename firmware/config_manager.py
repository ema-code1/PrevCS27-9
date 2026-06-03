# config_manager.py — lee y guarda la configuracion de la placa en config.json.
#
# config.json lo crea el modo AP (portal web) la primera vez. Tambien podes
# editarlo a mano en Thonny (ver config.example.json).
#
# Soporta DOS formatos:
#  - Simple (lo que guarda el portal): wifi_ssid + wifi_password + server_url + device_token
#  - Multi-lugar (a mano, ej. casa/escuela): wifi_networks[] + endpoints[]
# Internamente todo se normaliza a wifi_networks[] y endpoints[].

try:
    import ujson as json
except ImportError:
    import json

CONFIG_FILE = "config.json"

# Valores por defecto de los campos opcionales.
DEFAULTS = {
    "device_name": "ESP32",
    "pulses_per_rev": 1,     # ranuras del disco encoder del LM393 (1 pulso = 1 vuelta)
    "window_seconds": 15,    # cuanto dura cada ventana de medicion
    "period_seconds": 60,    # cada cuanto arranca una ventana
}


def _normalize_wifi(cfg):
    """Devuelve una lista [{'ssid':.., 'password':..}, ...].
    Acepta 'wifi_networks' (lista) o el par simple wifi_ssid/wifi_password."""
    nets = cfg.get("wifi_networks")
    if not nets:
        ssid = cfg.get("wifi_ssid", "")
        nets = [{"ssid": ssid, "password": cfg.get("wifi_password", "")}] if ssid else []

    out = []
    for n in nets:
        if isinstance(n, dict) and n.get("ssid"):
            out.append({"ssid": n["ssid"], "password": n.get("password", "")})
    return out


def _normalize_endpoints(cfg):
    """Devuelve una lista [{'url':.., 'token':..}, ...].
    Acepta 'endpoints' (lista) o el par simple server_url/device_token.
    En el formato simple, el mismo token vale para todas las URLs."""
    eps = cfg.get("endpoints")
    out = []

    if eps:
        for e in eps:
            if isinstance(e, dict) and e.get("url") and e.get("token"):
                out.append({"url": e["url"], "token": e["token"]})
        return out

    # Formato simple / legacy.
    token = cfg.get("device_token", "")
    urls = cfg.get("server_urls")
    if not urls:
        single = cfg.get("server_url", "")
        urls = [u.strip() for u in single.replace(",", " ").split() if u.strip()]
    if isinstance(urls, str):
        urls = [urls]

    for u in urls:
        if u and token:
            out.append({"url": u, "token": token})
    return out


def load():
    """Devuelve el dict de configuracion (con wifi_networks[] y endpoints[]),
    o None si no existe / esta incompleto."""
    try:
        with open(CONFIG_FILE, "r") as f:
            cfg = json.load(f)
    except (OSError, ValueError):
        return None

    if not isinstance(cfg, dict):
        return None

    cfg["wifi_networks"] = _normalize_wifi(cfg)
    cfg["endpoints"] = _normalize_endpoints(cfg)

    if not cfg["wifi_networks"]:
        print("[config] Falta la red WiFi (wifi_ssid o wifi_networks).")
        return None
    if not cfg["endpoints"]:
        print("[config] Falta servidor+token (endpoints o server_url + device_token).")
        return None

    for key, val in DEFAULTS.items():
        cfg.setdefault(key, val)

    return cfg


def save(cfg):
    """Persiste la configuracion en config.json. Devuelve True/False."""
    try:
        with open(CONFIG_FILE, "w") as f:
            json.dump(cfg, f)
        print("[config] Guardado en", CONFIG_FILE)
        return True
    except OSError as e:
        print("[config] ERROR al guardar:", e)
        return False
