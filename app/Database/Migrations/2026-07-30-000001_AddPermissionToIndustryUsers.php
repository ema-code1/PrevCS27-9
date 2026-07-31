<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermissionToIndustryUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('permission', 'industry_users')) {
            $this->forge->addColumn('industry_users', [
                'permission' => [
                    'type'       => 'ENUM',
                    'constraint' => ['read', 'edit'],
                    'default'    => 'read',
                    'after'      => 'role',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('permission', 'industry_users')) {
            $this->forge->dropColumn('industry_users', 'permission');
        }
    }
}
