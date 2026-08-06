<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>?v=20260612-2">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>?v=20260612-2">
<link rel="stylesheet" href="<?= base_url('css/date-range.css') ?>?v=20260609-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">
                    <a href="<?= base_url('registros-iot') ?>" class="esp32-back">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12 5-5 5 5 5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Mis espacios
                    </a>
                </p>
                <h1><?= esc($industry['space_name']) ?></h1>
                <p class="dashboard-head__text">
                    <?= esc($industry['company_name']) ?>
                    · <code class="esp32-card__mac"><?= esc($industry['industry_uid']) ?></code>
                    · <?= (int) $deviceCount ?> ESP32
                </p>
            </div>
            <div class="dashboard-head__meta">
                <span class="badge badge--info"><?= count($sensors) ?> sensor<?= count($sensors) === 1 ? '' : 'es' ?></span>
            </div>
        </header>

        <section class="panel card esp32-space-data">
            <div class="panel__header">
                <h3>Datos del espacio</h3>
                <?php if (! empty($isAdmin)): ?>
                    <a class="btn btn--primary" href="<?= base_url('esp32/agregar?industry_id=' . $industry['id']) ?>">Vincular ESP32</a>
                <?php endif; ?>
            </div>
            <dl class="esp32-sensor-meta">
                <div><dt>ID</dt><dd class="mono"><?= esc($industry['industry_uid']) ?></dd></div>
                <div><dt>Industria</dt><dd><?= esc($industry['company_name']) ?></dd></div>
                <div><dt>Espacio</dt><dd><?= esc($industry['space_name']) ?></dd></div>
                <div><dt>ESP32</dt><dd><?= (int) $deviceCount ?></dd></div>
            </dl>

            <?php if (! empty($devices)): ?>
                <div class="esp32-mini-list">
                    <?php foreach ($devices as $device): ?>
                        <a href="<?= base_url('esp32/' . $device['id']) ?>" class="esp32-mini-row">
                            <strong><?= esc($device['name']) ?></strong>
                            <code><?= esc($device['mac_address']) ?></code>
                            <span><?= esc($device['status'] ?? 'offline') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($sensors === []): ?>
            <section class="panel card esp32-empty">
                <h3>Sin sensores en este espacio</h3>
                <p>Asociá una ESP32 y sus sensores para ver registros.
                   Andá a <a href="<?= base_url('esp32/agregar') ?>">Añadir ESP</a>.</p>
            </section>
        <?php else: ?>
            <div class="section-switch" role="tablist" aria-label="Sensores del espacio">
                <?php foreach ($sensors as $idx => $s): ?>
                    <label class="section-switch__radio" role="tab" aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>">
                        <input type="radio" name="sensor-switch" value="sensor-<?= $s['id'] ?>"
                            data-section-target="sensor-<?= $s['id'] ?>" <?= $idx === 0 ? 'checked' : '' ?>>
                        <span class="section-switch__name">
                            <?= esc($s['device_name']) ?> · <?= esc($s['label'] ?: $s['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php foreach ($sensors as $idx => $s): ?>
                <section class="section-pane<?= $idx === 0 ? ' is-active' : '' ?>" data-section-pane="sensor-<?= $s['id'] ?>">
                    <div class="iot-grid"
                        data-sensor-id="<?= $s['id'] ?>"
                        data-sensor-code="<?= esc($s['code']) ?>"
                        data-sensor-unit="<?= esc($s['unit'] ?? '') ?>"
                        data-readings-url="<?= base_url('api/espacio/' . $industry['id'] . '/lecturas') ?>">
                        <section class="panel card iot-chart-card">
                            <div class="panel__header">
                                <h3><?= esc($s['name']) ?><?= !empty($s['unit']) ? ' · ' . esc($s['unit']) : '' ?></h3>
                                <div class="iot-chart-toolbar">
                                    <div class="drp" data-date-range></div>
                                    <span class="badge badge--info"><?= esc($s['device_name']) ?> · <?= esc($s['default_chart']) ?></span>
                                </div>
                            </div>
                            <div class="iot-chart is-skeleton">
                                <svg class="iot-line-chart" viewBox="0 0 640 360" role="img" aria-label="Gráfico de <?= esc($s['name']) ?>">
                                    <g class="iot-line-chart__grid">
                                        <line x1="78" y1="48" x2="600" y2="48"></line>
                                        <line x1="78" y1="112" x2="600" y2="112"></line>
                                        <line x1="78" y1="176" x2="600" y2="176"></line>
                                        <line x1="78" y1="240" x2="600" y2="240"></line>
                                        <line x1="78" y1="304" x2="600" y2="304"></line>
                                    </g>
                                    <g class="iot-line-chart__grid iot-line-chart__grid--v" data-grid-v></g>
                                    <g class="iot-line-chart__axis iot-line-chart__axis--y" data-axis-y></g>
                                    <g class="iot-line-chart__axis iot-line-chart__axis--x" data-axis-x></g>
                                    <text class="iot-line-chart__axis-title" x="18" y="176" transform="rotate(-90 18 176)" text-anchor="middle">Valores<?= !empty($s['unit']) ? ' · ' . esc($s['unit']) : '' ?></text>
                                    <text class="iot-line-chart__axis-title" x="339" y="350" text-anchor="middle">Tiempo</text>
                                    <polyline class="iot-line-chart__stroke" data-sensor-line points=""></polyline>
                                    <g data-sensor-points></g>
                                </svg>
                                <div class="iot-chart__skeleton" data-sensor-skeleton aria-hidden="true">
                                    <span class="iot-skel" style="height:42%"></span>
                                    <span class="iot-skel" style="height:64%"></span>
                                    <span class="iot-skel" style="height:50%"></span>
                                    <span class="iot-skel" style="height:74%"></span>
                                    <span class="iot-skel" style="height:58%"></span>
                                    <span class="iot-skel" style="height:82%"></span>
                                    <span class="iot-skel" style="height:46%"></span>
                                    <span class="iot-skel" style="height:66%"></span>
                                </div>
                                <div class="iot-chart__error" data-sensor-error hidden>
                                    <p>No se pudieron cargar las lecturas. Revisá tu conexión.</p>
                                    <button type="button" class="btn btn--secondary btn--sm" data-sensor-retry>Reintentar</button>
                                </div>
                                <p class="esp32-chart-empty" data-sensor-empty hidden>Sin lecturas todavía para este sensor.</p>
                            </div>
                        </section>

                        <div class="iot-side-stack">
                            <aside class="panel card">
                                <div class="panel__header"><h3>Resumen</h3></div>
                                <dl class="esp32-sensor-meta">
                                    <div><dt>Placá</dt><dd><?= esc($s['device_name']) ?></dd></div>
                                    <div><dt>MAC</dt><dd class="mono"><?= esc($s['mac_address']) ?></dd></div>
                                    <div><dt>Última lectura</dt>
                                        <dd class="mono" data-sensor-latest>
                                            <?= $s['latest_value'] !== null ? esc($s['latest_value']) . ' ' . esc($s['unit'] ?? '') : '—' ?>
                                        </dd>
                                    </div>
                                    <div><dt>Registrada</dt><dd class="mono" data-sensor-latest-at><?= esc($s['latest_at'] ?? '—') ?></dd></div>
                                </dl>
                            </aside>

                            <aside class="panel card">
                                <div class="panel__header"><h3>Observaciones</h3></div>
                                <p class="esp32-observations" data-sensor-observation>Esperando datos del sensor…</p>
                            </aside>

                            <aside class="panel card">
                                <div class="panel__header"><h3>Historial</h3></div>
                                <div class="esp32-history" data-sensor-history>
                                    <div class="iot-skel-rows" aria-hidden="true">
                                        <span class="iot-skel"></span>
                                        <span class="iot-skel"></span>
                                        <span class="iot-skel"></span>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/date-range-picker.js') ?>?v=20260609-1" defer></script>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260806-1" defer></script>
<script src="<?= base_url('js/iot-sensor-charts.js') ?>?v=20260612-1" defer></script>
<?= $this->endSection() ?>
