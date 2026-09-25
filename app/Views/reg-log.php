<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>?v=20260609-3">
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
        <div class="auth-switch<?= $activeForm === 'register' ? ' is-register' : '' ?>" role="tablist" aria-label="Seleccionar formulario">
            <button
                class="auth-switch__option<?= $activeForm === 'login' ? ' is-active' : '' ?>"
                type="button"
                data-auth-target="login"
                aria-selected="<?= $activeForm === 'login' ? 'true' : 'false' ?>"
            >
                Iniciar sesión
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

            <div class="auth-panes" data-auth-panes>
            <!-- LOGIN -->
            <section class="auth-pane<?= $activeForm === 'login' ? ' is-active' : '' ?>" data-auth-pane="login" aria-hidden="<?= $activeForm === 'login' ? 'false' : 'true' ?>">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow"></p>
                    <h1>Entrá a tu cuenta</h1>
                    <p>Usá tu email y contraseña para seguir trabajando en el sistema.</p>
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
                        <span class="auth-field__label">Contraseña</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password" placeholder="Ingresá tu contraseña" required>
                        </span>
                        <?php if (isset($errors['password']) && $activeForm === 'login'): ?>
                            <span class="auth-field__error"><?= esc($errors['password']) ?></span>
                        <?php endif; ?>
                    </label>

                    <div class="auth-actions">
                        <a class="auth-link" href="<?= base_url('forgot-password') ?>">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="auth-submit">Iniciar sesión</button>
                </form>
            </section>

            <!-- REGISTER -->
            <section class="auth-pane<?= $activeForm === 'register' ? ' is-active' : '' ?>" data-auth-pane="register" aria-hidden="<?= $activeForm === 'register' ? 'false' : 'true' ?>">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow"></p>
                    <h1>Creá tu cuenta</h1>
                    <p>Registrate en pocos pasos y empezá a usar Preventive Control System.</p>
                </div>

                <form action="<?= base_url('register') ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_mode" value="register">

                    <label class="auth-field">
                        <span class="auth-field__label">Nombre</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">U</span>
                            <input type="text" name="nombre" placeholder="Tu nombre" value="<?= esc(old('nombre')) ?>" required>
                        </span>
                        <?php if (isset($errors['nombre']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['nombre']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Apellido</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">U</span>
                            <input type="text" name="apellido" placeholder="Tu apellido" value="<?= esc(old('apellido')) ?>" required>
                        </span>
                        <?php if (isset($errors['apellido']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['apellido']) ?></span>
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
                        <span class="auth-field__label">Contraseña</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password" placeholder="Mínimo 8 caracteres" minlength="8" autocomplete="new-password" required>
                        </span>
                        <?php if (isset($errors['password']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['password']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Repetir contraseña</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="password_confirm" placeholder="Repetí tu contraseña" minlength="8" autocomplete="new-password" required>
                        </span>
                        <?php if (isset($errors['password_confirm']) && $activeForm === 'register'): ?>
                            <span class="auth-field__error"><?= esc($errors['password_confirm']) ?></span>
                        <?php endif; ?>
                    </label>

                    <button type="submit" class="auth-submit">Crear cuenta</button>
                </form>
            </section>
            </div>
        </div>

        <p class="auth-foot">
            ¿Necesitás más info? <a href="<?= base_url('/') ?>">Volver a la página principal</a>
        </p>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const switchers = document.querySelectorAll('[data-auth-target]');
    const panes = document.querySelectorAll('[data-auth-pane]');
    const panesHost = document.querySelector('[data-auth-panes]');
    const switchRoot = document.querySelector('.auth-switch');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeTarget = document.querySelector('[data-auth-pane].is-active')?.dataset.authPane || 'login';
    let transitionTimer = 0;

    function movePill(target) {
        if (!switchRoot) return;
        switchRoot.classList.toggle('is-register', target === 'register');
    }

    function activatePane(target, immediate) {
        if (target !== 'login' && target !== 'register') return;
        const current = document.querySelector('[data-auth-pane].is-active');
        const next = document.querySelector('[data-auth-pane="' + target + '"]');
        const direction = target === 'register' ? 1 : -1;

        switchers.forEach((b) => {
            const isActive = b.dataset.authTarget === target;
            b.classList.toggle('is-active', isActive);
            b.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        movePill(target);

        if (!next || current === next) {
            activeTarget = target;
            return;
        }

        window.clearTimeout(transitionTimer);
        panes.forEach((pane) => pane.classList.remove('is-entering', 'is-leaving', 'from-left', 'from-right', 'to-left', 'to-right'));

        const startHeight = panesHost ? panesHost.getBoundingClientRect().height : 0;
        next.classList.add('is-active');
        next.setAttribute('aria-hidden', 'false');
        current?.setAttribute('aria-hidden', 'true');

        if (immediate || reduceMotion || !current || !panesHost) {
            current?.classList.remove('is-active');
            activeTarget = target;
            requestAnimationFrame(() => movePill(target));
            return;
        }

        next.classList.add('is-entering', direction > 0 ? 'from-right' : 'from-left');
        current.classList.add('is-leaving', direction > 0 ? 'to-left' : 'to-right');

        panesHost.style.height = startHeight + 'px';
        const nextHeight = next.scrollHeight;
        requestAnimationFrame(() => {
            panesHost.style.height = nextHeight + 'px';
            next.classList.add('is-animating');
            current.classList.add('is-animating');
        });

        transitionTimer = window.setTimeout(() => {
            current.classList.remove('is-active', 'is-leaving', 'is-animating', 'to-left', 'to-right');
            next.classList.remove('is-entering', 'is-animating', 'from-left', 'from-right');
            panesHost.style.height = '';
        }, 280);
        activeTarget = target;
    }

    switchers.forEach((b) => b.addEventListener('click', () => {
        if (b.dataset.authTarget !== activeTarget) activatePane(b.dataset.authTarget, false);
    }));

    // Read ?form=register on load so the landing CTA opens the right tab
    const params = new URLSearchParams(window.location.search);
    const requested = params.get('form');
    if (requested === 'register' || requested === 'login') activeTarget = requested;
    activatePane(activeTarget, true);
});
</script>
<?= $this->endSection() ?>
