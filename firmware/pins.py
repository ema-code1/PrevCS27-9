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
# Todos los GPIO que NO usa la placa por dentro (LCD en 21/22, buzzer en 26)
# salen a un socket, y los sensores se conectan ahi y no al ESP32 directo.
# Este bloque es la numeracion oficial de ese conector: P1..P16.
#
# Fuera del socket, a proposito:
#   GPIO6..GPIO11  -> flash SPI interna. Usarlos cuelga la placa.
#   GPIO1 / GPIO3  -> UART0 (consola). El firmware imprime diagnosticos ahi.
#   GPIO21/22/26   -> LCD y buzzer (ver RESERVADOS arriba).
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
    ('P11', 32, ('io', 'adc1', 'touch', 'pwm', 'pull'), 'Analogico recomendado'),
    ('P12', 33, ('io', 'adc1', 'touch', 'pwm', 'pull'), 'Analogico recomendado'),
    ('P13', 34, ('in', 'adc1'), 'EN USO: DO del SW-420 (vibracion). Necesita pull-up externo'),
    ('P14', 35, ('in', 'adc1'), 'EN USO: AO del LM393 (RPM). Necesita pull-up externo'),
    ('P15', 36, ('in', 'adc1'), 'Rotulado VP. Necesita pull-up externo'),
    ('P16', 39, ('in', 'adc1'), 'Rotulado VN. Necesita pull-up externo'),
)

# Anexo: pines libres pero de strapping. Su nivel EN EL ARRANQUE decide como
# bootea el ESP32, asi que un sensor que fije el nivel equivocado deja la placa
# sin arrancar. Sirven despues del boot; no van al socket principal.
SOCKET_PORTS_STRAPPING = (
    ('S1',  0, ('io', 'adc2', 'touch', 'pwm', 'pull'), 'Boton BOOT. Debe estar en ALTO al arrancar'),
    ('S2',  2, ('io', 'adc2', 'touch', 'pwm', 'pull'), 'LED onboard. Debe estar en BAJO o libre al arrancar'),
    ('S3',  5, ('io', 'pwm', 'pull'), 'Debe estar en ALTO al arrancar'),
    ('S4', 12, ('io', 'adc2', 'touch', 'pwm', 'pull'), 'Debe estar en BAJO al arrancar, o el flash queda mal configurado'),
    ('S5', 15, ('io', 'adc2', 'touch', 'pwm', 'pull'), 'Debe estar en ALTO al arrancar'),
)

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


_BY_PORT, _BY_GPIO = _index(SOCKET_PORTS + SOCKET_PORTS_STRAPPING)


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
