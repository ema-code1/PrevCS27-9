<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanAttachmentModel extends Model
{
    protected $table            = 'kanban_attachments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'task_id', 'user_id', 'file_name', 'file_path', 'file_size', 'mime_type', 'is_image',
    ];

    protected bool $allowEmptyInserts = false;

    public function forTask(int $taskId): array
    {
        return $this->where('task_id', $taskId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Adjunto que cuelga de una tarea del tablero indicado (scope).
     */
    public function findInBoard(int $attachmentId, int $boardId): ?array
    {
        $row = $this->select('kanban_attachments.*')
            ->join('kanban_tasks', 'kanban_tasks.id = kanban_attachments.task_id')
            ->where('kanban_attachments.id', $attachmentId)
            ->where('kanban_tasks.board_id', $boardId)
            ->first();

        return $row ?: null;
    }

    /**
     * Cantidad de adjuntos por tarea del tablero (badge de la tarjeta).
     */
    public function countsByTask(int $boardId): array
    {
        $rows = $this->db->table('kanban_attachments a')
            ->select('a.task_id, COUNT(a.id) AS total')
            ->join('kanban_tasks t', 't.id = a.task_id')
            ->where('t.board_id', $boardId)
            ->groupBy('a.task_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['task_id']] = (int) $row['total'];
        }

        return $map;
    }

    /**
     * Imágenes adjuntas de varias tareas de una sola vez. Lo usa el panel de
     * "Completadas", que muestra las imágenes al expandir una fila.
     */
    public function imagesForTasks(array $taskIds): array
    {
        if ($taskIds === []) {
            return [];
        }

        $rows = $this->whereIn('task_id', $taskIds)
            ->where('is_image', 1)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['task_id']][] = $row;
        }

        return $map;
    }
}
