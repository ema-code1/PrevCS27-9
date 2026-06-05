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

No subas `config.example.json` como config real. Es solo ejemplo.

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
