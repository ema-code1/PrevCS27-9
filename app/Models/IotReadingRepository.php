<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Throwable;

class IotReadingRepository
{
    private BaseConnection $db;

    /**
     * Ajusta estos candidatos cuando cambie el esquema final de la ESP32.
     * Soporta tablas "wide" con una columna por metrica y tablas "long" con
     * columnas metric/value.
     */
    private array $schema = [
        'wideTables' => [
            'iot_readings',
            'esp32_readings',
            'sensor_readings',
            'registros_iot',
            'lecturas_iot',
        ],
        'longTables' => [
            'iot_measurements',
            'esp32_measurements',
            'sensor_measurements',
            'mediciones_iot',
        ],
        'timestampColumns' => [
            'measured_at',
            'recorded_at',
            'created_at',
            'fecha_hora',
            'timestamp',
        ],
        'longMetricColumns' => [
            'metric',
            'sensor',
            'type',
            'variable',
        ],
        'longValueColumns' => [
            'value',
            'reading',
            'valor',
            'measurement',
        ],
        'metrics' => [
            'vibration' => [
                'columns' => ['vibration', 'vibracion', 'vibration_value', 'valor_vibracion'],
                'aliases' => ['vibration', 'vibracion', 'vibración'],
            ],
            'rpm' => [
                'columns' => ['rpm', 'revolutions', 'revoluciones', 'rpm_value', 'valor_rpm'],
                'aliases' => ['rpm', 'revolutions', 'revoluciones'],
            ],
            'temperature' => [
                'columns' => ['temperature', 'temperatura', 'temperature_value', 'valor_temperatura'],
                'aliases' => ['temperature', 'temperatura'],
            ],
        ],
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function getSeries(string $metric, DateTimeImmutable $start, DateTimeImmutable $end, string $range): array
    {
        $points = $this->readFromDatabase($metric, $start, $end, $range);

        if ($points !== null) {
            return [
                'source' => 'database',
                'points' => $points,
            ];
        }

        return [
            'source' => 'demo',
            'points' => $this->buildDemoPoints($metric, $start, $end, $range),
        ];
    }

    private function readFromDatabase(string $metric, DateTimeImmutable $start, DateTimeImmutable $end, string $range): ?array
    {
        try {
            $wideSource = $this->resolveWideSource($metric);

            if ($wideSource !== null) {
                $rows = $this->db->table($wideSource['table'])
                    ->select($wideSource['timestamp'] . ' AS measured_at, ' . $wideSource['value'] . ' AS value')
                    ->where($wideSource['timestamp'] . ' >=', $start->format('Y-m-d H:i:s'))
                    ->where($wideSource['timestamp'] . ' <=', $end->format('Y-m-d H:i:s'))
                    ->orderBy($wideSource['timestamp'], 'ASC')
                    ->limit(5000)
                    ->get()
                    ->getResultArray();

                return $this->bucketRows($rows, $start, $end, $range);
            }

            $longSource = $this->resolveLongSource();

            if ($longSource !== null) {
                $metricAliases = $this->schema['metrics'][$metric]['aliases'];
                $rows = $this->db->table($longSource['table'])
                    ->select($longSource['timestamp'] . ' AS measured_at, ' . $longSource['value'] . ' AS value')
                    ->where($longSource['timestamp'] . ' >=', $start->format('Y-m-d H:i:s'))
                    ->where($longSource['timestamp'] . ' <=', $end->format('Y-m-d H:i:s'))
                    ->whereIn($longSource['metric'], $metricAliases)
                    ->orderBy($longSource['timestamp'], 'ASC')
                    ->limit(5000)
                    ->get()
                    ->getResultArray();

                return $this->bucketRows($rows, $start, $end, $range);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function resolveWideSource(string $metric): ?array
    {
        foreach ($this->schema['wideTables'] as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }

            $fields = $this->db->getFieldNames($table);
            $timestamp = $this->firstExisting($fields, $this->schema['timestampColumns']);
            $value = $this->firstExisting($fields, $this->schema['metrics'][$metric]['columns']);

            if ($timestamp !== null && $value !== null) {
                return [
                    'table' => $table,
                    'timestamp' => $timestamp,
                    'value' => $value,
                ];
            }
        }

        return null;
    }

    private function resolveLongSource(): ?array
    {
        foreach ($this->schema['longTables'] as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }

            $fields = $this->db->getFieldNames($table);
            $timestamp = $this->firstExisting($fields, $this->schema['timestampColumns']);
            $metric = $this->firstExisting($fields, $this->schema['longMetricColumns']);
            $value = $this->firstExisting($fields, $this->schema['longValueColumns']);

            if ($timestamp !== null && $metric !== null && $value !== null) {
                return [
                    'table' => $table,
                    'timestamp' => $timestamp,
                    'metric' => $metric,
                    'value' => $value,
                ];
            }
        }

        return null;
    }

    private function firstExisting(array $fields, array $candidates): ?string
    {
        $normalizedFields = array_change_key_case(array_flip($fields), CASE_LOWER);

        foreach ($candidates as $candidate) {
            $key = strtolower($candidate);

            if (array_key_exists($key, $normalizedFields)) {
                return $fields[$normalizedFields[$key]];
            }
        }

        return null;
    }

    private function bucketRows(array $rows, DateTimeImmutable $start, DateTimeImmutable $end, string $range): array
    {
        $buckets = $this->emptyBuckets($start, $end, $range);

        foreach ($rows as $row) {
            if (!isset($row['measured_at'], $row['value']) || !is_numeric($row['value'])) {
                continue;
            }

            $bucketKey = $this->bucketKey(new DateTimeImmutable((string) $row['measured_at']), $range);

            if (!isset($buckets[$bucketKey])) {
                continue;
            }

            $buckets[$bucketKey]['sum'] += (float) $row['value'];
            $buckets[$bucketKey]['count']++;
        }

        return array_values(array_map(static function (array $bucket): array {
            return [
                'at' => $bucket['at'],
                'label' => $bucket['label'],
                'value' => $bucket['count'] > 0 ? round($bucket['sum'] / $bucket['count'], 2) : null,
            ];
        }, $buckets));
    }

    private function emptyBuckets(DateTimeImmutable $start, DateTimeImmutable $end, string $range): array
    {
        $buckets = [];

        if ($range === 'day') {
            for ($hour = 0; $hour < 24; $hour++) {
                $at = $start->setTime($hour, 0);
                $buckets[$at->format('Y-m-d H:00')] = [
                    'at' => $at->format('Y-m-d H:i:s'),
                    'label' => $at->format('H:00'),
                    'sum' => 0,
                    'count' => 0,
                ];
            }

            return $buckets;
        }

        $period = new DatePeriod($start->setTime(0, 0), new DateInterval('P1D'), $end->setTime(0, 0)->modify('+1 day'));

        foreach ($period as $date) {
            $buckets[$date->format('Y-m-d')] = [
                'at' => $date->format('Y-m-d H:i:s'),
                'label' => $date->format('d/m'),
                'sum' => 0,
                'count' => 0,
            ];
        }

        return $buckets;
    }

    private function bucketKey(DateTimeImmutable $date, string $range): string
    {
        if ($range === 'day') {
            return $date->format('Y-m-d H:00');
        }

        return $date->format('Y-m-d');
    }

    private function buildDemoPoints(string $metric, DateTimeImmutable $start, DateTimeImmutable $end, string $range): array
    {
        $buckets = $this->emptyBuckets($start, $end, $range);
        $index = 0;

        return array_values(array_map(function (array $bucket) use ($metric, &$index): array {
            $index++;
            $base = match ($metric) {
                'rpm' => 1420,
                'temperature' => 41,
                default => 4.4,
            };
            $wave = sin($index / 2.2);
            $drift = cos($index / 4.5);
            $value = match ($metric) {
                'rpm' => round($base + ($wave * 55) + ($drift * 22), 0),
                'temperature' => round($base + ($wave * 3.4) + ($drift * 1.3), 1),
                default => round($base + ($wave * 0.45) + ($drift * 0.18), 2),
            };

            return [
                'at' => $bucket['at'],
                'label' => $bucket['label'],
                'value' => $value,
            ];
        }, $buckets));
    }
}
