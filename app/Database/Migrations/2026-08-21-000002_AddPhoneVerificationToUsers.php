<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhoneVerificationToUsers extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('phone_verification_enabled', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'phone_verification_enabled' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => false,
                'after'   => 'two_factor_enabled',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('phone_verification_enabled', 'users')) {
            $this->forge->dropColumn('users', 'phone_verification_enabled');
        }
    }
}
