<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanTaskModel extends Model
{
    public const PRIORITIES = ['baja', 'media', 'alta', 'urgente'];

    protected $table            = 'kanban_tasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'board_id', 'column_id', 'parent_task_id', 'code', 'title', 'description',
        'color', 'priority', 'status', 'position', 'start_date', 'due_date',
        'completed_at', 'estimated_minutes', 'logged_minutes', 'internal_notes',
        'is_completed', 'is_archived', 'created_by',
    ];

    protected bool $allowEmptyInserts = false;

    /**
     * Tareas visibles del tablero (las completadas siguen en su columna hasta
     * que se archivan; el panel "Completadas" las lista aparte).
     */
    public function activeForBoard(int $boardId): array
    {
        return $this->where('board_id', $boardId)
            ->where('is_archived', 0)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function archivedForBoard(int $boardId): array
    {
        return $this->where('board_id', $boardId)
            ->where('is_archived', 1)
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }

    /**
     * Tarea que además pertenece al tablero indicado (chequeo de scope).
     */
    public function findInBoard(int $taskId, int $boardId): ?array
    {
        return $this->where('id', $taskId)
            ->where('board_id', $boardId)
            ->first();
    }

    public function nextPosition(int $columnId): int
    {
        $row = $this->selectMax('position')
            ->where('column_id', $columnId)
            ->where('is_archived', 0)
            ->first();

        return (int) ($row['position'] ?? -1) + 1;
    }

    /**
     * Reasigna posiciones 0..n dentro de una columna respetando el orden
     * recibido. Solo toca tareas que ya son de esa columna y ese tablero.
     */
    public function applyOrder(int $boardId, int $columnId, array $orderedIds): void
    {
        $valid = array_map('intval', array_column(
            $this->where('board_id', $boardId)
                ->where('column_id', $columnId)
                ->where('is_archived', 0)
                ->select('id')
                ->findAll(),
            'id'
        ));

        $position = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if (! in_array($id, $valid, true)) {
                continue;
            }
            $this->db->table($this->table)
                ->where('id', $id)
                ->where('board_id', $boardId)
                ->update(['column_id' => $columnId, 'position' => $position++]);
        }
    }

    /**
     * Compacta las posiciones de una columna (0..n) sin cambiar el orden.
     * Se usa después de sacar una tarea de la columna.
     */
    public function resequence(int $columnId): void
    {
        $tasks = $this->where('column_id', $columnId)
            ->where('is_archived', 0)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($tasks as $index => $task) {
            if ((int) $task['position'] === $index) {
                continue;
            }
            $this->db->table($this->table)
                ->where('id', (int) $task['id'])
                ->update(['position' => $index]);
        }
    }

    /**
     * Deja hueco en una columna: empuja +1 a todo lo que esté en $position
     * o más abajo, para insertar ahí.
     */
    public function makeRoom(int $columnId, int $position, int $exceptTaskId = 0): void
    {
        $builder = $this->db->table($this->table)
            ->where('column_id', $columnId)
            ->where('is_archived', 0)
            ->where('position >=', $position);

        if ($exceptTaskId > 0) {
            $builder->where('id !=', $exceptTaskId);
        }

        $builder->set('position', 'position + 1', false)->update();
    }

    /**
     * Tareas completadas del tablero para el panel inferior, con búsqueda
     * opcional por título/descripción/código.
     */
    public function completedForBoard(int $boardId, string $search = ''): array
    {
        $builder = $this->where('board_id', $boardId)
            ->where('is_completed', 1)
            ->where('is_archived', 0);

        $search = trim($search);
        if ($search !== '') {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('description', $search)
                ->orLike('code', $search)
                ->groupEnd();
        }

        return $builder->orderBy('completed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}
