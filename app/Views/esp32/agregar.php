<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $errors = $errors ?? []; ?>
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
                                    <option value="<?= esc($i['id']) ?>" <?= old('industry_id') == $i['id'] ? 'selected' : '' ?>>
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
                            <input type="text" name="mac_address" placeholder="A4:CF:12:AB:88:01" value="<?= esc(old('mac_address')) ?>" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Sector</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">S</span>
                            <input type="text" name="sector" placeholder="Ej: Línea de prensado" value="<?= esc(old('sector')) ?>">
                        </span>
                    </label>

                    <button type="submit" class="auth-submit">Vincular ESP32</button>
                </form>
            </section>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>
