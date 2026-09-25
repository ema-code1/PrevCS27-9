<?php

namespace App\Controllers;

use App\Libraries\DeviceModalRegions;
use App\Libraries\SensorAlerts;
use App\Libraries\SpaceModalRegions;
use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;
use App\Models\SensorTypeModel;

class SensorController extends BaseController
{
    /**
     * Vuelta a la vista de la placa con el modal de configuración abierto: las
     * tres acciones de sensores se disparan desde adentro de esa ventana, así
     * que cerrarla en cada guardado obligaba a reabrirla para seguir.
     */
    private function backToDevice(int $deviceId): string
    {
        return base_url('esp32/' . $deviceId . '?config=1');
    }

    /**
     * Respuesta de una acción de sensores hecha desde el modal de la placa.
     *
     * Por fetch (el caso normal) contesta con la lista de sensores ya dibujada
     * de nuevo, así la ventana se actualiza sin recargar la página. `sensor`
     * le dice a la vista qué pasó, para que acomode las pestañas de arriba sin
     * tener que volver a pedir todo.
     *
     * @param array<string, mixed> $sensor
     */
    private function deviceOk(array $device, string $message, array $sensor = [])
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'      => true,
                'message' => $message,
                'csrf'    => csrf_hash(),
                'sensor'  => $sensor,
                'regions' => (new DeviceModalRegions())->render(
                    (int) $device['id'],
                    [DeviceModalRegions::SENSORS]
                ),
            ]);
        }

        return redirect()->to($this->backToDevice((int) $device['id']))->with('success', $message);
    }

    /** Error de una acción de sensores: inline por fetch, flash sin JavaScript. */
    private function deviceFail(?array $device, string $message, int $status = 422)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode($status)->setJSON([
                'ok'      => false,
                'message' => $message,
                'csrf'    => csrf_hash(),
            ]);
        }

        $back = $device === null
            ? base_url('registros-iot')
            : $this->backToDevice((int) $device['id']);

        return redirect()->to($back)->with('error', $message);
    }

    /**
     * Placa + permiso de admin del espacio. Devuelve la placa, o null si no
     * existe o el usuario no es admin (el llamador redirige).
     */
    private function adminDevice($deviceId): ?array
    {
        $device = (new Esp32DeviceModel())->find((int) $deviceId);
        if ($device === null) {
            return null;
        }

        $role = (new IndustryUserModel())->roleFor((int) session('user_id'), (int) $device['industry_id']);

        return $role === IndustryUserModel::ROLE_ADMIN ? $device : null;
    }

    /**
     * Asocia un sensor (tipo) a una ESP32. Solo admin_principal de la industria.
     */
    public function store($deviceId = null)
    {
        $device = $this->adminDevice($deviceId);
        if ($device === null) {
            return $this->deviceFail(null, 'No tenés permiso para agregar sensores a esa placa.', 403);
        }

        $rules = [
            'sensor_type_id' => 'required|is_natural_no_zero',
            'label'          => 'permit_empty|max_length[150]',
        ];

        if (! $this->validate($rules)) {
            return $this->deviceFail($device, 'Elegí un tipo de sensor válido.');
        }

        $sensorTypeId = (int) $this->request->getPost('sensor_type_id');

        $type = (new SensorTypeModel())->find($sensorTypeId);
        if ($type === null) {
            return $this->deviceFail($device, 'El tipo de sensor no existe.');
        }

        $sensorModel = new Esp32SensorModel();

        // El indice UNIQUE(device, type) evita duplicados; chequeamos para dar mensaje claro.
        if ($sensorModel->findForDeviceAndType((int) $device['id'], $sensorTypeId) !== null) {
            return $this->deviceFail($device, 'Ese sensor ya está asociado a la placa.');
        }

        $label = trim((string) $this->request->getPost('label'));

        $sensorId = $sensorModel->insert([
            'esp32_device_id' => (int) $device['id'],
            'sensor_type_id'  => $sensorTypeId,
            'label'           => $label !== '' ? $label : null,
        ] + SensorAlerts::defaultsFor((string) $type['code']), true);

        return $this->deviceOk($device, 'Sensor agregado a la ESP32.', [
            'action' => 'created',
            'id'     => (int) $sensorId,
        ]);
    }

    /**
     * Cambia el tipo (Corriente, Temperatura, RPM…) y/o la etiqueta de un
     * sensor ya asociado, desde el menú de la lista del modal.
     *
     * Cambiar el tipo NO toca las lecturas ya guardadas: quedan colgando del
     * mismo sensor y pasan a leerse con la unidad del tipo nuevo.
     */
    public function update($deviceId = null, $sensorId = null)
    {
        $device = $this->adminDevice($deviceId);
        if ($device === null) {
            return $this->deviceFail(null, 'No tenés permiso para editar los sensores de esa placa.', 403);
        }

        $sensorModel = new Esp32SensorModel();
        $sensor      = $sensorModel->findForDevice((int) $sensorId, (int) $device['id']);

        if ($sensor === null) {
            return $this->deviceFail($device, 'Ese sensor no es de esta placa.', 404);
        }

        $rules = [
            'sensor_type_id' => 'required|is_natural_no_zero',
            'label'          => 'permit_empty|max_length[150]',
            'alert_min'      => 'permit_empty|decimal',
            'alert_max'      => 'permit_empty|decimal',
        ];

        if (! $this->validate($rules)) {
            return $this->deviceFail($device, isset($this->validator->getErrors()['sensor_type_id'])
                ? 'Elegí un tipo de sensor válido.'
                : 'Los límites de alerta tienen que ser números.');
        }

        // Rango aceptable del sensor: fuera de él, la ingesta avisa al espacio.
        $alertMin = $this->optionalNumber('alert_min');
        $alertMax = $this->optionalNumber('alert_max');
        if ($alertMin !== null && $alertMax !== null && $alertMin >= $alertMax) {
            return $this->deviceFail($device, 'El mínimo de alerta tiene que ser menor que el máximo.');
        }

        $sensorTypeId = (int) $this->request->getPost('sensor_type_id');
        $type         = (new SensorTypeModel())->find($sensorTypeId);

        if ($type === null) {
            return $this->deviceFail($device, 'El tipo de sensor no existe.');
        }

        // UNIQUE(device, type): el tipo nuevo no puede estar tomado por otro
        // sensor de la misma placa (quedarse en el propio tipo sí vale).
        $taken = $sensorModel->findForDeviceAndType((int) $device['id'], $sensorTypeId);
        if ($taken !== null && (int) $taken['id'] !== (int) $sensor['id']) {
            return $this->deviceFail($device, 'La placa ya tiene un sensor de ese tipo.');
        }

        $label       = trim((string) $this->request->getPost('label'));
        $typeChanged = (int) $sensor['sensor_type_id'] !== $sensorTypeId;

        // Con otro tipo u otro rango, el estado se recalcula con la última
        // lectura: la tarjeta "Estado" queda al día sin esperar a la placa.
        $rangeChanged = $typeChanged
            || $alertMin !== ($sensor['alert_min'] === null ? null : (float) $sensor['alert_min'])
            || $alertMax !== ($sensor['alert_max'] === null ? null : (float) $sensor['alert_max']);

        $state = [];
        if ($rangeChanged) {
            $latest = (new SensorReadingModel())->latestForSensor((int) $sensor['id']);
            $state  = ['alert_state' => $latest === null ? null : SensorAlerts::evaluate((float) $latest['value'], $alertMin, $alertMax)];
        }

        $sensorModel->update((int) $sensor['id'], [
            'sensor_type_id' => $sensorTypeId,
            'label'          => $label !== '' ? $label : null,
            'alert_min'      => $alertMin,
            'alert_max'      => $alertMax,
        ] + $state);

        // typeChanged: con otro tipo cambian la unidad y el gráfico del panel,
        // que no vive en esta región; la vista lo redibuja al cerrar. Si solo
        // cambió la etiqueta, alcanza con reescribir el nombre de la pestaña.
        return $this->deviceOk($device, 'Sensor actualizado.', [
            'action'      => 'updated',
            'id'          => (int) $sensor['id'],
            'title'       => $label !== '' ? $label : (string) $type['name'],
            'typeChanged' => $rangeChanged, // tipo o rango: el panel del gráfico se redibuja
        ]);
    }

    /**
     * Borra un sensor de la placa desde la lista del modal. El borrado se lleva
     * también sus lecturas (FK en cascada) y no se puede deshacer.
     */
    public function deleteFromDevice($deviceId = null, $sensorId = null)
    {
        $device = $this->adminDevice($deviceId);
        if ($device === null) {
            return $this->deviceFail(null, 'No tenés permiso para borrar los sensores de esa placa.', 403);
        }

        $sensorModel = new Esp32SensorModel();

        if ($sensorModel->findForDevice((int) $sensorId, (int) $device['id']) === null) {
            return $this->deviceFail($device, 'Ese sensor no es de esta placa.', 404);
        }

        $sensorModel->delete((int) $sensorId);

        return $this->deviceOk($device, 'Sensor eliminado.', [
            'action' => 'deleted',
            'id'     => (int) $sensorId,
        ]);
    }

    /**
     * Borra un sensor del espacio desde el modal de configuración.
     * Requiere admin del espacio + espacio "desbloqueado" con la clave hace < 5 min
     * (el mismo desbloqueo de IndustryController::unlockSensors).
     */
    public function deleteFromSpace($industryId = null, $sensorId = null)
    {
        $userId     = (int) session('user_id');
        $industryId = (int) $industryId;
        $sensorId   = (int) $sensorId;

        if ((new IndustryUserModel())->roleFor($userId, $industryId) !== IndustryUserModel::ROLE_ADMIN) {
            return $this->spaceFail($industryId, 'No tenés permiso para administrar ese espacio.');
        }

        $unlock = session('space_unlock');
        if (! is_array($unlock) || (int) ($unlock['id'] ?? 0) !== $industryId || (time() - (int) ($unlock['at'] ?? 0)) > 300) {
            return $this->spaceFail($industryId, 'Ingresá la clave del espacio para poder borrar sensores.');
        }

        $sensorModel = new Esp32SensorModel();
        if (! $sensorModel->belongsToIndustry($sensorId, $industryId)) {
            return $this->spaceFail($industryId, 'Ese sensor no pertenece al espacio.');
        }

        $sensorModel->delete($sensorId);

        // Por fetch (el caso normal) contestamos con la sección de sensores ya
        // dibujada de nuevo, así el modal se actualiza sin recargar la página.
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'      => true,
                'message' => 'Sensor eliminado.',
                'csrf'    => csrf_hash(),
                'regions' => (new SpaceModalRegions())->render($industryId, [SpaceModalRegions::SENSORS]),
            ]);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', 'Sensor eliminado.');
    }

    /** Campo numérico opcional del formulario: vacío = sin límite. */
    private function optionalNumber(string $field): ?float
    {
        $raw = trim(str_replace(',', '.', (string) $this->request->getPost($field)));

        return $raw === '' ? null : (float) $raw;
    }

    private function spaceFail(int $industryId, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => $message,
                'csrf'    => csrf_hash(),
            ]);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('error', $message);
    }
}
