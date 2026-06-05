# wifi_manager.py — conexion WiFi (modo normal) y portal de configuracion (modo AP).
#
# Modo normal: connect() une la ESP32 a la red WiFi del cliente.
# Modo AP:     start_config_portal() crea la red "PREVCS-CONFIG", muestra una
#              pagina web donde el usuario carga sus datos (sin saber de codigo),
#              guarda config.json y reinicia la placa.

import time
import network
import machine

import config_manager

AP_SSID = "PREVCS-CONFIG"      # red que crea la placa para configurarse
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
    wifi_ip = _html_escape(prefill.get("wifi_ip", ""))
    has_web_data = bool(server_url and device_token)
    web_fields = """
 <input type="hidden" name="device_name" value="{device_name}">
 <label>URL API PrevCS</label>
 <input name="server_url" value="{server_url}" readonly>
 <label>Token de placa</label>
 <input name="device_token" value="{device_token}" readonly>
""".format(device_name=device_name, server_url=server_url, device_token=device_token) if has_web_data else """
 <label>Nombre de la placa</label>
 <input name="device_name" value="{device_name}" placeholder="Ej: ESP32 Motor 1" required>
 <label>URL API PrevCS</label>
 <input name="server_url" value="{server_url}" placeholder="http://IP/PreventiveControlSystem/public/api/iot/lectura" required>
 <label>Token de la placa</label>
 <input name="device_token" value="{device_token}" placeholder="Pega aqui el token" required>
""".format(device_name=device_name, server_url=server_url, device_token=device_token)

    return """<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configurar ESP32 - PrevCS</title>
<style>
 *{{box-sizing:border-box}} body{{font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif;background:#101820;color:#f4f7f5;margin:0;padding:18px}}
 .card{{max-width:480px;margin:0 auto;background:#16232d;border:1px solid #29404d;border-radius:18px;padding:22px;box-shadow:0 18px 50px rgba(0,0,0,.28)}}
 h1{{font-size:22px;margin:0 0 5px;letter-spacing:-.02em}} p{{color:#a9b8bd;font-size:14px;line-height:1.45;margin:6px 0 16px}}
 .mac{{background:#0c151b;border:1px solid #315465;border-radius:12px;padding:12px;font-family:monospace;
   font-size:16px;color:#65d6ad;text-align:center;letter-spacing:1px;margin-bottom:16px}}
 label{{display:flex;align-items:center;gap:7px;font-size:13px;margin:12px 0 5px;color:#d7e1df;font-weight:650}}
 input{{width:100%;padding:12px 13px;border-radius:10px;border:1px solid #315465;background:#0c151b;color:#f4f7f5;font-size:15px}}
 input:focus{{outline:none;border-color:#65d6ad;box-shadow:0 0 0 3px rgba(101,214,173,.16)}}
 button{{width:100%;margin-top:20px;padding:14px;border:0;border-radius:11px;background:#2f7d68;color:#fff;font-size:16px;font-weight:750}}
 input[readonly]{{color:#65d6ad;font-family:monospace;font-size:12px}}
 .row{{display:grid;grid-template-columns:1fr 110px;gap:10px}}
 .help{{position:relative;display:inline-grid;place-items:center;width:18px;height:18px;border-radius:50%;background:#29404d;color:#f4f7f5;font-size:12px;cursor:help}}
 .help:after{{content:attr(data-tip);position:absolute;left:50%;bottom:135%;transform:translateX(-50%);width:230px;padding:9px 10px;border-radius:10px;background:#f4f7f5;color:#101820;font-size:12px;line-height:1.35;box-shadow:0 10px 30px rgba(0,0,0,.25);opacity:0;pointer-events:none;transition:opacity .15s}}
 .help:hover:after{{opacity:1}}
 small{{display:block;color:#7e9298;margin-top:12px;line-height:1.4}}
</style></head><body><div class="card">
<h1>Configurar SP PrevCS</h1>
<p>Esta pantalla vive dentro de la placa. Carga tu WiFi y deja que PrevCS complete la API cuando abras el link listo.</p>
<div class="mac">MAC: {mac}</div>
<form method="post" action="/save">
 {web_fields}
 <label>Red WiFi (SSID)</label>
 <input name="wifi_ssid" placeholder="Nombre de tu WiFi" required>
 <div class="row">
  <div>
   <label>IP WiFi</label>
   <input name="wifi_ip" value="{wifi_ip}" placeholder="Ej: 192.168.1.50">
  </div>
  <div>
   <label>Pulsos <span class="help" data-tip="Cantidad de pulsos que entrega el sensor RPM por cada vuelta completa del eje. Si hay una sola marca o ranura, usa 1.">?</span></label>
   <input name="pulses_per_rev" type="number" value="1" min="1">
  </div>
 </div>
 <label>Contrasena del WiFi</label>
 <input name="wifi_password" type="password" placeholder="Contrasena de tu WiFi">
 <button type="submit">Guardar y reiniciar</button>
</form>
<small>La placa se reiniciara y se conectara sola a tu WiFi. Si no conecta, volvera a liberar PREVCS-CONFIG.</small>
</div></body></html>""".format(mac=mac, web_fields=web_fields, wifi_ip=wifi_ip)


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
                    "wifi_ip":       form.get("wifi_ip", ""),
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
