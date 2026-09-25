<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>?v=20260924-2">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>?v=20260924-2">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$dashboard = $dashboard ?? [];
$state = $dashboard['state'] ?? 'no_space';
$lastReading = $dashboard['last_reading_at'] ?? null;
$alerts = $dashboard['alerts'] ?? [];
?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Hoy en planta</p>
                <h1>Panel IoT</h1>
                <p class="dashboard-head__text">Datos reales de espacios, ESP32 y sensores.</p>
            </div>
            <div class="dashboard-head__meta">
                <span class="badge <?= $lastReading ? 'badge--success' : 'badge--info' ?>">
                    <span class="dot <?= $lastReading ? 'dot--live' : '' ?>"></span>
                    <?= $lastReading ? 'recibiendo datos' : 'sin lecturas' ?>
                </span>
            </div>
        </header>

        <?php if ($state !== 'active'): ?>
            <section class="panel card onboarding-status">
                <div class="onboarding-status__steps">
                    <span class="<?= $dashboard['spaces_count'] > 0 ? 'is-done' : 'is-current' ?>">1. Crear espacio</span>
                    <span class="<?= $dashboard['devices_count'] > 0 ? 'is-done' : ($dashboard['spaces_count'] > 0 ? 'is-current' : '') ?>">2. Vincular ESP32</span>
                    <span class="<?= $dashboard['total_readings'] > 0 ? 'is-done' : ($dashboard['devices_count'] > 0 ? 'is-current' : '') ?>">3. Recibir lectura</span>
                </div>

                <?php if ($state === 'no_space'): ?>
                    <h2>Primero creá tu espacio</h2>
                    <p>Después vas a vincular la ESP32 y ver lecturas acá.</p>
                    <a class="btn btn--primary" href="<?= base_url('crear-espacio') ?>">Crear espacio</a>
                <?php elseif ($state === 'no_device'): ?>
                    <h2>Espacio listo</h2>
                    <p>Ahora vincula tu primera ESP32. No vas a volver a elegir sensores.</p>
                    <a class="btn btn--primary" href="<?= base_url('esp32/agregar') ?>">Vincular ESP32</a>
                <?php else: ?>
                    <h2>ESP32 lista</h2>
                    <p>Falta la primera lectura. Entrá a la placa y escaneá el QR de vinculación desde la configuración.</p>
                    <a class="btn btn--primary" href="<?= base_url('registros-iot') ?>">Ver ESP32</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="kpi-grid kpi-grid--six">
            <a class="kpi-card card" href="<?= base_url('registros-iot') ?>">
                <p>Espacios</p>
                <strong><?= (int) $dashboard['spaces_count'] ?></strong>
                <small class="text-muted mono">creados</small>
            </a>
            <a class="kpi-card card" href="<?= base_url('registros-iot') ?>">
                <p>ESP32</p>
                <strong><?= (int) $dashboard['devices_count'] ?></strong>
                <small class="text-muted mono"><?= (int) $dashboard['active_devices'] ?> activas 24h</small>
            </a>
            <a class="kpi-card card" href="<?= base_url('registros-iot') ?>">
                <p>Sensores</p>
                <strong><?= (int) $dashboard['sensors_count'] ?></strong>
                <small class="text-muted mono">configurados</small>
            </a>
            <a class="kpi-card card" href="<?= base_url('registros-iot') ?>">
                <p>Lecturas 24h</p>
                <strong><?= (int) $dashboard['readings_24h'] ?></strong>
                <small class="text-muted mono">últimas 24h</small>
            </a>
            <article class="kpi-card card">
                <p>Última lectura</p>
                <strong class="kpi-card__small"><?= esc($lastReading ? date('d/m H:i', strtotime($lastReading)) : '-') ?></strong>
                <small class="text-muted mono">hora local</small>
            </article>
            <article class="kpi-card card<?= $alerts !== [] ? ' kpi-card--alert' : '' ?>">
                <p>Alertas activas</p>
                <strong><?= count($alerts) ?></strong>
                <small class="text-muted mono"><?= $alerts === [] ? 'todo en rango' : 'sensores fuera de rango' ?></small>
            </article>
        </section>

        <?php if ($alerts !== []): ?>
            <section class="panel card list-card dashboard-alerts">
                <div class="panel__header">
                    <h3>Sensores fuera de rango</h3>
                    <span class="badge badge--error"><?= count($alerts) ?> alerta<?= count($alerts) === 1 ? '' : 's' ?></span>
                </div>
                <div class="list-table">
                    <?php foreach ($alerts as $sensor): ?>
                        <?php $isHigh = $sensor['alert_state'] === \App\Libraries\SensorAlerts::STATE_HIGH; ?>
                        <a class="list-row list-row--link" href="<?= base_url('esp32/' . $sensor['esp32_device_id']) ?>">
                            <span class="list-row__status <?= $isHigh ? 'is-alert' : 'is-warn' ?>"></span>
                            <strong><?= esc($sensor['label'] ?: $sensor['name']) ?></strong>
                            <span><?= esc($sensor['device_name']) ?></span>
                            <span>
                                <?= esc(\App\Libraries\SensorAlerts::num((float) $sensor['latest_value'])) . ' ' . esc($sensor['unit'] ?? '') ?>
                                · <?= $isHigh
                                    ? 'máx. ' . esc(\App\Libraries\SensorAlerts::num((float) $sensor['alert_max']))
                                    : 'mín. ' . esc(\App\Libraries\SensorAlerts::num((float) $sensor['alert_min'])) ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="panel card list-card">
            <div class="panel__header">
                <h3>Sensores recientes</h3>
                <span class="badge badge--info"><?= count($dashboard['sensors']) ?> sensores</span>
            </div>

            <?php if (($dashboard['sensors'] ?? []) === []): ?>
                <p class="text-muted">Todavía no hay sensores para mostrar.</p>
            <?php else: ?>
                <div class="list-table">
                    <?php foreach (array_slice($dashboard['sensors'], 0, 10) as $sensor): ?>
                        <a class="list-row list-row--link" href="<?= base_url('esp32/' . $sensor['esp32_device_id']) ?>">
                            <?php
                            $status = match ($sensor['alert_state'] ?? null) {
                                \App\Libraries\SensorAlerts::STATE_HIGH => 'is-alert',
                                \App\Libraries\SensorAlerts::STATE_LOW  => 'is-warn',
                                default => $sensor['latest_at'] ? 'is-ok' : 'is-warn',
                            };
                            ?>
                            <span class="list-row__status <?= $status ?>"></span>
                            <strong><?= esc($sensor['label'] ?: $sensor['name']) ?></strong>
                            <span><?= esc($sensor['device_name']) ?></span>
                            <span><?= $sensor['latest_value'] !== null ? esc(\App\Libraries\SensorAlerts::num((float) $sensor['latest_value'])) . ' ' . esc($sensor['unit'] ?? '') : 'sin datos' ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>
<?= $this->endSection() ?>
