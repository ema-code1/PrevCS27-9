<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateEsp32DevicesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'industry_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            // MAC normalizada en mayúsculas, ej: A4:CF:12:AB:88:01
            'mac_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 17,
            ],
            'sector' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            // secreto que la placa envía en cada lectura (auth de la API IoT)
            'device_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'offline',
            ],
            'last_seen_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // La MAC identifica la placa de forma única en todo el sistema
        $this->forge->addUniqueKey('mac_address');
        $this->forge->addKey('industry_id', false, false, 'fk_esp32_devices_industry');
        $this->forge->addForeignKey('industry_id', 'industries', 'id', 'CASCADE', 'CASCADE', 'fk_esp32_devices_industry');
        $this->forge->createTable('esp32_devices', true);
    }

    public function down()
    {
        $this->forge->dropTable('esp32_devices', true);
    }
}
