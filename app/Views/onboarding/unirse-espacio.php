<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/onboarding.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $errors = $errors ?? []; ?>
<main class="auth-page">
    <div class="auth-nebula" aria-hidden="true">
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
    </div>

    <section class="auth-panel">
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
                <p class="auth-pane__eyebrow">Unirse a una industria</p>
                <h1>Unirme a un espacio</h1>
                <p>Ingresá el ID de industria y la contraseña del espacio que te compartió el administrador. Te sumarás como supervisor.</p>
            </div>

            <form action="<?= base_url('unirse-espacio') ?>" method="post" class="auth-form">
                <?= csrf_field() ?>

                <label class="auth-field">
                    <span class="auth-field__label">ID de industria</span>
                    <span class="auth-field__control">
                        <span class="auth-field__icon">#</span>
                        <input type="text" name="industry_uid" placeholder="Ej: SJ-IND-8F42A1" value="<?= esc(old('industry_uid')) ?>" required>
                    </span>
                </label>

                <label class="auth-field">
                    <span class="auth-field__label">Contraseña del espacio</span>
                    <span class="auth-field__control">
                        <span class="auth-field__icon">*</span>
                        <input type="password" name="space_password" placeholder="Contraseña del espacio" required>
                    </span>
                </label>

                <button type="submit" class="auth-submit">Unirme al espacio</button>
            </form>
        </div>

        <p class="auth-foot">
            ¿Querés crear tu propio espacio? <a href="<?= base_url('crear-espacio') ?>">Crear espacio</a>
        </p>
    </section>
</main>
<?= $this->endSection() ?>
