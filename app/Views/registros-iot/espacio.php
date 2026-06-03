<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">
                    <a href="<?= base_url('registros-iot') ?>" class="esp32-back">← Registros IoT</a>
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
                                <span class="badge badge--info"><?= esc($s['device_name']) ?> · <?= esc($s['default_chart']) ?></span>
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
                                    <div><dt>Placa</dt><dd><?= esc($s['device_name']) ?></dd></div>
                                    <div><dt>MAC</dt><dd class="mono"><?= esc($s['mac_address']) ?></dd></div>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── Cambio de sensor (mismo patrón que esp32/detalle) ── */
    const switchers = document.querySelectorAll('[data-section-target]');
    const panes = document.querySelectorAll('[data-section-pane]');

    function activatePane(target) {
        panes.forEach((p) => p.classList.toggle('is-active', p.dataset.sectionPane === target));
        switchers.forEach((input) => {
            const isActive = input.dataset.sectionTarget === target;
            input.checked = isActive;
            input.closest('.section-switch__radio')?.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        loadSensor(target);
    }
    switchers.forEach((input) => input.addEventListener('change', () => activatePane(input.dataset.sectionTarget)));

    /* ── Carga + dibujo de la serie de un sensor ── */
    const loaded = new Set();

    function drawLine(grid, points) {
        const line = grid.querySelector('[data-sensor-line]');
        const dots = grid.querySelector('[data-sensor-points]');
        const empty = grid.querySelector('[data-sensor-empty]');
        dots.innerHTML = '';
        if (!points.length) {
            line.setAttribute('points', '');
            if (empty) empty.hidden = false;
            return;
        }
        if (empty) empty.hidden = true;

        const X0 = 78, X1 = 600, Y0 = 48, Y1 = 304;
        const values = points.map((p) => p.value);
        let min = Math.min(...values), max = Math.max(...values);
        if (min === max) { min -= 1; max += 1; }
        const n = points.length;
        const coords = points.map((p, i) => {
            const x = n === 1 ? (X0 + X1) / 2 : X0 + (X1 - X0) * (i / (n - 1));
            const y = Y1 - (Y1 - Y0) * ((p.value - min) / (max - min));
            return [x, y];
        });
        line.setAttribute('points', coords.map((c) => c[0].toFixed(1) + ',' + c[1].toFixed(1)).join(' '));
        coords.forEach((c) => {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', c[0].toFixed(1));
            circle.setAttribute('cy', c[1].toFixed(1));
            circle.setAttribute('r', '3');
            circle.setAttribute('class', 'iot-line-chart__point');
            dots.appendChild(circle);
        });
    }

    function loadSensor(target) {
        if (loaded.has(target)) return;
        const grid = document.querySelector('[data-section-pane="' + target + '"] .iot-grid');
        if (!grid) return;
        loaded.add(target);

        const sensorId = grid.dataset.sensorId;
        const unit = grid.dataset.sensorUnit || '';
        const code = grid.dataset.sensorCode || '';
        const url = grid.dataset.readingsUrl + '?sensor=' + encodeURIComponent(sensorId);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.ok ? r.json() : Promise.reject(r.status))
            .then((data) => {
                const points = data.points || [];
                drawLine(grid, points);

                const latestEl = grid.querySelector('[data-sensor-latest]');
                const obsEl = grid.querySelector('[data-sensor-observation]');
                const histEl = grid.querySelector('[data-sensor-history]');

                if (data.latest !== null && data.latest !== undefined) {
                    if (latestEl) latestEl.textContent = data.latest + ' ' + unit;
                    let obs = 'Última medición: ' + data.latest + ' ' + unit + '. ' + points.length + ' lecturas registradas.';
                    // El sensor RPM informa además si el motor está andando (RPM > 0).
                    if (code === 'rpm') {
                        obs += ' Motor: ' + (Number(data.latest) > 0 ? 'ANDANDO ▶' : 'DETENIDO ⏸');
                    }
                    if (obsEl) obsEl.textContent = obs;
                } else if (obsEl) {
                    obsEl.textContent = 'Sin lecturas todavía para este sensor.';
                }

                if (histEl) {
                    if (!points.length) {
                        histEl.innerHTML = '<p class="text-muted">Sin historial.</p>';
                    } else {
                        const rows = points.slice(-12).reverse().map((p) =>
                            '<div class="esp32-history__row"><span class="mono">' + p.label + '</span><strong>' + p.value + ' ' + unit + '</strong></div>'
                        ).join('');
                        histEl.innerHTML = rows;
                    }
                }
            })
            .catch(() => {
                loaded.delete(target);
                const histEl = grid.querySelector('[data-sensor-history]');
                if (histEl) histEl.innerHTML = '<p class="text-muted">No se pudieron cargar las lecturas.</p>';
            });
    }

    const first = document.querySelector('.section-pane.is-active');
    if (first) loadSensor(first.dataset.sectionPane);
});
</script>
<?= $this->endSection() ?>
