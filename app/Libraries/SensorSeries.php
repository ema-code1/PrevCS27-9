<?php

namespace App\Libraries;

use App\Models\SensorReadingModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Respuesta de la serie de un sensor, compartida por las dos APIs de lecturas
 * (la del espacio y la de la placa): cada una valida el acceso a su manera y
 * después delega acá el armado.
 *
 *  - JSON (por defecto): puntos para el gráfico + estado de alerta del sensor.
 *  - ?format=csv: el historial completo del rango filtrado, para descargar.
 */
class SensorSeries
{
    /** Puntos que dibuja el gráfico (los más recientes del rango). */
    private const CHART_LIMIT = 500;

    /** Techo de filas del CSV: un año de lecturas cada 60 s entra holgado. */
    private const CSV_LIMIT = 600000;

    /**
     * @param array $sensor Fila de esp32_sensors (con alert_min/max/state).
     */
    public function respond(IncomingRequest $request, ResponseInterface $response, array $sensor, array $type = []): ResponseInterface
    {
        [$from, $to] = $this->rangeBounds($request);
        $sensorId    = (int) $sensor['id'];
        $readings    = new SensorReadingModel();

        if ($request->getGet('format') === 'csv') {
            return $this->csv($response, $sensor, $type, $readings->seriesForSensor($sensorId, self::CSV_LIMIT, $from, $to), $from, $to);
        }

        $min = $sensor['alert_min'] ?? null;
        $max = $sensor['alert_max'] ?? null;

        $points = array_map(static function ($row) use ($min, $max) {
            $value = (float) $row['value'];

            return [
                'at'    => $row['measured_at'],
                'label' => date('d/m H:i', strtotime((string) $row['measured_at'])),
                'value' => $value,
                'state' => SensorAlerts::evaluate($value, $min, $max),
            ];
        }, $readings->seriesForSensor($sensorId, self::CHART_LIMIT, $from, $to));

        return $response->setJSON([
            'sensor'    => $sensorId,
            'from'      => $from,
            'to'        => $to,
            'latest'    => $points !== [] ? end($points)['value'] : null,
            'points'    => $points,
            'alert_min' => $min === null ? null : (float) $min,
            'alert_max' => $max === null ? null : (float) $max,
            // Estado con la última lectura real (no la del rango filtrado).
            'state'     => $sensor['alert_state'] ?? null,
        ]);
    }

    private function csv(ResponseInterface $response, array $sensor, array $type, array $rows, ?string $from, ?string $to): ResponseInterface
    {
        $unit = (string) ($type['unit'] ?? '');
        $name = (string) (($sensor['label'] ?? '') ?: ($type['name'] ?? 'sensor'));

        $out = fopen('php://temp', 'r+');
        // BOM: Excel abre el archivo en UTF-8 y respeta tildes y "°C".
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Fecha y hora', 'Sensor', 'Valor', 'Unidad', 'Estado'], ';');

        foreach ($rows as $row) {
            $value = (float) $row['value'];
            fputcsv($out, [
                $row['measured_at'],
                $name,
                str_replace('.', ',', SensorAlerts::num($value)),
                $unit,
                SensorAlerts::label(SensorAlerts::evaluate($value, $sensor['alert_min'] ?? null, $sensor['alert_max'] ?? null)),
            ], ';');
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: 'sensor')), '-');
        $span = ($from !== null ? date('Ymd', strtotime($from)) : 'inicio') . '-' . ($to !== null ? date('Ymd', strtotime($to)) : date('Ymd'));

        return $response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="historial-' . ($slug ?: 'sensor') . '-' . $span . '.csv"')
            ->setBody($csv);
    }

    /**
     * Lee from/to de la query y los normaliza a 'Y-m-d H:i:s' (o null).
     * El date range selector envía, p.ej. from=2026-03-09 00:00:00.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function rangeBounds(IncomingRequest $request): array
    {
        $parse = static function ($value): ?string {
            $value = is_string($value) ? trim($value) : '';
            if ($value === '') {
                return null;
            }
            $ts = strtotime($value);

            return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        };

        return [$parse($request->getGet('from')), $parse($request->getGet('to'))];
    }
}
