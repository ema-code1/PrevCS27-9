<?= $this->extend('templates/layout') ?>

<?= $this->section('content') ?>
<?php
$errors      = is_array($errors ?? null) ? $errors : [];
$nombre      = old('nombre', $user['nombre'] ?? '');
$apellido    = old('apellido', $user['apellido'] ?? '');
$email       = old('email', $user['email'] ?? '');
$displayName = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''));
$inicial     = strtoupper(mb_substr($displayName !== '' ? $displayName : ($user['email'] ?? '?'), 0, 1));
$flashOk     = session()->getFlashdata('success');
$flashErr    = session()->getFlashdata('error');
?>
<main class="page page--dashboard page--account">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area account-shell">
        <form id="account-form" class="account-form" method="post" action="<?= base_url('admin-account/update') ?>" novalidate autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="account_token" value="<?= esc($account_token, 'attr') ?>">

            <header class="dashboard-head card account-head">
                <div>
                    <p class="dashboard-head__eyebrow">Usuario: <?= esc($user['email'] ?? '') ?></p>
                    <h1>Mi cuenta</h1>
                    <p class="dashboard-head__text">Actualiza tus datos personales y, si lo necesitas, tu contrasena.</p>
                </div>
                <div class="account-head__actions">
                    <button type="submit" class="btn btn--primary account-save-btn">
                        <span class="account-save-btn__check" aria-hidden="true">&#10003;</span>
                        <span>Guardar</span>
                    </button>
                </div>
            </header>

            <section class="panel card account-identity">
                <div class="account-identity__avatar" aria-hidden="true">
                    <span class="account-identity__avatar-placeholder"><?= esc($inicial) ?></span>
                </div>
                <div class="account-identity__meta">
                    <h2 class="account-identity__name"><?= esc($displayName !== '' ? $displayName : 'Sin nombre') ?></h2>
                    <p class="account-identity__line">
                        <span class="account-identity__email"><?= esc($user['email'] ?? '') ?></span>
                    </p>
                    <p class="account-identity__line account-identity__line--muted">Los cambios se aplican al guardar.</p>
                </div>
            </section>

            <section class="panel card account-section">
                <div class="panel__header">
                    <h3>Cuenta</h3>
                </div>

                <div class="account-grid">
                    <label class="account-grid__label" for="nombre">
                        Nombre <span class="account-grid__required" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="nombre"
                        name="nombre"
                        class="input<?= isset($errors['nombre']) ? ' input--error' : '' ?>"
                        type="text"
                        required
                        maxlength="100"
                        value="<?= esc($nombre, 'attr') ?>"
                        autocomplete="given-name"
                        aria-describedby="<?= isset($errors['nombre']) ? 'err-nombre' : '' ?>">
                    <?php if (isset($errors['nombre'])): ?>
                        <p id="err-nombre" class="account-grid__error"><?= esc($errors['nombre']) ?></p>
                    <?php endif; ?>

                    <label class="account-grid__label" for="apellido">Apellido</label>
                    <input
                        id="apellido"
                        name="apellido"
                        class="input<?= isset($errors['apellido']) ? ' input--error' : '' ?>"
                        type="text"
                        maxlength="100"
                        value="<?= esc($apellido, 'attr') ?>"
                        autocomplete="family-name"
                        aria-describedby="<?= isset($errors['apellido']) ? 'err-apellido' : '' ?>">
                    <?php if (isset($errors['apellido'])): ?>
                        <p id="err-apellido" class="account-grid__error"><?= esc($errors['apellido']) ?></p>
                    <?php endif; ?>

                    <label class="account-grid__label" for="email">
                        Email <span class="account-grid__required" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="email"
                        name="email"
                        class="input<?= isset($errors['email']) ? ' input--error' : '' ?>"
                        type="email"
                        required
                        maxlength="200"
                        value="<?= esc($email, 'attr') ?>"
                        autocomplete="email"
                        aria-describedby="<?= isset($errors['email']) ? 'err-email' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <p id="err-email" class="account-grid__error"><?= esc($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel card account-section">
                <div class="panel__header">
                    <h3>Cambiar contrasena</h3>
                    <span class="account-section__hint">Dejalo en blanco para mantener la actual.</span>
                </div>

                <div class="account-grid">
                    <label class="account-grid__label" for="current_password">Contrasena actual</label>
                    <input
                        id="current_password"
                        name="current_password"
                        class="input<?= isset($errors['current_password']) ? ' input--error' : '' ?>"
                        type="password"
                        maxlength="200"
                        autocomplete="current-password"
                        aria-describedby="<?= isset($errors['current_password']) ? 'err-current' : '' ?>">
                    <?php if (isset($errors['current_password'])): ?>
                        <p id="err-current" class="account-grid__error"><?= esc($errors['current_password']) ?></p>
                    <?php endif; ?>

                    <label class="account-grid__label" for="new_password">Nueva contrasena</label>
                    <input
                        id="new_password"
                        name="new_password"
                        class="input<?= isset($errors['new_password']) ? ' input--error' : '' ?>"
                        type="password"
                        minlength="6"
                        maxlength="200"
                        autocomplete="new-password"
                        aria-describedby="<?= isset($errors['new_password']) ? 'err-new' : '' ?>">
                    <?php if (isset($errors['new_password'])): ?>
                        <p id="err-new" class="account-grid__error"><?= esc($errors['new_password']) ?></p>
                    <?php endif; ?>

                    <label class="account-grid__label" for="new_password_confirm">Repetir nueva</label>
                    <input
                        id="new_password_confirm"
                        name="new_password_confirm"
                        class="input<?= isset($errors['new_password_confirm']) ? ' input--error' : '' ?>"
                        type="password"
                        minlength="6"
                        maxlength="200"
                        autocomplete="new-password"
                        aria-describedby="<?= isset($errors['new_password_confirm']) ? 'err-confirm' : '' ?>">
                    <?php if (isset($errors['new_password_confirm'])): ?>
                        <p id="err-confirm" class="account-grid__error"><?= esc($errors['new_password_confirm']) ?></p>
                    <?php endif; ?>
                </div>
            </section>
        </form>
    </section>
</main>

<div class="toast-region" data-toast-region aria-live="polite" aria-atomic="false"></div>

<span
    id="account-flash"
    hidden
    data-success="<?= esc($flashOk ?? '', 'attr') ?>"
    data-error="<?= esc($flashErr ?? '', 'attr') ?>"></span>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
(function () {
    const form = document.getElementById('account-form');
    const region = document.querySelector('[data-toast-region]');
    if (! form || ! region) {
        return;
    }

    const fields = Array.from(form.querySelectorAll('input[name]:not([type="hidden"])'));
    const initial = new Map();
    fields.forEach(function (field) { initial.set(field, field.value); });

    let isDirty = false;
    let isSubmitting = false;

    function refreshDirty() {
        isDirty = fields.some(function (field) {
            return initial.get(field) !== field.value;
        });
    }

    form.addEventListener('input', refreshDirty);
    form.addEventListener('change', refreshDirty);

    form.addEventListener('submit', function () {
        isSubmitting = true;
        isDirty = false;
    });

    window.addEventListener('beforeunload', function (event) {
        if (isDirty && ! isSubmitting) {
            event.preventDefault();
            event.returnValue = '';
            return '';
        }
    });

    // Intercepta navegacion interna por enlace para mostrar un toast con accion.
    document.addEventListener('click', function (event) {
        if (! isDirty) {
            return;
        }
        const link = event.target.closest('a[href]');
        if (! link) {
            return;
        }
        const href = link.getAttribute('href');
        if (! href || href === '#' || href.startsWith('javascript:') || link.target === '_blank') {
            return;
        }
        // Mismo origen y ruta distinta de la actual.
        let url;
        try {
            url = new URL(link.href, window.location.href);
        } catch (e) {
            return;
        }
        if (url.origin !== window.location.origin) {
            return;
        }
        if (url.pathname === window.location.pathname && url.search === window.location.search) {
            return;
        }
        event.preventDefault();
        showToast('warning', 'Tenes cambios sin guardar.', {
            sticky: true,
            actions: [
                { label: 'Permanecer', onClick: function () {} },
                {
                    label: 'Salir sin guardar',
                    primary: true,
                    onClick: function () {
                        isDirty = false;
                        window.location.href = link.href;
                    }
                }
            ]
        });
    });

    function showToast(type, message, opts) {
        opts = opts || {};
        const el = document.createElement('div');
        el.className = 'toast toast--' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');

        const icon = document.createElement('span');
        icon.className = 'toast__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = type === 'success' ? '✓'
            : type === 'error' ? '!'
            : type === 'warning' ? '⚠'
            : 'ℹ';

        const body = document.createElement('div');
        body.className = 'toast__body';

        const msg = document.createElement('p');
        msg.className = 'toast__message';
        msg.textContent = message;
        body.appendChild(msg);

        if (Array.isArray(opts.actions) && opts.actions.length) {
            const actions = document.createElement('div');
            actions.className = 'toast__actions';
            opts.actions.forEach(function (action) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'toast__btn' + (action.primary ? ' toast__btn--primary' : '');
                btn.textContent = action.label;
                btn.addEventListener('click', function () {
                    try { action.onClick(); } catch (e) {}
                    dismiss();
                });
                actions.appendChild(btn);
            });
            body.appendChild(actions);
        }

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast__close';
        close.setAttribute('aria-label', 'Cerrar notificacion');
        close.textContent = '×';
        close.addEventListener('click', dismiss);

        el.appendChild(icon);
        el.appendChild(body);
        el.appendChild(close);
        region.appendChild(el);

        let dismissTimer = null;
        function dismiss() {
            if (dismissTimer) {
                window.clearTimeout(dismissTimer);
                dismissTimer = null;
            }
            el.classList.add('is-leaving');
            window.setTimeout(function () { el.remove(); }, 220);
        }

        window.requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });

        if (! opts.sticky) {
            dismissTimer = window.setTimeout(dismiss, opts.duration || 4200);
        }

        return dismiss;
    }

    // Consumo de flashdata renderizado por el servidor.
    const flash = document.getElementById('account-flash');
    if (flash) {
        const ok = flash.getAttribute('data-success');
        const err = flash.getAttribute('data-error');
        if (ok) { showToast('success', ok); }
        if (err) { showToast('error', err); }
    }
})();
</script>
<?= $this->endSection() ?>
