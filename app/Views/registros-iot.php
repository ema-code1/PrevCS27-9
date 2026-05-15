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

        <!-- KPIs NO funcionales -->
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
                <strong data-iot-kpi="vibration">3.2</strong>
            </article>
            <article class="kpi-card card">
                <p>RPM</p>
                <strong data-iot-kpi="rpm">1420</strong>
            </article>
            <article class="kpi-card card">
                <p>TEMPERATURA</p>
                <strong data-iot-kpi="temperature">41 C</strong>
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
            <div class="iot-grid" data-iot-monitor="vibration">
                <section class="panel card iot-chart-card">
                    <div class="panel__header">
                        <h3>Vibraciones</h3>
                        <span class="badge badge--success" data-iot-chart-status>Normal</span>
                    </div>

                    <div class="iot-date-controls" data-iot-date-controls>
                        <label class="iot-date-field">
                            <span>Rango</span>
                            <select class="iot-status-select iot-range-select" data-iot-range-select aria-label="Seleccionar rango de vibracion">
                                <option value="day">Dia</option>
                                <option value="week">Semana</option>
                                <option value="month">Mes</option>
                            </select>
                        </label>
                        <label class="iot-date-field">
                            <span>Desde</span>
                            <input class="iot-date-input" type="text" data-iot-start-date aria-label="Fecha inicial de vibracion" readonly>
                        </label>
                        <label class="iot-date-field">
                            <span>Hasta</span>
                            <input class="iot-date-input" type="text" data-iot-end-date aria-label="Fecha final de vibracion" readonly>
                        </label>
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
                            <g class="iot-line-chart__axis" data-iot-axis></g>
                            <polyline class="iot-line-chart__stroke" data-iot-line points=""></polyline>
                            <g class="iot-line-chart__points" data-iot-points></g>
                            <g class="iot-line-chart__labels" data-iot-labels></g>
                        </svg>
                    </div>
                </section>

                <div class="iot-side-stack">
                    <aside class="panel card iot-control-card">
                        <div class="panel__header">
                            <h3 data-iot-status-title>Todo OK</h3>
                            <span class="badge badge--success" data-iot-status-badge>Todo OK</span>
                        </div>

                        <label class="iot-status-control">
                            <span>Estado del motor</span>
                            <select class="iot-status-select" data-iot-state-select aria-label="Seleccionar estado de vibracion">
                                <option value="ok">Todo OK</option>
                                <option value="medium">Vibraciones minimas</option>
                                <option value="high">Vibraciones altas</option>
                            </select>
                        </label>
                    </aside>

                    <aside class="panel card iot-status-card">
                        <div class="iot-illustration iot-illustration--vibration" data-iot-illustration aria-hidden="true">
                            <img class="iot-illustration__image" data-iot-illustration-image src="<?= base_url('images/motors_iso/motor-ok.png') ?>" alt="">
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <section class="section-pane" data-section-pane="rpm">
            <div class="iot-grid" data-iot-monitor="rpm">
                <section class="panel card iot-chart-card">
                    <div class="panel__header">
                        <h3>RPM</h3>
                        <span class="badge badge--success" data-iot-chart-status>Estable</span>
                    </div>

                    <div class="iot-date-controls" data-iot-date-controls>
                        <label class="iot-date-field">
                            <span>Rango</span>
                            <select class="iot-status-select iot-range-select" data-iot-range-select aria-label="Seleccionar rango de RPM">
                                <option value="day">Dia</option>
                                <option value="week">Semana</option>
                                <option value="month">Mes</option>
                            </select>
                        </label>
                        <label class="iot-date-field">
                            <span>Desde</span>
                            <input class="iot-date-input" type="text" data-iot-start-date aria-label="Fecha inicial de RPM" readonly>
                        </label>
                        <label class="iot-date-field">
                            <span>Hasta</span>
                            <input class="iot-date-input" type="text" data-iot-end-date aria-label="Fecha final de RPM" readonly>
                        </label>
                    </div>

                    <div class="iot-chart" aria-label="Grafico de linea de RPM">
                        <svg class="iot-line-chart" viewBox="0 0 640 360" role="img" aria-labelledby="rpm-chart-title">
                            <title id="rpm-chart-title">Mediciones periodicas de RPM</title>
                            <g class="iot-line-chart__grid">
                                <line x1="78" y1="48" x2="600" y2="48"></line>
                                <line x1="78" y1="112" x2="600" y2="112"></line>
                                <line x1="78" y1="176" x2="600" y2="176"></line>
                                <line x1="78" y1="240" x2="600" y2="240"></line>
                                <line x1="78" y1="304" x2="600" y2="304"></line>
                            </g>
                            <g class="iot-line-chart__axis" data-iot-axis></g>
                            <polyline class="iot-line-chart__stroke" data-iot-line points=""></polyline>
                            <g class="iot-line-chart__points" data-iot-points></g>
                            <g class="iot-line-chart__labels" data-iot-labels></g>
                        </svg>
                    </div>
                </section>

                <div class="iot-side-stack">
                    <aside class="panel card iot-control-card">
                        <div class="panel__header">
                            <h3 data-iot-status-title>Revoluciones normales</h3>
                            <span class="badge badge--success" data-iot-status-badge>Normal</span>
                        </div>

                        <label class="iot-status-control">
                            <span>Estado del motor</span>
                            <select class="iot-status-select" data-iot-state-select aria-label="Seleccionar estado de RPM">
                                <option value="ok">Revoluciones normales</option>
                                <option value="low">Revoluciones bajas</option>
                                <option value="high">Revoluciones altas</option>
                            </select>
                        </label>
                    </aside>

                    <aside class="panel card iot-status-card">
                        <div class="iot-illustration iot-illustration--rpm" data-iot-illustration aria-hidden="true">
                            <img class="iot-illustration__image" data-iot-illustration-image src="<?= base_url('images/motors_iso/motor-ok.png') ?>" alt="">
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <section class="section-pane" data-section-pane="temperatura">
            <div class="iot-grid" data-iot-monitor="temperature">
                <section class="panel card iot-chart-card">
                    <div class="panel__header">
                        <h3>Temperatura</h3>
                        <span class="badge badge--success" data-iot-chart-status>Normal</span>
                    </div>

                    <div class="iot-date-controls" data-iot-date-controls>
                        <label class="iot-date-field">
                            <span>Rango</span>
                            <select class="iot-status-select iot-range-select" data-iot-range-select aria-label="Seleccionar rango de temperatura">
                                <option value="day">Dia</option>
                                <option value="week">Semana</option>
                                <option value="month">Mes</option>
                            </select>
                        </label>
                        <label class="iot-date-field">
                            <span>Desde</span>
                            <input class="iot-date-input" type="text" data-iot-start-date aria-label="Fecha inicial de temperatura" readonly>
                        </label>
                        <label class="iot-date-field">
                            <span>Hasta</span>
                            <input class="iot-date-input" type="text" data-iot-end-date aria-label="Fecha final de temperatura" readonly>
                        </label>
                    </div>

                    <div class="iot-chart" aria-label="Grafico de linea de temperatura">
                        <svg class="iot-line-chart" viewBox="0 0 640 360" role="img" aria-labelledby="temperature-chart-title">
                            <title id="temperature-chart-title">Mediciones periodicas de temperatura</title>
                            <g class="iot-line-chart__grid">
                                <line x1="78" y1="48" x2="600" y2="48"></line>
                                <line x1="78" y1="112" x2="600" y2="112"></line>
                                <line x1="78" y1="176" x2="600" y2="176"></line>
                                <line x1="78" y1="240" x2="600" y2="240"></line>
                                <line x1="78" y1="304" x2="600" y2="304"></line>
                            </g>
                            <g class="iot-line-chart__axis" data-iot-axis></g>
                            <polyline class="iot-line-chart__stroke" data-iot-line points=""></polyline>
                            <g class="iot-line-chart__points" data-iot-points></g>
                            <g class="iot-line-chart__labels" data-iot-labels></g>
                        </svg>
                    </div>
                </section>

                <div class="iot-side-stack">
                    <aside class="panel card iot-control-card">
                        <div class="panel__header">
                            <h3 data-iot-status-title>Temperatura normal</h3>
                            <span class="badge badge--success" data-iot-status-badge>Normal</span>
                        </div>

                        <label class="iot-status-control">
                            <span>Estado del motor</span>
                            <select class="iot-status-select" data-iot-state-select aria-label="Seleccionar estado de temperatura">
                                <option value="ok">Temperatura normal</option>
                                <option value="low">Temperatura baja</option>
                                <option value="high">Temperatura alta</option>
                            </select>
                        </label>
                    </aside>

                    <aside class="panel card iot-status-card">
                        <div class="iot-illustration iot-illustration--temperature" data-iot-illustration aria-hidden="true">
                            <img class="iot-illustration__image" data-iot-illustration-image src="<?= base_url('images/motors_iso/motor-ok.png') ?>" alt="">
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    // Control de Imagnes Iso.
document.addEventListener('DOMContentLoaded', function () {
    const switchers = document.querySelectorAll('[data-section-target]');
    const panes = document.querySelectorAll('[data-section-pane]');
    const monitors = document.querySelectorAll('[data-iot-monitor]');
    const body = document.body;
    let paneTransitionTimer = null;

    const assetBase = '<?= rtrim(base_url('images/motors_iso'), '/') ?>';
    const readingsEndpoint = '<?= base_url('api/iot-readings') ?>';
    const REPORT_PERIOD_HOURS = 1;
    const chart = {
        left: 78,
        right: 600,
        top: 48,
        bottom: 304
    };

    const monitorConfig = {
        vibration: {
            kpi: 'vibration',
            min: 0,
            max: 10,
            unit: '',
            axis: [10, 8, 6, 4, 2],
            states: {
                ok: {
                    title: 'Todo OK',
                    badge: 'Todo OK',
                    badgeClass: 'badge badge--success',
                    image: 'motor-ok.png',
                    darkImage: 'motor-ok-dark.png',
                    readings: [3.0, 3.2, 2.9, 3.4, 3.1, 3.2]
                },
                medium: {
                    title: 'Vibraciones minimas',
                    badge: 'Minimas',
                    badgeClass: 'badge badge--warning',
                    image: 'vibration-medium.png',
                    darkImage: 'vibration-medium-dark.png',
                    readings: [4.6, 5.1, 5.8, 5.4, 6.2, 6.0]
                },
                high: {
                    title: 'Vibraciones altas',
                    badge: 'Alerta',
                    badgeClass: 'badge badge--error',
                    image: 'vibration-high.png',
                    darkImage: 'vibration-high-dark.png',
                    readings: [6.9, 7.8, 8.4, 8.1, 9.2, 9.5]
                }
            }
        },
        rpm: {
            kpi: 'rpm',
            min: 800,
            max: 2200,
            unit: '',
            axis: [2200, 1850, 1500, 1150, 800],
            states: {
                ok: {
                    title: 'Revoluciones normales',
                    badge: 'Normal',
                    badgeClass: 'badge badge--success',
                    image: 'motor-ok.png',
                    darkImage: 'motor-ok-dark.png',
                    readings: [1410, 1440, 1390, 1460, 1425, 1430]
                },
                low: {
                    title: 'Revoluciones bajas',
                    badge: 'Bajas',
                    badgeClass: 'badge badge--warning',
                    image: 'rpm-low.png',
                    darkImage: 'rpm-low-dark.png',
                    readings: [1150, 1080, 1025, 980, 940, 900]
                },
                high: {
                    title: 'Revoluciones altas',
                    badge: 'Alerta',
                    badgeClass: 'badge badge--error',
                    image: 'rpm-high.png',
                    darkImage: 'rpm-high-dark.png',
                    readings: [1620, 1740, 1860, 1910, 2030, 2100]
                }
            }
        },
        temperature: {
            kpi: 'temperature',
            min: 0,
            max: 90,
            unit: ' C',
            axis: [90, 68, 45, 23, 0],
            states: {
                ok: {
                    title: 'Temperatura normal',
                    badge: 'Normal',
                    badgeClass: 'badge badge--success',
                    image: 'motor-ok.png',
                    darkImage: 'motor-ok-dark.png',
                    readings: [39, 40, 42, 41, 43, 41]
                },
                low: {
                    title: 'Temperatura baja',
                    badge: 'Baja',
                    badgeClass: 'badge badge--warning',
                    image: 'temperature-low.png',
                    darkImage: 'temperature-low-dark.png',
                    readings: [31, 28, 25, 22, 19, 17]
                },
                high: {
                    title: 'Temperatura alta',
                    badge: 'Alerta',
                    badgeClass: 'badge badge--error',
                    image: 'temperature-high.png',
                    darkImage: 'temperature-high-dark.png',
                    readings: [54, 61, 66, 72, 77, 82]
                }
            }
        }
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

    function getPoint(value, index, total, config) {
        const x = chart.left + ((chart.right - chart.left) / (total - 1)) * index;
        const ratio = Math.max(0, Math.min(1, (value - config.min) / (config.max - config.min)));
        const y = chart.bottom - ratio * (chart.bottom - chart.top);
        return { x, y };
    }

    function getThemedImage(state) {
        return body.getAttribute('data-theme') === 'dark' ? state.darkImage : state.image;
    }

    function renderChartAxis(panel, config) {
        const axisGroup = panel.querySelector('[data-iot-axis]');

        if (!axisGroup) {
            return;
        }

        axisGroup.innerHTML = config.axis.map(function (value, index) {
            const y = 52 + (64 * index);
            return '<text x="60" y="' + y + '">' + value + '</text>';
        }).join('');
    }

    function toDateInputValue(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function toDisplayDate(isoDate) {
        const parts = isoDate.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function parseIsoDate(isoDate) {
        return new Date(isoDate + 'T00:00:00');
    }

    function addDays(date, days) {
        const nextDate = new Date(date);
        nextDate.setDate(nextDate.getDate() + days);
        return nextDate;
    }

    function getRangeLength(range) {
        if (range === 'month') {
            return 30;
        }

        if (range === 'week') {
            return 7;
        }

        return 1;
    }

    function syncDateControls(panel) {
        const rangeSelect = panel.querySelector('[data-iot-range-select]');
        const startInput = panel.querySelector('[data-iot-start-date]');
        const endInput = panel.querySelector('[data-iot-end-date]');

        if (!rangeSelect || !startInput || !endInput) {
            return null;
        }

        if (!startInput.dataset.iso) {
            startInput.dataset.iso = toDateInputValue(new Date());
            startInput.value = toDisplayDate(startInput.dataset.iso);
        }

        const startDate = parseIsoDate(startInput.dataset.iso);
        const endDate = addDays(startDate, getRangeLength(rangeSelect.value) - 1);
        endInput.dataset.iso = toDateInputValue(endDate);
        endInput.value = toDisplayDate(endInput.dataset.iso);

        return {
            range: rangeSelect.value,
            start: startInput.dataset.iso,
            end: endInput.dataset.iso
        };
    }

    function closeDatePickers(exceptPicker) {
        document.querySelectorAll('.iot-calendar').forEach(function (picker) {
            if (picker !== exceptPicker) {
                picker.remove();
            }
        });
    }

    function renderDatePicker(input, panel) {
        closeDatePickers();

        const field = input.closest('.iot-date-field');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const currentYear = today.getFullYear();

        const selectedDate = parseIsoDate(input.dataset.iso || toDateInputValue(new Date()));
        let initialMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        if (initialMonth > currentMonth) initialMonth = new Date(currentMonth);

        // Rango actualmente seleccionado en el panel (Dia / Semana / Mes). Lo usamos
        // para resaltar en el calendario todos los dias que abarca la seleccion.
        const rangeSelect = panel.querySelector('[data-iot-range-select]');
        const rangeValue = rangeSelect ? rangeSelect.value : 'day';
        const rangeLength = getRangeLength(rangeValue);
        const rangeStart = new Date(selectedDate);
        rangeStart.setHours(0, 0, 0, 0);
        const rangeEnd = new Date(rangeStart);
        rangeEnd.setDate(rangeStart.getDate() + rangeLength - 1);

        const YEARS_PER_PAGE = 12;
        const INITIAL_MONTH_COUNT = 18;
        const LAZY_LOAD_BATCH = 12;
        const LAZY_LOAD_THRESHOLD_PX = 320;

        let viewMode = 'days';
        let yearPageStart = Math.floor(initialMonth.getFullYear() / YEARS_PER_PAGE) * YEARS_PER_PAGE;
        // Mes "actualmente visible" segun la posicion de scroll. Se actualiza en el handler de scroll.
        let visibleMonth = new Date(initialMonth);

        const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const dayNames = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'];
        const picker = document.createElement('div');
        picker.className = 'iot-calendar';

        function isSameMonth(a, b) {
            return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
        }

        function monthKey(d) {
            return d.getFullYear() + '-' + String(d.getMonth()).padStart(2, '0');
        }

        function parseMonthKey(key) {
            const parts = key.split('-');
            return new Date(Number(parts[0]), Number(parts[1]), 1);
        }

        function buildMonthSection(monthDate) {
            const section = document.createElement('section');
            section.className = 'iot-calendar__month';
            section.dataset.monthKey = monthKey(monthDate);

            const label = document.createElement('div');
            label.className = 'iot-calendar__month-label';
            label.textContent = monthNames[monthDate.getMonth()] + ' ' + monthDate.getFullYear();
            section.appendChild(label);

            const days = document.createElement('div');
            days.className = 'iot-calendar__days';

            const firstDay = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
            const gridStart = new Date(firstDay);
            gridStart.setDate(firstDay.getDate() - firstDay.getDay());
            const selectedIso = input.dataset.iso;

            for (let index = 0; index < 42; index++) {
                const date = new Date(gridStart);
                date.setDate(gridStart.getDate() + index);
                const iso = toDateInputValue(date);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'iot-calendar__day';
                button.textContent = String(date.getDate());
                button.dataset.iso = iso;

                if (date.getMonth() !== monthDate.getMonth()) {
                    button.classList.add('is-muted');
                }
                if (iso === selectedIso) {
                    button.classList.add('is-selected');
                }
                if (rangeLength > 1 && date >= rangeStart && date <= rangeEnd) {
                    button.classList.add('is-in-range');
                    if (date.getTime() === rangeStart.getTime()) {
                        button.classList.add('is-range-start');
                    }
                    if (date.getTime() === rangeEnd.getTime()) {
                        button.classList.add('is-range-end');
                    }
                }
                if (date > today) {
                    button.classList.add('is-disabled');
                    button.disabled = true;
                }
                days.appendChild(button);
            }

            section.appendChild(days);
            return section;
        }

        function appendOlderMonths(scroll, count) {
            const lastSection = scroll.lastElementChild;
            if (! lastSection) return;
            let last = parseMonthKey(lastSection.dataset.monthKey);
            for (let i = 0; i < count; i++) {
                last = new Date(last.getFullYear(), last.getMonth() - 1, 1);
                scroll.appendChild(buildMonthSection(last));
            }
        }

        function ensureMonthLoaded(scroll, target) {
            let guard = 0;
            while (guard++ < 240) {
                const lastSection = scroll.lastElementChild;
                if (! lastSection) return;
                const last = parseMonthKey(lastSection.dataset.monthKey);
                if (target >= last) return;
                appendOlderMonths(scroll, LAZY_LOAD_BATCH);
            }
        }

        function scrollToMonth(scroll, target, smooth) {
            ensureMonthLoaded(scroll, target);
            const node = scroll.querySelector('[data-month-key="' + monthKey(target) + '"]');
            if (! node) return;
            const top = node.offsetTop;
            if (smooth) {
                scroll.scrollTo({ top: top, behavior: 'smooth' });
            } else {
                scroll.scrollTop = top;
            }
        }

        function updateTitle() {
            const titleBtn = picker.querySelector('[data-calendar-year-view]');
            if (titleBtn) {
                titleBtn.textContent = monthNames[visibleMonth.getMonth()] + ' ' + visibleMonth.getFullYear();
            }
            const nextBtn = picker.querySelector('[data-calendar-next]');
            if (nextBtn) {
                const atCurrent = isSameMonth(visibleMonth, currentMonth);
                if (atCurrent) {
                    nextBtn.setAttribute('disabled', '');
                    nextBtn.setAttribute('aria-disabled', 'true');
                } else {
                    nextBtn.removeAttribute('disabled');
                    nextBtn.setAttribute('aria-disabled', 'false');
                }
            }
        }

        function updateVisibleMonthFromScroll(scroll) {
            // Mes "dominante" = el que contiene la linea de referencia (25% desde el tope
            // del viewport del scroll). Asi el cambio de mes en el titulo se siente natural.
            const scrollRect = scroll.getBoundingClientRect();
            const referenceY = scrollRect.top + scrollRect.height * 0.25;
            const sections = scroll.querySelectorAll('.iot-calendar__month');
            for (let i = 0; i < sections.length; i++) {
                const rect = sections[i].getBoundingClientRect();
                if (rect.top <= referenceY && rect.bottom > referenceY) {
                    const newMonth = parseMonthKey(sections[i].dataset.monthKey);
                    if (! isSameMonth(newMonth, visibleMonth)) {
                        visibleMonth = newMonth;
                        updateTitle();
                    }
                    return;
                }
            }
        }

        function renderDaysView() {
            picker.innerHTML = [
                '<div class="iot-calendar__head">',
                    '<button type="button" class="iot-calendar__nav" data-calendar-prev aria-label="Mes anterior">&lsaquo;</button>',
                    '<button type="button" class="iot-calendar__title" data-calendar-year-view aria-label="Elegir anio"></button>',
                    '<button type="button" class="iot-calendar__nav" data-calendar-next aria-label="Mes siguiente">&rsaquo;</button>',
                '</div>',
                '<div class="iot-calendar__weekdays">' + dayNames.map(function (day) {
                    return '<span>' + day + '</span>';
                }).join('') + '</div>',
                '<div class="iot-calendar__scroll" data-scroll></div>',
            ].join('');

            const scroll = picker.querySelector('.iot-calendar__scroll');

            // Stack inicial: del mes actual hacia atras (mas reciente arriba, mas viejo abajo).
            let m = new Date(currentMonth);
            for (let i = 0; i < INITIAL_MONTH_COUNT; i++) {
                scroll.appendChild(buildMonthSection(m));
                m = new Date(m.getFullYear(), m.getMonth() - 1, 1);
            }

            // Lazy load al acercarse al fondo + tracking del mes visible.
            let scheduled = false;
            scroll.addEventListener('scroll', function () {
                if (scheduled) return;
                scheduled = true;
                requestAnimationFrame(function () {
                    if (scroll.scrollTop + scroll.clientHeight >= scroll.scrollHeight - LAZY_LOAD_THRESHOLD_PX) {
                        appendOlderMonths(scroll, LAZY_LOAD_BATCH);
                    }
                    updateVisibleMonthFromScroll(scroll);
                    scheduled = false;
                });
            }, { passive: true });

            updateTitle();
            ensureMonthLoaded(scroll, visibleMonth);
            // Posicion inicial sin animacion: alinea el mes seleccionado/actual al tope.
            const initialNode = scroll.querySelector('[data-month-key="' + monthKey(visibleMonth) + '"]');
            if (initialNode) {
                scroll.scrollTop = initialNode.offsetTop;
            }
        }

        function renderYearsView() {
            const lastYearOfPage = yearPageStart + YEARS_PER_PAGE - 1;
            const canGoNext = lastYearOfPage < currentYear;
            const nextDisabledAttr = canGoNext ? '' : ' disabled aria-disabled="true"';
            const referenceYear = visibleMonth.getFullYear();

            picker.innerHTML = [
                '<div class="iot-calendar__head">',
                    '<button type="button" class="iot-calendar__nav" data-calendar-year-prev aria-label="Anios anteriores">&lsaquo;</button>',
                    '<strong class="iot-calendar__title iot-calendar__title--static">' + yearPageStart + ' - ' + lastYearOfPage + '</strong>',
                    '<button type="button" class="iot-calendar__nav" data-calendar-year-next' + nextDisabledAttr + ' aria-label="Anios siguientes">&rsaquo;</button>',
                '</div>',
                '<div class="iot-calendar__years"></div>',
            ].join('');

            const yearsGrid = picker.querySelector('.iot-calendar__years');
            for (let offset = 0; offset < YEARS_PER_PAGE; offset++) {
                const year = yearPageStart + offset;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'iot-calendar__year';
                button.textContent = String(year);
                button.dataset.year = String(year);
                if (year === referenceYear) button.classList.add('is-selected');
                if (year > currentYear) {
                    button.classList.add('is-disabled');
                    button.disabled = true;
                }
                yearsGrid.appendChild(button);
            }
        }

        function draw(direction) {
            if (viewMode === 'years') {
                renderYearsView();
            } else {
                renderDaysView();
            }
            if (direction) {
                picker.classList.remove('is-anim-forward', 'is-anim-backward');
                void picker.offsetWidth;
                picker.classList.add(direction === 'forward' ? 'is-anim-forward' : 'is-anim-backward');
            }
        }

        picker.addEventListener('click', function (event) {
            // El handler global de "click fuera" usa closest('.iot-calendar') sobre event.target.
            // Cortamos la propagacion aca por las dudas: ciertos re-renders detachan el target
            // del DOM y el handler global lo confunde con un click externo.
            event.stopPropagation();

            const previous = event.target.closest('[data-calendar-prev]');
            if (previous) {
                if (previous.hasAttribute('disabled')) return;
                const target = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
                const scroll = picker.querySelector('.iot-calendar__scroll');
                scrollToMonth(scroll, target, true);
                return;
            }

            const next = event.target.closest('[data-calendar-next]');
            if (next) {
                if (next.hasAttribute('disabled')) return;
                const candidate = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
                if (candidate > currentMonth) return;
                const scroll = picker.querySelector('.iot-calendar__scroll');
                scrollToMonth(scroll, candidate, true);
                return;
            }

            const yearView = event.target.closest('[data-calendar-year-view]');
            if (yearView) {
                viewMode = 'years';
                yearPageStart = Math.floor(visibleMonth.getFullYear() / YEARS_PER_PAGE) * YEARS_PER_PAGE;
                draw('forward');
                return;
            }

            const yearPrev = event.target.closest('[data-calendar-year-prev]');
            if (yearPrev) {
                yearPageStart -= YEARS_PER_PAGE;
                draw('backward');
                return;
            }

            const yearNext = event.target.closest('[data-calendar-year-next]');
            if (yearNext) {
                if (yearNext.hasAttribute('disabled')) return;
                yearPageStart += YEARS_PER_PAGE;
                draw('forward');
                return;
            }

            const yearBtn = event.target.closest('.iot-calendar__year');
            if (yearBtn) {
                if (yearBtn.disabled) return;
                const pickedYear = Number(yearBtn.dataset.year);
                let newMonth = new Date(pickedYear, visibleMonth.getMonth(), 1);
                if (newMonth > currentMonth) newMonth = new Date(currentMonth);
                visibleMonth = newMonth;
                viewMode = 'days';
                draw('backward');
                return;
            }

            const day = event.target.closest('.iot-calendar__day');
            if (day) {
                if (day.disabled) return;
                input.dataset.iso = day.dataset.iso;
                input.value = toDisplayDate(day.dataset.iso);
                closeDatePickers();
                renderMonitor(panel);
            }
        });

        draw();
        field.appendChild(picker);
    }

    function renderChartSeries(panel, config, readings, labels) {
        const line = panel.querySelector('[data-iot-line]');
        const pointsGroup = panel.querySelector('[data-iot-points]');
        const labelsGroup = panel.querySelector('[data-iot-labels]');
        const svg = panel.querySelector('.iot-line-chart');

        if (!line || !pointsGroup || !labelsGroup) {
            return;
        }

        let xGridGroup = panel.querySelector('[data-iot-x-grid]');

        if (!xGridGroup && svg) {
            xGridGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            xGridGroup.setAttribute('class', 'iot-line-chart__grid iot-line-chart__grid--x');
            xGridGroup.setAttribute('data-iot-x-grid', '');
            svg.insertBefore(xGridGroup, panel.querySelector('[data-iot-axis]'));
        }

        const availableReadings = readings.map(function (value) {
            return value === null ? null : Number(value);
        });
        const pointTotal = Math.max(availableReadings.length, 2);
        const points = availableReadings.map(function (value, index) {
            return value === null ? null : getPoint(value, index, pointTotal, config);
        });

        line.setAttribute('points', points.filter(Boolean).map(function (point) {
            return point.x.toFixed(1) + ',' + point.y.toFixed(1);
        }).join(' '));

        if (xGridGroup) {
            xGridGroup.innerHTML = points.map(function (point, index) {
                const x = point ? point.x : chart.left + ((chart.right - chart.left) / (pointTotal - 1)) * index;
                return '<line x1="' + x.toFixed(1) + '" y1="' + chart.top + '" x2="' + x.toFixed(1) + '" y2="' + chart.bottom + '"></line>';
            }).join('');
        }

        pointsGroup.innerHTML = points.map(function (point, index) {
            if (!point) {
                return '';
            }

            return '<circle cx="' + point.x.toFixed(1) + '" cy="' + point.y.toFixed(1) + '" r="7"><title>' + labels[index] + ': ' + availableReadings[index] + config.unit + '</title></circle>';
        }).join('');

        const labelStep = labels.length > 14 ? Math.ceil(labels.length / 7) : 1;
        labelsGroup.innerHTML = labels.map(function (label, index) {
            if (index % labelStep !== 0 && index !== labels.length - 1) {
                return '';
            }

            const x = chart.left + ((chart.right - chart.left) / (pointTotal - 1)) * index;
            return '<text x="' + x.toFixed(1) + '" y="336">' + label + '</text>';
        }).join('');
    }

    function renderFallbackChart(panel, config, state) {
        const labels = state.readings.map(function (_, index) {
            return ((index + 1) * REPORT_PERIOD_HOURS) + 'h';
        });
        renderChartSeries(panel, config, state.readings, labels);
    }

    async function fetchMonitorSeries(panel, config, state) {
        const rangeData = syncDateControls(panel);
        const kpi = document.querySelector('[data-iot-kpi="' + config.kpi + '"]');

        if (!rangeData) {
            renderFallbackChart(panel, config, state);
            return;
        }

        const url = new URL(readingsEndpoint, window.location.origin);
        url.searchParams.set('metric', panel.dataset.iotMonitor);
        url.searchParams.set('range', rangeData.range);
        url.searchParams.set('start', rangeData.start);

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Invalid IoT response');
            }

            const payload = await response.json();
            const points = Array.isArray(payload.points) ? payload.points : [];
            const readings = points.map(function (point) {
                return point.value;
            });
            const labels = points.map(function (point) {
                return point.label;
            });

            renderChartSeries(panel, config, readings, labels);

            if (kpi && payload.latest !== null && payload.latest !== undefined) {
                kpi.textContent = payload.latest + config.unit;
            }
        } catch (error) {
            renderFallbackChart(panel, config, state);

            if (kpi) {
                kpi.textContent = state.readings[state.readings.length - 1] + config.unit;
            }
        }
    }

    function renderMonitor(panel) {
        const monitorKey = panel.dataset.iotMonitor;
        const config = monitorConfig[monitorKey];
        const select = panel.querySelector('[data-iot-state-select]');
        const selectedState = select?.value || 'ok';
        const state = config.states[selectedState] || config.states.ok;
        const chartStatus = panel.querySelector('[data-iot-chart-status]');
        const title = panel.querySelector('[data-iot-status-title]');
        const badge = panel.querySelector('[data-iot-status-badge]');
        const illustration = panel.querySelector('[data-iot-illustration]');
        const image = panel.querySelector('[data-iot-illustration-image]');

        renderChartAxis(panel, config);

        if (chartStatus) {
            chartStatus.textContent = state.badge;
            chartStatus.className = state.badgeClass;
        }

        if (title) {
            title.textContent = state.title;
        }

        if (badge) {
            badge.textContent = state.badge;
            badge.className = state.badgeClass;
        }

        if (illustration) {
            illustration.dataset.iotState = selectedState;
            illustration.classList.toggle('is-danger', selectedState === 'high');
            illustration.classList.toggle('is-warn', selectedState === 'medium' || selectedState === 'low');
            illustration.classList.toggle('is-ok', selectedState === 'ok');
        }

        if (image) {
            image.src = assetBase + '/' + getThemedImage(state);
        }

        fetchMonitorSeries(panel, config, state);
    }

    function renderAllMonitors() {
        monitors.forEach(renderMonitor);
    }

    switchers.forEach(function (input) {
        input.addEventListener('change', function () {
            activatePane(input.dataset.sectionTarget);
        });
    });

    monitors.forEach(function (panel) {
        panel.querySelector('[data-iot-state-select]')?.addEventListener('change', function () {
            renderMonitor(panel);
        });

        panel.querySelector('[data-iot-range-select]')?.addEventListener('change', function () {
            renderMonitor(panel);
        });

        panel.querySelector('[data-iot-start-date]')?.addEventListener('click', function (event) {
            renderDatePicker(event.currentTarget, panel);
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.iot-calendar') && !event.target.closest('[data-iot-start-date]')) {
            closeDatePickers();
        }
    });

    new MutationObserver(renderAllMonitors).observe(body, {
        attributes: true,
        attributeFilter: ['data-theme']
    });

    renderAllMonitors();
});
</script>
<?= $this->endSection() ?>
