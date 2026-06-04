<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = $errors ?? [];
$preselect = $preselect ?? 0;
$fromOnboarding = $fromOnboarding ?? false;
?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Vincular dispositivo</p>
                <h1>Añadir ESP</h1>
                <p class="dashboard-head__text">Registrá una placa ESP32 por su MAC y vinculala a una industria.</p>
            </div>
        </header>

        <?php if ($fromOnboarding): ?>
            <div class="auth-alert auth-alert--success">
                Tu espacio está listo. Vinculá tu primera ESP32 para empezar a ver datos reales en el panel.
            </div>
        <?php elseif (session('success')): ?>
            <div class="auth-alert auth-alert--success"><?= esc(session('success')) ?></div>
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
                <h3>No administrás ningún espacio</h3>
                <p>Para añadir una ESP32 necesitás ser <strong>admin_principal</strong> de una industria. Creá un espacio o pedí al admin que la registre.</p>
                <a class="btn btn--primary" href="<?= base_url('crear-espacio') ?>">Crear espacio</a>
            </section>
        <?php else: ?>
            <section class="panel card esp32-form-card">
                <form action="<?= base_url('esp32/agregar') ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>

                    <label class="auth-field">
                        <span class="auth-field__label">Industria</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">#</span>
                            <select name="industry_id" class="esp32-select" required>
                                <?php foreach ($industries as $i): ?>
                                    <?php $sel = old('industry_id') == $i['id'] || (old('industry_id') === null && (int) $preselect === (int) $i['id']); ?>
                                    <option value="<?= esc($i['id']) ?>" <?= $sel ? 'selected' : '' ?>>
                                        <?= esc($i['company_name']) ?> · <?= esc($i['space_name']) ?> (<?= esc($i['industry_uid']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Nombre de la ESP32</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">E</span>
                            <input type="text" name="name" placeholder="Ej: ESP32 Motor Línea 1" value="<?= esc(old('name')) ?>" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Dirección MAC</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">::</span>
                            <input type="text" name="mac_address" data-mac-input placeholder="A4:CF:12:AB:88:01" value="<?= esc(old('mac_address')) ?>" required autocomplete="off">
                        </span>
                        <small class="auth-field__hint" data-mac-hint>Formato esperado: XX:XX:XX:XX:XX:XX (la que imprime tu ESP32 en consola).</small>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Sector</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">S</span>
                            <input type="text" name="sector" placeholder="Ej: Línea de prensado" value="<?= esc(old('sector')) ?>">
                        </span>
                    </label>

                    <button type="submit" class="auth-submit" data-esp32-submit>Vincular ESP32</button>
                </form>
            </section>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const macInput = document.querySelector('[data-mac-input]');
    const macHint  = document.querySelector('[data-mac-hint]');
    const form     = macInput ? macInput.closest('form') : null;
    const submit   = document.querySelector('[data-esp32-submit]');

    function validateMac() {
        if (!macInput || !macHint) return false;
        const hex = (macInput.value || '').replace(/[^0-9A-Fa-f]/g, '');
        macHint.classList.remove('is-ok', 'is-error');
        if (hex.length === 0) {
            macHint.textContent = 'Formato esperado: XX:XX:XX:XX:XX:XX (la que imprime tu ESP32 en consola).';
            macInput.setCustomValidity('');
            return false;
        }
        if (hex.length === 12) {
            const pretty = (hex.toUpperCase().match(/.{2}/g) || []).join(':');
            macHint.textContent = '✓ MAC válida: ' + pretty;
            macHint.classList.add('is-ok');
            macInput.setCustomValidity('');
            return true;
        }
        macHint.textContent = 'MAC incompleta: ' + hex.length + '/12 dígitos hex.';
        macHint.classList.add('is-error');
        macInput.setCustomValidity('La MAC debe tener 12 dígitos hexadecimales.');
        return false;
    }

    if (macInput) {
        macInput.addEventListener('input', validateMac);
        if (macInput.value) validateMac();
    }

    if (form && submit) {
        form.addEventListener('submit', function (e) {
            if (!validateMac()) { macInput.reportValidity(); e.preventDefault(); return; }
            submit.classList.add('is-loading');
            submit.disabled = true;
            submit.textContent = 'Vinculando…';
        });
    }
});
</script>
<?= $this->endSection() ?>
