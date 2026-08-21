<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Filas de una tabla de stock. Cada fila es un ítem: su nombre vive en la celda
 * de la columna con is_item_column = 1.
 */
class StockRowModel extends Model
{
    protected $table            = 'stock_rows';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['table_id', 'position'];

    protected bool $allowEmptyInserts = false;

    public function forTable(int $tableId): array
    {
        return $this->where('table_id', $tableId)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Fila que además pertenece a la tabla indicada (chequeo de scope).
     */
    public function findInTable(int $rowId, int $tableId): ?array
    {
        return $this->where('id', $rowId)
            ->where('table_id', $tableId)
            ->first();
    }

    public function nextPosition(int $tableId): int
    {
        $row = $this->selectMax('position')
            ->where('table_id', $tableId)
            ->first();

        return (int) ($row['position'] ?? -1) + 1;
    }

    /**
     * Cantidad de filas (para no dejar la tabla sin ninguna).
     */
    public function countForTable(int $tableId): int
    {
        return $this->where('table_id', $tableId)->countAllResults();
    }

    public function resequence(int $tableId): void
    {
        $position = 0;
        foreach ($this->forTable($tableId) as $row) {
            if ((int) $row['position'] !== $position) {
                $this->db->table($this->table)
                    ->where('id', (int) $row['id'])
                    ->update(['position' => $position]);
            }
            $position++;
        }
    }
}
