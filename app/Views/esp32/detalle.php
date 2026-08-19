<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>?v=20260807-1">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>?v=20260612-2">
<link rel="stylesheet" href="<?= base_url('css/date-range.css') ?>?v=20260609-1">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=wifi_off" />
<link rel="stylesheet" href="<?= base_url('css/space-modal.css') ?>?v=20260806-1">
<link rel="stylesheet" href="<?= base_url('css/kanban.css') ?>?v=20260819-3">
<style>
.esp32-qr{display:flex;flex-direction:column;gap:16px;align-items:center;margin:10px 0}
.esp32-qr__code{background:#fff;padding:10px;border-radius:var(--radius-md);border:1px solid var(--hair);line-height:0}
.esp32-qr__code img,.esp32-qr__code canvas{display:block}
.material-symbols-rounded{font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard page--iot">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area iot-shell">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">
                    <a href="<?= base_url('registros-iot') ?>" class="esp32-back">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12 5-5 5 5 5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Mis espacios
                    </a>
                </p>
                <h1><?= esc($device['name']) ?></h1>
                <p class="dashboard-head__text">
                    <code class="esp32-card__mac"><?= ! empty($device['mac_address']) ? esc($device['mac_address']) : 'Sin vincular' ?></code>
                    · Sector: <?= esc($device['sector'] ?? '~') ?>
                </p>
            </div>
            <div class="dashboard-head__meta dashboard-head__meta--esp">
                <button type="button" class="iot-gear-btn" data-open-modal="esp32-config-modal" aria-label="Configuración de la placa" title="Configuración de la placa">
                    <!-- Heroicons v2 solid · cog-6-tooth (MIT) -->
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 0 0-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 0 0-2.282.819l-.922 1.597a1.875 1.875 0 0 0 .432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 0 0 0 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 0 0-.432 2.385l.922 1.597a1.875 1.875 0 0 0 2.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 0 0 2.28-.819l.923-1.597a1.875 1.875 0 0 0-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 0 0 0-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 0 0-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 0 0-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 0 0-1.85-1.567h-1.843ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                    </svg>
                </button>
                <div class="dashboard-head__meta-row">
                    <span class="badge <?= ($device['status'] ?? '') === 'online' ? 'badge--success' : 'badge--info' ?>">
                        <span class="dot <?= ($device['status'] ?? '') === 'online' ? 'dot--live' : '' ?>"></span>
                        <?= esc($device['status'] ?? 'offline') ?>
                    </span>
                    <small class="text-muted mono">Última: <span data-device-last-seen><?= esc($device['last_seen_at'] ?? '�') ?></span></small>
                </div>
            </div>
        </header>

        <?php if (session('success')): ?>
            <div class="auth-alert auth-alert--success"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (! empty($sensorCount)): ?>
            <div class="auth-alert auth-alert--success"><?= (int) $sensorCount ?> sensores creados automaticamente.</div>
        <?php endif; ?>

        <?php if (empty($device['mac_address'])): ?>
            <!-- La placa todavía no se vinculó: en lugar de gráficos vacíos,
                 mostramos el aviso con la guía para vincularla. -->
            <section class="panel card esp32-unlinked">
                <h3>¡No has conectado tu ESP32!</h3>
                <img src="<?= base_url('images/no_esp_conected.svg') ?>" alt="ESP32 sin conectar">
                <p>Conecta tu placa para poder empezar a recibir los datos</p>
                <button type="button" class="btn btn--primary esp32-unlinked__cta" data-open-modal="esp32-config-modal">
                    <span class="material-symbols-rounded" aria-hidden="true">wifi_off</span>
                    Vincular mi ESP
                </button>
            </section>
        <?php elseif ($sensors === []): ?>
            <section class="panel card esp32-empty">
                <h3>Sin sensores</h3>
                <p>Esta ESP32 todavía no tiene sensores asociados.</p>
            </section>
        <?php else: ?>
            <div class="section-switch" role="tablist" aria-label="Sensores de la ESP32">
                <?php foreach ($sensors as $idx => $s): ?>
                    <label class="section-switch__radio" role="tab" aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>">
                        <input type="radio" name="sensor-switch" value="sensor-<?= $s['id'] ?>"
                            data-section-target="sensor-<?= $s['id'] ?>" <?= $idx === 0 ? 'checked' : '' ?>>
                        <span class="section-switch__name"><?= esc($s['label'] ?: $s['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php foreach ($sensors as $idx => $s): ?>
                <section class="section-pane<?= $idx === 0 ? ' is-active' : '' ?>" data-section-pane="sensor-<?= $s['id'] ?>">
                    <div class="iot-grid"
                        data-sensor-id="<?= $s['id'] ?>"
                        data-sensor-unit="<?= esc($s['unit'] ?? '') ?>"
                        data-readings-url="<?= base_url('api/esp32/' . $device['id'] . '/lecturas') ?>">
                        <section class="panel card iot-chart-card">
                            <div class="panel__header">
                                <h3><?= esc($s['name']) ?><?= !empty($s['unit']) ? ' · ' . esc($s['unit']) : '' ?></h3>
                                <div class="iot-chart-toolbar">
                                    <div class="drp" data-date-range></div>
                                    <span class="badge badge--info" data-chart-type><?= esc($s['default_chart']) ?></span>
                                </div>
                            </div>
                            <div class="iot-chart is-skeleton">
                                <svg class="iot-line-chart" viewBox="0 0 640 360" role="img" aria-label="Gráfico de <?= esc($s['name']) ?>">
                                    <g class="iot-line-chart__grid">
                                        <line x1="78" y1="48" x2="600" y2="48"></line>
                                        <line x1="78" y1="112" x2="600" y2="112"></line>
                                        <line x1="78" y1="176" x2="600" y2="176"></line>
                                        <line x1="78" y1="240" x2="600" y2="240"></line>
                                        <line x1="78" y1="304" x2="600" y2="304"></line>
                                    </g>
                                    <g class="iot-line-chart__grid iot-line-chart__grid--v" data-grid-v></g>
                                    <g class="iot-line-chart__axis iot-line-chart__axis--y" data-axis-y></g>
                                    <g class="iot-line-chart__axis iot-line-chart__axis--x" data-axis-x></g>
                                    <text class="iot-line-chart__axis-title" x="18" y="176" transform="rotate(-90 18 176)" text-anchor="middle">Valores<?= !empty($s['unit']) ? ' · ' . esc($s['unit']) : '' ?></text>
                                    <text class="iot-line-chart__axis-title" x="339" y="350" text-anchor="middle">Tiempo</text>
                                    <polyline class="iot-line-chart__stroke" data-sensor-line points=""></polyline>
                                    <g data-sensor-points></g>
                                </svg>
                                <div class="iot-chart__skeleton" data-sensor-skeleton aria-hidden="true">
                                    <span class="iot-skel" style="height:42%"></span>
                                    <span class="iot-skel" style="height:64%"></span>
                                    <span class="iot-skel" style="height:50%"></span>
                                    <span class="iot-skel" style="height:74%"></span>
                                    <span class="iot-skel" style="height:58%"></span>
                                    <span class="iot-skel" style="height:82%"></span>
                                    <span class="iot-skel" style="height:46%"></span>
                                    <span class="iot-skel" style="height:66%"></span>
                                </div>
                                <div class="iot-chart__error" data-sensor-error hidden>
                                    <p>No se pudieron cargar las lecturas. Revisá tu conexión.</p>
                                    <button type="button" class="btn btn--secondary btn--sm" data-sensor-retry>Reintentar</button>
                                </div>
                                <p class="esp32-chart-empty" data-sensor-empty hidden>Sin lecturas todavía para este sensor.</p>
                            </div>
                        </section>

                        <div class="iot-side-stack">
                            <aside class="panel card">
                                <div class="panel__header"><h3>Resumen</h3></div>
                                <dl class="esp32-sensor-meta">
                                    <div><dt>Descripción</dt><dd>Mediciones de <?= esc(mb_strtolower($s['name'])) ?> reportadas por la ESP32.</dd></div>
                                    <div><dt>Última lectura</dt>
                                        <dd class="mono" data-sensor-latest>
                                            <?= $s['latest_value'] !== null ? esc($s['latest_value']) . ' ' . esc($s['unit'] ?? '') : '�' ?>
                                        </dd>
                                    </div>
                                    <div><dt>Registrada</dt><dd class="mono" data-sensor-latest-at><?= esc($s['latest_at'] ?? '�') ?></dd></div>
                                </dl>
                            </aside>

                            <aside class="panel card">
                                <div class="panel__header"><h3>Observaciones</h3></div>
                                <p class="esp32-observations" data-sensor-observation>Esperando datos del sensor��</p>
                            </aside>

                            <aside class="panel card">
                                <div class="panel__header"><h3>Historial</h3></div>
                                <div class="esp32-history" data-sensor-history>
                                    <div class="iot-skel-rows" aria-hidden="true">
                                        <span class="iot-skel"></span>
                                        <span class="iot-skel"></span>
                                        <span class="iot-skel"></span>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Tablero de tareas de la placa. Va FUERA del foreach de sensores:
             es global a la ESP32 y no cambia al moverse entre las pestañas. -->
        <?= $this->include('esp32/_kanban') ?>

        <!-- Configuración de la placa: ventana emergente (botón de engranaje).
             Si acabamos de crear la placa o revelar el token, llega abierta
             para que el token/QR quede a la vista sin un click extra. -->
        <div class="space-modal" id="esp32-config-modal" data-space-modal<?= ! empty($autoOpenConfig) ? '' : ' hidden' ?>>
            <div class="space-modal__backdrop" data-modal-dismiss></div>
            <div class="space-modal__dialog card" role="dialog" aria-modal="true" aria-labelledby="esp32-config-title">
                <header class="space-modal__head">
                    <div>
                        <h3 id="esp32-config-title">Configuración de la placa</h3>
                        <p class="text-muted mono"><?= esc($device['name']) ?></p>
                    </div>
                    <button type="button" class="space-modal__close" data-modal-dismiss aria-label="Cerrar">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </header>

                <div class="space-modal__body" data-lenis-prevent>
                    <!-- 1) Datos de configuración -->
                    <section class="space-modal__section">
                        <h4>Datos de configuración</h4>
                        <dl class="esp32-sensor-meta">
                            <div>
                                <dt>MAC
                                    <button type="button" class="pv-tip" aria-label="¿Por qué importa la MAC?"
                                        data-tip="La MAC identifica de forma única a tu placa en la red. Cuando la ESP32 envía su primer dato, el sistema la reconoce por su MAC y queda vinculada sola a este panel.">?</button>
                                </dt>
                                <dd class="mono"><?= ! empty($device['mac_address']) ? esc($device['mac_address']) : '<em>No hubo comunicacion con la placa</em>' ?></dd>
                            </div>
                            <div><dt>Estado</dt><dd><?= esc($device['status'] ?? 'offline') ?></dd></div>
                            <div><dt>Última lectura</dt><dd class="mono"><?= esc($device['last_seen_at'] ?? '-') ?></dd></div>
                        </dl>
                    </section>

                    <!-- 2) Token / vinculación con el firmware -->
                    <section class="space-modal__section">
                        <h4>Token del dispositivo
                            <button type="button" class="pv-tip" aria-label="¿Para qué sirve el token?"
                                data-tip="Configuralo en el firmware. Se envía en cada lectura (header X-Device-Token).">?</button>
                        </h4>
                        <?php if (! empty($deviceToken)): ?>
                            <code class="esp32-token"><?= esc($deviceToken) ?></code>

                            <?php if (! empty($setupUrl)): ?>
                                <div class="esp32-setup-box">
                                    <strong>Configurá la placa: conectate a PREVCS-CONFIG</strong>
                                    <p>Primero conectate al WiFi <code>PREVCS-CONFIG</code> que crea la placa y escaneá el QR (o tocá el botón).</p>
                                    <div class="esp32-qr" data-qr-url="<?= esc($setupUrl) ?>">
                                        <div class="esp32-qr__code" data-qr-target></div>
                                    </div>
                                    <a class="btn btn--primary" href="<?= esc($setupUrl) ?>">Completar configuracion</a>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($isAdmin): ?>
                            <form action="<?= base_url('esp32/' . $device['id'] . '/token') ?>" method="post" class="esp32-token-form">
                                <?= csrf_field() ?>
                                <label class="auth-field">
                                    <span class="auth-field__label">Ver token</span>
                                    <span class="auth-field__control">
                                        <span class="auth-field__icon">*</span>
                                        <input type="password" name="space_password" placeholder="Clave del espacio" required>
                                    </span>
                                </label>
                                <button type="submit" class="btn btn--primary">Mostrar token</button>
                                <?php if (! empty($tokenError)): ?>
                                    <span class="auth-field__error"><?= esc($tokenError) ?></span>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <p class="text-muted">Solo el admin del espacio puede ver el token.</p>
                        <?php endif; ?>
                    </section>

                    <!-- 3) Agregar sensor extra -->
                    <?php if ($isAdmin): ?>
                        <section class="space-modal__section">
                            <h4>Agregar sensor extra</h4>
                            <?php if ($availableTypes === []): ?>
                                <p class="text-muted">Todos los tipos de sensor ya están asociados a esta placa.</p>
                            <?php else: ?>
                                <form action="<?= base_url('esp32/' . $device['id'] . '/sensores') ?>" method="post" class="esp32-sensor-form esp32-sensor-form--modal">
                                    <?= csrf_field() ?>
                                    <select name="sensor_type_id" class="esp32-select" required>
                                        <option value="" disabled selected>Tipo de sensor</option>
                                        <?php foreach ($availableTypes as $t): ?>
                                            <option value="<?= esc($t['id']) ?>"><?= esc($t['name']) ?><?= !empty($t['unit']) ? ' (' . esc($t['unit']) . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="label" class="esp32-input" placeholder="Etiqueta (opcional)" maxlength="150">
                                    <button type="submit" class="btn btn--primary">Agregar</button>
                                </form>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <!-- 4) Eliminar la placa: solo si ya está creada y configurada (tiene MAC)
                         y el usuario puede editar el espacio (admin o supervisor con permiso). -->
                    <?php if (! empty($canEdit) && ! empty($device['mac_address'])): ?>
                        <section class="space-modal__section esp32-delete">
                            <div class="esp32-delete__intro">
                                <h4>Zona de peligro</h4>
                                <p class="text-muted">Eliminar la placa borra también sus sensores y todas sus lecturas. Esta acción no se puede deshacer.</p>
                            </div>
                            <button type="button" class="btn btn--danger" data-delete-toggle aria-expanded="<?= ! empty($deleteError) ? 'true' : 'false' ?>">Eliminar ESP32</button>
                            <form action="<?= base_url('esp32/' . $device['id'] . '/eliminar') ?>" method="post" class="esp32-delete__form" data-delete-form<?= ! empty($deleteError) ? '' : ' hidden' ?>>
                                <?= csrf_field() ?>
                                <label class="auth-field">
                                    <span class="auth-field__label">Escribí el nombre exacto de la placa: <strong><?= esc($device['name']) ?></strong></span>
                                    <span class="auth-field__control">
                                        <span class="auth-field__icon">E</span>
                                        <input type="text" name="confirm_name" placeholder="<?= esc($device['name']) ?>" autocomplete="off" required data-delete-name data-expected-name="<?= esc($device['name']) ?>">
                                    </span>
                                </label>
                                <label class="auth-field">
                                    <span class="auth-field__label">Clave del espacio</span>
                                    <span class="auth-field__control">
                                        <span class="auth-field__icon">*</span>
                                        <input type="password" name="space_password" placeholder="Clave del espacio" required>
                                    </span>
                                </label>
                                <?php if (! empty($deleteError)): ?>
                                    <span class="auth-field__error"><?= esc($deleteError) ?></span>
                                <?php endif; ?>
                                <button type="submit" class="btn btn--danger" disabled data-delete-submit>Eliminar definitivamente</button>
                            </form>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/date-range-picker.js') ?>?v=20260609-1" defer></script>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260806-1" defer></script>
<script src="<?= base_url('js/iot-sensor-charts.js') ?>?v=20260612-1" defer></script>
<script src="<?= base_url('js/kanban.js') ?>?v=20260819-3" defer></script>
<script src="<?= base_url('js/qrcode.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var box = document.querySelector('[data-qr-url]');
    var target = box && box.querySelector('[data-qr-target]');
    if (box && target && typeof QRCode !== 'undefined') {
        new QRCode(target, {
            text: box.getAttribute('data-qr-url'),
            width: 252,
            height: 252,
            colorDark: '#2A2C32',
            colorLight: '#FFFFFF',
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    /* Eliminar ESP32: despliega el form y habilita el submit recién
       cuando el nombre tipeado coincide exactamente con el de la placa. */
    var deleteToggle = document.querySelector('[data-delete-toggle]');
    var deleteForm = document.querySelector('[data-delete-form]');
    if (deleteToggle && deleteForm) {
        deleteToggle.addEventListener('click', function () {
            var willOpen = deleteForm.hidden;
            deleteForm.hidden = !willOpen;
            deleteToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) deleteForm.querySelector('[data-delete-name]').focus();
        });

        var nameInput = deleteForm.querySelector('[data-delete-name]');
        var submitBtn = deleteForm.querySelector('[data-delete-submit]');
        nameInput.addEventListener('input', function () {
            submitBtn.disabled = nameInput.value.trim() !== nameInput.dataset.expectedName;
        });
    }
});
</script>
<?= $this->endSection() ?>
