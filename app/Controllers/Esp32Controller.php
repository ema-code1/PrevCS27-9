<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryMetricModel;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;
use App\Models\SensorTypeModel;
use Config\Database;

class Esp32Controller extends BaseController
{
    public function index()
    {
        $userId = (int) session('user_id');

        return view('esp32/listado', [
            'title'      => 'Mis ESP32 | Prevention Control System',
            'activeMenu' => 'esp32',
            'devices'    => (new Esp32DeviceModel())->devicesForUser($userId),
            'newToken'   => session('new_device_token'),
            'newDevice'  => session('new_device_name'),
        ]);
    }

    public function createForm()
    {
        $userId      = (int) session('user_id');
        $industries  = (new IndustryUserModel())->industriesForUser($userId);
        $adminSpaces = array_values(array_filter(
            $industries,
            static fn ($industry) => $industry['role'] === IndustryUserModel::ROLE_ADMIN
        ));

        $selectedIndustryId = (int) ($this->request->getGet('industry_id') ?? $this->request->getGet('space') ?? 0);
        $selectedIndustry = null;
        foreach ($adminSpaces as $space) {
            if ((int) $space['id'] === $selectedIndustryId) {
                $selectedIndustry = $space;
                break;
            }
        }

        if ($selectedIndustry === null && count($adminSpaces) === 1) {
            $selectedIndustry = $adminSpaces[0];
        }

        $metricModel = new IndustryMetricModel();
        $metricsByIndustry = [];
        foreach ($adminSpaces as $space) {
            $metricsByIndustry[(int) $space['id']] = $metricModel->metricsForIndustry((int) $space['id']);
        }

        return view('esp32/agregar', [
            'title'              => 'Anadir ESP | Prevention Control System',
            'activeMenu'         => 'esp32',
            'industries'         => $adminSpaces,
            'selectedIndustry'   => $selectedIndustry,
            'metricsByIndustry'  => $metricsByIndustry,
            'fromOnboarding'     => $this->request->getGet('from') === 'onboarding',
            'createdIndustryUid' => session('created_industry_uid'),
            'errors'             => session('errors') ?? [],
        ]);
    }

    public function store()
    {
        $userId = (int) session('user_id');

        $rules = [
            'name'        => 'required|min_length[2]|max_length[150]',
            'mac_address' => 'required|max_length[32]',
            'industry_id' => 'required|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $industryId = (int) $this->request->getPost('industry_id');
        if ((new IndustryUserModel())->roleFor($userId, $industryId) !== IndustryUserModel::ROLE_ADMIN) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('error', 'No tenes permiso para anadir ESP32 en ese espacio.');
        }

        $mac = Esp32DeviceModel::normalizeMac((string) $this->request->getPost('mac_address'));
        if ($mac === null) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('errors', ['mac_address' => 'La MAC no es valida. Usa AA:BB:CC:DD:EE:FF.']);
        }

        $deviceModel = new Esp32DeviceModel();
        if ($deviceModel->macExists($mac)) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('errors', ['mac_address' => 'Ya existe una ESP32 registrada con esa MAC.']);
        }

        $token = Esp32DeviceModel::generateToken();
        $name  = trim((string) $this->request->getPost('name'));

        $db = Database::connect();
        $db->transStart();

        $deviceId = $deviceModel->insert([
            'industry_id'  => $industryId,
            'name'         => $name,
            'mac_address'  => $mac,
            'sector'       => null,
            'token_hash'   => Esp32DeviceModel::hashToken($token),
            'token_secret' => Esp32DeviceModel::protectToken($token),
            'is_active'    => 1,
            'status'       => 'offline',
        ], true);

        $sensorCount = 0;
        if ($deviceId !== false) {
            $sensorCount = $this->createDefaultSensors((int) $deviceId, $industryId);
        }

        $db->transComplete();

        if ($deviceId === false || $db->transStatus() === false) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('error', 'No se pudo registrar la ESP32. Intentalo nuevamente.');
        }

        session()->setFlashdata('new_device_token', $token);
        session()->setFlashdata('new_device_name', $name);
        session()->setFlashdata('new_device_sensor_count', $sensorCount);

        return redirect()->to(base_url('esp32/' . $deviceId))
            ->with('success', 'ESP32 registrada correctamente.');
    }

    public function show($id = null)
    {
        $device = $this->findAccessibleDevice((int) $id);
        if ($device === null) {
            return redirect()->to(base_url('esp32'))->with('error', 'No tenes acceso a esa ESP32.');
        }

        $sensorModel  = new Esp32SensorModel();
        $readingModel = new SensorReadingModel();
        $sensors      = $sensorModel->sensorsForDevice((int) $device['id']);

        foreach ($sensors as &$sensor) {
            $latest = $readingModel->latestForSensor((int) $sensor['id']);
            $sensor['latest_value'] = $latest['value'] ?? null;
            $sensor['latest_at']    = $latest['measured_at'] ?? null;
        }
        unset($sensor);

        $usedTypeIds = array_map('intval', array_column($sensors, 'sensor_type_id'));
        $availableTypes = array_values(array_filter(
            (new SensorTypeModel())->orderBy('name', 'ASC')->findAll(),
            static fn ($type) => ! in_array((int) $type['id'], $usedTypeIds, true)
        ));

        $isAdmin = (new IndustryUserModel())->roleFor((int) session('user_id'), (int) $device['industry_id'])
            === IndustryUserModel::ROLE_ADMIN;

        $deviceToken = session('new_device_token');

        return view('esp32/detalle', [
            'title'          => esc($device['name']) . ' | ESP32',
            'activeMenu'     => 'esp32',
            'device'         => $device,
            'sensors'        => $sensors,
            'availableTypes' => $availableTypes,
            'isAdmin'        => $isAdmin,
            'deviceToken'    => $deviceToken,
            'sensorCount'    => session('new_device_sensor_count'),
            'setupUrl'       => $this->firmwareSetupUrl($device, $deviceToken),
        ]);
    }

    public function revealToken($id = null)
    {
        $device = $this->findAccessibleDevice((int) $id);
        if ($device === null) {
            return redirect()->to(base_url('esp32'))->with('error', 'No tenes acceso a esa ESP32.');
        }

        $industry = (new IndustryModel())->find((int) $device['industry_id']);
        $password = (string) $this->request->getPost('space_password');

        if ($industry === null || ! password_verify($password, (string) $industry['space_password_hash'])) {
            return redirect()->to(base_url('esp32/' . $device['id']))
                ->with('error', 'Clave del espacio incorrecta.');
        }

        $token = Esp32DeviceModel::revealToken($device['token_secret'] ?? null);
        $message = 'Token visible. Copialo o usa el link listo para la ESP32.';

        if ($token === null) {
            $token = Esp32DeviceModel::generateToken();
            (new Esp32DeviceModel())->update((int) $device['id'], [
                'token_hash'   => Esp32DeviceModel::hashToken($token),
                'token_secret' => Esp32DeviceModel::protectToken($token),
            ]);
            $message = 'Token regenerado. Cargalo de nuevo en la ESP32.';
        }

        session()->setFlashdata('new_device_token', $token);
        session()->setFlashdata('new_device_name', $device['name']);

        return redirect()->to(base_url('esp32/' . $device['id']))
            ->with('success', $message);
    }

    public function readings($id = null)
    {
        $device = $this->findAccessibleDevice((int) $id);
        if ($device === null) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'forbidden']);
        }

        $sensorId = (int) $this->request->getGet('sensor');
        $sensor = (new Esp32SensorModel())
            ->where('id', $sensorId)
            ->where('esp32_device_id', (int) $device['id'])
            ->first();

        if ($sensor === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'sensor_not_found']);
        }

        $series = (new SensorReadingModel())->seriesForSensor($sensorId, 200);

        $points = array_map(static function ($row) {
            return [
                'at'    => $row['measured_at'],
                'label' => date('d/m H:i', strtotime((string) $row['measured_at'])),
                'value' => (float) $row['value'],
            ];
        }, $series);

        return $this->response->setJSON([
            'sensor' => $sensorId,
            'latest' => $points !== [] ? end($points)['value'] : null,
            'points' => $points,
        ]);
    }

    private function findAccessibleDevice(int $deviceId): ?array
    {
        if ($deviceId <= 0) {
            return null;
        }

        $device = (new Esp32DeviceModel())->find($deviceId);
        if ($device === null) {
            return null;
        }

        if (! (new IndustryUserModel())->userBelongsTo((int) session('user_id'), (int) $device['industry_id'])) {
            return null;
        }

        return $device;
    }

    private function createDefaultSensors(int $deviceId, int $industryId): int
    {
        $sensorModel = new Esp32SensorModel();
        $created = 0;

        foreach ((new IndustryMetricModel())->metricsForIndustry($industryId) as $metric) {
            $sensorTypeId = (int) $metric['sensor_type_id'];
            if ($sensorModel->findForDeviceAndType($deviceId, $sensorTypeId) !== null) {
                continue;
            }

            if ($sensorModel->insert([
                'esp32_device_id' => $deviceId,
                'sensor_type_id'  => $sensorTypeId,
                'label'           => null,
            ]) !== false) {
                $created++;
            }
        }

        return $created;
    }

    private function firmwareSetupUrl(array $device, ?string $token): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }

        return 'http://192.168.4.1/?' . http_build_query([
            'device_name'  => $device['name'] ?? 'ESP32',
            'server_url'   => base_url('api/iot/lectura'),
            'device_token' => $token,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
