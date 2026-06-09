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
     * Última lectura registrada para un sensor.
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
     *
     * Si se pasan $from / $to (formato 'Y-m-d H:i:s'), acota la serie a ese
     * rango de fechas — usado por el date range selector de los gráficos.
     */
    public function seriesForSensor(int $sensorId, int $limit = 200, ?string $from = null, ?string $to = null): array
    {
        $builder = $this->select('value, measured_at')
            ->where('esp32_sensor_id', $sensorId);

        if ($from !== null && $from !== '') {
            $builder->where('measured_at >=', $from);
        }
        if ($to !== null && $to !== '') {
            $builder->where('measured_at <=', $to);
        }

        $rows = $builder->orderBy('measured_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->findAll();

        // Devolvemos en orden cronologico ascendente para el grafico
        return array_reverse($rows);
    }
}
