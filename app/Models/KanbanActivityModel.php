<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Historial de actividad del tablero. Cada entrada guarda la clave estable de
 * la acción (para poder filtrar) y la frase ya armada que se muestra.
 */
class KanbanActivityModel extends Model
{
    protected $table            = 'kanban_activity_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['board_id', 'task_id', 'user_id', 'action', 'details'];

    protected bool $allowEmptyInserts = false;

    public function log(int $boardId, ?int $taskId, ?int $userId, string $action, string $details = ''): void
    {
        $this->insert([
            'board_id' => $boardId,
            'task_id'  => $taskId,
            'user_id'  => $userId,
            'action'   => $action,
            'details'  => $details !== '' ? $details : null,
        ]);
    }

    /**
     * Historial de una tarea, del más nuevo al más viejo.
     */
    public function forTask(int $taskId, int $limit = 50): array
    {
        return $this->select('kanban_activity_log.*, users.nombre, users.apellido')
            ->join('users', 'users.id = kanban_activity_log.user_id', 'left')
            ->where('kanban_activity_log.task_id', $taskId)
            ->orderBy('kanban_activity_log.created_at', 'DESC')
            ->orderBy('kanban_activity_log.id', 'DESC')
            ->findAll($limit);
    }

    /**
     * Historial del tablero completo (acciones sobre columnas incluidas).
     */
    public function forBoard(int $boardId, int $limit = 80): array
    {
        return $this->select('kanban_activity_log.*, users.nombre, users.apellido')
            ->join('users', 'users.id = kanban_activity_log.user_id', 'left')
            ->where('kanban_activity_log.board_id', $boardId)
            ->orderBy('kanban_activity_log.created_at', 'DESC')
            ->orderBy('kanban_activity_log.id', 'DESC')
            ->findAll($limit);
    }
}
