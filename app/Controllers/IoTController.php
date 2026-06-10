<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\SensorReadingModel;
use App\Models\SensorTypeModel;
use Config\Database;
use Throwable;

/**
 * Endpoint de ingesta IoT consumido por las placas ESP32.
 * No usa sesión ni CSRF: se autentica por MAC + token (hash SHA-256).
 * (Ruta exenta en Config\Filters.)
 *
 * Acepta dos formatos de cuerpo (JSON o form-encoded):
 *
 *  A) LOTE (recomendado, una ventana de medición con varias métricas):
 *     {
 *       "mac_address": "A4:CF:12:AB:88:01",
 *       "token": "....",                     // o header X-Device-Token
 *       "timestamp": "2026-06-02 15:30:00",  // opcional, se aplica a todo el lote
 *       "window_seconds": 15,                 // opcional, informativo
 *       "readings": [
 *         { "type": "vibration",  "value": 3 },
 *         { "type": "rpm",        "value": 1250 },
 *         { "type": "rpm_analog", "value": 1870 },
 *         { "type": "temperature","value": 36.7 }
 *       ]
 *     }
 *
 *  B) LECTURA SIMPLE (retrocompatible):
 *     { "mac_address": "...", "sensor": "vibration", "value": 3, "token": "..." }
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

        // Identificación de la placa: MAC (acepta alias "mac")
        $mac = (string) ($payload['mac_address'] ?? $payload['mac'] ?? '');

        // Token: header preferido, fallback al cuerpo
        $token = (string) ($this->request->getHeaderLine('X-Device-Token') ?: ($payload['token'] ?? ''));

        if ($mac === '' || $token === '') {
            return $this->fail(422, 'Faltan credenciales: se requiere mac_address y token.');
        }

        // 1) Identificar la placa por su TOKEN (no por MAC). La placa se autentica
        //    con el token; la MAC se vincula sola en el primer contacto y luego
        //    queda "candada" a esa placa física.
        $deviceModel = new Esp32DeviceModel();
        $device      = $deviceModel->findByTokenHash(Esp32DeviceModel::hashToken($token));
        if ($device === null) {
            return $this->fail(401, 'Token de dispositivo inválido.');
        }

        // 2) La placa debe estar activa
        if ((int) ($device['is_active'] ?? 1) !== 1) {
            return $this->fail(403, 'La ESP32 está deshabilitada.');
        }

        // 3) Vincular / verificar la MAC.
        $incomingMac = Esp32DeviceModel::normalizeMac($mac);
        $storedMac   = (string) ($device['mac_address'] ?? '');

        if ($storedMac === '') {
            // Primer contacto: la placa "reclama" su registro y graba su MAC.
            if ($incomingMac === null) {
                return $this->fail(422, 'MAC inválida: no se pudo vincular la placa.');
            }
            if ($deviceModel->macExists($incomingMac)) {
                return $this->fail(409, 'Esa MAC ya está vinculada a otra placa.');
            }
            $deviceModel->update((int) $device['id'], ['mac_address' => $incomingMac]);
            $device['mac_address'] = $incomingMac;
        } elseif ($incomingMac !== null && ! hash_equals($storedMac, $incomingMac)) {
            // Token candado: el token ya pertenece a otra placa física (MAC distinta).
            return $this->fail(409, 'El token ya está vinculado a otra placa (MAC distinta).');
        }

        // 4) Normalizar a una lista de lecturas {type, value, measured_at}
        $items = $this->extractReadings($payload);
        if ($items === []) {
            return $this->fail(422, 'No se recibieron lecturas (readings[] o sensor+value).');
        }

        $typeModel   = new SensorTypeModel();
        $sensorModel = new Esp32SensorModel();
        $deviceId    = (int) $device['id'];

        $rows    = [];   // filas listas para insertar en sensor_readings
        $saved   = [];   // resumen de lo aceptado (para la respuesta)
        $skipped = [];   // lo omitido + motivo (para diagnóstico en la consola de la ESP)

        foreach ($items as $item) {
            $typeIn = trim((string) ($item['type'] ?? ''));
            $value  = $item['value'] ?? null;

            if ($typeIn === '' || ! is_numeric($value)) {
                $skipped[] = ['type' => $typeIn, 'reason' => 'valor_invalido'];
                continue;
            }

            $type = $typeModel->resolve($typeIn);
            if ($type === null) {
                $skipped[] = ['type' => $typeIn, 'reason' => 'tipo_desconocido'];
                continue;
            }

            $sensor = $sensorModel->findForDeviceAndType($deviceId, (int) $type['id']);
            if ($sensor === null) {
                // El tipo existe pero esta placa no tiene ese sensor asociado.
                $skipped[] = ['type' => $type['code'], 'reason' => 'sensor_no_asociado'];
                continue;
            }

            $rows[] = [
                'esp32_sensor_id' => (int) $sensor['id'],
                'value'           => (float) $value,
                'measured_at'     => $this->resolveMeasuredAt($item, $payload),
            ];
            $saved[] = ['type' => $type['code'], 'value' => (float) $value];
        }

        // 5) Si nada coincide con sensores asociados, avisamos (config incompleta).
        if ($rows === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'error'   => 'Ninguna lectura coincide con sensores asociados a esta ESP32.',
                'skipped' => $skipped,
            ]);
        }

        // 6) Guardar en transacción + heartbeat
        $db = Database::connect();
        try {
            $db->transStart();
            (new SensorReadingModel())->insertBatch($rows);
            $deviceModel->touchLastSeen($deviceId);
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('transacción fallida');
            }
        } catch (Throwable $e) {
            log_message('error', 'IoT ingest failed for MAC {mac}: {msg}', [
                'mac' => $mac,
                'msg' => $e->getMessage(),
            ]);

            return $this->fail(500, 'No se pudieron guardar las lecturas.');
        }

        // 7) Responder (201 con detalle de aceptadas/omitidas)
        return $this->response->setStatusCode(201)->setJSON([
            'ok'      => true,
            'device'  => $device['name'],
            'saved'   => $saved,
            'skipped' => $skipped,
            'count'   => count($rows),
        ]);
    }

    /**
     * Devuelve una lista uniforme de lecturas a partir de cualquiera de los
     * dos formatos aceptados (lote o simple).
     *
     * @return list<array{type:string, value:mixed, measured_at?:string}>
     */
    private function extractReadings(array $payload): array
    {
        // Formato A: readings[]
        if (isset($payload['readings']) && is_array($payload['readings'])) {
            $out = [];
            foreach ($payload['readings'] as $r) {
                if (! is_array($r)) {
                    continue;
                }
                $out[] = [
                    'type'        => (string) ($r['type'] ?? $r['sensor'] ?? ''),
                    'value'       => $r['value'] ?? null,
                    'measured_at' => (string) ($r['measured_at'] ?? ''),
                ];
            }

            return $out;
        }

        // Formato B: sensor + value en la raíz
        if (isset($payload['sensor']) && isset($payload['value'])) {
            return [[
                'type'        => (string) $payload['sensor'],
                'value'       => $payload['value'],
                'measured_at' => (string) ($payload['measured_at'] ?? ''),
            ]];
        }

        return [];
    }

    /**
     * Resuelve el momento de la lectura: measured_at del item, si no el
     * timestamp del lote, si no la hora del servidor.
     */
    private function resolveMeasuredAt(array $item, array $payload): string
    {
        foreach ([$item['measured_at'] ?? '', $payload['timestamp'] ?? '', $payload['measured_at'] ?? ''] as $raw) {
            $raw = (string) $raw;
            if ($raw !== '' && ($ts = strtotime($raw)) !== false) {
                return date('Y-m-d H:i:s', $ts);
            }
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
