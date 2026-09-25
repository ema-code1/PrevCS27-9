<?php

namespace App\Libraries;

use App\Models\Esp32SensorModel;
use App\Models\NotificationModel;

/**
 * Alertas de los sensores IoT: la parte de la web que reacciona a los datos.
 *
 * Cada sensor tiene un rango aceptable propio (alert_min / alert_max, los dos
 * opcionales) y guarda en alert_state cómo quedó con su última lectura. Al
 * llegar un lote, IoTController::ingest() llama a process(): si una lectura
 * cambia el estado del sensor (ok → alto, alto → ok…) se avisa a todo el
 * espacio por la campana de notificaciones. Mientras el valor siga fuera de
 * rango no se repite el aviso: se notifica la transición, igual que las
 * alertas de stock (StockRowAlerts).
 */
class SensorAlerts
{
    public const STATE_OK   = 'ok';
    public const STATE_HIGH = 'high';
    public const STATE_LOW  = 'low';

    /**
     * Rango con el que nace cada sensor según su tipo (el admin lo cambia desde
     * "Configuración de la placa"). Los valores salen de la maqueta: el motor
     * trabaja tibio y el SW-420 casi no cuenta eventos si está bien sujeto.
     * RPM no trae rango: un motor detenido no es necesariamente una falla.
     *
     * @var array<string, array{0: float|null, 1: float|null}>
     */
    public const DEFAULTS = [
        'temperature' => [null, 60.0],
        'vibration'   => [null, 50.0],
    ];

    /**
     * @return array{alert_min: float|null, alert_max: float|null}
     */
    public static function defaultsFor(string $code): array
    {
        [$min, $max] = self::DEFAULTS[$code] ?? [null, null];

        return ['alert_min' => $min, 'alert_max' => $max];
    }

    /**
     * Estado de un valor frente al rango del sensor. Sin límites, siempre ok.
     *
     * @param mixed $min
     * @param mixed $max
     */
    public static function evaluate(float $value, $min, $max): string
    {
        if ($max !== null && $max !== '' && $value > (float) $max) {
            return self::STATE_HIGH;
        }

        if ($min !== null && $min !== '' && $value < (float) $min) {
            return self::STATE_LOW;
        }

        return self::STATE_OK;
    }

    /**
     * Evalúa las lecturas recién guardadas de una placa, actualiza el estado
     * de cada sensor y notifica los cambios.
     *
     * @param array                                                 $device
     * @param list<array{sensor: array, type: array, value: float}> $readings
     *
     * @return list<array{type: string, state: string}> Sensores fuera de rango
     *                                                  (la placa los usa para sonar).
     */
    public function process(array $device, array $readings): array
    {
        $sensorModel   = new Esp32SensorModel();
        $notifications = new NotificationModel();
        $active        = [];

        foreach ($readings as $reading) {
            $sensor = $reading['sensor'];
            $type   = $reading['type'];
            $value  = (float) $reading['value'];

            $state    = self::evaluate($value, $sensor['alert_min'] ?? null, $sensor['alert_max'] ?? null);
            $previous = (string) ($sensor['alert_state'] ?? '') ?: self::STATE_OK;

            if ($state !== self::STATE_OK) {
                $active[] = ['type' => (string) $type['code'], 'state' => $state];
            }

            if ($state === ($sensor['alert_state'] ?? null)) {
                continue;
            }

            $sensorModel->update((int) $sensor['id'], ['alert_state' => $state]);

            if ($state === $previous) {
                // Primera lectura del sensor y dentro de rango: nada que avisar.
                continue;
            }

            $notifications->fanOut((int) $device['industry_id'], self::message($device, $sensor, $type, $state, $value) + [
                'source'     => NotificationModel::SOURCE_SENSOR,
                'source_key' => 'sensor:' . (int) $sensor['id'],
                'link'       => 'esp32/' . (int) $device['id'],
            ]);
        }

        return $active;
    }

    /**
     * Texto de la notificación de un cambio de estado.
     *
     * @return array{level: int, title: string, text: string}
     */
    public static function message(array $device, array $sensor, array $type, string $state, float $value): array
    {
        $name   = trim((string) ($sensor['label'] ?? '')) ?: (string) $type['name'];
        $unit   = trim((string) ($type['unit'] ?? ''));
        $where  = ' en ' . $device['name'];
        $reading = self::num($value) . ($unit !== '' ? ' ' . $unit : '');

        switch ($state) {
            case self::STATE_HIGH:
                return [
                    'level' => NotificationModel::LEVEL_CRITICAL,
                    'title' => $name . ' por encima del límite',
                    'text'  => $name . $where . ' marcó ' . $reading . ', arriba del máximo de '
                        . self::num((float) $sensor['alert_max']) . ($unit !== '' ? ' ' . $unit : '') . '.',
                ];

            case self::STATE_LOW:
                return [
                    'level' => NotificationModel::LEVEL_WARNING,
                    'title' => $name . ' por debajo del límite',
                    'text'  => $name . $where . ' marcó ' . $reading . ', abajo del mínimo de '
                        . self::num((float) $sensor['alert_min']) . ($unit !== '' ? ' ' . $unit : '') . '.',
                ];

            default:
                return [
                    'level' => NotificationModel::LEVEL_INFO,
                    'title' => $name . ' volvió a la normalidad',
                    'text'  => $name . $where . ' marcó ' . $reading . ', dentro del rango configurado.',
                ];
        }
    }

    /** Etiqueta corta del estado, para la vista. */
    public static function label(?string $state): string
    {
        switch ($state) {
            case self::STATE_HIGH:
                return 'Sobre el límite';
            case self::STATE_LOW:
                return 'Bajo el límite';
            case self::STATE_OK:
                return 'Normal';
            default:
                return 'Sin datos';
        }
    }

    /**
     * Ilustración del motor para el estado (public/images/motors_iso). Cada
     * una tiene su versión "-dark" para el tema oscuro.
     */
    public static function image(string $code, ?string $state): string
    {
        $map = [
            'temperature' => [self::STATE_HIGH => 'temperature-high', self::STATE_LOW => 'temperature-low'],
            'vibration'   => [self::STATE_HIGH => 'vibration-high', self::STATE_LOW => 'vibration-medium'],
            'rpm'         => [self::STATE_HIGH => 'rpm-high', self::STATE_LOW => 'rpm-low'],
        ];

        return $map[$code][$state] ?? 'motor-ok';
    }

    /** "60", "12.5": sin ceros de más. */
    public static function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
