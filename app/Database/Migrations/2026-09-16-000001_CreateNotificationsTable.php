<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bandeja de notificaciones por usuario (campana del header).
 *
 * Antes solo existía como script manual (sql/2026-08-31-notificaciones.sql),
 * así que en una PC nueva `spark migrate` no la creaba. Misma estructura que
 * ese script. Es idempotente: en las bases donde ya se corrió el script, la
 * migración queda registrada sin tocar la tabla.
 *
 * Se escribe una fila por cada miembro del espacio (fan-out en escritura), lo
 * que deja el "leído" y el contador de la campana como una query por usuario.
 */
class CreateNotificationsTable extends Migration
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
            // Destinatario: una fila por miembro del espacio.
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            // 1 = agotado, 2 = bajo el mínimo, 3 = aviso propio.
            'level' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
            ],
            'text' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'stock',
            ],
            // stock:{rowId}: identifica el origen para no repetir el aviso.
            'source_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'link' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'id'], false, false, 'idx_notifications_bandeja');
        $this->forge->addKey(['user_id', 'read_at'], false, false, 'idx_notifications_sin_leer');
        $this->forge->addKey(['source_key', 'user_id', 'id'], false, false, 'idx_notifications_origen');
        $this->forge->addKey('industry_id', false, false, 'idx_notifications_industry');
        $this->forge->addForeignKey('industry_id', 'industries', 'id', '', 'CASCADE', 'fk_notifications_industry');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE', 'fk_notifications_user');

        $this->forge->createTable('notifications', true);
    }

    public function down()
    {
        $this->forge->dropTable('notifications', true);
    }
}
