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
    public const TYPE_MINIMO   = 'minimo';
    public const TYPE_AVISO    = 'aviso';

    public const TYPES = [self::TYPE_TEXTO, self::TYPE_CONTADOR, self::TYPE_MINIMO, self::TYPE_AVISO];

    /** Tipos cuyo valor vive en stock_cells.value_number en vez de value_text. */
    public const NUMERIC_TYPES = [self::TYPE_CONTADOR, self::TYPE_MINIMO, self::TYPE_AVISO];

    /**
     * Tipos donde la celda vacía significa algo distinto de cero. En `aviso` el
     * umbral en blanco es "esta fila no avisa", que no es lo mismo que avisar
     * cuando llega a 0.
     */
    public const NULLABLE_TYPES = [self::TYPE_AVISO];

    /** Config por defecto de la columna de aviso (y de la tabla que no la tiene). */
    public const AVISO_DEFAULTS = [
        'label'      => 'Reponer pronto',
        'comparator' => 'lte',
        'zero'       => true,
        'minimo'     => true,
    ];

    public const COMPARATORS = ['lte', 'lt', 'eq'];

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

    public static function isExclusiveType(?string $type): bool
    {
        return in_array((string) $type, [self::TYPE_CONTADOR, self::TYPE_MINIMO, self::TYPE_AVISO], true);
    }

    /**
     * ¿La celda vacía de este tipo se guarda como NULL en vez de 0?
     */
    public static function isNullableType(?string $type): bool
    {
        return in_array((string) $type, self::NULLABLE_TYPES, true);
    }

    /**
     * Config de notificaciones de una columna de aviso, con los defaults
     * aplicados. Acepta la columna cruda de la base (settings como JSON) o ya
     * decodificada por StockTableModel::payload().
     */
    public static function settingsFor(?array $column): array
    {
        $raw = $column['settings'] ?? null;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            'label'      => (string) ($raw['label'] ?? self::AVISO_DEFAULTS['label']),
            'comparator' => in_array($raw['comparator'] ?? '', self::COMPARATORS, true)
                ? (string) $raw['comparator']
                : self::AVISO_DEFAULTS['comparator'],
            'zero'       => (bool) ($raw['zero'] ?? self::AVISO_DEFAULTS['zero']),
            'minimo'     => (bool) ($raw['minimo'] ?? self::AVISO_DEFAULTS['minimo']),
        ];
    }

    /**
     * La columna de aviso de la tabla: el panel de notificaciones. Si no existe,
     * la tabla usa AVISO_DEFAULTS (o sea, los avisos automáticos prendidos).
     */
    public function avisoColumn(int $tableId): ?array
    {
        return $this->where('table_id', $tableId)
            ->where('type', self::TYPE_AVISO)
            ->first();
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

    public function countTypeForTable(int $tableId, string $type, ?int $exceptColumnId = null): int
    {
        $builder = $this->where('table_id', $tableId)
            ->where('type', $type);

        if ($exceptColumnId !== null) {
            $builder->where('id !=', $exceptColumnId);
        }

        return $builder->countAllResults();
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
