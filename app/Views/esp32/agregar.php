<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>?v=20260612-2">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = $errors ?? [];
$selectedIndustry = $selectedIndustry ?? null;
$metricsByIndustry = $metricsByIndustry ?? [];
$selectedId = (int) old('industry_id', $selectedIndustry['id'] ?? 0);
$selectedMetrics = $selectedId > 0 ? ($metricsByIndustry[$selectedId] ?? []) : [];
$defaultName = $selectedIndustry !== null ? 'ESP32 ' . $selectedIndustry['space_name'] : 'ESP32 Motor 1';
?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow"><?= $fromOnboarding ? 'Paso 2 de 3' : 'Configurar ESP32' ?></p>
                <h1>Vincular ESP32</h1>
                <p class="dashboard-head__text">Registrala una vez. Los sensores del espacio se cargan solos.</p>
            </div>
        </header>

        <?php if (session('success')): ?>
            <div class="auth-alert auth-alert--success"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (! empty($createdIndustryUid)): ?>
            <section class="panel card esp32-info-strip">
                <strong>ID del espacio</strong>
                <code class="esp32-token"><?= esc($createdIndustryUid) ?></code>
                <span>Guardalo para sumar supervisores despues.</span>
            </section>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="auth-alert auth-alert--error">
                <?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($industries === []): ?>
            <section class="panel card esp32-empty">
                <h3>No administras ningun espacio</h3>
                <p>Primero crea un espacio. Después vinculas la ESP32.</p>
                <a class="btn btn--primary" href="<?= base_url('crear-espacio') ?>">Crear espacio</a>
            </section>
        <?php else: ?>
            <div class="esp32-setup-layout">
            <section class="panel card esp32-form-card">
                <form action="<?= base_url('esp32/agregar') ?>" method="post" class="auth-form" data-esp32-form>
                    <?= csrf_field() ?>

                    <?php if ($selectedIndustry !== null): ?>
                        <input type="hidden" name="industry_id" value="<?= esc($selectedIndustry['id']) ?>">
                        <div class="esp32-readonly-box">
                            <span>Espacio</span>
                            <strong><?= esc($selectedIndustry['space_name']) ?></strong>
                            <small><?= esc($selectedIndustry['company_name']) ?> - <?= esc($selectedIndustry['industry_uid']) ?></small>
                        </div>
                    <?php else: ?>
                        <label class="auth-field">
                            <span class="auth-field__label">Espacio</span>
                            <span class="auth-field__control">
                                <span class="auth-field__icon">#</span>
                                <select name="industry_id" class="esp32-select" required data-space-select>
                                    <option value="" disabled <?= $selectedId === 0 ? 'selected' : '' ?>>Elegír espacio</option>
                                    <?php foreach ($industries as $space): ?>
                                        <option value="<?= esc($space['id']) ?>" data-space-name="<?= esc($space['space_name']) ?>" <?= $selectedId === (int) $space['id'] ? 'selected' : '' ?>>
                                            <?= esc($space['space_name']) ?> - <?= esc($space['company_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </span>
                        </label>
                    <?php endif; ?>

                    <label class="auth-field">
                        <span class="auth-field__label">Nombre de la placa</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">E</span>
                            <input type="text" name="name" value="<?= esc(old('name', $defaultName)) ?>" required>
                        </span>
                    </label>

                    <div class="esp32-sensor-preview" data-sensor-preview>
                        <?php if ($selectedMetrics === []): ?>
                            <p class="text-muted">Este espacio no tiene sensores definidos.</p>
                        <?php else: ?>
                            <strong>Sensores que se crean solos</strong>
                            <div class="esp32-chip-list">
                                <?php foreach ($selectedMetrics as $metric): ?>
                                    <span class="chip chip--soft"><?= esc($metric['name']) ?><?= ! empty($metric['unit']) ? ' - ' . esc($metric['unit']) : '' ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="auth-submit">Vincular ESP32</button>
                </form>
            </section>

            <aside class="esp32-setup-guide" aria-label="Guía de vinculación">
                <h2>Cómo vincular tu placa</h2>
                <p>Completás el espacio y el nombre. PrevCS genera el token y, en la pantalla siguiente, te da un <strong>QR</strong> y un <strong>botón</strong> para configurar la placa.</p>
                <div class="esp32-setup-guide__steps">
                    <div>
                        <span>1</span>
                        <p>Elegí el <strong>espacio</strong> y el <strong>nombre</strong> acá al lado y tocá <strong>Vincular ESP32</strong>.</p>
                    </div>
                    <div>
                        <span>2</span>
                        <p>Conectate al WiFi <strong>PREVCS-CONFIG</strong> que crea la placa y abrí su formulario con el <strong>QR</strong> o el <strong>botón</strong>.</p>
                    </div>
                    <div>
                        <span>3</span>
                        <p>Elegí tu red WiFi en la placa y guardá. Se conecta y se <strong>vincula sola</strong>.</p>
                    </div>
                </div>
            </aside>
            </div>
        <?php endif; ?>
    </section>
</main>

<script type="application/json" data-space-metrics>
<?= json_encode($metricsByIndustry, JSON_UNESCAPED_UNICODE) ?>
</script>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260820-1" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const spaceSelect = document.querySelector('[data-space-select]');
    const nameInput = document.querySelector('input[name="name"]');
    const preview = document.querySelector('[data-sensor-preview]');
    const metrics = JSON.parse(document.querySelector('[data-space-metrics]')?.textContent || '{}');

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    }

    if (spaceSelect && preview) {
        spaceSelect.addEventListener('change', function () {
            const selected = spaceSelect.options[spaceSelect.selectedIndex];
            const list = metrics[spaceSelect.value] || [];

            if (nameInput && (!nameInput.value || nameInput.value === 'ESP32 Motor 1')) {
                nameInput.value = 'ESP32 ' + (selected?.dataset.spaceName || 'Motor 1');
            }

            if (!list.length) {
                preview.innerHTML = '<p class="text-muted">Este espacio no tiene sensores definidos.</p>';
                return;
            }

            preview.innerHTML = '<strong>Sensores que se crean solos</strong><div class="esp32-chip-list">' +
                list.map((metric) => '<span class="chip chip--soft">' + escapeHtml(metric.name) + (metric.unit ? ' - ' + escapeHtml(metric.unit) : '') + '</span>').join('') +
                '</div>';
        });
    }
});
</script>
<?= $this->endSection() ?>
