<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSensorReadingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'auto_increment' => true,
            ],
            'esp32_sensor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'value' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,3',
            ],
            'measured_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // Index compuesto para consultas por sensor y rango temporal
        $this->forge->addKey(['esp32_sensor_id', 'measured_at']);
        $this->forge->addForeignKey('esp32_sensor_id', 'esp32_sensors', 'id', 'CASCADE', 'CASCADE', 'fk_sensor_readings_sensor');
        $this->forge->createTable('sensor_readings', true);
    }

    public function down()
    {
        $this->forge->dropTable('sensor_readings', true);
    }
}
