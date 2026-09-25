# pantalla.py — prueba directa del LCD1602. UN SOLO ARCHIVO.
#
# No importa nada del firmware (ni pins, ni lcd1602, ni buzzer): no hace falta
# que haya ningun otro .py en la placa. Abrilo en Thonny y apreta F5.
#
# Cableado que asume: VCC a 5V, GND a GND, SDA a GPIO32, SCL a GPIO33.

import time
from machine import Pin, I2C

SDA = 32
SCL = 33
COLS = 16

RS = 0x01
EN = 0x04
LUZ = 0x08

i2c = I2C(0, sda=Pin(SDA), scl=Pin(SCL), freq=50000)

encontrados = i2c.scan()
print("I2C:", [hex(a) for a in encontrados])
if not encontrados:
    print("No hay nada en el bus. Revisar VCC 5V, GND comun y los cables 32/33.")
    raise SystemExit

ADDR = encontrados[0]
print("Usando", hex(ADDR))


def escribir(dato):
    i2c.writeto(ADDR, bytes([dato | LUZ]))


def pulso(dato):
    escribir(dato | EN)
    time.sleep_us(1)
    escribir(dato & ~EN)
    time.sleep_us(50)


def enviar(valor, rs):
    pulso((valor & 0xF0) | rs)
    pulso(((valor << 4) & 0xF0) | rs)


def comando(cmd):
    enviar(cmd, 0)
    time.sleep_ms(3)


def iniciar():
    time.sleep_ms(50)
    for _ in range(3):        # arranque en 8 bits que pide el HD44780
        pulso(0x30)
        time.sleep_ms(5)
    pulso(0x20)               # y recien ahi se pasa a 4 bits
    time.sleep_ms(5)
    comando(0x28)             # 4 bits, 2 lineas, 5x8
    comando(0x0C)             # display encendido, sin cursor
    comando(0x06)             # el cursor avanza solo
    comando(0x01)             # limpiar


def linea(fila, texto):
    comando(0x80 | (0x40 if fila else 0x00))
    texto = texto[:COLS]
    texto += " " * (COLS - len(texto))   # sin ljust: no existe en MicroPython
    for ch in texto:
        enviar(ord(ch), RS)


def mostrar(l1, l2=""):
    linea(0, l1)
    linea(1, l2)
    print("LCD:", l1, "|", l2)


def bloques():
    for fila in (0, 1):
        comando(0x80 | (0x40 if fila else 0x00))
        for _ in range(COLS):
            enviar(0xFF, RS)


iniciar()

# Bloques primero: se ven aunque el texto salga mal, y solo dependen de la
# tension y del contraste.
print("BLOQUES 4s: si no ves dos filas de rectangulos, gira el potenciometro AHORA.")
bloques()
time.sleep(4)

MENSAJES = (
    ("HOLA JOSE", "LA PANTALLA VA"),
    ("PrevCS", "Iniciando..."),
    ("PrevCS", "Conectado!"),
    ("1234567890123456", "ABCDEFGHIJKLMNOP"),
)

for l1, l2 in MENSAJES:
    mostrar(l1, l2)
    time.sleep(2)

mostrar("LCD OK", "Anda")
print("Listo.")
