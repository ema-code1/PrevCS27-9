<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/space-modal.css') ?>?v=20260609-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Monitoreo de dispositivos</p>
                <h1>Registros IoT</h1>
                <p class="dashboard-head__text">Elegí un espacio para ver los gráficos de sus sensores en tiempo real.</p>
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
                <h3>Todavía no tenes espacios</h3>
                <p>Creá un espacio para tu planta o máquina y asociá una ESP32 para empezar a ver registros.</p>
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
                                            <button type="button" data-open-modal="space-modal-<?= $ind['id'] ?>">Configurar espacio</button>
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
                    </article>
                <?php endforeach; ?>
            </div>

            <?php foreach ($industries as $ind): ?>
                <?php if ($ind['role'] !== 'admin_principal') { continue; } ?>
                <?php $isUnlocked = (int) ($unlockedSpaceId ?? 0) === (int) $ind['id']; ?>
                <div class="space-modal" id="space-modal-<?= $ind['id'] ?>" data-space-modal<?= (int) ($openConfigId ?? 0) === (int) $ind['id'] ? '' : ' hidden' ?>>
                    <div class="space-modal__backdrop" data-modal-dismiss></div>
                    <div class="space-modal__dialog card" role="dialog" aria-modal="true" aria-labelledby="space-modal-title-<?= $ind['id'] ?>">
                        <header class="space-modal__head">
                            <div>
                                <h3 id="space-modal-title-<?= $ind['id'] ?>">Configurar espacio</h3>
                                <p class="text-muted mono"><?= esc($ind['space_name']) ?> · <?= esc($ind['industry_uid']) ?></p>
                            </div>
                            <button type="button" class="space-modal__close" data-modal-dismiss aria-label="Cerrar">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </header>

                        <div class="space-modal__body">
                            <!-- 1) Datos del espacio -->
                            <section class="space-modal__section">
                                <h4>Datos del espacio</h4>
                                <form action="<?= base_url('espacios/' . $ind['id'] . '/editar') ?>" method="post" class="auth-form space-modal__form">
                                    <?= csrf_field() ?>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Empresa / industria</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">E</span>
                                            <input type="text" name="company_name" value="<?= esc($ind['company_name']) ?>" required>
                                        </span>
                                    </label>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Nombre del espacio</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">#</span>
                                            <input type="text" name="space_name" value="<?= esc($ind['space_name']) ?>" required>
                                        </span>
                                    </label>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Rubro o sector</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">S</span>
                                            <input type="text" name="sector" value="<?= esc($ind['sector'] ?? '') ?>">
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
                            </section>

                            <!-- 2) Personas con acceso -->
                            <section class="space-modal__section">
                                <h4>Personas con acceso <span class="space-modal__count"><?= count($ind['members'] ?? []) ?></span></h4>
                                <div class="iot-space-members">
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

                            <!-- 3) Tokens de las ESP + sensores (se desbloquean con la clave) -->
                            <section class="space-modal__section">
                                <h4>Tokens y sensores</h4>
                                <?php if (! $isUnlocked): ?>
                                    <p class="text-muted">Ingresá la clave del espacio para ver los tokens de las ESP y poder borrar sensores.</p>
                                    <form action="<?= base_url('espacios/' . $ind['id'] . '/tokens') ?>" method="post" class="space-modal__unlock">
                                        <?= csrf_field() ?>
                                        <input type="password" name="space_password" placeholder="Clave del espacio" required>
                                        <button type="submit" class="btn btn--primary">Desbloquear</button>
                                    </form>
                                <?php else: ?>
                                    <p class="space-modal__unlocked">✓ Desbloqueado por 5 minutos. Tokens visibles y sensores borrables.</p>

                                    <div class="space-modal__group">
                                        <strong>Tokens de las ESP</strong>
                                        <?php if (($ind['devices'] ?? []) === []): ?>
                                            <p class="text-muted">Este espacio no tiene ESP vinculadas.</p>
                                        <?php else: ?>
                                            <?php foreach ($ind['devices'] as $device): ?>
                                                <?php $tk = $unlockedTokens[$device['id']] ?? null; ?>
                                                <div class="space-modal__token-row">
                                                    <span><?= esc($device['name']) ?></span>
                                                    <code class="esp32-token"><?= $tk ? esc($tk) : 'sin token' ?></code>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="space-modal__group">
                                        <strong>Sensores del espacio</strong>
                                        <?php if (($ind['sensors'] ?? []) === []): ?>
                                            <p class="text-muted">No hay sensores en este espacio.</p>
                                        <?php else: ?>
                                            <?php foreach ($ind['sensors'] as $sensor): ?>
                                                <div class="iot-space-member">
                                                    <div>
                                                        <strong><?= esc($sensor['label'] ?: $sensor['name']) ?></strong>
                                                        <small><?= esc($sensor['device_name']) ?> · <?= esc($sensor['name']) ?><?= ! empty($sensor['unit']) ? ' (' . esc($sensor['unit']) . ')' : '' ?></small>
                                                    </div>
                                                    <form action="<?= base_url('espacios/' . $ind['id'] . '/sensores/' . $sensor['id'] . '/eliminar') ?>" method="post" onsubmit="return confirm('¿Borrar este sensor? Se eliminan también sus lecturas.');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="iot-danger-link">Borrar</button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ─── Kebab (menú de opciones por tarjeta) ───────────────────────
    document.querySelectorAll('[data-kebab]').forEach(function (wrap) {
        const button = wrap.querySelector('[data-kebab-button]');
        const menu = wrap.querySelector('[data-kebab-menu]');

        button?.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = menu.hidden;
            document.querySelectorAll('[data-kebab-menu]').forEach((item) => item.hidden = true);
            document.querySelectorAll('[data-kebab-button]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
            menu.hidden = !willOpen;
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('[data-kebab-menu]').forEach((item) => item.hidden = true);
        document.querySelectorAll('[data-kebab-button]').forEach((item) => item.setAttribute('aria-expanded', 'false'));
    });

    // ─── Modal de configuración del espacio ─────────────────────────
    function syncBody() {
        document.body.classList.toggle('has-modal-open', !!document.querySelector('[data-space-modal]:not([hidden])'));
    }
    function openModal(modal) {
        if (!modal) return;
        modal.hidden = false;
        syncBody();
    }
    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        syncBody();
    }

    document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            openModal(document.getElementById(btn.getAttribute('data-open-modal')));
        });
    });

    document.querySelectorAll('[data-modal-dismiss]').forEach(function (el) {
        el.addEventListener('click', function () {
            closeModal(el.closest('[data-space-modal]'));
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-space-modal]:not([hidden])').forEach(closeModal);
        }
    });

    // Si se abrió vía ?config=<id> (tras desbloquear o borrar), marcar el body.
    syncBody();
});
</script>
<?= $this->endSection() ?>
