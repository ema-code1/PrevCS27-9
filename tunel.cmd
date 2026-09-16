@echo off
REM Abre el tunel publico de ngrok hacia XAMPP (Apache en el puerto 80).
REM Requisito por PC: ngrok instalado y "ngrok config add-authtoken ..." hecho una vez.
REM Uso: doble clic, o ".\tunel" desde la carpeta del proyecto. Ctrl+C para cortarlo.

where ngrok >nul 2>nul
if errorlevel 1 (
  echo ngrok no esta instalado. Instalalo con: winget install ngrok -s msstore
  pause
  exit /b 1
)

echo.
echo  App:      https://moonrise-colt-islamic.ngrok-free.dev/PreventiveControlSystem/public/
echo  Panel:    http://127.0.0.1:4040  (peticiones que llegan por el tunel)
echo.

ngrok http --url=moonrise-colt-islamic.ngrok-free.dev --traffic-policy-file="%~dp0ngrok-policy.yml" 80
pause
