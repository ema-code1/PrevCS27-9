<?php

namespace App\Controllers;

use App\Models\IndustryModel;
use App\Models\IndustryUserModel;

/**
 * Stock por espacio.
 *
 *  GET espacios/{industryId}/stock → lista de stock del espacio (vacía por ahora)
 *
 * Cada espacio tiene su propia lista, así que la membresía se valida en cada
 * request igual que en el resto del módulo IoT.
 */
class StockController extends BaseController
{
    public function index($industryId = null)
    {
        $userId     = (int) session('user_id');
        $industryId = (int) $industryId;

        if ($industryId <= 0 || ! (new IndustryUserModel())->userBelongsTo($userId, $industryId)) {
            return redirect()->to(base_url('registros-iot'))
                ->with('error', 'No tenés acceso al stock de ese espacio.');
        }

        $industry = (new IndustryModel())->find($industryId);
        if ($industry === null) {
            return redirect()->to(base_url('registros-iot'))
                ->with('error', 'El espacio ya no existe.');
        }

        // El contenedor de la vista acepta varias tablas, pero la unidad es la
        // empresa: una sola tabla junta todo el stock de la empresa del
        // espacio. Cuando existan los items en base, 'rows' se llena acá.
        $tables = [[
            'id'      => 'empresa-' . $industryId,
            'title'   => $industry['company_name'] ?: $industry['space_name'],
            'columns' => [
                ['label' => 'Item',       'icon' => 'text'],
                ['label' => 'Categoría',  'icon' => 'tag'],
                ['label' => 'Cantidad',   'icon' => 'number'],
                ['label' => 'Unidad',     'icon' => 'text'],
                ['label' => 'Estado',     'icon' => 'chip'],
                ['label' => 'Actualizado','icon' => 'date'],
            ],
            'rows'    => [],
        ]];

        return view('stock/index', [
            'title'                 => 'Stock · ' . esc($industry['space_name']) . ' | PrevCS',
            'industry'              => $industry,
            'tables'                => $tables,
            // El sidebar marca "Stock" abierto y resalta el espacio actual.
            'activeMenu'            => 'stock',
            'activeStockIndustryId' => $industryId,
        ]);
    }
}
