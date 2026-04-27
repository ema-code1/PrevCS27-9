<?= $this->extend('templates/layout') ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Monitoreo de dispositivos</p>
                <h1>Registros IoT</h1>
                <p class="dashboard-head__text">Reportes periodicos de vibracion, RPM y temperatura enviados por ESP32.</p>
            </div>
        </header>

        <section class="kpi-grid">
            <article class="kpi-card card">
                <p>ESP32</p>
                <strong>Online</strong>
            </article>
            <article class="kpi-card card">
                <p>ULTIMO REPORTE</p>
                <strong>06:00</strong>
            </article>
            <article class="kpi-card card">
                <p>VIBRACION</p>
                <strong>9.1</strong>
            </article>
            <article class="kpi-card card">
                <p>RPM</p>
                <strong>1420</strong>
            </article>
            <article class="kpi-card card">
                <p>TEMPERATURA</p>
                <strong>46 C</strong>
            </article>
        </section>

        <div class="section-switch card" role="tablist" aria-label="Parametros IoT">
            <label class="section-switch__radio" role="tab" aria-selected="true">
                <input type="radio" name="iot-section-switch" value="vibracion" data-section-target="vibracion" checked>
                <span class="section-switch__name">Vibracion</span>
            </label>
            <label class="section-switch__radio" role="tab" aria-selected="false">
                <input type="radio" name="iot-section-switch" value="rpm" data-section-target="rpm">
                <span class="section-switch__name">RPM</span>
            </label>
            <label class="section-switch__radio" role="tab" aria-selected="false">
                <input type="radio" name="iot-section-switch" value="temperatura" data-section-target="temperatura">
                <span class="section-switch__name">Temperatura</span>
            </label>
        </div>

        <section class="section-pane is-active" data-section-pane="vibracion">
            <div class="iot-grid">
                <section class="panel card iot-chart-card">
                    <div class="panel__header">
                        <h3>Vibraciones</h3>
                        <span class="badge badge--warning" data-vibration-status>Evaluando</span>
                    </div>

                    <div class="iot-chart" aria-label="Grafico de linea de vibracion">
                        <svg class="iot-line-chart" viewBox="0 0 640 360" role="img" aria-labelledby="vibration-chart-title">
                            <title id="vibration-chart-title">Mediciones periodicas de vibracion</title>
                            <g class="iot-line-chart__grid">
                                <line x1="78" y1="48" x2="600" y2="48"></line>
                                <line x1="78" y1="112" x2="600" y2="112"></line>
                                <line x1="78" y1="176" x2="600" y2="176"></line>
                                <line x1="78" y1="240" x2="600" y2="240"></line>
                                <line x1="78" y1="304" x2="600" y2="304"></line>
                            </g>
                            <g class="iot-line-chart__axis">
                                <text x="44" y="52">10</text>
                                <text x="52" y="116">8</text>
                                <text x="52" y="180">6</text>
                                <text x="52" y="244">4</text>
                                <text x="52" y="308">2</text>
                            </g>
                            <polyline class="iot-line-chart__stroke" data-vibration-line points=""></polyline>
                            <g class="iot-line-chart__points" data-vibration-points></g>
                            <g class="iot-line-chart__labels" data-vibration-labels></g>
                        </svg>
                    </div>
                </section>

                <aside class="panel card iot-status-card">
                    <div class="panel__header">
                        <h3 data-vibration-title>Estado</h3>
                        <span class="badge badge--success" data-vibration-badge>Normal</span>
                    </div>

                    <div class="iot-illustration" data-vibration-illustration="ok" aria-hidden="true">
                        <svg viewBox="0 0 420 300" class="motor-iso motor-iso--ok">
                            <path class="motor-iso__shadow" d="M76 218 L228 134 L346 202 L194 286 Z"></path>
                            <path class="motor-iso__base-top" d="M62 192 L214 106 L364 190 L208 278 Z"></path>
                            <path class="motor-iso__base-side" d="M62 192 L208 278 L208 300 L62 214 Z"></path>
                            <path class="motor-iso__base-face" d="M208 278 L364 190 L364 212 L208 300 Z"></path>
                            <path class="motor-iso__foot" d="M184 204 L252 166 L286 186 L218 226 Z"></path>
                            <path class="motor-iso__foot-side" d="M184 204 L218 226 L218 250 L184 228 Z"></path>
                            <path class="motor-iso__body-side" d="M158 130 L270 66 L342 108 L230 174 Z"></path>
                            <path class="motor-iso__body-front" d="M158 130 L230 174 L230 224 L158 180 Z"></path>
                            <path class="motor-iso__body-back" d="M230 174 L342 108 L342 158 L230 224 Z"></path>
                            <g class="motor-iso__ribs">
                                <path d="M248 164 L338 112"></path>
                                <path d="M262 172 L352 120"></path>
                                <path d="M276 180 L366 128"></path>
                                <path d="M290 188 L380 136"></path>
                                <path d="M304 196 L394 144"></path>
                            </g>
                            <ellipse class="motor-iso__cap" cx="160" cy="176" rx="46" ry="58" transform="rotate(-28 160 176)"></ellipse>
                            <ellipse class="motor-iso__cap-inner" cx="160" cy="176" rx="28" ry="36" transform="rotate(-28 160 176)"></ellipse>
                            <path class="motor-iso__shaft" d="M64 180 L144 136 L158 144 L78 190 Z"></path>
                            <path class="motor-iso__box" d="M134 82 L214 36 L268 66 L188 114 Z"></path>
                            <path class="motor-iso__box-side" d="M134 82 L188 114 L188 154 L134 122 Z"></path>
                            <path class="motor-iso__box-face" d="M188 114 L268 66 L268 104 L188 154 Z"></path>
                            <circle class="motor-iso__bolt" cx="207" cy="124" r="8"></circle>
                            <circle class="motor-iso__bolt" cx="242" cy="104" r="8"></circle>
                            <g class="motor-iso__state motor-iso__state--ok">
                                <circle cx="328" cy="224" r="48"></circle>
                                <path data-vibration-state-path d="M306 226 L322 244 L354 196"></path>
                            </g>
                        </svg>
                    </div>
                </aside>
            </div>
        </section>

        <section class="section-pane" data-section-pane="rpm">
            <div class="iot-metric-grid">
                <article class="panel card metric-panel">
                    <div class="panel__header">
                        <h3>RPM actual</h3>
                        <span class="badge badge--success">Estable</span>
                    </div>
                    <strong>1420</strong>
                    <p>Promedio ficticio del ultimo reporte recibido por la ESP32.</p>
                </article>
                <article class="panel card metric-panel">
                    <div class="panel__header">
                        <h3>Rango operativo</h3>
                        <span class="badge badge--info">Demo</span>
                    </div>
                    <strong>1200 - 1600</strong>
                    <p>Card temporal hasta conectar la tabla real de mediciones.</p>
                </article>
            </div>
        </section>

        <section class="section-pane" data-section-pane="temperatura">
            <div class="iot-metric-grid">
                <article class="panel card metric-panel">
                    <div class="panel__header">
                        <h3>Temperatura actual</h3>
                        <span class="badge badge--warning">Observacion</span>
                    </div>
                    <strong>46 C</strong>
                    <p>Dato ficticio para visualizar el estado del futuro sensor.</p>
                </article>
                <article class="panel card metric-panel">
                    <div class="panel__header">
                        <h3>Promedio diario</h3>
                        <span class="badge badge--info">Demo</span>
                    </div>
                    <strong>42 C</strong>
                    <p>Card de muestra para mantener la estructura del panel.</p>
                </article>
            </div>
        </section>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const switchers = document.querySelectorAll('[data-section-target]');
    const panes = document.querySelectorAll('[data-section-pane]');
    let paneTransitionTimer = null;

    /*
     * Periodo de reporte:
     * Cambia este valor cuando la ESP32 cargue datos cada 3 horas, 6 horas, 24 horas,
     * una semana, etc. Este valor solo etiqueta/agrupa el grafico en la vista.
     * El periodo real de escaneo y guardado se configura en el firmware de la ESP32
     * y en el endpoint/modelo que inserte las mediciones en la base de datos.
     */
    const REPORT_PERIOD_HOURS = 1;

    /*
     * Rangos de tolerancia para vibracion:
     * Ajusta estos umbrales cuando definas la unidad real del sensor.
     * - okMax: valor maximo para mostrar "Todo correcto".
     * - warnMax: valor maximo para mostrar "Vibracion media".
     * - jumpToDangerDelta: salto entre reportes que manda directo a "Alta vibracion".
     * - varianceWarn / varianceDanger: varianza de la serie completa.
     */
    const VIBRATION_TOLERANCE = {
        okMax: 7.4,
        warnMax: 8.6,
        jumpToDangerDelta: 1.4,
        varianceWarn: 0.45,
        varianceDanger: 0.95
    };

    const vibrationReadings = [8.0, 8.1, 7.0, 8.0, 7.95, 9.0];
    const chart = {
        min: 0,
        max: 10,
        left: 78,
        right: 600,
        top: 48,
        bottom: 304
    };

    function updateSwitchState(target) {
        switchers.forEach(function (input) {
            const isActive = input.dataset.sectionTarget === target;
            input.checked = isActive;
            input.closest('.section-switch__radio')?.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function activatePane(target) {
        const nextPane = Array.from(panes).find(function (pane) {
            return pane.dataset.sectionPane === target;
        });
        const activePane = Array.from(panes).find(function (pane) {
            return pane.classList.contains('is-active');
        });

        updateSwitchState(target);

        if (!nextPane || activePane === nextPane) {
            return;
        }

        if (paneTransitionTimer) {
            clearTimeout(paneTransitionTimer);
            panes.forEach(function (pane) {
                pane.classList.remove('is-exiting');
            });
        }

        if (!activePane) {
            nextPane.classList.add('is-active');
            return;
        }

        activePane.classList.add('is-exiting');

        paneTransitionTimer = window.setTimeout(function () {
            activePane.classList.remove('is-active', 'is-exiting');
            nextPane.classList.add('is-active');
            paneTransitionTimer = null;
        }, 150);
    }

    function getPoint(value, index, total) {
        const x = chart.left + ((chart.right - chart.left) / (total - 1)) * index;
        const ratio = (value - chart.min) / (chart.max - chart.min);
        const y = chart.bottom - ratio * (chart.bottom - chart.top);
        return { x, y };
    }

    function getVariance(values) {
        const average = values.reduce(function (sum, value) {
            return sum + value;
        }, 0) / values.length;

        return values.reduce(function (sum, value) {
            return sum + Math.pow(value - average, 2);
        }, 0) / values.length;
    }

    function getMaxJump(values) {
        return values.reduce(function (maxJump, value, index) {
            if (index === 0) {
                return maxJump;
            }

            return Math.max(maxJump, Math.abs(value - values[index - 1]));
        }, 0);
    }

    function getVibrationState(values) {
        const latest = values[values.length - 1];
        const variance = getVariance(values);
        const maxJump = getMaxJump(values);

        if (
            latest > VIBRATION_TOLERANCE.warnMax ||
            variance >= VIBRATION_TOLERANCE.varianceDanger ||
            maxJump >= VIBRATION_TOLERANCE.jumpToDangerDelta
        ) {
            return {
                key: 'danger',
                title: 'Alta vibracion',
                badge: 'Peligro',
                badgeClass: 'badge badge--error',
                illustrationClass: 'iot-illustration is-danger',
                statusClass: 'badge badge--error',
                statePath: 'M328 188 L328 224 M328 248 L328 249'
            };
        }

        if (latest > VIBRATION_TOLERANCE.okMax || variance >= VIBRATION_TOLERANCE.varianceWarn) {
            return {
                key: 'warn',
                title: 'Vibracion media',
                badge: 'Advertencia',
                badgeClass: 'badge badge--warning',
                illustrationClass: 'iot-illustration is-warn',
                statusClass: 'badge badge--warning',
                statePath: 'M328 190 L328 228 M328 252 L328 253'
            };
        }

        return {
            key: 'ok',
            title: 'Todo correcto',
            badge: 'Normal',
            badgeClass: 'badge badge--success',
            illustrationClass: 'iot-illustration is-ok',
            statusClass: 'badge badge--success',
            statePath: 'M306 226 L322 244 L354 196'
        };
    }

    function renderVibrationChart() {
        const line = document.querySelector('[data-vibration-line]');
        const pointsGroup = document.querySelector('[data-vibration-points]');
        const labelsGroup = document.querySelector('[data-vibration-labels]');
        const statusBadge = document.querySelector('[data-vibration-status]');
        const title = document.querySelector('[data-vibration-title]');
        const badge = document.querySelector('[data-vibration-badge]');
        const illustration = document.querySelector('[data-vibration-illustration]');
        const statePath = document.querySelector('[data-vibration-state-path]');

        if (!line || !pointsGroup || !labelsGroup) {
            return;
        }

        const points = vibrationReadings.map(function (value, index) {
            return getPoint(value, index, vibrationReadings.length);
        });

        line.setAttribute('points', points.map(function (point) {
            return point.x.toFixed(1) + ',' + point.y.toFixed(1);
        }).join(' '));

        pointsGroup.innerHTML = points.map(function (point) {
            return '<circle cx="' + point.x.toFixed(1) + '" cy="' + point.y.toFixed(1) + '" r="7"></circle>';
        }).join('');

        labelsGroup.innerHTML = points.map(function (point, index) {
            const label = ((index + 1) * REPORT_PERIOD_HOURS) + 'h';
            return '<text x="' + point.x.toFixed(1) + '" y="336">' + label + '</text>';
        }).join('');

        const state = getVibrationState(vibrationReadings);

        if (statusBadge) {
            statusBadge.textContent = state.badge;
            statusBadge.className = state.statusClass;
        }

        if (title) {
            title.textContent = state.title;
        }

        if (badge) {
            badge.textContent = state.badge;
            badge.className = state.badgeClass;
        }

        if (illustration) {
            illustration.className = state.illustrationClass;
            illustration.dataset.vibrationIllustration = state.key;
        }

        if (statePath) {
            statePath.setAttribute('d', state.statePath);
        }
    }

    switchers.forEach(function (input) {
        input.addEventListener('change', function () {
            activatePane(input.dataset.sectionTarget);
        });
    });

    renderVibrationChart();
});
</script>
<?= $this->endSection() ?>
