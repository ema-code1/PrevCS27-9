<?php

namespace App\Libraries;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryUserModel;
use App\Models\SensorTypeModel;

/**
 * Regiones del modal "Configuración de la placa", dibujadas de nuevo desde el
 * servidor.
 *
 * Mismo trato que SpaceModalRegions para el modal del espacio: cada acción de
 * sensores viaja por fetch y contesta con el HTML actualizado de la lista, así
 * el navegador reemplaza esa parte en vez de recargar la página entera (que
 * cerraba la ventana y perdía el scroll).
 */
class DeviceModalRegions
{
    public const SENSORS = 'sensors';

    /**
     * @param list<string> $regions
     *
     * @return array<string, string> Mapa data-region => HTML.
     */
    public function render(int $deviceId, array $regions): array
    {
        $device = (new Esp32DeviceModel())->find($deviceId);
        if ($device === null || $regions === []) {
            return [];
        }

        $out = [];

        foreach ($regions as $region) {
            if ($region === self::SENSORS) {
                $out['esp32-sensors-' . $deviceId] = view('esp32/_config_sensors', $this->sensorData($device));
            }
        }

        return $out;
    }

    /**
     * Los mismos datos que arma Esp32Controller::show para la primera carga:
     * la región tiene que volver exactamente igual que como salió de ahí.
     *
     * @return array<string, mixed>
     */
    public function sensorData(array $device): array
    {
        $sensors     = (new Esp32SensorModel())->sensorsForDevice((int) $device['id']);
        $usedTypeIds = array_map('intval', array_column($sensors, 'sensor_type_id'));
        $allTypes    = (new SensorTypeModel())->orderBy('name', 'ASC')->findAll();

        return [
            'device'         => $device,
            'sensors'        => $sensors,
            'allTypes'       => $allTypes,
            'usedTypeIds'    => $usedTypeIds,
            'availableTypes' => array_values(array_filter(
                $allTypes,
                static fn ($type) => ! in_array((int) $type['id'], $usedTypeIds, true)
            )),
            'isAdmin' => (new IndustryUserModel())->roleFor((int) session('user_id'), (int) $device['industry_id'])
                === IndustryUserModel::ROLE_ADMIN,
        ];
    }
}
