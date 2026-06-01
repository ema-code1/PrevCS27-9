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
    protected $allowedFields    = ['industry_id', 'name', 'mac_address', 'sector', 'device_token', 'status', 'last_seen_at'];

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
     * Token secreto que la placa enviara en cada lectura.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16)); // 32 chars hex
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
