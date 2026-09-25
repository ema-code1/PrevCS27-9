<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hasta acá la app corría con appTimezone = 'UTC', así que todo lo que PHP
 * sellaba con date() quedó 3 horas adelantado respecto de la hora de pared de
 * Argentina (la que usa MySQL con CURRENT_TIMESTAMP). Ahora la app corre en
 * America/Argentina/Buenos_Aires: esta migración corre esas columnas a la hora
 * local para que lo viejo y lo nuevo se lean con el mismo reloj.
 *
 * El corrimiento sale de la propia base (NOW() vs UTC_TIMESTAMP()), así que en
 * una PC configurada en UTC no mueve nada.
 */
class ShiftUtcTimestampsToLocal extends Migration
{
    /** tabla => columnas selladas por PHP (date('Y-m-d H:i:s')). */
    private const COLUMNS = [
        'sensor_readings' => ['measured_at'],
        'esp32_devices'   => ['last_seen_at'],
        'kanban_tasks'    => ['completed_at'],
        'kanban_comments' => ['edited_at'],
    ];

    public function up()
    {
        $this->shift($this->offsetMinutes());
    }

    public function down()
    {
        $this->shift(-$this->offsetMinutes());
    }

    private function offsetMinutes(): int
    {
        $row = $this->db->query('SELECT TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), NOW()) AS diff')->getRowArray();

        return (int) ($row['diff'] ?? 0);
    }

    private function shift(int $minutes): void
    {
        if ($minutes === 0) {
            return;
        }

        foreach (self::COLUMNS as $table => $columns) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! $this->db->fieldExists($column, $table)) {
                    continue;
                }

                $this->db->query(
                    "UPDATE `{$table}` SET `{$column}` = `{$column}` + INTERVAL {$minutes} MINUTE WHERE `{$column}` IS NOT NULL"
                );
            }
        }
    }
}
