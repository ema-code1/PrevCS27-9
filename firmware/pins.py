# pins.py — mapa unico de GPIO de la placa. Cualquier cambio de cableado se hace
# SOLO aca; el resto del firmware importa estas constantes y nunca numeros sueltos.
#
# Cableado actual (ESP32 de 38 pines, todo a 3.3V salvo el LCD):
#   SW-420 (vibracion)  DO   -> GPIO34   (solo-entrada, sin pull interno)
#   LM393 RPM           DO   -> GPIO25   (cuenta de pulsos por interrupcion)
#   LM393 RPM           AO   -> GPIO35   (ADC1, solo-entrada)
#   DS18B20 (temp)      DATA -> GPIO27   (OneWire, 4.7k a 3.3V)
#   LCD1602 I2C 5V      SDA  -> GPIO21   (via conversor de nivel 3.3V<->5V)
#   LCD1602 I2C 5V      SCL  -> GPIO22   (via conversor de nivel 3.3V<->5V)
#   Buzzer activo 3.3V       -> GPIO26 -> 1k -> base BC337
#
# RESERVADOS: GPIO21, GPIO22 y GPIO26 son de uso interno (LCD y buzzer).
# No asignarlos a sensores ni sacarlos al socket exterior.

# --- Sensores ---
PIN_VIBRACION = 34   # SW-420 DO
PIN_RPM       = 25   # LM393 DO (antes GPIO26, liberado para el buzzer)
PIN_RPM_AO    = 35   # LM393 AO
PIN_TEMP      = 27   # DS18B20 DATA

# --- Interfaz de estado (interno) ---
PIN_LCD_SDA = 21
PIN_LCD_SCL = 22
PIN_BUZZER  = 26

# --- LCD1602 I2C ---
# Direccion del backpack PCF8574. None = autodetectar entre LCD_I2C_CANDIDATES
# (los modulos vienen en 0x27 o en 0x3F segun el fabricante). Si conoces la
# tuya, ponela fija aca para que el arranque sea mas rapido.
LCD_I2C_ADDRESS = None
LCD_I2C_CANDIDATES = (0x27, 0x3F, 0x20, 0x38)
LCD_I2C_FREQ = 100000
LCD_COLS = 16
LCD_ROWS = 2
