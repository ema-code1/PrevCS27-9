<?php

namespace App\Models;

use CodeIgniter\Model;

class SpaceActionTokenModel extends Model
{
    public const PURPOSE_PASSWORD_RESET = 'space_password_reset';
    public const PURPOSE_DELETE = 'space_delete';
    public const TTL_SECONDS = 15 * 60;

    protected $table            = 'space_action_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'industry_id',
        'user_id',
        'purpose',
        'token_hash',
        'created_at',
        'expires_at',
        'used_at',
    ];

    protected bool $allowEmptyInserts = false;

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function issue(int $industryId, int $userId, string $purpose): ?string
    {
        $token = bin2hex(random_bytes(32));
        $now   = time();

        $this->where('industry_id', $industryId)
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->where('used_at', null)
            ->delete();

        $ok = $this->insert([
            'industry_id' => $industryId,
            'user_id'     => $userId,
            'purpose'     => $purpose,
            'token_hash'  => self::hashToken($token),
            'created_at'  => date('Y-m-d H:i:s', $now),
            'expires_at'  => date('Y-m-d H:i:s', $now + self::TTL_SECONDS),
        ]);

        return $ok ? $token : null;
    }

    public function findPending(string $token, string $purpose): ?array
    {
        if ($token === '') {
            return null;
        }

        $row = $this->where('token_hash', self::hashToken($token))
            ->where('purpose', $purpose)
            ->where('used_at', null)
            ->first();

        if ($row === null || strtotime((string) ($row['expires_at'] ?? '')) < time()) {
            return null;
        }

        return $row;
    }

    public function markUsed(int $id): void
    {
        $this->update($id, ['used_at' => date('Y-m-d H:i:s')]);
    }
}
