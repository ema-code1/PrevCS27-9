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
    protected $allowedFields    = ['esp32_device_id', 'sensor_type_id', 'label', 'alert_min', 'alert_max', 'alert_state'];

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

    /**
     * Un sensor puntual de una placa. Devuelve null si el id existe pero es de
     * otra placa: así el modal de configuración no puede tocar sensores ajenos.
     */
    public function findForDevice(int $sensorId, int $deviceId): ?array
    {
        return $this->where('id', $sensorId)
            ->where('esp32_device_id', $deviceId)
            ->first();
    }

    /**
     * Todos los sensores de una industria/espacio (de todas sus placas),
     * enriquecidos con el tipo y el nombre de la placa. Para Registros IoT.
     */
    public function sensorsForIndustry(int $industryId): array
    {
        return $this->select('esp32_sensors.*, esp32_devices.name AS device_name, esp32_devices.mac_address, sensor_types.code, sensor_types.name, sensor_types.unit, sensor_types.default_chart')
            ->join('esp32_devices', 'esp32_devices.id = esp32_sensors.esp32_device_id')
            ->join('sensor_types', 'sensor_types.id = esp32_sensors.sensor_type_id')
            ->where('esp32_devices.industry_id', $industryId)
            ->orderBy('esp32_devices.name', 'ASC')
            ->orderBy('sensor_types.name', 'ASC')
            ->findAll();
    }

    /**
     * ¿El sensor pertenece a alguna placa de esta industria? (scope de la API).
     */
    public function belongsToIndustry(int $sensorId, int $industryId): bool
    {
        return $this->join('esp32_devices', 'esp32_devices.id = esp32_sensors.esp32_device_id')
            ->where('esp32_sensors.id', $sensorId)
            ->where('esp32_devices.industry_id', $industryId)
            ->countAllResults() > 0;
    }
}
