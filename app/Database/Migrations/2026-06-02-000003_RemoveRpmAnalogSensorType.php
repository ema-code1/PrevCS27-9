<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Decisión de diseño: la RPM es UN solo sensor. El valor analógico (AO) del
 * LM393 no es una métrica propia: la RPM real sale de los pulsos del DO, y de
 * ahí ya se deriva "andando / detenido" (RPM > 0). Por eso quitamos el tipo
 * rpm_analog que se había sembrado, para no mostrar dos opciones al usuario.
 *
 * El FK de esp32_sensors es ON DELETE CASCADE, así que borrar el tipo limpia
 * también cualquier sensor/lectura asociado (solo había datos de prueba).
 */
class RemoveRpmAnalogSensorType extends Migration
{
    public function up()
    {
        $this->db->table('sensor_types')->where('code', 'rpm_analog')->delete();
    }

    public function down()
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
}
