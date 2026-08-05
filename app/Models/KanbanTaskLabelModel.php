<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanTaskLabelModel extends Model
{
    protected $table            = 'kanban_task_labels';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['task_id', 'label_id'];

    protected bool $allowEmptyInserts = false;

    /**
     * Pone o saca la etiqueta de la tarea. Devuelve true si quedó puesta.
     */
    public function toggle(int $taskId, int $labelId): bool
    {
        $existing = $this->where('task_id', $taskId)
            ->where('label_id', $labelId)
            ->first();

        if ($existing !== null) {
            $this->delete((int) $existing['id']);

            return false;
        }

        $this->insert(['task_id' => $taskId, 'label_id' => $labelId]);

        return true;
    }

    public function labelIdsFor(int $taskId): array
    {
        return array_map(
            'intval',
            array_column($this->select('label_id')->where('task_id', $taskId)->findAll(), 'label_id')
        );
    }
}
