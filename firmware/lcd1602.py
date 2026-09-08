# lcd1602.py — driver minimo para LCD1602 con backpack I2C (PCF8574 + HD44780).
#
# No usa librerias externas: MicroPython no trae una y agregar una dependencia
# entera para 16x2 caracteres no se justifica. Solo modo 4 bits y texto ASCII.
#
# Bits del PCF8574 -> HD44780:
#   P0=RS  P1=RW  P2=EN  P3=backlight  P4..P7=D4..D7

import time
from machine import Pin, I2C

import pins

_RS = 0x01
_EN = 0x04
_BACKLIGHT = 0x08

_CMD_CLEAR = 0x01
_CMD_HOME = 0x02
_CMD_ENTRY = 0x06          # incrementa cursor, sin shift
_CMD_DISPLAY_ON = 0x0C     # display on, cursor off, blink off
_CMD_FUNCTION_4BIT = 0x28  # 4 bits, 2 lineas, 5x8
_DDRAM = 0x80

_ROW_OFFSET = (0x00, 0x40)


def detect_address(i2c, candidates=None):
    """Devuelve la direccion del LCD presente en el bus, o None si no hay ninguna."""
    try:
        found = i2c.scan()
    except Exception:
        return None
    for addr in (candidates or pins.LCD_I2C_CANDIDATES):
        if addr in found:
            return addr
    return found[0] if found else None


class Lcd1602:
    """LCD 16x2. write_line() solo toca el display si el texto cambio, asi que
    se puede llamar en cada vuelta del loop sin parpadeo ni clear() constante."""

    def __init__(self, i2c=None, addr=None, cols=pins.LCD_COLS, rows=pins.LCD_ROWS):
        self.cols = cols
        self.rows = rows
        self._backlight = _BACKLIGHT
        self._shown = [None] * rows

        self.i2c = i2c or I2C(0, scl=Pin(pins.PIN_LCD_SCL),
                              sda=Pin(pins.PIN_LCD_SDA), freq=pins.LCD_I2C_FREQ)
        self.addr = addr if addr is not None else pins.LCD_I2C_ADDRESS
        if self.addr is None:
            self.addr = detect_address(self.i2c)
        if self.addr is None:
            raise OSError("LCD I2C no encontrado")

        self._init_display()

    # --- bajo nivel ---

    def _write_raw(self, data):
        self.i2c.writeto(self.addr, bytes([data | self._backlight]))

    def _pulse(self, data):
        self._write_raw(data | _EN)
        time.sleep_us(1)
        self._write_raw(data & ~_EN)
        time.sleep_us(50)

    def _send(self, value, rs):
        high = (value & 0xF0) | rs
        low = ((value << 4) & 0xF0) | rs
        self._pulse(high)
        self._pulse(low)

    def _command(self, cmd):
        self._send(cmd, 0)

    def _init_display(self):
        time.sleep_ms(50)
        # Secuencia de arranque en 8 bits del HD44780 antes de pasar a 4 bits.
        for _ in range(3):
            self._pulse(0x30)
            time.sleep_ms(5)
        self._pulse(0x20)
        time.sleep_ms(5)

        self._command(_CMD_FUNCTION_4BIT)
        self._command(_CMD_DISPLAY_ON)
        self._command(_CMD_ENTRY)
        self.clear()

    # --- uso normal ---

    def clear(self):
        self._command(_CMD_CLEAR)
        time.sleep_ms(2)
        self._shown = [None] * self.rows

    def backlight(self, on=True):
        self._backlight = _BACKLIGHT if on else 0x00
        self._write_raw(0x00)

    def write_line(self, row, text):
        """Escribe una linea completa (rellenada a 16 caracteres).
        Si ya dice lo mismo, no hace nada: eso evita el flicker."""
        if row >= self.rows:
            return False
        text = _ascii(text)[:self.cols].ljust(self.cols)
        if self._shown[row] == text:
            return False
        self._command(_DDRAM | _ROW_OFFSET[row])
        for ch in text:
            self._send(ord(ch), _RS)
        self._shown[row] = text
        return True

    def show(self, line1, line2=""):
        """Muestra las dos lineas. Devuelve True si algo cambio en pantalla."""
        changed = self.write_line(0, line1)
        if self.rows > 1:
            changed = self.write_line(1, line2) or changed
        return changed


def _ascii(text):
    """El HD44780 no tiene acentos ni simbolos raros: los pasamos a ASCII simple
    para no imprimir caracteres basura."""
    out = ""
    for ch in str(text):
        code = ord(ch)
        if 32 <= code < 127:
            out += ch
        else:
            out += _EQUIV.get(ch, "?")
    return out


_EQUIV = {
    "á": "a", "é": "e", "í": "i", "ó": "o", "ú": "u",
    "Á": "A", "É": "E", "Í": "I", "Ó": "O", "Ú": "U",
    "ñ": "n", "Ñ": "N", "ü": "u", "Ü": "U", "°": " ",
}
