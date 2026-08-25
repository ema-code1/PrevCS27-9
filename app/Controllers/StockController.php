<?php

namespace App\Controllers;

use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\StockCellModel;
use App\Models\StockColumnModel;
use App\Models\StockRowModel;
use App\Models\StockTableModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * Stock por espacio: vista + API JSON de las tablas interactivas.
 *
 * Reglas de scope (se aplican en TODOS los endpoints):
 *   1. La URL siempre lleva el id del espacio.
 *   2. Se verifica que el usuario logueado sea miembro de ese espacio; si no,
 *      el espacio "no existe" para él (404).
 *   3. Escribir exige además IndustryUserModel::canEdit().
 *   4. Tablas, columnas, filas y celdas se buscan siempre con findInIndustry()
 *      / findInTable(), que exigen la pertenencia. Mandar el id de un elemento
 *      de otro espacio devuelve 404.
 *
 * Cada fila es un ítem de stock: su nombre vive en la columna con
 * is_item_column = 1, que es permanente y no se puede borrar ni cambiar de
 * tipo. Esas reglas se aplican acá y no solo en el JS, porque el cliente es
 * manipulable.
 */
class StockController extends BaseController
{
    private array $industry = [];

    private bool $canEdit = false;

    /* =================================================================
       Vista
       ================================================================= */

    public function index($industryId = null)
    {
        $industryId = (int) $industryId;
        $this->loadIndustryOrFail($industryId);

        $tableModel = new StockTableModel();

        // Los espacios creados antes de esta feature no tienen tabla todavía:
        // se les provisiona la primera al entrar, para sostener el mínimo de
        // una tabla por espacio.
        if ($tableModel->countForIndustry($industryId) === 0) {
            $tableModel->provisionDefault(
                $industryId,
                $this->industry['company_name'] ?: $this->industry['space_name'],
                (int) session('user_id')
            );
        }

        return view('stock/index', [
            'title'                 => 'Stock · ' . esc($this->industry['space_name']) . ' | PrevCS',
            'industry'              => $this->industry,
            'stockState'            => $tableModel->payloadForIndustry($industryId),
            'canEdit'               => $this->canEdit,
            // El sidebar marca "Stock" abierto y resalta el espacio actual.
            'activeMenu'            => 'stock',
            'activeStockIndustryId' => $industryId,
        ]);
    }

    /* =================================================================
       Tablas
       ================================================================= */

    public function createTable($industryId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $tableModel = new StockTableModel();
        $title      = $this->text($this->payload()['title'] ?? '', 200);

        $tableId = $tableModel->provisionDefault(
            (int) $industryId,
            $title !== '' ? $title : 'Tabla de stock',
            (int) session('user_id')
        );

        return $this->ok(['table' => $tableModel->payload($tableId)]);
    }

    public function updateTable($industryId = null, $tableId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $data  = $this->payload();
        $model = new StockTableModel();

        if (array_key_exists('title', $data)) {
            $title = $this->text($data['title'], 200);
            if ($title === '') {
                return $this->fail('La tabla necesita un título.');
            }
            $model->update((int) $table['id'], ['title' => $title]);
        }

        return $this->ok(['table' => $model->payload((int) $table['id'])]);
    }

    public function deleteTable($industryId = null, $tableId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $model = new StockTableModel();
        if ($model->countForIndustry((int) $industryId) <= 1) {
            return $this->fail('El espacio tiene que conservar al menos una tabla.');
        }

        // Las FK en cascada se llevan columnas, filas y celdas.
        $model->delete((int) $table['id']);

        return $this->ok(['deleted' => (int) $table['id']]);
    }

    /* =================================================================
       Columnas
       ================================================================= */

    public function createColumn($industryId = null, $tableId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $data = $this->payload();
        $name = $this->text($data['name'] ?? '', 120);
        $type = (string) ($data['type'] ?? StockColumnModel::TYPE_TEXTO);

        if ($name === '') {
            return $this->fail('La columna necesita un nombre.');
        }
        if (! StockColumnModel::isValidType($type)) {
            return $this->fail('Ese tipo de columna no existe.');
        }

        $columnModel = new StockColumnModel();
        $ultima      = $columnModel->nextPosition((int) $table['id']);

        // La posición 0 es de la columna del ítem: cualquier inserción arranca
        // como mínimo en la 1.
        $position = array_key_exists('position', $data)
            ? max(1, min((int) $data['position'], $ultima))
            : $ultima;

        if ($position < $ultima) {
            $columnModel->shiftFrom((int) $table['id'], $position);
        }

        $columnModel->insert([
            'table_id' => (int) $table['id'],
            'name'     => $name,
            'type'     => $type,
            'unit'     => $this->unitFor($type, $data['unit'] ?? null),
            'position' => $position,
        ]);

        $columnModel->resequence((int) $table['id']);

        return $this->ok(['table' => (new StockTableModel())->payload((int) $table['id'])]);
    }

    public function updateColumn($industryId = null, $tableId = null, $columnId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $columnModel = new StockColumnModel();
        $column      = $columnModel->findInTable((int) $columnId, (int) $table['id']);
        if ($column === null) {
            return $this->fail('Esa columna no existe en esta tabla.', 404);
        }

        $data    = $this->payload();
        $esItem  = (bool) $column['is_item_column'];
        $changes = [];

        if (array_key_exists('name', $data)) {
            $name = $this->text($data['name'], 120);
            if ($name === '') {
                return $this->fail('La columna necesita un nombre.');
            }
            $changes['name'] = $name;
        }

        // La columna del ítem se puede renombrar, nada más: es la que identifica
        // cada ítem en las alertas de stock, así que su tipo es intocable.
        if (! $esItem && array_key_exists('type', $data)) {
            $type = (string) $data['type'];
            if (! StockColumnModel::isValidType($type)) {
                return $this->fail('Ese tipo de columna no existe.');
            }

            if ($type !== $column['type']) {
                // Los valores se trasladan al tipo nuevo en vez de perderse.
                (new StockCellModel())->convertColumn((int) $column['id'], (string) $column['type'], $type);
                $changes['type'] = $type;
            }
        }

        if (! $esItem && array_key_exists('unit', $data)) {
            $type            = $changes['type'] ?? (string) $column['type'];
            $changes['unit'] = $this->unitFor($type, $data['unit']);
        }

        if ($changes !== []) {
            $columnModel->update((int) $column['id'], $changes);
        }

        return $this->ok(['table' => (new StockTableModel())->payload((int) $table['id'])]);
    }

    public function deleteColumn($industryId = null, $tableId = null, $columnId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $columnModel = new StockColumnModel();
        $column      = $columnModel->findInTable((int) $columnId, (int) $table['id']);
        if ($column === null) {
            return $this->fail('Esa columna no existe en esta tabla.', 404);
        }

        if ((bool) $column['is_item_column']) {
            return $this->fail('La columna de ítem no se puede eliminar.');
        }

        if ($columnModel->countForTable((int) $table['id']) <= 1) {
            return $this->fail('La tabla tiene que conservar al menos una columna.');
        }

        $columnModel->delete((int) $column['id']);
        $columnModel->resequence((int) $table['id']);

        return $this->ok(['table' => (new StockTableModel())->payload((int) $table['id'])]);
    }

    /* =================================================================
       Filas (cada una es un ítem)
       ================================================================= */

    public function createRow($industryId = null, $tableId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $rowModel = new StockRowModel();
        $rowModel->insert([
            'table_id' => (int) $table['id'],
            'position' => $rowModel->nextPosition((int) $table['id']),
        ]);

        return $this->ok(['table' => (new StockTableModel())->payload((int) $table['id'])]);
    }

    public function deleteRow($industryId = null, $tableId = null, $rowId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $rowModel = new StockRowModel();
        $row      = $rowModel->findInTable((int) $rowId, (int) $table['id']);
        if ($row === null) {
            return $this->fail('Esa fila no existe en esta tabla.', 404);
        }

        if ($rowModel->countForTable((int) $table['id']) <= 1) {
            return $this->fail('La tabla tiene que conservar al menos una fila.');
        }

        $rowModel->delete((int) $row['id']);
        $rowModel->resequence((int) $table['id']);

        return $this->ok(['table' => (new StockTableModel())->payload((int) $table['id'])]);
    }

    /* =================================================================
       Celdas
       ================================================================= */

    public function updateCell($industryId = null, $tableId = null, $rowId = null, $columnId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $table = $this->findTable((int) $industryId, (int) $tableId);
        if ($table === null) {
            return $this->fail('Esa tabla no existe en este espacio.', 404);
        }

        $row    = (new StockRowModel())->findInTable((int) $rowId, (int) $table['id']);
        $column = (new StockColumnModel())->findInTable((int) $columnId, (int) $table['id']);

        if ($row === null || $column === null) {
            return $this->fail('Esa celda no existe en esta tabla.', 404);
        }

        $data  = $this->payload();
        $type  = (string) $column['type'];
        $value = StockColumnModel::isNumericType($type)
            ? $this->number($data['value'] ?? 0)
            : $this->text($data['value'] ?? '', 2000);

        (new StockCellModel())->put((int) $row['id'], (int) $column['id'], $type, $value);

        // La celda no devuelve la tabla entera: es el endpoint más caliente
        // (una escritura por edición) y el cliente ya tiene el valor.
        return $this->ok(['value' => $value]);
    }

    /* =================================================================
       Importación de Excel
       ================================================================= */

    /**
     * Crea una tabla completa a partir de la matriz que el cliente saca del
     * .xlsx con SheetJS. La primera fila del archivo son los encabezados y la
     * primera columna pasa a ser la columna de ítem.
     */
    public function importTable($industryId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $data    = $this->payload();
        $title   = $this->text($data['title'] ?? '', 200);
        $headers = is_array($data['headers'] ?? null) ? array_values($data['headers']) : [];
        $rows    = is_array($data['rows'] ?? null) ? array_values($data['rows']) : [];

        if ($headers === []) {
            return $this->fail('La planilla no tiene encabezados.');
        }

        // Techo defensivo: el payload viene del navegador y una planilla enorme
        // no debe poder tumbar la request.
        $headers = array_slice($headers, 0, 50);
        $rows    = array_slice($rows, 0, 1000);

        $db = Database::connect();
        $db->transStart();

        $tableModel  = new StockTableModel();
        $columnModel = new StockColumnModel();
        $rowModel    = new StockRowModel();
        $cellModel   = new StockCellModel();

        $tableId = (int) $tableModel->insert([
            'industry_id' => (int) $industryId,
            'title'       => $title !== '' ? $title : 'Planilla importada',
            'position'    => $tableModel->nextPosition((int) $industryId),
            'created_by'  => (int) session('user_id'),
        ], true);

        $columnIds = [];
        foreach ($headers as $index => $header) {
            $name = $this->text($header, 120);

            $columnIds[] = (int) $columnModel->insert([
                'table_id'       => $tableId,
                'name'           => $name !== '' ? $name : 'Columna ' . ($index + 1),
                'type'           => StockColumnModel::TYPE_TEXTO,
                'is_item_column' => $index === 0 ? 1 : 0,
                'position'       => $index,
            ], true);
        }

        // Una planilla sin filas igual necesita una para no quedar sin forma.
        if ($rows === []) {
            $rows = [[]];
        }

        foreach ($rows as $index => $values) {
            $rowId = (int) $rowModel->insert([
                'table_id' => $tableId,
                'position' => $index,
            ], true);

            if (! is_array($values)) {
                continue;
            }

            foreach ($columnIds as $colIndex => $colId) {
                $value = $this->text($values[$colIndex] ?? '', 2000);
                if ($value === '') {
                    continue;
                }
                $cellModel->put($rowId, $colId, StockColumnModel::TYPE_TEXTO, $value);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->fail('No se pudo importar la planilla.', 500);
        }

        return $this->ok(['table' => $tableModel->payload($tableId)]);
    }

    /* =================================================================
       Scope y helpers de respuesta
       ================================================================= */

    /**
     * Carga el espacio para la vista. Si no existe o el usuario no es miembro,
     * el espacio no existe para él: 404, no un redirect que insinúe que está
     * ahí.
     */
    private function loadIndustryOrFail(int $industryId): void
    {
        $userId = (int) session('user_id');

        if ($industryId <= 0 || ! (new IndustryUserModel())->userBelongsTo($userId, $industryId)) {
            throw PageNotFoundException::forPageNotFound('No encontramos ese espacio.');
        }

        $industry = (new IndustryModel())->find($industryId);
        if ($industry === null) {
            throw PageNotFoundException::forPageNotFound('No encontramos ese espacio.');
        }

        $this->industry = $industry;
        $this->canEdit  = (new IndustryUserModel())->canEdit($userId, $industryId);
    }

    /**
     * Resuelve el espacio y verifica membresía + permiso de escritura para los
     * endpoints JSON. Devuelve null si todo está bien, o la respuesta de error.
     */
    private function resolve(int $industryId): ?ResponseInterface
    {
        $userId = (int) session('user_id');

        if ($industryId <= 0) {
            return $this->fail('Espacio inválido.', 404);
        }

        $memberships = new IndustryUserModel();

        if (! $memberships->userBelongsTo($userId, $industryId)) {
            return $this->fail('No tenés acceso al stock de ese espacio.', 404);
        }

        if (! $memberships->canEdit($userId, $industryId)) {
            return $this->fail('No tenés permiso para editar el stock de este espacio.', 403);
        }

        $industry = (new IndustryModel())->find($industryId);
        if ($industry === null) {
            return $this->fail('Ese espacio ya no existe.', 404);
        }

        $this->industry = $industry;
        $this->canEdit  = true;

        return null;
    }

    private function findTable(int $industryId, int $tableId): ?array
    {
        return $tableId > 0
            ? (new StockTableModel())->findInIndustry($tableId, $industryId)
            : null;
    }

    /**
     * La unidad solo tiene sentido en los tipos numéricos; en el resto se
     * guarda null para no arrastrar datos muertos.
     */
    private function unitFor(string $type, $unit): ?string
    {
        if (! StockColumnModel::isNumericType($type)) {
            return null;
        }

        $unit = $this->text($unit ?? '', 40);

        return $unit !== '' ? $unit : 'unidades';
    }

    /** Cuerpo de la petición, venga como JSON o como POST clásico. */
    private function payload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        return $this->request->getPost() ?? [];
    }

    /** Normaliza texto libre: recorta, limita y saca caracteres de control. */
    private function text($value, int $max): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value) ?? '';

        return mb_substr(trim($value), 0, $max);
    }

    /** Valor numérico de contador o mínimo: nunca negativo ni no numérico. */
    private function number($value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return max(0.0, round((float) $value, 3));
    }

    /** Respuesta correcta: siempre viaja el CSRF nuevo para el próximo POST. */
    private function ok(array $payload): ResponseInterface
    {
        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()] + $payload);
    }

    private function fail(string $message, int $status = 422): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(['ok' => false, 'error' => $message, 'csrf' => csrf_hash()]);
    }
}
