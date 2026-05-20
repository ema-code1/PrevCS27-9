<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>">
<?= $this->endSection() ?>

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
            <div class="dashboard-head__meta">
                <span class="badge badge--success"><span class="dot dot--live"></span> stream live</span>
            </div>
        </header>

        <!-- KPIs -->
        <section class="kpi-grid">
            <article class="kpi-card card">
                <p>ESP32</p>
                <strong class="text-up">Online</strong>
                <small class="text-muted mono">heartbeat 12s</small>
            </article>
            <article class="kpi-card card">
                <p>Ultimo reporte</p>
                <strong>06:00</strong>
                <small class="text-muted mono">hace 4 min</small>
            </article>
            <article class="kpi-card card">
                <p>Vibracion</p>
                <strong><span data-iot-kpi="vibration">3.2</span></strong>
                <small class="text-muted mono">mm/s</small>
            </article>
            <article class="kpi-card card">
                <p>RPM</p>
                <strong><span data-iot-kpi="rpm">1420</span></strong>
                <small class="text-muted mono">rev/min</small>
            </article>
            <article class="kpi-card card">
                <p>Temperatura</p>
                <strong><span data-iot-kpi="temperature">41 C</span></strong>
                <small class="text-muted mono">grados</small>
            </article>
        </section>

        <div class="section-switch" role="tablist" aria-label="Parametros IoT">
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

        <?php
        $metrics = [
            'vibracion'   => ['monitor' => 'vibration',   'label' => 'Vibraciones',   'title' => 'Todo OK',                'statusOpts' => [['ok','Todo OK'], ['medium','Vibraciones minimas'], ['high','Vibraciones altas']]],
            'rpm'         => ['monitor' => 'rpm',         'label' => 'RPM',           'title' => 'Revoluciones normales',  'statusOpts' => [['ok','Revoluciones normales'], ['low','Revoluciones bajas'], ['high','Revoluciones altas']]],
            'temperatura' => ['monitor' => 'temperature', 'label' => 'Temperatura',   'title' => 'Temperatura normal',     'statusOpts' => [['ok','Temperatura normal'], ['low','Temperatura baja'], ['high','Temperatura alta']]],
        ];
        ?>

        <?php foreach ($metrics as $key => $cfg): ?>
        <section class="section-pane<?= $key === 'vibracion' ? ' is-active' : '' ?>" data-section-pane="<?= $key ?>">
            <div class="iot-grid" data-iot-monitor="<?= $cfg['monitor'] ?>">
                <section class="panel card iot-chart-card">
                    <div class="panel__header">
                        <h3><?= $cfg['label'] ?></h3>
                        <span class="badge badge--success" data-iot-chart-status>Normal</span>
                    </div>

                    <div class="iot-date-controls" data-iot-date-controls>
                        <label class="iot-date-field">
                            <span>Rango</span>
                            <select class="iot-status-select iot-range-select" data-iot-range-select aria-label="Seleccionar rango de <?= $cfg['label'] ?>">
                                <option value="day">Dia</option>
                                <option value="week">Semana</option>
                                <option value="month">Mes</option>
                            </select>
                        </label>
                        <label class="iot-date-field">
                            <span>Desde</span>
                            <input class="iot-date-input" type="text" data-iot-start-date aria-label="Fecha inicial de <?= $cfg['label'] ?>" readonly>
                        </label>
                        <label class="iot-date-field">
                            <span>Hasta</span>
                            <input class="iot-date-input" type="text" data-iot-end-date aria-label="Fecha final de <?= $cfg['label'] ?>" readonly>
                        </label>
                    </div>

                    <div class="iot-chart" aria-label="Grafico de linea de <?= $cfg['label'] ?>">
                        <svg class="iot-line-chart" viewBox="0 0 640 360" role="img" aria-labelledby="<?= $cfg['monitor'] ?>-chart-title">
                            <title id="<?= $cfg['monitor'] ?>-chart-title">Mediciones periodicas de <?= $cfg['label'] ?></title>
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
                            <h3 data-iot-status-title><?= $cfg['title'] ?></h3>
                            <span class="badge badge--success" data-iot-status-badge>Normal</span>
                        </div>

                        <label class="iot-status-control">
                            <span>Estado del motor</span>
                            <select class="iot-status-select" data-iot-state-select aria-label="Seleccionar estado de <?= $cfg['label'] ?>">
                                <?php foreach ($cfg['statusOpts'] as [$v, $t]): ?>
                                    <option value="<?= $v ?>"><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </aside>

                    <aside class="panel card iot-status-card" data-state="ok">
                        <div class="iot-illustration iot-illustration--<?= $cfg['monitor'] ?>" data-iot-illustration aria-hidden="true">
                            <img class="iot-illustration__image" data-iot-illustration-image src="<?= base_url('images/motors_iso/motor-ok.png') ?>" alt="">
                        </div>
                    </aside>
                </div>
            </div>
        </section>
        <?php endforeach; ?>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const switchers = document.querySelectorAll('[data-section-target]');
    const panes = document.querySelectorAll('[data-section-pane]');
    const monitors = document.querySelectorAll('[data-iot-monitor]');
    const body = document.body;
    let paneTransitionTimer = null;

    const assetBase = '<?= rtrim(base_url('images/motors_iso'), '/') ?>';
    const readingsEndpoint = '<?= base_url('api/iot-readings') ?>';
    const REPORT_PERIOD_HOURS = 1;
    const chart = { left: 78, right: 600, top: 48, bottom: 304 };

    const monitorConfig = {
        vibration: {
            kpi: 'vibration', min: 0, max: 10, unit: '',
            axis: [10, 8, 6, 4, 2],
            states: {
                ok:     { title: 'Todo OK',              badge: 'Todo OK', badgeClass: 'badge badge--success', image: 'motor-ok.png',       darkImage: 'motor-ok-dark.png',       readings: [3.0, 3.2, 2.9, 3.4, 3.1, 3.2] },
                medium: { title: 'Vibraciones minimas',  badge: 'Minimas', badgeClass: 'badge badge--warning', image: 'vibration-medium.png', darkImage: 'vibration-medium-dark.png', readings: [4.6, 5.1, 5.8, 5.4, 6.2, 6.0] },
                high:   { title: 'Vibraciones altas',    badge: 'Alerta',  badgeClass: 'badge badge--error',   image: 'vibration-high.png',   darkImage: 'vibration-high-dark.png',   readings: [6.9, 7.8, 8.4, 8.1, 9.2, 9.5] }
            }
        },
        rpm: {
            kpi: 'rpm', min: 800, max: 2200, unit: '',
            axis: [2200, 1850, 1500, 1150, 800],
            states: {
                ok:   { title: 'Revoluciones normales', badge: 'Normal', badgeClass: 'badge badge--success', image: 'motor-ok.png',  darkImage: 'motor-ok-dark.png',  readings: [1410, 1440, 1390, 1460, 1425, 1430] },
                low:  { title: 'Revoluciones bajas',    badge: 'Bajas',  badgeClass: 'badge badge--warning', image: 'rpm-low.png',   darkImage: 'rpm-low-dark.png',   readings: [1150, 1080, 1025, 980, 940, 900] },
                high: { title: 'Revoluciones altas',    badge: 'Alerta', badgeClass: 'badge badge--error',   image: 'rpm-high.png',  darkImage: 'rpm-high-dark.png',  readings: [1620, 1740, 1860, 1910, 2030, 2100] }
            }
        },
        temperature: {
            kpi: 'temperature', min: 0, max: 90, unit: ' C',
            axis: [90, 68, 45, 23, 0],
            states: {
                ok:   { title: 'Temperatura normal', badge: 'Normal', badgeClass: 'badge badge--success', image: 'motor-ok.png',          darkImage: 'motor-ok-dark.png',          readings: [39, 40, 42, 41, 43, 41] },
                low:  { title: 'Temperatura baja',   badge: 'Baja',   badgeClass: 'badge badge--warning', image: 'temperature-low.png',   darkImage: 'temperature-low-dark.png',   readings: [31, 28, 25, 22, 19, 17] },
                high: { title: 'Temperatura alta',   badge: 'Alerta', badgeClass: 'badge badge--error',   image: 'temperature-high.png',  darkImage: 'temperature-high-dark.png',  readings: [54, 61, 66, 72, 77, 82] }
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
        const nextPane  = Array.from(panes).find((p) => p.dataset.sectionPane === target);
        const activePane = Array.from(panes).find((p) => p.classList.contains('is-active'));
        updateSwitchState(target);
        if (!nextPane || activePane === nextPane) return;
        if (paneTransitionTimer) { clearTimeout(paneTransitionTimer); panes.forEach((p) => p.classList.remove('is-exiting')); }
        if (!activePane) { nextPane.classList.add('is-active'); return; }
        activePane.classList.add('is-exiting');
        paneTransitionTimer = window.setTimeout(() => {
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
        if (!axisGroup) return;
        axisGroup.innerHTML = config.axis.map(function (value, index) {
            const y = 52 + (64 * index);
            return '<text x="60" y="' + y + '">' + value + '</text>';
        }).join('');
    }

    function toDateInputValue(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }
    function toDisplayDate(isoDate) {
        const parts = isoDate.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    function parseIsoDate(isoDate) { return new Date(isoDate + 'T00:00:00'); }
    function addDays(date, days) { const n = new Date(date); n.setDate(n.getDate() + days); return n; }
    function getRangeLength(range) { return range === 'month' ? 30 : range === 'week' ? 7 : 1; }

    function syncDateControls(panel) {
        const rangeSelect = panel.querySelector('[data-iot-range-select]');
        const startInput  = panel.querySelector('[data-iot-start-date]');
        const endInput    = panel.querySelector('[data-iot-end-date]');
        if (!rangeSelect || !startInput || !endInput) return null;

        if (!startInput.dataset.iso) {
            startInput.dataset.iso = toDateInputValue(new Date());
            startInput.value = toDisplayDate(startInput.dataset.iso);
        }
        const startDate = parseIsoDate(startInput.dataset.iso);
        const endDate = addDays(startDate, getRangeLength(rangeSelect.value) - 1);
        endInput.dataset.iso = toDateInputValue(endDate);
        endInput.value = toDisplayDate(endInput.dataset.iso);
        return { range: rangeSelect.value, start: startInput.dataset.iso, end: endInput.dataset.iso };
    }

    function closeDatePickers(exceptPicker) {
        document.querySelectorAll('.iot-calendar').forEach(function (picker) {
            if (picker !== exceptPicker) picker.remove();
        });
    }

    function renderDatePicker(input, panel) {
        closeDatePickers();
        const field = input.closest('.iot-date-field');
        const selectedDate = parseIsoDate(input.dataset.iso || toDateInputValue(new Date()));
        let visibleMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const dayNames = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'];
        const picker = document.createElement('div');
        picker.className = 'iot-calendar';

        function draw() {
            const firstDay = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
            const gridStart = new Date(firstDay);
            gridStart.setDate(firstDay.getDate() - firstDay.getDay());
            const selectedIso = input.dataset.iso;
            picker.innerHTML = [
                '<div class="iot-calendar__head">',
                    '<button type="button" class="iot-calendar__nav" data-calendar-prev aria-label="Mes anterior">&lsaquo;</button>',
                    '<strong>' + monthNames[visibleMonth.getMonth()] + ' ' + visibleMonth.getFullYear() + '</strong>',
                    '<button type="button" class="iot-calendar__nav" data-calendar-next aria-label="Mes siguiente">&rsaquo;</button>',
                '</div>',
                '<div class="iot-calendar__weekdays">' + dayNames.map(function (d) { return '<span>' + d + '</span>'; }).join('') + '</div>',
                '<div class="iot-calendar__days"></div>',
            ].join('');
            const days = picker.querySelector('.iot-calendar__days');
            for (let i = 0; i < 42; i++) {
                const date = new Date(gridStart);
                date.setDate(gridStart.getDate() + i);
                const iso = toDateInputValue(date);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'iot-calendar__day';
                button.textContent = String(date.getDate());
                button.dataset.iso = iso;
                if (date.getMonth() !== visibleMonth.getMonth()) button.classList.add('is-muted');
                if (iso === selectedIso) button.classList.add('is-selected');
                days.appendChild(button);
            }
        }

        picker.addEventListener('click', function (e) {
            // Evitar que el click se propague al <label> contenedor (que reabriria el picker).
            e.stopPropagation();
            e.preventDefault();
            const previous = e.target.closest('[data-calendar-prev]');
            const next     = e.target.closest('[data-calendar-next]');
            const day      = e.target.closest('.iot-calendar__day');
            if (previous) { visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1); draw(); return; }
            if (next)     { visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1); draw(); return; }
            if (day) {
                input.dataset.iso = day.dataset.iso;
                input.value = toDisplayDate(day.dataset.iso);
                closeDatePickers();
                renderMonitor(panel);
            }
        });
        // Bloquear mousedown tambien para que el browser no de focus al <input> debajo
        picker.addEventListener('mousedown', function (e) { e.preventDefault(); e.stopPropagation(); });

        draw();
        field.appendChild(picker);
    }

    function renderChartSeries(panel, config, readings, labels) {
        const line = panel.querySelector('[data-iot-line]');
        const pointsGroup = panel.querySelector('[data-iot-points]');
        const labelsGroup = panel.querySelector('[data-iot-labels]');
        const svg = panel.querySelector('.iot-line-chart');
        if (!line || !pointsGroup || !labelsGroup) return;

        let xGridGroup = panel.querySelector('[data-iot-x-grid]');
        if (!xGridGroup && svg) {
            xGridGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            xGridGroup.setAttribute('class', 'iot-line-chart__grid iot-line-chart__grid--x');
            xGridGroup.setAttribute('data-iot-x-grid', '');
            svg.insertBefore(xGridGroup, panel.querySelector('[data-iot-axis]'));
        }

        const availableReadings = readings.map(function (v) { return v === null ? null : Number(v); });
        const pointTotal = Math.max(availableReadings.length, 2);
        const points = availableReadings.map(function (v, i) { return v === null ? null : getPoint(v, i, pointTotal, config); });

        line.setAttribute('points', points.filter(Boolean).map(function (p) { return p.x.toFixed(1) + ',' + p.y.toFixed(1); }).join(' '));

        if (xGridGroup) {
            xGridGroup.innerHTML = points.map(function (p, i) {
                const x = p ? p.x : chart.left + ((chart.right - chart.left) / (pointTotal - 1)) * i;
                return '<line x1="' + x.toFixed(1) + '" y1="' + chart.top + '" x2="' + x.toFixed(1) + '" y2="' + chart.bottom + '"></line>';
            }).join('');
        }

        pointsGroup.innerHTML = points.map(function (p, i) {
            if (!p) return '';
            return '<circle cx="' + p.x.toFixed(1) + '" cy="' + p.y.toFixed(1) + '" r="5"><title>' + labels[i] + ': ' + availableReadings[i] + config.unit + '</title></circle>';
        }).join('');

        const labelStep = labels.length > 14 ? Math.ceil(labels.length / 7) : 1;
        labelsGroup.innerHTML = labels.map(function (label, i) {
            if (i % labelStep !== 0 && i !== labels.length - 1) return '';
            const x = chart.left + ((chart.right - chart.left) / (pointTotal - 1)) * i;
            return '<text x="' + x.toFixed(1) + '" y="336">' + label + '</text>';
        }).join('');
    }

    function renderFallbackChart(panel, config, state) {
        const labels = state.readings.map(function (_, i) { return ((i + 1) * REPORT_PERIOD_HOURS) + 'h'; });
        renderChartSeries(panel, config, state.readings, labels);
    }

    async function fetchMonitorSeries(panel, config, state) {
        const rangeData = syncDateControls(panel);
        const kpi = document.querySelector('[data-iot-kpi="' + config.kpi + '"]');
        if (!rangeData) { renderFallbackChart(panel, config, state); return; }

        const url = new URL(readingsEndpoint, window.location.origin);
        url.searchParams.set('metric', panel.dataset.iotMonitor);
        url.searchParams.set('range', rangeData.range);
        url.searchParams.set('start', rangeData.start);

        try {
            const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Invalid IoT response');
            const payload = await response.json();
            const points = Array.isArray(payload.points) ? payload.points : [];
            const readings = points.map(function (p) { return p.value; });
            const labels   = points.map(function (p) { return p.label; });
            renderChartSeries(panel, config, readings, labels);
            if (kpi && payload.latest !== null && payload.latest !== undefined) kpi.textContent = payload.latest + config.unit;
        } catch (error) {
            renderFallbackChart(panel, config, state);
            if (kpi) kpi.textContent = state.readings[state.readings.length - 1] + config.unit;
        }
    }

    function renderMonitor(panel) {
        const monitorKey = panel.dataset.iotMonitor;
        const config = monitorConfig[monitorKey];
        const select = panel.querySelector('[data-iot-state-select]');
        const selectedState = select?.value || 'ok';
        const state = config.states[selectedState] || config.states.ok;
        const chartStatus = panel.querySelector('[data-iot-chart-status]');
        const title  = panel.querySelector('[data-iot-status-title]');
        const badge  = panel.querySelector('[data-iot-status-badge]');
        const illustration = panel.querySelector('[data-iot-illustration]');
        const image  = panel.querySelector('[data-iot-illustration-image]');
        const statusCard = panel.querySelector('.iot-status-card');
        const chartCard  = panel.querySelector('.iot-chart-card');

        renderChartAxis(panel, config);

        const stateClass = selectedState === 'high' ? 'error' : (selectedState === 'medium' || selectedState === 'low') ? 'warn' : 'ok';
        if (statusCard) statusCard.dataset.state = stateClass;
        if (chartCard)  chartCard.dataset.state  = stateClass;

        if (chartStatus) { chartStatus.textContent = state.badge; chartStatus.className = state.badgeClass; }
        if (title) title.textContent = state.title;
        if (badge) { badge.textContent = state.badge; badge.className = state.badgeClass; }
        if (image) image.src = assetBase + '/' + getThemedImage(state);

        fetchMonitorSeries(panel, config, state);
    }

    function renderAllMonitors() { monitors.forEach(renderMonitor); }

    switchers.forEach(function (input) {
        input.addEventListener('change', function () { activatePane(input.dataset.sectionTarget); });
    });

    /* ─── Custom select widget (Rango / Estado motor) ──────────
       Reemplaza visualmente el <select> nativo por un boton + dropdown
       estilizado. El <select> queda invisible pero dispara 'change' como
       siempre, asi el resto del codigo no se entera. */
    function closeAllSelectMenus(except) {
        document.querySelectorAll('.iot-select-menu').forEach(function (m) { if (m !== except) m.hidden = true; });
        document.querySelectorAll('.iot-select-btn').forEach(function (b) {
            if (!except || b.nextElementSibling !== except) b.setAttribute('aria-expanded', 'false');
        });
    }
    function enhanceIotSelect(selectEl) {
        if (!selectEl || selectEl.dataset.enhanced === '1') return;
        selectEl.dataset.enhanced = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'iot-select-wrapper';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'iot-select-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');

        const labelSpan = document.createElement('span');
        labelSpan.className = 'iot-select-btn__label';
        btn.appendChild(labelSpan);

        const caret = document.createElement('span');
        caret.className = 'iot-select-btn__caret';
        caret.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 11 7" fill="none" aria-hidden="true"><path d="M1 1l4.5 4.5L10 1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        btn.appendChild(caret);

        const menu = document.createElement('div');
        menu.className = 'iot-select-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;

        function refreshLabel() {
            const sel = selectEl.options[selectEl.selectedIndex];
            labelSpan.textContent = sel ? sel.textContent : '';
        }
        function buildMenu() {
            menu.innerHTML = '';
            Array.from(selectEl.options).forEach(function (opt) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'iot-select-opt' + (opt.selected ? ' is-selected' : '');
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                item.dataset.value = opt.value;
                item.innerHTML =
                    '<svg class="iot-select-opt__check" viewBox="0 0 13 13" fill="none" aria-hidden="true">' +
                        '<path d="M2 6.8L5.4 10 11 3.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>' +
                    '</svg>' +
                    '<span>' + opt.textContent + '</span>';
                item.addEventListener('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    if (selectEl.value !== opt.value) {
                        selectEl.value = opt.value;
                        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    Array.from(menu.children).forEach(function (c) {
                        c.classList.toggle('is-selected', c === item);
                        c.setAttribute('aria-selected', c === item ? 'true' : 'false');
                    });
                    refreshLabel();
                    menu.hidden = true;
                    btn.setAttribute('aria-expanded', 'false');
                });
                menu.appendChild(item);
            });
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const willOpen = menu.hidden;
            closeAllSelectMenus(willOpen ? menu : null);
            closeDatePickers();
            menu.hidden = !willOpen;
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        // Si algo cambia el select desde fuera (no deberia, pero por las dudas):
        selectEl.addEventListener('change', function () {
            refreshLabel();
            Array.from(menu.children).forEach(function (c) {
                const isSel = c.dataset.value === selectEl.value;
                c.classList.toggle('is-selected', isSel);
                c.setAttribute('aria-selected', isSel ? 'true' : 'false');
            });
        });

        // Insertar el wrapper en el DOM, manteniendo el <select> dentro
        const parent = selectEl.parentNode;
        parent.insertBefore(wrapper, selectEl);
        wrapper.appendChild(selectEl);
        wrapper.appendChild(btn);
        wrapper.appendChild(menu);

        refreshLabel();
        buildMenu();
    }

    monitors.forEach(function (panel) {
        const rangeSel = panel.querySelector('[data-iot-range-select]');
        const stateSel = panel.querySelector('[data-iot-state-select]');
        rangeSel?.addEventListener('change', function () { renderMonitor(panel); });
        stateSel?.addEventListener('change', function () { renderMonitor(panel); });
        if (rangeSel) enhanceIotSelect(rangeSel);
        if (stateSel) enhanceIotSelect(stateSel);

        const startInputEl = panel.querySelector('[data-iot-start-date]');
        if (startInputEl) {
            const openPicker = function (e) {
                e.preventDefault();
                e.stopPropagation();
                const existing = panel.querySelector('.iot-calendar');
                if (existing) { closeDatePickers(); return; }
                closeAllSelectMenus();
                renderDatePicker(startInputEl, panel);
            };
            startInputEl.addEventListener('mousedown', openPicker);
            startInputEl.closest('.iot-date-field')?.addEventListener('click', function (e) {
                if (e.target === startInputEl) return;
                if (e.target.closest('.iot-calendar')) return;
                openPicker(e);
            });
        }
    });

    // Click fuera cierra calendar Y dropdowns custom
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.iot-calendar') && !e.target.closest('[data-iot-start-date]')) closeDatePickers();
        if (!e.target.closest('.iot-select-wrapper')) closeAllSelectMenus();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeDatePickers(); closeAllSelectMenus(); }
    });

    new MutationObserver(renderAllMonitors).observe(body, { attributes: true, attributeFilter: ['data-theme'] });
    renderAllMonitors();
});
</script>
<?= $this->endSection() ?>
