<?php

namespace App\Models;

use CodeIgniter\Model;

class StockColumnModel extends Model
{
    /**
     * Tipos de columna soportados. La columna `type` es VARCHAR justamente para
     * que sumar uno nuevo sea agregarlo acá y enseñarle a la UI a dibujarlo,
     * sin migración ni conversión de datos.
     */
    public const TYPE_TEXTO    = 'texto';
    public const TYPE_CONTADOR = 'contador';

    public const TYPES = [self::TYPE_TEXTO, self::TYPE_CONTADOR];

    /** Tipos cuyo valor vive en stock_cells.value_number en vez de value_text. */
    public const NUMERIC_TYPES = [self::TYPE_CONTADOR];

    protected $table            = 'stock_columns';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'table_id', 'name', 'type', 'unit', 'settings', 'is_item_column', 'position',
    ];

    protected bool $allowEmptyInserts = false;

    public static function isValidType(?string $type): bool
    {
        return in_array((string) $type, self::TYPES, true);
    }

    /**
     * ¿El valor de este tipo se guarda en value_number?
     */
    public static function isNumericType(?string $type): bool
    {
        return in_array((string) $type, self::NUMERIC_TYPES, true);
    }

    /**
     * Columnas de la tabla, en orden. La del ítem siempre cae primera porque
     * vive en la posición 0 y nunca se mueve.
     */
    public function forTable(int $tableId): array
    {
        return $this->where('table_id', $tableId)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Columna que además pertenece a la tabla indicada. Es el chequeo de scope
     * que impide tocar columnas de otro espacio mandando un id cualquiera.
     */
    public function findInTable(int $columnId, int $tableId): ?array
    {
        return $this->where('id', $columnId)
            ->where('table_id', $tableId)
            ->first();
    }

    /**
     * La columna del ítem: la que nombra cada fila en las alertas de stock.
     */
    public function itemColumn(int $tableId): ?array
    {
        return $this->where('table_id', $tableId)
            ->where('is_item_column', 1)
            ->first();
    }

    /**
     * Posición para una columna nueva: al final.
     */
    public function nextPosition(int $tableId): int
    {
        $row = $this->selectMax('position')
            ->where('table_id', $tableId)
            ->first();

        return (int) ($row['position'] ?? -1) + 1;
    }

    /**
     * Cantidad de columnas de la tabla (para no dejarla sin ninguna).
     */
    public function countForTable(int $tableId): int
    {
        return $this->where('table_id', $tableId)->countAllResults();
    }

    /**
     * Abre un hueco en `$position` corriendo una posición a la derecha todas las
     * columnas que estén ahí o más allá. La columna del ítem queda excluida:
     * nunca se mueve de la posición 0.
     */
    public function shiftFrom(int $tableId, int $position): void
    {
        $this->db->table($this->table)
            ->where('table_id', $tableId)
            ->where('is_item_column', 0)
            ->where('position >=', $position)
            ->set('position', 'position + 1', false)
            ->update();
    }

    /**
     * Renumera las posiciones de 0 en adelante después de un borrado, para que
     * no queden huecos que compliquen las inserciones siguientes.
     */
    public function resequence(int $tableId): void
    {
        $position = 0;
        foreach ($this->forTable($tableId) as $column) {
            if ((int) $column['position'] !== $position) {
                $this->db->table($this->table)
                    ->where('id', (int) $column['id'])
                    ->update(['position' => $position]);
            }
            $position++;
        }
    }
}
