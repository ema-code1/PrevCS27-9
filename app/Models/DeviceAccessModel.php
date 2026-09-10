<?php

namespace App\Models;

use CodeIgniter\Model;

class DeviceAccessModel extends Model
{
    protected $table            = 'esp32_device_access';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_user_id', 'esp32_device_id'];

    protected bool $allowEmptyInserts = false;
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function allowedDeviceIdsForMembership(int $membershipId): array
    {
        $rows = $this->select('esp32_device_id')
            ->where('industry_user_id', $membershipId)
            ->findAll();

        return array_map('intval', array_column($rows, 'esp32_device_id'));
    }

    public function accessMapForMemberships(array $membershipIds): array
    {
        $membershipIds = array_values(array_unique(array_map('intval', $membershipIds)));
        if ($membershipIds === []) {
            return [];
        }

        $rows = $this->select('industry_user_id, esp32_device_id')
            ->whereIn('industry_user_id', $membershipIds)
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $membershipId = (int) $row['industry_user_id'];
            $deviceId     = (int) $row['esp32_device_id'];
            $map[$membershipId][$deviceId] = true;
        }

        return $map;
    }

    public function setAccess(int $membershipId, int $deviceId, bool $allowed): bool
    {
        $existing = $this->where('industry_user_id', $membershipId)
            ->where('esp32_device_id', $deviceId)
            ->first();

        if ($allowed) {
            if ($existing !== null) {
                return true;
            }

            return $this->insert([
                'industry_user_id' => $membershipId,
                'esp32_device_id'  => $deviceId,
            ]) !== false;
        }

        if ($existing === null) {
            return true;
        }

        return (bool) $this->delete((int) $existing['id']);
    }
}
