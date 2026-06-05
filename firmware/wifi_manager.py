# wifi_manager.py — conexion WiFi (modo normal) y portal de configuracion (modo AP).
#
# Modo normal: connect() une la ESP32 a la red WiFi del cliente.
# Modo AP:     start_config_portal() crea la red "PrevCS_Config", muestra una
#              pagina web donde el usuario carga sus datos (sin saber de codigo),
#              guarda config.json y reinicia la placa.

import time
import network
import machine

import config_manager

AP_SSID = "PrevCS_Config"      # red que crea la placa para configurarse
AP_PASSWORD = ""               # abierta (vacio) para que sea facil de conectar

_sta = network.WLAN(network.STA_IF)


def get_sta_mac():
    """MAC de la interfaz WiFi (la que el backend usa para identificar la placa).
    Formato AA:BB:CC:DD:EE:FF. Es la que hay que registrar en la pagina web."""
    if not _sta.active():
        _sta.active(True)
    mac = _sta.config("mac")
    return ":".join("{:02X}".format(b) for b in mac)


def is_connected():
    return _sta.isconnected()


def connect(ssid, password="", timeout=20):
    """Conecta al WiFi. Devuelve True si quedo conectada antes del timeout."""
    _sta.active(True)
    if _sta.isconnected():
        return True

    # Cancela cualquier intento previo en curso. Sin esto, al reintentar salta
    # "sta is connecting, cannot set config" / "Wifi Internal State Error".
    try:
        _sta.disconnect()
    except OSError:
        pass
    time.sleep_ms(300)

    print("[wifi] Conectando a '{}'...".format(ssid))
    try:
        _sta.connect(ssid, password)
    except OSError as e:
        print("[wifi] No se pudo iniciar la conexion:", e)
        return False

    t0 = time.time()
    while not _sta.isconnected():
        if time.time() - t0 > timeout:
            print("[wifi] Timeout: no se pudo conectar a", ssid)
            try:
                _sta.disconnect()
            except OSError:
                pass
            return False
        time.sleep_ms(300)

    print("[wifi] Conectada. IP:", _sta.ifconfig()[0])
    return True


def connect_any(networks, timeout=20):
    """Conecta a la primera red configurada que este disponible (escanea primero).
    'networks' es una lista [{'ssid':.., 'password':..}, ...]. Util para alternar
    entre, por ejemplo, el WiFi de casa y el de la escuela sin tocar la placa."""
    _sta.active(True)
    if _sta.isconnected():
        return True

    # Que SSIDs hay en rango ahora mismo.
    available = []
    try:
        for n in _sta.scan():
            ssid = n[0].decode() if isinstance(n[0], (bytes, bytearray)) else n[0]
            available.append(ssid)
    except Exception as e:
        print("[wifi] no se pudo escanear:", e)

    for net in networks:
        ssid = net.get("ssid")
        if not ssid:
            continue
        if available and ssid not in available:
            print("[wifi] '{}' fuera de rango, salteo.".format(ssid))
            continue
        if connect(ssid, net.get("password", ""), timeout):
            return True

    print("[wifi] Ninguna red configurada disponible.")
    return False


# ───────────────────────── Portal de configuracion (modo AP) ─────────────────────────

def _url_decode(s):
    """Decodifica un valor application/x-www-form-urlencoded ('+' y %XX)."""
    s = s.replace("+", " ")
    out = ""
    i = 0
    while i < len(s):
        c = s[i]
        if c == "%" and i + 2 < len(s):
            try:
                out += chr(int(s[i + 1:i + 3], 16))
                i += 3
                continue
            except ValueError:
                pass
        out += c
        i += 1
    return out


def _parse_form(body):
    """Convierte 'a=1&b=2' en un dict."""
    data = {}
    for pair in body.split("&"):
        if "=" in pair:
            k, v = pair.split("=", 1)
            data[_url_decode(k)] = _url_decode(v)
    return data


def _html_escape(value):
    value = str(value or "")
    return (value.replace("&", "&amp;")
                 .replace("<", "&lt;")
                 .replace(">", "&gt;")
                 .replace('"', "&quot;"))


def _page(mac, prefill=None):
    """HTML del formulario de configuracion (se ve en el celular del usuario)."""
    prefill = prefill or {}
    device_name = _html_escape(prefill.get("device_name", "ESP32"))
    server_url = _html_escape(prefill.get("server_url", ""))
    device_token = _html_escape(prefill.get("device_token", ""))
    has_web_data = bool(server_url and device_token)
    web_fields = """
 <input type="hidden" name="device_name" value="{device_name}">
 <input type="hidden" name="server_url" value="{server_url}">
 <input type="hidden" name="device_token" value="{device_token}">
 <div class="ok">Datos de PrevCS cargados. Solo falta el WiFi.</div>
""".format(device_name=device_name, server_url=server_url, device_token=device_token) if has_web_data else """
 <label>Nombre de la placa</label>
 <input name="device_name" value="{device_name}" placeholder="Ej: ESP32 Motor 1" required>
 <label>URL del servidor</label>
 <input name="server_url" value="{server_url}" placeholder="http://IP/PreventiveControlSystem/public/api/iot/lectura" required>
 <label>Token de la placa</label>
 <input name="device_token" value="{device_token}" placeholder="Pega aqui el token" required>
""".format(device_name=device_name, server_url=server_url, device_token=device_token)
    rpm_field = '<input name="pulses_per_rev" type="hidden" value="1">' if has_web_data else """
 <label>Pulsos por vuelta del sensor RPM</label>
 <input name="pulses_per_rev" type="number" value="1" min="1">
"""

    return """<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configurar ESP32 - PrevCS</title>
<style>
 body{{font-family:system-ui,Arial,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:18px}}
 .card{{max-width:440px;margin:0 auto;background:#1e293b;border-radius:14px;padding:22px}}
 h1{{font-size:20px;margin:0 0 4px}} p{{color:#94a3b8;font-size:14px;margin:6px 0 16px}}
 .mac{{background:#0b1220;border:1px solid #334155;border-radius:8px;padding:10px;font-family:monospace;
   font-size:16px;color:#38bdf8;text-align:center;letter-spacing:1px;margin-bottom:16px}}
 label{{display:block;font-size:13px;margin:12px 0 4px;color:#cbd5e1}}
 input{{width:100%;box-sizing:border-box;padding:11px;border-radius:8px;border:1px solid #334155;
   background:#0b1220;color:#e2e8f0;font-size:15px}}
 button{{width:100%;margin-top:20px;padding:13px;border:0;border-radius:9px;background:#2563eb;
   color:#fff;font-size:16px;font-weight:600}}
 .ok{{background:#064e3b;color:#d1fae5;border-radius:8px;padding:10px;margin:10px 0 14px;font-size:14px}}
 small{{color:#64748b}}
</style></head><body><div class="card">
<h1>Configurar tu ESP32</h1>
<p>Si abriste desde PrevCS, solo carga el WiFi.</p>
<div class="mac">MAC: {mac}</div>
<form method="post" action="/save">
 {web_fields}
 <label>Red WiFi (SSID)</label>
 <input name="wifi_ssid" placeholder="Nombre de tu WiFi" required>
 <label>Contrasena WiFi</label>
 <input name="wifi_password" type="password" placeholder="Contrasena de tu WiFi">
 {rpm_field}
 <button type="submit">Guardar y reiniciar</button>
</form>
<p><small>La placa se reiniciara y se conectara sola a tu WiFi.</small></p>
</div></body></html>""".format(mac=mac, web_fields=web_fields, rpm_field=rpm_field)


def _ok_page():
    return ("<!DOCTYPE html><html><head><meta charset='utf-8'>"
            "<meta name='viewport' content='width=device-width, initial-scale=1'></head>"
            "<body style='font-family:sans-serif;background:#0f172a;color:#e2e8f0;text-align:center;padding:40px'>"
            "<h2>Configuracion guardada</h2>"
            "<p>La ESP32 se esta reiniciando y se conectara a tu WiFi.</p>"
            "<p>Ya podes cerrar esta pagina.</p></body></html>")


def _query_from_request(req):
    try:
        first = req.split("\r\n", 1)[0]
        path = first.split(" ")[1]
    except Exception:
        return {}

    if "?" not in path:
        return {}

    return _parse_form(path.split("?", 1)[1])


def start_config_portal():
    """Levanta el AP + servidor web de configuracion. No retorna: al guardar
    reinicia la placa (machine.reset())."""
    import socket

    mac = get_sta_mac()

    ap = network.WLAN(network.AP_IF)
    ap.active(True)
    try:
        ap.config(essid=AP_SSID, authmode=network.AUTH_OPEN)
    except Exception:
        ap.config(essid=AP_SSID)

    print("=" * 52)
    print("[portal] Modo configuracion ACTIVO")
    print("[portal] 1) Conectate al WiFi:", AP_SSID)
    print("[portal] 2) Abri en el navegador: http://192.168.4.1")
    print("[portal] MAC de esta placa:", mac)
    print("=" * 52)

    s = socket.socket()
    s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    s.bind(("0.0.0.0", 80))
    s.listen(2)

    while True:
        conn = None
        try:
            conn, addr = s.accept()
            conn.settimeout(8)
            req = conn.recv(2048)
            if not req:
                conn.close()
                continue
            req = req.decode("utf-8", "ignore")

            is_post = req.startswith("POST")
            if is_post and "/save" in req.split("\r\n", 1)[0]:
                # El cuerpo va despues de la linea en blanco.
                body = ""
                if "\r\n\r\n" in req:
                    body = req.split("\r\n\r\n", 1)[1]

                # Si el cuerpo no llego completo, leemos un poco mas.
                clen = _content_length(req)
                while clen and len(body) < clen:
                    chunk = conn.recv(1024)
                    if not chunk:
                        break
                    body += chunk.decode("utf-8", "ignore")

                form = _parse_form(body)
                cfg = {
                    "device_name":   form.get("device_name", "ESP32"),
                    "wifi_ssid":     form.get("wifi_ssid", ""),
                    "wifi_password": form.get("wifi_password", ""),
                    "server_url":    form.get("server_url", ""),
                    "device_token":  form.get("device_token", ""),
                    "pulses_per_rev": int(form.get("pulses_per_rev", "1") or "1"),
                }
                config_manager.save(cfg)

                _respond(conn, _ok_page())
                conn.close()
                print("[portal] Config guardada. Reiniciando en 2s...")
                time.sleep(2)
                machine.reset()
            else:
                _respond(conn, _page(mac, _query_from_request(req)))
        except Exception as e:
            print("[portal] error atendiendo request:", e)
        finally:
            if conn is not None:
                try:
                    conn.close()
                except Exception:
                    pass


def _content_length(req):
    for line in req.split("\r\n"):
        if line.lower().startswith("content-length:"):
            try:
                return int(line.split(":", 1)[1].strip())
            except ValueError:
                return 0
    return 0


def _respond(conn, html):
    conn.send("HTTP/1.1 200 OK\r\n")
    conn.send("Content-Type: text/html; charset=utf-8\r\n")
    conn.send("Connection: close\r\n\r\n")
    conn.send(html)
