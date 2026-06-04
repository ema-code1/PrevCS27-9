<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$onboarding  = $dashboard['onboarding'];
$state       = $dashboard['state'];
$active      = $dashboard['active_industry'];
$kpis        = $dashboard['kpis'];
$activeId    = $active['id'] ?? null;
$showCharts  = $dashboard['trend'] !== null && $dashboard['total_readings'] >= 10;

// El badge "live" solo late si hubo una lectura en los ultimos 10 minutos.
$lastAtTs = isset($kpis['last_reading']['at']) && $kpis['last_reading']['at']
    ? strtotime((string) $kpis['last_reading']['at']) : 0;
$isLive   = $lastAtTs > 0 && $lastAtTs >= time() - 600;
?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Monitoreo industrial en tiempo real</p>
                <h1>Panel</h1>
                <p class="dashboard-head__text">
                    <?php if ($active !== null): ?>
                        Estado operativo de <strong><?= esc($active['space_name']) ?></strong>.
                    <?php else: ?>
                        Creá tu primer espacio para empezar a ver datos reales.
                    <?php endif; ?>
                </p>
            </div>
            <div class="dashboard-head__meta">
                <?php if ($active !== null && $dashboard['space_count'] > 1): ?>
                    <label class="space-switch">
                        <span class="space-switch__label">Espacio</span>
                        <select class="space-switch__select" data-space-switch
                                onchange="window.location.href=this.value">
                            <?php foreach ($industries as $ind): ?>
                                <option value="<?= base_url('principal?espacio=' . $ind['id']) ?>"
                                    <?= (int) $ind['id'] === (int) $activeId ? 'selected' : '' ?>>
                                    <?= esc($ind['space_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php elseif ($active !== null): ?>
                    <span class="badge badge--info"><?= esc($active['industry_uid']) ?></span>
                <?php endif; ?>

                <?php if ($state === 'active'): ?>
                    <span class="badge <?= $isLive ? 'badge--success' : 'badge--info' ?>">
                        <span class="dot <?= $isLive ? 'dot--live' : '' ?>"></span>
                        <?= $isLive ? 'live' : ($kpis['last_reading']['human'] ?? 'sin datos') ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <?php if (session('success')): ?>
            <div class="auth-alert auth-alert--success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <?php /* ───────────── Vista de bienvenida / onboarding ───────────── */ ?>
        <?php if ($state !== 'active'): ?>
            <?php
            $devicesCount = $kpis['devices_active']['total'] ?? 0;
            if (! $onboarding['space']) {
                $step = 'space';
            } elseif ($devicesCount === 0) {
                $step = 'device';
            } else {
                $step = 'reading';
            }
            ?>
            <section class="onboarding-hero card">
                <div class="onboarding-hero__main">
                    <ol class="milestones">
                        <li class="milestone <?= $onboarding['space'] ? 'is-done' : ($step === 'space' ? 'is-next' : '') ?>">
                            <span class="milestone__dot"><?= $onboarding['space'] ? '✓' : '1' ?></span>
                            <span class="milestone__label">Espacio creado</span>
                        </li>
                        <li class="milestone <?= $onboarding['device'] ? 'is-done' : ($step === 'device' ? 'is-next' : '') ?>">
                            <span class="milestone__dot"><?= $onboarding['device'] ? '✓' : '2' ?></span>
                            <span class="milestone__label">Vincular ESP32</span>
                        </li>
                        <li class="milestone <?= $onboarding['reading'] ? 'is-done' : ($step === 'reading' ? 'is-next' : '') ?>">
                            <span class="milestone__dot"><?= $onboarding['reading'] ? '✓' : '3' ?></span>
                            <span class="milestone__label">Primera lectura</span>
                        </li>
                    </ol>

                    <?php if ($step === 'space'): ?>
                        <h2>Creá tu primer espacio</h2>
                        <p>Un espacio representa tu planta o máquina. Generamos un ID único para que tu equipo se sume y empieces a monitorear.</p>
                        <a class="btn btn--primary onboarding-hero__cta" href="<?= base_url('crear-espacio') ?>">Crear espacio</a>
                    <?php elseif ($step === 'device'): ?>
                        <h2>Vinculá tu primera ESP32</h2>
                        <p>Tu espacio está listo. Registrá una placa por su MAC y te daremos el token para cargar en el firmware. En cuanto envíe una lectura, aparecerá acá.</p>
                        <a class="btn btn--primary onboarding-hero__cta" href="<?= base_url('esp32/agregar?from=onboarding') ?>">Vincular primera ESP32</a>
                    <?php else: ?>
                        <h2>Esperando la primera lectura</h2>
                        <p>Tu ESP32 ya está registrada. Cargá el token en el firmware y encendé la placa: en cuanto reciba la primera medición, el panel se llena solo.</p>
                        <div class="onboarding-hero__actions">
                            <a class="btn btn--primary onboarding-hero__cta" href="<?= base_url('esp32') ?>">Ver mis ESP32 y token</a>
                            <a class="btn btn--secondary" href="<?= base_url('principal') ?>">Actualizar</a>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="onboarding-hero__preview" aria-hidden="true">
                    <p class="onboarding-hero__preview-title">Cuando termines vas a ver</p>
                    <div class="preview-kpis">
                        <div class="preview-kpi"><span></span><small>Dispositivos activos</small></div>
                        <div class="preview-kpi"><span></span><small>Sensores reportando</small></div>
                        <div class="preview-kpi"><span></span><small>Lecturas 24h</small></div>
                        <div class="preview-kpi"><span></span><small>Última lectura</small></div>
                    </div>
                    <div class="preview-chart"></div>
                </aside>
            </section>

        <?php else: ?>
            <?php /* ───────────── Banner slim si quedan hitos pendientes ───────────── */ ?>
            <?php if (! $onboarding['complete']): ?>
                <div class="onboarding-banner card" data-onboarding-banner>
                    <div class="onboarding-banner__steps">
                        <span class="milestone-chip is-done">✓ Espacio</span>
                        <span class="milestone-chip <?= $onboarding['device'] ? 'is-done' : '' ?>"><?= $onboarding['device'] ? '✓' : '○' ?> ESP32</span>
                        <span class="milestone-chip <?= $onboarding['reading'] ? 'is-done' : '' ?>"><?= $onboarding['reading'] ? '✓' : '○' ?> Lectura</span>
                    </div>
                    <button type="button" class="onboarding-banner__close" data-onboarding-dismiss aria-label="Ocultar">×</button>
                </div>
            <?php endif; ?>

            <?php /* ───────────── KPIs reales y clickeables ───────────── */ ?>
            <section class="kpi-grid">
                <a class="kpi-card card" href="<?= base_url('esp32') ?>">
                    <p>Dispositivos activos</p>
                    <strong><?= (int) $kpis['devices_active']['value'] ?></strong>
                    <small class="text-muted mono">de <?= (int) $kpis['devices_active']['total'] ?> registrados</small>
                </a>
                <a class="kpi-card card" href="<?= base_url('registros-iot/' . $activeId) ?>">
                    <p>Sensores reportando</p>
                    <strong><?= (int) $kpis['sensors_reporting']['value'] ?></strong>
                    <small class="text-muted mono">de <?= (int) $kpis['sensors_reporting']['total'] ?> · últimas 24h</small>
                </a>
                <a class="kpi-card card" href="<?= base_url('registros-iot/' . $activeId) ?>">
                    <p>Lecturas 24h</p>
                    <strong><?= (int) $kpis['readings_24h']['value'] ?></strong>
                    <small class="text-muted mono">mediciones recibidas</small>
                </a>
                <a class="kpi-card card" href="<?= base_url('registros-iot/' . $activeId) ?>">
                    <p>Última lectura</p>
                    <strong class="kpi-card__time"><?= esc($kpis['last_reading']['human'] ?? '—') ?></strong>
                    <small class="text-muted mono"><?= $kpis['last_reading']['at'] ? esc(date('d/m H:i', strtotime((string) $kpis['last_reading']['at']))) : 'sin datos' ?></small>
                </a>
                <a class="kpi-card card" href="<?= base_url('registros-iot') ?>">
                    <p>Espacios bajo gestión</p>
                    <strong><?= (int) $kpis['space_count']['value'] ?></strong>
                    <small class="text-muted mono">tu cuenta</small>
                </a>
            </section>

            <div class="section-switch" role="tablist" aria-label="Secciones principales">
                <label class="section-switch__radio" role="tab" aria-selected="true">
                    <input type="radio" name="section-switch" value="principal" data-section-target="principal" checked>
                    <span class="section-switch__name">Principal</span>
                </label>
                <?php if ($showCharts): ?>
                <label class="section-switch__radio" role="tab" aria-selected="false">
                    <input type="radio" name="section-switch" value="graficos" data-section-target="graficos">
                    <span class="section-switch__name">Gráficos</span>
                </label>
                <?php endif; ?>
                <label class="section-switch__radio" role="tab" aria-selected="false">
                    <input type="radio" name="section-switch" value="lista" data-section-target="lista">
                    <span class="section-switch__name">Sensores</span>
                </label>
            </div>

            <section class="section-pane is-active" data-section-pane="principal">
                <div class="dashboard-grid dashboard-grid--iot">
                    <section class="panel panel--actividad card">
                        <div class="panel__header">
                            <h3>Actividad reciente</h3>
                            <span class="badge badge--info"><?= count($dashboard['activity']) ?> sensores</span>
                        </div>
                        <div class="event-list event-list--activity">
                            <?php if ($dashboard['activity'] === []): ?>
                                <p class="text-muted">Sin lecturas recientes.</p>
                            <?php else: ?>
                                <?php foreach ($dashboard['activity'] as $a): ?>
                                    <article class="event-item">
                                        <span class="dot <?= $a['fresh'] ? 'dot--success' : 'dot--error' ?>"></span>
                                        <strong><?= esc($a['sensor']) ?></strong>
                                        <small><?= esc($a['device']) ?> · <?= esc($a['human']) ?></small>
                                        <span class="mono"><?= esc($a['value']) ?> <?= esc($a['unit']) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="panel card">
                        <div class="panel__header">
                            <h3>Resumen del espacio</h3>
                            <span class="badge <?= $isLive ? 'badge--success' : 'badge--warning' ?>"><?= $isLive ? 'Operación en vivo' : 'Sin datos recientes' ?></span>
                        </div>
                        <div class="summary-grid">
                            <article class="summary-item">
                                <strong>Dispositivos</strong>
                                <p><?= (int) $kpis['devices_active']['value'] ?> de <?= (int) $kpis['devices_active']['total'] ?> ESP32 activas en las últimas 24h.</p>
                            </article>
                            <article class="summary-item">
                                <strong>Sensores</strong>
                                <p><?= (int) $kpis['sensors_reporting']['value'] ?> de <?= (int) $kpis['sensors_reporting']['total'] ?> sensores reportaron mediciones hoy.</p>
                            </article>
                            <article class="summary-item">
                                <strong>Lecturas</strong>
                                <p><?= (int) $kpis['readings_24h']['value'] ?> mediciones en 24h. Última <?= esc($kpis['last_reading']['human'] ?? '—') ?>.</p>
                            </article>
                        </div>
                    </section>
                </div>
            </section>

            <?php if ($showCharts): ?>
            <section class="section-pane" data-section-pane="graficos">
                <section class="panel card">
                    <div class="panel__header">
                        <h3>Tendencia · <?= esc($dashboard['trend']['sensor']) ?></h3>
                        <span class="badge badge--info"><?= esc($dashboard['trend']['device']) ?> · 7 días</span>
                    </div>
                    <div class="dash-chart">
                        <svg class="iot-line-chart" viewBox="0 0 640 320" role="img" aria-label="Tendencia del sensor más activo">
                            <g class="iot-line-chart__grid">
                                <line x1="20" y1="40" x2="620" y2="40"></line>
                                <line x1="20" y1="100" x2="620" y2="100"></line>
                                <line x1="20" y1="160" x2="620" y2="160"></line>
                                <line x1="20" y1="220" x2="620" y2="220"></line>
                                <line x1="20" y1="280" x2="620" y2="280"></line>
                            </g>
                            <polyline class="iot-line-chart__stroke" data-trend-line points=""></polyline>
                            <g data-trend-points></g>
                        </svg>
                    </div>
                </section>
            </section>
            <?php endif; ?>

            <section class="section-pane" data-section-pane="lista">
                <section class="panel card list-card">
                    <div class="panel__header">
                        <h3>Sensores del espacio</h3>
                        <span class="badge badge--info"><?= count($dashboard['sensors']) ?> items</span>
                    </div>
                    <div class="list-table">
                        <?php if ($dashboard['sensors'] === []): ?>
                            <p class="text-muted">Este espacio todavía no tiene sensores asociados.</p>
                        <?php else: ?>
                            <?php foreach ($dashboard['sensors'] as $s): ?>
                                <?php
                                $fresh = ! empty($s['latest_at']) && strtotime((string) $s['latest_at']) >= time() - 86400;
                                $status = $s['latest_at'] === null ? 'is-alert' : ($fresh ? 'is-ok' : 'is-warn');
                                $statusLabel = $s['latest_at'] === null ? 'Sin datos' : ($fresh ? 'OK' : 'Atrasado');
                                ?>
                                <a class="list-row" href="<?= base_url('registros-iot/' . $activeId) ?>">
                                    <span class="list-row__status <?= $status ?>"></span>
                                    <strong><?= esc($s['label'] ?: $s['name']) ?></strong>
                                    <span><?= esc($s['device_name']) ?></span>
                                    <span><?= $s['latest_value'] !== null ? esc($s['latest_value']) . ' ' . esc($s['unit'] ?? '') : $statusLabel ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </section>
        <?php endif; ?>
    </section>
</main>

<?php if ($state === 'active' && $showCharts): ?>
<script type="application/json" data-trend-data>
<?= json_encode($dashboard['trend']['points'], JSON_UNESCAPED_UNICODE) ?>
</script>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── Banner de onboarding: ocultable y recordado en localStorage ── */
    (function () {
        const banner = document.querySelector('[data-onboarding-banner]');
        if (!banner) return;
        const KEY = 'pcs-onboarding-banner-hidden';
        if (localStorage.getItem(KEY) === '1') { banner.style.display = 'none'; return; }
        const close = banner.querySelector('[data-onboarding-dismiss]');
        if (close) close.addEventListener('click', function () {
            banner.style.display = 'none';
            localStorage.setItem(KEY, '1');
        });
    })();

    /* ── Section switch (mismo patrón pulido del resto del sistema) ── */
    const switchers = document.querySelectorAll('[data-section-target]');
    const panes = document.querySelectorAll('[data-section-pane]');
    let paneTransitionTimer = null;

    function updateSwitchState(target) {
        switchers.forEach((input) => {
            const isActive = input.dataset.sectionTarget === target;
            input.checked = isActive;
            input.closest('.section-switch__radio')?.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function activatePane(target) {
        const nextPane  = Array.from(panes).find((p) => p.dataset.sectionPane === target);
        const activePane = Array.from(panes).find((p) => p.classList.contains('is-active'));
        updateSwitchState(target);
        if (!nextPane || activePane === nextPane) return;
        if (paneTransitionTimer) {
            clearTimeout(paneTransitionTimer);
            panes.forEach((p) => p.classList.remove('is-exiting'));
        }
        if (!activePane) { nextPane.classList.add('is-active'); return; }
        activePane.classList.add('is-exiting');
        paneTransitionTimer = window.setTimeout(() => {
            activePane.classList.remove('is-active', 'is-exiting');
            nextPane.classList.add('is-active');
            paneTransitionTimer = null;
        }, 150);
    }

    switchers.forEach((input) => {
        input.addEventListener('change', () => activatePane(input.dataset.sectionTarget));
    });

    /* ── Tendencia: dibuja la serie real del sensor más activo ── */
    (function () {
        const dataEl = document.querySelector('[data-trend-data]');
        const line = document.querySelector('[data-trend-line]');
        const dotsG = document.querySelector('[data-trend-points]');
        if (!dataEl || !line || !dotsG) return;
        let points;
        try { points = JSON.parse(dataEl.textContent || '[]'); } catch (e) { return; }
        if (!points.length) return;

        const X0 = 20, X1 = 620, Y0 = 40, Y1 = 280;
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
            circle.setAttribute('r', '2.5');
            circle.setAttribute('class', 'iot-line-chart__point');
            dotsG.appendChild(circle);
        });
    })();
});
</script>
<?= $this->endSection() ?>
