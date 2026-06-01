<?php

namespace App\Models;

use CodeIgniter\Model;

class SensorTypeModel extends Model
{
    protected $table            = 'sensor_types';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['code', 'name', 'unit', 'default_chart'];

    public function findByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Resuelve un tipo de sensor por code o por nombre (ej: "Temperatura"),
     * util para la ingesta IoT donde el JSON puede traer "sensor": "Temperatura".
     */
    public function resolve(string $codeOrName): ?array
    {
        $needle = trim($codeOrName);

        $byCode = $this->where('code', strtolower($needle))->first();
        if ($byCode !== null) {
            return $byCode;
        }

        return $this->where('LOWER(name)', mb_strtolower($needle))->first();
    }
}
