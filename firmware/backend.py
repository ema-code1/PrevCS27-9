# backend.py — envia el paquete de lecturas al backend PHP por HTTP POST (JSON).
#
# Soporta VARIOS servidores (ej: casa y escuela). Prueba cada uno hasta que
# alguno acepte el lote; recuerda el ultimo que funciono para probarlo primero
# (asi al moverte de lugar se reacomoda solo, sin tocar la placa).
#
# Cada endpoint = {'url': '.../api/iot/lectura', 'token': 'token_de_ese_server'}.
# Auth por header 'X-Device-Token' (no usa sesion ni CSRF).

try:
    import ujson as json
except ImportError:
    import json

try:
    import urequests as requests
except ImportError:
    import requests

import time

_last_ok = 0  # indice del ultimo endpoint que acepto (se prueba primero)


def send_readings(endpoints, mac_address, readings, window_seconds=15,
                  timestamp=None, retries=2):
    """Envia el lote a los endpoints hasta que uno acepte. Devuelve True/False.
    No lanza excepciones: ante error loguea y sigue con el siguiente."""
    global _last_ok

    if not endpoints:
        print("[http] No hay endpoints configurados.")
        return False

    base = {
        "mac_address": mac_address,
        "window_seconds": window_seconds,
        "readings": readings,
    }
    if timestamp:
        base["timestamp"] = timestamp

    # Orden: primero el ultimo que funciono, despues el resto.
    order = list(range(len(endpoints)))
    if 0 <= _last_ok < len(endpoints):
        order = [_last_ok] + [i for i in order if i != _last_ok]

    for idx in order:
        ep = endpoints[idx]
        url = ep.get("url", "")
        token = ep.get("token", "")
        if not url or not token:
            continue

        payload = dict(base)
        payload["token"] = token
        body = json.dumps(payload)
        headers = {"Content-Type": "application/json", "X-Device-Token": token}

        for intento in range(1, retries + 1):
            resp = None
            try:
                print("[http] POST", url, "(intento", intento, "de", retries, ")")
                resp = requests.post(url, data=body, headers=headers)
                code = resp.status_code
                text = resp.text
                if 200 <= code < 300:
                    print("[http] OK", code, "->", text)
                    _last_ok = idx
                    return True
                # El server respondio pero rechazo: no sirve reintentar este
                # endpoint (token/registro/sensores). Pasamos al siguiente.
                print("[http] Rechazado", code, "->", text)
                break
            except Exception as e:
                print("[http] Sin respuesta de", url, "->", e)
            finally:
                if resp is not None:
                    try:
                        resp.close()
                    except Exception:
                        pass

            if intento < retries:
                time.sleep(1)

        print("[http] Pruebo el siguiente servidor (si hay)...")

    print("[http] Ningun servidor acepto el lote. Se descarta este paquete.")
    return False
