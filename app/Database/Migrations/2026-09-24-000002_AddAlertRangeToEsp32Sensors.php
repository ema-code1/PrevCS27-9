<?php

namespace App\Database\Migrations;

use App\Libraries\SensorAlerts;
use CodeIgniter\Database\Migration;

/**
 * Rango aceptable por sensor y el estado en que lo dejó su última lectura.
 *
 *  - alert_min / alert_max: límites opcionales. Fuera de ellos, la ingesta IoT
 *    avisa a todo el espacio (SensorAlerts).
 *  - alert_state: ok | high | low. Guarda el estado anterior para avisar solo
 *    cuando cambia, no en cada lectura.
 *
 * También corrige la unidad de vibración: el SW-420 no mide velocidad (mm/s),
 * la placa manda cuántos eventos de vibración contó en la ventana de medición.
 */
class AddAlertRangeToEsp32Sensors extends Migration
{
    public function up()
    {
        $this->forge->addColumn('esp32_sensors', [
            'alert_min' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,3',
                'null'       => true,
                'after'      => 'label',
            ],
            'alert_max' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,3',
                'null'       => true,
                'after'      => 'alert_min',
            ],
            'alert_state' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'after'      => 'alert_max',
            ],
        ]);

        // Los sensores que ya existen arrancan con el rango por defecto de su tipo.
        foreach (SensorAlerts::DEFAULTS as $code => [$min, $max]) {
            $this->db->query(
                'UPDATE esp32_sensors s JOIN sensor_types t ON t.id = s.sensor_type_id
                    SET s.alert_min = ?, s.alert_max = ?
                  WHERE t.code = ?',
                [$min, $max, $code]
            );
        }

        $this->db->table('sensor_types')->where('code', 'vibration')->update(['unit' => 'eventos']);
    }

    public function down()
    {
        $this->db->table('sensor_types')->where('code', 'vibration')->update(['unit' => 'mm/s']);
        $this->forge->dropColumn('esp32_sensors', ['alert_min', 'alert_max', 'alert_state']);
    }
}
