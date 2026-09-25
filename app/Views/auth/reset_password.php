<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = session('errors') ?? [];
$errorMessage = session('error');
?>
<main class="auth-page">
    <section class="auth-panel">
        <div class="auth-card card">
            <?php if ($errorMessage): ?>
                <div class="auth-alert auth-alert--error"><?= esc($errorMessage) ?></div>
            <?php endif; ?>

            <section class="auth-pane is-active">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow">Nueva contraseña</p>
                    <h1>Cambiar contraseña</h1>
                    <p>Define una nueva contraseña para volver a ingresar de forma segura.</p>
                </div>

                <form action="<?= base_url('reset-password') ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

                    <label class="auth-field">
                        <span class="auth-field__label">Nueva contraseña</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password" placeholder="Mínimo 8 caracteres" minlength="8" autocomplete="new-password" required>
                        </span>
                        <?php if (isset($errors['password'])): ?>
                            <span class="auth-field__error"><?= esc($errors['password']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Repetir contraseña</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password_confirm" placeholder="Repetí tu contraseña" minlength="8" autocomplete="new-password" required>
                        </span>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <span class="auth-field__error"><?= esc($errors['password_confirm']) ?></span>
                        <?php endif; ?>
                    </label>

                    <button type="submit" class="auth-submit">Actualizar contraseña</button>
                </form>
            </section>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
