<?php

namespace App\Controllers;

use App\Libraries\MercadoPagoSubscriptions;
use App\Models\SubscriptionModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Subscription;
use MercadoPago\Exceptions\MPApiException;
use Throwable;

/**
 * Suscripción a PrevCS Pro con Mercado Pago (Suscripciones con plan).
 *
 * El navegador solo manda el token de tarjeta de un solo uso (MercadoPago.js)
 * y los datos del pagador. Plan, monto, moneda y frecuencia salen siempre de
 * Config\Subscription. Las respuestas siguen el formato {ok, csrf, ...} del
 * resto de la app porque el CSRF se regenera en cada POST.
 */
class SubscriptionController extends BaseController
{
    /** Acción permitida → estado que se envía a /preapproval/{id}. */
    private const LIFECYCLE = [
        'pause'      => 'paused',
        'reactivate' => 'authorized',
        'cancel'     => 'canceled',
    ];

    private const STATUS_LABELS = [
        'creating'   => 'Procesando',
        'failed'     => 'Rechazada',
        'pending'    => 'Pendiente',
        'authorized' => 'Activa',
        'paused'     => 'Pausada',
        'canceled'   => 'Cancelada',
    ];

    /** GET api/mp-config · Public Key para MercadoPago.js, sin cache. */
    public function config(): ResponseInterface
    {
        $plan = config(Subscription::class);
        $this->response->setHeader('Cache-Control', 'no-store, max-age=0');

        if ($plan->publicKey === '') {
            return $this->response
                ->setStatusCode(503)
                ->setJSON(['error' => 'Falta MP_PUBLIC_KEY en el .env del servidor.']);
        }

        return $this->response->setJSON(['publicKey' => $plan->publicKey]);
    }

    /** POST api/subscriptions · crea la suscripción al plan (status authorized). */
    public function create(): ResponseInterface
    {
        $plan = config(Subscription::class);
        if (! $plan->isConfigured()) {
            log_message('error', 'Mercado Pago: faltan MP_ACCESS_TOKEN, MP_PUBLIC_KEY o MP_SUBSCRIPTION_PLAN_ID en el .env.');

            return $this->fail('Las suscripciones todavía no están configuradas. Probá de nuevo más tarde.', 503);
        }

        $input     = $this->request->getJSON(true) ?? [];
        $cardToken = trim((string) ($input['token'] ?? ''));
        $email     = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $name      = trim((string) ($input['name'] ?? ''));

        if (! preg_match('/^[A-Za-z0-9]{8,64}$/', $cardToken)) {
            return $this->fail('No pudimos leer los datos de la tarjeta. Revisalos e intentá nuevamente.');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Ingresá un email válido.');
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            return $this->fail('Ingresá tu nombre completo.');
        }

        $userId = session('isLoggedIn') === true ? (int) session('user_id') : null;
        $model  = new SubscriptionModel();

        if ($userId !== null && $model->activeForUser($userId) !== null) {
            return $this->fail('Tu cuenta ya tiene una suscripción a PrevCS Pro.', 409);
        }

        $offer  = $plan->trustedOffer();
        $record = $model->start($offer, $email, $name, $userId);
        if ($record === null) {
            return $this->fail('No pudimos iniciar la suscripción. Intentá nuevamente.', 500);
        }

        try {
            $result = (new MercadoPagoSubscriptions($plan))
                ->subscribe($offer, $email, $cardToken, $record['external_reference']);
        } catch (MPApiException $e) {
            $model->update($record['id'], ['status' => 'failed']);
            $this->logApiError('crear suscripción', $e, $record['external_reference']);

            return $this->fail($this->apiErrorMessage($e->getStatusCode()), $e->getStatusCode() >= 500 ? 502 : 422);
        } catch (Throwable $e) {
            $model->update($record['id'], ['status' => 'failed']);
            log_message('error', 'Mercado Pago sin respuesta al crear suscripción {ref}: {message}', [
                'ref'     => $record['external_reference'],
                'message' => $e->getMessage(),
            ]);

            return $this->fail('No pudimos conectarnos con Mercado Pago. Intentá nuevamente en unos minutos.', 502);
        }

        $model->update($record['id'], [
            'mp_preapproval_id' => $result['id'],
            'status'            => mb_substr($result['status'], 0, 20),
        ]);

        if ($userId === null) {
            // Todavía no hay cuenta: Auth::register la vincula al crearla.
            session()->set('pending_subscription_ref', $record['external_reference']);
        }

        return $this->ok([
            'subscription' => $this->publicView($record['external_reference'], $result['status'], $result['nextPaymentDate']),
        ]);
    }

    /** GET api/subscriptions/:id · estado actual, consultado en Mercado Pago. */
    public function show(string $reference): ResponseInterface
    {
        $model  = new SubscriptionModel();
        $record = $this->ownedRecord($model, $reference);
        if ($record === null) {
            return $this->fail('No encontramos esa suscripción.', 404);
        }

        if (empty($record['mp_preapproval_id'])) {
            return $this->ok(['subscription' => $this->publicView($reference, $record['status'])]);
        }

        try {
            $result = (new MercadoPagoSubscriptions(config(Subscription::class)))->find($record['mp_preapproval_id']);
        } catch (MPApiException $e) {
            $this->logApiError('consultar suscripción', $e, $reference);

            return $this->fail('No pudimos consultar el estado en Mercado Pago. Intentá nuevamente.', 502);
        } catch (Throwable $e) {
            log_message('error', 'Mercado Pago sin respuesta al consultar {ref}: {message}', ['ref' => $reference, 'message' => $e->getMessage()]);

            return $this->fail('No pudimos conectarnos con Mercado Pago. Intentá nuevamente en unos minutos.', 502);
        }

        if (in_array($result['status'], SubscriptionModel::MP_STATUSES, true) && $result['status'] !== $record['status']) {
            $model->update($record['id'], ['status' => $result['status']]);
        }

        return $this->ok(['subscription' => $this->publicView($reference, $result['status'], $result['nextPaymentDate'])]);
    }

    /** POST api/subscriptions/:id/(pause|reactivate|cancel) */
    public function lifecycle(string $reference, string $action): ResponseInterface
    {
        $target = self::LIFECYCLE[$action] ?? null;
        if ($target === null) {
            return $this->fail('Acción no permitida.', 400);
        }

        $model  = new SubscriptionModel();
        $record = $this->ownedRecord($model, $reference);
        if ($record === null || empty($record['mp_preapproval_id'])) {
            return $this->fail('No encontramos esa suscripción.', 404);
        }
        if ($record['status'] === 'canceled') {
            return $this->fail('La suscripción ya está cancelada. Para volver a usar PrevCS Pro, suscribite de nuevo.', 409);
        }
        if ($record['status'] === $target) {
            return $this->ok(['subscription' => $this->publicView($reference, $target)]);
        }

        try {
            $result = (new MercadoPagoSubscriptions(config(Subscription::class)))
                ->changeStatus($record['mp_preapproval_id'], $target);
        } catch (MPApiException $e) {
            $this->logApiError($action . ' suscripción', $e, $reference);

            return $this->fail('Mercado Pago no permitió ese cambio. Intentá nuevamente.', 422);
        } catch (Throwable $e) {
            log_message('error', 'Mercado Pago sin respuesta al cambiar {ref}: {message}', ['ref' => $reference, 'message' => $e->getMessage()]);

            return $this->fail('No pudimos conectarnos con Mercado Pago. Intentá nuevamente en unos minutos.', 502);
        }

        $model->update($record['id'], ['status' => mb_substr($result['status'], 0, 20)]);

        return $this->ok(['subscription' => $this->publicView($reference, $result['status'], $result['nextPaymentDate'])]);
    }

    /** Solo el dueño logueado ve o modifica su suscripción. */
    private function ownedRecord(SubscriptionModel $model, string $reference): ?array
    {
        $record = $model->findByReference($reference);
        $userId = (int) session('user_id');

        if ($record === null || $userId === 0 || (int) $record['user_id'] !== $userId) {
            return null;
        }

        return $record;
    }

    private function publicView(string $reference, string $status, ?string $nextPaymentDate = null): array
    {
        return [
            'id'              => $reference,
            'status'          => $status,
            'label'           => self::STATUS_LABELS[$status] ?? $status,
            'nextPaymentDate' => $nextPaymentDate,
        ];
    }

    private function apiErrorMessage(int $statusCode): string
    {
        return match (true) {
            $statusCode === 400, $statusCode === 422 => 'Mercado Pago rechazó la tarjeta o los datos. Revisalos o probá con otra tarjeta.',
            $statusCode === 401, $statusCode === 403 => 'Hay un problema con la configuración de pagos. Avisanos y lo resolvemos.',
            $statusCode === 404                      => 'El plan de suscripción no está disponible. Avisanos y lo resolvemos.',
            default                                  => 'Mercado Pago no pudo procesar la suscripción. Intentá nuevamente.',
        };
    }

    /** Log sin token ni datos de tarjeta: código HTTP, mensaje de la API y referencia. */
    private function logApiError(string $operation, MPApiException $e, string $reference): void
    {
        $content = $e->getApiResponse()->getContent();

        log_message('error', 'Mercado Pago rechazó {operation} ({status}) ref {ref}: {detail}', [
            'operation' => $operation,
            'status'    => $e->getStatusCode(),
            'ref'       => $reference,
            'detail'    => (string) ($content['message'] ?? $e->getMessage()),
        ]);
    }

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
