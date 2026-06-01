<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateEsp32SensorsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'esp32_device_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'sensor_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            // etiqueta opcional, ej: "Temp. motor 1"
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // Una placa no repite el mismo tipo de sensor
        $this->forge->addUniqueKey(['esp32_device_id', 'sensor_type_id']);
        $this->forge->addKey('esp32_device_id', false, false, 'fk_esp32_sensors_device');
        $this->forge->addKey('sensor_type_id', false, false, 'fk_esp32_sensors_type');
        $this->forge->addForeignKey('esp32_device_id', 'esp32_devices', 'id', 'CASCADE', 'CASCADE', 'fk_esp32_sensors_device');
        $this->forge->addForeignKey('sensor_type_id', 'sensor_types', 'id', 'RESTRICT', 'CASCADE', 'fk_esp32_sensors_type');
        $this->forge->createTable('esp32_sensors', true);
    }

    public function down()
    {
        $this->forge->dropTable('esp32_sensors', true);
    }
}
