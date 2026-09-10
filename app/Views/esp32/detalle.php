<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>?v=20260908-2">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>?v=20260908-2">
<link rel="stylesheet" href="<?= base_url('css/date-range.css') ?>?v=20260609-1">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=wifi_off" />
<link rel="stylesheet" href="<?= base_url('css/space-modal.css') ?>?v=20260908-1">
<link rel="stylesheet" href="<?= base_url('css/kanban.css') ?>?v=20260820-1">
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
                        <p class="text-muted"><?= esc($device['name']) ?></p>
                    </div>
                    <button type="button" class="space-modal__close" data-modal-dismiss aria-label="Cerrar">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </header>

                <div class="space-modal__body" data-lenis-prevent>
                    <!-- 1) La placa de un vistazo.
                         Antes era una lista de "Datos de configuración" con un
                         tooltip explicando la MAC. Adentro de la ventana que
                         justamente sirve para configurar, ese texto sobraba:
                         quedan los tres datos que se miran de verdad. -->
                    <section class="space-modal__section">
                        <dl class="esp32-facts">
                            <div>
                                <dt>Estado</dt>
                                <dd>
                                    <span class="dot <?= ($device['status'] ?? '') === 'online' ? 'dot--live' : '' ?>"></span>
                                    <?= esc($device['status'] ?? 'offline') ?>
                                </dd>
                            </div>
                            <div>
                                <dt>MAC</dt>
                                <dd><?= ! empty($device['mac_address']) ? esc($device['mac_address']) : 'Sin vincular' ?></dd>
                            </div>
                            <div>
                                <dt>Última lectura</dt>
                                <dd><?= esc($device['last_seen_at'] ?? 'Nunca') ?></dd>
                            </div>
                        </dl>
                    </section>

                    <!-- 2) Vinculación con el firmware.
                         El token viaja adentro del QR y del enlace, pero no se
                         imprime en pantalla ni se puede pedir con la clave del
                         espacio: es un secreto del firmware. -->
                    <?php if (! empty($setupUrl)): ?>
                        <section class="space-modal__section">
                            <h4>Vincular la placa</h4>
                            <div class="esp32-setup-box">
                                <strong>Conectate al WiFi PREVCS-CONFIG</strong>
                                <p>Es la red que crea la placa. Con esa red conectada, escaneá el QR o tocá el botón para completar la configuración.</p>
                                <div class="esp32-qr" data-qr-url="<?= esc($setupUrl) ?>">
                                    <div class="esp32-qr__code" data-qr-target></div>
                                </div>
                                <a class="btn btn--primary" href="<?= esc($setupUrl) ?>">Completar configuración</a>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- 3) Sensores configurados en la placa.
                         Cada fila tiene su menú (cambiar el tipo / borrar) y el
                         alta vive detrás del "+" de la esquina: la lista es lo
                         que se mira, agregar es la excepción.

                         Va como región aparte: las tres acciones viajan por
                         fetch y el servidor contesta con esta misma parcial ya
                         dibujada de nuevo, así guardar no recarga la página ni
                         cierra la ventana. -->
                    <section class="space-modal__section" data-region="esp32-sensors-<?= (int) $device['id'] ?>">
                        <?= $this->include('esp32/_config_sensors') ?>
                    </section>

                    <!-- 4) Zona de peligro: contraída por defecto y desplegable.
                         Es la única acción sin vuelta atrás de la ventana, así
                         que no tiene por qué ocupar lugar hasta que la busquen.
                         Solo si la placa ya está vinculada (tiene MAC) y el
                         usuario puede editar el espacio. -->
                    <?php if (! empty($canEdit) && ! empty($device['mac_address'])): ?>
                        <section class="space-modal__section esp32-danger">
                            <button type="button" class="esp32-danger__toggle" data-danger-toggle
                                    aria-expanded="<?= ! empty($deleteError) ? 'true' : 'false' ?>" aria-controls="esp32-danger-panel">
                                <span>Zona de peligro</span>
                                <svg class="esp32-danger__chevron" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <div class="esp-collapse<?= ! empty($deleteError) ? ' is-open' : '' ?>" id="esp32-danger-panel" data-danger-panel>
                                <div class="esp-collapse__inner">
                                    <form action="<?= base_url('esp32/' . $device['id'] . '/eliminar') ?>" method="post" class="esp32-delete__form">
                                        <?= csrf_field() ?>
                                        <p class="esp32-delete__note">Eliminar la placa borra también sus sensores y todas sus lecturas. No se puede deshacer.</p>
                                        <label class="auth-field">
                                            <span class="auth-field__label">Escribí el nombre exacto de la placa: <strong><?= esc($device['name']) ?></strong></span>
                                            <input type="text" name="confirm_name" placeholder="<?= esc($device['name']) ?>" autocomplete="off" required data-delete-name data-expected-name="<?= esc($device['name']) ?>">
                                        </label>
                                        <label class="auth-field">
                                            <span class="auth-field__label">Clave del espacio</span>
                                            <input type="password" name="space_password" placeholder="Clave del espacio" required>
                                        </label>
                                        <?php if (! empty($deleteError)): ?>
                                            <span class="auth-field__error"><?= esc($deleteError) ?></span>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn--danger" disabled data-delete-submit>Eliminar definitivamente</button>
                                    </form>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Confirmación de acciones destructivas (reemplaza al confirm() nativo).
             La usa el "Borrar" del menú de cada sensor. -->
        <div class="space-modal iot-confirm" data-space-modal data-confirm-modal hidden>
            <div class="space-modal__backdrop" data-modal-dismiss></div>
            <div class="space-modal__dialog card iot-confirm__dialog" role="alertdialog" aria-modal="true" aria-labelledby="esp32-confirm-title">
                <header class="space-modal__head">
                    <div>
                        <h3 id="esp32-confirm-title">¿Estás seguro?</h3>
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
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/date-range-picker.js') ?>?v=20260609-1" defer></script>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260908-2" defer></script>
<script src="<?= base_url('js/iot-sensor-charts.js') ?>?v=20260612-1" defer></script>
<script src="<?= base_url('js/kanban.js') ?>?v=20260831-2" defer></script>
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

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var COLLAPSE_MS = 340;

    /* ── Bloques que crecen y se achican con la altura animada ─────────
       .esp-collapse va de 0fr a 1fr: el alto se interpola y el card
       acompaña el movimiento en vez de saltar de un alto al otro.

       Mientras se mueve, el interior queda recortado; cuando termina se
       libera el overflow para que la lista desplegable de un select pueda
       salirse del bloque sin que la corten. */
    function setCollapsed(panel, open) {
        if (!panel) return;

        var inner = panel.firstElementChild;
        if (inner) inner.style.overflow = 'hidden';
        panel.classList.toggle('is-open', open);

        window.clearTimeout(panel.espTimer);
        if (!open || !inner) return;

        panel.espTimer = window.setTimeout(function () {
            if (panel.classList.contains('is-open')) inner.style.overflow = 'visible';
        }, reduceMotion.matches ? 0 : COLLAPSE_MS);
    }

    function isOpen(panel) {
        return !!panel && panel.classList.contains('is-open');
    }

    function focusInside(panel) {
        if (!panel) return;
        window.setTimeout(function () {
            var field = panel.querySelector('.pv-select__trigger') || panel.querySelector('input, select');
            if (!field) return;
            try { field.focus({ preventScroll: true }); } catch (err) { field.focus(); }
        }, reduceMotion.matches ? 0 : COLLAPSE_MS);
    }

    /* Un solo desplegable abierto en la sección de sensores: con el alta y
       una fila en edición al mismo tiempo quedaban dos formularios iguales
       uno encima del otro y no se sabía cuál se estaba tocando. */
    function closeSensorPanels(except) {
        document.querySelectorAll('[data-sensor-edit-panel]').forEach(function (panel) {
            if (panel !== except) setCollapsed(panel, false);
        });
        document.querySelectorAll('[data-sensor-item]').forEach(function (item) {
            if (!except || !item.contains(except)) item.classList.remove('is-editing');
        });

        var add = document.querySelector('[data-add-panel]');
        if (add && add !== except) {
            setCollapsed(add, false);
            var btn = document.querySelector('[data-add-toggle]');
            if (btn) {
                btn.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            }
        }
    }

    /* Todo por delegación: la lista de sensores se vuelve a dibujar sola
       cuando se guarda algo, y los nodos nuevos no tendrían los listeners
       de la carga inicial. */
    document.addEventListener('click', function (event) {
        // Ninguno frena la propagación: el click tiene que llegar al documento
        // para que prevcs-ui cierre los kebabs y las listas de los selects.
        var add = event.target.closest('[data-add-toggle]');
        if (add) {
            var addPanel = document.querySelector('[data-add-panel]');
            var opening = !isOpen(addPanel);

            closeSensorPanels(opening ? addPanel : null);
            setCollapsed(addPanel, opening);
            add.classList.toggle('is-open', opening);
            add.setAttribute('aria-expanded', opening ? 'true' : 'false');
            if (opening) focusInside(addPanel);
            return;
        }

        var edit = event.target.closest('[data-sensor-edit]');
        if (edit) {
            var item = edit.closest('[data-sensor-item]');
            var panel = item && item.querySelector('[data-sensor-edit-panel]');
            if (!panel) return;

            closeSensorPanels(panel);
            item.classList.add('is-editing');
            setCollapsed(panel, true);
            focusInside(panel);
            return;
        }

        var cancel = event.target.closest('[data-sensor-edit-cancel]');
        if (cancel) {
            closeSensorPanels(null);
            return;
        }

    });

    /* ── Sincronizar las pestañas con lo que pasó adentro del modal ─────

       Guardar un sensor ya no recarga la página: la ventana se actualiza
       sola con la respuesta del servidor. Lo que queda afuera de esa
       región son las pestañas y los gráficos, así que se acomodan acá:

         · cambió la etiqueta  → se reescribe el nombre de la pestaña;
         · se borró un sensor  → se sacan su pestaña y su panel;
         · se agregó uno, o cambió el tipo → hace falta el panel nuevo con
           su gráfico, que se arma del lado del servidor. Se marca la vista
           como vencida y se redibuja al cerrar la ventana, nunca en el
           medio de lo que esté haciendo el usuario. */
    var modal = document.getElementById('esp32-config-modal');
    var stale = false;

    function tabInput(id) {
        return document.querySelector('[data-section-target="sensor-' + id + '"]');
    }

    function renameTab(id, title) {
        var input = tabInput(id);
        var label = input && input.closest('.section-switch__radio');
        var name = label && label.querySelector('.section-switch__name');
        if (name) name.textContent = title;
    }

    function dropTab(id) {
        var input = tabInput(id);
        var label = input && input.closest('.section-switch__radio');
        var pane = document.querySelector('[data-section-pane="sensor-' + id + '"]');
        var wasActive = !!input && input.checked;

        if (label) label.remove();
        if (pane) pane.remove();

        var rest = document.querySelectorAll('.section-switch__radio input');
        if (!rest.length) return true; // sin sensores la vista cambia entera

        if (wasActive) {
            rest[0].checked = true;
            rest[0].dispatchEvent(new Event('change', { bubbles: true }));
        }
        return false;
    }

    document.addEventListener('prevcs:ajax-done', function (event) {
        var data = (event.detail && event.detail.data) || {};
        var sensor = data.sensor;
        if (!sensor) return;

        if (sensor.action === 'deleted') stale = dropTab(sensor.id) || stale;
        else if (sensor.action === 'updated' && !sensor.typeChanged) renameTab(sensor.id, sensor.title);
        else stale = true;
    });

    /* Al cerrar la ventana, si quedó algo pendiente de dibujar, se vuelve a
       pedir la página (sin ?config, para que no se reabra sola). */
    if (modal) {
        new MutationObserver(function () {
            if (modal.hidden && stale) {
                stale = false;
                window.location.replace(<?= json_encode(base_url('esp32/' . $device['id'])) ?>);
            }
        }).observe(modal, { attributes: true, attributeFilter: ['hidden'] });
    }
});
</script>
<?= $this->endSection() ?>
