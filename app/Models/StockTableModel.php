<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tablas de stock de un espacio. Un espacio tiene siempre al menos una: se le
 * crea junto con el espacio (OnboardingController) y no se puede borrar la
 * última.
 */
class StockTableModel extends Model
{
    protected $table            = 'stock_tables';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_id', 'title', 'position', 'created_by'];

    protected bool $allowEmptyInserts = false;

    public function forIndustry(int $industryId): array
    {
        return $this->where('industry_id', $industryId)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Tabla que además pertenece al espacio indicado. Es el chequeo de scope
     * que impide tocar el stock de otro espacio mandando un id cualquiera.
     */
    public function findInIndustry(int $tableId, int $industryId): ?array
    {
        return $this->where('id', $tableId)
            ->where('industry_id', $industryId)
            ->first();
    }

    public function nextPosition(int $industryId): int
    {
        $row = $this->selectMax('position')
            ->where('industry_id', $industryId)
            ->first();

        return (int) ($row['position'] ?? -1) + 1;
    }

    public function countForIndustry(int $industryId): int
    {
        return $this->where('industry_id', $industryId)->countAllResults();
    }

    /**
     * Crea una tabla vacía con su columna de ítem. Toda tabla nace con la
     * columna de ítem en la posición 0, un contador y una fila, para que nunca
     * exista una tabla sin forma.
     *
     * @return int id de la tabla creada
     */
    public function provisionDefault(int $industryId, string $title, ?int $userId = null): int
    {
        $tableId = (int) $this->insert([
            'industry_id' => $industryId,
            'title'       => $title !== '' ? $title : 'Stock',
            'position'    => $this->nextPosition($industryId),
            'created_by'  => $userId,
        ], true);

        $columnModel = new StockColumnModel();

        $columnModel->insert([
            'table_id'       => $tableId,
            'name'           => 'Item',
            'type'           => StockColumnModel::TYPE_TEXTO,
            'is_item_column' => 1,
            'position'       => 0,
        ]);

        $columnModel->insert([
            'table_id' => $tableId,
            'name'     => 'Cantidad',
            'type'     => StockColumnModel::TYPE_CONTADOR,
            'unit'     => 'unidades',
            'position' => 1,
        ]);

        (new StockRowModel())->insert([
            'table_id' => $tableId,
            'position' => 0,
        ]);

        return $tableId;
    }

    /**
     * Estado completo de una tabla, con la forma exacta que consume el JS de la
     * vista. Tres queries fijas (columnas, filas, celdas), sin N+1.
     */
    public function payload(int $tableId): ?array
    {
        $table = $this->find($tableId);
        if ($table === null) {
            return null;
        }

        $columns = (new StockColumnModel())->forTable($tableId);
        $rows    = (new StockRowModel())->forTable($tableId);
        $cells   = (new StockCellModel())->forTable($tableId);

        $columnsPayload = array_map(static fn (array $column): array => [
            'id'         => (int) $column['id'],
            'name'       => (string) $column['name'],
            'type'       => (string) $column['type'],
            'unit'       => (string) ($column['unit'] ?? ''),
            'isItem'     => (bool) $column['is_item_column'],
            'settings'   => $column['settings'] !== null ? json_decode($column['settings'], true) : null,
        ], $columns);

        $rowsPayload = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $cell    = $cells[$row['id'] . ':' . $column['id']] ?? null;
                $numeric = StockColumnModel::isNumericType($column['type']);

                $values[(int) $column['id']] = $numeric
                    ? (float) ($cell['value_number'] ?? 0)
                    : (string) ($cell['value_text'] ?? '');
            }

            $rowsPayload[] = [
                'id'     => (int) $row['id'],
                'values' => $values,
            ];
        }

        return [
            'id'      => (int) $table['id'],
            'title'   => (string) $table['title'],
            'columns' => $columnsPayload,
            'rows'    => $rowsPayload,
        ];
    }

    /**
     * Estado de todas las tablas del espacio, listo para sembrar la vista.
     */
    public function payloadForIndustry(int $industryId): array
    {
        $payloads = [];
        foreach ($this->forIndustry($industryId) as $table) {
            $payload = $this->payload((int) $table['id']);
            if ($payload !== null) {
                $payloads[] = $payload;
            }
        }

        return $payloads;
    }
}
