# test_lcd_buzzer.py — prueba SOLO la pantalla LCD1602 y el buzzer.
#
# No toca sensores, ni WiFi, ni la API. Sirve para verificar el armado antes de
# confiar en los avisos del firmware.
#
# Como usarlo en Thonny:
#   1) Subi este archivo a la placa (junto a pins.py, lcd1602.py y buzzer.py).
#   2) Abrilo y apreta Run (F5).
#   Alternativa desde la consola:  import test_lcd_buzzer ; test_lcd_buzzer.run()
#
# El orden de las pruebas no es casual: va de lo que se rompe mas seguido a lo
# que se rompe menos.
#   1) Scan I2C          -> ¿llega la senal a la ESP32?
#   2) Bloques solidos   -> ¿el panel tiene tension y contraste?
#   3) Textos            -> ¿escribe bien lo que le mandamos?
#   4) Luz de fondo      -> ¿le podemos dar ordenes por I2C?
#   5) Buzzer            -> los 4 patrones del firmware.
#
# Si el paso 2 no muestra NADA pero el 1 encontro la direccion, el problema no
# es de codigo: es el potenciometro de contraste o la tension del modulo. Ojo
# con esto: el chip I2C (PCF8574) contesta perfecto a 3.3V, pero el panel
# HD44780 necesita 5V para que los caracteres se vean. Alimentado a 3.3V se
# prende la luz azul y no aparece ninguna letra, por mas que gires el
# potenciometro y el scan encuentre el 0x27.

import time
from machine import Pin, I2C

import pins
import lcd1602
import buzzer as buzzer_module

PAUSA_MS = 1500   # cuanto queda cada mensaje en pantalla

# Los mismos textos que usa el firmware, mas una regla para ver las 16 columnas.
PANTALLAS = (
    ("PrevCS",           "Iniciando..."),
    ("PrevCS",           "Conectando WiFi"),
    ("PrevCS",           "Conectando API"),
    ("PrevCS",           "Conectado!"),
    ("PrevCS",           "Midiendo..."),
    ("PrevCS",           "Enviando datos"),
    ("PrevCS",           "Datos enviados"),
    ("ERROR WIFI",       "Reintentando..."),
    ("ERROR API",        "Reintentando..."),
    ("ERROR SENSOR",     "TEMP DS18B20"),
    ("1234567890123456", "ABCDEFGHIJKLMNOP"),   # las 16 columnas de cada linea
)

SONIDOS = (
    ("1 beep: OK",       buzzer_module.PATTERN_OK),
    ("2 beeps: sensor",  buzzer_module.PATTERN_SENSOR_ERROR),
    ("3 beeps: enlace",  buzzer_module.PATTERN_CONN_ERROR),
    ("1 largo: critico", buzzer_module.PATTERN_CRITICAL),
)


def test_i2c():
    """Escanea el bus con el cableado esperado y, si no hay nada, tambien con
    SDA y SCL al reves, que es el error de armado mas comun."""
    ordenes = (
        ("normal",  pins.PIN_LCD_SDA, pins.PIN_LCD_SCL),
        ("cruzado", pins.PIN_LCD_SCL, pins.PIN_LCD_SDA),
    )

    for nombre, sda, scl in ordenes:
        print("[test] Scan I2C ({}): SDA=GPIO{} SCL=GPIO{}".format(nombre, sda, scl))
        try:
            found = I2C(0, sda=Pin(sda), scl=Pin(scl), freq=pins.LCD_I2C_FREQ).scan()
        except Exception as e:
            print("[test]   ERROR creando el bus:", e)
            continue

        if not found:
            print("[test]   nada")
            continue

        print("[test]   encontrado:", [hex(a) for a in found])
        if nombre == "cruzado":
            print("[test]   OJO: responde con los cables al reves. Dalos vuelta,")
            print("[test]   o corregi PIN_LCD_SDA / PIN_LCD_SCL en pins.py.")
        return found

    print("[test] No aparecio ningun dispositivo. Revisar 5V/GND del LCD, el")
    print("[test] conversor de nivel (LV a 3.3V, HV a 5V, los tres GND unidos)")
    print("[test] y que los cables SDA/SCL no esten flojos.")
    return []


def test_lcd():
    """Devuelve el LCD listo, o None si no se pudo tomar."""
    try:
        lcd = lcd1602.Lcd1602()
    except Exception as e:
        print("[test] LCD: NO DETECTADO ({}). Sigo con el buzzer.".format(e))
        return None

    print("[test] LCD listo en 0x{:02X}".format(lcd.addr))

    # 1) Bloques: se ven aunque el texto este mal, y solo dependen de tension y
    #    contraste. Es la prueba que separa "no escribe" de "no se ve".
    print("[test] LCD -> bloques solidos (prueba de contraste)")
    print("[test]   Si NO ves dos filas de rectangulos oscuros, gira el")
    print("[test]   potenciometro del modulo de tope a tope ahora mismo.")
    print("[test]   Si aun asi no aparece nada, el panel necesita 5V.")
    lcd.test_pattern()
    time.sleep_ms(3000)

    # 2) Textos reales del firmware.
    for line1, line2 in PANTALLAS:
        print("[test] LCD -> {} | {}".format(line1, line2))
        lcd.show(line1, line2)
        time.sleep_ms(PAUSA_MS)

    # 3) Luz de fondo: si parpadea, el modulo obedece ordenes por I2C.
    lcd.show("Luz de fondo", "apagando...")
    time.sleep_ms(800)
    lcd.backlight(False)
    time.sleep_ms(800)
    lcd.backlight(True)
    lcd.show("LCD OK", "Ahora el buzzer")
    time.sleep_ms(PAUSA_MS)
    print("[test] LCD: OK")
    return lcd


def test_buzzer(lcd=None):
    """Prueba primero el pin a mano y despues los 4 patrones del firmware."""
    print("[test] Buzzer en GPIO{}: prueba directa (0.5s encendido).".format(
        pins.PIN_BUZZER))
    if lcd:
        lcd.show("Buzzer", "Prueba directa")

    pin = Pin(pins.PIN_BUZZER, Pin.OUT)
    pin.value(1)
    time.sleep_ms(500)
    pin.value(0)
    time.sleep_ms(700)

    zumbador = buzzer_module.Buzzer()
    for etiqueta, patron in SONIDOS:
        print("[test] Buzzer ->", etiqueta)
        if lcd:
            lcd.show("Buzzer", etiqueta)
        zumbador.play(patron)
        _esperar(zumbador)
        time.sleep_ms(700)

    zumbador.stop()
    print("[test] Buzzer: OK")


def _esperar(zumbador, timeout_ms=5000):
    """Espera a que termine el patron. Llama a update() por las dudas: si la
    placa no tenia un Timer libre, nadie mas lo esta avanzando."""
    t0 = time.ticks_ms()
    while zumbador.is_busy():
        zumbador.update()
        if time.ticks_diff(time.ticks_ms(), t0) > timeout_ms:
            print("[test] AVISO: el patron no termino, corto por timeout.")
            zumbador.stop()
            return
        time.sleep_ms(20)


def run():
    print("\n" + "=" * 52)
    print("[test] Prueba de LCD1602 + buzzer (no usa sensores ni WiFi)")
    print("=" * 52)

    test_i2c()
    lcd = test_lcd()
    test_buzzer(lcd)

    if lcd:
        lcd.show("Test terminado", "LCD y buzzer OK")
    print("[test] Listo. Si viste los bloques, todos los mensajes y escuchaste")
    print("[test] los 4 sonidos, el hardware esta bien y podes correr main.py.")


if __name__ == "__main__":
    run()
