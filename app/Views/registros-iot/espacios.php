<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>?v=20260612-1">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>?v=20260612-2">
<link rel="stylesheet" href="<?= base_url('css/space-modal.css') ?>?v=20260612-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Monitoreo de dispositivos</p>
                <h1>Mis espacios</h1>
                <p class="dashboard-head__text">Desplegá un espacio para ver sus ESP32 y entrar a los gráficos de cada placa.</p>
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
            <a class="iot-acc__add iot-create-space" href="<?= base_url('crear-espacio') ?>">
                <span aria-hidden="true">+</span> Crear espacio
            </a>

            <div class="iot-acc-list">
                <?php foreach ($industries as $ind): ?>
                    <?php $isAdmin = $ind['role'] === 'admin_principal'; ?>
                    <article class="esp32-card card iot-space-card iot-acc" data-acc<?= (int) ($openConfigId ?? 0) === (int) $ind['id'] ? ' data-acc-start-open' : '' ?>>
                        <div class="iot-acc__head" data-acc-toggle role="button" tabindex="0" aria-label="Desplegar <?= esc($ind['space_name']) ?>">
                            <div class="iot-acc__info">
                                <div class="esp32-card__head">
                                    <h3><?= esc($ind['space_name']) ?></h3>
                                </div>
                                <p class="esp32-card__sub">
                                    <?= esc($ind['company_name']) ?><?= ! empty($ind['sector']) ? ' - ' . esc($ind['sector']) : '' ?>
                                </p>
                                <p class="text-muted mono"><?= (int) $ind['device_count'] ?> ESP32 - <?= esc($ind['industry_uid']) ?></p>
                            </div>
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
                                <span class="iot-acc__chevron" data-acc-chevron aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </div>
                        </div>

                        <div class="iot-acc__panel">
                            <div class="iot-acc__panel-inner">
                                <?php if (($ind['devices'] ?? []) === []): ?>
                                    <p class="text-muted iot-acc__empty">Este espacio todavía no tiene ESP32 vinculadas.</p>
                                <?php else: ?>
                                    <div class="iot-acc__esp-list">
                                        <?php foreach ($ind['devices'] as $device): ?>
                                            <a class="iot-acc__esp" href="<?= base_url('esp32/' . $device['id']) ?>">
                                                <span class="iot-acc__esp-status">
                                                    <span class="dot <?= ($device['status'] ?? '') === 'online' ? 'dot--live' : '' ?>"></span>
                                                </span>
                                                <span class="iot-acc__esp-id">
                                                    <strong><?= esc($device['name']) ?></strong>
                                                    <code><?= ! empty($device['mac_address']) ? esc($device['mac_address']) : 'Sin vincular' ?></code>
                                                </span>
                                                <span class="iot-acc__esp-meta mono"><?= esc($device['last_seen_at'] ?? '—') ?></span>
                                                <svg class="iot-acc__esp-arrow" viewBox="0 0 20 20" aria-hidden="true"><path d="m8 5 5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isAdmin): ?>
                                    <a class="iot-acc__add" href="<?= base_url('esp32/agregar?industry_id=' . $ind['id']) ?>">
                                        <span aria-hidden="true">+</span> Vincular ESP32
                                    </a>
                                <?php endif; ?>
                            </div>
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

                        <div class="space-modal__body" data-lenis-prevent>
                            <!-- 1) Datos del espacio -->
                            <section class="space-modal__section">
                                <h4>Datos del espacio</h4>
                                <form action="<?= base_url('espacios/' . $ind['id'] . '/editar') ?>" method="post" class="auth-form space-modal__form" data-warn-unsaved>
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
                                    <label class="auth-field">
                                        <span class="auth-field__label">Clave actual del espacio</span>
                                        <span class="auth-field__control">
                                            <span class="auth-field__icon">*</span>
                                            <input type="password" name="current_password" placeholder="Requerida para guardar cambios" required>
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
                                        <?php
                                        $memberName = trim(($member['nombre'] ?? '') . ' ' . ($member['apellido'] ?? '')) ?: $member['email'];
                                        $memberPermission = $member['permission'] ?? 'read';
                                        ?>
                                        <div class="iot-space-member">
                                            <div>
                                                <strong><?= esc($memberName) ?></strong>
                                                <small>
                                                    <?= esc($member['email']) ?> - <?= $member['role'] === 'admin_principal' ? 'Admin' : 'Supervisor' ?>
                                                    <?php if ($member['role'] !== 'admin_principal'): ?>
                                                        · <?= $memberPermission === 'edit' ? 'Puede editar' : 'Solo lectura' ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <?php if ($member['role'] !== 'admin_principal'): ?>
                                                <div class="iot-kebab" data-kebab>
                                                    <button type="button" class="iot-kebab__button" aria-label="Configurar acceso de <?= esc($memberName) ?>" aria-expanded="false" data-kebab-button>
                                                        <span></span><span></span><span></span>
                                                    </button>
                                                    <div class="iot-kebab__menu" hidden data-kebab-menu>
                                                        <form action="<?= base_url('espacios/' . $ind['id'] . '/miembros/' . $member['id'] . '/permiso') ?>" method="post">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="permission" value="read">
                                                            <button type="submit" class="iot-kebab__option<?= $memberPermission === 'read' ? ' is-active' : '' ?>">
                                                                <!-- Heroicons mini · eye -->
                                                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/></svg>
                                                                Leer
                                                            </button>
                                                        </form>
                                                        <form action="<?= base_url('espacios/' . $ind['id'] . '/miembros/' . $member['id'] . '/permiso') ?>" method="post">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="permission" value="edit">
                                                            <button type="submit" class="iot-kebab__option<?= $memberPermission === 'edit' ? ' is-active' : '' ?>">
                                                                <!-- Heroicons mini · pencil -->
                                                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/></svg>
                                                                Editar
                                                            </button>
                                                        </form>
                                                        <form action="<?= base_url('espacios/' . $ind['id'] . '/miembros/' . $member['id'] . '/eliminar') ?>" method="post"
                                                              data-confirm="¿Quitar a <?= esc($memberName) ?> del espacio? Va a perder el acceso a los registros.">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="iot-kebab__option iot-kebab__option--danger">
                                                                <!-- Heroicons mini · trash -->
                                                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/></svg>
                                                                Eliminar usuario
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
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
                                                    <form action="<?= base_url('espacios/' . $ind['id'] . '/sensores/' . $sensor['id'] . '/eliminar') ?>" method="post"
                                                          data-confirm="¿Borrar el sensor &quot;<?= esc($sensor['label'] ?: $sensor['name']) ?>&quot;? Se eliminan también todas sus lecturas.">
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

            <!-- Confirmación de acciones destructivas (reemplaza al confirm() nativo) -->
            <div class="space-modal iot-confirm" data-space-modal data-confirm-modal hidden>
                <div class="space-modal__backdrop" data-modal-dismiss></div>
                <div class="space-modal__dialog card iot-confirm__dialog" role="alertdialog" aria-modal="true" aria-labelledby="iot-confirm-title">
                    <header class="space-modal__head">
                        <div>
                            <h3 id="iot-confirm-title">¿Estás seguro?</h3>
                        </div>
                        <button type="button" class="space-modal__close" data-modal-dismiss aria-label="Cerrar">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </header>
                    <div class="space-modal__body" data-lenis-prevent>
                        <p class="iot-confirm__text" data-confirm-text>Esta acción no se puede deshacer.</p>
                        <div class="iot-confirm__actions">
                            <button type="button" class="btn btn--secondary" data-modal-dismiss>Cancelar</button>
                            <button type="button" class="btn btn--danger" data-confirm-accept>Sí, continuar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260612-2" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Si la página volvió con ?config=<id> (desbloqueo / borrado), dejar
    // desplegado el acordeón de ese espacio para mantener el contexto.
    document.querySelectorAll('[data-acc-start-open]').forEach(function (acc) {
        acc.classList.add('is-open');
        var chevron = acc.querySelector('[data-acc-chevron]');
        if (chevron) chevron.setAttribute('aria-expanded', 'true');
    });
});
</script>
<?= $this->endSection() ?>
