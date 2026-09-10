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
                    <p class="auth-pane__eyebrow">Espacio</p>
                    <h1>Cambiar clave</h1>
                    <p><?= esc($spaceName ?? 'Este espacio') ?> tendrá una clave nueva.</p>
                </div>

                <form action="<?= base_url('espacios/clave') ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

                    <label class="auth-field">
                        <span class="auth-field__label">Clave nueva</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password" placeholder="Mínimo 6 caracteres" required>
                        </span>
                        <?php if (isset($errors['space_password'])): ?>
                            <span class="auth-field__error"><?= esc($errors['space_password']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Repetir clave</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password_confirm" placeholder="Repetí la clave" required>
                        </span>
                        <?php if (isset($errors['space_password_confirm'])): ?>
                            <span class="auth-field__error"><?= esc($errors['space_password_confirm']) ?></span>
                        <?php endif; ?>
                    </label>

                    <button type="submit" class="auth-submit">Guardar clave</button>
                </form>
            </section>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
