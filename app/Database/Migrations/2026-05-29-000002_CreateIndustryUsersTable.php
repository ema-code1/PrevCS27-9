<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateIndustryUsersTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            // admin_principal = creador del espacio; supervisor = usuario que se unio
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin_principal', 'supervisor'],
                'default'    => 'supervisor',
            ],
            'joined_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // Un usuario no puede pertenecer dos veces a la misma industria
        $this->forge->addUniqueKey(['industry_id', 'user_id']);
        $this->forge->addKey('industry_id', false, false, 'fk_industry_users_industry');
        $this->forge->addKey('user_id', false, false, 'fk_industry_users_user');
        $this->forge->addForeignKey('industry_id', 'industries', 'id', 'CASCADE', 'CASCADE', 'fk_industry_users_industry');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_industry_users_user');
        $this->forge->createTable('industry_users', true);
    }

    public function down()
    {
        $this->forge->dropTable('industry_users', true);
    }
}
