<?php

namespace App\Commands;

use App\Libraries\SpaceEventReminders;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * spark calendario:recordatorios
 *
 * Reparte los recordatorios vencidos del calendario de los espacios entre los
 * encargados de cada evento.
 *
 * El mismo barrido corre solo en cada lectura del feed de la campana, así que
 * con la app abierta los avisos ya salen sin este comando. Está para el caso
 * en que nadie esté conectado a la hora del recordatorio: colgado del cron (o
 * del Programador de tareas de Windows) los avisos salen igual y esperan en la
 * bandeja.
 *
 * Uso:
 *   php spark calendario:recordatorios
 *
 * Cron de ejemplo, cada 5 minutos:
 *   *\/5 * * * * cd /ruta/al/proyecto && php spark calendario:recordatorios >> writable/logs/recordatorios.log 2>&1
 */
class CalendarReminders extends BaseCommand
{
    protected $group       = 'PrevCS';
    protected $name        = 'calendario:recordatorios';
    protected $description = 'Reparte los recordatorios vencidos del calendario entre los encargados de cada evento.';

    protected $usage   = 'calendario:recordatorios';
    protected $options = [];

    public function run(array $params)
    {
        $sent = SpaceEventReminders::flushDue();

        if ($sent === 0) {
            CLI::write('Sin recordatorios vencidos.', 'dark_gray');

            return;
        }

        CLI::write('Recordatorios repartidos: ' . $sent, 'green');
    }
}
