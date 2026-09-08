# i2c_lcd_check.py
# Subir a la ESP32 y ejecutar desde Thonny para revisar el LCD I2C.

from machine import Pin, I2C
import time

TESTS = (
    ("normal", 21, 22),   # SDA=GPIO21, SCL=GPIO22
    ("cruzado", 22, 21),  # SDA=GPIO22, SCL=GPIO21
)


def scan_i2c(name, sda_pin, scl_pin):
    print("")
    print("Prueba:", name)
    print("SDA=GPIO{}".format(sda_pin))
    print("SCL=GPIO{}".format(scl_pin))

    try:
        i2c = I2C(0, sda=Pin(sda_pin), scl=Pin(scl_pin), freq=100000)
        found = i2c.scan()
    except Exception as e:
        print("ERROR bus I2C:", e)
        return

    if not found:
        print("Nada detectado")
        return

    print("Detectado:")
    for addr in found:
        print(" - 0x{:02X}".format(addr))


print("Chequeo LCD I2C")
print("LCD esperado: 0x27 o 0x3F")
print("Cable esperado: SDA GPIO21 / SCL GPIO22")

for test in TESTS:
    scan_i2c(*test)
    time.sleep_ms(300)

print("")
print("Fin.")
