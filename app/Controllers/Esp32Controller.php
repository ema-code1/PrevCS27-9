<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;
use App\Models\SensorTypeModel;

class Esp32Controller extends BaseController
{
    /**
     * Listado de ESP32 visibles para el usuario (todas sus industrias).
     */
    public function index()
    {
        $userId = (int) session('user_id');

        return view('esp32/listado', [
            'title'      => 'Mis ESP32 | Prevention Control System',
            'activeMenu' => 'anadir-esp',
            'devices'    => (new Esp32DeviceModel())->devicesForUser($userId),
            'newToken'   => session('new_device_token'),
            'newDevice'  => session('new_device_name'),
        ]);
    }

    /**
     * Formulario "Añadir ESP". Solo industrias donde el usuario es admin_principal.
     */
    public function createForm()
    {
        $userId       = (int) session('user_id');
        $industries   = (new IndustryUserModel())->industriesForUser($userId);
        $adminSpaces  = array_values(array_filter(
            $industries,
            static fn ($i) => $i['role'] === IndustryUserModel::ROLE_ADMIN
        ));

        return view('esp32/agregar', [
            'title'       => 'Añadir ESP | Prevention Control System',
            'activeMenu'  => 'anadir-esp',
            'industries'  => $adminSpaces,
            'errors'      => session('errors') ?? [],
        ]);
    }

    /**
     * Alta de una ESP32 vinculada a una industria del usuario (admin).
     */
    public function store()
    {
        $userId = (int) session('user_id');

        $rules = [
            'name'        => 'required|min_length[2]|max_length[150]',
            'mac_address' => 'required|max_length[32]',
            'sector'      => 'permit_empty|max_length[120]',
            'industry_id' => 'required|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $industryId = (int) $this->request->getPost('industry_id');

        // El usuario debe ser admin_principal de esa industria.
        if ((new IndustryUserModel())->roleFor($userId, $industryId) !== IndustryUserModel::ROLE_ADMIN) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('error', 'No tenés permiso para añadir ESP32 en ese espacio.');
        }

        $mac = Esp32DeviceModel::normalizeMac((string) $this->request->getPost('mac_address'));
        if ($mac === null) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('errors', ['mac_address' => 'La dirección MAC no es válida (formato AA:BB:CC:DD:EE:FF).']);
        }

        $deviceModel = new Esp32DeviceModel();
        if ($deviceModel->macExists($mac)) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('errors', ['mac_address' => 'Ya existe una ESP32 registrada con esa MAC.']);
        }

        $token = Esp32DeviceModel::generateToken();

        $sector = trim((string) $this->request->getPost('sector'));
        $name   = trim((string) $this->request->getPost('name'));

        $deviceId = $deviceModel->insert([
            'industry_id'  => $industryId,
            'name'         => $name,
            'mac_address'  => $mac,
            'sector'       => $sector !== '' ? $sector : null,
            'token_hash'   => Esp32DeviceModel::hashToken($token),
            'is_active'    => 1,
            'status'       => 'offline',
        ], true);

        if ($deviceId === false) {
            return redirect()->to(base_url('esp32/agregar'))
                ->withInput()
                ->with('error', 'No se pudo registrar la ESP32. Intentalo nuevamente.');
        }

        // Mostramos el token una vez en el listado (para cargarlo en el firmware).
        session()->setFlashdata('new_device_token', $token);
        session()->setFlashdata('new_device_name', $name);

        return redirect()->to(base_url('esp32/' . $deviceId))
            ->with('success', 'ESP32 registrada correctamente.');
    }

    /**
     * Vista de una ESP32: datos, sensores, gráficos e historial.
     */
    public function show($id = null)
    {
        $device = $this->findAccessibleDevice((int) $id);
        if ($device === null) {
            return redirect()->to(base_url('esp32'))->with('error', 'No tenés acceso a esa ESP32.');
        }

        $sensorModel  = new Esp32SensorModel();
        $readingModel = new SensorReadingModel();
        $sensors      = $sensorModel->sensorsForDevice((int) $device['id']);

        // Enriquecemos cada sensor con su ultima lectura
        foreach ($sensors as &$sensor) {
            $latest = $readingModel->latestForSensor((int) $sensor['id']);
            $sensor['latest_value'] = $latest['value'] ?? null;
            $sensor['latest_at']    = $latest['measured_at'] ?? null;
        }
        unset($sensor);

        // Tipos de sensor que todavia no estan asociados (para el alta rapida)
        $usedTypeIds   = array_column($sensors, 'sensor_type_id');
        $availableTypes = array_values(array_filter(
            (new SensorTypeModel())->orderBy('name', 'ASC')->findAll(),
            static fn ($t) => ! in_array($t['id'], array_map('intval', $usedTypeIds), true)
        ));

        $isAdmin = (new IndustryUserModel())->roleFor((int) session('user_id'), (int) $device['industry_id'])
            === IndustryUserModel::ROLE_ADMIN;

        return view('esp32/detalle', [
            'title'          => esc($device['name']) . ' | ESP32',
            'activeMenu'     => 'anadir-esp',
            'device'         => $device,
            'sensors'        => $sensors,
            'availableTypes' => $availableTypes,
            'isAdmin'        => $isAdmin,
            'deviceToken'    => session('new_device_token'),
        ]);
    }

    /**
     * Serie de lecturas (JSON) para los gráficos del detalle, scopeada por acceso.
     */
    public function readings($id = null)
    {
        $device = $this->findAccessibleDevice((int) $id);
        if ($device === null) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'forbidden']);
        }

        $sensorId = (int) $this->request->getGet('sensor');

        // El sensor debe pertenecer a esta placa
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

    /**
     * Devuelve el dispositivo si el usuario pertenece a su industria; si no, null.
     */
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
}
