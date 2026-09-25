<?php

namespace App\Controllers;

use App\Libraries\SensorAlerts;
use App\Libraries\SensorSeries;
use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\DeviceAccessModel;
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
        // El listado de placas vive ahora dentro de "Mis espacios" (acordeón por espacio).
        return redirect()->to(base_url('registros-iot'));
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
            'title'              => 'Añadir ESP | Preventive Control System',
            'activeMenu'         => 'registros-iot',
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
                ->with('error', 'No tenés permiso para añadir ESP32 en ese espacio.');
        }

        $deviceModel = new Esp32DeviceModel();
        $token = Esp32DeviceModel::generateToken();
        $name  = trim((string) $this->request->getPost('name'));

        $db = Database::connect();
        $db->transStart();

        $deviceId = $deviceModel->insert([
            'industry_id'  => $industryId,
            'name'         => $name,
            'mac_address'  => null, // se vincula sola en la 1.a lectura (auth por token)
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
                ->with('error', 'No se pudo registrar la ESP32. Intentá nuevamente.');
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
            return redirect()->to(base_url('registros-iot'))->with('error', 'No tenés acceso a esa ESP32.');
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
        // allTypes alimenta el "Cambiar" de cada sensor de la lista (ahí también
        // vale el tipo propio); availableTypes, el alta de un sensor nuevo.
        $allTypes = (new SensorTypeModel())->orderBy('name', 'ASC')->findAll();
        $availableTypes = array_values(array_filter(
            $allTypes,
            static fn ($type) => ! in_array((int) $type['id'], $usedTypeIds, true)
        ));

        $membershipModel = new IndustryUserModel();
        $isAdmin = $membershipModel->roleFor((int) session('user_id'), (int) $device['industry_id'])
            === IndustryUserModel::ROLE_ADMIN;
        $canEdit = $membershipModel->canEdit((int) session('user_id'), (int) $device['industry_id']);

        $flashToken  = session('new_device_token');
        $deviceToken = $flashToken;
        $isLinked    = ! empty($device['mac_address']);

        // Mientras la placa no está vinculada, el admin ve el QR directo,
        // sin pedir la clave del espacio (todavía no hay nada que proteger).
        if ($deviceToken === null && ! $isLinked && $isAdmin) {
            $deviceToken = Esp32DeviceModel::revealToken($device['token_secret'] ?? null);
        }

        return view('esp32/detalle', [
            'title'          => esc($device['name']) . ' | ESP32',
            'activeMenu'     => 'registros-iot',
            'device'         => $device,
            'sensors'        => $sensors,
            'availableTypes' => $availableTypes,
            'allTypes'       => $allTypes,
            'usedTypeIds'    => $usedTypeIds,
            'isAdmin'        => $isAdmin,
            'canEdit'        => $canEdit,
            'deviceToken'    => $deviceToken,
            'deleteError'    => session('delete_error'),
            'sensorCount'    => session('new_device_sensor_count'),
            // El QR de vinculación solo tiene sentido si la placa aún no se vinculó.
            'setupUrl'       => $isLinked ? null : $this->firmwareSetupUrl($device, $deviceToken),
            // ?config=1 es la vuelta de las acciones del propio modal (sensores,
            // borrado fallido): reabrirlo evita tener que buscar el engranaje
            // otra vez para seguir configurando.
            'autoOpenConfig' => $flashToken !== null || session('new_device_sensor_count') || session('delete_error') || $this->request->getGet('config') !== null,
        ]);
    }

    /**
     * Elimina una ESP32 ya creada y configurada. Exige el nombre exacto de la
     * placa + la clave del espacio, y que el usuario pueda editar el espacio
     * (admin o supervisor con permiso de edición).
     */
    public function delete($id = null)
    {
        $device = $this->findAccessibleDevice((int) $id);
        if ($device === null) {
            return redirect()->to(base_url('registros-iot'))->with('error', 'No tenés acceso a esa ESP32.');
        }

        $back = base_url('esp32/' . $device['id']);

        if (! (new IndustryUserModel())->canEdit((int) session('user_id'), (int) $device['industry_id'])) {
            return redirect()->to($back)->with('error', 'No tenés permiso para eliminar placas de este espacio.');
        }

        // Los errores de confirmación se muestran inline dentro del modal
        // (que se reabre con el form desplegado), no como toast.
        $confirmName = trim((string) $this->request->getPost('confirm_name'));
        if ($confirmName !== (string) $device['name']) {
            return redirect()->to($back)->with('delete_error', 'El nombre no coincide con el de la placa. No se eliminó nada.');
        }

        $industry = (new IndustryModel())->find((int) $device['industry_id']);
        $password = (string) $this->request->getPost('space_password');
        if ($industry === null || ! password_verify($password, (string) $industry['space_password_hash'])) {
            return redirect()->to($back)->with('delete_error', 'Contraseña incorrecta. No se eliminó nada.');
        }

        // Las FK en cascada borran también sensores y lecturas de la placa.
        (new Esp32DeviceModel())->delete((int) $device['id']);

        return redirect()->to(base_url('registros-iot'))
            ->with('success', 'La ESP32 "' . $device['name'] . '" fue eliminada junto a sus sensores y lecturas.');
    }

    /* El token del dispositivo no se muestra más en la vista de la placa: es un
       secreto del firmware y no tiene por qué pasar por la pantalla. Por eso se
       fue de acá el "Mostrar token" (y su endpoint), y el modal solo ofrece el
       QR de vinculación, que ya lleva el token adentro sin exhibirlo. */

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

        // JSON para el gráfico, o ?format=csv para descargar el historial.
        return (new SensorSeries())->respond(
            $this->request,
            $this->response,
            $sensor,
            (new SensorTypeModel())->find((int) $sensor['sensor_type_id']) ?? []
        );
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

        $userId = (int) session('user_id');
        $industryId = (int) $device['industry_id'];
        $membershipModel = new IndustryUserModel();
        $membership = $membershipModel->membershipFor($userId, $industryId);
        if ($membership === null) {
            return null;
        }

        if ($membershipModel->canSeeAllDevices($userId, $industryId)) {
            return $device;
        }

        $allowedIds = (new DeviceAccessModel())->allowedDeviceIdsForMembership((int) $membership['id']);
        if (! in_array((int) $device['id'], $allowedIds, true)) {
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

            $type = (new SensorTypeModel())->find($sensorTypeId);

            if ($sensorModel->insert([
                'esp32_device_id' => $deviceId,
                'sensor_type_id'  => $sensorTypeId,
                'label'           => null,
            ] + SensorAlerts::defaultsFor((string) ($type['code'] ?? ''))) !== false) {
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
            'server_url'   => $this->publicApiUrl(),
            'device_token' => $token,
            'return_url'   => base_url('registros-iot/' . (int) ($device['industry_id'] ?? 0)),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function publicApiUrl(): string
    {
        $uri = $this->request->getUri();
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $basePath = $basePath === '/' || $basePath === '\\' ? '' : str_replace('\\', '/', $basePath);

        return $uri->getScheme() . '://' . $uri->getAuthority() . $basePath . '/api/iot/lectura';
    }
}
