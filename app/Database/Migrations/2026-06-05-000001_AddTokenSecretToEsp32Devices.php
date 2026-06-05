<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTokenSecretToEsp32Devices extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('token_secret', 'esp32_devices')) {
            $this->forge->addColumn('esp32_devices', [
                'token_secret' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'token_hash',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('token_secret', 'esp32_devices')) {
            $this->forge->dropColumn('esp32_devices', 'token_secret');
        }
    }
}
