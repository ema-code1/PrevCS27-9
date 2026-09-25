<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/admin-account.css') ?>?v=20260821-2">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors         = session('errors') ?? [];
$errorMessage   = session('error');
$successMessage = session('success');
$nombre   = old('nombre', $user['nombre'] ?? '');
$apellido = old('apellido', $user['apellido'] ?? '');
$email    = $user['email'] ?? '';
$foto     = trim((string) ($user['foto_perfil'] ?? ''));
$initials = strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
$initials = $initials !== '' ? $initials : 'U';
?>
<main class="page page--dashboard page--account">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="acct-head card">
            <div class="acct-head__copy">
                <p class="acct-head__eyebrow">Panel personal</p>
                <h1>Administrar cuenta</h1>
                <p class="acct-head__text">Actualizá tus datos personales y tu contraseña.</p>
            </div>
            <?php if (! empty($user['created_at'])): ?>
                <div class="acct-head__meta">
                    <span>Cuenta creada:</span>
                    <b><?= esc(date('Y-m-d', strtotime((string) $user['created_at']))) ?></b>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($errorMessage): ?>
            <div class="acct-alert acct-alert--error"><?= esc($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="acct-alert acct-alert--success"><?= esc($successMessage) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('admin-account') ?>" method="post" enctype="multipart/form-data" autocomplete="off" novalidate>
            <?= csrf_field() ?>

            <div class="acct-grid">
                <div class="acct-grid__left">
                    <section class="card id-card">
                        <div class="avatar-picker" data-avatar-picker>
                            <input
                                type="file"
                                name="foto_perfil"
                                id="foto_perfil"
                                class="avatar-picker__input"
                                accept="image/png,image/jpeg,image/webp"
                                data-avatar-input
                                data-avatar-max="<?= 5 * 1024 * 1024 ?>"
                            >
                            <label class="avatar" for="foto_perfil" title="Cambiar foto de perfil">
                                <span class="avatar__face">
                                    <?php if ($foto !== ''): ?>
                                        <img class="avatar__img" src="<?= base_url('uploads/avatars/' . rawurlencode($foto)) ?>" alt="Foto de perfil" data-avatar-preview>
                                    <?php else: ?>
                                        <img class="avatar__img" src="" alt="" hidden data-avatar-preview>
                                        <span class="avatar__initials" data-avatar-initials><?= esc($initials) ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="avatar__overlay" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M3 8.25A1.5 1.5 0 0 1 4.5 6.75h2.086a1.5 1.5 0 0 0 1.06-.44l1.208-1.207a1.5 1.5 0 0 1 1.061-.44h4.17a1.5 1.5 0 0 1 1.06.44l1.208 1.208a1.5 1.5 0 0 0 1.06.439H19.5A1.5 1.5 0 0 1 21 8.25v9A1.5 1.5 0 0 1 19.5 18.75h-15A1.5 1.5 0 0 1 3 17.25v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span class="acct-sr-only">Cambiar foto de perfil</span>
                            </label>
                        </div>
                        <div class="id-card__info">
                            <div class="id-card__name"><?= esc(trim($nombre . ' ' . $apellido)) ?></div>
                            <div class="id-card__line">
                                <span class="id-card__email"><?= esc($email) ?></span>
                                <?php if (! empty($user['email_verified_at'])): ?>
                                    <span class="id-card__check" title="Correo verificado">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m4.5 12.75 5.25 5.25 9.75-13.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="acct-sr-only">Correo verificado</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <div class="acct-row">
                    <section class="card sec sec--data">
                        <div class="sec__head">
                            <h2>Datos personales</h2>
                            <p>Aparecen en tu sesión y registros que crees.</p>
                        </div>

                        <div class="form-grid">
                            <label class="field">
                                <span class="field__label">Nombre</span>
                                <input type="text" name="nombre" value="<?= esc($nombre) ?>" required maxlength="150">
                                <?php if (isset($errors['nombre'])): ?>
                                    <span class="field__error"><?= esc($errors['nombre']) ?></span>
                                <?php endif; ?>
                            </label>
                            <label class="field">
                                <span class="field__label">Apellido</span>
                                <input type="text" name="apellido" value="<?= esc($apellido) ?>" required maxlength="150">
                                <?php if (isset($errors['apellido'])): ?>
                                    <span class="field__error"><?= esc($errors['apellido']) ?></span>
                                <?php endif; ?>
                            </label>
                        </div>

                        <div class="form-grid form-grid--row">
                            <div class="field">
                                <span class="field__label">Email</span>
                                <div class="field__row">
                                    <input class="field__email" type="email" value="<?= esc($email) ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de contraseña siempre visible -->
                        <div class="acct-password" id="acct-password">
                            <div class="sec__head">
                                <h2>Cambiar contraseña</h2>
                            </div>

                            <div class="form-grid form-grid--row">
                                <label class="field">
                                    <span class="field__label">Contraseña actual <span class="field__hint">No hace falta para cambiar solo la foto</span></span>
                                    <input type="password" name="current_password" autocomplete="current-password" placeholder="••••••••">
                                    <?php if (isset($errors['current_password'])): ?>
                                        <span class="field__error"><?= esc($errors['current_password']) ?></span>
                                    <?php endif; ?>
                                </label>
                            </div>

                            <div class="form-grid">
                                <label class="field">
                                    <span class="field__label">Nueva contraseña</span>
                                    <input type="password" name="new_password" autocomplete="new-password" minlength="8" placeholder="Mínimo 8 caracteres">
                                    <?php if (isset($errors['new_password'])): ?>
                                        <span class="field__error"><?= esc($errors['new_password']) ?></span>
                                    <?php endif; ?>
                                </label>
                                <label class="field">
                                    <span class="field__label">Confirmar nueva <span class="field__hint">Debe coincidir</span></span>
                                    <input type="password" name="confirm_password" autocomplete="new-password">
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <span class="field__error"><?= esc($errors['confirm_password']) ?></span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>

                        <div class="acct-actions">
                            <a class="btn btn--secondary" href="<?= base_url('principal') ?>">Cancelar</a>
                            <button type="submit" class="btn btn--accent">Guardar cambios</button>
                        </div>
                    </section>

                    <section class="card sec sec--security">
                        <div class="sec__head">
                            <h2>Protección de la cuenta</h2>
                        </div>

                        <div class="security-control">
                            <div class="security-control__copy">
                                <strong>Protección 2FA</strong>
                                <span>Protege tu cuenta mediante una simple verificación con un código enviado a tu Mail</span>
                            </div>
                            <label class="security-switch">
                                <input
                                    type="checkbox"
                                    name="two_factor_enabled"
                                    value="1"
                                    <?= ! empty($user['two_factor_enabled']) ? 'checked' : '' ?>
                                    aria-label="Activar protección 2FA por correo"
                                >
                                <span class="security-switch__track" aria-hidden="true"></span>
                            </label>
                        </div>

                        <?php /* Verificación con número de teléfono 
                        <div class="security-control">
                            <div class="security-control__copy">
                                <strong>Verificación con número de teléfono</strong>
                                <span>Protege tu cuenta con un código enviado a tu Teléfono Móvil</span>
                            </div>
                            <label class="security-switch">
                                <input
                                    type="checkbox"
                                    name="phone_verification_enabled"
                                    value="1"
                                    <?= ! empty($user['phone_verification_enabled']) ? 'checked' : '' ?>
                                    aria-label="Activar verificación con número de teléfono"
                                >
                                <span class="security-switch__track" aria-hidden="true"></span>
                            </label>
                        </div>
                            */ ?>
                    </section>
                    </div>
                </div>

                <div class="acct-grid__right">
                    <?php
                    $subscription = $subscription ?? null;
                    $subLabels = ['pending' => 'Pendiente de pago', 'authorized' => 'Activa', 'paused' => 'Pausada'];
                    ?>
                    <aside class="card sec sec--help">
                        <div class="sec__head">
                            <h2>Suscripción</h2>
                        </div>

                        <?php if ($subscription !== null): ?>
                            <div class="security-control">
                                <div class="security-control__copy">
                                    <strong>PrevCS Pro · <?= esc($subLabels[$subscription['status']] ?? $subscription['status']) ?></strong>
                                    <span><?= esc($subscription['currency']) ?> $<?= esc(number_format((float) $subscription['amount'], 0, ',', '.')) ?> por mes, cobrado por Mercado Pago a <?= esc($subscription['payer_email']) ?>.</span>
                                </div>
                            </div>
                            <?php if (! empty($subscription['mp_preapproval_id'])): ?>
                                <div class="acct-actions">
                                    <button type="submit" class="btn btn--secondary" formnovalidate
                                            formaction="<?= base_url('admin-account/suscripcion/cancelar') ?>"
                                            onclick="return confirm('¿Cancelar la suscripción a PrevCS Pro? No se te va a volver a cobrar.');">
                                        Cancelar suscripción
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="security-control">
                                <div class="security-control__copy">
                                    <strong>Sin suscripción activa</strong>
                                    <span>Podés suscribirte a PrevCS Pro cuando quieras y cancelarlo desde acá.</span>
                                </div>
                            </div>
                            <div class="acct-actions">
                                <a class="btn btn--secondary" href="<?= base_url('pago') ?>">Ver plan</a>
                            </div>
                        <?php endif; ?>
                    </aside>

                    <aside class="card sec sec--help">
                        <div class="sec__head sec__head--icon">
                            <h2>Ayuda</h2>
                            <svg class="sec__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M9.4 9.3a2.7 2.7 0 0 1 5.25.9c0 1.8-2.7 2.7-2.7 2.7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 16.7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <div class="security-control">
                            <div class="security-control__copy">
                                <strong>Cambiar contraseña</strong>
                                <span>Para cambiar la contraseña debe introducir la actual, crear una nueva y confirmarla</span>
                            </div>
                        </div>

                        <div class="security-control">
                            <div class="security-control__copy">
                                <strong>¿Por qué no puedo cambiar mi email?</strong>
                                <span>Por razones de seguridad internas de la organización no está disponible esta opción</span>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </form>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/admin-account.js') ?>?v=20260821-1" defer></script>
<?= $this->endSection() ?>
