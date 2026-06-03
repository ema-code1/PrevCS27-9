<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;

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

        $deviceModel = new Esp32DeviceModel();
        foreach ($industries as &$ind) {
            $ind['device_count'] = count($deviceModel->devicesForIndustry((int) $ind['id']));
        }
        unset($ind);

        return view('registros-iot/espacios', [
            'title'      => 'Registros IoT | Prevention Control System',
            'activeMenu' => 'registros-iot',
            'industries' => $industries,
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

        $sensorModel  = new Esp32SensorModel();
        $readingModel = new SensorReadingModel();
        $sensors      = $sensorModel->sensorsForIndustry((int) $industry['id']);

        // Enriquecemos cada sensor con su última lectura
        foreach ($sensors as &$s) {
            $latest = $readingModel->latestForSensor((int) $s['id']);
            $s['latest_value'] = $latest['value'] ?? null;
            $s['latest_at']    = $latest['measured_at'] ?? null;
        }
        unset($s);

        $devices = (new Esp32DeviceModel())->devicesForIndustry((int) $industry['id']);

        return view('registros-iot/espacio', [
            'title'       => 'Registros IoT · ' . esc($industry['space_name']) . ' | PrevCS',
            'activeMenu'  => 'registros-iot',
            'industry'    => $industry,
            'sensors'     => $sensors,
            'deviceCount' => count($devices),
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
        if ($sensorId <= 0 || ! (new Esp32SensorModel())->belongsToIndustry($sensorId, (int) $industry['id'])) {
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
