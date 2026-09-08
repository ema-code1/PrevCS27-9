<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>?v=20260908-2">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>?v=20260908-2">
<link rel="stylesheet" href="<?= base_url('css/space-modal.css') ?>?v=20260908-2">
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
                                <?php /* Los data-space-* son el ancla que usa el modal para
                                        reescribir estos textos cuando se guardan los datos del
                                        espacio, sin recargar la página. */ ?>
                                <div class="esp32-card__head">
                                    <h3 data-space-company="<?= $ind['id'] ?>"><?= esc($ind['company_name']) ?></h3>
                                </div>
                                <p class="esp32-card__sub" data-space-subtitle="<?= $ind['id'] ?>">
                                    <?= esc($ind['space_name']) ?><?= ! empty($ind['sector']) ? ' - ' . esc($ind['sector']) : '' ?>
                                </p>
                                <p class="text-muted mono"><?= (int) $ind['device_count'] ?> ESP32 - <?= esc($ind['industry_uid']) ?></p>
                            </div>
                            <div class="iot-space-card__top-actions">
                                <a class="iot-stock-btn" href="<?= base_url('espacios/' . $ind['id'] . '/stock') ?>"
                                   aria-label="Ver el stock de <?= esc($ind['space_name']) ?>">
                                    <!-- Heroicons outline · cube -->
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 7.5 12 12m0 0L3 7.5M12 12v9.75M21 7.5v9l-9 5.25L3 16.5v-9L12 2.25 21 7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span>Stock</span>
                                </a>
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
                                                <span class="iot-acc__esp-meta mono"><?= esc($device['last_seen_at'] ?? '�') ?></span>
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
                <?php
                $isUnlocked    = (int) ($unlockedSpaceId ?? 0) === (int) $ind['id'];
                $sessionUserId = (int) session('user_id');
                ?>
                <div class="space-modal" id="space-modal-<?= $ind['id'] ?>" data-space-modal<?= (int) ($openConfigId ?? 0) === (int) $ind['id'] ? '' : ' hidden' ?>>
                    <div class="space-modal__backdrop" data-modal-dismiss></div>
                    <div class="space-modal__dialog card" role="dialog" aria-modal="true" aria-labelledby="space-modal-title-<?= $ind['id'] ?>">
                        <header class="space-modal__head">
                            <div>
                                <h3 id="space-modal-title-<?= $ind['id'] ?>">Configurar espacio</h3>
                            </div>
                            <button type="button" class="space-modal__close" data-modal-dismiss aria-label="Cerrar">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </header>

                        <div class="space-modal__body" data-lenis-prevent>
                            <!-- 1) Datos del espacio -->
                            <section class="space-modal__section">
                                <h4>Datos del espacio</h4>
                                <form action="<?= base_url('espacios/' . $ind['id'] . '/editar') ?>" method="post" class="auth-form space-modal__form" data-warn-unsaved data-ajax>
                                    <?= csrf_field() ?>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Empresa / industria</span>
                                        <input type="text" name="company_name" value="<?= esc($ind['company_name']) ?>" required>
                                    </label>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Nombre del espacio</span>
                                        <input type="text" name="space_name" value="<?= esc($ind['space_name']) ?>" required>
                                    </label>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Rubro o sector</span>
                                        <input type="text" name="sector" value="<?= esc($ind['sector'] ?? '') ?>">
                                    </label>

                                    <?php /* La clave nueva se pide solo si la van a cambiar, igual que en
                                            "Administrar cuenta": el campo aparece recién al tocar el botón. */ ?>
                                    <div class="space-disclosure">
                                        <button type="button" class="space-ghost-btn"
                                                data-disclosure="space-pass-<?= $ind['id'] ?>"
                                                aria-expanded="false" aria-controls="space-pass-<?= $ind['id'] ?>">
                                            <!-- Heroicons mini · key -->
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 7a5 5 0 1 1 3.61 4.804l-1.903 1.903A1 1 0 0 1 9 14H8v1a1 1 0 0 1-1 1H6v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-2a1 1 0 0 1 .293-.707L8.196 8.39A5.002 5.002 0 0 1 8 7Zm5-3a.75.75 0 0 0 0 1.5A1.5 1.5 0 0 1 14.5 7 .75.75 0 0 0 16 7a3 3 0 0 0-3-3Z" clip-rule="evenodd"/></svg>
                                            Cambiar la clave del espacio
                                        </button>
                                        <div class="space-disclosure__panel" id="space-pass-<?= $ind['id'] ?>" data-disclosure-panel hidden>
                                            <label class="auth-field">
                                                <span class="auth-field__label">Clave nueva</span>
                                                <input type="password" name="space_password" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                                            </label>
                                        </div>
                                    </div>

                                    <?php /* Gate de toda la sección, no solo del cambio de clave: el
                                            controlador exige la clave actual para cualquier guardado. */ ?>
                                    <label class="auth-field">
                                        <span class="auth-field__label">Clave actual del espacio</span>
                                        <input type="password" name="current_password" placeholder="Requerida para guardar" autocomplete="current-password" required>
                                    </label>
                                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                                </form>
                            </section>

                            <?php /* Las dos secciones que siguen se vuelven a dibujar solas: cada
                                    acción de adentro viaja por fetch y la respuesta trae la parcial
                                    ya actualizada, que reemplaza a esta región sin recargar nada. */ ?>
                            <!-- 2) Personas con acceso -->
                            <section class="space-modal__section" data-region="members-<?= $ind['id'] ?>">
                                <?= view('registros-iot/_space_members', [
                                    'ind'           => $ind,
                                    'sessionUserId' => $sessionUserId,
                                ]) ?>
                            </section>

                            <!-- 3) Tokens de las ESP + sensores (se desbloquean con la clave) -->
                            <section class="space-modal__section" data-region="tokens-<?= $ind['id'] ?>">
                                <?= view('registros-iot/_space_tokens', [
                                    'ind'            => $ind,
                                    'isUnlocked'     => $isUnlocked,
                                    'unlockedTokens' => $unlockedTokens ?? [],
                                ]) ?>
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
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260908-2" defer></script>
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
