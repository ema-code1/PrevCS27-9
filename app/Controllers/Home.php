<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;

class Home extends BaseController
{
    public function index()
    {
        $userId            = (int) session('user_id');
        $industryUserModel = new IndustryUserModel();
        $industries        = $industryUserModel->industriesForUser($userId);

        // Cambio de espacio activo (?espacio=ID) → se persiste en sesion.
        $requested = (int) $this->request->getGet('espacio');
        if ($requested > 0 && $industryUserModel->userBelongsTo($userId, $requested)) {
            session()->set('active_industry_id', $requested);

            return redirect()->to(base_url('principal'));
        }

        return view('principal', [
            'title'      => 'Principal | Prevention Control System',
            'activeMenu' => 'panel',
            'industries' => $industries,
            'dashboard'  => $this->buildDashboard($userId, $industries),
        ]);
    }

    /**
     * Arma el payload real del dashboard a partir de la DB. Nada hardcoded:
     * KPIs, estado de onboarding, sensores y tendencia salen de las lecturas.
     */
    private function buildDashboard(int $userId, array $industries): array
    {
        $deviceModel  = new Esp32DeviceModel();
        $sensorModel  = new Esp32SensorModel();
        $readingModel = new SensorReadingModel();

        // ── Onboarding a nivel cuenta (a traves de TODOS los espacios) ──
        $allDevices  = $deviceModel->devicesForUser($userId);
        $allSensorIds = [];
        foreach ($industries as $ind) {
            foreach ($sensorModel->sensorsForIndustry((int) $ind['id']) as $s) {
                $allSensorIds[] = (int) $s['id'];
            }
        }
        $hasSpace   = $industries !== [];
        $hasDevice  = $allDevices !== [];
        $hasReading = $readingModel->totalForSensors($allSensorIds) > 0;

        $onboarding = [
            'space'    => $hasSpace,
            'device'   => $hasDevice,
            'reading'  => $hasReading,
            'complete' => $hasSpace && $hasDevice && $hasReading,
        ];

        // ── Espacio activo (sesion → primero del usuario como fallback) ──
        $activeId       = (int) session('active_industry_id');
        $activeIndustry = null;
        foreach ($industries as $ind) {
            if ((int) $ind['id'] === $activeId) {
                $activeIndustry = $ind;
                break;
            }
        }
        if ($activeIndustry === null && $industries !== []) {
            $activeIndustry = $industries[0];
            $activeId       = (int) $activeIndustry['id'];
            session()->set('active_industry_id', $activeId);
        }

        $dashboard = [
            'onboarding'      => $onboarding,
            'active_industry' => $activeIndustry,
            'space_count'     => count($industries),
            'state'           => 'empty',
            'kpis'            => [],
            'sensors'         => [],
            'activity'        => [],
            'trend'           => null,
            'total_readings'  => 0,
        ];

        if ($activeIndustry === null) {
            return $dashboard; // estado 'empty' → vista de bienvenida sin datos
        }

        $now       = time();
        $since24h  = date('Y-m-d H:i:s', $now - 86400);
        $since7d   = date('Y-m-d H:i:s', $now - 7 * 86400);

        $devices   = $deviceModel->devicesForIndustry($activeId);
        $sensors   = $sensorModel->sensorsForIndustry($activeId);
        $sensorIds = array_map(static fn ($s) => (int) $s['id'], $sensors);

        // Enriquecemos cada sensor con su ultima lectura
        foreach ($sensors as &$s) {
            $latest            = $readingModel->latestForSensor((int) $s['id']);
            $s['latest_value'] = $latest['value'] ?? null;
            $s['latest_at']    = $latest['measured_at'] ?? null;
        }
        unset($s);

        $totalReadings = $readingModel->totalForSensors($sensorIds);

        // Estado del espacio activo
        if ($devices === []) {
            $state = 'onboarding';
        } elseif ($totalReadings === 0) {
            $state = 'onboarding';
        } else {
            $state = 'active';
        }

        // ── KPIs honestas del dominio IoT ──
        $activeDevices = 0;
        foreach ($devices as $d) {
            $online = ($d['status'] ?? '') === 'online'
                || (! empty($d['last_seen_at']) && strtotime((string) $d['last_seen_at']) >= $now - 86400);
            if ($online) {
                $activeDevices++;
            }
        }

        $reporting = 0;
        foreach ($sensors as $s) {
            if (! empty($s['latest_at']) && strtotime((string) $s['latest_at']) >= $now - 86400) {
                $reporting++;
            }
        }

        $readings24 = $readingModel->countForSensorsSince($sensorIds, $since24h);
        $lastAt     = $readingModel->latestAtForSensors($sensorIds);

        $dashboard['kpis'] = [
            'devices_active'   => ['value' => $activeDevices, 'total' => count($devices)],
            'sensors_reporting' => ['value' => $reporting, 'total' => count($sensors)],
            'readings_24h'     => ['value' => $readings24],
            'last_reading'     => ['at' => $lastAt, 'human' => $this->humanizeSince($lastAt)],
            'space_count'      => ['value' => count($industries)],
        ];

        // ── Actividad reciente: ultima lectura por sensor, mas nuevas primero ──
        $activity = [];
        foreach ($sensors as $s) {
            if ($s['latest_at'] === null) {
                continue;
            }
            $activity[] = [
                'sensor' => $s['label'] ?: $s['name'],
                'device' => $s['device_name'],
                'value'  => $s['latest_value'],
                'unit'   => $s['unit'] ?? '',
                'at'     => $s['latest_at'],
                'human'  => $this->humanizeSince($s['latest_at']),
                'fresh'  => strtotime((string) $s['latest_at']) >= $now - 86400,
            ];
        }
        usort($activity, static fn ($a, $b) => strtotime($b['at']) <=> strtotime($a['at']));
        $dashboard['activity'] = array_slice($activity, 0, 6);

        // ── Tendencia: serie del sensor mas activo (7d) ──
        $trendSensorId = $readingModel->mostActiveSensorId($sensorIds, $since7d);
        if ($trendSensorId !== null) {
            $meta = null;
            foreach ($sensors as $s) {
                if ((int) $s['id'] === $trendSensorId) {
                    $meta = $s;
                    break;
                }
            }
            $series = $readingModel->seriesForSensor($trendSensorId, 100);
            $points = array_map(static fn ($r) => [
                'label' => date('d/m H:i', strtotime((string) $r['measured_at'])),
                'value' => (float) $r['value'],
            ], $series);

            $dashboard['trend'] = [
                'sensor' => $meta !== null ? ($meta['label'] ?: $meta['name']) : 'Sensor',
                'device' => $meta['device_name'] ?? '',
                'unit'   => $meta['unit'] ?? '',
                'points' => $points,
            ];
        }

        $dashboard['state']          = $state;
        $dashboard['sensors']        = $sensors;
        $dashboard['total_readings'] = $totalReadings;

        return $dashboard;
    }

    /**
     * Convierte un timestamp en texto relativo ("hace 12 min", "hace 2 h").
     */
    private function humanizeSince(?string $datetime): ?string
    {
        if ($datetime === null || $datetime === '') {
            return null;
        }

        $diff = time() - strtotime($datetime);
        if ($diff < 0) {
            $diff = 0;
        }

        if ($diff < 60) {
            return 'hace instantes';
        }
        if ($diff < 3600) {
            return 'hace ' . (int) floor($diff / 60) . ' min';
        }
        if ($diff < 86400) {
            return 'hace ' . (int) floor($diff / 3600) . ' h';
        }

        return 'hace ' . (int) floor($diff / 86400) . ' d';
    }
}
