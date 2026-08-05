<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanLabelModel extends Model
{
    protected $table            = 'kanban_labels';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['board_id', 'name', 'color'];

    protected bool $allowEmptyInserts = false;

    public function forBoard(int $boardId): array
    {
        return $this->where('board_id', $boardId)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function findInBoard(int $labelId, int $boardId): ?array
    {
        return $this->where('id', $labelId)
            ->where('board_id', $boardId)
            ->first();
    }

    /**
     * Etiquetas por tarea: [task_id => [label_id, ...]] para todo el tablero,
     * en una sola consulta (evita el N+1 al armar el payload).
     */
    public function labelIdsByTask(int $boardId): array
    {
        $rows = $this->db->table('kanban_task_labels tl')
            ->select('tl.task_id, tl.label_id')
            ->join('kanban_tasks t', 't.id = tl.task_id')
            ->where('t.board_id', $boardId)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['task_id']][] = (int) $row['label_id'];
        }

        return $map;
    }
}
