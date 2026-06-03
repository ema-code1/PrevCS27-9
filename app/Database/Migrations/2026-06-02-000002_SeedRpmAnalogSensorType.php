<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Agrega el tipo de sensor "rpm_analog": el valor analógico (AO) del módulo
 * LM393. Es opcional/diagnóstico (la RPM real se calcula con los pulsos del DO).
 * Solo se almacena si el usuario asocia este sensor a la placa.
 */
class SeedRpmAnalogSensorType extends Migration
{
    public function up()
    {
        $exists = $this->db->table('sensor_types')
            ->where('code', 'rpm_analog')
            ->countAllResults() > 0;

        if (! $exists) {
            $this->db->table('sensor_types')->insert([
                'code'          => 'rpm_analog',
                'name'          => 'RPM (analógico)',
                'unit'          => 'adc',
                'default_chart' => 'line',
            ]);
        }
    }

    public function down()
    {
        $this->db->table('sensor_types')->where('code', 'rpm_analog')->delete();
    }
}
