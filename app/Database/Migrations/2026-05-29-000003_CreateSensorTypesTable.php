<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSensorTypesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            // code estable usado por la API IoT y el frontend
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
            ],
            'unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            // gráfico sugerido por defecto: line, area, gauge, bars, timeline
            'default_chart' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'line',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('sensor_types', true);

        // Tipos de sensores iniciales (garantiza presencia sin paso de seed manual)
        $this->db->table('sensor_types')->insertBatch([
            ['code' => 'temperature', 'name' => 'Temperatura',   'unit' => '°C',    'default_chart' => 'line'],
            ['code' => 'humidity',    'name' => 'Humedad',       'unit' => '%',     'default_chart' => 'area'],
            ['code' => 'vibration',   'name' => 'Vibración',     'unit' => 'mm/s',  'default_chart' => 'line'],
            ['code' => 'rpm',         'name' => 'RPM',           'unit' => 'rev/min', 'default_chart' => 'gauge'],
            ['code' => 'current',     'name' => 'Corriente',     'unit' => 'A',     'default_chart' => 'bars'],
            ['code' => 'on_off',      'name' => 'Estado ON/OFF', 'unit' => null,    'default_chart' => 'timeline'],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('sensor_types', true);
    }
}
