<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/date-range.css') ?>?v=20260609-1">
<style>
.esp32-qr{display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin:10px 0}
.esp32-qr__code{background:#fff;padding:10px;border-radius:var(--radius-md);border:1px solid var(--hair);line-height:0}
.esp32-qr__code img,.esp32-qr__code canvas{display:block}
.esp32-qr__hints{margin:0;padding-left:18px;color:var(--text-muted);font-size:var(--font-size-sm);display:grid;gap:4px}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">
                    <a href="<?= base_url('esp32') ?>" class="esp32-back">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12 5-5 5 5 5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Mis ESP32
                    </a>
                </p>
                <h1><?= esc($device['name']) ?></h1>
                <p class="dashboard-head__text">
                    <code class="esp32-card__mac"><?= ! empty($device['mac_address']) ? esc($device['mac_address']) : 'Sin vincular' ?></code>
                    · Sector: <?= esc($device['sector'] ?? '—') ?>
                </p>
            </div>
            <div class="dashboard-head__meta">
                <span class="badge <?= ($device['status'] ?? '') === 'online' ? 'badge--success' : 'badge--info' ?>">
                    <span class="dot <?= ($device['status'] ?? '') === 'online' ? 'dot--live' : '' ?>"></span>
                    <?= esc($device['status'] ?? 'offline') ?>
                </span>
                <small class="text-muted mono">Última: <?= esc($device['last_seen_at'] ?? '—') ?></small>
            </div>
        </header>

        <?php if (session('success')): ?>
            <div class="auth-alert auth-alert--success"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <?php if (! empty($deviceToken)): ?>
            <div class="esp32-token-banner card">
                <div>
                    <strong>Token del dispositivo</strong>
                    <p>Configuralo en el firmware. Se envía en cada lectura (header <code>X-Device-Token</code>).</p>
                </div>
                <code class="esp32-token"><?= esc($deviceToken) ?></code>
            </div>
        <?php endif; ?>

        <section class="panel card esp32-token-access">
            <div class="panel__header"><h3>Datos de configuracion</h3></div>
            <dl class="esp32-sensor-meta">
                <div><dt>MAC</dt><dd class="mono"><?= ! empty($device['mac_address']) ? esc($device['mac_address']) : 'Se vincula sola al primer dato' ?></dd></div>
                <div><dt>Estado</dt><dd><?= esc($device['status'] ?? 'offline') ?></dd></div>
                <div><dt>Última lectura</dt><dd class="mono"><?= esc($device['last_seen_at'] ?? '-') ?></dd></div>
            </dl>

            <?php if (! empty($deviceToken)): ?>
                <div class="esp32-setup-box">
                    <strong>Configurá la placa: conectate a PREVCS-CONFIG</strong>
                    <p>Primero conectate al WiFi <code>PREVCS-CONFIG</code> que crea la placa. API, token y nombre ya van cargados; en la placa solo elegís tu red WiFi.</p>
                    <?php if (! empty($setupUrl)): ?>
                        <div class="esp32-qr" data-qr-url="<?= esc($setupUrl) ?>">
                            <div class="esp32-qr__code" data-qr-target></div>
                            <ul class="esp32-qr__hints">
                                <li><strong>Desde el celular:</strong> tocá el botón de abajo.</li>
                                <li><strong>Compu + celular:</strong> escaneá el QR con el celu (ya conectado a PREVCS-CONFIG).</li>
                                <li><strong>Solo compu:</strong> tocá el botón.</li>
                            </ul>
                        </div>
                        <a class="btn btn--primary" href="<?= esc($setupUrl) ?>">Abrir formulario de la ESP32</a>
                        <code class="esp32-token"><?= esc($setupUrl) ?></code>
                    <?php endif; ?>
                </div>
            <?php elseif ($isAdmin): ?>
                <form action="<?= base_url('esp32/' . $device['id'] . '/token') ?>" method="post" class="esp32-token-form">
                    <?= csrf_field() ?>
                    <label class="auth-field">
                        <span class="auth-field__label">Ver token</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password" placeholder="Clave del espacio" required>
                        </span>
                    </label>
                    <button type="submit" class="btn btn--primary">Mostrar token</button>
                </form>
            <?php endif; ?>
        </section>

        <?php if (! empty($sensorCount)): ?>
            <div class="auth-alert auth-alert--success"><?= (int) $sensorCount ?> sensores creados automaticamente.</div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <section class="panel card esp32-add-sensor">
                <div class="panel__header"><h3>Agregar sensor extra</h3></div>
                <?php if ($availableTypes === []): ?>
                    <p class="text-muted">Todos los tipos de sensor ya están asociados a esta placa.</p>
                <?php else: ?>
                    <form action="<?= base_url('esp32/' . $device['id'] . '/sensores') ?>" method="post" class="esp32-sensor-form">
                        <?= csrf_field() ?>
                        <select name="sensor_type_id" class="esp32-select" required>
                            <option value="" disabled selected>Tipo de sensor…</option>
                            <?php foreach ($availableTypes as $t): ?>
                                <option value="<?= esc($t['id']) ?>"><?= esc($t['name']) ?><?= !empty($t['unit']) ? ' (' . esc($t['unit']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="label" class="esp32-input" placeholder="Etiqueta (opcional)" maxlength="150">
                        <button type="submit" class="btn btn--primary">Agregar</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($sensors === []): ?>
            <section class="panel card esp32-empty">
                <h3>Sin sensores</h3>
                <p>Esta ESP32 todavía no tiene sensores asociados.</p>
            </section>
        <?php else: ?>
            <div class="section-switch" role="tablist" aria-label="Sensores de la ESP32">
                <?php foreach ($sensors as $idx => $s): ?>
                    <label class="section-switch__radio" role="tab" aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>">
                        <input type="radio" name="sensor-switch" value="sensor-<?= $s['id'] ?>"
                            data-section-target="sensor-<?= $s['id'] ?>" <?= $idx === 0 ? 'checked' : '' ?>>
                        <span class="section-switch__name"><?= esc($s['label'] ?: $s['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php foreach ($sensors as $idx => $s): ?>
                <section class="section-pane<?= $idx === 0 ? ' is-active' : '' ?>" data-section-pane="sensor-<?= $s['id'] ?>">
                    <div class="iot-grid"
                        data-sensor-id="<?= $s['id'] ?>"
                        data-sensor-unit="<?= esc($s['unit'] ?? '') ?>"
                        data-readings-url="<?= base_url('api/esp32/' . $device['id'] . '/lecturas') ?>">
                        <section class="panel card iot-chart-card">
                            <div class="panel__header">
                                <h3><?= esc($s['name']) ?><?= !empty($s['unit']) ? ' · ' . esc($s['unit']) : '' ?></h3>
                                <div class="iot-chart-toolbar">
                                    <div class="drp" data-date-range></div>
                                    <span class="badge badge--info" data-chart-type><?= esc($s['default_chart']) ?></span>
                                </div>
                            </div>
                            <div class="iot-chart">
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
                                <p class="esp32-chart-empty" data-sensor-empty hidden>Sin lecturas todavía para este sensor.</p>
                            </div>
                        </section>

                        <div class="iot-side-stack">
                            <aside class="panel card">
                                <div class="panel__header"><h3>Resumen</h3></div>
                                <dl class="esp32-sensor-meta">
                                    <div><dt>Descripción</dt><dd>Mediciones de <?= esc(mb_strtolower($s['name'])) ?> reportadas por la ESP32.</dd></div>
                                    <div><dt>Última lectura</dt>
                                        <dd class="mono" data-sensor-latest>
                                            <?= $s['latest_value'] !== null ? esc($s['latest_value']) . ' ' . esc($s['unit'] ?? '') : '—' ?>
                                        </dd>
                                    </div>
                                    <div><dt>Registrada</dt><dd class="mono"><?= esc($s['latest_at'] ?? '—') ?></dd></div>
                                </dl>
                            </aside>

                            <aside class="panel card">
                                <div class="panel__header"><h3>Observaciones</h3></div>
                                <p class="esp32-observations" data-sensor-observation>Esperando datos del sensor…</p>
                            </aside>

                            <aside class="panel card">
                                <div class="panel__header"><h3>Historial</h3></div>
                                <div class="esp32-history" data-sensor-history>
                                    <p class="text-muted">Cargando…</p>
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
<script src="<?= base_url('js/iot-sensor-charts.js') ?>?v=20260609-1" defer></script>
<script src="<?= base_url('js/qrcode.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.querySelector('[data-qr-url]');
    const target = box && box.querySelector('[data-qr-target]');
    if (!box || !target || typeof QRCode === 'undefined') return;
    new QRCode(target, {
        text: box.getAttribute('data-qr-url'),
        width: 168,
        height: 168,
        colorDark: '#2A2C32',
        colorLight: '#FFFFFF',
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>
<?= $this->endSection() ?>
