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

        return view('stock/index', [
            'title'                 => 'Stock · ' . esc($industry['space_name']) . ' | PrevCS',
            'industry'              => $industry,
            // El sidebar marca "Stock" abierto y resalta el espacio actual.
            'activeMenu'            => 'stock',
            'activeStockIndustryId' => $industryId,
        ]);
    }
}
