<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/admin-account.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors         = session('errors') ?? [];
$errorMessage   = session('error');
$successMessage = session('success');
$nombre   = old('nombre', $user['nombre'] ?? '');
$apellido = old('apellido', $user['apellido'] ?? '');
$email    = $user['email'] ?? '';
$initials = strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="acct-head card">
            <div>
                <p class="acct-head__eyebrow">Panel personal · sesión activa</p>
                <h1>Administrar cuenta</h1>
                <p class="acct-head__text">Actualizá tus datos personales y tu contraseña. Tu email de inicio de sesión no puede modificarse desde acá.</p>
            </div>
            <div class="acct-head__meta">
                <span class="badge badge--success"><span class="dot dot--live"></span> sesión activa</span>
                <span>user&nbsp;id&nbsp;·&nbsp;<b>#<?= esc($user['id']) ?></b></span>
                <?php if (! empty($user['created_at'])): ?>
                    <span>desde&nbsp;<b><?= esc(date('Y-m-d', strtotime((string) $user['created_at']))) ?></b></span>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($errorMessage): ?>
            <div class="acct-alert acct-alert--error"><?= esc($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="acct-alert acct-alert--success"><?= esc($successMessage) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('admin-account') ?>" method="post" autocomplete="off" novalidate>
            <?= csrf_field() ?>

            <div class="acct-grid">
                <div class="acct-grid__left">
                    <section class="card id-card">
                        <span class="avatar"><?= esc($initials !== '' ? $initials : 'U') ?></span>
                        <div>
                            <div class="id-card__name"><?= esc(trim($nombre . ' ' . $apellido)) ?></div>
                            <div class="id-card__email"><?= esc($email) ?></div>
                            <div class="id-card__roles">
                                <span class="badge badge--info">cuenta activa</span>
                                <?php if (! empty($user['email_verified_at'])): ?>
                                    <span class="badge badge--success">correo verificado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="card sec">
                        <div class="sec__head">
                            <h2><span class="num">01</span> Datos personales</h2>
                            <p>Aparecen en tu sesión y registros que crees.</p>
                        </div>
                        <div class="form-grid">
                            <label class="field">
                                <span class="field__label">Nombre <span class="field__hint">requerido</span></span>
                                <input type="text" name="nombre" value="<?= esc($nombre) ?>" required maxlength="150">
                                <?php if (isset($errors['nombre'])): ?>
                                    <span class="field__error">↳ <?= esc($errors['nombre']) ?></span>
                                <?php endif; ?>
                            </label>
                            <label class="field">
                                <span class="field__label">Apellido <span class="field__hint">requerido</span></span>
                                <input type="text" name="apellido" value="<?= esc($apellido) ?>" required maxlength="150">
                                <?php if (isset($errors['apellido'])): ?>
                                    <span class="field__error">↳ <?= esc($errors['apellido']) ?></span>
                                <?php endif; ?>
                            </label>
                            <label class="field field--full">
                                <span class="field__label">Email <span class="field__hint">no se puede cambiar</span></span>
                                <input type="email" value="<?= esc($email) ?>" readonly>
                            </label>
                        </div>
                    </section>

                    <section class="card sec">
                        <div class="sec__head">
                            <h2><span class="num">02</span> Cambiar contraseña</h2>
                            <p>Deja los campos vacios si no queres cambiarla.</p>
                        </div>
                        <div class="form-grid">
                            <label class="field field--full">
                                <span class="field__label">Contraseña actual <span class="field__hint">obligatoria si vas a cambiarla</span></span>
                                <input type="password" name="current_password" autocomplete="current-password">
                                <?php if (isset($errors['current_password'])): ?>
                                    <span class="field__error">↳ <?= esc($errors['current_password']) ?></span>
                                <?php endif; ?>
                            </label>
                            <label class="field">
                                <span class="field__label">Nueva contraseña <span class="field__hint">min 8 chars</span></span>
                                <input type="password" name="new_password" autocomplete="new-password">
                                <?php if (isset($errors['new_password'])): ?>
                                    <span class="field__error">↳ <?= esc($errors['new_password']) ?></span>
                                <?php endif; ?>
                            </label>
                            <label class="field">
                                <span class="field__label">Confirmar nueva <span class="field__hint">debe coincidir</span></span>
                                <input type="password" name="confirm_password" autocomplete="new-password">
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <span class="field__error">↳ <?= esc($errors['confirm_password']) ?></span>
                                <?php endif; ?>
                            </label>
                        </div>
                    </section>
                </div>

                <div class="acct-grid__right">
                    <section class="card sec">
                        <div class="sec__head">
                            <h2><span class="num">03</span> Verificacion en dos pasos</h2>
                            <p>Pide un codigo por correo en cada nuevo inicio de sesion.</p>
                        </div>
                        <div class="security-control">
                            <div class="security-control__copy">
                                <strong>Proteccion 2FA</strong>
                                <span>No se recuerda el dispositivo. El codigo vence en 5 minutos.</span>
                            </div>
                            <label class="security-switch">
                                <input
                                    type="checkbox"
                                    name="two_factor_enabled"
                                    value="1"
                                    <?= ! empty($user['two_factor_enabled']) ? 'checked' : '' ?>
                                    aria-label="Activar verificacion en dos pasos"
                                >
                                <span class="security-switch__track" aria-hidden="true"></span>
                            </label>
                        </div>
                    </section>

                    <section class="card sec">
                        <div class="sec__head">
                            <h2><span class="num">04</span> Sesión &amp; cuenta</h2>
                            <p>Informacion de tu registro.</p>
                        </div>
                        <div class="meta">
                            <div class="meta__row"><span>ID de usuario</span><b>#<?= esc($user['id']) ?></b></div>
                            <?php if (! empty($user['created_at'])): ?>
                                <div class="meta__row"><span>Cuenta creada</span><b><?= esc($user['created_at']) ?></b></div>
                            <?php endif; ?>
                            <div class="meta__row"><span>Email</span><b><?= esc($email) ?></b></div>
                            <div class="meta__row"><span>Correo verificado</span><b><?= ! empty($user['email_verified_at']) ? 'si' : 'no' ?></b></div>
                            <div class="meta__row"><span>2FA</span><b><?= ! empty($user['two_factor_enabled']) ? 'activo' : 'apagado' ?></b></div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="acct-actions">
                <a class="btn btn--secondary btn--sm" href="<?= base_url('principal') ?>">Cancelar</a>
                <button type="submit" class="btn btn--accent btn--sm">Guardar cambios</button>
            </div>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
