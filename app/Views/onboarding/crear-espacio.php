<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/onboarding.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = $errors ?? [];
$chartLabels = [
    'line'     => 'Gráfico de línea',
    'area'     => 'Gráfico de área',
    'gauge'    => 'Medidor (gauge)',
    'bars'     => 'Barras',
    'timeline' => 'Línea de tiempo',
];
?>
<main class="auth-page">
    <div class="auth-nebula" aria-hidden="true">
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
    </div>

    <section class="auth-panel auth-panel--wide">
        <div class="auth-card card onboarding-card">
            <?php if (session('error')): ?>
                <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
            <?php endif; ?>
            <?php if ($errors !== []): ?>
                <div class="auth-alert auth-alert--error">
                    <?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="onboarding-head">
                <p class="auth-pane__eyebrow">Onboarding · <?= esc($userName) ?></p>
                <h1>Crear espacio</h1>
                <p>Un espacio representa una empresa que gestionás. Adentro vas a poder vincular una o más ESP32, en distintos sectores. Generamos un ID único para que tu equipo se sume.</p>
            </div>

            <div class="wizard-progress" data-wizard-indicator>
                <div class="wizard-progress__track"><span class="wizard-progress__fill" data-progress-fill style="width:33%"></span></div>
                <div class="wizard-progress__meta">
                    <span data-progress-label>Paso 1 de 3 · Industria</span>
                    <span class="wizard-progress__pct" data-progress-pct>33%</span>
                </div>
            </div>

            <form action="<?= base_url('crear-espacio') ?>" method="post" class="auth-form" data-wizard-form>
                <?= csrf_field() ?>

                <!-- PASO 1: Industria + ID -->
                <section class="wizard-pane is-active" data-step-pane="1">
                    <label class="auth-field">
                        <span class="auth-field__label">Nombre de la empresa / industria</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">E</span>
                            <input type="text" name="company_name" placeholder="Ej: San Jose Metalúrgica" value="<?= esc(old('company_name')) ?>" required>
                        </span>
                    </label>

                    <div class="wizard-uid wizard-uid--inline">
                        <span class="auth-field__label">Tu ID de industria (se genera solo)</span>
                        <div class="wizard-uid__code" data-uid-preview>XX-IND-XXXXXX</div>
                        <p class="wizard-hint">Único e irrepetible, derivado de tu empresa. Tu equipo lo usará para unirse.</p>
                    </div>

                    <label class="auth-field">
                        <span class="auth-field__label">Nombre del espacio</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">#</span>
                            <input type="text" name="space_name" placeholder="Ej: Planta Norte" value="<?= esc(old('space_name')) ?>" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Sector industrial</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">S</span>
                            <input type="text" name="sector" placeholder="Ej: Metalurgia, Alimenticia, Textil" value="<?= esc(old('sector')) ?>">
                        </span>
                    </label>

                    <div class="wizard-nav">
                        <span></span>
                        <button type="button" class="auth-submit wizard-next" data-wizard-next>Continuar</button>
                    </div>
                </section>

                <!-- PASO 2: Seguridad -->
                <section class="wizard-pane" data-step-pane="2">
                    <p class="wizard-hint">Define una contraseña para el espacio. Tu equipo la usará (junto al ID) para unirse como supervisores.</p>

                    <label class="auth-field">
                        <span class="auth-field__label">Contraseña del espacio</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password" placeholder="Mínimo 6 caracteres" minlength="6" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Repetir contraseña</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password_confirm" placeholder="Repetí la contraseña" minlength="6" required>
                        </span>
                    </label>

                    <div class="wizard-nav">
                        <button type="button" class="auth-link wizard-prev" data-wizard-prev>Volver</button>
                        <button type="button" class="auth-submit wizard-next" data-wizard-next>Continuar</button>
                    </div>
                </section>

                <!-- PASO 3: Métricas (con gráficos sugeridos expandibles) -->
                <section class="wizard-pane" data-step-pane="3">
                    <p class="wizard-hint">Elegí las métricas que querés monitorear en esta industria.</p>

                    <div class="metric-grid">
                        <?php foreach ($sensorTypes as $type): ?>
                            <label class="metric-option">
                                <input type="checkbox" name="metrics[]" value="<?= esc($type['code']) ?>"
                                    data-metric-code="<?= esc($type['code']) ?>"
                                    data-metric-name="<?= esc($type['name']) ?>"
                                    data-metric-chart="<?= esc($type['default_chart']) ?>">
                                <span class="metric-option__box">
                                    <span class="metric-option__name"><?= esc($type['name']) ?></span>
                                    <?php if (!empty($type['unit'])): ?>
                                        <span class="metric-option__unit"><?= esc($type['unit']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <details class="wizard-charts">
                        <summary>Ver gráficos sugeridos</summary>
                        <div class="chart-suggestions" data-chart-suggestions>
                            <p class="chart-suggestions__empty" data-chart-empty>Elegí al menos una métrica para ver las sugerencias.</p>
                        </div>
                    </details>

                    <div class="wizard-nav">
                        <button type="button" class="auth-link wizard-prev" data-wizard-prev>Volver</button>
                        <button type="submit" class="auth-submit" data-wizard-submit>Crear espacio</button>
                    </div>
                </section>
            </form>
        </div>

        <p class="auth-foot">
            ¿Preferís unirte a un espacio existente? <a href="<?= base_url('unirse-espacio') ?>">Unirme a un espacio</a>
        </p>
    </section>
</main>

<!-- Mapa de etiquetas de gráficos para el JS -->
<script type="application/json" data-chart-labels>
<?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>
</script>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form  = document.querySelector('[data-wizard-form]');
    if (!form) return;
    const panes = Array.from(form.querySelectorAll('[data-step-pane]'));
    const chartLabels = JSON.parse(document.querySelector('[data-chart-labels]').textContent || '{}');
    const stepNames = ['Industria', 'Seguridad', 'Métricas'];
    const fill   = document.querySelector('[data-progress-fill]');
    const pctEl  = document.querySelector('[data-progress-pct]');
    const labelEl = document.querySelector('[data-progress-label]');
    let current = 1;

    function show(step) {
        current = Math.max(1, Math.min(panes.length, step));
        panes.forEach((p) => p.classList.toggle('is-active', Number(p.dataset.stepPane) === current));
        const pct = Math.round((current / panes.length) * 100);
        if (fill)  fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
        if (labelEl) labelEl.textContent = 'Paso ' + current + ' de ' + panes.length + ' · ' + (stepNames[current - 1] || '');
        if (current === panes.length) renderSuggestions();
    }

    // Validacion HTML5 nativa de los campos visibles antes de avanzar
    function validateCurrent() {
        const pane = panes.find((p) => Number(p.dataset.stepPane) === current);
        const fields = Array.from(pane.querySelectorAll('input[required]'));
        for (const f of fields) {
            if (!f.reportValidity()) return false;
        }
        // Paso 2: confirmar coincidencia de contrasenas
        if (current === 2) {
            const pw = pane.querySelector('[name="space_password"]');
            const pc = pane.querySelector('[name="space_password_confirm"]');
            if (pw && pc && pw.value !== pc.value) {
                pc.setCustomValidity('Las contraseñas no coinciden.');
                pc.reportValidity();
                pc.setCustomValidity('');
                return false;
            }
        }
        return true;
    }

    form.querySelectorAll('[data-wizard-next]').forEach((b) =>
        b.addEventListener('click', () => { if (validateCurrent()) show(current + 1); }));
    form.querySelectorAll('[data-wizard-prev]').forEach((b) =>
        b.addEventListener('click', () => show(current - 1)));

    // Vista previa del prefijo del UID a partir del nombre de empresa
    const companyInput = form.querySelector('[name="company_name"]');
    const uidPreview = form.querySelector('[data-uid-preview]');
    function updateUidPreview() {
        const words = (companyInput.value || '').trim().split(/\s+/).filter(Boolean);
        let initials = words.map((w) => w[0]).join('').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 3);
        if (!initials) initials = 'XX';
        if (initials.length === 1) initials += 'X';
        uidPreview.textContent = initials + '-IND-XXXXXX';
    }
    if (companyInput && uidPreview) { companyInput.addEventListener('input', updateUidPreview); updateUidPreview(); }

    // Sugerencias de graficos segun metricas tildadas
    function renderSuggestions() {
        const box = form.querySelector('[data-chart-suggestions]');
        const checked = Array.from(form.querySelectorAll('input[name="metrics[]"]:checked'));
        box.innerHTML = '';
        if (checked.length === 0) {
            box.innerHTML = '<p class="chart-suggestions__empty">Volvé al paso anterior y elegí al menos una métrica.</p>';
            return;
        }
        checked.forEach((c) => {
            const chart = c.dataset.metricChart || 'line';
            const row = document.createElement('div');
            row.className = 'chart-suggestion';
            row.innerHTML = '<strong>' + c.dataset.metricName + '</strong>' +
                '<span class="chart-suggestion__chart">' + (chartLabels[chart] || chart) + '</span>';
            box.appendChild(row);
        });
    }

    // Las sugerencias se actualizan en vivo al tildar métricas
    form.querySelectorAll('input[name="metrics[]"]').forEach((cb) =>
        cb.addEventListener('change', renderSuggestions));

    // Feedback de envío: deshabilita el botón para evitar dobles creaciones
    form.addEventListener('submit', function () {
        const btn = form.querySelector('[data-wizard-submit]');
        if (btn) { btn.classList.add('is-loading'); btn.disabled = true; btn.textContent = 'Creando espacio…'; }
    });

    show(1);
});
</script>
<?= $this->endSection() ?>
