# PrevCS · Preventive Control System

Sistema de mantenimiento preventivo para motores industriales. Una placa **ESP32**
con sensores de **temperatura (DS18B20)**, **vibración (SW-420)** y **RPM (LM393)**
manda una lectura por minuto a una web en **PHP (CodeIgniter 4)** que la guarda en
**MySQL**, la grafica en vivo y avisa cuando un valor sale del rango configurado.

**Instalar o actualizar en una PC:** prender Apache y MySQL en XAMPP y hacer doble clic en `setup.cmd`.
Guía completa: [docs/INSTALACION.md](docs/INSTALACION.md).

## Recorrido de los datos

```
Sensor → ESP32 (MicroPython) → POST /api/iot/lectura → MySQL (sensor_readings)
       → Panel web (gráficos, historial, alertas) → Usuario
                 ↘ respuesta con "alerts" → LCD + buzzer de la placa
```

## Qué hace

- **Cuentas:** registro con verificación por correo, 2FA opcional, recuperación de
  contraseña por enlace de un solo uso, contraseñas con `password_hash`.
- **Espacios:** uno por planta o sector, con invitaciones por correo, roles
  (admin / supervisor) y permisos de lectura o edición por placa.
- **Placas ESP32:** alta desde la web, vinculación por QR y token; la MAC queda
  asociada en la primera lectura.
- **Datos en vivo:** gráfico por sensor que se refresca cada 15 s, filtro por fechas,
  historial y descarga en CSV.
- **Alertas:** mínimo y máximo por sensor; al cruzarlos avisa a todo el espacio por
  la campana de notificaciones y la placa suena.
- **Mantenimiento:** tablero de tareas por placa, stock de repuestos con avisos y
  calendario con recordatorios.
- **Suscripción:** PrevCS Pro por Mercado Pago, con cancelación desde la cuenta.

## Documentación

- [docs/INSTALACION.md](docs/INSTALACION.md): instalación con XAMPP.
- [docs/SISTEMA-IOT.md](docs/SISTEMA-IOT.md): hardware, base de datos, API y firmware.
- [docs/MERCADOPAGO-INTEGRACION.md](docs/MERCADOPAGO-INTEGRACION.md): suscripciones.
- [firmware/README_FIRMWARE.md](firmware/README_FIRMWARE.md): cómo cargar la placa.

Requisitos: PHP 8.2+ con `intl` y `mbstring`, MySQL/MariaDB (XAMPP) y Composer
para el SDK de Mercado Pago.
