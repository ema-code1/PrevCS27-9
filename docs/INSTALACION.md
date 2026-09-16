# Instalación de PrevCS en una PC

## Requisitos (una sola vez por PC)

| Programa | Para qué | Dónde |
|---|---|---|
| **XAMPP** con PHP 8.2 o superior | Apache + MySQL + PHP | https://www.apachefriends.org |
| **Composer** | Dependencias (SDK de Mercado Pago) | https://getcomposer.org/Composer-Setup.exe |
| **Git** | Clonar el repo | https://git-scm.com |
| ngrok *(opcional)* | URL pública HTTPS para webhooks | `winget install ngrok -s msstore` |

## Instalar

1. Clonar dentro de `C:\xampp\htdocs`:
   ```bash
   git clone https://github.com/JoseMangold/PreventiveControlSystem.git
   ```
2. Prender **Apache** y **MySQL** en el panel de XAMPP.
3. Doble clic en **`setup.cmd`**.
4. Abrir http://localhost/PreventiveControlSystem/public/

`setup.cmd` hace todo esto:

| Paso | Detalle |
|---|---|
| Dependencias | `composer install --no-dev` (crea `vendor/`) |
| PHP | Revisa versión y extensiones (`intl`, `mbstring`, `mysqli`, `curl`, `openssl`, `json`) |
| `.env` | Si no existe, lo copia de `.env.example` con valores para XAMPP |
| `writable/` | Revisa permisos de `cache`, `logs`, `session` y `uploads` |
| Base de datos | Crea `prevcs` si no existe |
| Migraciones | Crea o actualiza todas las tablas |
| Resumen | Muestra qué claves faltan en el `.env`, sin imprimir valores |

## Actualizar (después de un `git pull`)

Doble clic en **`setup.cmd`** de nuevo. Es seguro repetirlo: solo instala lo que falta y corre las migraciones nuevas. Nunca borra datos.

Sin Composer de por medio (solo base de datos):

```bash
php spark prevcs:setup
```

## Lo que queda a mano (según lo que uses)

| Función | Qué cargar en el `.env` |
|---|---|
| Correos (verificación, invitaciones) | `email.SMTPUser`, `email.SMTPPass`, `email.fromEmail` |
| Suscripciones con Mercado Pago | `MP_ACCESS_TOKEN`, `MP_PUBLIC_KEY` y luego `php spark mp:plan` |
| Webhooks de Mercado Pago | `MP_WEBHOOK_SECRET`, `APP_URL` con la URL de ngrok y `app.tunnelHost` |

Detalle de Mercado Pago: [MERCADOPAGO-INTEGRACION.md](MERCADOPAGO-INTEGRACION.md).

## Problemas comunes

| Mensaje | Solución |
|---|---|
| `No se pudo conectar a MySQL` | Prender MySQL en XAMPP; revisar `database.default.*` en el `.env` |
| `Faltan extensiones de PHP` | En `C:\xampp\php\php.ini`, quitar el `;` de `extension=...` y reiniciar Apache |
| `Composer no esta instalado` | Instalar Composer y abrir una terminal nueva |
| `Table '...' doesn't exist` | Correr `setup.cmd` (faltan migraciones) |

## Para desarrolladores

- **Toda tabla nueva va en una migración** (`app/Database/Migrations`), no en un `.sql` suelto: así `setup.cmd` la crea en cualquier PC.
- Los archivos de `sql/` son dumps y scripts históricos, solo de referencia.
