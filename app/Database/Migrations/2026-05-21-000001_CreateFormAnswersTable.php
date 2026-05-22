<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateFormAnswersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            // identificador unico por respuesta — viene del CSV (Marca temporal).
            // VARCHAR para conservar formato original y poder hashear como UNIQUE.
            'raw_timestamp' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            // datetime normalizado para queries por fecha
            'submitted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'rol_empresa' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'conoce_similar' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'funcion_util' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'nivel_mejora' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'es_ecologico' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'imported_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // raw_timestamp es UNIQUE para que el import sea idempotente (no duplica)
        $this->forge->addUniqueKey('raw_timestamp');
        $this->forge->addKey('submitted_at');
        $this->forge->createTable('form_answers', true);
    }

    public function down()
    {
        $this->forge->dropTable('form_answers', true);
    }
}
