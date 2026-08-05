<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Tablero Kanban por ESP32.
 *
 * Jerarquía: usuario → espacio (industries) → ESP32 (esp32_devices) → tablero.
 * Cada placa tiene UN tablero propio (unique en esp32_device_id): los datos de
 * un tablero nunca son visibles ni editables desde otro espacio, porque todo
 * cuelga del device y el acceso se resuelve por industry_users.
 */
class CreateKanbanTables extends Migration
{
    public function up()
    {
        $this->createBoards();
        $this->createColumns();
        $this->createTasks();
        $this->createAssignees();
        $this->createLabels();
        $this->createTaskLabels();
        $this->createChecklists();
        $this->createChecklistItems();
        $this->createComments();
        $this->createAttachments();
        $this->createLinks();
        $this->createActivityLog();
    }

    public function down()
    {
        // Orden inverso al de creación para no chocar con las FK.
        foreach ([
            'kanban_activity_log',
            'kanban_links',
            'kanban_attachments',
            'kanban_comments',
            'kanban_checklist_items',
            'kanban_checklists',
            'kanban_task_labels',
            'kanban_labels',
            'kanban_task_assignees',
            'kanban_tasks',
            'kanban_columns',
            'kanban_boards',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    /* ── Tableros: uno por placa ─────────────────────────────────────── */
    private function createBoards(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'esp32_device_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => 'Tareas de mantenimiento',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Clave de la paleta cerrada (azul, celeste, turquesa, verde, ...).
            'background_color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'azul',
            ],
            // Contador de identificadores de tarea; nunca reutiliza números.
            'task_seq' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
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
        // Una placa = un tablero.
        $this->forge->addUniqueKey('esp32_device_id');
        $this->forge->addKey('created_by', false, false, 'fk_kanban_boards_creator');
        $this->forge->addForeignKey('esp32_device_id', 'esp32_devices', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_boards_device');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_kanban_boards_creator');
        $this->forge->createTable('kanban_boards', true);
    }

    /* ── Columnas ────────────────────────────────────────────────────── */
    private function createColumns(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'board_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'gris',
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            // Estado del flujo que representa la columna; al mover una tarea
            // acá, la tarea hereda este estado.
            'status_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            // Si es una columna final, las tareas que caen acá se completan.
            'is_final' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'task_limit' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'is_collapsed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_archived' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addKey(['board_id', 'is_archived', 'position']);
        $this->forge->addForeignKey('board_id', 'kanban_boards', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_columns_board');
        $this->forge->createTable('kanban_columns', true);
    }

    /* ── Tareas ──────────────────────────────────────────────────────── */
    private function createTasks(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            // board_id redundante a propósito: permite filtrar y validar el
            // scope de una tarea sin joinear con columnas en cada request.
            'board_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'column_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            // Subtareas: apuntan a la tarea padre dentro del mismo tablero.
            'parent_task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'priority' => [
                'type'       => 'ENUM',
                'constraint' => ['baja', 'media', 'alta', 'urgente'],
                'default'    => 'media',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'estimated_minutes' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'logged_minutes' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'internal_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_completed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_archived' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addKey(['column_id', 'is_archived', 'position']);
        $this->forge->addKey(['board_id', 'is_completed']);
        $this->forge->addKey('parent_task_id', false, false, 'fk_kanban_tasks_parent');
        $this->forge->addKey('created_by', false, false, 'fk_kanban_tasks_creator');
        $this->forge->addForeignKey('board_id', 'kanban_boards', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_tasks_board');
        $this->forge->addForeignKey('column_id', 'kanban_columns', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_tasks_column');
        $this->forge->addForeignKey('parent_task_id', 'kanban_tasks', 'id', 'SET NULL', 'CASCADE', 'fk_kanban_tasks_parent');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_kanban_tasks_creator');
        $this->forge->createTable('kanban_tasks', true);
    }

    /* ── Responsables (N:M tarea ↔ usuario) ──────────────────────────── */
    private function createAssignees(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'assigned_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['task_id', 'user_id']);
        $this->forge->addKey('user_id', false, false, 'fk_kanban_assignees_user');
        $this->forge->addForeignKey('task_id', 'kanban_tasks', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_assignees_task');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_assignees_user');
        $this->forge->createTable('kanban_task_assignees', true);
    }

    /* ── Etiquetas (paleta propia de cada tablero) ───────────────────── */
    private function createLabels(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'board_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'azul',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['board_id', 'name']);
        $this->forge->addForeignKey('board_id', 'kanban_boards', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_labels_board');
        $this->forge->createTable('kanban_labels', true);
    }

    private function createTaskLabels(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'label_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['task_id', 'label_id']);
        $this->forge->addKey('label_id', false, false, 'fk_kanban_task_labels_label');
        $this->forge->addForeignKey('task_id', 'kanban_tasks', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_task_labels_task');
        $this->forge->addForeignKey('label_id', 'kanban_labels', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_task_labels_label');
        $this->forge->createTable('kanban_task_labels', true);
    }

    /* ── Checklists ──────────────────────────────────────────────────── */
    private function createChecklists(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => 'Checklist',
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['task_id', 'position']);
        $this->forge->addForeignKey('task_id', 'kanban_tasks', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_checklists_task');
        $this->forge->createTable('kanban_checklists', true);
    }

    private function createChecklistItems(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'checklist_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'content' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'is_completed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
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
        $this->forge->addKey(['checklist_id', 'position']);
        $this->forge->addForeignKey('checklist_id', 'kanban_checklists', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_items_checklist');
        $this->forge->createTable('kanban_checklist_items', true);
    }

    /* ── Comentarios (con respuestas anidadas) ───────────────────────── */
    private function createComments(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'parent_comment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'content' => [
                'type' => 'TEXT',
            ],
            'edited_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey(['task_id', 'created_at']);
        $this->forge->addKey('user_id', false, false, 'fk_kanban_comments_user');
        $this->forge->addKey('parent_comment_id', false, false, 'fk_kanban_comments_parent');
        $this->forge->addForeignKey('task_id', 'kanban_tasks', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_comments_task');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_kanban_comments_user');
        $this->forge->addForeignKey('parent_comment_id', 'kanban_comments', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_comments_parent');
        $this->forge->createTable('kanban_comments', true);
    }

    /* ── Adjuntos y enlaces ──────────────────────────────────────────── */
    private function createAttachments(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // Nombre original que subió el usuario (solo para mostrar).
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            // Ruta relativa dentro de public/uploads/kanban/.
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_size' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'is_image' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id', false, false, 'fk_kanban_attachments_task');
        $this->forge->addKey('user_id', false, false, 'fk_kanban_attachments_user');
        $this->forge->addForeignKey('task_id', 'kanban_tasks', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_attachments_task');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_kanban_attachments_user');
        $this->forge->createTable('kanban_attachments', true);
    }

    private function createLinks(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id', false, false, 'fk_kanban_links_task');
        $this->forge->addForeignKey('task_id', 'kanban_tasks', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_links_task');
        $this->forge->createTable('kanban_links', true);
    }

    /* ── Historial de actividad ──────────────────────────────────────── */
    private function createActivityLog(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'board_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // Clave estable de la acción (task_created, task_moved, ...).
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            // Frase ya armada para mostrar en el historial.
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['board_id', 'created_at']);
        $this->forge->addKey('task_id', false, false, 'fk_kanban_activity_task');
        $this->forge->addKey('user_id', false, false, 'fk_kanban_activity_user');
        $this->forge->addForeignKey('board_id', 'kanban_boards', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_activity_board');
        $this->forge->addForeignKey('task_id', 'kanban_tasks', 'id', 'CASCADE', 'CASCADE', 'fk_kanban_activity_task');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_kanban_activity_user');
        $this->forge->createTable('kanban_activity_log', true);
    }
}
