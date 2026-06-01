<?php

namespace App\Models;

use CodeIgniter\Model;

class IndustryUserModel extends Model
{
    public const ROLE_ADMIN      = 'admin_principal';
    public const ROLE_SUPERVISOR = 'supervisor';

    protected $table            = 'industry_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_id', 'user_id', 'role'];

    protected bool $allowEmptyInserts = false;

    /**
     * ¿El usuario pertenece a la industria dada?
     */
    public function userBelongsTo(int $userId, int $industryId): bool
    {
        return $this->where('user_id', $userId)
            ->where('industry_id', $industryId)
            ->countAllResults() > 0;
    }

    /**
     * Rol del usuario en una industria (o null si no pertenece).
     */
    public function roleFor(int $userId, int $industryId): ?string
    {
        $row = $this->select('role')
            ->where('user_id', $userId)
            ->where('industry_id', $industryId)
            ->first();

        return $row['role'] ?? null;
    }

    /**
     * Industrias a las que pertenece el usuario, con su rol.
     */
    public function industriesForUser(int $userId): array
    {
        return $this->select('industries.*, industry_users.role')
            ->join('industries', 'industries.id = industry_users.industry_id')
            ->where('industry_users.user_id', $userId)
            ->orderBy('industries.company_name', 'ASC')
            ->findAll();
    }

    /**
     * Agrega (idempotente) un usuario a una industria con un rol.
     */
    public function addMembership(int $userId, int $industryId, string $role): bool
    {
        if ($this->userBelongsTo($userId, $industryId)) {
            return true;
        }

        return (bool) $this->insert([
            'user_id'     => $userId,
            'industry_id' => $industryId,
            'role'        => $role,
        ]);
    }
}
