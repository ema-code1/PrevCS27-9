<?php
/**
 * Tablero Kanban de una ESP32.
 *
 * Se incluye de forma estática en la vista de la placa, FUERA de las
 * pestañas de sensores: no cambia al moverse entre sensores porque es
 * global a la placa, no a cada gráfico.
 *
 * El markup de acá es solo el esqueleto (encabezado, toolbar, contenedores
 * y modales). Columnas y tarjetas las arma js/kanban.js con los datos que
 * devuelve KanbanController, y toda escritura se valida de nuevo en el
 * servidor contra la membresía del espacio de esta placa.
 *
 * @var array $device
 */
?>
<section class="panel card kb"
    data-kb-root
    data-kb-color="azul"
    data-kb-device="<?= (int) $device['id'] ?>"
    data-kb-base="<?= base_url('kanban/' . (int) $device['id']) ?>"
    data-kb-token="<?= csrf_hash() ?>"
    data-kb-token-name="<?= csrf_token() ?>"
    aria-label="Tablero de tareas de la placa">

    <header class="kb__head">
        <div class="kb__heading">
            <input type="text" class="kb__title kb-inline" data-kb-title maxlength="150"
                aria-label="Nombre del tablero" placeholder="Nombre del tablero">
        </div>

        <div class="kb__toolbar" data-kb-toolbar role="toolbar" aria-label="Herramientas del tablero">
            <span class="kb-saving" data-kb-saving role="status" aria-live="polite"></span>

            <label class="kb-search">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" aria-hidden="true">
                    <circle cx="9" cy="9" r="5.2"></circle><path d="m13 13 4 4"></path>
                </svg>
                <input type="search" data-kb-search placeholder="Buscar tareas…" aria-label="Buscar tareas">
            </label>

            <button type="button" class="kb-tool" data-kb-filters-btn aria-expanded="false" aria-controls="kb-filters">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3.5 5h13l-5 5.6V16l-3-1.6v-3.8z"></path>
                </svg>
                Filtros
            </button>

            <button type="button" class="kb-tool" data-kb-sort aria-haspopup="menu">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 4v12M6 16l-2.5-2.5M6 16l2.5-2.5M14 16V4M14 4l-2.5 2.5M14 4l2.5 2.5"></path>
                </svg>
                Ordenar
            </button>

            <button type="button" class="kb-tool kb-tool--icon" data-kb-archive
                aria-label="Elementos archivados" title="Elementos archivados">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3.2" y="4" width="13.6" height="3.4" rx="1"></rect>
                    <path d="M4.6 7.4v7.4a1.6 1.6 0 0 0 1.6 1.6h7.6a1.6 1.6 0 0 0 1.6-1.6V7.4M8.2 10.6h3.6"></path>
                </svg>
            </button>

            <button type="button" class="kb-tool kb-tool--icon" data-kb-bg aria-haspopup="menu"
                aria-label="Apariencia del tablero" title="Apariencia del tablero">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10 3a7 7 0 0 0 0 14c1 0 1.6-.7 1.6-1.5 0-.9-.8-1.4-.8-2.2 0-.7.6-1.3 1.3-1.3H14a3 3 0 0 0 3-3c0-3.3-3.1-6-7-6z"></path>
                </svg>
            </button>
        </div>
    </header>

    <div class="kb-offline" data-kb-offline role="status" aria-live="polite" hidden></div>

    <div class="kb-filters" id="kb-filters" data-kb-filters hidden></div>

    <!-- Contenedor de columnas: lo llena y reconcilia js/kanban.js. -->
    <div class="kb__board" data-kb-board data-kb-sort="manual" role="list"
        aria-label="Columnas del tablero" aria-busy="true" data-lenis-prevent-touch></div>

    <!-- ── Panel de tareas completadas (buscador + filas expandibles) ── -->
    <section class="kb-done" data-kb-done aria-label="Tareas completadas">
        <header class="kb-done__head">
            <h4>
                Completadas
                <!-- Sin número la burbuja se oculta sola (.kb-tool__badge:empty). -->
                <span class="kb-tool__badge" data-kb-done-count></span>
            </h4>
            <label class="kb-search">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" aria-hidden="true">
                    <circle cx="9" cy="9" r="5.2"></circle><path d="m13 13 4 4"></path>
                </svg>
                <input type="search" data-kb-done-search placeholder="Buscar en completadas…"
                    aria-label="Buscar entre las tareas completadas">
            </label>
        </header>
        <div class="kb-done__list" data-kb-done-list></div>
    </section>
</section>

<!-- ── Modal de detalle de tarea ─────────────────────────────────────
     Reutiliza .space-modal para heredar backdrop, animaciones de
     entrada/salida y el bloqueo de scroll de prevcs-ui.js. -->
<div class="space-modal kb-modal" data-space-modal data-kb-modal data-kb-device="<?= (int) $device['id'] ?>" hidden>
    <div class="space-modal__backdrop" data-kb-dismiss></div>
    <div class="space-modal__dialog card" role="dialog" aria-modal="true" aria-labelledby="kb-modal-title">
        <header class="space-modal__head">
            <div>
                <h3 id="kb-modal-title" data-kb-modal-title>Tarea</h3>
                <p class="text-muted mono">Tablero de <?= esc($device['name']) ?></p>
            </div>
            <button type="button" class="space-modal__close" data-kb-dismiss aria-label="Cerrar">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </header>
        <div class="space-modal__body" data-kb-modal-body data-lenis-prevent></div>
    </div>
</div>

<!-- ── Confirmaciones y diálogos chicos ─────────────────────────────── -->
<div class="space-modal" data-space-modal data-kb-confirm data-kb-device="<?= (int) $device['id'] ?>" hidden>
    <div class="space-modal__backdrop" data-kb-dismiss></div>
    <div class="space-modal__dialog card" role="dialog" aria-modal="true" aria-labelledby="kb-confirm-title">
        <header class="space-modal__head">
            <div><h3 id="kb-confirm-title" data-kb-confirm-title>Confirmar</h3></div>
            <button type="button" class="space-modal__close" data-kb-dismiss aria-label="Cerrar">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </header>
        <div class="space-modal__body" data-lenis-prevent>
            <div data-kb-confirm-text></div>
            <div class="kb-compose__row">
                <button type="button" class="btn btn--primary" data-kb-confirm-accept>Confirmar</button>
                <button type="button" class="btn btn--secondary" data-kb-dismiss>Cancelar</button>
            </div>
        </div>
    </div>
</div>
