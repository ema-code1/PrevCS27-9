<?php

namespace App\Models;

use CodeIgniter\Model;

class IndustryUserModel extends Model
{
    public const ROLE_ADMIN      = 'admin_principal';
    public const ROLE_SUPERVISOR = 'supervisor';

    public const PERM_READ = 'read';
    public const PERM_EDIT = 'edit';

    protected $table            = 'industry_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_id', 'user_id', 'role', 'permission'];

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
     * ¿El usuario puede editar el espacio y sus placas?
     * El admin siempre puede; los supervisores solo con permission = edit.
     */
    public function canEdit(int $userId, int $industryId): bool
    {
        $row = $this->select('role, permission')
            ->where('user_id', $userId)
            ->where('industry_id', $industryId)
            ->first();

        if ($row === null) {
            return false;
        }

        return $row['role'] === self::ROLE_ADMIN || ($row['permission'] ?? self::PERM_READ) === self::PERM_EDIT;
    }

    public function membershipFor(int $userId, int $industryId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('industry_id', $industryId)
            ->first();
    }

    public function canSeeAllDevices(int $userId, int $industryId): bool
    {
        return $this->canEdit($userId, $industryId);
    }

    public function canManageDeviceAccess(int $userId, int $industryId): bool
    {
        return $this->canEdit($userId, $industryId);
    }

    /**
     * Industrias a las que pertenece el usuario, con su rol.
     */
    public function industriesForUser(int $userId): array
    {
        return $this->select('industries.*, industry_users.role, industry_users.permission')
            ->join('industries', 'industries.id = industry_users.industry_id')
            ->where('industry_users.user_id', $userId)
            ->orderBy('industries.company_name', 'ASC')
            ->findAll();
    }

    /**
     * Usuarios que tienen acceso a una industria.
     */
    public function membersForIndustry(int $industryId): array
    {
        // foto_perfil viaja para los avatares de los encargados del calendario.
        return $this->select('industry_users.*, users.nombre, users.apellido, users.email, users.foto_perfil')
            ->join('users', 'users.id = industry_users.user_id')
            ->where('industry_users.industry_id', $industryId)
            ->orderBy('industry_users.role', 'ASC')
            ->orderBy('users.nombre', 'ASC')
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
