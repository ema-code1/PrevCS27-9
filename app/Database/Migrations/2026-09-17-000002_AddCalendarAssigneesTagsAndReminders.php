<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Calendario del espacio, segunda vuelta: encargados, recordatorios y
 * etiquetas propias.
 *
 *   space_events.notify      cuándo avisar (clave de SpaceEventReminders)
 *   space_events.notify_at   momento exacto ya resuelto, para poder buscar
 *                            los recordatorios vencidos con un índice
 *   space_events.notified_at cuándo se repartió el aviso (NULL = pendiente)
 *
 * notify_at se calcula al guardar y no al leer: así el barrido de vencidos es
 * un WHERE indexado y no hay que recorrer todos los eventos del sistema.
 *
 * Las etiquetas propias viven por espacio (space_event_tags) y se suman a las
 * de SpaceEventModel::TAGS, que son fijas y las tienen todos los espacios.
 */
class AddCalendarAssigneesTagsAndReminders extends Migration
{
    public function up()
    {
        $this->addReminderColumns();
        $this->createAssignees();
        $this->createTags();
    }

    public function down()
    {
        $this->forge->dropTable('space_event_tags', true);
        $this->forge->dropTable('space_event_assignees', true);

        foreach (['notified_at', 'notify_at', 'notify'] as $column) {
            if ($this->db->fieldExists($column, 'space_events')) {
                $this->forge->dropColumn('space_events', $column);
            }
        }
    }

    /* ── Recordatorios sobre la tabla de eventos ─────────────────────── */
    private function addReminderColumns(): void
    {
        if (! $this->db->fieldExists('notify', 'space_events')) {
            $this->forge->addColumn('space_events', [
                'notify' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'none',
                    'after'      => 'tags',
                ],
            ]);
        }

        if (! $this->db->fieldExists('notify_at', 'space_events')) {
            $this->forge->addColumn('space_events', [
                'notify_at' => [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'notify',
                ],
            ]);
        }

        if (! $this->db->fieldExists('notified_at', 'space_events')) {
            $this->forge->addColumn('space_events', [
                'notified_at' => [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'notify_at',
                ],
            ]);

            // El barrido pregunta siempre por lo mismo: pendientes que ya
            // vencieron. Con las dos columnas en el índice no toca la tabla.
            $this->db->query('CREATE INDEX idx_space_events_recordatorio ON space_events (notified_at, notify_at)');
        }
    }

    /* ── Encargados: N usuarios por evento ───────────────────────────── */
    private function createAssignees(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'event_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // Un usuario no puede figurar dos veces en el mismo evento.
        $this->forge->addUniqueKey(['event_id', 'user_id'], 'uq_space_event_assignees');
        $this->forge->addKey('user_id', false, false, 'idx_space_event_assignees_user');
        $this->forge->addForeignKey('event_id', 'space_events', 'id', 'CASCADE', 'CASCADE', 'fk_space_event_assignees_event');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_space_event_assignees_user');
        $this->forge->createTable('space_event_assignees', true);
    }

    /* ── Etiquetas propias del espacio ───────────────────────────────── */
    private function createTags(): void
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
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
        ]);

        $this->forge->addKey('id', true);
        // Dos etiquetas con el mismo nombre en un espacio no tienen sentido.
        $this->forge->addUniqueKey(['industry_id', 'name'], 'uq_space_event_tags');
        $this->forge->addForeignKey('industry_id', 'industries', 'id', 'CASCADE', 'CASCADE', 'fk_space_event_tags_industry');
        // CI4 recibe (onUpdate, onDelete): borrar el usuario deja la etiqueta
        // del espacio con created_by en NULL, no se lleva la etiqueta.
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_space_event_tags_creator');
        $this->forge->createTable('space_event_tags', true);
    }
}
