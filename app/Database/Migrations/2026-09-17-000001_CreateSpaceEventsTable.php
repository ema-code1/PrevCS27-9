<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Calendario por espacio: cada evento cuelga de industry_id, así que nunca es
 * visible desde otro espacio. El acceso se resuelve por industry_users, igual
 * que el stock.
 *
 * start_at / end_at guardan la hora "de pared" de la planta (sin zona
 * horaria): lo que el usuario elige en el formulario es lo que se muestra.
 */
class CreateSpaceEventsTable extends Migration
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
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'start_at' => [
                'type' => 'DATETIME',
            ],
            'end_at' => [
                'type' => 'DATETIME',
            ],
            // Clave de la paleta del calendario (blue, green...), no un color libre.
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'blue',
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ],
            // Lista de etiquetas en JSON. Son pocas y no se consultan por SQL.
            'tags' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['industry_id', 'start_at'], false, false, 'idx_space_events_agenda');
        $this->forge->addKey('created_by', false, false, 'fk_space_events_creator');
        $this->forge->addForeignKey('industry_id', 'industries', 'id', 'CASCADE', 'CASCADE', 'fk_space_events_industry');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_space_events_creator');
        $this->forge->createTable('space_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('space_events', true);
    }
}
