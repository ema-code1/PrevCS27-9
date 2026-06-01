<?php

namespace App\Models;

use CodeIgniter\Model;

class Esp32SensorModel extends Model
{
    protected $table            = 'esp32_sensors';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['esp32_device_id', 'sensor_type_id', 'label'];

    protected bool $allowEmptyInserts = false;

    /**
     * Sensores de una placa, enriquecidos con el tipo (code/name/unit/chart).
     */
    public function sensorsForDevice(int $deviceId): array
    {
        return $this->select('esp32_sensors.*, sensor_types.code, sensor_types.name, sensor_types.unit, sensor_types.default_chart')
            ->join('sensor_types', 'sensor_types.id = esp32_sensors.sensor_type_id')
            ->where('esp32_sensors.esp32_device_id', $deviceId)
            ->orderBy('sensor_types.name', 'ASC')
            ->findAll();
    }

    /**
     * Encuentra el sensor de una placa para un tipo dado (ingesta IoT).
     */
    public function findForDeviceAndType(int $deviceId, int $sensorTypeId): ?array
    {
        return $this->where('esp32_device_id', $deviceId)
            ->where('sensor_type_id', $sensorTypeId)
            ->first();
    }
}
