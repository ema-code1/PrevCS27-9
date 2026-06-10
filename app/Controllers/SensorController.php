<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryUserModel;
use App\Models\SensorTypeModel;

class SensorController extends BaseController
{
    /**
     * Asocia un sensor (tipo) a una ESP32. Solo admin_principal de la industria.
     */
    public function store($deviceId = null)
    {
        $userId = (int) session('user_id');
        $device = (new Esp32DeviceModel())->find((int) $deviceId);

        if ($device === null) {
            return redirect()->to(base_url('esp32'))->with('error', 'ESP32 no encontrada.');
        }

        $membership = new IndustryUserModel();
        if ($membership->roleFor($userId, (int) $device['industry_id']) !== IndustryUserModel::ROLE_ADMIN) {
            return redirect()->to(base_url('esp32/' . $device['id']))
                ->with('error', 'Solo el admin del espacio puede agregar sensores.');
        }

        $rules = [
            'sensor_type_id' => 'required|is_natural_no_zero',
            'label'          => 'permit_empty|max_length[150]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('esp32/' . $device['id']))
                ->with('error', 'Elegí un tipo de sensor válido.');
        }

        $sensorTypeId = (int) $this->request->getPost('sensor_type_id');

        if ((new SensorTypeModel())->find($sensorTypeId) === null) {
            return redirect()->to(base_url('esp32/' . $device['id']))
                ->with('error', 'El tipo de sensor no existe.');
        }

        $sensorModel = new Esp32SensorModel();

        // El indice UNIQUE(device, type) evita duplicados; chequeamos para dar mensaje claro.
        if ($sensorModel->findForDeviceAndType((int) $device['id'], $sensorTypeId) !== null) {
            return redirect()->to(base_url('esp32/' . $device['id']))
                ->with('error', 'Ese sensor ya está asociado a la placa.');
        }

        $label = trim((string) $this->request->getPost('label'));

        $sensorModel->insert([
            'esp32_device_id' => (int) $device['id'],
            'sensor_type_id'  => $sensorTypeId,
            'label'           => $label !== '' ? $label : null,
        ]);

        return redirect()->to(base_url('esp32/' . $device['id']))
            ->with('success', 'Sensor agregado a la ESP32.');
    }

    /**
     * Borra un sensor del espacio desde el modal de configuración.
     * Requiere admin del espacio + espacio "desbloqueado" con la clave hace < 5 min
     * (el mismo desbloqueo que revela los tokens en IndustryController::revealTokens).
     */
    public function deleteFromSpace($industryId = null, $sensorId = null)
    {
        $userId     = (int) session('user_id');
        $industryId = (int) $industryId;
        $sensorId   = (int) $sensorId;
        $back       = base_url('registros-iot?config=' . $industryId);

        if ((new IndustryUserModel())->roleFor($userId, $industryId) !== IndustryUserModel::ROLE_ADMIN) {
            return redirect()->to(base_url('registros-iot'))->with('error', 'No tenes permiso para administrar ese espacio.');
        }

        $unlock = session('space_unlock');
        if (! is_array($unlock) || (int) ($unlock['id'] ?? 0) !== $industryId || (time() - (int) ($unlock['at'] ?? 0)) > 300) {
            return redirect()->to($back)->with('error', 'Ingresá la clave del espacio para poder borrar sensores.');
        }

        $sensorModel = new Esp32SensorModel();
        if (! $sensorModel->belongsToIndustry($sensorId, $industryId)) {
            return redirect()->to($back)->with('error', 'Ese sensor no pertenece al espacio.');
        }

        $sensorModel->delete($sensorId);

        return redirect()->to($back)->with('success', 'Sensor eliminado.');
    }
}
