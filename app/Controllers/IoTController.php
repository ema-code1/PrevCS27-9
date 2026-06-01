<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\SensorReadingModel;
use App\Models\SensorTypeModel;
use Throwable;

/**
 * Endpoint de ingesta IoT consumido por las placas ESP32.
 * No usa sesion ni CSRF: se autentica por MAC + device_token.
 * (Ruta exenta en Config\Filters.)
 */
class IoTController extends BaseController
{
    public function ingest()
    {
        // Soporta JSON o form-encoded
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost();
        }

        $mac      = (string) ($payload['mac_address'] ?? '');
        $sensorIn = (string) ($payload['sensor'] ?? '');
        $value    = $payload['value'] ?? null;

        // Token: header preferido, fallback al cuerpo
        $token = (string) ($this->request->getHeaderLine('X-Device-Token') ?: ($payload['token'] ?? ''));

        if ($mac === '' || $sensorIn === '' || $token === '' || ! is_numeric($value)) {
            return $this->fail(422, 'Payload inválido. Se requiere mac_address, sensor, value y token.');
        }

        // 1) Buscar ESP32 por MAC
        $deviceModel = new Esp32DeviceModel();
        $device      = $deviceModel->findByMac($mac);
        if ($device === null) {
            return $this->fail(404, 'ESP32 no registrada.');
        }

        // 2) Validar token del dispositivo (comparacion en tiempo constante)
        if (! hash_equals((string) $device['device_token'], $token)) {
            return $this->fail(401, 'Token de dispositivo inválido.');
        }

        // 3) Resolver tipo de sensor y su asociacion con la placa
        $type = (new SensorTypeModel())->resolve($sensorIn);
        if ($type === null) {
            return $this->fail(422, 'Tipo de sensor desconocido.');
        }

        $sensor = (new Esp32SensorModel())->findForDeviceAndType((int) $device['id'], (int) $type['id']);
        if ($sensor === null) {
            return $this->fail(422, 'El sensor no está asociado a esta ESP32.');
        }

        // 4) Guardar la lectura + heartbeat
        try {
            $readingId = (new SensorReadingModel())->insert([
                'esp32_sensor_id' => (int) $sensor['id'],
                'value'           => (float) $value,
                'measured_at'     => $this->resolveMeasuredAt($payload),
            ], true);

            $deviceModel->touchLastSeen((int) $device['id']);
        } catch (Throwable $e) {
            log_message('error', 'IoT ingest failed for MAC {mac}: {msg}', [
                'mac' => $mac,
                'msg' => $e->getMessage(),
            ]);

            return $this->fail(500, 'No se pudo guardar la lectura.');
        }

        // 5) Responder
        return $this->response->setStatusCode(201)->setJSON([
            'ok'      => true,
            'id'      => $readingId,
            'device'  => $device['name'],
            'sensor'  => $type['code'],
            'value'   => (float) $value,
        ]);
    }

    /**
     * Usa measured_at del payload si es valido, si no la hora del servidor.
     */
    private function resolveMeasuredAt(array $payload): string
    {
        $raw = (string) ($payload['measured_at'] ?? '');
        if ($raw !== '' && ($ts = strtotime($raw)) !== false) {
            return date('Y-m-d H:i:s', $ts);
        }

        return date('Y-m-d H:i:s');
    }

    private function fail(int $status, string $message)
    {
        return $this->response->setStatusCode($status)->setJSON([
            'ok'    => false,
            'error' => $message,
        ]);
    }
}
