<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Celdas: la intersección fila × columna.
 *
 * El valor vive en value_text o en value_number según el tipo de la columna.
 * Están separados a propósito: las alertas de stock futuras van a filtrar con
 * `WHERE value_number < umbral` sin castear nada.
 */
class StockCellModel extends Model
{
    protected $table            = 'stock_cells';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['row_id', 'column_id', 'value_text', 'value_number'];

    protected bool $allowEmptyInserts = false;

    /**
     * Guarda el valor de una celda respetando el UNIQUE (row_id, column_id):
     * si ya existe la actualiza, si no la crea.
     */
    public function put(int $rowId, int $columnId, string $type, $value): void
    {
        $data = StockColumnModel::isNumericType($type)
            ? ['value_text' => null, 'value_number' => $this->toNumber($value)]
            : ['value_text' => $value === null ? null : (string) $value, 'value_number' => null];

        $existing = $this->where('row_id', $rowId)
            ->where('column_id', $columnId)
            ->first();

        if ($existing !== null) {
            $this->update((int) $existing['id'], $data);

            return;
        }

        $this->insert($data + ['row_id' => $rowId, 'column_id' => $columnId]);
    }

    /**
     * Todas las celdas de una tabla en una sola query, indexadas por
     * "rowId:columnId" para hidratar la vista sin N+1.
     */
    public function forTable(int $tableId): array
    {
        $rows = $this->select('stock_cells.*')
            ->join('stock_rows', 'stock_rows.id = stock_cells.row_id')
            ->where('stock_rows.table_id', $tableId)
            ->findAll();

        $indexed = [];
        foreach ($rows as $cell) {
            $indexed[$cell['row_id'] . ':' . $cell['column_id']] = $cell;
        }

        return $indexed;
    }

    /**
     * Nombre del ítem de una fila: el valor de su celda en la columna con
     * is_item_column = 1. Es lo que van a mostrar las alertas de stock
     * ("hay una baja de stock en {item}").
     */
    public function itemLabel(int $rowId): string
    {
        $cell = $this->select('stock_cells.value_text')
            ->join('stock_columns', 'stock_columns.id = stock_cells.column_id')
            ->where('stock_cells.row_id', $rowId)
            ->where('stock_columns.is_item_column', 1)
            ->first();

        return trim((string) ($cell['value_text'] ?? ''));
    }

    /**
     * Borra las celdas de una columna. Se usa al cambiarle el tipo, cuando el
     * valor viejo no se puede trasladar al nuevo.
     */
    public function deleteForColumn(int $columnId): void
    {
        $this->where('column_id', $columnId)->delete();
    }

    /**
     * Traslada los valores de una columna al cambiarle el tipo, en vez de
     * perderlos: texto → contador parsea el número (0 si no se puede) y
     * contador → texto conserva el número como texto.
     */
    public function convertColumn(int $columnId, string $fromType, string $toType): void
    {
        $wasNumeric = StockColumnModel::isNumericType($fromType);
        $isNumeric  = StockColumnModel::isNumericType($toType);

        if ($wasNumeric === $isNumeric) {
            return;
        }

        foreach ($this->where('column_id', $columnId)->findAll() as $cell) {
            $data = $isNumeric
                ? ['value_number' => $this->toNumber($cell['value_text']), 'value_text' => null]
                : ['value_text' => $this->numberToText($cell['value_number']), 'value_number' => null];

            $this->update((int) $cell['id'], $data);
        }
    }

    /**
     * Cualquier cosa → número. Lo que no se pueda interpretar vale 0, así una
     * columna de texto convertida en contador nunca queda con celdas vacías.
     */
    private function toNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // "12 cajas" → 12; "" o "sin stock" → 0.
        $limpio = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($limpio) ? (float) $limpio : 0.0;
    }

    /**
     * Número → texto sin decimales de relleno: 12.000 se muestra "12".
     */
    private function numberToText($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;

        return $number == (int) $number ? (string) (int) $number : rtrim(rtrim((string) $number, '0'), '.');
    }
}
