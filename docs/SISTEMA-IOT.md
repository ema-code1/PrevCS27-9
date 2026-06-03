# Sistema IoT PrevCS — Documentación técnica

> **Proyecto:** PreventiveControlSystem (PrevCS) — Sistema de Control Preventivo IoT
> **Stack:** ESP32 (MicroPython) → API PHP (CodeIgniter 4) → MySQL → Vistas web
> **Estado:** Funcionando de punta a punta (placa real enviando y graficando en la web).
> **Fecha de esta documentación:** 2026-06-03

Este documento describe **solo las configuraciones que quedaron funcionando**. Está
redactado para que lo entienda tanto una persona (el profesor evaluador) como una
inteligencia artificial que retome el proyecto.

---

## Índice

1. [Resumen del sistema](#1-resumen-del-sistema)
2. [Arquitectura y flujo de datos](#2-arquitectura-y-flujo-de-datos)
3. [Hardware: placa, sensores y conexiones](#3-hardware-placa-sensores-y-conexiones)
4. [Base de datos](#4-base-de-datos)
5. [Backend web (CodeIgniter 4)](#5-backend-web-codeigniter-4)
6. [Firmware ESP32 (MicroPython)](#6-firmware-esp32-micropython)
7. [Puesta en marcha paso a paso](#7-puesta-en-marcha-paso-a-paso)
8. [Pruebas y verificación](#8-pruebas-y-verificación)
9. [Problemas encontrados y soluciones](#9-problemas-encontrados-y-soluciones)
10. [Estado actual verificado](#10-estado-actual-verificado)
11. [Glosario](#11-glosario)

---

## 1. Resumen del sistema

El sistema mide variables físicas de una máquina industrial con una placa **ESP32** y
sus sensores, y las guarda en una base de datos **MySQL** para visualizarlas en una
página web **PHP**.

La placa, una vez por minuto, abre una **ventana de medición de 15 segundos**, calcula
los valores de los sensores y envía un **paquete JSON** por HTTP a un endpoint del
backend. El backend autentica la placa, guarda las lecturas y las muestra en gráficos
por espacio (planta/máquina).

**Variables medidas (3 sensores):**
- **Vibración** (cantidad de eventos en la ventana) — sensor SW-420.
- **RPM** (revoluciones por minuto) — sensor LM393. De la RPM se deriva además el
  estado **ANDANDO / DETENIDO** (RPM > 0).
- **Temperatura** (°C) — sensor DS18B20.

---

## 2. Arquitectura y flujo de datos

```
┌─────────────┐   HTTP POST (JSON)   ┌──────────────────┐      ┌─────────┐
│   ESP32     │ ───────────────────► │  API PHP (CI4)   │ ───► │  MySQL  │
│ MicroPython │  /api/iot/lectura    │ IoTController    │      │ prevcs  │
│ + 3 sensores│  auth: MAC + token   │ ::ingest()       │      └────┬────┘
└─────────────┘                      └──────────────────┘           │
                                                                    │ consulta
                                            ┌───────────────────────┘
                                            ▼
                                   ┌──────────────────┐
                                   │  Vistas web PHP  │  Registros IoT por espacio
                                   │ RegistrosController│  (1 gráfico por sensor)
                                   └──────────────────┘
```

**Cadena de identificación de los datos (modelo relacional):**

```
users ─< industry_users >─ industries (espacios)
                              └─< esp32_devices (1 placa = 1 MAC + 1 token)
                                     └─< esp32_sensors (sensor físico = placa + tipo)
                                            └─< sensor_readings (valor + fecha/hora)
sensor_types (catálogo de tipos) ──┘
```

Gracias a esta cadena, cada lectura se asocia sin ambigüedad a **qué sensor**, **de qué
placa**, **de qué espacio** y **en qué momento**. Dos sensores iguales (ej. dos SW-420)
en placas distintas nunca se confunden, porque la lectura cuelga de un `esp32_sensor_id`
que pertenece a una placa identificada por su **MAC única**.

---

## 3. Hardware: placa, sensores y conexiones

| Elemento | Detalle |
|---|---|
| Placa | **ESP32S NodeMCU** |
| Firmware de placa | **MicroPython v1.28.0** (Generic ESP32 module) |
| IDE | **Thonny** |
| Alimentación | USB-C desde la PC; sensores a **3.3 V** |

**Conexiones que funcionaron:**

| Sensor | Pin del sensor | GPIO ESP32 | Notas |
|---|---|---|---|
| SW-420 (vibración) | DO | **GPIO34** | Cuenta de eventos por interrupción. GPIO34 es solo-entrada. |
| LM393 (RPM) | DO | **GPIO26** | Cuenta de pulsos por interrupción → RPM. |
| LM393 (RPM) | AO | **GPIO35** | Solo **diagnóstico** en consola (no se guarda). |
| DS18B20 (temperatura) | DATA | **GPIO27** | OneWire. **Requiere resistencia de 4.7 kΩ entre DATA y 3.3 V.** |

- Línea positiva de la protoboard → **3.3 V** del ESP32; línea negativa → **GND**.
- Todos los sensores VCC → 3.3 V, GND → GND.
- Los módulos SW-420 y LM393 tienen **LED de encendido** (debe quedar fijo) y un LED de
  señal. El DS18B20 **no tiene LED** (es normal).

> **Decisión de diseño sobre RPM:** se usa **un solo sensor RPM**. La salida digital (DO)
> da la RPM real (pulsos); la salida analógica (AO) **no** es una métrica aparte, solo se
> lee como diagnóstico de alineación. Por eso el sistema **no** tiene un tipo "rpm_analog".

---

## 4. Base de datos

Base de datos MySQL: **`prevcs`**. Tablas del módulo IoT:

| Tabla | Rol |
|---|---|
| `industries` | Espacios (planta / máquina). Tienen UID público y contraseña hasheada. |
| `industry_users` | Relación usuario ↔ espacio, con rol (`admin_principal` / `supervisor`). |
| `esp32_devices` | Cada placa física. **Clave única: `mac_address`.** |
| `sensor_types` | Catálogo de tipos de sensor (temperature, vibration, rpm, etc.). |
| `esp32_sensors` | Sensor físico = (placa + tipo). Único por `(esp32_device_id, sensor_type_id)`. |
| `sensor_readings` | **Tabla principal**: cada lectura (`value` + `measured_at`). |
| `industry_metrics` | Qué gráficos eligió cada espacio al crearse. |

### 4.1 `esp32_devices` (con la seguridad agregada)

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT PK | |
| `industry_id` | INT FK → industries | Espacio al que pertenece. |
| `name` | VARCHAR(150) | Nombre visible (ej. "Motor 1"). |
| `mac_address` | VARCHAR(17) **UNIQUE** | Identifica la placa. Formato `AA:BB:CC:DD:EE:FF`. |
| `token_hash` | VARCHAR(64) | **SHA-256** del token secreto (nunca se guarda el token en claro). |
| `is_active` | TINYINT(1) | 1 = puede enviar; 0 = deshabilitada (la ingesta la rechaza). |
| `status` | VARCHAR(20) | `online` / `offline` (heartbeat). |
| `last_seen_at` | DATETIME | Última vez que envió datos. |

### 4.2 `sensor_types` (catálogo final)

| id | code | name | unit | default_chart |
|---|---|---|---|---|
| 1 | `temperature` | Temperatura | °C | line |
| 2 | `humidity` | Humedad | % | area |
| 3 | `vibration` | Vibración | mm/s | line |
| 4 | `rpm` | RPM | rev/min | gauge |
| 5 | `current` | Corriente | A | bars |
| 6 | `on_off` | Estado ON/OFF | — | timeline |

### 4.3 `sensor_readings` (tabla principal)

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT PK | Preparada para millones de filas. |
| `esp32_sensor_id` | INT FK → esp32_sensors | A qué sensor pertenece la lectura. |
| `value` | DECIMAL(12,3) | Valor medido. |
| `measured_at` | DATETIME | Momento de la medición (lo pone el servidor al recibir). |
| `created_at` | TIMESTAMP | Cuándo se insertó. |

Índice `(esp32_sensor_id, measured_at)` para consultas rápidas de series temporales.

### 4.4 Migraciones aplicadas (CodeIgniter)

Carpeta `app/Database/Migrations/`. Las relevantes al módulo IoT:

| Archivo | Qué hace |
|---|---|
| `2026-05-29-000001 … 000007` | Crean las 7 tablas del módulo IoT y siembran `sensor_types`. |
| `2026-06-02-000001_AlterEsp32DevicesAddSecurity` | Agrega `token_hash` + `is_active`; **elimina** la columna `device_token` (texto plano). |
| `2026-06-02-000002_SeedRpmAnalogSensorType` | (histórica) sembró un tipo `rpm_analog`. |
| `2026-06-02-000003_RemoveRpmAnalogSensorType` | **Elimina** `rpm_analog` (decisión: RPM es un solo sensor). |

Comando para aplicarlas: `php spark migrate`.

---

## 5. Backend web (CodeIgniter 4)

Ubicación del proyecto: `C:\xampp\htdocs\PreventiveControlSystem` (XAMPP, Apache + MySQL).

### 5.1 Endpoint de ingesta — `POST /api/iot/lectura`

**Controlador:** `App\Controllers\IoTController::ingest()`

Recibe el paquete JSON de la placa. Formato **en lote** (recomendado):

```json
{
  "mac_address": "D4:E9:F4:8D:18:7C",
  "token": "<token secreto de la placa>",
  "window_seconds": 15,
  "readings": [
    { "type": "vibration",   "value": 0 },
    { "type": "rpm",         "value": 0 },
    { "type": "temperature", "value": 20.12 }
  ]
}
```

- El token también se acepta por header **`X-Device-Token`**.
- La placa se identifica por **`mac_address`** (no por un código inventado).
- También acepta el formato de **una sola lectura** (`sensor` + `value`) por
  retrocompatibilidad.

**Lógica de `ingest()`:**
1. Busca la placa por MAC → si no existe, **404**.
2. Valida el token: `Esp32DeviceModel::tokenMatches()` compara, en tiempo constante
   (`hash_equals`), el SHA-256 del token recibido contra `token_hash` → si no coincide, **401**.
3. Verifica `is_active = 1` → si está deshabilitada, **403**.
4. Por cada lectura: resuelve el tipo (`SensorTypeModel::resolve`) y busca el sensor
   asociado a esa placa (`Esp32SensorModel::findForDeviceAndType`).
   - Si el sensor no está asociado o el tipo no existe, **se omite** esa lectura (no
     rompe el resto) → aparece en `skipped` con su motivo.
5. Inserta las lecturas válidas en una transacción (`SensorReadingModel::insertBatch`) y
   actualiza el heartbeat (`touchLastSeen` → `status=online`, `last_seen_at=ahora`).
6. Responde **201** con `saved` (aceptadas) y `skipped` (omitidas).
   - Si ninguna lectura coincide con sensores asociados → **422** con el detalle.

**Respuesta de ejemplo (201):**
```json
{ "ok": true, "device": "Motor 1",
  "saved": [ {"type":"vibration","value":0}, {"type":"rpm","value":0} ],
  "skipped": [], "count": 2 }
```

### 5.2 Seguridad de la ingesta

- **No usa sesión ni CSRF** (la ESP no es un navegador logueado). La ruta
  `api/iot/lectura` está **exenta** de los filtros `authGuard` y `csrf` en
  `app/Config/Filters.php`.
- **Token hasheado:** la base guarda solo el **SHA-256** del token. El token en claro se
  muestra **una sola vez** en la web al registrar la placa (para cargarlo en el firmware).
- **Flag `is_active`:** permite deshabilitar una placa sin borrarla.
- Comparación de token en **tiempo constante** (`hash_equals`) contra timing attacks.

### 5.3 Visualización — "Registros IoT" por espacio

**Controlador:** `App\Controllers\RegistrosController`

| Ruta | Método | Qué muestra |
|---|---|---|
| `GET registros-iot` | `index()` | Lista de los espacios del usuario, con botón "Registros IoT" en cada uno. |
| `GET registros-iot/{id}` | `space($id)` | Vista del espacio: **un gráfico por cada sensor** de cada placa. |
| `GET api/espacio/{id}/lecturas?sensor={id}` | `readings($id)` | Serie temporal (JSON) de un sensor, validada por pertenencia al espacio. |

- Cada gráfico es una línea SVG con la serie real de `sensor_readings`.
- Para el sensor **RPM**, además del valor se muestra **ANDANDO ▶ / DETENIDO ⏸** (derivado
  de RPM > 0).
- El acceso está scopeado: un usuario solo ve los espacios a los que pertenece
  (`IndustryUserModel::userBelongsTo`).

### 5.4 Controladores y modelos (nombres)

**Controladores** (`app/Controllers/`):
- `IoTController` — ingesta de lecturas (API placa).
- `RegistrosController` — Registros IoT por espacio (vistas + API de series).
- `Esp32Controller` — alta/listado/detalle de placas; asociar token.
- `SensorController` — asociar un tipo de sensor a una placa.
- `OnboardingController` — crear espacio (industria) + métricas elegidas.
- `IndustryController` — unirse a un espacio existente.

**Modelos** (`app/Models/`):
- `Esp32DeviceModel` — `normalizeMac()`, `generateToken()`, **`hashToken()`**,
  **`tokenMatches()`**, `findByMac()`, `devicesForIndustry()`, `touchLastSeen()`.
- `Esp32SensorModel` — `findForDeviceAndType()`, `sensorsForDevice()`,
  `sensorsForIndustry()`, `belongsToIndustry()`.
- `SensorReadingModel` — `latestForSensor()`, `seriesForSensor()` (+ `insertBatch`).
- `SensorTypeModel` — `findByCode()`, `resolve()` (acepta code o nombre).
- `IndustryModel`, `IndustryUserModel`, `IndustryMetricModel`.

### 5.5 Rutas del módulo IoT (`app/Config/Routes.php`)

```php
// Registros IoT por-espacio
$routes->get('registros-iot', 'RegistrosController::index');
$routes->get('registros-iot/(:num)', 'RegistrosController::space/$1');
$routes->get('api/espacio/(:num)/lecturas', 'RegistrosController::readings/$1');

// ESP32
$routes->get('esp32', 'Esp32Controller::index');
$routes->get('esp32/agregar', 'Esp32Controller::createForm');
$routes->post('esp32/agregar', 'Esp32Controller::store');
$routes->post('esp32/(:num)/sensores', 'SensorController::store/$1');
$routes->get('esp32/(:num)', 'Esp32Controller::show/$1');
$routes->get('api/esp32/(:num)/lecturas', 'Esp32Controller::readings/$1');

// Ingesta desde la ESP32 (auth por MAC + token, exenta de CSRF/authGuard)
$routes->post('api/iot/lectura', 'IoTController::ingest');
```

> **Código legacy eliminado:** la vista vieja con datos demo (`IotReadings.php`,
> `IotReadingRepository.php`, `Views/registros-iot.php` y la ruta `api/iot-readings`)
> fue **removida** y reemplazada por `RegistrosController` con datos reales.

---

## 6. Firmware ESP32 (MicroPython)

Ubicación en la PC: **`C:\Users\josem\prevcs-firmware\`**. Se suben a la raíz `/` de la
placa con Thonny.

### 6.1 Archivos

| Archivo | Función |
|---|---|
| `boot.py` | Se ejecuta al encender; baja el ruido de logs y arranca `main.py`. |
| `main.py` | Programa principal: carga config → portal **o** ciclo de medición. |
| `config_manager.py` | Lee/escribe `config.json`; normaliza redes WiFi y endpoints. |
| `wifi_manager.py` | Conexión WiFi + portal de configuración (modo AP). |
| `sensors.py` | Lectura de SW-420, LM393 (RPM) y DS18B20 en la ventana de medición. |
| `backend.py` | Envío HTTP POST del lote, con reintentos y failover entre servidores. |
| `config.example.json` | Ejemplo de configuración (no se sube con ese nombre). |
| `README_FIRMWARE.md` | Guía del firmware. |

### 6.2 Ciclo de medición (`main.py`)

1. Carga `config.json`. Si no existe o es inválido → arranca el **portal de
   configuración** (modo Access Point).
2. Si hay config → conecta al WiFi e imprime la **MAC** (debe estar registrada en la web).
3. Entra al loop: **cada 60 s** abre una **ventana de 15 s**, mide los 3 sensores,
   arma el JSON y lo envía.
4. Imprime en consola lo que lee y lo que envía. Maneja errores de red, reconexión
   WiFi y no se bloquea si el servidor no responde.

Lo que se imprime en la consola de Thonny cuando funciona:
```
[wifi] Conectada. IP: 192.168.1.xx
[main] --- Ventana de medicion (15s) ---
[main] Leido -> vibracion:0 eventos | rpm:0.0 (DETENIDO) | temp:20.12
[http] POST http://192.168.1.113/PreventiveControlSystem/public/api/iot/lectura
[http] OK 201 -> {"ok":true, ...}
```

### 6.3 `config.json` — formato que funcionó

**Formato simple** (el que genera el portal AP; un solo lugar):
```json
{
  "device_name": "Motor 1",
  "wifi_ssid": "MANGOLD-WiFi",
  "wifi_password": "********",
  "server_url": "http://192.168.1.113/PreventiveControlSystem/public/api/iot/lectura",
  "device_token": "<token que da la web al registrar la placa>",
  "pulses_per_rev": 1,
  "window_seconds": 15,
  "period_seconds": 60
}
```

> **Crítico:** `server_url` debe terminar en **`/api/iot/lectura`** (es el endpoint que
> recibe datos), **no** la URL de una página como `/esp32/6`.

**Formato multi-lugar** (opcional, para trabajar en casa **y** escuela sin reconfigurar):
```json
{
  "device_name": "Motor 1",
  "wifi_networks": [
    { "ssid": "WIFI-CASA",    "password": "..." },
    { "ssid": "WIFI-ESCUELA", "password": "..." }
  ],
  "endpoints": [
    { "url": "http://192.168.1.113/PreventiveControlSystem/public/api/iot/lectura", "token": "TOKEN_CASA" },
    { "url": "http://IP_ESCUELA/PreventiveControlSystem/public/api/iot/lectura",     "token": "TOKEN_ESCUELA" }
  ],
  "pulses_per_rev": 1, "window_seconds": 15, "period_seconds": 60
}
```
La placa se conecta a la WiFi que esté en rango y prueba cada servidor hasta que uno
acepte (recuerda el último que funcionó). Requiere registrar la **misma MAC** en ambas
bases para obtener un token por cada una.

### 6.4 Modo Access Point (configuración del usuario final)

Si no hay `config.json`, la placa crea la red WiFi **`PrevCS_Config`**. El usuario se
conecta y abre `http://192.168.4.1`, donde una página muestra la **MAC** de la placa y un
formulario para cargar WiFi, URL del servidor y token. Al guardar, la placa escribe
`config.json` y se reinicia en modo normal.

---

## 7. Puesta en marcha paso a paso

**A. Registrar la placa en la web**
1. Ingresar al panel y entrar a un **espacio** (ej. "Extrusoras").
2. **Añadir ESP**: cargar la **MAC real** de la placa y un nombre → la web genera un
   **token** que se muestra **una sola vez** (copiarlo).
3. En la vista de la ESP, **Agregar sensor** para asociar los 3 tipos: **Vibración, RPM,
   Temperatura**.

**B. Cargar el firmware en la ESP32 (Thonny)**
4. Conectar la placa por USB; en Thonny elegir intérprete **MicroPython (ESP32)** + puerto COM.
5. Subir a la raíz `/` los 6 `.py` (`boot, main, config_manager, wifi_manager, sensors, backend`).
   *(Para subir/borrar archivos, la placa debe estar detenida en `>>>`.)*

**C. Configurar la placa**
6. Reiniciar → se crea la red `PrevCS_Config` → conectarse → abrir `http://192.168.4.1`.
7. Verificar que la **MAC** mostrada sea la registrada, y cargar:
   - WiFi de **2.4 GHz** (SSID + contraseña). *(El ESP32 no funciona con 5 GHz.)*
   - **URL del servidor:** `http://<IP-de-la-PC>/PreventiveControlSystem/public/api/iot/lectura`.
   - **Token** (el que dio la web).
8. **Guardar y reiniciar.** En la consola de Thonny debe aparecer `[http] OK 201`.

**D. Verificar**
9. En la web, **Registros IoT → (espacio)**: los gráficos se llenan y la placa figura **online**.

---

## 8. Pruebas y verificación

Pruebas reales del endpoint (todas verificadas):

| Caso | Resultado esperado | Resultado |
|---|---|---|
| Lote válido (3 lecturas) | 201, guarda y marca online | ✅ |
| Token inválido | 401 | ✅ |
| MAC no registrada | 404 | ✅ |
| Placa con `is_active=0` | 403 | ✅ |
| Lectura de sensor no asociado | 201 con `skipped`, o 422 si ninguna coincide | ✅ |
| Heartbeat | `status=online`, `last_seen_at` actualizado | ✅ |

Verificación en base de datos (consulta de control):
```sql
SELECT t.code, r.value, r.measured_at
FROM sensor_readings r
JOIN esp32_sensors s ON s.id = r.esp32_sensor_id
JOIN sensor_types  t ON t.id = s.sensor_type_id
ORDER BY r.id DESC LIMIT 5;
```

---

## 9. Problemas encontrados y soluciones

Registro de los problemas reales que surgieron en la puesta en marcha y cómo se
resolvieron (útil para futuras instalaciones):

| Síntoma | Causa | Solución |
|---|---|---|
| `[wifi] Timeout: no se pudo conectar` | Red WiFi de **5 GHz** | El ESP32 solo soporta **2.4 GHz**. Conectar a la red de 2.4 GHz del router. |
| `OSError: Wifi Internal State Error` al reintentar | `connect()` llamaba a conectar mientras la interfaz seguía "conectando" | En `wifi_manager.connect()` se hace `disconnect()` + pausa antes de reconectar. Un **apagado/encendido** del USB limpia el estado trabado. |
| Server devuelve error / no guarda | Se cargó como `server_url` la **URL de una página** (`/esp32/6`) | La URL del servidor debe terminar en **`/api/iot/lectura`**. |
| `[http] Rechazado 404` | La **MAC registrada** no coincidía con la MAC real de la placa | Registrar exactamente la MAC que imprime la placa en consola (`[main] MAC : ...`). |
| `[http] Rechazado 401` | El token del `config.json` no coincide con el de la web | Recopiar el token tal cual lo dio la web al registrar. |
| `no se detecto ningún DS18B20` | Falta / mal puesta la **resistencia de 4.7 kΩ** | La 4.7 kΩ va entre **DATA (GPIO27) y 3.3 V** (no a GND). Verificar cableado. |
| Vibración y RPM en **0** | El motor estaba **detenido** y no había vibración | Es correcto: 0 = sin movimiento. Se confirma estimulando los sensores (los LED de los módulos reaccionan). |

**Aclaración sobre `localhost` vs IP:** `localhost` significa "esta misma máquina". Sirve
para el navegador de la PC, pero **no** para la ESP32 (para ella, `localhost` sería ella
misma). Por eso el firmware usa la **IP de red de la PC** (ej. `192.168.1.113`). Conviene
fijar esa IP por reserva DHCP en el router para que no cambie.

---

## 10. Estado actual verificado

- Placa real: **"Motor 1"**, MAC `D4:E9:F4:8D:18:7C`, espacio **"Extrusoras"**, **online**.
- Sensores asociados y enviando: **vibración, rpm, temperatura**.
- Última temperatura leída: **20.12 °C**; vibración y RPM en 0 (motor detenido).
- Lecturas acumuladas en `sensor_readings` y graficándose en **Registros IoT**.
- Todas las pruebas de seguridad del endpoint pasan (201/401/403/404/422).

---

## 11. Glosario

| Término | Significado |
|---|---|
| **Espacio** | Una industria/planta/máquina (`industries`). Agrupa placas y sensores. |
| **ESP32 / placa** | El dispositivo físico que mide y envía (`esp32_devices`), identificado por su MAC. |
| **MAC** | Dirección física única de la placa. Es su identidad ante el backend. |
| **Token** | Secreto por placa. Se envía en cada lectura; la base guarda solo su hash SHA-256. |
| **Endpoint** | URL que recibe los datos: `POST /api/iot/lectura`. |
| **Ventana de medición** | Los 15 s en que la placa mide antes de enviar. |
| **Heartbeat** | Señal de vida: al recibir datos, la placa pasa a `online`. |
| **Ingesta** | Proceso de recibir, autenticar y guardar las lecturas (`IoTController::ingest`). |
| **AP / portal** | Modo Access Point para configurar la placa sin saber de código. |
