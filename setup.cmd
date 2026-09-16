@echo off
REM Instala o actualiza PrevCS en esta PC: dependencias, .env, base de datos y migraciones.
REM Requisitos: XAMPP con Apache y MySQL prendidos, y Composer instalado.
REM Uso: doble clic, o ".\setup" desde la carpeta del proyecto. Se puede repetir sin riesgo.

cd /d "%~dp0"

set "PHP=php"
where php >nul 2>nul
if errorlevel 1 set "PHP=C:\xampp\php\php.exe"

"%PHP%" -v >nul 2>nul
if errorlevel 1 (
  echo No encuentro PHP. Instala XAMPP en C:\xampp o agrega php al PATH.
  pause
  exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
  echo Composer no esta instalado. Descargalo de https://getcomposer.org/Composer-Setup.exe
  pause
  exit /b 1
)

echo.
echo  [1/2] Dependencias de Composer
call composer install --no-dev --no-interaction
if errorlevel 1 (
  echo composer install fallo. Revisa el mensaje de arriba.
  pause
  exit /b 1
)

echo.
echo  [2/2] .env, base de datos y migraciones
"%PHP%" spark prevcs:setup

echo.
pause
