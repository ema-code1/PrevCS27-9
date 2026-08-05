<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanTaskAssigneeModel extends Model
{
    protected $table            = 'kanban_task_assignees';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['task_id', 'user_id'];

    protected bool $allowEmptyInserts = false;

    /**
     * Asigna o desasigna al usuario. Devuelve true si quedó asignado.
     */
    public function toggle(int $taskId, int $userId): bool
    {
        $existing = $this->where('task_id', $taskId)
            ->where('user_id', $userId)
            ->first();

        if ($existing !== null) {
            $this->delete((int) $existing['id']);

            return false;
        }

        $this->insert(['task_id' => $taskId, 'user_id' => $userId]);

        return true;
    }

    /**
     * Responsables por tarea de todo el tablero, en una sola consulta.
     * Devuelve [task_id => [['id','nombre','apellido','foto_perfil'], ...]].
     */
    public function assigneesByTask(int $boardId): array
    {
        $rows = $this->db->table('kanban_task_assignees a')
            ->select('a.task_id, u.id, u.nombre, u.apellido, u.foto_perfil')
            ->join('kanban_tasks t', 't.id = a.task_id')
            ->join('users u', 'u.id = a.user_id')
            ->where('t.board_id', $boardId)
            ->orderBy('u.nombre', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['task_id']][] = [
                'id'      => (int) $row['id'],
                'nombre'  => $row['nombre'],
                'apellido' => $row['apellido'],
                'foto'    => $row['foto_perfil'],
            ];
        }

        return $map;
    }

    public function userIdsFor(int $taskId): array
    {
        return array_map(
            'intval',
            array_column($this->select('user_id')->where('task_id', $taskId)->findAll(), 'user_id')
        );
    }
}
