<?php

namespace App\Controllers;

use App\Models\IotReadingRepository;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

class IotReadings extends BaseController
{
    private const ALLOWED_METRICS = ['vibration', 'rpm', 'temperature'];
    private const ALLOWED_RANGES = ['day', 'week', 'month'];

    public function index()
    {
        $metric = (string) $this->request->getGet('metric');
        $range = (string) $this->request->getGet('range');
        $start = (string) $this->request->getGet('start');

        if (!in_array($metric, self::ALLOWED_METRICS, true) || !in_array($range, self::ALLOWED_RANGES, true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => 'Parametros invalidos.',
            ]);
        }

        try {
            [$startDate, $endDate] = $this->resolveRange($range, $start);
            $series = (new IotReadingRepository())->getSeries($metric, $startDate, $endDate, $range);
            $values = array_values(array_filter(array_column($series['points'], 'value'), static fn ($value) => $value !== null));

            return $this->response->setJSON([
                'metric' => $metric,
                'range' => $range,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'source' => $series['source'],
                'latest' => $values !== [] ? end($values) : null,
                'points' => $series['points'],
            ]);
        } catch (Throwable) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => 'No se pudo resolver el rango solicitado.',
            ]);
        }
    }

    private function resolveRange(string $range, string $start): array
    {
        $timezone = new DateTimeZone('America/Buenos_Aires');
        $startDate = new DateTimeImmutable($start !== '' ? $start : 'today', $timezone);
        $startDate = $startDate->setTime(0, 0, 0);

        $endDate = match ($range) {
            'week' => $startDate->modify('+6 days')->setTime(23, 59, 59),
            'month' => $startDate->modify('+29 days')->setTime(23, 59, 59),
            default => $startDate->setTime(23, 59, 59),
        };

        return [$startDate, $endDate];
    }
}
