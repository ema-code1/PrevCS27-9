<?php

namespace App\Controllers;

use App\Libraries\SensorSeries;
use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\DeviceAccessModel;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;
use App\Models\SensorTypeModel;
use App\Models\SpaceInvitationModel;

/**
 * Registros IoT por-espacio.
 *
 *  GET registros-iot               → lista de los espacios del usuario (un botón c/u)
 *  GET registros-iot/{industryId}  → vista del espacio: un gráfico por sensor
 *  GET api/espacio/{industryId}/lecturas?sensor={id} → serie JSON (scopeada)
 *
 * Reemplaza la vieja vista global con datos demo (Home::registrosIot +
 * IotReadings + IotReadingRepository).
 */
class RegistrosController extends BaseController
{
    /**
     * Lista de espacios a los que pertenece el usuario, cada uno con su
     * botón "Registros IoT" y un conteo de placas.
     */
    public function index(): string
    {
        $userId     = (int) session('user_id');
        $industries = (new IndustryUserModel())->industriesForUser($userId);

        $deviceModel     = new Esp32DeviceModel();
        $sensorModel     = new Esp32SensorModel();
        $accessModel     = new DeviceAccessModel();
        $membershipModel = new IndustryUserModel();
        $invitationModel = new SpaceInvitationModel();
        foreach ($industries as &$ind) {
            $industryId = (int) $ind['id'];
            $devices    = $deviceModel->devicesForIndustry($industryId);
            $allDevices = $devices;
            $canSeeAll  = $membershipModel->canSeeAllDevices($userId, $industryId);
            $membership = $membershipModel->membershipFor($userId, $industryId);

            if (! $canSeeAll) {
                $allowedIds = $membership !== null
                    ? $accessModel->allowedDeviceIdsForMembership((int) $membership['id'])
                    : [];
                $devices = array_values(array_filter(
                    $devices,
                    static fn ($device) => in_array((int) $device['id'], $allowedIds, true)
                ));
            }

            $isAdmin = $ind['role'] === IndustryUserModel::ROLE_ADMIN;
            $canManageAccess = $membershipModel->canManageDeviceAccess($userId, $industryId);

            $ind['device_count']      = count($devices);
            $ind['all_device_count']  = count($allDevices);
            $ind['devices']           = $devices;
            $ind['all_devices']       = $allDevices;
            $ind['can_manage_access'] = $canManageAccess;
            $ind['can_manage_roles']  = $isAdmin;

            if ($canManageAccess) {
                $members = $membershipModel->membersForIndustry($industryId);
                $ind['members'] = $members;
                $ind['device_access'] = $accessModel->accessMapForMemberships(array_column($members, 'id'));
                $ind['sensors'] = $isAdmin ? $sensorModel->sensorsForIndustry($industryId) : [];
                $ind['invitations'] = $isAdmin ? $invitationModel->pendingForIndustry($industryId) : [];
            } else {
                $ind['members']     = [];
                $ind['sensors']     = [];
                $ind['invitations'] = [];
                $ind['device_access'] = [];
            }
        }
        unset($ind);

        // Desbloqueo del modal (clave del espacio, válido 5 min): habilita el
        // borrado de sensores. Vive en session('space_unlock').
        $unlock          = session('space_unlock');
        $unlockedSpaceId = 0;
        if (is_array($unlock) && (time() - (int) ($unlock['at'] ?? 0)) <= 300) {
            $unlockedSpaceId = (int) ($unlock['id'] ?? 0);
        }

        return view('registros-iot/espacios', [
            'title'           => 'Mis espacios | Preventive Control System',
            'activeMenu'      => 'registros-iot',
            'industries'      => $industries,
            'openConfigId'    => (int) ($this->request->getGet('config') ?? 0),
            'unlockedSpaceId' => $unlockedSpaceId,
        ]);
    }

    /**
     * Vista de un espacio: un panel con gráfico por cada sensor de cada placa.
     */
    public function space($industryId = null)
    {
        $userId   = (int) session('user_id');
        $industry = $this->accessibleIndustry((int) $industryId, $userId);
        if ($industry === null) {
            return redirect()->to(base_url('registros-iot'))
                ->with('error', 'No tenés acceso a ese espacio.');
        }

        $sensorModel     = new Esp32SensorModel();
        $readingModel    = new SensorReadingModel();
        $membershipModel = new IndustryUserModel();
        $accessModel     = new DeviceAccessModel();
        $devices         = (new Esp32DeviceModel())->devicesForIndustry((int) $industry['id']);

        if (! $membershipModel->canSeeAllDevices($userId, (int) $industry['id'])) {
            $membership = $membershipModel->membershipFor($userId, (int) $industry['id']);
            $allowedIds = $membership !== null
                ? $accessModel->allowedDeviceIdsForMembership((int) $membership['id'])
                : [];
            $devices = array_values(array_filter(
                $devices,
                static fn ($device) => in_array((int) $device['id'], $allowedIds, true)
            ));
        }

        $visibleDeviceIds = array_map('intval', array_column($devices, 'id'));
        $sensors = $sensorModel->sensorsForIndustry((int) $industry['id']);
        if (! $membershipModel->canSeeAllDevices($userId, (int) $industry['id'])) {
            $sensors = array_values(array_filter(
                $sensors,
                static fn ($sensor) => in_array((int) $sensor['esp32_device_id'], $visibleDeviceIds, true)
            ));
        }

        // Enriquecemos cada sensor con su última lectura
        foreach ($sensors as &$s) {
            $latest = $readingModel->latestForSensor((int) $s['id']);
            $s['latest_value'] = $latest['value'] ?? null;
            $s['latest_at']    = $latest['measured_at'] ?? null;
        }
        unset($s);

        $role = $membershipModel->roleFor($userId, (int) $industry['id']);

        return view('registros-iot/espacio', [
            'title'       => 'Registros IoT · ' . esc($industry['space_name']) . ' | PrevCS',
            'activeMenu'  => 'registros-iot',
            'industry'    => $industry,
            'sensors'     => $sensors,
            'devices'     => $devices,
            'deviceCount' => count($devices),
            'isAdmin'     => $role === IndustryUserModel::ROLE_ADMIN,
        ]);
    }

    /**
     * Serie temporal (JSON) de un sensor, validando que pertenezca al espacio.
     */
    public function readings($industryId = null)
    {
        $userId   = (int) session('user_id');
        $industry = $this->accessibleIndustry((int) $industryId, $userId);
        if ($industry === null) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'forbidden']);
        }

        $sensorId = (int) $this->request->getGet('sensor');
        $sensorModel = new Esp32SensorModel();
        $sensor = $sensorModel->where('id', $sensorId)->first();
        if ($sensorId <= 0 || $sensor === null || ! $sensorModel->belongsToIndustry($sensorId, (int) $industry['id'])) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'sensor_not_found']);
        }

        $membershipModel = new IndustryUserModel();
        if (! $membershipModel->canSeeAllDevices($userId, (int) $industry['id'])) {
            $membership = $membershipModel->membershipFor($userId, (int) $industry['id']);
            $allowedIds = $membership !== null
                ? (new DeviceAccessModel())->allowedDeviceIdsForMembership((int) $membership['id'])
                : [];

            if (! in_array((int) $sensor['esp32_device_id'], $allowedIds, true)) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'forbidden']);
            }
        }

        // JSON para el gráfico, o ?format=csv para descargar el historial.
        return (new SensorSeries())->respond(
            $this->request,
            $this->response,
            $sensor,
            (new SensorTypeModel())->find((int) $sensor['sensor_type_id']) ?? []
        );
    }

    /**
     * Devuelve la industria si el usuario pertenece a ella; si no, null.
     */
    private function accessibleIndustry(int $industryId, int $userId): ?array
    {
        if ($industryId <= 0) {
            return null;
        }

        if (! (new IndustryUserModel())->userBelongsTo($userId, $industryId)) {
            return null;
        }

        return (new IndustryModel())->find($industryId);
    }
}
