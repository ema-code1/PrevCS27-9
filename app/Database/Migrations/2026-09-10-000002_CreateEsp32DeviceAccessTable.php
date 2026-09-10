<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEsp32DeviceAccessTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'industry_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'esp32_device_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['industry_user_id', 'esp32_device_id'], 'uniq_device_access_member_device');
        $this->forge->addForeignKey('industry_user_id', 'industry_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('esp32_device_id', 'esp32_devices', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('esp32_device_access');
    }

    public function down()
    {
        $this->forge->dropTable('esp32_device_access', true);
    }
}
