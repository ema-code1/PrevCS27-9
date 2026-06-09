<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailVerificationAndTwoFactor extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'email_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'two_factor_enabled' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => false,
            ],
        ]);

        $this->db->table('users')
            ->where('email_verified_at', null)
            ->update(['email_verified_at' => date('Y-m-d H:i:s')]);

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'purpose' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'code_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'attempts' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'purpose'], 'uq_verification_user_purpose');
        $this->forge->addKey('expires_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_verification_codes_user');
        $this->forge->createTable('verification_codes', true);
    }

    public function down()
    {
        $this->forge->dropTable('verification_codes', true);
        $this->forge->dropColumn('users', ['email_verified_at', 'two_factor_enabled']);
    }
}
