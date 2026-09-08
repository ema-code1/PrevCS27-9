<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SensorReadingModel;
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
        $membershipModel = new IndustryUserModel();
        $invitationModel = new SpaceInvitationModel();
        foreach ($industries as &$ind) {
            $devices = $deviceModel->devicesForIndustry((int) $ind['id']);
            $ind['device_count'] = count($devices);
            // Lista de placas del acordeón: visible para cualquier miembro del espacio.
            $ind['devices'] = $devices;

            // El modal de configuración (miembros, invitaciones, tokens, sensores)
            // es solo de admin.
            if ($ind['role'] === IndustryUserModel::ROLE_ADMIN) {
                $ind['members']     = $membershipModel->membersForIndustry((int) $ind['id']);
                $ind['sensors']     = $sensorModel->sensorsForIndustry((int) $ind['id']);
                $ind['invitations'] = $invitationModel->pendingForIndustry((int) $ind['id']);
            } else {
                $ind['members']     = [];
                $ind['sensors']     = [];
                $ind['invitations'] = [];
            }
        }
        unset($ind);

        // Desbloqueo del modal (clave del espacio, válido 5 min): revela tokens y
        // habilita el borrado de sensores. Vive en session('space_unlock').
        $unlock          = session('space_unlock');
        $unlockedSpaceId = 0;
        $unlockedTokens  = [];
        if (is_array($unlock) && (time() - (int) ($unlock['at'] ?? 0)) <= 300) {
            $unlockedSpaceId = (int) ($unlock['id'] ?? 0);
            $unlockedTokens  = is_array($unlock['tokens'] ?? null) ? $unlock['tokens'] : [];
        }

        return view('registros-iot/espacios', [
            'title'           => 'Mis espacios | Preventive Control System',
            'activeMenu'      => 'registros-iot',
            'industries'      => $industries,
            'openConfigId'    => (int) ($this->request->getGet('config') ?? 0),
            'unlockedSpaceId' => $unlockedSpaceId,
            'unlockedTokens'  => $unlockedTokens,
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
        $role = (new IndustryUserModel())->roleFor($userId, (int) $industry['id']);

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
        if ($sensorId <= 0 || ! (new Esp32SensorModel())->belongsToIndustry($sensorId, (int) $industry['id'])) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'sensor_not_found']);
        }

        [$from, $to] = $this->rangeBounds();
        $series = (new SensorReadingModel())->seriesForSensor($sensorId, 500, $from, $to);

        $points = array_map(static function ($row) {
            return [
                'at'    => $row['measured_at'],
                'label' => date('d/m H:i', strtotime((string) $row['measured_at'])),
                'value' => (float) $row['value'],
            ];
        }, $series);

        return $this->response->setJSON([
            'sensor' => $sensorId,
            'from'   => $from,
            'to'     => $to,
            'latest' => $points !== [] ? end($points)['value'] : null,
            'points' => $points,
        ]);
    }

    /**
     * Lee from/to de la query y los normaliza a 'Y-m-d H:i:s' (o null).
     * El date range selector envía, p.ej. from=2026-03-09 00:00:00.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function rangeBounds(): array
    {
        $parse = function (?string $value): ?string {
            $value = $value !== null ? trim($value) : '';
            if ($value === '') {
                return null;
            }
            $ts = strtotime($value);

            return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        };

        return [
            $parse($this->request->getGet('from')),
            $parse($this->request->getGet('to')),
        ];
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
