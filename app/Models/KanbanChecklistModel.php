<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanChecklistModel extends Model
{
    protected $table            = 'kanban_checklists';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['task_id', 'title', 'position'];

    protected bool $allowEmptyInserts = false;

    public function forTask(int $taskId): array
    {
        return $this->where('task_id', $taskId)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function nextPosition(int $taskId): int
    {
        $row = $this->selectMax('position')->where('task_id', $taskId)->first();

        return (int) ($row['position'] ?? -1) + 1;
    }

    /**
     * Checklist que cuelga de una tarea del tablero indicado (scope).
     */
    public function findInBoard(int $checklistId, int $boardId): ?array
    {
        $row = $this->select('kanban_checklists.*')
            ->join('kanban_tasks', 'kanban_tasks.id = kanban_checklists.task_id')
            ->where('kanban_checklists.id', $checklistId)
            ->where('kanban_tasks.board_id', $boardId)
            ->first();

        return $row ?: null;
    }

    /**
     * Avance de checklist por tarea del tablero: [task_id => [done, total]].
     * Alimenta la barra de progreso de la tarjeta compacta.
     */
    public function progressByTask(int $boardId): array
    {
        $rows = $this->db->table('kanban_checklist_items i')
            ->select('t.id AS task_id, COUNT(i.id) AS total, SUM(i.is_completed) AS done')
            ->join('kanban_checklists c', 'c.id = i.checklist_id')
            ->join('kanban_tasks t', 't.id = c.task_id')
            ->where('t.board_id', $boardId)
            ->groupBy('t.id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['task_id']] = [
                'done'  => (int) $row['done'],
                'total' => (int) $row['total'],
            ];
        }

        return $map;
    }
}
