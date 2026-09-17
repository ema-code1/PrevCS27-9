<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Suscripción PrevCS Pro (Mercado Pago · Suscripciones con plan).
 *
 * Todo lo que define el cobro sale del .env y vive acá: el navegador nunca
 * manda monto, moneda, frecuencia ni plan. Las vistas muestran el mismo valor
 * que se le cobra al cliente.
 *
 * Cambiar el precio: editar MP_PLAN_AMOUNT y correr `php spark mp:plan`, que
 * actualiza el plan en Mercado Pago.
 *
 * Pasar a producción: reemplazar MP_ACCESS_TOKEN y MP_PUBLIC_KEY por las de la
 * pestaña Producción y volver a correr `php spark mp:plan`. El plan pertenece a
 * la cuenta, así que el de prueba no existe en la cuenta real.
 */
class Subscription extends BaseConfig
{
    public string $accessToken = '';
    public string $publicKey = '';
    public string $planId = '';
    public string $reason = 'PrevCS Pro';
    public float $amount = 1000.0;
    public string $currency = 'ARS';
    public int $frequency = 1;
    public string $frequencyType = 'months';
    public string $appUrl = '';
    public string $webhookSecret = '';

    public function __construct()
    {
        parent::__construct();

        $this->accessToken = trim((string) env('MP_ACCESS_TOKEN', ''));
        $this->publicKey   = trim((string) env('MP_PUBLIC_KEY', ''));
        $this->planId      = trim((string) env('MP_SUBSCRIPTION_PLAN_ID', ''));
        $this->amount      = round((float) env('MP_PLAN_AMOUNT', $this->amount), 2);
        $this->currency    = strtoupper(trim((string) env('MP_PLAN_CURRENCY', $this->currency)));
        $this->appUrl      = rtrim(trim((string) env('APP_URL', '')), '/');
        $this->webhookSecret = trim((string) env('MP_WEBHOOK_SECRET', ''));
    }

    /**
     * Oferta confiable: lo único que se usa para crear el plan y las
     * suscripciones. Nada de esto se acepta desde el request.
     *
     * @return array{planId: string, reason: string, amount: float, currency: string, frequency: int, frequencyType: string, backUrl: string}
     */
    public function trustedOffer(): array
    {
        return [
            'planId'        => $this->planId,
            'reason'        => $this->reason,
            'amount'        => $this->amount,
            'currency'      => $this->currency,
            'frequency'     => $this->frequency,
            'frequencyType' => $this->frequencyType,
            'backUrl'       => $this->backUrl(),
        ];
    }

    /** back_url del plan: APP_URL, o app.baseURL si APP_URL no está. */
    public function backUrl(): string
    {
        helper('url');
        $base = $this->appUrl !== '' ? $this->appUrl : rtrim(base_url(), '/');

        return $base . '/pago';
    }

    public function isConfigured(): bool
    {
        return $this->accessToken !== '' && $this->publicKey !== '' && $this->planId !== '';
    }

    /** Precio para las vistas: "ARS $1.000" o "ARS $1.234,50". */
    public function displayPrice(): string
    {
        return $this->currency . "\u{00A0}$" . $this->displayAmount();
    }

    public function displayAmount(): string
    {
        $decimals = floor($this->amount) === $this->amount ? 0 : 2;

        return number_format($this->amount, $decimals, ',', '.');
    }
}
