# wifi_manager.py — conexion WiFi (modo normal) y portal de configuracion (modo AP).
#
# Modo normal: connect() une la ESP32 a la red WiFi del cliente.
# Modo AP:     start_config_portal() crea la red "PREVCS-CONFIG", muestra un
#              formulario con los estilos de PrevCS donde el usuario carga su
#              WiFi (el resto viene precargado por el QR/boton de la web),
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
    Formato AA:BB:CC:DD:EE:FF. La placa se autovincula con esta MAC en su 1er dato."""
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

def _scan_networks():
    """Lista de SSIDs en rango (unicos, mejor senal primero). El ESP32 es 2.4GHz
    por hardware, asi que todo lo que escanea ya es 2.4GHz."""
    try:
        _sta.active(True)
        seen = []
        nets = []
        for n in _sta.scan():
            ssid = n[0].decode() if isinstance(n[0], (bytes, bytearray)) else n[0]
            rssi = n[3] if len(n) > 3 else -100
            if ssid and ssid not in seen:
                seen.append(ssid)
                nets.append((rssi, ssid))
        nets.sort(reverse=True)  # mejor senal primero
        return [s for _, s in nets]
    except Exception as e:
        print("[portal] no se pudo escanear WiFi:", e)
        return []


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
    """Escapa un valor para usarlo dentro de un atributo HTML (value="...")."""
    return (str(value)
            .replace("&", "&amp;").replace('"', "&quot;")
            .replace("<", "&lt;").replace(">", "&gt;"))


# Icono "ojo" (mostrar/ocultar) reutilizado en los inputs sensibles.
_EYE = ('<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" '
        'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'
        '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>')

# Plantilla del formulario. Usa marcadores __X__ (no .format) para que las llaves
# del CSS/JS queden literales. Estilos = los de PrevCS (tema claro, verde musgo).
_PAGE_TPL = """<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configurar placa - PrevCS</title>
<style>
 *{box-sizing:border-box}
 body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#FBFAF7;color:#2A2C32;margin:0;padding:24px 18px;line-height:1.45;min-height:100vh;display:flex;align-items:flex-start;justify-content:center}
 .card{width:100%;max-width:440px;background:#FCFBF7;border:1px solid rgba(42,44,50,.08);border-radius:18px;padding:26px 24px;box-shadow:0 18px 48px -12px rgba(42,44,50,.16)}
 .brand{display:flex;align-items:center;gap:10px;margin-bottom:18px}
 .brand__logo{display:inline-grid;grid-template-columns:repeat(2,6px);gap:2px;padding:5px;border-radius:8px;background:#2A2C32}
 .brand__logo i{width:6px;height:6px;border-radius:1px;background:#FBFAF7;display:block}
 .brand b{font-size:14px;letter-spacing:-.02em}
 h1{font-size:20px;margin:0 0 18px;letter-spacing:-.02em}
 label{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:#4E5158;margin:16px 0 6px}
 input,select{width:100%;padding:11px 12px;border-radius:10px;border:1px solid rgba(42,44,50,.13);background:#fff;color:#2A2C32;font-size:15px;font-family:inherit}
 input:focus,select:focus{outline:none;border-color:#5b7a3c;box-shadow:0 0 0 3px rgba(91,122,60,.16)}
 input[readonly]{background:#F4F2EC;color:#4E5158}
 .field{position:relative}
 .field input{padding-right:46px}
 .eye{position:absolute;top:50%;right:7px;transform:translateY(-50%);width:32px;height:32px;display:grid;place-items:center;border:0;background:transparent;color:#8A8C92;cursor:pointer;border-radius:8px;padding:0}
 .eye:hover{color:#2A2C32;background:#F0EEE8}
 .eye.is-on{color:#5b7a3c}
 .help{position:relative;display:inline-grid;place-items:center;width:17px;height:17px;border-radius:50%;border:0;background:#E7E5DE;color:#6F7278;font-size:11px;font-weight:700;cursor:pointer;padding:0}
 .help::after{content:attr(data-tip);position:absolute;left:50%;bottom:155%;transform:translateX(-50%);width:210px;max-width:62vw;padding:9px 11px;border-radius:10px;background:#2A2C32;color:#FBFAF7;font-size:12px;font-weight:400;line-height:1.4;box-shadow:0 12px 28px rgba(42,44,50,.22);opacity:0;pointer-events:none;transition:opacity .14s;z-index:5}
 .help.is-open::after{opacity:1}
 details{margin-top:18px;border-top:1px solid rgba(42,44,50,.08);padding-top:12px}
 details summary{cursor:pointer;font-size:13px;color:#6F7278;font-weight:600}
 .adv small{display:block;color:#8A8C92;font-size:12px;margin-top:6px}
 .submit{width:100%;margin-top:24px;padding:13px;border:0;border-radius:999px;background:#2A2C32;color:#FBFAF7;font-size:15px;font-weight:600;cursor:pointer}
 .submit:active{transform:translateY(1px)}
</style></head><body><div class="card">
<div class="brand"><span class="brand__logo"><i></i><i></i><i></i><i></i></span><b>PrevCS IoT</b></div>
<h1>Configura tu placa</h1>
<form method="post" action="/save">
 <input type="hidden" name="server_url" value="__SERVER_URL__">
 <input type="hidden" name="return_url" value="__RETURN_URL__">

 <label>Etiqueta de la placa <button type="button" class="help" data-tip="Un nombre para reconocer la placa en tu panel. Ej: Extrusora 1.">?</button></label>
 <input name="device_name" value="__DEVICE_NAME__" placeholder="Ej: Extrusora 1" required>

 <label>Red WiFi <button type="button" class="help" data-tip="La red WiFi (2.4 GHz) a la que se conectara la placa para enviar datos.">?</button></label>
 __SSID_FIELD__

 <label>Contrasena del WiFi <button type="button" class="help" data-tip="La clave de tu red WiFi. Toca el ojo para verla mientras la escribis.">?</button></label>
 <div class="field"><input id="pw" name="wifi_password" type="password" placeholder="Clave de tu red"><button type="button" class="eye" data-target="pw" aria-label="Mostrar u ocultar">__EYE__</button></div>

 <label>Token de la placa <button type="button" class="help" data-tip="Identifica y autoriza la placa ante el servidor. Es secreto: no lo compartas.">?</button></label>
 <div class="field"><input id="tok" name="device_token" type="password" value="__DEVICE_TOKEN__" __TOKEN_RO__ placeholder="Pega el token de la web"><button type="button" class="eye" data-target="tok" aria-label="Mostrar u ocultar">__EYE__</button></div>

 <details>
  <summary>Mi red no aparece / opciones avanzadas</summary>
  <div class="adv">
   <label>Escribi el nombre de tu red (SSID)</label>
   <input name="wifi_ssid_other" placeholder="Solo si no esta en la lista de arriba">
   <label>Pulsos por vuelta (sensor RPM) <button type="button" class="help" data-tip="Pulsos que cuenta el sensor RPM por cada vuelta del eje. Una sola ranura = 1.">?</button></label>
   <input name="pulses_per_rev" type="number" value="__PULSES__" min="1">
  </div>
 </details>

 <button type="submit" class="submit">Guardar y reiniciar</button>
</form>
</div>
<script>
(function(){
  function closeTips(except){
    var hs=document.querySelectorAll('.help');
    for(var i=0;i<hs.length;i++){ if(hs[i]!==except) hs[i].classList.remove('is-open'); }
  }
  var helps=document.querySelectorAll('.help');
  for(var i=0;i<helps.length;i++){
    helps[i].addEventListener('click',function(e){
      e.preventDefault(); e.stopPropagation();
      var open=this.classList.contains('is-open');
      closeTips(this);
      this.classList.toggle('is-open', !open);
    });
  }
  document.addEventListener('click',function(){ closeTips(null); });
  var eyes=document.querySelectorAll('.eye');
  for(var j=0;j<eyes.length;j++){
    eyes[j].addEventListener('click',function(){
      var inp=document.getElementById(this.getAttribute('data-target'));
      if(!inp) return;
      var show=inp.type==='password';
      inp.type=show?'text':'password';
      this.classList.toggle('is-on', show);
    });
  }
})();
</script>
</body></html>"""

# Pantalla final (tras guardar). Boton llamativo para volver a la app, sin pedir
# que el usuario adivine que tiene que cerrar/recargar.
_OK_TPL = """<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Listo - PrevCS</title>
<style>
 *{box-sizing:border-box}
 body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#FBFAF7;color:#2A2C32;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
 .card{width:100%;max-width:420px;background:#FCFBF7;border:1px solid rgba(42,44,50,.08);border-radius:18px;padding:32px 26px;text-align:center;box-shadow:0 18px 48px -12px rgba(42,44,50,.16)}
 .check{width:56px;height:56px;margin:0 auto 16px;border-radius:50%;background:rgba(91,122,60,.14);display:grid;place-items:center;color:#5b7a3c}
 h2{margin:0 0 8px;font-size:21px;letter-spacing:-.02em}
 p{color:#6F7278;font-size:14px;margin:0 0 6px}
 .btn{display:inline-block;margin-top:22px;padding:13px 24px;border-radius:999px;background:#2A2C32;color:#FBFAF7;font-size:15px;font-weight:600;text-decoration:none}
 .btn:active{transform:translateY(1px)}
</style></head><body><div class="card">
<div class="check"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
<h2>Listo, configuraste la placa</h2>
<p>Se esta reiniciando y se conectara sola a tu WiFi.</p>
<p>Va a aparecer en tu espacio apenas mande su primer dato.</p>
__BUTTON__
</div></body></html>"""


def _page(prefill=None, networks=None):
    """HTML del formulario (se ve en el celular del usuario). El usuario SOLO
    completa su WiFi; etiqueta/token vienen precargados (editables) y la URL del
    servidor va oculta. Token y contrasena tienen ojo de mostrar/ocultar y cada
    campo tiene un '?' con su explicacion."""
    prefill = prefill or {}
    networks = networks or []
    device_name  = _html_escape(prefill.get("device_name", ""))
    server_url   = _html_escape(prefill.get("server_url", ""))
    return_url   = _html_escape(prefill.get("return_url", ""))
    device_token = _html_escape(prefill.get("device_token", ""))
    pulses       = _html_escape(prefill.get("pulses_per_rev", "") or "1")
    token_ro     = "readonly" if device_token else ""

    # Desplegable de redes WiFi escaneadas por la placa (+ opcion "otra red").
    if networks:
        opts = '<option value="" disabled selected>Elegi tu red...</option>'
        for ssid in networks:
            s = _html_escape(ssid)
            opts += '<option value="' + s + '">' + s + '</option>'
        opts += '<option value="__other__">Otra red (la escribo abajo)...</option>'
        ssid_field = '<select name="wifi_ssid" required>' + opts + '</select>'
    else:
        ssid_field = '<input name="wifi_ssid" placeholder="Nombre de tu red WiFi (2.4 GHz)" required>'

    html = _PAGE_TPL
    html = html.replace("__SSID_FIELD__", ssid_field)
    html = html.replace("__DEVICE_NAME__", device_name)
    html = html.replace("__SERVER_URL__", server_url)
    html = html.replace("__RETURN_URL__", return_url)
    html = html.replace("__DEVICE_TOKEN__", device_token)
    html = html.replace("__TOKEN_RO__", token_ro)
    html = html.replace("__PULSES__", pulses)
    html = html.replace("__EYE__", _EYE)
    return html


def _ok_page(return_url="", server_url=""):
    """Pantalla final con boton 'Volver a mis espacios'. El destino lo da la web
    (return_url); si no vino, se deriva del server_url (.../api/iot/lectura ->
    .../registros-iot)."""
    back = (return_url or "").strip()
    if not back and server_url:
        b = server_url.strip()
        for suf in ("/api/iot/lectura/", "/api/iot/lectura"):
            if b.endswith(suf):
                b = b[:-len(suf)]
                break
        if b:
            back = b + "/registros-iot"

    if back:
        button = '<a class="btn" href="' + _html_escape(back) + '">Volver a mis espacios</a>'
    else:
        button = '<p style="margin-top:18px">Ya podes cerrar esta pagina.</p>'

    return _OK_TPL.replace("__BUTTON__", button)


def start_config_portal():
    """Levanta el AP + servidor web de configuracion. No retorna: al guardar
    reinicia la placa (machine.reset())."""
    import socket

    mac = get_sta_mac()
    # Escaneamos una vez las redes en rango para ofrecerlas en el desplegable.
    networks = _scan_networks()

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
    print("[portal] Redes WiFi en rango:", len(networks))
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

                # SSID: del desplegable o, si eligio "otra red", del texto manual.
                ssid = form.get("wifi_ssid", "")
                if ssid in ("", "__other__"):
                    ssid = form.get("wifi_ssid_other", "")
                ssid = ssid.strip()

                # URL del servidor: la precarga la web (QR/boton). Si no vino, cae
                # al valor fijo de config_manager.DEFAULT_SERVER_URL (si se definio).
                server_url = form.get("server_url", "").strip() or config_manager.DEFAULT_SERVER_URL
                return_url = form.get("return_url", "").strip()

                cfg = {
                    "device_name":   form.get("device_name", "ESP32"),
                    "wifi_ssid":     ssid,
                    "wifi_password": form.get("wifi_password", ""),
                    "server_url":    server_url,
                    "device_token":  form.get("device_token", ""),
                    "pulses_per_rev": int(form.get("pulses_per_rev", "1") or "1"),
                }
                config_manager.save(cfg)

                _respond(conn, _ok_page(return_url, server_url))
                conn.close()
                print("[portal] Config guardada. Reiniciando en 2s...")
                time.sleep(2)
                machine.reset()
            else:
                # La web manda server_url/device_token/device_name/return_url como
                # query (?a=b&c=d) en el link/QR. Los precargamos para que el
                # usuario no tipee nada de eso.
                prefill = {}
                first_line = req.split("\r\n", 1)[0]
                parts = first_line.split(" ")
                if len(parts) >= 2 and "?" in parts[1]:
                    prefill = _parse_form(parts[1].split("?", 1)[1])
                _respond(conn, _page(prefill, networks))
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
