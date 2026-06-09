<?php

namespace App\Libraries;

use App\Models\VerificationCodeModel;

class VerificationCodeService
{
    public const PURPOSE_EMAIL_VERIFICATION = 'email_verification';
    public const PURPOSE_TWO_FACTOR_LOGIN = 'two_factor_login';

    public const STATUS_VALID = 'valid';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_USED = 'used';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_MISSING = 'missing';

    public const EXPIRATION_SECONDS = 300;
    public const RESEND_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    private VerificationCodeModel $codes;

    public function __construct(?VerificationCodeModel $codes = null)
    {
        $this->codes = $codes ?? new VerificationCodeModel();
    }

    public function issue(int $userId, string $purpose): array
    {
        $existing = $this->find($userId, $purpose);
        $retryAfter = $this->retryAfterFromRow($existing);

        if ($existing !== null && empty($existing['used_at']) && $retryAfter > 0) {
            return [
                'sent'        => false,
                'cooldown'    => true,
                'retry_after' => $retryAfter,
                'code'        => null,
            ];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = date('Y-m-d H:i:s');
        $data = [
            'user_id'    => $userId,
            'purpose'    => $purpose,
            'code_hash'  => password_hash($code, PASSWORD_DEFAULT),
            'attempts'   => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + self::EXPIRATION_SECONDS),
            'used_at'    => null,
            'created_at' => $now,
        ];

        if ($existing === null) {
            $this->codes->insert($data);
        } else {
            $this->codes->builder()
                ->where('id', $existing['id'])
                ->update($data);
        }

        return [
            'sent'        => true,
            'cooldown'    => false,
            'retry_after' => self::RESEND_SECONDS,
            'code'        => $code,
        ];
    }

    public function verify(int $userId, string $purpose, string $code): array
    {
        $row = $this->find($userId, $purpose);

        if ($row === null) {
            return ['status' => self::STATUS_MISSING, 'attempts_left' => self::MAX_ATTEMPTS];
        }

        if (! empty($row['used_at'])) {
            return ['status' => self::STATUS_USED, 'attempts_left' => 0];
        }

        if (time() > strtotime((string) $row['expires_at'])) {
            return ['status' => self::STATUS_EXPIRED, 'attempts_left' => 0];
        }

        $attempts = (int) $row['attempts'];
        if ($attempts >= self::MAX_ATTEMPTS) {
            return ['status' => self::STATUS_LOCKED, 'attempts_left' => 0];
        }

        $normalizedCode = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($normalizedCode) === 6 && password_verify($normalizedCode, (string) $row['code_hash'])) {
            $this->codes->builder()
                ->where('id', $row['id'])
                ->update(['used_at' => date('Y-m-d H:i:s')]);

            return ['status' => self::STATUS_VALID, 'attempts_left' => self::MAX_ATTEMPTS - $attempts];
        }

        $attempts++;
        $this->codes->builder()
            ->where('id', $row['id'])
            ->update(['attempts' => $attempts]);

        return [
            'status'        => $attempts >= self::MAX_ATTEMPTS ? self::STATUS_LOCKED : self::STATUS_INVALID,
            'attempts_left' => max(0, self::MAX_ATTEMPTS - $attempts),
        ];
    }

    public function retryAfter(int $userId, string $purpose): int
    {
        return $this->retryAfterFromRow($this->find($userId, $purpose));
    }

    public function discard(int $userId, string $purpose): void
    {
        $this->codes
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->delete();
    }

    private function find(int $userId, string $purpose): ?array
    {
        return $this->codes
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->first();
    }

    private function retryAfterFromRow(?array $row): int
    {
        if ($row === null || ! empty($row['used_at'])) {
            return 0;
        }

        $createdAt = strtotime((string) $row['created_at']);
        if ($createdAt === false) {
            return 0;
        }

        return max(0, self::RESEND_SECONDS - (time() - $createdAt));
    }
}
