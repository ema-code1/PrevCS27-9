<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanLinkModel extends Model
{
    protected $table            = 'kanban_links';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['task_id', 'title', 'url'];

    protected bool $allowEmptyInserts = false;

    public function forTask(int $taskId): array
    {
        return $this->where('task_id', $taskId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Enlace que cuelga de una tarea del tablero indicado (scope).
     */
    public function findInBoard(int $linkId, int $boardId): ?array
    {
        $row = $this->select('kanban_links.*')
            ->join('kanban_tasks', 'kanban_tasks.id = kanban_links.task_id')
            ->where('kanban_links.id', $linkId)
            ->where('kanban_tasks.board_id', $boardId)
            ->first();

        return $row ?: null;
    }
}
