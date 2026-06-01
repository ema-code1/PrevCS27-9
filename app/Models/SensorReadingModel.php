<?php

namespace App\Models;

use CodeIgniter\Model;

class SensorReadingModel extends Model
{
    protected $table            = 'sensor_readings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['esp32_sensor_id', 'value', 'measured_at'];

    protected bool $allowEmptyInserts = false;

    /**
     * Ultima lectura registrada para un sensor.
     */
    public function latestForSensor(int $sensorId): ?array
    {
        return $this->where('esp32_sensor_id', $sensorId)
            ->orderBy('measured_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Serie temporal (ASC) para graficar, limitada a $limit puntos.
     */
    public function seriesForSensor(int $sensorId, int $limit = 200): array
    {
        $rows = $this->select('value, measured_at')
            ->where('esp32_sensor_id', $sensorId)
            ->orderBy('measured_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->findAll();

        // Devolvemos en orden cronologico ascendente para el grafico
        return array_reverse($rows);
    }
}
