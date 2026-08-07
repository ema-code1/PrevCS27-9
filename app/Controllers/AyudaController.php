<?php

namespace App\Controllers;

/**
 * Centro de ayuda.
 *
 *  GET ayuda → preguntas frecuentes (acordeón) + navegación interna.
 *
 * Es pública: se abre en una pestaña nueva desde el menú de navegación y
 * también tiene que ser alcanzable sin sesión iniciada.
 */
class AyudaController extends BaseController
{
    public function index(): string
    {
        // Contenido demo: se reemplaza cuando exista la base de conocimiento.
        $sections = [
            [
                'id'    => 'primeros-pasos',
                'title' => 'Primeros pasos',
                'items' => [
                    [
                        'q' => 'Creé mi cuenta pero no puedo entrar',
                        'a' => 'Revisá la casilla del correo con el que te registraste y confirmá la cuenta con el código de verificación. Si no llegó, pedí un código nuevo desde la pantalla de verificación: el anterior queda invalidado.',
                    ],
                    [
                        'q' => '¿Cuál es la diferencia entre empresa y espacio?',
                        'a' => 'La empresa es la organización dueña de los datos. El espacio es cada planta, línea o sector dentro de esa empresa, y es la unidad que agrupa las placas ESP32, el stock y los permisos.',
                    ],
                    [
                        'q' => '¿Cómo sumo a un supervisor a mi espacio?',
                        'a' => 'Pasale el identificador del espacio y la clave del espacio. Con esos dos datos puede unirse desde "Unirse a un espacio". Después, desde Configurar espacio, definís si tiene permiso de lectura o de edición.',
                    ],
                ],
            ],
            [
                'id'    => 'dispositivos',
                'title' => 'Dispositivos ESP32',
                'items' => [
                    [
                        'q' => 'Mi ESP32 aparece como "Sin vincular"',
                        'a' => 'La placa todavía no reportó su dirección MAC. Verificá que el firmware tenga cargado el token del dispositivo y que la placa tenga salida a internet: en el primer envío correcto la MAC se registra sola.',
                    ],
                    [
                        'q' => 'El indicador quedó en gris y no vuelve a verde',
                        'a' => 'El punto se pone gris cuando no llegan lecturas nuevas. Empezá por la alimentación y el WiFi de la placa; si el problema sigue, revisá que el token no haya sido regenerado desde Configurar espacio.',
                    ],
                    [
                        'q' => '¿Dónde veo el token de una placa?',
                        'a' => 'En Configurar espacio, sección "Tokens y sensores". Hay que desbloquear con la clave del espacio y queda visible durante cinco minutos.',
                    ],
                ],
            ],
            [
                'id'    => 'registros',
                'title' => 'Registros y gráficos',
                'items' => [
                    [
                        'q' => 'Los gráficos me aparecen vacíos',
                        'a' => 'Suele ser el rango de fechas: si no hay lecturas en el período elegido no se dibuja nada. Ampliá el rango y confirmá que el sensor esté enviando datos desde la vista de la placa.',
                    ],
                    [
                        'q' => 'Borré un sensor por error',
                        'a' => 'El borrado de un sensor elimina también sus lecturas y no se puede deshacer. Creá el sensor de nuevo con el mismo nombre: la placa vuelve a registrar desde la próxima lectura.',
                    ],
                ],
            ],
            [
                'id'    => 'cuenta',
                'title' => 'Cuenta y seguridad',
                'items' => [
                    [
                        'q' => 'Olvidé la clave del espacio',
                        'a' => 'La clave del espacio solo puede cambiarla el admin principal desde Configurar espacio, y para hacerlo necesita la clave actual. Si nadie la tiene, escribinos desde el correo del admin.',
                    ],
                    [
                        'q' => '¿Cómo cambio mi contraseña?',
                        'a' => 'Desde Administrar cuenta, en el menú de navegación. Si no podés iniciar sesión, usá "Olvidé mi contraseña" en la pantalla de acceso.',
                    ],
                ],
            ],
        ];

        return view('ayuda/index', [
            'title'    => 'Ayuda | Preventive Control System',
            'sections' => $sections,
        ]);
    }
}
