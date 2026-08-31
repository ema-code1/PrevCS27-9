<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bandeja de la campana del header.
 *
 * El feed es GET a propósito: no pasa por el filtro CSRF, así el polling del
 * layout no le rota el token a la página que esté abierta (stock y kanban lo
 * tienen cacheado y `$regenerate = true` invalida el anterior en cada POST).
 */
class NotificationController extends BaseController
{
    public function feed(): ResponseInterface
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'error' => 'Sesión vencida.']);
        }

        $model = new NotificationModel();

        return $this->response->setJSON([
            'ok'     => true,
            'items'  => $model->feedFor($userId),
            'unread' => $model->unreadCountFor($userId),
        ]);
    }

    public function markAllRead(): ResponseInterface
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['ok' => false, 'error' => 'Sesión vencida.', 'csrf' => csrf_hash()]);
        }

        (new NotificationModel())->markAllRead($userId);

        // El hash nuevo viaja como en el resto de la API: el cliente lo publica
        // para que el siguiente POST de la página no falle.
        return $this->response->setJSON(['ok' => true, 'unread' => 0, 'csrf' => csrf_hash()]);
    }
}
