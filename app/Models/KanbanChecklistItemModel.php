<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanChecklistItemModel extends Model
{
    protected $table            = 'kanban_checklist_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['checklist_id', 'content', 'is_completed', 'position'];

    protected bool $allowEmptyInserts = false;

    public function forChecklists(array $checklistIds): array
    {
        if ($checklistIds === []) {
            return [];
        }

        return $this->whereIn('checklist_id', $checklistIds)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function nextPosition(int $checklistId): int
    {
        $row = $this->selectMax('position')->where('checklist_id', $checklistId)->first();

        return (int) ($row['position'] ?? -1) + 1;
    }

    /**
     * Ítem que cuelga de una tarea del tablero indicado (scope).
     */
    public function findInBoard(int $itemId, int $boardId): ?array
    {
        $row = $this->select('kanban_checklist_items.*, kanban_tasks.id AS task_id')
            ->join('kanban_checklists', 'kanban_checklists.id = kanban_checklist_items.checklist_id')
            ->join('kanban_tasks', 'kanban_tasks.id = kanban_checklists.task_id')
            ->where('kanban_checklist_items.id', $itemId)
            ->where('kanban_tasks.board_id', $boardId)
            ->first();

        return $row ?: null;
    }

    public function applyOrder(int $checklistId, array $orderedIds): void
    {
        $valid = array_map('intval', array_column(
            $this->select('id')->where('checklist_id', $checklistId)->findAll(),
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
                ->where('checklist_id', $checklistId)
                ->update(['position' => $position++]);
        }
    }
}
