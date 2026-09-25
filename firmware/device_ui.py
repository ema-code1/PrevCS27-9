# device_ui.py — capa de estados de la placa: un mismo evento actualiza LCD,
# buzzer y consola. Esto solo REFLEJA lo que ya hace el firmware; no decide
# conexiones, reintentos ni lecturas.
#
# Uso:
#   ui = device_ui.DeviceUI()
#   ui.set(device_ui.CONNECTING_WIFI)
#   ui.set(device_ui.SENSOR_ERROR, "DS18B20 ausente", hold_ms=2500)
#   ui.sensors_ok()
#
# Reglas:
#  - El LCD solo se reescribe cuando cambia el texto (sin flicker, sin clear()
#    dentro del loop).
#  - El buzzer suena por FLANCO y por tipo de alarma: un error que persiste no
#    vuelve a sonar; si se recupera y vuelve a fallar, ahi si suena de nuevo.
#  - Nada bloquea: los patrones corren sobre ticks_ms() + Timer.
#  - Si el LCD no esta conectado, el firmware sigue igual (solo se loguea).

import time

import buzzer as buzzer_module
import lcd1602

BOOTING = "BOOTING"
CONNECTING_WIFI = "CONNECTING_WIFI"
CONNECTING_API = "CONNECTING_API"
CONNECTED = "CONNECTED"
MEASURING = "MEASURING"
SENDING_DATA = "SENDING_DATA"
DATA_SENT = "DATA_SENT"
SENSOR_ERROR = "SENSOR_ERROR"
WIFI_ERROR = "WIFI_ERROR"
API_ERROR = "API_ERROR"
CRITICAL_ERROR = "CRITICAL_ERROR"
# La web respondio que algun sensor quedo fuera de su rango de alerta.
SENSOR_ALERT = "SENSOR_ALERT"

TITLE = "PrevCS"

# Cada cuanto se reintenta el LCD despues de un corte del bus.
LCD_RETRY_MS = 15000

# estado -> (linea 1, linea 2). Maximo 16 caracteres, ASCII simple.
_TEXTS = {
    BOOTING:         (TITLE, "Iniciando..."),
    CONNECTING_WIFI: (TITLE, "Conectando WiFi"),
    CONNECTING_API:  (TITLE, "Conectando API"),
    CONNECTED:       (TITLE, "Conectado!"),
    MEASURING:       (TITLE, "Midiendo..."),
    SENDING_DATA:    (TITLE, "Enviando datos"),
    DATA_SENT:       (TITLE, "Datos enviados"),
    SENSOR_ERROR:    ("ERROR SENSOR", "Revisar sensor"),
    WIFI_ERROR:      ("ERROR WIFI", "Reintentando..."),
    API_ERROR:       ("ERROR API", "Reintentando..."),
    CRITICAL_ERROR:  ("ERROR CRITICO", "Reiniciar placa"),
    SENSOR_ALERT:    ("ALERTA SENSOR", "Fuera de rango"),
}

# Estados que significan "enlace sano": cierran la alarma de conexion.
_HEALTHY = (CONNECTED, DATA_SENT)

# Cada alarma tiene un "tipo": mientras siga vigente no se repite el beep.
# Los tipos son independientes, asi un error de sensor no reactiva el de WiFi.
_ALARMS = {
    SENSOR_ERROR:   ("sensor", buzzer_module.PATTERN_SENSOR_ERROR),
    WIFI_ERROR:     ("conn", buzzer_module.PATTERN_CONN_ERROR),
    API_ERROR:      ("conn", buzzer_module.PATTERN_CONN_ERROR),
    CRITICAL_ERROR: ("critical", buzzer_module.PATTERN_CRITICAL),
    SENSOR_ALERT:   ("limit", buzzer_module.PATTERN_LIMIT),
}


class DeviceUI:
    def __init__(self):
        self.state = None
        self.detail = ""
        self._active = {}        # tipo de alarma -> estado que ya sono
        self._link_ok = False    # ya avisamos "conectado" con 1 beep
        self._pending = None     # texto en espera mientras se retiene otro
        self._hold_until = time.ticks_ms()

        self.buzzer = buzzer_module.Buzzer()

        self.lcd = None
        self._lcd_retry_at = time.ticks_ms()
        try:
            self.lcd = lcd1602.Lcd1602()
            print("[ui] LCD1602 detectado en 0x{:02X}".format(self.lcd.addr))
        except Exception as e:
            # Si no aparece al arranque tampoco se lo da por perdido: puede ser
            # que el modulo todavia no tenga los 5V estables.
            print("[ui] AVISO: LCD no disponible:", e)
            self._lcd_retry_at = time.ticks_add(time.ticks_ms(), LCD_RETRY_MS)

    # --- API principal ---

    def set(self, state, detail="", hold_ms=0):
        """Aplica un estado. Repetirlo no reescribe el LCD ni vuelve a sonar."""
        if state != self.state or detail != self.detail:
            self.state = state
            self.detail = detail
            self._alert(state)
            self._queue(state, detail, hold_ms)
        self.tick()

    def sensors_ok(self):
        """Los sensores volvieron a responder: se rearma su alarma."""
        self._active.pop("sensor", None)

    def limits_ok(self):
        """Todos los sensores volvieron a su rango: se rearma la alarma de limite."""
        self._active.pop("limit", None)

    def message(self, line1, line2=""):
        """Texto libre (MAC, IP, portal) sin pasar por la maquina de estados."""
        self.state = None  # el proximo set() vuelve a escribir aunque repita estado
        self._pending = None
        self._write(line1, line2)

    def tick(self):
        """Avanza buzzer y vuelca el texto retenido. Barato: se puede llamar seguido."""
        self.buzzer.update()
        if self._pending and time.ticks_diff(time.ticks_ms(), self._hold_until) >= 0:
            line1, line2 = self._pending
            self._pending = None
            self._write(line1, line2)

    def wait(self, seconds):
        """Espera cooperativa: no bloquea el buzzer ni deja el LCD desactualizado."""
        t0 = time.ticks_ms()
        total = int(seconds * 1000)
        while time.ticks_diff(time.ticks_ms(), t0) < total:
            self.tick()
            time.sleep_ms(50)

    # --- interno ---

    def _queue(self, state, detail, hold_ms):
        line1, line2 = _TEXTS.get(state, (TITLE, state))
        if state in (SENSOR_ERROR, SENSOR_ALERT) and detail:
            line2 = detail

        now = time.ticks_ms()
        if time.ticks_diff(now, self._hold_until) < 0:
            # Hay un mensaje retenido en pantalla: este espera su turno.
            self._pending = (line1, line2)
            return

        self._write(line1, line2)
        self._pending = None
        if hold_ms:
            self._hold_until = time.ticks_add(now, hold_ms)

    def _write(self, line1, line2):
        if self.lcd is None:
            self._reconnect()
        if self.lcd is None:
            return
        try:
            self.lcd.show(line1, line2)
        except Exception as e:
            # Antes esto apagaba el LCD para siempre: un solo corte del bus
            # dejaba la placa sin cartel hasta el proximo reinicio, que es
            # justo lo que se ve como "la pantalla no anda". Ahora se apaga y
            # se vuelve a intentar cada LCD_RETRY_MS.
            print("[ui] LCD sin respuesta, reintento en {}s:".format(LCD_RETRY_MS // 1000), e)
            self.lcd = None
            self._lcd_retry_at = time.ticks_add(time.ticks_ms(), LCD_RETRY_MS)

    def _reconnect(self):
        """Reintenta tomar el LCD, pero no en cada vuelta del loop: un scan I2C
        por segundo contra un modulo que no esta solo gasta tiempo del ciclo."""
        if time.ticks_diff(time.ticks_ms(), self._lcd_retry_at) < 0:
            return
        self._lcd_retry_at = time.ticks_add(time.ticks_ms(), LCD_RETRY_MS)
        try:
            self.lcd = lcd1602.Lcd1602()
            print("[ui] LCD recuperado en 0x{:02X}".format(self.lcd.addr))
        except Exception:
            self.lcd = None

    def _alert(self, state):
        alarm = _ALARMS.get(state)
        if alarm is None:
            if state in _HEALTHY:
                self._active.pop("conn", None)
                if not self._link_ok:
                    self._link_ok = True
                    self.buzzer.play(buzzer_module.PATTERN_OK)
            return

        kind, pattern = alarm
        if kind == "conn":
            self._link_ok = False
        if self._active.get(kind) != state:
            self._active[kind] = state
            self.buzzer.play(pattern)
