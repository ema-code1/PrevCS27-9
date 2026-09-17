<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Suscripciones a PrevCS Pro (Mercado Pago · preapproval con plan).
 */
class SubscriptionModel extends Model
{
    /** Estados que devuelve Mercado Pago para una suscripción. */
    public const MP_STATUSES = ['pending', 'authorized', 'paused', 'cancelled'];

    /** Estados que cuentan como "ya tiene suscripción" para una cuenta. */
    private const ACTIVE_STATUSES = ['pending', 'authorized', 'paused'];

    protected $table            = 'subscriptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id',
        'external_reference',
        'mp_preapproval_id',
        'mp_plan_id',
        'payer_email',
        'payer_name',
        'status',
        'amount',
        'currency',
    ];

    protected bool $allowEmptyInserts = false;

    /**
     * Registra el intento antes de llamar a Mercado Pago, con su
     * external_reference único. Devuelve la fila creada o null.
     */
    public function start(array $trustedOffer, string $payerEmail, string $payerName, ?int $userId): ?array
    {
        $reference = 'prevcs-sub-' . bin2hex(random_bytes(12));

        $ok = $this->insert([
            'user_id'            => $userId,
            'external_reference' => $reference,
            'mp_plan_id'         => $trustedOffer['planId'],
            'payer_email'        => $payerEmail,
            'payer_name'         => $payerName,
            'status'             => 'creating',
            'amount'             => $trustedOffer['amount'],
            'currency'           => $trustedOffer['currency'],
        ]);

        return $ok ? $this->find($this->getInsertID()) : null;
    }

    public function findByReference(string $reference): ?array
    {
        return $this->where('external_reference', $reference)->first();
    }

    public function activeForUser(int $userId): ?array
    {
        return $this->where('user_id', $userId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->first();
    }

    /**
     * Guarda el estado real de una suscripción consultada en Mercado Pago
     * (summary de MercadoPagoSubscriptions::find). Busca por preapproval id y,
     * si todavía no se guardó, por external_reference. Devuelve si hubo fila.
     */
    public function syncFromMercadoPago(array $summary): bool
    {
        $record = $summary['id'] !== '' ? $this->where('mp_preapproval_id', $summary['id'])->first() : null;
        if ($record === null && str_starts_with($summary['externalReference'], 'prevcs-sub-')) {
            $record = $this->findByReference($summary['externalReference']);
        }
        if ($record === null) {
            return false;
        }

        $changes = [];
        if (empty($record['mp_preapproval_id']) && $summary['id'] !== '') {
            $changes['mp_preapproval_id'] = $summary['id'];
        }
        if (in_array($summary['status'], self::MP_STATUSES, true) && $summary['status'] !== $record['status']) {
            $changes['status'] = $summary['status'];
        }
        if ($changes !== []) {
            $this->update($record['id'], $changes);
        }

        return true;
    }

    /** Vincula a la cuenta recién creada la suscripción pagada antes del registro. */
    public function linkToUser(string $reference, int $userId): void
    {
        $this->where('external_reference', $reference)
            ->where('user_id', null)
            ->set(['user_id' => $userId])
            ->update();
    }
}
