<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
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
            <section class="esp32-setup-guide">
                <div class="esp32-setup-guide__intro">
                    <span class="esp32-setup-guide__step">Guia de linkeo</span>
                    <h2>Conecta la placa al espacio sin escribir la API a mano</h2>
                    <p>Todas las SP liberan la misma red de configuracion. Desde tu celular o computadora, conectate al WiFi <code>PREVCS-CONFIG</code> y despues abri el formulario interno de la placa.</p>
                </div>
                <a class="esp32-ap-link" href="http://192.168.4.1">
                    <span>Formulario de la SP</span>
                    <strong>192.168.4.1</strong>
                </a>
                <div class="esp32-setup-guide__steps">
                    <div>
                        <span>1</span>
                        <p>Conectate al AP <strong>PREVCS-CONFIG</strong> que libera la placa.</p>
                    </div>
                    <div>
                        <span>2</span>
                        <p>Entra a <strong>192.168.4.1</strong>, copia la MAC que muestra y usala abajo.</p>
                    </div>
                    <div>
                        <span>3</span>
                        <p>Al vincularla, PrevCS genera el token y un link listo con API, token y nombre.</p>
                    </div>
                </div>
            </section>

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

                    <label class="auth-field">
                        <span class="auth-field__label">MAC de la ESP32</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">::</span>
                            <input type="text" name="mac_address" placeholder="A4:CF:12:AB:88:01" value="<?= esc(old('mac_address')) ?>" required data-mac-input>
                        </span>
                        <small class="field-help" data-mac-help>Formato: AA:BB:CC:DD:EE:FF</small>
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
        <?php endif; ?>
    </section>
</main>

<script type="application/json" data-space-metrics>
<?= json_encode($metricsByIndustry, JSON_UNESCAPED_UNICODE) ?>
</script>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const macInput = document.querySelector('[data-mac-input]');
    const macHelp = document.querySelector('[data-mac-help]');
    const spaceSelect = document.querySelector('[data-space-select]');
    const nameInput = document.querySelector('input[name="name"]');
    const preview = document.querySelector('[data-sensor-preview]');
    const metrics = JSON.parse(document.querySelector('[data-space-metrics]')?.textContent || '{}');

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    }

    if (macInput && macHelp) {
        macInput.addEventListener('input', function () {
            const clean = macInput.value.trim();
            const valid = /^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/.test(clean) || /^[0-9A-Fa-f]{12}$/.test(clean);
            macHelp.textContent = clean === '' ? 'Formato: AA:BB:CC:DD:EE:FF' : (valid ? 'MAC valida' : 'Revisa la MAC');
            macHelp.classList.toggle('is-ok', valid);
            macHelp.classList.toggle('is-error', clean !== '' && !valid);
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
