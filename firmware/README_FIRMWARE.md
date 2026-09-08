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

El buzzer avisa por patrones, una sola vez por episodio de error:

- 1 beep corto: conectado / recuperado
- 2 beeps: error de sensor
- 3 beeps: error de WiFi o de API
- 1 beep largo: error critico

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
