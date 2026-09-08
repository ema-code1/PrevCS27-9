# Sistema IoT PrevCS — Documentación técnica

> **Proyecto:** PreventiveControlSystem (PrevCS) — Sistema de Control Preventivo IoT
> **Stack:** ESP32 (MicroPython) → API PHP (CodeIgniter 4) → MySQL → Vistas web
> **Estado:** Funcionando de punta a punta (placa real enviando y graficando en la web).
> **Fecha de esta documentación:** 2026-06-09 (rework del flujo de vinculación).

Este documento describe **solo las configuraciones que quedaron funcionando**. Está
redactado para que lo entienda tanto una persona (el profesor evaluador) como una
inteligencia artificial que retome el proyecto.

> **Cambio del 2026-06-09 (vinculación sin MAC):** se reescribió el flujo para vincular
> una ESP32 con la menor fricción posible. Lo importante: la ingesta ahora **identifica la
> placa por su token** (no por la MAC), la **MAC se vincula sola en la primera lectura** y
> el usuario **ya no carga la MAC** en la web. El resto del documento ya refleja este
> flujo nuevo.

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
│ + 3 sensores│  auth: token         │ ::ingest()       │      └────┬────┘
│             │  (MAC autovincula)   │                  │           │
└─────────────┘                      └──────────────────┘           │
                                                                    │ consulta
                                            ┌───────────────────────┘
                                            ▼
                                   ┌───────────────────┐
                                   │  Vistas web PHP   │  Registros IoT por espacio
                                   |RegistrosController│  (1 gráfico por sensor)
                                   └───────────────────┘
```

**Cadena de identificación de los datos (modelo relacional):**

```
users ─< industry_users >─ industries (espacios)
                              └─< esp32_devices (1 placa = 1 token; la MAC se vincula sola)
                                     └─< esp32_sensors (sensor físico = placa + tipo)
                                            └─< sensor_readings (valor + fecha/hora)
sensor_types (catálogo de tipos) ──┘
```

Gracias a esta cadena, cada lectura se asocia sin ambigüedad a **qué sensor**, **de qué
placa**, **de qué espacio** y **en qué momento**. Dos sensores iguales (ej. dos SW-420)
en placas distintas nunca se confunden, porque la lectura cuelga de un `esp32_sensor_id`
que pertenece a una placa identificada por su **token único**. La MAC sigue siendo única
por placa, pero ya **no es la identidad ante el backend**: se graba sola en el primer dato
y queda "candada" a ese token (ver §5.1).

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
| LM393 (RPM) | DO | **GPIO25** | Cuenta de pulsos por interrupción → RPM. (Antes GPIO26; se movió al agregar el buzzer.) |
| LM393 (RPM) | AO | **GPIO35** | Solo **diagnóstico** en consola (no se guarda). |
| DS18B20 (temperatura) | DATA | **GPIO27** | OneWire. **Requiere resistencia de 4.7 kΩ entre DATA y 3.3 V.** |
| LCD1602 I2C (5 V) | SDA / SCL | **GPIO21 / GPIO22** | Vía conversor de nivel 3.3 V ↔ 5 V. Estado de la placa. |
| Buzzer activo | + | **GPIO26** | GPIO26 → 1 kΩ → base BC337. Patrones de aviso. |

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
| `esp32_devices` | Cada placa física. **Identidad ante la API: `token_hash`.** La MAC se vincula sola. |
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
| `mac_address` | VARCHAR(17) **UNIQUE, NULLABLE** | Formato `AA:BB:CC:DD:EE:FF`. **Empieza en NULL** (la web no la carga) y la placa la graba sola en su primera lectura. Luego queda candada (ver §5.1). MySQL admite varios `NULL` en una clave única. |
| `token_hash` | VARCHAR(64) | **SHA-256** del token secreto. Es la **identidad primaria** de la placa ante la API. Nunca se guarda el token en claro acá. |
| `token_secret` | VARCHAR(255) | Token **cifrado y reversible** (AES-256-CBC + HMAC). Permite **re-mostrar** el token con la clave del espacio sin guardarlo en texto plano. La API sigue validando solo `token_hash`. |
| `sector` | VARCHAR | Sector/etiqueta opcional de la placa (puede ser NULL). |
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
| `2026-06-05-000001_AddTokenSecretToEsp32Devices` | Agrega `token_secret` (token cifrado reversible) para poder re-mostrar el token con la clave del espacio. |
| `2026-06-09-000002_MakeEsp32MacNullable` | Hace `mac_address` **NULLABLE** (la placa se autovincula por token en su 1.ª lectura; el registro nace sin MAC). |

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
- La placa se identifica por su **`token`** (no por la MAC ni por un código inventado).
  La MAC se sigue enviando en el cuerpo, pero solo para **vincularla/verificarla** (ver
  abajo): la placa todavía no sabe en qué registro de la web fue dada de alta, así que se
  autentica con el token.
- También acepta el formato de **una sola lectura** (`sensor` + `value`) por
  retrocompatibilidad.

**Lógica de `ingest()` (token-first + autovinculación de MAC):**
1. Exige que lleguen **`mac_address` y `token`** → si falta alguno, **422**.
2. Identifica la placa por **`token`**: `Esp32DeviceModel::findByTokenHash()` busca por el
   SHA-256 del token recibido → si ningún registro coincide, **401** (token inválido).
3. Verifica `is_active = 1` → si está deshabilitada, **403**.
4. **Vincula o verifica la MAC** (`Esp32DeviceModel::normalizeMac`):
   - **Primer contacto** (la placa todavía tiene `mac_address = NULL`): la placa "reclama"
     su registro y se graba su MAC. Si esa MAC ya pertenece a **otra** placa
     (`macExists`), **409**. Si la MAC es inválida, **422**.
   - **Contactos siguientes** (ya tiene MAC): si la MAC entrante **no coincide** con la
     guardada, **409** ("el token ya está vinculado a otra placa"). El token queda así
     "candado" a su placa física: aunque alguien copie el token a otra placa, será
     rechazado.
5. Por cada lectura: resuelve el tipo (`SensorTypeModel::resolve`) y busca el sensor
   asociado a esa placa (`Esp32SensorModel::findForDeviceAndType`).
   - Si el sensor no está asociado o el tipo no existe, **se omite** esa lectura (no
     rompe el resto) → aparece en `skipped` con su motivo.
6. Inserta las lecturas válidas en una transacción (`SensorReadingModel::insertBatch`) y
   actualiza el heartbeat (`touchLastSeen` → `status=online`, `last_seen_at=ahora`).
7. Responde **201** con `saved` (aceptadas) y `skipped` (omitidas).
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
- **Identidad por token (token-first):** la placa se autentica con su token; la API la
  encuentra por `token_hash` (`findByTokenHash`). El token es aleatorio de 16 bytes
  (`generateToken`) → único e imposible de adivinar.
- **Token hasheado:** la base guarda el **SHA-256** del token (`token_hash`), que es lo
  que valida la API. El token en claro se muestra al registrar la placa (para cargarlo en
  el firmware) y puede **re-mostrarse** después con la clave del espacio gracias al
  `token_secret` cifrado reversible (AES-256-CBC + HMAC); nunca se guarda en texto plano.
- **Candado de MAC:** la primera lectura graba la MAC de la placa en su registro; a partir
  de ahí el token solo se acepta desde **esa** MAC (otra MAC → **409**). Así, aunque el
  token se filtre, no sirve en una placa distinta.
- **Flag `is_active`:** permite deshabilitar una placa sin borrarla.
- Comparaciones sensibles en **tiempo constante** (`hash_equals`) contra timing attacks.

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
- **En el sidebar** esta vista es el ítem **"Espacios"**. El orden del menú lateral
  (`templates/sidebar.php`) es: **Panel → Mis ESP32 → Espacios**.

### 5.4 Controladores y modelos (nombres)

**Controladores** (`app/Controllers/`):
- `IoTController` — ingesta de lecturas (API placa): identifica por token y autovincula la MAC.
- `RegistrosController` — Registros IoT por espacio (vistas + API de series). `index()`
  también arma los datos del **modal de configuración** (miembros, tokens, sensores) y
  resuelve el desbloqueo (`space_unlock`).
- `Esp32Controller` — alta/listado/detalle de placas. `store()` crea la placa **sin MAC**
  (`mac_address => null`) y siembra los sensores del espacio (`createDefaultSensors`);
  `show()` arma el QR + botón de configuración (`firmwareSetupUrl` → `http://192.168.4.1/?…`);
  `revealToken()` re-muestra el token de **una** placa con la clave del espacio.
- `SensorController` — `store()` asocia un tipo de sensor a una placa; `deleteFromSpace()`
  borra un sensor desde el modal (requiere espacio desbloqueado).
- `OnboardingController` — crear espacio (industria) + métricas elegidas.
- `IndustryController` — unirse a un espacio (`join`), editar espacio (`update`), quitar
  miembros (`removeMember`) y **revelar los tokens de TODAS las ESP del espacio**
  (`revealTokens`, setea `session('space_unlock')` válido 5 min).

**Modelos** (`app/Models/`):
- `Esp32DeviceModel` — `normalizeMac()`, `generateToken()`, **`hashToken()`**,
  **`findByTokenHash()`** (identidad primaria de la API), `macExists()`, `findByMac()`,
  `tokenMatches()`, **`protectToken()` / `revealToken()`** (token cifrado reversible),
  `devicesForUser()`, `devicesForIndustry()`, `touchLastSeen()`.
- `Esp32SensorModel` — `findForDeviceAndType()`, `sensorsForDevice()`,
  `sensorsForIndustry()`, `belongsToIndustry()`.
- `SensorReadingModel` — `latestForSensor()`, `seriesForSensor()` (+ `insertBatch`).
- `SensorTypeModel` — `findByCode()`, `resolve()` (acepta code o nombre).
- `IndustryModel`, `IndustryUserModel` (`membersForIndustry()`), `IndustryMetricModel`.

### 5.5 Rutas del módulo IoT (`app/Config/Routes.php`)

```php
// Registros IoT por-espacio
$routes->get('registros-iot', 'RegistrosController::index');
$routes->get('registros-iot/(:num)', 'RegistrosController::space/$1');
$routes->get('api/espacio/(:num)/lecturas', 'RegistrosController::readings/$1');

// Gestión del espacio (modal "Configurar espacio", solo admin)
$routes->post('espacios/(:num)/editar', 'IndustryController::update/$1');
$routes->post('espacios/(:num)/miembros/(:num)/eliminar', 'IndustryController::removeMember/$1/$2');
$routes->post('espacios/(:num)/tokens', 'IndustryController::revealTokens/$1');             // revela tokens + desbloquea
$routes->post('espacios/(:num)/sensores/(:num)/eliminar', 'SensorController::deleteFromSpace/$1/$2');

// ESP32
$routes->get('esp32', 'Esp32Controller::index');
$routes->get('esp32/agregar', 'Esp32Controller::createForm');
$routes->post('esp32/agregar', 'Esp32Controller::store');                 // crea la placa SIN MAC
$routes->post('esp32/(:num)/token', 'Esp32Controller::revealToken/$1');   // re-mostrar token (clave del espacio)
$routes->post('esp32/(:num)/sensores', 'SensorController::store/$1');
$routes->get('esp32/(:num)', 'Esp32Controller::show/$1');
$routes->get('api/esp32/(:num)/lecturas', 'Esp32Controller::readings/$1');

// Ingesta desde la ESP32 (auth por TOKEN; la MAC se autovincula. Exenta de CSRF/authGuard)
$routes->post('api/iot/lectura', 'IoTController::ingest');
```

> **Modal de configuración del espacio:** desde *Espacios* (kebab → "Configurar espacio")
> un admin puede editar el espacio, quitar miembros, y —tras ingresar la clave del
> espacio una vez— **ver los tokens de todas las ESP** y **borrar sensores**. Esos dos
> últimos quedan habilitados 5 minutos (`session('space_unlock')`).

> **Código legacy eliminado:** la vista vieja con datos demo (`IotReadings.php`,
> `IotReadingRepository.php`, `Views/registros-iot.php` y la ruta `api/iot-readings`)
> fue **removida** y reemplazada por `RegistrosController` con datos reales.

---

## 6. Firmware ESP32 (MicroPython)

El firmware vive en **dos copias que se mantienen en sync**: la copia de trabajo de Thonny
en **`C:\Users\josem\prevcs-firmware\`** y una copia versionada en el repo en
**`C:\xampp\htdocs\PreventiveControlSystem\firmware\`**. Los `.py` se suben a la raíz `/`
de la placa con Thonny.

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
2. Si hay config → conecta al WiFi e imprime la **MAC**. **No hace falta registrarla en la
   web**: viaja en cada lote y el backend la vincula sola al token en la primera lectura.
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
> recibe datos), **no** la URL de una página como `/esp32/6`. Normalmente **no se tipea a
> mano**: la web la precarga en el portal (QR/botón). Si algún día la API queda fija, se
> puede hardcodear una vez en `config_manager.DEFAULT_SERVER_URL` y el usuario ni la ve.

> **Nota:** el campo `wifi_ip` quedó **eliminado** (la placa toma su IP por DHCP). Si tenés
> un `config.json` viejo con `wifi_ip`, simplemente se ignora.

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
acepte (recuerda el último que funcionó). Requiere **vincular la placa en cada servidor**
(cada base da su propio token, uno por endpoint); la **MAC se autovincula sola** en cada
base en la primera lectura aceptada.

### 6.4 Modo Access Point (configuración del usuario final)

Si no hay `config.json`, la placa crea la red WiFi **`PREVCS-CONFIG`** (abierta) y levanta
un portal en `http://192.168.4.1`. El portal (`wifi_manager._page`) está **restyleado al
tema claro de la web** (verde musgo `#5b7a3c`, botón tipo pill) y pensado para que el
usuario **casi no tipee nada**:

- **El usuario SOLO elige su red WiFi.** El SSID se toma de una **lista de redes
  escaneadas** por la placa (`_scan_networks`, `<select>` con mejor señal primero); como
  el ESP32 es 2.4 GHz por hardware, todo lo que aparece ya es 2.4 GHz. Si su red no
  aparece, un bloque **avanzado** (`<details>`) permite escribir el SSID a mano.
- **`server_url`, `device_token` y `device_name` van ocultos (precargados).** Llegan como
  query string del link/QR **"Abrir formulario de la ESP32"** de la web
  (`http://192.168.4.1/?server_url=…&device_token=…&device_name=…`), así que el usuario no
  los ve ni los copia. Si no vinieran, `server_url` cae a `config_manager.DEFAULT_SERVER_URL`.
- **`pulses_per_rev`** (pulsos por vuelta del sensor RPM) se movió al bloque **avanzado**;
  por defecto 1.
- **Se eliminó el campo `wifi_ip`** (la placa usa DHCP).
- La **MAC** se muestra solo a título **informativo**: ya no hay que registrarla, se
  vincula sola en la primera lectura.

Al guardar, la placa escribe `config.json` (formato simple) y se reinicia en modo normal.

> La URL/QR los genera la web en la pantalla de detalle de la ESP (`esp32/detalle.php`):
> el QR se renderiza **offline** con `public/js/qrcode.min.js` (el token nunca sale de la
> LAN). Ver §7.B–C.

---

## 7. Puesta en marcha paso a paso

**A. Vincular la placa en la web (sin MAC)**
1. Entrar a **Espacios** y, como admin del espacio, tocar **Vincular ESP32** (o
   *Mis ESP32 → Añadir*).
2. Elegir el **espacio** y un **nombre** para la placa → **Vincular ESP32**.
   - **No se carga ninguna MAC.** La web genera el **token** y, en la misma operación,
     **crea solos los sensores** del espacio (los tipos definidos en sus métricas, ej.
     Vibración, RPM, Temperatura). Si hace falta, se pueden agregar sensores extra desde
     la pantalla de la ESP.
3. Quedás en la **pantalla de detalle de la ESP**, que muestra el **token**, un **QR** y un
   **botón "Abrir formulario de la ESP32"** (ambos llevan a `http://192.168.4.1/?…` con la
   URL del server, el token y el nombre ya cargados). La MAC figura **"Sin vincular"**
   hasta el primer dato.

**B. Cargar el firmware en la ESP32 (Thonny)**
4. Conectar la placa por USB; en Thonny elegir intérprete **MicroPython (ESP32)** + puerto COM.
5. Subir a la raíz `/` los 6 `.py` (`boot, main, config_manager, wifi_manager, sensors, backend`).
   *(Para subir/borrar archivos, la placa debe estar detenida en `>>>`.)*

**C. Configurar la placa (el usuario solo elige su WiFi)**
6. Reiniciar → se crea la red `PREVCS-CONFIG` → **conectarse a esa red**.
7. Abrir el formulario de la placa **sin tipear nada de la API**:
   - **Celular:** escaneá el **QR** de la pantalla de detalle (con el celu ya conectado a
     `PREVCS-CONFIG`), o tocá el **botón** si estás navegando desde el mismo celu.
   - **Solo compu:** tocá el **botón "Abrir formulario de la ESP32"**.
   - El formulario ya trae **URL del server, token y nombre** (ocultos).
8. En el formulario, **elegí tu red WiFi de la lista** (2.4 GHz) y poné la contraseña.
   *(El ESP32 no funciona con 5 GHz; si tu red no aparece, usá el bloque avanzado.)*
9. **Guardar y reiniciar.** La placa se conecta, envía su primer lote y **se vincula sola**:
   el backend graba su MAC y candaa el token a esa placa. En la consola de Thonny debe
   aparecer `[http] OK 201`.

**D. Verificar**
10. En la web, **Espacios → (espacio) → Registros IoT**: los gráficos se llenan y la placa
    figura **online**. En el **detalle de la ESP**, la MAC ya muestra la dirección real
    (dejó de decir "Sin vincular").

---

## 8. Pruebas y verificación

Pruebas reales del endpoint (todas verificadas):

| Caso | Resultado esperado | Resultado |
|---|---|---|
| Lote válido (3 lecturas) | 201, guarda y marca online | ✅ |
| **Primer contacto** (placa sin MAC) | 201 + graba `mac_address` (autovinculación) | ✅ |
| Token inválido | 401 | ✅ |
| **MAC distinta a la candada** (token usado en otra placa) | 409 | ✅ |
| Faltan `mac_address` o `token` | 422 | ✅ |
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
| `[http] Rechazado 401` | El token del `config.json` no coincide con ninguna placa | Recargar el token tal cual lo dio la web (QR/botón ya lo precargan; si lo pusiste a mano, revisalo). |
| `[http] Rechazado 409` | El **token ya está candado a otra placa** (otra MAC). Pasa si copiaste el mismo token a una segunda placa, o si reflasheaste una placa con la MAC cambiada | Usar **un token por placa física** (uno nuevo desde la web). El candado es a propósito: protege contra tokens filtrados. |
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
- Todas las pruebas de seguridad del endpoint pasan (201/401/403/409/422).
- **Flujo nuevo (2026-06-09) verificado de punta a punta:** vinculación sin MAC →
  autovinculación de la MAC en el primer dato (201) → candado del token a esa placa
  (409 ante otra MAC) → camino feliz (201).

---

## 11. Glosario

| Término | Significado |
|---|---|
| **Espacio** | Una industria/planta/máquina (`industries`). Agrupa placas y sensores. |
| **ESP32 / placa** | El dispositivo físico que mide y envía (`esp32_devices`), identificado ante el backend por su **token**. |
| **Token** | Secreto por placa y su **identidad ante el backend**. Se envía en cada lectura; la base guarda su hash SHA-256 (y una copia cifrada reversible para re-mostrarlo). |
| **MAC** | Dirección física única de la placa. Ya **no** es su identidad: se vincula sola en el primer dato y queda candada al token. |
| **Autovinculación** | La placa graba su MAC en su registro al enviar el **primer** lote (la web la crea con `mac_address = NULL`). |
| **Candado de MAC** | Tras la 1.ª lectura, el token solo se acepta desde esa MAC; otra MAC → 409. Protege contra tokens filtrados. |
| **Endpoint** | URL que recibe los datos: `POST /api/iot/lectura`. |
| **Ventana de medición** | Los 15 s en que la placa mide antes de enviar. |
| **Heartbeat** | Señal de vida: al recibir datos, la placa pasa a `online`. |
| **Ingesta** | Proceso de recibir, autenticar y guardar las lecturas (`IoTController::ingest`). |
| **AP / portal** | Modo Access Point para configurar la placa sin saber de código. |
