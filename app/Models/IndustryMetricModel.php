<?php

namespace App\Models;

use CodeIgniter\Model;

class IndustryMetricModel extends Model
{
    protected $table            = 'industry_metrics';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['industry_id', 'sensor_type_id', 'suggested_chart'];

    protected bool $allowEmptyInserts = false;

    /**
     * Métricas elegidas por una industria, con datos del tipo de sensor.
     */
    public function metricsForIndustry(int $industryId): array
    {
        return $this->select('industry_metrics.*, sensor_types.code, sensor_types.name, sensor_types.unit')
            ->join('sensor_types', 'sensor_types.id = industry_metrics.sensor_type_id')
            ->where('industry_metrics.industry_id', $industryId)
            ->orderBy('sensor_types.name', 'ASC')
            ->findAll();
    }
}
