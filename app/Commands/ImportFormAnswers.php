<?php

namespace App\Commands;

use App\Libraries\FormImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * spark form:import
 *
 * Comando que ejecuta la importacion del CSV publicado por Google Sheets
 * hacia la tabla `form_answers`. Pensado para correr en cron job.
 *
 * Uso:
 *   php spark form:import
 *
 * Cron de ejemplo (Windows Task Scheduler / cron de Linux), cada 30 min:
 *   *\/30 * * * * cd /ruta/al/proyecto && php spark form:import >> writable/logs/form_import.log 2>&1
 */
class ImportFormAnswers extends BaseCommand
{
    protected $group       = 'PrevCS';
    protected $name        = 'form:import';
    protected $description = 'Importa respuestas del Google Forms publicado en form_answers (MySQL).';

    protected $usage   = 'form:import';
    protected $options = [];

    public function run(array $params)
    {
        CLI::write('▶  Importando respuestas desde Google Sheets...', 'cyan');

        $service = new FormImportService();
        $result  = $service->importFromUrl();

        CLI::newLine();
        CLI::write('Resultado:', 'yellow');
        CLI::table(
            [
                ['Estado',          $result['ok'] ? 'OK' : 'CON ERRORES'],
                ['Filas en CSV',    (string) $result['total_in_csv']],
                ['Insertadas',      (string) $result['inserted']],
                ['Saltadas (dup)',  (string) $result['skipped']],
                ['Errores',         (string) count($result['errors'])],
            ],
            ['Metrica', 'Valor']
        );

        if ($result['errors'] !== []) {
            CLI::newLine();
            CLI::write('Errores:', 'red');
            foreach ($result['errors'] as $err) {
                CLI::write('  · ' . $err, 'red');
            }
        }

        return $result['ok'] ? 0 : 1;
    }
}
