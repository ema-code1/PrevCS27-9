<?php

namespace App\Controllers;

use App\Libraries\FormImportService;
use App\Models\FormAnswerModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * FormAnswers
 *
 * - GET  /api/form-metrics         → JSON publico para la vista de Portfolio
 *                                    (consumido por Chart.js en portfolio.js)
 * - GET  /form-answers/import      → Ejecuta importacion. Protegido por
 *                                    AuthGuard (solo usuarios logueados).
 *                                    Pensado para correr manualmente o desde
 *                                    un cron job (con autenticacion preestablecida).
 */
class FormAnswers extends BaseController
{
    /**
     * Endpoint publico de metricas. Devuelve un payload listo para Chart.js.
     */
    public function metrics(): ResponseInterface
    {
        $model   = new FormAnswerModel();
        $payload = $model->getMetrics();

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=60')
            ->setJSON($payload);
    }

    /**
     * Endpoint protegido para correr la importacion CSV → MySQL.
     * Devuelve un resumen JSON.
     */
    public function import(): ResponseInterface
    {
        $service = new FormImportService();
        $result  = $service->importFromUrl();

        $status = $result['ok'] ? 200 : 500;
        return $this->response
            ->setStatusCode($status)
            ->setJSON($result);
    }
}
