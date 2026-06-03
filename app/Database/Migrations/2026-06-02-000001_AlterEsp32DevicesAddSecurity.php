<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Endurece la autenticación de las placas ESP32:
 *  - token_hash: guardamos SHA-256 del token, nunca el secreto en claro.
 *  - is_active:  permite deshabilitar una placa sin borrarla (la ingesta la rechaza).
 *  - se elimina device_token (texto plano). Seguro: aún no hay placas registradas.
 */
class AlterEsp32DevicesAddSecurity extends Migration
{
    public function up()
    {
        $this->forge->addColumn('esp32_devices', [
            // SHA-256 (64 hex) del device_token; se compara en tiempo constante.
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'sector',
            ],
            // 1 = la placa puede enviar lecturas; 0 = deshabilitada.
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'token_hash',
            ],
        ]);

        // Quitamos el token en texto plano (0 filas → sin pérdida de datos).
        if ($this->db->fieldExists('device_token', 'esp32_devices')) {
            $this->forge->dropColumn('esp32_devices', 'device_token');
        }
    }

    public function down()
    {
        $this->forge->addColumn('esp32_devices', [
            'device_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'sector',
            ],
        ]);

        $this->forge->dropColumn('esp32_devices', ['token_hash', 'is_active']);
    }
}
