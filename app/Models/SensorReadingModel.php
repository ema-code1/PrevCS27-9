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

    /**
     * Total de lecturas registradas para un conjunto de sensores.
     * Usado por el dashboard para decidir el estado (vacio/onboarding/activo).
     */
    public function totalForSensors(array $sensorIds): int
    {
        if ($sensorIds === []) {
            return 0;
        }

        return $this->whereIn('esp32_sensor_id', $sensorIds)->countAllResults();
    }

    /**
     * Cuenta lecturas de un conjunto de sensores desde una fecha (inclusive).
     */
    public function countForSensorsSince(array $sensorIds, string $since): int
    {
        if ($sensorIds === []) {
            return 0;
        }

        return $this->whereIn('esp32_sensor_id', $sensorIds)
            ->where('measured_at >=', $since)
            ->countAllResults();
    }

    /**
     * Timestamp de la lectura mas reciente entre varios sensores (o null).
     */
    public function latestAtForSensors(array $sensorIds): ?string
    {
        if ($sensorIds === []) {
            return null;
        }

        $row = $this->select('measured_at')
            ->whereIn('esp32_sensor_id', $sensorIds)
            ->orderBy('measured_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        return $row['measured_at'] ?? null;
    }

    /**
     * Sensor con mas lecturas (desde $since; si no hay, en todo el historial).
     * El dashboard lo usa para pintar la tendencia del sensor mas activo.
     */
    public function mostActiveSensorId(array $sensorIds, string $since): ?int
    {
        if ($sensorIds === []) {
            return null;
        }

        $row = $this->select('esp32_sensor_id, COUNT(*) AS c')
            ->whereIn('esp32_sensor_id', $sensorIds)
            ->where('measured_at >=', $since)
            ->groupBy('esp32_sensor_id')
            ->orderBy('c', 'DESC')
            ->first();

        if ($row === null) {
            $row = $this->select('esp32_sensor_id, COUNT(*) AS c')
                ->whereIn('esp32_sensor_id', $sensorIds)
                ->groupBy('esp32_sensor_id')
                ->orderBy('c', 'DESC')
                ->first();
        }

        return $row !== null ? (int) $row['esp32_sensor_id'] : null;
    }
}
