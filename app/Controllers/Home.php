<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\DeviceAccessModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;

class Home extends BaseController
{
    public function index(): string
    {
        $userId = (int) session('user_id');

        $industryModel = new IndustryUserModel();
        $industries = $industryModel->industriesForUser($userId);
        $industryIds = array_map('intval', array_column($industries, 'id'));

        $deviceModel = new Esp32DeviceModel();
        $accessModel = new DeviceAccessModel();
        $devices = [];
        $sensors = [];
        foreach ($industryIds as $industryId) {
            $spaceDevices = $deviceModel->devicesForIndustry($industryId);
            if (! $industryModel->canSeeAllDevices($userId, $industryId)) {
                $membership = $industryModel->membershipFor($userId, $industryId);
                $allowedIds = $membership !== null
                    ? $accessModel->allowedDeviceIdsForMembership((int) $membership['id'])
                    : [];
                $spaceDevices = array_values(array_filter(
                    $spaceDevices,
                    static fn ($device) => in_array((int) $device['id'], $allowedIds, true)
                ));
            }

            $devices = array_merge($devices, $spaceDevices);
            $visibleDeviceIds = array_map('intval', array_column($spaceDevices, 'id'));
            $spaceSensors = (new Esp32SensorModel())->sensorsForIndustry($industryId);
            $sensors = array_merge($sensors, array_values(array_filter(
                $spaceSensors,
                static fn ($sensor) => in_array((int) $sensor['esp32_device_id'], $visibleDeviceIds, true)
            )));
        }
        $deviceIds = array_map('intval', array_column($devices, 'id'));

        $readingModel = new SensorReadingModel();
        $latestSensors = [];
        $lastReadingAt = null;
        foreach ($sensors as $sensor) {
            $latest = $readingModel->latestForSensor((int) $sensor['id']);
            $sensor['latest_value'] = $latest['value'] ?? null;
            $sensor['latest_at'] = $latest['measured_at'] ?? null;
            if (! empty($sensor['latest_at']) && ($lastReadingAt === null || strtotime($sensor['latest_at']) > strtotime($lastReadingAt))) {
                $lastReadingAt = $sensor['latest_at'];
            }
            $latestSensors[] = $sensor;
        }

        usort($latestSensors, static function ($a, $b) {
            return strtotime((string) ($b['latest_at'] ?? '1970-01-01')) <=> strtotime((string) ($a['latest_at'] ?? '1970-01-01'));
        });

        $since24h = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $readings24h = $deviceIds === [] ? 0 : $this->countReadingsSince($deviceIds, $since24h);
        $totalReadings = $deviceIds === [] ? 0 : $this->countReadingsSince($deviceIds, '1970-01-01 00:00:00');
        $activeDevices = count(array_filter($devices, static fn ($device) => ! empty($device['last_seen_at']) && strtotime((string) $device['last_seen_at']) >= strtotime('-24 hours')));

        return view('principal', [
            'title'      => 'Principal | Preventive Control System',
            'activeMenu' => 'panel',
            'dashboard'  => [
                'industries'     => $industries,
                'devices'        => $devices,
                'sensors'        => $latestSensors,
                'spaces_count'   => count($industries),
                'devices_count'  => count($devices),
                'active_devices' => $activeDevices,
                'sensors_count'  => count($sensors),
                'readings_24h'   => $readings24h,
                'total_readings' => $totalReadings,
                'last_reading_at'=> $lastReadingAt,
                'state'          => $this->resolveState(count($industries), count($devices), $totalReadings),
            ],
        ]);
    }

    private function resolveState(int $spacesCount, int $devicesCount, int $totalReadings): string
    {
        if ($spacesCount === 0) {
            return 'no_space';
        }
        if ($devicesCount === 0) {
            return 'no_device';
        }
        if ($totalReadings === 0) {
            return 'no_readings';
        }

        return 'active';
    }

    private function countReadingsSince(array $deviceIds, string $since): int
    {
        return (int) db_connect()
            ->table('sensor_readings')
            ->join('esp32_sensors', 'esp32_sensors.id = sensor_readings.esp32_sensor_id')
            ->whereIn('esp32_sensors.esp32_device_id', $deviceIds)
            ->where('sensor_readings.measured_at >=', $since)
            ->countAllResults();
    }
}
