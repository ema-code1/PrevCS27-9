<?php

namespace App\Controllers;

use App\Libraries\MercadoPagoSubscriptions;
use App\Models\SubscriptionModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Subscription;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Webhook\WebhookSignatureValidator;
use Throwable;

/**
 * Notificaciones (webhooks) de Mercado Pago.
 *
 * POST api/webhooks/mercadopago?data.id=...&type=...
 *
 * Queda fuera de authGuard y csrf: la autenticidad se prueba con la firma
 * HMAC del header x-signature y la clave secreta MP_WEBHOOK_SECRET.
 *
 * Nunca se confía en el contenido de la notificación: solo se usa el id para
 * consultar el estado real en la API. Por eso procesar el mismo evento dos
 * veces no tiene efectos duplicados (idempotente).
 *
 * Respuestas: 200 recibido (o ignorado), 401 firma inválida, 503 sin clave
 * configurada, 502 si Mercado Pago no respondió (así reintenta más tarde).
 */
class WebhookController extends BaseController
{
    public function receive(): ResponseInterface
    {
        $plan = config(Subscription::class);
        if ($plan->webhookSecret === '' || $plan->accessToken === '') {
            log_message('error', 'Webhook Mercado Pago: faltan MP_WEBHOOK_SECRET o MP_ACCESS_TOKEN en el .env.');

            return $this->reply(503);
        }

        // PHP convierte "data.id" en "data_id" dentro de $_GET: se lee la query cruda.
        $query     = $this->rawQuery();
        $body      = $this->request->getJSON(true) ?? [];
        $dataId    = (string) ($query['data.id'] ?? $query['id'] ?? ($body['data']['id'] ?? ''));
        $type      = (string) ($query['type'] ?? $query['topic'] ?? ($body['type'] ?? ''));
        $requestId = $this->request->getHeaderLine('x-request-id');

        try {
            WebhookSignatureValidator::validate(
                $this->request->getHeaderLine('x-signature'),
                $requestId,
                $this->signatureId($dataId),
                $plan->webhookSecret
            );
        } catch (InvalidWebhookSignatureException $e) {
            log_message('warning', 'Webhook Mercado Pago con firma inválida ({reason}) request {request}.', [
                'reason'  => $e->getMessage(),
                'request' => $requestId,
            ]);

            return $this->reply(401);
        }

        if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $dataId)) {
            log_message('warning', 'Webhook Mercado Pago {type} sin data.id válido, request {request}.', [
                'type'    => $type,
                'request' => $requestId,
            ]);

            return $this->reply(200);
        }

        return match ($type) {
            'subscription_preapproval' => $this->syncPreapproval($plan, $dataId, $requestId),
            default                    => $this->logOnly($type, $dataId, $requestId),
        };
    }

    /** Estado de la suscripción: se consulta en la API y se guarda en la fila local. */
    private function syncPreapproval(Subscription $plan, string $preapprovalId, string $requestId): ResponseInterface
    {
        try {
            $result = (new MercadoPagoSubscriptions($plan))->find($preapprovalId);
        } catch (MPApiException $e) {
            if ($e->getStatusCode() === 404) {
                // Ej.: la notificación de prueba del panel usa un id inventado.
                log_message('info', 'Webhook Mercado Pago: preapproval {id} no existe (request {request}).', [
                    'id'      => $preapprovalId,
                    'request' => $requestId,
                ]);

                return $this->reply(200);
            }

            log_message('error', 'Webhook Mercado Pago: error {status} al consultar preapproval {id}.', [
                'status' => $e->getStatusCode(),
                'id'     => $preapprovalId,
            ]);

            return $this->reply(502);
        } catch (Throwable $e) {
            log_message('error', 'Webhook Mercado Pago: sin respuesta al consultar preapproval {id}: {message}', [
                'id'      => $preapprovalId,
                'message' => $e->getMessage(),
            ]);

            return $this->reply(502);
        }

        $updated = (new SubscriptionModel())->syncFromMercadoPago($result);

        log_message('info', 'Webhook Mercado Pago: preapproval {id} → {status} ({outcome}).', [
            'id'      => $preapprovalId,
            'status'  => $result['status'],
            'outcome' => $updated ? 'sincronizada' : 'sin fila local',
        ]);

        return $this->reply(200);
    }

    /** Tópicos que todavía no se procesan: se registran y se confirma la recepción. */
    private function logOnly(string $type, string $dataId, string $requestId): ResponseInterface
    {
        log_message('info', 'Webhook Mercado Pago recibido sin procesar: {type} {id} (request {request}).', [
            'type'    => $type !== '' ? $type : '(sin tipo)',
            'id'      => $dataId,
            'request' => $requestId,
        ]);

        return $this->reply(200);
    }

    /** Mercado Pago firma el id en minúsculas cuando es alfanumérico. */
    private function signatureId(string $dataId): string
    {
        return ctype_alnum($dataId) ? strtolower($dataId) : $dataId;
    }

    /** @return array<string, string> */
    private function rawQuery(): array
    {
        $params = [];
        foreach (explode('&', $this->request->getUri()->getQuery()) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $params[urldecode($key)] = urldecode($value);
        }

        return $params;
    }

    private function reply(int $status): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['ok' => $status === 200]);
    }
}
