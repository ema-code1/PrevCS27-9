<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIndustryMetricsTable extends Migration
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
            'sensor_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            // gráfico sugerido elegído en el onboarding (line/area/gauge/bars/timeline)
            'suggested_chart' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'line',
            ],
        ]);

        $this->forge->addKey('id', true);
        // Una industria no repite la misma métrica
        $this->forge->addUniqueKey(['industry_id', 'sensor_type_id']);
        $this->forge->addKey('industry_id', false, false, 'fk_industry_metrics_industry');
        $this->forge->addKey('sensor_type_id', false, false, 'fk_industry_metrics_type');
        $this->forge->addForeignKey('industry_id', 'industries', 'id', 'CASCADE', 'CASCADE', 'fk_industry_metrics_industry');
        $this->forge->addForeignKey('sensor_type_id', 'sensor_types', 'id', 'RESTRICT', 'CASCADE', 'fk_industry_metrics_type');
        $this->forge->createTable('industry_metrics', true);
    }

    public function down()
    {
        $this->forge->dropTable('industry_metrics', true);
    }
}
