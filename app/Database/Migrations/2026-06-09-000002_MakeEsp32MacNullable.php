<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * La MAC ya no se carga a mano en la web: la placa se autovincula por TOKEN en
 * su primera lectura y la MAC se graba en ese momento. Por eso la columna debe
 * permitir NULL (registro creado sin MAC, vinculada luego). El índice único se
 * mantiene: MySQL permite múltiples NULL en una clave única.
 */
class MakeEsp32MacNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('esp32_devices', [
            'mac_address' => [
                'name'       => 'mac_address',
                'type'       => 'VARCHAR',
                'constraint' => 17,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('esp32_devices', [
            'mac_address' => [
                'name'       => 'mac_address',
                'type'       => 'VARCHAR',
                'constraint' => 17,
                'null'       => false,
            ],
        ]);
    }
}
