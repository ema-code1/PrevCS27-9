<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<style>
  .acct-head { display: flex; align-items: flex-end; justify-content: space-between;
    gap: 20px; padding: 24px 28px; flex-wrap: wrap; }
  .acct-head__eyebrow { font-family: var(--font-mono); font-size: 11.5px; letter-spacing: .06em;
    text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
  .acct-head h1 { font-size: clamp(28px, 3.4vw, 40px); letter-spacing: -.03em;
    font-weight: var(--font-weight-medium); }
  .acct-head__text { margin-top: 8px; color: var(--text-muted); font-size: 14.5px; max-width: 56ch; }
  .acct-head__meta { display: flex; flex-direction: column; align-items: flex-end; gap: 6px;
    font-family: var(--font-mono); font-size: 11.5px; color: var(--text-muted); }
  .acct-head__meta b { color: var(--text-primary); font-weight: 500; }

  .acct-grid { display: grid; grid-template-columns: 1fr; gap: var(--content-gap); }
  @media (min-width: 1080px) {
    .acct-grid { grid-template-columns: 1.4fr 1fr; align-items: start; }
    .acct-grid__left, .acct-grid__right { display: flex; flex-direction: column; gap: var(--content-gap); }
  }

  .id-card { display: grid; grid-template-columns: auto 1fr; gap: 20px;
    align-items: center; padding: 20px 24px; }
  .avatar { --size: 84px; position: relative; width: var(--size); height: var(--size);
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-2));
    color: #fff; display: grid; place-items: center;
    font-size: 32px; font-weight: 500; letter-spacing: -.02em;
    overflow: hidden;
    box-shadow: 0 8px 28px -10px var(--accent-ring), 0 0 0 4px var(--bg-card);
    flex-shrink: 0; }
  .id-card__name { font-size: 22px; font-weight: 500; letter-spacing: -.02em; }
  .id-card__email { margin-top: 4px; color: var(--text-muted);
    font-family: var(--font-mono); font-size: 13px; }
  .id-card__roles { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }

  .sec { padding: 22px 24px; display: flex; flex-direction: column; gap: 18px; }
  .sec__head h2 { font-size: 16px; font-weight: 500; letter-spacing: -.01em;
    display: flex; align-items: center; gap: 10px; }
  .sec__head h2 .num { font-family: var(--font-mono); font-size: 10.5px;
    color: var(--text-faint); background: var(--bg-card-soft);
    padding: 3px 7px; border-radius: 4px; letter-spacing: .04em; }
  .sec__head p { color: var(--text-muted); font-size: 13px; margin-top: 4px; }

  .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 18px; }
  @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

  .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
  .field--full { grid-column: 1 / -1; }
  .field__label { font-size: 12.5px; font-weight: 500; color: var(--text-secondary);
    display: flex; align-items: center; justify-content: space-between; gap: 8px; }
  .field__hint { font-family: var(--font-mono); font-size: 10.5px; color: var(--text-faint);
    text-transform: uppercase; letter-spacing: .05em; }
  .field input { width: 100%; padding: 11px 13px; border-radius: var(--radius-md);
    border: 1px solid var(--hair); background: var(--bg-card); color: var(--text-primary);
    font-family: inherit; font-size: 14px; }
  .field input[readonly] { background: var(--bg-card-soft); color: var(--text-muted);
    cursor: not-allowed; }
  .field__error { font-size: 12px; color: var(--error); margin-top: 2px;
    font-family: var(--font-mono); letter-spacing: -.005em; }

  .meta { display: flex; flex-direction: column; padding: 6px 0; }
  .meta__row { display: flex; justify-content: space-between; align-items: center;
    padding: 12px 24px; border-bottom: 1px dashed var(--hair); font-size: 13px; }
  .meta__row:last-child { border-bottom: 0; }
  .meta__row span { color: var(--text-muted); }
  .meta__row b { font-family: var(--font-mono); font-weight: 500; letter-spacing: -.01em; }

  .acct-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
  .acct-actions .btn--accent { background: var(--accent); color: #fff;
    box-shadow: 0 1px 0 rgba(255,255,255,.12) inset, 0 6px 16px -6px var(--accent-ring); }
  .acct-actions .btn--accent:hover { background: var(--accent-hover); }

  .acct-alert { padding: 11px 14px; border-radius: var(--radius-md); font-size: 13px;
    margin-bottom: 14px; }
  .acct-alert--success { background: var(--success-soft); color: var(--success); }
  .acct-alert--error { background: var(--error-soft); color: var(--error); }
</style>
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
                <p class="acct-head__eyebrow">Panel personal · sesion activa</p>
                <h1>Administrar cuenta</h1>
                <p class="acct-head__text">Actualiza tus datos personales y tu contrasena. Tu email de inicio de sesion no puede modificarse desde aca.</p>
            </div>
            <div class="acct-head__meta">
                <span class="badge badge--success"><span class="dot dot--live"></span> sesion activa</span>
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
                            </div>
                        </div>
                    </section>

                    <section class="card sec">
                        <div class="sec__head">
                            <h2><span class="num">01</span> Datos personales</h2>
                            <p>Aparecen en tu sesion y registros que crees.</p>
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
                            <h2><span class="num">02</span> Cambiar contrasena</h2>
                            <p>Deja los campos vacios si no queres cambiarla.</p>
                        </div>
                        <div class="form-grid">
                            <label class="field field--full">
                                <span class="field__label">Contrasena actual <span class="field__hint">obligatoria si vas a cambiarla</span></span>
                                <input type="password" name="current_password" autocomplete="current-password">
                                <?php if (isset($errors['current_password'])): ?>
                                    <span class="field__error">↳ <?= esc($errors['current_password']) ?></span>
                                <?php endif; ?>
                            </label>
                            <label class="field">
                                <span class="field__label">Nueva contrasena <span class="field__hint">min 8 chars</span></span>
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
                            <h2><span class="num">03</span> Sesion &amp; cuenta</h2>
                            <p>Informacion de tu registro.</p>
                        </div>
                        <div class="meta">
                            <div class="meta__row"><span>ID de usuario</span><b>#<?= esc($user['id']) ?></b></div>
                            <?php if (! empty($user['created_at'])): ?>
                                <div class="meta__row"><span>Cuenta creada</span><b><?= esc($user['created_at']) ?></b></div>
                            <?php endif; ?>
                            <div class="meta__row"><span>Email</span><b><?= esc($email) ?></b></div>
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
