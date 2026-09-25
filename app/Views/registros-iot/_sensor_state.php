<?php
/**
 * Tarjeta "Estado" de un sensor: la parte visible de las alertas.
 *
 * Muestra el motor ilustrado según el estado (normal, alto, bajo) y el rango
 * configurado. iot-sensor-charts.js la actualiza con cada refresco en vivo,
 * así que un valor fuera de rango se ve sin recargar la página.
 *
 * @var array $s Sensor (code, unit, alert_min, alert_max, alert_state).
 */
use App\Libraries\SensorAlerts;

$state = $s['alert_state'] ?? null;
$image = SensorAlerts::image((string) $s['code'], $state);
$unit  = ! empty($s['unit']) ? ' ' . $s['unit'] : '';
$limits = [];
if (($s['alert_min'] ?? null) !== null) {
    $limits[] = 'mín. ' . SensorAlerts::num((float) $s['alert_min']) . $unit;
}
if (($s['alert_max'] ?? null) !== null) {
    $limits[] = 'máx. ' . SensorAlerts::num((float) $s['alert_max']) . $unit;
}
$badge = [
    SensorAlerts::STATE_HIGH => 'badge--error',
    SensorAlerts::STATE_LOW  => 'badge--warning',
    SensorAlerts::STATE_OK   => 'badge--success',
][$state] ?? 'badge--info';
?>
<aside class="panel card iot-state" data-sensor-state="<?= esc($state ?? 'none') ?>"
       data-sensor-code="<?= esc($s['code']) ?>"
       data-images-base="<?= base_url('images/motors_iso/') ?>">
    <div class="panel__header">
        <h3>Estado</h3>
        <span class="badge <?= $badge ?>" data-sensor-state-badge><?= esc(SensorAlerts::label($state)) ?></span>
    </div>
    <div class="iot-state__art">
        <img class="iot-state__img iot-state__img--light" data-sensor-state-img="light"
             src="<?= base_url('images/motors_iso/' . $image . '.png') ?>" alt="" width="160" height="160" loading="lazy">
        <img class="iot-state__img iot-state__img--dark" data-sensor-state-img="dark"
             src="<?= base_url('images/motors_iso/' . $image . '-dark.png') ?>" alt="" width="160" height="160" loading="lazy">
    </div>
    <p class="iot-state__range" data-sensor-state-range>
        <?= $limits === [] ? 'Sin límites de alerta configurados.' : 'Rango aceptable: ' . esc(implode(' · ', $limits)) . '.' ?>
    </p>
</aside>
