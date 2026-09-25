# pins.py — mapa unico de GPIO de la placa. Cualquier cambio de cableado se hace
# SOLO aca; el resto del firmware importa estas constantes y nunca numeros sueltos.
#
# Cableado actual (ESP32 de 38 pines, todo a 3.3V salvo el LCD):
#   SW-420 (vibracion)  DO   -> GPIO34   (solo-entrada, sin pull interno)
#   LM393 RPM           DO   -> GPIO25   (cuenta de pulsos por interrupcion)
#   LM393 RPM           AO   -> GPIO35   (ADC1, solo-entrada)
#   DS18B20 (temp)      DATA -> GPIO27   (OneWire, 4.7k a 3.3V)
#   LCD1602 I2C 5V      SDA  -> GPIO32   (via conversor de nivel 3.3V<->5V)
#   LCD1602 I2C 5V      SCL  -> GPIO33   (via conversor de nivel 3.3V<->5V)
#   Buzzer activo 3.3V       -> GPIO26 -> 1k -> base BC337
#
# CAMBIO TEMPORAL DE DIAGNOSTICO (LCD con luz de fondo pero sin texto):
# se movio el bus I2C de GPIO21/22 a GPIO32/33 para descartar que esos dos
# pines del ESP32 esten danados. GPIO21/22 quedan libres por ahora.
#
# RESERVADOS: GPIO32, GPIO33 y GPIO26 son de uso interno (LCD y buzzer).
# No asignarlos a sensores ni sacarlos al socket exterior.

# --- Sensores ---
PIN_VIBRACION = 34   # SW-420 DO
PIN_RPM       = 25   # LM393 DO (antes GPIO26, liberado para el buzzer)
PIN_RPM_AO    = 35   # LM393 AO
PIN_TEMP      = 27   # DS18B20 DATA

# --- Interfaz de estado (interno) ---
PIN_LCD_SDA = 32
PIN_LCD_SCL = 33
PIN_BUZZER  = 26

# --- LCD1602 I2C ---
# Direccion del backpack PCF8574. None = autodetectar entre LCD_I2C_CANDIDATES
# (los modulos vienen en 0x27 o en 0x3F segun el fabricante). Si conoces la
# tuya, ponela fija aca para que el arranque sea mas rapido.
LCD_I2C_ADDRESS = None
LCD_I2C_CANDIDATES = (0x27, 0x3F, 0x20, 0x38)
# 50 kHz y no los 100 kHz de siempre: con el conversor de nivel, sus pull-ups
# de 10k y cables de protoboard, el flanco de subida llega justo y el bus se
# cuelga con ETIMEDOUT a mitad de una pantalla. A la mitad de velocidad sobra
# margen, y para dos lineas de texto la diferencia no se nota.
LCD_I2C_FREQ = 50000
LCD_COLS = 16
LCD_ROWS = 2


# ============================================================================
# SocketPuertos GPIO — mapa del conector exterior
# ============================================================================
# Todos los GPIO que NO usa la placa por dentro (LCD en 32/33, buzzer en 26)
# salen a un socket, y los sensores se conectan ahi y no al ESP32 directo.
#
# El conector tiene 20 columnas y dos filas:
#
#   fila de arriba : 20 GPIO            -> P1..P20, de izquierda a derecha
#   fila de abajo  : columnas 1..9      -> GND   (G1..G9)
#                    columna 10         -> VACIA a proposito (la ranura negra
#                                          del conector: sin GPIO, sin VCC, sin GND)
#                    columnas 11..20    -> 3.3V  (V1..V10)
#
# Fuera del socket, a proposito:
#   GPIO6..GPIO11  -> flash SPI interna. Usarlos cuelga la placa.
#   GPIO1 / GPIO3  -> UART0 (consola). El firmware imprime diagnosticos ahi.
#   GPIO32/33/26   -> LCD y buzzer (ver RESERVADOS arriba).
#   GPIO12         -> strapping de voltaje del flash: si un sensor lo deja en
#                     ALTO al arrancar, la placa no bootea. Es el que sobra para
#                     entrar en 20 columnas.
#
# Cada puerto es una tupla:
#   (puerto, gpio, capacidades, nota)
#
# Capacidades (strings, para que el backend las pueda leer tal cual):
#   'io'    entrada y salida
#   'in'    SOLO entrada, y sin pull-up/pull-down interno
#   'adc1'  ADC1: unico ADC utilizable, porque anda con el WiFi prendido
#   'adc2'  ADC2: NO leer analogico mientras haya WiFi (el firmware siempre lo tiene)
#   'touch' sensor capacitivo tactil
#   'pwm'   salida PWM / LEDC
#   'pull'  admite pull-up y pull-down internos
#   'dac'   salida analogica real de 8 bits (solo GPIO25; el 26 lo usa el buzzer)
#   'i2c'   par I2C por defecto del ESP32 (GPIO21 = SDA, GPIO22 = SCL)
#   'strap' pin de strapping: su nivel EN EL ARRANQUE decide como bootea la
#           placa. Sirve despues del boot; el sensor no debe forzarlo al arrancar.

SOCKET_PORTS = (
    ('P1',   4, ('io', 'adc2', 'touch', 'pwm', 'pull'), ''),
    ('P2',  13, ('io', 'adc2', 'touch', 'pwm', 'pull'), ''),
    ('P3',  14, ('io', 'adc2', 'touch', 'pwm', 'pull'), ''),
    ('P4',  16, ('io', 'pwm', 'pull'), 'Ocupado por la PSRAM si se migra a un modulo WROVER'),
    ('P5',  17, ('io', 'pwm', 'pull'), 'Ocupado por la PSRAM si se migra a un modulo WROVER'),
    ('P6',  18, ('io', 'pwm', 'pull'), 'VSPI SCK si alguna vez se usa SPI'),
    ('P7',  19, ('io', 'pwm', 'pull'), 'VSPI MISO si alguna vez se usa SPI'),
    ('P8',  23, ('io', 'pwm', 'pull'), 'VSPI MOSI si alguna vez se usa SPI'),
    ('P9',  25, ('io', 'adc2', 'pwm', 'pull', 'dac'), 'EN USO: DO del LM393 (RPM)'),
    ('P10', 27, ('io', 'adc2', 'touch', 'pwm', 'pull'), 'EN USO: DATA del DS18B20 (temp)'),
    ('P11', 21, ('io', 'pwm', 'pull', 'i2c'), 'I2C SDA por defecto (libre mientras el LCD este en 32/33)'),
    ('P12', 22, ('io', 'pwm', 'pull', 'i2c'), 'I2C SCL por defecto (libre mientras el LCD este en 32/33)'),
    ('P13', 34, ('in', 'adc1'), 'EN USO: DO del SW-420 (vibracion). Necesita pull-up externo'),
    ('P14', 35, ('in', 'adc1'), 'EN USO: AO del LM393 (RPM). Necesita pull-up externo'),
    ('P15', 36, ('in', 'adc1'), 'Rotulado VP. Necesita pull-up externo'),
    ('P16', 39, ('in', 'adc1'), 'Rotulado VN. Necesita pull-up externo'),
    ('P17',  0, ('io', 'adc2', 'touch', 'pwm', 'pull', 'strap'), 'Boton BOOT. Debe estar en ALTO al arrancar'),
    ('P18',  2, ('io', 'adc2', 'touch', 'pwm', 'pull', 'strap'), 'LED onboard. Debe estar en BAJO o libre al arrancar'),
    ('P19',  5, ('io', 'pwm', 'pull', 'strap'), 'Debe estar en ALTO al arrancar'),
    ('P20', 15, ('io', 'adc2', 'touch', 'pwm', 'pull', 'strap'), 'Debe estar en ALTO al arrancar'),
)

# Fila de abajo del conector, por columna (1..20). None = ranura vacia.
SOCKET_COLUMNS = 20
SOCKET_GND_COLUMNS = tuple(range(1, 10))     # G1..G9  (columnas 1-9)
SOCKET_BLOCKED_COLUMN = 10                   # sin pin
SOCKET_3V3_COLUMNS = tuple(range(11, 21))    # V1..V10 (columnas 11-20)

# GPIO que nunca salen del ESP32.
RESERVED_GPIO = (PIN_LCD_SDA, PIN_LCD_SCL, PIN_BUZZER)

# Con el WiFi encendido solo ADC1 da lecturas. Todo sensor analogico va aca.
ANALOG_PORTS = tuple(p for p in SOCKET_PORTS if 'adc1' in p[2])


def _index(ports):
    by_port = {}
    by_gpio = {}
    for port, gpio, caps, note in ports:
        entry = {'port': port, 'gpio': gpio, 'caps': caps, 'note': note}
        by_port[port] = entry
        by_gpio[gpio] = entry
    return by_port, by_gpio


_BY_PORT, _BY_GPIO = _index(SOCKET_PORTS)


def gpio_for_port(port):
    """'P9' -> 25. Levanta KeyError si el puerto no existe."""
    return _BY_PORT[port]['gpio']


def port_for_gpio(gpio):
    """25 -> 'P9'. None si ese GPIO no sale al socket."""
    entry = _BY_GPIO.get(gpio)
    return entry['port'] if entry else None


def port_info(port):
    """Ficha completa del puerto: port, gpio, caps, note."""
    return _BY_PORT[port]


def supports(port, capability):
    """True si el puerto admite 'io', 'adc1', 'pwm', etc."""
    return capability in _BY_PORT[port]['caps']
