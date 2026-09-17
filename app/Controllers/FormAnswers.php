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
        $this->syncFromSheet();

        $model   = new FormAnswerModel();
        $payload = $model->getMetrics();

        // Sin cache: cada recarga del portfolio tiene que ver las respuestas nuevas.
        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON($payload);
    }

    /**
     * Trae las respuestas nuevas del Google Sheet antes de calcular métricas.
     * Como el import es idempotente, solo inserta lo que falta. Se limita a
     * una sincronización cada `formImport.syncSeconds` (30 s por defecto) para
     * no descargar el CSV en cada request ni frenar la página si Google no
     * responde; un fallo no rompe el endpoint, se sirven los datos que haya.
     */
    private function syncFromSheet(): void
    {
        $cacheKey = 'form_answers_last_sync';
        $interval = max(0, (int) env('formImport.syncSeconds', 30));

        if ($interval > 0 && cache($cacheKey) !== null) {
            return;
        }
        if ($interval > 0) {
            cache()->save($cacheKey, time(), $interval);
        }

        $url = env('formImport.csvUrl', FormImportService::DEFAULT_CSV_URL);
        // Parámetro descartable para que la caché de Google no devuelva un CSV viejo.
        $url .= (str_contains($url, '?') ? '&' : '?') . '_=' . time();

        $result = (new FormImportService())->importFromUrl($url);
        if (! $result['ok']) {
            log_message('warning', 'Sync de respuestas del formulario: ' . implode(' | ', $result['errors']));
        }
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
