<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Invitaciones por correo a un espacio (flujo tipo Google Docs).
 *
 * El token que viaja en el link nunca se guarda entero: en la base queda su
 * SHA-256, que además permite buscar la invitación de una sola consulta
 * (a diferencia de password_resets, que guarda un password_hash y obliga a
 * recorrer las filas comparando una por una).
 */
class SpaceInvitationModel extends Model
{
    /** Una invitación vive 7 días. */
    public const TTL_SECONDS = 7 * 24 * 60 * 60;

    protected $table            = 'space_invitations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'industry_id',
        'email',
        'role',
        'permission',
        'token_hash',
        'invited_by',
        'created_at',
        'expires_at',
        'accepted_at',
        'accepted_by',
    ];

    protected bool $allowEmptyInserts = false;

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Crea la invitación y devuelve el token en claro (única vez que existe).
     * Si ya había una pendiente para ese mail en ese espacio, se reemplaza:
     * el link viejo deja de servir y el admin no acumula filas muertas.
     */
    public function issue(int $industryId, string $email, string $role, string $permission, int $invitedBy): ?string
    {
        $email = mb_strtolower(trim($email));
        $token = bin2hex(random_bytes(32));
        $now   = time();

        $this->where('industry_id', $industryId)
            ->where('email', $email)
            ->where('accepted_at', null)
            ->delete();

        $ok = $this->insert([
            'industry_id' => $industryId,
            'email'       => $email,
            'role'        => $role,
            'permission'  => $permission,
            'token_hash'  => self::hashToken($token),
            'invited_by'  => $invitedBy,
            'created_at'  => date('Y-m-d H:i:s', $now),
            'expires_at'  => date('Y-m-d H:i:s', $now + self::TTL_SECONDS),
        ]);

        return $ok ? $token : null;
    }

    /**
     * Invitación pendiente y vigente para ese token, o null.
     */
    public function findPending(string $token): ?array
    {
        $row = $this->where('token_hash', self::hashToken($token))
            ->where('accepted_at', null)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->expired($row) ? null : $row;
    }

    public function expired(array $invitation): bool
    {
        $expires = $invitation['expires_at'] ?? null;

        return $expires !== null && strtotime((string) $expires) < time();
    }

    /**
     * Pendientes de un espacio, para listarlas debajo de los miembros.
     */
    public function pendingForIndustry(int $industryId): array
    {
        return $this->where('industry_id', $industryId)
            ->where('accepted_at', null)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function markAccepted(int $invitationId, int $userId): void
    {
        $this->update($invitationId, [
            'accepted_at' => date('Y-m-d H:i:s'),
            'accepted_by' => $userId,
        ]);
    }
}
