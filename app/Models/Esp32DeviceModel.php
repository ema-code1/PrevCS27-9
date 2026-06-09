<?php

namespace App\Models;

use CodeIgniter\Model;

class Esp32DeviceModel extends Model
{
    protected $table            = 'esp32_devices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_id', 'name', 'mac_address', 'sector', 'token_hash', 'token_secret', 'is_active', 'status', 'last_seen_at'];

    protected bool $allowEmptyInserts = false;

    /**
     * Normaliza una MAC a mayusculas con separador ':' (o null si es invalida).
     * Acepta entradas con ':', '-' o sin separador.
     */
    public static function normalizeMac(string $mac): ?string
    {
        $clean = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));

        if (strlen($clean) !== 12) {
            return null;
        }

        return implode(':', str_split($clean, 2));
    }

    /**
     * Token secreto en claro que la placa enviara en cada lectura.
     * Solo se muestra UNA vez (al crear la ESP); en la DB guardamos su hash.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16)); // 32 chars hex
    }

    /**
     * Hash que se persiste (la DB nunca contiene el token en claro).
     */
    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken); // 64 chars hex
    }

    /**
     * Guarda el token reversible para poder verlo luego con contraseña del espacio.
     * La API sigue validando solo token_hash.
     */
    public static function protectToken(string $plainToken): string
    {
        $key    = self::tokenCipherKey();
        $iv     = random_bytes(16);
        $cipher = openssl_encrypt($plainToken, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($cipher === false) {
            throw new \RuntimeException('No se pudo proteger el token.');
        }

        $mac = hash_hmac('sha256', $iv . $cipher, $key, true);

        return base64_encode($iv . $mac . $cipher);
    }

    public static function revealToken(?string $protectedToken): ?string
    {
        if ($protectedToken === null || $protectedToken === '') {
            return null;
        }

        $raw = base64_decode($protectedToken, true);
        if ($raw === false || strlen($raw) <= 48) {
            return null;
        }

        $iv     = substr($raw, 0, 16);
        $mac    = substr($raw, 16, 32);
        $cipher = substr($raw, 48);
        $key    = self::tokenCipherKey();

        $expected = hash_hmac('sha256', $iv . $cipher, $key, true);
        if (! hash_equals($expected, $mac)) {
            return null;
        }

        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain === false ? null : $plain;
    }

    private static function tokenCipherKey(): string
    {
        $seed = (string) env('encryption.key');

        if ($seed === '') {
            $app  = config('App');
            $seed = (string) ($app->baseURL ?? '') . '|' . FCPATH;
        }

        return hash('sha256', $seed, true);
    }

    /**
     * Compara, en tiempo constante, el token recibido contra el hash guardado.
     */
    public static function tokenMatches(array $device, string $plainToken): bool
    {
        $stored = (string) ($device['token_hash'] ?? '');

        return $stored !== '' && hash_equals($stored, self::hashToken($plainToken));
    }

    public function findByMac(string $mac): ?array
    {
        $normalized = self::normalizeMac($mac);

        if ($normalized === null) {
            return null;
        }

        return $this->where('mac_address', $normalized)->first();
    }

    public function macExists(string $normalizedMac): bool
    {
        return $this->where('mac_address', $normalizedMac)->countAllResults() > 0;
    }

    /**
     * Dispositivos visibles para un usuario (todas sus industrias).
     */
    public function devicesForUser(int $userId): array
    {
        return $this->select('esp32_devices.*, industries.company_name, industries.space_name, industries.industry_uid')
            ->join('industries', 'industries.id = esp32_devices.industry_id')
            ->join('industry_users', 'industry_users.industry_id = esp32_devices.industry_id')
            ->where('industry_users.user_id', $userId)
            ->orderBy('esp32_devices.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Dispositivos de una industria/espacio (para la vista Registros IoT).
     */
    public function devicesForIndustry(int $industryId): array
    {
        return $this->where('industry_id', $industryId)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Marca la placa como vista recien (heartbeat de la API IoT).
     */
    public function touchLastSeen(int $deviceId): void
    {
        $this->builder()
            ->where('id', $deviceId)
            ->update([
                'status'       => 'online',
                'last_seen_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
