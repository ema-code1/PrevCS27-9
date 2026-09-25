# Firmware PrevCS para ESP32S NodeMCU

MicroPython para leer sensores y mandar datos al backend PHP.

## Archivos a subir

Subi estos archivos a la ESP32:

- `boot.py`
- `main.py`
- `config_manager.py`
- `wifi_manager.py`
- `sensors.py`
- `backend.py`
- `pins.py` (mapa de GPIO)
- `lcd1602.py` (driver del LCD I2C)
- `buzzer.py`
- `device_ui.py` (LCD + buzzer + estados)

No subas `config.example.json` como config real. Es solo ejemplo.

Para probar el armado sin sensores ni WiFi hay dos scripts sueltos:
`test_lcd_buzzer.py` (pantalla + buzzer, paso a paso) e `i2c_lcd_check.py`
(solo el scan del bus). No hace falta subirlos: se abren en Thonny y se corren
con F5.

## Cableado

| GPIO | Uso |
|------|-----|
| GPIO34 | SW-420 (vibracion) DO |
| GPIO25 | LM393 RPM DO |
| GPIO35 | LM393 RPM AO |
| GPIO27 | DS18B20 DATA (4.7k a 3.3V) |
| GPIO21 | LCD1602 I2C SDA (via conversor de nivel) |
| GPIO22 | LCD1602 I2C SCL (via conversor de nivel) |
| GPIO26 | Buzzer activo -> 1k -> base BC337 |

GPIO21, GPIO22 y GPIO26 quedan reservados para uso interno. No los uses para
sensores.

Todos los numeros de pin estan en `pins.py`. Si cambias el cableado, tocas ese
archivo y nada mas.

El LCD es de 5V: alimentalo con VIN/5V y pasa SDA/SCL por el conversor de nivel
bidireccional (lado LV a 3.3V de la ESP32, lado HV a 5V). La direccion I2C se
autodetecta entre 0x27 y 0x3F; si queres fijarla, pone `LCD_I2C_ADDRESS` en
`pins.py`.

## Pantalla y buzzer

El LCD muestra el estado de la placa (`PrevCS / Iniciando...`,
`Conectando WiFi`, `Conectando API`, `Enviando datos`, `Datos enviados`,
`ERROR WIFI`, `ERROR API`, `ERROR SENSOR`). Solo se reescribe cuando el texto
cambia, asi que no parpadea.

**Si la pantalla prende azul pero no muestra letras**, el problema es la
tension, no el codigo. El chip I2C del backpack (PCF8574) funciona con 3.3V y
contesta el scan sin drama, pero el panel HD44780 necesita **5V** para que los
caracteres se vean: a 3.3V no alcanza la tension de contraste y la pantalla
queda vacia, gires lo que gires el potenciometro. VCC del modulo va a VIN/5V,
y las senales SDA/SCL pasan por el conversor de nivel.

Si el bus I2C se cuelga (`OSError: 116 ETIMEDOUT`, tipico con cables largos o
pull-ups flojos), el driver destraba el bus con pulsos de reloj, reinicializa el
display y reintenta. Si aun asi no vuelve, la placa sigue midiendo y enviando sin
pantalla, y reintenta tomar el LCD cada 15 segundos. El bus corre a 50 kHz para
tener margen; esta en `LCD_I2C_FREQ` de `pins.py`.

El buzzer avisa por patrones, una sola vez por episodio de error:

- 1 beep corto: conectado / recuperado
- 2 beeps: error de sensor
- 3 beeps: error de WiFi o de API
- 1 beep largo: error critico
- 2 beeps largos: un sensor quedo fuera del rango configurado en la web

La web decide las alertas: cada respuesta de `/api/iot/lectura` trae `alerts` con
los sensores fuera de rango. La placa lo muestra como `ALERTA SENSOR` + detalle
(p. ej. `TEMP ALTA`) y suena una vez por episodio; cuando todo vuelve al rango se
rearma la alarma.

## Primer uso

1. Subi los `.py` con Thonny.
2. Reinicia la ESP32.
3. La ESP crea la red WiFi `PREVCS-CONFIG`.
4. Conectate a esa red.
5. Abri `http://192.168.4.1`.
6. Copia la MAC que muestra la placa.
7. En PrevCS, entra a `Mis ESP32` o `Espacios`.
8. Vincula la ESP32 con esa MAC.
9. PrevCS te da un link listo para `192.168.4.1`.
10. Abri ese link. Solo cargas WiFi y guardas.

El link ya lleva:

- nombre de placa
- URL de API
- token

El usuario no tiene que escribir esos datos.

## Sensores

El firmware envia:

- `vibration`
- `rpm`
- `temperature`

PrevCS crea esos sensores automaticamente segun lo elegido en onboarding.

## Reconfigurar

Si queres empezar de cero, borra `config.json` de la ESP32 y reinicia.

## Config manual opcional

```json
{
  "device_name": "ESP32 Motor 1",
  "wifi_ssid": "WIFI",
  "wifi_password": "clave",
  "server_url": "http://localhost/PreventiveControlSystem/public/api/iot/lectura",
  "device_token": "TOKEN",
  "pulses_per_rev": 1,
  "window_seconds": 15,
  "period_seconds": 60
}
```
