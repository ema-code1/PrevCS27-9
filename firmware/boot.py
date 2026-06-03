# boot.py — se ejecuta automaticamente al encender la ESP32, antes de main.py.
# Lo dejamos casi vacio a proposito: solo bajamos el ruido de logs del sistema.
# Toda la logica vive en main.py para que sea mas facil de leer en Thonny.

import esp
esp.osdebug(None)  # silencia mensajes internos del nucleo en la consola

import gc
gc.collect()

print("[boot] ESP32 lista. Arrancando main.py...")
