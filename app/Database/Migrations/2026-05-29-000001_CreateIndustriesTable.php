<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateIndustriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            // ID publico e irrepetible del espacio, ej: SJ-IND-8F42A1
            'industry_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'company_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'space_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'sector' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            // Contrasena del espacio (hasheada con password_hash)
            'space_password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // industry_uid debe ser unico (se comparte para unirse al espacio)
        $this->forge->addUniqueKey('industry_uid');
        $this->forge->addKey('created_by', false, false, 'fk_industries_creator');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_industries_creator');
        $this->forge->createTable('industries', true);
    }

    public function down()
    {
        $this->forge->dropTable('industries', true);
    }
}
