<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/onboarding.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = $errors ?? [];
$oldMetrics = (array) old('metrics', []);
$chartLabels = [
    'line'     => 'Grafico de linea',
    'area'     => 'Grafico de area',
    'gauge'    => 'Medidor',
    'bars'     => 'Barras',
    'timeline' => 'Linea de tiempo',
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
                <p class="auth-pane__eyebrow">Onboarding - <?= esc($userName) ?></p>
                <h1>Crear espacio de industria</h1>
                <p>Crea tu primer espacio. Al terminar te guiamos para vincular la ESP32.</p>
            </div>

            <ol class="wizard-steps" data-wizard-indicator>
                <li class="wizard-steps__item is-active" data-step-dot="1"><span>1</span> Espacio</li>
                <li class="wizard-steps__item" data-step-dot="2"><span>2</span> Seguridad</li>
                <li class="wizard-steps__item" data-step-dot="3"><span>3</span> Sensores</li>
            </ol>

            <form action="<?= base_url('crear-espacio') ?>" method="post" class="auth-form" data-wizard-form>
                <?= csrf_field() ?>

                <section class="wizard-pane is-active" data-step-pane="1">
                    <label class="auth-field">
                        <span class="auth-field__label">Nombre de la empresa / industria</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">E</span>
                            <input type="text" name="company_name" placeholder="Ej: San Jose Metalurgica" value="<?= esc(old('company_name')) ?>" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Nombre del espacio</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">#</span>
                            <input type="text" name="space_name" placeholder="Ej: Planta Norte" value="<?= esc(old('space_name')) ?>" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Rubro o sector general</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">S</span>
                            <input type="text" name="sector" placeholder="Ej: Metalurgia, alimenticia, textil" value="<?= esc(old('sector')) ?>">
                        </span>
                    </label>

                    <div class="wizard-uid wizard-uid--compact">
                        <p class="auth-field__label">Vista previa del ID</p>
                        <div class="wizard-uid__code" data-uid-preview>XX-IND-XXXXXX</div>
                        <p class="wizard-hint">El ID real aparece al crear el espacio.</p>
                    </div>

                    <div class="wizard-nav">
                        <span></span>
                        <button type="button" class="auth-submit wizard-next" data-wizard-next>Continuar</button>
                    </div>
                </section>

                <section class="wizard-pane" data-step-pane="2">
                    <p class="wizard-hint">Esta clave sirve para unir supervisores y para ver datos sensibles, como tokens.</p>

                    <label class="auth-field">
                        <span class="auth-field__label">Clave del espacio</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password" placeholder="Minimo 6 caracteres" minlength="6" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Repetir clave</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password_confirm" placeholder="Repeti la clave" minlength="6" required>
                        </span>
                    </label>

                    <div class="wizard-nav">
                        <button type="button" class="auth-link wizard-prev" data-wizard-prev>Volver</button>
                        <button type="button" class="auth-submit wizard-next" data-wizard-next>Continuar</button>
                    </div>
                </section>

                <section class="wizard-pane" data-step-pane="3">
                    <p class="wizard-hint">Elegí una vez. Cada ESP32 nueva tendra estos sensores creados solos.</p>

                    <div class="metric-grid">
                        <?php foreach ($sensorTypes as $type): ?>
                            <label class="metric-option">
                                <input type="checkbox" name="metrics[]" value="<?= esc($type['code']) ?>"
                                    data-metric-name="<?= esc($type['name']) ?>"
                                    data-metric-chart="<?= esc($type['default_chart']) ?>"
                                    <?= in_array($type['code'], $oldMetrics, true) ? 'checked' : '' ?>>
                                <span class="metric-option__box">
                                    <span class="metric-option__name"><?= esc($type['name']) ?></span>
                                    <?php if (!empty($type['unit'])): ?>
                                        <span class="metric-option__unit"><?= esc($type['unit']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="chart-suggestions" data-chart-suggestions>
                        <p class="chart-suggestions__empty" data-chart-empty>Elegí al menos un sensor.</p>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="auth-link wizard-prev" data-wizard-prev>Volver</button>
                        <button type="submit" class="auth-submit">Crear espacio</button>
                    </div>
                </section>
            </form>
        </div>

        <p class="auth-foot">
            Preferis unirte a un espacio existente? <a href="<?= base_url('unirse-espacio') ?>">Unirme a un espacio</a>
        </p>
    </section>
</main>

<script type="application/json" data-chart-labels>
<?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>
</script>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-wizard-form]');
    if (!form) return;

    const panes = Array.from(form.querySelectorAll('[data-step-pane]'));
    const dots = Array.from(document.querySelectorAll('[data-step-dot]'));
    const chartLabels = JSON.parse(document.querySelector('[data-chart-labels]').textContent || '{}');
    let current = 1;

    function renderSuggestions() {
        const box = form.querySelector('[data-chart-suggestions]');
        const checked = Array.from(form.querySelectorAll('input[name="metrics[]"]:checked'));
        box.innerHTML = '';

        if (checked.length === 0) {
            box.innerHTML = '<p class="chart-suggestions__empty">Elegí al menos un sensor.</p>';
            return;
        }

        checked.forEach((input) => {
            const chart = input.dataset.metricChart || 'line';
            const row = document.createElement('div');
            row.className = 'chart-suggestion';
            row.innerHTML = '<strong>' + input.dataset.metricName + '</strong>' +
                '<span class="chart-suggestion__chart">' + (chartLabels[chart] || chart) + '</span>';
            box.appendChild(row);
        });
    }

    function show(step) {
        current = Math.max(1, Math.min(panes.length, step));
        panes.forEach((pane) => pane.classList.toggle('is-active', Number(pane.dataset.stepPane) === current));
        dots.forEach((dot) => dot.classList.toggle('is-active', Number(dot.dataset.stepDot) <= current));
        renderSuggestions();
    }

    function validateCurrent() {
        const pane = panes.find((item) => Number(item.dataset.stepPane) === current);
        const fields = Array.from(pane.querySelectorAll('input[required]'));

        for (const field of fields) {
            if (!field.reportValidity()) return false;
        }

        if (current === 2) {
            const password = pane.querySelector('[name="space_password"]');
            const confirm = pane.querySelector('[name="space_password_confirm"]');
            if (password && confirm && password.value !== confirm.value) {
                confirm.setCustomValidity('Las claves no coinciden.');
                confirm.reportValidity();
                confirm.setCustomValidity('');
                return false;
            }
        }

        if (current === 3 && form.querySelectorAll('input[name="metrics[]"]:checked').length === 0) {
            form.querySelector('input[name="metrics[]"]')?.focus();
            return false;
        }

        return true;
    }

    form.querySelectorAll('[data-wizard-next]').forEach((button) => {
        button.addEventListener('click', () => { if (validateCurrent()) show(current + 1); });
    });

    form.querySelectorAll('[data-wizard-prev]').forEach((button) => {
        button.addEventListener('click', () => show(current - 1));
    });

    form.querySelectorAll('input[name="metrics[]"]').forEach((input) => {
        input.addEventListener('change', renderSuggestions);
    });

    const companyInput = form.querySelector('[name="company_name"]');
    const uidPreview = form.querySelector('[data-uid-preview]');
    function updateUidPreview() {
        const words = (companyInput.value || '').trim().split(/\s+/).filter(Boolean);
        let initials = words.map((word) => word[0]).join('').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 3);
        if (!initials) initials = 'XX';
        if (initials.length === 1) initials += 'X';
        uidPreview.textContent = initials + '-IND-XXXXXX';
    }

    if (companyInput && uidPreview) {
        companyInput.addEventListener('input', updateUidPreview);
        updateUidPreview();
    }

    show(1);
});
</script>
<?= $this->endSection() ?>
