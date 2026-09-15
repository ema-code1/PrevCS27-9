<?php

namespace App\Libraries;

use Config\Subscription;
use InvalidArgumentException;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\Client\PreApprovalPlan\PreApprovalPlanClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\PreApproval;
use MercadoPago\Resources\PreApprovalPlan;

/**
 * Único punto de contacto con la API de Suscripciones de Mercado Pago, a
 * través del SDK oficial mercadopago/dx-php:
 *   - https://api.mercadopago.com/preapproval       (suscripciones)
 *   - https://api.mercadopago.com/preapproval_plan  (plan de PrevCS Pro)
 *
 * El Access Token solo vive del lado del servidor. Los métodos devuelven
 * arrays chicos con lo que la app necesita; los errores de la API llegan como
 * MercadoPago\Exceptions\MPApiException (con el código HTTP).
 */
final class MercadoPagoSubscriptions
{
    /** Estados que la app puede pedir por PUT /preapproval/{id}. */
    private const SETTABLE_STATUSES = ['paused', 'authorized', 'canceled'];

    public function __construct(Subscription $config)
    {
        MercadoPagoConfig::setAccessToken($config->accessToken);
    }

    /**
     * Suscribe al pagador al plan con la tarjeta ya tokenizada por MercadoPago.js.
     * El token es de un solo uso: no se guarda ni se loguea.
     */
    public function subscribe(array $trustedOffer, string $payerEmail, string $cardToken, string $externalReference): array
    {
        $preapproval = (new PreApprovalClient())->create([
            'preapproval_plan_id' => $trustedOffer['planId'],
            'payer_email'         => $payerEmail,
            'card_token_id'       => $cardToken,
            'external_reference'  => $externalReference,
            'back_url'            => $trustedOffer['backUrl'],
            'status'              => 'authorized',
        ]);

        return $this->summary($preapproval);
    }

    public function find(string $preapprovalId): array
    {
        return $this->summary((new PreApprovalClient())->get($preapprovalId));
    }

    public function changeStatus(string $preapprovalId, string $status): array
    {
        if (! in_array($status, self::SETTABLE_STATUSES, true)) {
            throw new InvalidArgumentException('Estado de suscripción no permitido: ' . $status);
        }

        return $this->summary((new PreApprovalClient())->update($preapprovalId, ['status' => $status]));
    }

    public function createPlan(array $trustedOffer): array
    {
        return $this->planSummary((new PreApprovalPlanClient())->create($this->planBody($trustedOffer)));
    }

    public function findPlan(string $planId): array
    {
        return $this->planSummary((new PreApprovalPlanClient())->get($planId));
    }

    public function updatePlan(string $planId, array $trustedOffer): array
    {
        return $this->planSummary((new PreApprovalPlanClient())->update($planId, $this->planBody($trustedOffer)));
    }

    private function planBody(array $trustedOffer): array
    {
        return [
            'reason'         => $trustedOffer['reason'],
            'auto_recurring' => [
                'frequency'          => $trustedOffer['frequency'],
                'frequency_type'     => $trustedOffer['frequencyType'],
                'transaction_amount' => $trustedOffer['amount'],
                'currency_id'        => $trustedOffer['currency'],
            ],
            'back_url' => $trustedOffer['backUrl'],
        ];
    }

    private function summary(PreApproval $preapproval): array
    {
        return [
            'id'                => (string) ($preapproval->id ?? ''),
            'status'            => (string) ($preapproval->status ?? ''),
            'externalReference' => (string) ($preapproval->external_reference ?? ''),
            'nextPaymentDate'   => $preapproval->next_payment_date ?? null,
        ];
    }

    private function planSummary(PreApprovalPlan $plan): array
    {
        $recurring = (array) ($plan->auto_recurring ?? []);

        return [
            'id'            => (string) ($plan->id ?? ''),
            'status'        => (string) ($plan->status ?? ''),
            'reason'        => (string) ($plan->reason ?? ''),
            'amount'        => isset($recurring['transaction_amount']) ? (float) $recurring['transaction_amount'] : null,
            'currency'      => (string) ($recurring['currency_id'] ?? ''),
            'frequency'     => isset($recurring['frequency']) ? (int) $recurring['frequency'] : null,
            'frequencyType' => (string) ($recurring['frequency_type'] ?? ''),
        ];
    }
}
