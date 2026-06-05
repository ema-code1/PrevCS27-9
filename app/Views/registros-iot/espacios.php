<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Monitoreo de dispositivos</p>
                <h1>Registros IoT</h1>
                <p class="dashboard-head__text">Elegi un espacio para ver los graficos de sus sensores en tiempo real.</p>
            </div>
            <div class="dashboard-head__meta">
                <span class="badge badge--info"><?= count($industries) ?> espacio<?= count($industries) === 1 ? '' : 's' ?></span>
            </div>
        </header>

        <?php if (session('error')): ?>
            <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="auth-alert auth-alert--success"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (($errors = session('errors') ?? []) !== []): ?>
            <div class="auth-alert auth-alert--error">
                <?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($industries === []): ?>
            <section class="panel card esp32-empty">
                <h3>Todavia no tenes espacios</h3>
                <p>Crea un espacio para tu planta o maquina y asocia una ESP32 para empezar a ver registros.</p>
                <a href="<?= base_url('crear-espacio') ?>" class="btn btn--primary">Crear espacio</a>
            </section>
        <?php else: ?>
            <div class="esp32-grid">
                <?php foreach ($industries as $ind): ?>
                    <?php $isAdmin = $ind['role'] === 'admin_principal'; ?>
                    <article class="esp32-card card iot-space-card" data-space-card>
                        <div class="esp32-card__head">
                            <h3><?= esc($ind['space_name']) ?></h3>
                            <div class="iot-space-card__top-actions">
                                <span class="badge <?= $isAdmin ? 'badge--success' : 'badge--info' ?>">
                                    <?= $isAdmin ? 'Admin' : 'Supervisor' ?>
                                </span>
                                <?php if ($isAdmin): ?>
                                    <div class="iot-kebab" data-kebab>
                                        <button type="button" class="iot-kebab__button" aria-label="Opciones de <?= esc($ind['space_name']) ?>" aria-expanded="false" data-kebab-button>
                                            <span></span><span></span><span></span>
                                        </button>
                                        <div class="iot-kebab__menu" hidden data-kebab-menu>
                                            <button type="button" data-edit-space>Editar espacio</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="esp32-card__sub">
                            <?= esc($ind['company_name']) ?><?= ! empty($ind['sector']) ? ' - ' . esc($ind['sector']) : '' ?>
                        </p>
                        <p class="text-muted mono"><?= (int) $ind['device_count'] ?> ESP32 - <?= esc($ind['industry_uid']) ?></p>

                        <div class="esp32-card__actions">
                            <a href="<?= base_url('registros-iot/' . $ind['id']) ?>" class="btn btn--primary">Registros IoT</a>
                            <?php if ($isAdmin): ?>
                                <a href="<?= base_url('esp32/agregar?industry_id=' . $ind['id']) ?>" class="btn btn--secondary">Vincular ESP32</a>
                            <?php endif; ?>
                        </div>

                        <?php if ($isAdmin): ?>
                            <section class="iot-space-editor<?= (int) session('editing_industry_id') === (int) $ind['id'] ? ' is-open' : '' ?>" data-space-editor>
                                <form action="<?= base_url('espacios/' . $ind['id'] . '/editar') ?>" method="post" class="auth-form iot-space-editor__form">
                                    <?= csrf_field() ?>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Empresa / industria</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">E</span>
                                            <input type="text" name="company_name" value="<?= esc(old('company_name', $ind['company_name'])) ?>" required>
                                        </span>
                                    </label>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Nombre del espacio</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">#</span>
                                            <input type="text" name="space_name" value="<?= esc(old('space_name', $ind['space_name'])) ?>" required>
                                        </span>
                                    </label>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Rubro o sector</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">S</span>
                                            <input type="text" name="sector" value="<?= esc(old('sector', $ind['sector'] ?? '')) ?>">
                                        </span>
                                    </label>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Nueva clave del espacio</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">*</span>
                                            <input type="password" name="space_password" placeholder="Dejar igual">
                                        </span>
                                    </label>
                                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                                </form>

                                <div class="iot-space-members">
                                    <div class="iot-space-members__head">
                                        <strong>Personas con acceso</strong>
                                        <span><?= count($ind['members'] ?? []) ?></span>
                                    </div>
                                    <?php foreach (($ind['members'] ?? []) as $member): ?>
                                        <div class="iot-space-member">
                                            <div>
                                                <strong><?= esc(trim(($member['nombre'] ?? '') . ' ' . ($member['apellido'] ?? '')) ?: $member['email']) ?></strong>
                                                <small><?= esc($member['email']) ?> - <?= $member['role'] === 'admin_principal' ? 'Admin' : 'Supervisor' ?></small>
                                            </div>
                                            <?php if ($member['role'] !== 'admin_principal'): ?>
                                                <form action="<?= base_url('espacios/' . $ind['id'] . '/miembros/' . $member['id'] . '/eliminar') ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="iot-danger-link">Quitar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-kebab]').forEach(function (wrap) {
        const button = wrap.querySelector('[data-kebab-button]');
        const menu = wrap.querySelector('[data-kebab-menu]');
        const card = wrap.closest('[data-space-card]');
        const editor = card?.querySelector('[data-space-editor]');

        button?.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = menu.hidden;
            document.querySelectorAll('[data-kebab-menu]').forEach((item) => item.hidden = true);
            document.querySelectorAll('[data-kebab-button]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
            menu.hidden = !willOpen;
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        wrap.querySelector('[data-edit-space]')?.addEventListener('click', function () {
            menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            editor?.classList.toggle('is-open');
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('[data-kebab-menu]').forEach((item) => item.hidden = true);
        document.querySelectorAll('[data-kebab-button]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
    });
});
</script>
<?= $this->endSection() ?>
