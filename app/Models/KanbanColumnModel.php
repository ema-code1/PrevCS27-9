<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanColumnModel extends Model
{
    protected $table            = 'kanban_columns';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'board_id', 'name', 'description', 'color', 'position',
        'status_key', 'is_final', 'task_limit', 'is_collapsed', 'is_archived',
    ];

    protected bool $allowEmptyInserts = false;

    /**
     * Columnas visibles del tablero, en orden.
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
     * Columna que además pertenece al tablero indicado. Es el chequeo de scope
     * que impide tocar columnas de otra placa mandando un id cualquiera.
     */
    public function findInBoard(int $columnId, int $boardId): ?array
    {
        return $this->where('id', $columnId)
            ->where('board_id', $boardId)
            ->first();
    }

    /**
     * Posición para una columna nueva: al final de las visibles.
     */
    public function nextPosition(int $boardId): int
    {
        $row = $this->selectMax('position')
            ->where('board_id', $boardId)
            ->where('is_archived', 0)
            ->first();

        return (int) ($row['position'] ?? -1) + 1;
    }

    /**
     * Reescribe el orden de las columnas según el array de ids recibido.
     * Ignora los ids que no sean del tablero (defensa ante payloads armados).
     */
    public function applyOrder(int $boardId, array $orderedIds): void
    {
        $valid = array_column($this->activeForBoard($boardId), 'id');
        $valid = array_map('intval', $valid);

        $position = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if (! in_array($id, $valid, true)) {
                continue;
            }
            $this->db->table($this->table)
                ->where('id', $id)
                ->where('board_id', $boardId)
                ->update(['position' => $position++]);
        }
    }
}
