<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = session('errors') ?? [];
$errorMessage = session('error');
$successMessage = session('success');
?>
<main class="auth-page">
    <section class="auth-panel">
        <div class="auth-card card">
            <?php if ($errorMessage): ?>
                <div class="auth-alert auth-alert--error"><?= esc($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($successMessage): ?>
                <div class="auth-alert auth-alert--success"><?= esc($successMessage) ?></div>
            <?php endif; ?>

            <section class="auth-pane is-active">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow">Recuperación</p>
                    <h1>Restablecer contraseña</h1>
                    <p>Ingresá tu email y te enviaremos un enlace para crear una nueva contraseña.</p>
                </div>

                <form action="<?= base_url('forgot-password') ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>

                    <label class="auth-field">
                        <span class="auth-field__label">Email</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">@</span>
                            <input type="email" name="email" placeholder="nombre@empresa.com" value="<?= esc(old('email')) ?>" required>
                        </span>
                        <?php if (isset($errors['email'])): ?>
                            <span class="auth-field__error"><?= esc($errors['email']) ?></span>
                        <?php endif; ?>
                    </label>

                    <button type="submit" class="auth-submit">Enviar enlace</button>
                </form>

                <div class="auth-actions auth-actions--top-gap">
                    <a class="auth-link" href="<?= base_url('login') ?>">Volver a iniciar sesión</a>
                </div>
            </section>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
