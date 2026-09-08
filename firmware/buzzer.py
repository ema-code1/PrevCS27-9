# buzzer.py — buzzer activo en GPIO26 (via 1k -> base del BC337).
#
# Los patrones NO bloquean: se guardan como una lista de duraciones
# (ON, OFF, ON, ...) y avanzan con ticks_ms(). Un machine.Timer periodico llama
# a update() cada pocos ms, asi que los beeps suenan igual mientras el firmware
# esta midiendo la ventana de sensores o esperando una respuesta HTTP.

import time
from machine import Pin, Timer

import pins

_SHORT_ON = 120
_SHORT_OFF = 130
_LONG_ON = 900

# Patrones: duraciones alternadas empezando por ON (ms).
PATTERN_OK = (_SHORT_ON,)
PATTERN_SENSOR_ERROR = (_SHORT_ON, _SHORT_OFF, _SHORT_ON)
PATTERN_CONN_ERROR = (_SHORT_ON, _SHORT_OFF, _SHORT_ON, _SHORT_OFF, _SHORT_ON)
PATTERN_CRITICAL = (_LONG_ON,)

_TICK_MS = 20


class Buzzer:
    def __init__(self, pin=pins.PIN_BUZZER, timer_id=1):
        self.pin = Pin(pin, Pin.OUT)
        self.pin.value(0)
        self._pattern = None
        self._index = 0
        self._t0 = time.ticks_ms()
        self._timer = None
        try:
            self._timer = Timer(timer_id)
            self._timer.init(period=_TICK_MS, mode=Timer.PERIODIC,
                             callback=self._on_tick)
        except Exception as e:
            # Sin timer disponible seguimos andando: main() tambien llama update().
            print("[buzzer] sin timer de hardware:", e)

    def _on_tick(self, _timer):
        self.update()

    def play(self, pattern):
        """Arranca un patron (corta el anterior si habia uno sonando)."""
        if not pattern:
            return
        self._pattern = pattern
        self._index = 0
        self._t0 = time.ticks_ms()
        self.pin.value(1)

    def stop(self):
        self._pattern = None
        self.pin.value(0)

    def is_busy(self):
        return self._pattern is not None

    def update(self):
        """Avanza la maquina de estados. Seguro de llamar todas las veces que sea."""
        pattern = self._pattern
        if pattern is None:
            return
        if time.ticks_diff(time.ticks_ms(), self._t0) < pattern[self._index]:
            return
        self._index += 1
        if self._index >= len(pattern):
            self._pattern = None
            self.pin.value(0)
            return
        self._t0 = time.ticks_ms()
        # Indices pares = ON, impares = OFF.
        self.pin.value(0 if self._index % 2 else 1)
