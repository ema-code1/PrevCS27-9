<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors         = session('errors') ?? [];
$errorMessage   = session('error');
$successMessage = session('success');
$activeForm     = old('form_mode', $activeForm ?? ($_GET['form'] ?? 'login'));
if (!in_array($activeForm, ['login', 'register'], true)) { $activeForm = 'login'; }
?>
<main class="auth-page">
    <div class="auth-nebula" aria-hidden="true">
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
    </div>
    <section class="auth-panel">
        <div class="auth-switch" role="tablist" aria-label="Seleccionar formulario">
            <button
                class="auth-switch__option<?= $activeForm === 'login' ? ' is-active' : '' ?>"
                type="button"
                data-auth-target="login"
                aria-selected="<?= $activeForm === 'login' ? 'true' : 'false' ?>"
            >
                Iniciar sesion
            </button>
            <button
                class="auth-switch__option<?= $activeForm === 'register' ? ' is-active' : '' ?>"
                type="button"
                data-auth-target="register"
                aria-selected="<?= $activeForm === 'register' ? 'true' : 'false' ?>"
            >
                Crear cuenta
            </button>
        </div>

        <div class="auth-card card">
            <?php if ($errorMessage): ?>
                <div class="auth-alert auth-alert--error"><?= esc($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($successMessage): ?>
                <div class="auth-alert auth-alert--success"><?= esc($successMessage) ?></div>
            <?php endif; ?>

            <!-- LOGIN -->
            <section class="auth-pane<?= $activeForm === 'login' ? ' is-active' : '' ?>" data-auth-pane="login">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow">Acceso rapido</p>
                    <h1>Entra a tu cuenta</h1>
                    <p>Usa tu email y contrasena para seguir trabajando en el sistema.</p>
                </div>

                <form action="<?= base_url('login') ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_mode" value="login">

                    <label class="auth-field">
                        <span class="auth-field__label">Email</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">@</span>
                            <input type="email" name="email" placeholder="nombre@empresa.com" value="<?= esc(old('email')) ?>" required>
                        </span>
                        <?php if (isset($errors['email']) && $activeForm === 'login'): ?>
                            <span class="auth-field__error"><?= esc($errors['email']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Contrasena</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password" placeholder="Ingresa tu contrasena" required>
                        </span>
                        <?php if (isset($errors['password']) && $activeForm === 'login'): ?>
                            <span class="auth-field__error"><?= esc($errors['password']) ?></span>
                        <?php endif; ?>
                    </label>

                    <div class="auth-actions">
                        <a class="auth-link" href="<?= base_url('forgot-password') ?>">¿Olvidaste tu contrasena?</a>
                    </div>

                    <button type="submit" class="auth-submit">Iniciar sesion</button>
                </form>
            </section>

            <!-- REGISTER -->
            <section class="auth-pane<?= $activeForm === 'register' ? ' is-active' : '' ?>" data-auth-pane="register">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow">Primer ingreso</p>
                    <h1>Crea tu cuenta</h1>
                    <p>Registrate en pocos pasos y empeza a usar Prevention Control System.</p>
                </div>

                <form action="<?= base_url('register') ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_mode" value="register">

                    <label class="auth-field">
                        <span class="auth-field__label">Nombre</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">U</span>
                            <input type="text" name="name" placeholder="Tu nombre completo" value="<?= esc(old('name')) ?>" required>
                        </span>
                        <?php if (isset($errors['name']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['name']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Email</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">@</span>
                            <input type="email" name="email" placeholder="nombre@empresa.com" value="<?= esc(old('email')) ?>" required>
                        </span>
                        <?php if (isset($errors['email']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['email']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Contrasena</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password" placeholder="Minimo 6 caracteres" required>
                        </span>
                        <?php if (isset($errors['password']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['password']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Repetir contrasena</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password_confirm" placeholder="Repeti tu contrasena" required>
                        </span>
                        <?php if (isset($errors['password_confirm']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['password_confirm']) ?></span>
                        <?php endif; ?>
                    </label>

                    <button type="submit" class="auth-submit">Crear cuenta</button>
                </form>
            </section>
        </div>

        <p class="auth-foot">
            ¿Necesitas mas info? <a href="<?= base_url('/') ?>">Volver a la pagina principal</a>
        </p>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const switchers = document.querySelectorAll('[data-auth-target]');
    const panes = document.querySelectorAll('[data-auth-pane]');

    function activatePane(target) {
        panes.forEach((p) => p.classList.toggle('is-active', p.dataset.authPane === target));
        switchers.forEach((b) => {
            const isActive = b.dataset.authTarget === target;
            b.classList.toggle('is-active', isActive);
            b.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    switchers.forEach((b) => b.addEventListener('click', () => activatePane(b.dataset.authTarget)));

    // Read ?form=register on load so the landing CTA opens the right tab
    const params = new URLSearchParams(window.location.search);
    const requested = params.get('form');
    if (requested === 'register' || requested === 'login') activatePane(requested);
});
</script>
<?= $this->endSection() ?>
