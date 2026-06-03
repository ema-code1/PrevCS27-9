# Firmware PrevCS para ESP32S NodeMCU (MicroPython + Thonny)

Toma lecturas de 3 sensores cada minuto (ventana de 15 s) y las envía al backend
PHP en un solo paquete JSON, autenticándose por MAC + token.

## Archivos (subir TODOS a la ESP32)

| Archivo | Para qué sirve |
|---|---|
| `boot.py` | Se ejecuta al encender; sólo baja el ruido de logs. |
| `main.py` | Programa principal: portal de config **o** ciclo de medición. |
| `config_manager.py` | Lee/escribe `config.json`. |
| `wifi_manager.py` | Conexión WiFi + portal de configuración (modo AP). |
| `sensors.py` | Lectura de SW-420, LM393 (RPM + AO) y DS18B20. |
| `backend.py` | Envío HTTP POST al backend con reintentos. |
| `config.example.json` | Ejemplo de `config.json` (por si querés cargarlo a mano). |

> **No** subas `config.example.json` con ese nombre. El `config.json` real lo crea
> el portal de configuración la primera vez (o copiá el ejemplo y renombralo).

## Conexiones (resumen)

```
SW-420  DO  -> GPIO34        LM393 DO -> GPIO26 (RPM, pulsos)
LM393   AO  -> GPIO35        DS18B20 DATA -> GPIO27 (+ 4.7k a 3.3V)
Todos VCC -> 3.3V   |   Todos GND -> GND
```

## Puesta en marcha (lo que hace el cliente final, sin saber código)

1. Subí los `.py` a la ESP32 con Thonny y reiniciá la placa.
2. Como todavía no hay `config.json`, la ESP crea una red WiFi llamada **`PrevCS_Config`**.
3. Desde el celular/PC, conectate a esa red WiFi.
4. Abrí el navegador en **`http://192.168.4.1`**.
5. La página muestra la **MAC** de la placa. Anotala.
6. En la web de PrevCS: *Añadir ESP* → pegá esa MAC y ponele nombre → la web te da un **token** (se muestra una sola vez).
7. Volvé al portal (`192.168.4.1`) y cargá: nombre, WiFi (SSID + clave), URL del servidor y el token.
8. **Guardar y reiniciar.** La ESP se conecta sola a tu WiFi y empieza a enviar datos cada minuto.

A partir de ahí queda configurada para siempre (salvo que borres `config.json`).

## Probar desde Thonny (modo desarrollador)

- Abrí la consola (REPL) de Thonny: vas a ver los logs `[main] ... [http] ...`.
- Para reconfigurar de cero: borrá `config.json` de la placa y reiniciá → vuelve el portal.
- La **MAC** también se imprime en la consola al arrancar.

## Notas técnicas

- La hora de cada lectura la pone el **servidor** (la ESP no trae reloj). Por eso el
  firmware no envía `timestamp`; si querés precisión, se puede agregar NTP.
- Se envían **3 métricas** a `sensor_readings`: `vibration`, `rpm`, `temperature`.
  Asegurate de asociar esos 3 sensores a la placa en la web (botón "Agregar sensor").
- `pulses_per_rev` = ranuras del disco encoder del LM393. Si tu disco da 1 pulso por
  vuelta, dejá 1. RPM = (pulsos / pulses_per_rev) × (60 / 15).
- **RPM = un solo sensor con dos lecturas:** la RPM real (pulsos del DO) y, derivado de
  ella, el estado **ANDANDO / DETENIDO** (RPM > 0). El AO del LM393 se lee solo como
  diagnóstico en consola (alineación del sensor con la ranura); **no** se envía ni se
  guarda como métrica aparte.
- Si el DS18B20 no responde, la temperatura se omite del paquete y se loguea el aviso.

## Trabajar en dos lugares (casa / escuela) sin reconfigurar

Como cada lugar tiene **otra WiFi, otra IP y otra base de datos** (otro token), la placa
puede llevar **las dos configuraciones** y elegir sola cuál usar según dónde esté.

Editá `config.json` a mano (Thonny) con listas en vez de valores sueltos:

```json
{
  "device_name": "Extrusora-1",
  "wifi_networks": [
    { "ssid": "WIFI-CASA",    "password": "clave_casa" },
    { "ssid": "WIFI-ESCUELA", "password": "clave_escuela" }
  ],
  "endpoints": [
    { "url": "http://192.168.1.113/PreventiveControlSystem/public/api/iot/lectura", "token": "TOKEN_CASA" },
    { "url": "http://IP_ESCUELA/PreventiveControlSystem/public/api/iot/lectura",     "token": "TOKEN_ESCUELA" }
  ],
  "pulses_per_rev": 1, "window_seconds": 15, "period_seconds": 60
}
```

Cómo funciona:
- **WiFi:** la placa escanea y se conecta a la red que esté en rango (casa *o* escuela).
- **Servidor:** prueba cada `endpoint` hasta que uno acepte el lote, y recuerda el último
  que funcionó para probarlo primero. Así, al moverte de lugar, se reacomoda solo.

Requisito: registrá la **misma ESP (misma MAC)** en *ambas* webs (casa y escuela). Cada
una te dará **su propio token** → poné cada token con su URL en la lista `endpoints`.

> El formato simple (`wifi_ssid` + `server_url` + `device_token`) que guarda el portal AP
> sigue funcionando; las listas son solo para el caso multi-lugar.

## Librerías

`onewire`, `ds18x20`, `network`, `machine`, `esp` vienen incluidas en el firmware
MicroPython oficial para ESP32. `urequests` viene en la mayoría de los builds; si
faltara, instalalo con `mip` o `upip` (`import mip; mip.install("urequests")`).
