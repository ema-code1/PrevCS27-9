<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanCommentModel extends Model
{
    protected $table            = 'kanban_comments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['task_id', 'user_id', 'parent_comment_id', 'content', 'edited_at'];

    protected bool $allowEmptyInserts = false;

    /**
     * Comentarios de una tarea con el autor resuelto, del más viejo al más nuevo.
     */
    public function forTask(int $taskId): array
    {
        return $this->select('kanban_comments.*, users.nombre, users.apellido, users.foto_perfil')
            ->join('users', 'users.id = kanban_comments.user_id', 'left')
            ->where('kanban_comments.task_id', $taskId)
            ->orderBy('kanban_comments.created_at', 'ASC')
            ->orderBy('kanban_comments.id', 'ASC')
            ->findAll();
    }

    /**
     * Comentario que cuelga de una tarea del tablero indicado (scope).
     */
    public function findInBoard(int $commentId, int $boardId): ?array
    {
        $row = $this->select('kanban_comments.*')
            ->join('kanban_tasks', 'kanban_tasks.id = kanban_comments.task_id')
            ->where('kanban_comments.id', $commentId)
            ->where('kanban_tasks.board_id', $boardId)
            ->first();

        return $row ?: null;
    }

    /**
     * Cantidad de comentarios por tarea del tablero (badge de la tarjeta).
     */
    public function countsByTask(int $boardId): array
    {
        $rows = $this->db->table('kanban_comments c')
            ->select('c.task_id, COUNT(c.id) AS total')
            ->join('kanban_tasks t', 't.id = c.task_id')
            ->where('t.board_id', $boardId)
            ->groupBy('c.task_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['task_id']] = (int) $row['total'];
        }

        return $map;
    }
}
