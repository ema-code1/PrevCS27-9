<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/standalone.css') ?>?v=20260807-3">
<link rel="stylesheet" href="<?= base_url('css/stock.css') ?>?v=20260820-8">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$companyName = $industry['company_name'] ?? 'Espacio';
$spaceName   = $industry['space_name'] ?? '';
$sector      = $industry['sector'] ?? '';
$spaceLine   = trim($spaceName . ($sector !== '' ? ' - ' . $sector : ''));
?>

<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area standalone standalone--embedded stock-page">
        <div class="standalone__bar">
            <a class="btn btn--secondary standalone__back" href="<?= base_url('registros-iot') ?>">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 5 7 10l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver
            </a>
        </div>

        <header class="standalone__head">
            <p class="standalone__eyebrow">Stock del espacio</p>
            <h1><?= esc($companyName) ?></h1>
            <?php if ($spaceLine !== ''): ?>
                <p><?= esc($spaceLine) ?></p>
            <?php endif; ?>
        </header>

        <section class="stock-interactive-card" data-stock-app>
            <header class="stock-feature-head">
                <h2>Control de Stock</h2>

                <div class="stock-global-header">
                    <button class="stock-menu-btn" id="btn-menu-global" type="button" aria-label="Opciones de tablas" aria-haspopup="true" aria-expanded="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="12" cy="5" r="1.85"/>
                            <circle cx="12" cy="12" r="1.85"/>
                            <circle cx="12" cy="19" r="1.85"/>
                        </svg>
                    </button>

                    <div class="dropdown-menu" id="menu-global">
                        <button class="dropdown-item" id="btn-toggle-modo" type="button">Activar modo edicion</button>
                        <button class="dropdown-item" id="btn-nueva-tabla" type="button">Anadir nueva tabla</button>
                    </div>
                </div>
            </header>

            <div id="tablas-container" class="stock-tables-app"></div>

            <div class="drop-zone" id="drop-zone">
                <span class="drop-zone__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7.2 18.4a4.45 4.45 0 0 1-.6-8.86 5.45 5.45 0 0 1 10.55-1.48A4.2 4.2 0 0 1 17.4 18.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 20.5v-8.4M8.9 15.2 12 12.1l3.1 3.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <p>Solta un Excel o elegi archivo</p>
                <span class="drop-zone__formats">.xlsx y .xls</span>
                <input type="file" id="file-input" accept=".xlsx, .xls">
            </div>
        </section>

        <div class="column-menu" id="column-menu" hidden>
            <button type="button" data-column-action="properties">
                <span class="column-menu__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 5v5M5 15v4M12 5v2M12 12v7M19 5v7M19 17v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="5" cy="12.5" r="2.4" stroke="currentColor" stroke-width="1.8"/>
                        <circle cx="12" cy="9.5" r="2.4" stroke="currentColor" stroke-width="1.8"/>
                        <circle cx="19" cy="14.5" r="2.4" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </span>
                Propiedades de Columna
            </button>
            <button type="button" data-column-action="rename">
                <span class="column-menu__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M14.7 4.8a2.1 2.1 0 0 1 3 3l-8.4 8.4-3.9.9.9-3.9 8.4-8.4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </span>
                Renombrar Columna
            </button>
            <button type="button" data-column-action="insert-left">
                <span class="column-menu__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="12.5" y="4" width="8" height="16" rx="2.2" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M7 8.5v7M3.5 12h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                Insertar a la Izquierda
            </button>
            <button type="button" data-column-action="insert-right">
                <span class="column-menu__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="3.5" y="4" width="8" height="16" rx="2.2" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M17 8.5v7M13.5 12h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                Insertar a la Derecha
            </button>
            <button type="button" data-column-action="delete" class="is-danger">
                <span class="column-menu__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 7h16M10 11.5v5M14 11.5v5M6.5 7l.9 12.2a1.5 1.5 0 0 0 1.5 1.3h6.2a1.5 1.5 0 0 0 1.5-1.3L17.5 7M9.5 7V4.8a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                Eliminar Columna
            </button>
        </div>

        <div class="stock-modal-overlay" id="modal-overlay" hidden></div>
        <section class="stock-modal" id="prop-modal" role="dialog" aria-modal="true" aria-labelledby="prop-modal-title" hidden>
            <header class="stock-modal__head">
                <span class="stock-modal__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 5v6M5 15v4M12 5v3M12 12v7M19 5v9M19 18v1" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                        <circle cx="5" cy="13" r="1.9" stroke="currentColor" stroke-width="1.9"/>
                        <circle cx="12" cy="10" r="1.9" stroke="currentColor" stroke-width="1.9"/>
                        <circle cx="19" cy="16" r="1.9" stroke="currentColor" stroke-width="1.9"/>
                    </svg>
                </span>
                <div class="stock-modal__heading">
                    <h2 id="prop-modal-title">Propiedades de Columna</h2>
                    <p class="stock-modal__subtitle" data-prop-subtitle>Configurando la columna</p>
                </div>
                <button class="stock-modal__close" id="btn-cerrar-prop" type="button" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6.5 6.5l11 11M17.5 6.5l-11 11" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                    </svg>
                </button>
            </header>

            <div class="stock-modal__body">
                <fieldset class="stock-fieldset">
                    <legend class="stock-fieldset__label">Tipo de columna</legend>
                    <div class="stock-type-grid">
                        <label class="stock-type-card">
                            <input type="radio" name="stock-col-tipo" value="texto" checked>
                            <span class="stock-type-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M6 3h8l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="M14 3v5h4M8.5 13h7M8.5 17h4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="stock-type-card__text">
                                <span class="stock-type-card__title">Texto</span>
                                <span class="stock-type-card__hint">Texto libre</span>
                            </span>
                        </label>

                        <label class="stock-type-card">
                            <input type="radio" name="stock-col-tipo" value="contador">
                            <span class="stock-type-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M9 4L7 20M17 4l-2 16M4.5 9h15M3.5 15h15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span class="stock-type-card__text">
                                <span class="stock-type-card__title">Contador</span>
                                <span class="stock-type-card__hint">Valor (&#94; 0 &#8964;)</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div class="stock-fieldset stock-fieldset--unit" id="config-contador" hidden>
                    <label class="stock-fieldset__label" for="input-unidad">Etiqueta de unidad</label>
                    <input class="stock-input" type="text" id="input-unidad" value="unidades" autocomplete="off">
                    <p class="stock-fieldset__help">Esta etiqueta se mostrara junto al valor del contador.</p>
                </div>
            </div>

            <footer class="stock-modal__actions">
                <button class="stock-modal__btn stock-modal__btn--ghost" id="btn-cancelar-prop" type="button">Cancelar</button>
                <button class="stock-modal__btn stock-modal__btn--primary" id="btn-guardar-prop" type="button">Guardar Cambios</button>
            </footer>
        </section>

        <section class="stock-confirm" id="stock-confirm" role="dialog" aria-modal="true" aria-labelledby="stock-confirm-title" hidden>
            <h2 id="stock-confirm-title">Eliminar tabla</h2>
            <p id="stock-confirm-message">Seguro que queres eliminar esta tabla?</p>
            <div class="stock-confirm__actions">
                <button class="stock-confirm__btn" id="btn-cancel-delete" type="button">Cancelar</button>
                <button class="stock-confirm__btn stock-confirm__btn--danger" id="btn-confirm-delete" type="button">Eliminar</button>
            </div>
        </section>

    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260820-1" defer></script>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script>
(function () {
    'use strict';

    var modoEdicion = false;
    var contadorTablas = 0;
    var colActiva = null;

    var app = document.querySelector('[data-stock-app]');
    if (!app) return;

    var tablasContainer = document.getElementById('tablas-container');
    var menuButton = document.getElementById('btn-menu-global');
    var menuGlobal = document.getElementById('menu-global');
    var toggleModo = document.getElementById('btn-toggle-modo');
    var nuevaTabla = document.getElementById('btn-nueva-tabla');
    var modal = document.getElementById('prop-modal');
    var overlay = document.getElementById('modal-overlay');
    var tipoRadios = Array.prototype.slice.call(modal.querySelectorAll('input[name="stock-col-tipo"]'));
    var propSubtitle = modal.querySelector('[data-prop-subtitle]');
    var configContador = document.getElementById('config-contador');
    var inputUnidad = document.getElementById('input-unidad');
    var guardarProp = document.getElementById('btn-guardar-prop');
    var cancelarProp = document.getElementById('btn-cancelar-prop');
    var cerrarProp = document.getElementById('btn-cerrar-prop');
    var dropZone = document.getElementById('drop-zone');
    var fileInput = document.getElementById('file-input');
    var columnMenu = document.getElementById('column-menu');
    var confirmDialog = document.getElementById('stock-confirm');
    var confirmMessage = document.getElementById('stock-confirm-message');
    var confirmDelete = document.getElementById('btn-confirm-delete');
    var cancelDelete = document.getElementById('btn-cancel-delete');
    var columnaMenuActiva = null;
    var tablaPendienteEliminar = null;
    var columnMenuTimer = null;
    var columnHighlight = null;
    var menuGlobalHighlight = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function iconoTipo(tipo) {
        return tipo === 'contador' ? '#' : 'T';
    }

    /* ── Capas modales ────────────────────────────────────────────────────
       Abren y cierran con transicion: el atributo [hidden] se saca antes de
       animar y se repone recien cuando termina la salida, asi el dialogo no
       desaparece de golpe. El overlay se comparte entre todas las capas. */
    function capaActiva() {
        return (modal && modal.classList.contains('is-open')) ||
            (confirmDialog && confirmDialog.classList.contains('is-open'));
    }

    function abrirCapa(el) {
        if (!el) return;

        if (el._stockCloseTimer) {
            window.clearTimeout(el._stockCloseTimer);
            el._stockCloseTimer = null;
        }

        el.hidden = false;
        if (overlay) overlay.hidden = false;
        void el.offsetWidth;
        if (overlay) overlay.classList.add('is-open');
        el.classList.add('is-open');
    }

    function cerrarCapa(el) {
        if (!el || el.hidden) return;

        el.classList.remove('is-open');
        if (overlay && !capaActiva()) overlay.classList.remove('is-open');

        el._stockCloseTimer = window.setTimeout(function () {
            el.hidden = true;
            el._stockCloseTimer = null;
            if (overlay && !capaActiva()) overlay.hidden = true;
        }, 180);
    }

    /* ── Recuadro flotante de la celda enfocada ───────────────────────────
       Vive dentro de .tabla-canvas (que es position:relative) y se comparte
       entre todas las celdas de esa tabla, asi el salto de una a otra es un
       desplazamiento y no un borde que se apaga y otro que se prende. */
    var celdaHl = null;

    function highlightDeCanvas(canvas) {
        var hl = canvas.querySelector(':scope > .cell-hl');

        if (!hl) {
            hl = document.createElement('span');
            hl.className = 'cell-hl';
            hl.setAttribute('aria-hidden', 'true');
            canvas.appendChild(hl);
        }

        return hl;
    }

    function ocultarCeldaActiva() {
        if (!celdaHl) return;
        celdaHl.classList.remove('is-visible');
        celdaHl = null;
    }

    function moverCeldaActiva(celda) {
        var canvas = celda.closest('.tabla-canvas');
        if (!canvas) return;

        var hl = highlightDeCanvas(canvas);

        // Cambiar de tabla apaga el recuadro anterior: no puede viajar entre
        // dos canvas distintos.
        if (celdaHl && celdaHl !== hl) celdaHl.classList.remove('is-visible');

        var box = celda.getBoundingClientRect();
        var base = canvas.getBoundingClientRect();
        var entrando = celdaHl !== hl;

        if (entrando) hl.classList.add('is-placing');

        hl.style.width = box.width + 'px';
        hl.style.height = box.height + 'px';
        hl.style.transform = 'translate3d(' + (box.left - base.left) + 'px,' + (box.top - base.top) + 'px,0)';

        if (entrando) {
            void hl.offsetWidth;
            hl.classList.remove('is-placing');
        }

        hl.classList.add('is-visible');
        celdaHl = hl;
    }

    /* ── Highlight deslizante (mismo efecto que el sidebar y el header) ───
       Una sola superficie que viaja entre los items del menu en vez de que
       cada uno prenda su propio fondo. La geometria sale del item apuntado;
       el color, del currentColor del contenedor. */
    function montarHighlight(container, selector) {
        if (!container) return null;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

        var hl = document.createElement('span');
        hl.className = 'nav-hl';
        hl.setAttribute('aria-hidden', 'true');
        container.appendChild(hl);
        container.classList.add('has-hl');

        var visible = false;

        function moveTo(target) {
            var box = target.getBoundingClientRect();
            var base = container.getBoundingClientRect();

            if (!visible) hl.classList.add('is-placing');

            hl.style.width = box.width + 'px';
            hl.style.height = box.height + 'px';
            hl.style.transform = 'translate3d(' + (box.left - base.left) + 'px,' + (box.top - base.top) + 'px,0)';
            hl.style.borderRadius = window.getComputedStyle(target).borderRadius;

            if (!visible) {
                void hl.offsetWidth;
                hl.classList.remove('is-placing');
                visible = true;
            }

            hl.classList.add('is-visible');
        }

        function hide() {
            visible = false;
            hl.classList.remove('is-visible');
        }

        container.addEventListener('mouseover', function (event) {
            var target = event.target.closest(selector);
            if (target && container.contains(target)) moveTo(target);
        });
        container.addEventListener('mouseleave', hide);
        window.addEventListener('resize', hide);

        return { hide: hide };
    }

    var TOAST_MS = 2000;

    /* Los avisos del stock usan el mismo toast que el resto del proyecto
       (arriba a la derecha, con entrada y salida animadas y barra de vida),
       en vez de una pila propia abajo. prevcs-ui.js se carga con defer, asi
       que recien existe despues de este script: solo se llama desde acciones
       del usuario, pero igual se comprueba antes de usarlo. */
    function mostrarPopup(mensaje, tipo, accionTexto, accionCallback) {
        var toast = window.PrevcsToast;
        if (!toast || typeof toast.show !== 'function') return;

        var accion = null;
        if (accionTexto && typeof accionCallback === 'function') {
            accion = { label: accionTexto, onClick: accionCallback };
        }

        // El toast compartido solo distingue error de exito.
        var esError = tipo === 'error' || tipo === 'warning';
        toast.show(mensaje, esError ? 'error' : 'success', TOAST_MS, accion);
    }

    function chevronSvg(direction) {
        var path = direction === 'up' ? 'M6 14l6-6 6 6' : 'M6 10l6 6 6-6';

        return '<svg class="contador-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
            '<path d="' + path + '" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>' +
        '</svg>';
    }

    function dotsSvg() {
        return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
            '<circle cx="12" cy="5" r="1.85"/>' +
            '<circle cx="12" cy="12" r="1.85"/>' +
            '<circle cx="12" cy="19" r="1.85"/>' +
        '</svg>';
    }

    function plusSvg() {
        return '<svg class="add-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
            '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>' +
        '</svg>';
    }

    function generarTh(nombre, tipo, unidad) {
        var columnType = tipo || 'texto';
        var columnUnit = unidad || '';

        return '' +
            '<th data-type="' + escapeHtml(columnType) + '" data-unit="' + escapeHtml(columnUnit) + '">' +
                '<div class="col-header-content">' +
                    '<span class="column-kind" data-col-icon>' + iconoTipo(columnType) + '</span>' +
                    '<span class="col-name" data-editable="true">' + escapeHtml(nombre) + '</span>' +
                    '<button class="col-menu-btn" type="button" data-action="column-menu" aria-label="Menu de columna" aria-haspopup="true" aria-expanded="false">' + dotsSvg() + '</button>' +
                '</div>' +
            '</th>';
    }

    function generarHtmlContador(unidad) {
        var label = unidad || 'unidades';

        return '' +
            '<div class="contador-wrapper">' +
                '<div class="contador-controls">' +
                    '<button class="contador-btn" type="button" data-counter-delta="1" aria-label="Sumar">' + chevronSvg('up') + '</button>' +
                    '<input type="text" inputmode="numeric" pattern="[0-9]*" class="contador-input" value="0" autocomplete="off">' +
                    '<button class="contador-btn" type="button" data-counter-delta="-1" aria-label="Restar">' + chevronSvg('down') + '</button>' +
                '</div>' +
                '<span class="unidad-label" data-editable-unidad="true">' + escapeHtml(label) + '</span>' +
            '</div>';
    }

    function botonEliminarFila() {
        return '<button class="btn-eliminar-fila" type="button" data-action="delete-row" aria-label="Eliminar fila" title="Eliminar fila">' +
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                '<path d="M7 7l10 10M17 7L7 17" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>' +
            '</svg>' +
        '</button>';
    }

    /* La cruz de borrar vive en la primera celda de cada fila y se dibuja en
       el margen izquierdo del canvas. Como insertar o borrar columnas cambia
       cual es "la primera", se rehace despues de cada cambio estructural en
       vez de intentar seguirle el rastro. */
    function sincronizarBotonesFila(tabla) {
        if (!tabla) return;

        tabla.querySelectorAll('tbody tr').forEach(function (tr) {
            tr.querySelectorAll('.btn-eliminar-fila').forEach(function (btn) {
                btn.remove();
            });

            var primera = tr.firstElementChild;
            if (primera) primera.insertAdjacentHTML('beforeend', botonEliminarFila());
        });
    }

    function generarTd(tipo, unidad, texto, placeholder) {
        if (tipo === 'contador') {
            return '<td>' + generarHtmlContador(unidad) + '</td>';
        }

        return '' +
            '<td>' +
                '<div class="cell-content" data-editable="true" data-placeholder="' + escapeHtml(placeholder || '') + '">' +
                    escapeHtml(texto || '') +
                '</div>' +
            '</td>';
    }

    function crearNuevaTabla() {
        contadorTablas++;

        var tablaId = 'tabla-' + contadorTablas;
        var wrapper = document.createElement('article');
        wrapper.className = 'tabla-wrapper';
        wrapper.id = 'wrapper-' + tablaId;
        wrapper.dataset.tableId = tablaId;

        wrapper.innerHTML = '' +
            '<header class="tabla-header">' +
                '<div class="tabla-title-group">' +
                    '<span class="tabla-icon" aria-hidden="true">' +
                        '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18" stroke="currentColor" stroke-width="1.7"/></svg>' +
                    '</span>' +
                    '<h2 class="tabla-titulo" data-editable="true">Tabla Interactiva ' + contadorTablas + '</h2>' +
                    '<span class="tabla-meta" data-table-meta></span>' +
                '</div>' +
                '<div class="tabla-actions">' +
                    '<span class="tabla-mode" data-table-mode>Modo lectura interactivo</span>' +
                    '<button class="btn-eliminar-tabla" type="button" data-action="delete-table" aria-label="Eliminar tabla" title="Eliminar tabla">' +
                        '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '</button>' +
                '</div>' +
            '</header>' +
            '<div class="tabla-scroll">' +
                '<div class="tabla-canvas">' +
                    '<table id="' + tablaId + '" class="interactive-table">' +
                        '<thead>' +
                            '<tr>' +
                                generarTh('Columna 1', 'texto', '') +
                                generarTh('Columna 2', 'contador', 'unidades') +
                                generarTh('Columna 3', 'texto', '') +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>' +
                            '<tr>' +
                                generarTd('texto', '', 'Fila 1', '') +
                                generarTd('contador', 'unidades') +
                                generarTd('texto', '', '', 'Escribe algo aqui...') +
                            '</tr>' +
                        '</tbody>' +
                    '</table>' +
                    '<div class="add-controls btn-add-col">' +
                        '<button class="btn-add" type="button" data-action="add-col" aria-label="Anadir columna">' + plusSvg() + '</button>' +
                    '</div>' +
                    '<div class="add-controls btn-add-row">' +
                        '<button class="btn-add" type="button" data-action="add-row" aria-label="Anadir fila">' + plusSvg() + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        tablasContainer.appendChild(wrapper);
        actualizarAtributosEditables();
        actualizarMeta(wrapper);
    }

    function crearTablaDesdeDatos(nombreArchivo, datos) {
        contadorTablas++;

        var tablaId = 'tabla-' + contadorTablas;
        var headers = datos[0] || [];
        var filas = datos.slice(1);
        var wrapper = document.createElement('article');

        wrapper.className = 'tabla-wrapper';
        wrapper.id = 'wrapper-' + tablaId;
        wrapper.dataset.tableId = tablaId;

        var theadHtml = '';
        headers.forEach(function (header, index) {
            theadHtml += generarTh(header || ('Col ' + (index + 1)), 'texto', '');
        });

        var tbodyHtml = '';
        filas.forEach(function (fila) {
            tbodyHtml += '<tr>';
            headers.forEach(function (_, colIndex) {
                var value = fila[colIndex] !== undefined ? fila[colIndex] : '';
                tbodyHtml += generarTd('texto', '', value, '');
            });
            tbodyHtml += '</tr>';
        });

        wrapper.innerHTML = '' +
            '<header class="tabla-header">' +
                '<div class="tabla-title-group">' +
                    '<span class="tabla-icon" aria-hidden="true">' +
                        '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18" stroke="currentColor" stroke-width="1.7"/></svg>' +
                    '</span>' +
                    '<h2 class="tabla-titulo" data-editable="true">' + escapeHtml(nombreArchivo.replace(/\.[^/.]+$/, '')) + '</h2>' +
                    '<span class="tabla-meta" data-table-meta></span>' +
                '</div>' +
                '<div class="tabla-actions">' +
                    '<span class="tabla-mode" data-table-mode>Modo lectura interactivo</span>' +
                    '<button class="btn-eliminar-tabla" type="button" data-action="delete-table" aria-label="Eliminar tabla" title="Eliminar tabla">' +
                        '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '</button>' +
                '</div>' +
            '</header>' +
            '<div class="tabla-scroll">' +
                '<div class="tabla-canvas">' +
                    '<table id="' + tablaId + '" class="interactive-table">' +
                        '<thead><tr>' + theadHtml + '</tr></thead>' +
                        '<tbody>' + tbodyHtml + '</tbody>' +
                    '</table>' +
                    '<div class="add-controls btn-add-col">' +
                        '<button class="btn-add" type="button" data-action="add-col" aria-label="Anadir columna">' + plusSvg() + '</button>' +
                    '</div>' +
                    '<div class="add-controls btn-add-row">' +
                        '<button class="btn-add" type="button" data-action="add-row" aria-label="Anadir fila">' + plusSvg() + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        tablasContainer.appendChild(wrapper);
        actualizarAtributosEditables();
        actualizarMeta(wrapper);
    }

    function procesarExcel(file) {
        if (!file) return;

        var extension = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls'].indexOf(extension) === -1) {
            mostrarPopup('Solo se admiten archivos .xlsx y .xls', 'error');
            if (fileInput) fileInput.value = '';
            return;
        }

        if (!window.XLSX) {
            mostrarPopup('No se pudo cargar la libreria de Excel', 'error');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (event) {
            var data = new Uint8Array(event.target.result);
            var workbook = XLSX.read(data, { type: 'array' });
            var primeraHoja = workbook.Sheets[workbook.SheetNames[0]];
            var jsonDatos = XLSX.utils.sheet_to_json(primeraHoja, { header: 1 });

            if (jsonDatos.length > 0) {
                crearTablaDesdeDatos(file.name, jsonDatos);
                mostrarPopup('Tabla creada desde Excel', 'success');
            } else {
                mostrarPopup('El archivo no tiene datos para mostrar', 'warning');
            }
            if (fileInput) fileInput.value = '';
        };
        reader.readAsArrayBuffer(file);
    }

    function actualizarMeta(wrapper) {
        var table = wrapper.querySelector('table');
        if (!table) return;

        var cols = table.querySelectorAll('thead th').length;
        var rows = table.querySelectorAll('tbody tr').length;
        var meta = wrapper.querySelector('[data-table-meta]');
        var mode = wrapper.querySelector('[data-table-mode]');

        sincronizarBotonesFila(table);

        if (meta) meta.textContent = '';

        if (mode) {
            mode.textContent = modoEdicion ? 'Modo edicion activo' : 'Modo lectura interactivo';
        }
    }

    function actualizarTodoMeta() {
        document.querySelectorAll('.tabla-wrapper').forEach(actualizarMeta);
    }

    function actualizarAtributosEditables() {
        app.classList.toggle('modo-edicion', modoEdicion);

        document.querySelectorAll('[data-editable="true"]').forEach(function (elemento) {
            elemento.setAttribute('contenteditable', modoEdicion ? 'true' : 'false');
        });

        document.querySelectorAll('[data-editable-unidad="true"]').forEach(function (elemento) {
            elemento.setAttribute('contenteditable', modoEdicion ? 'true' : 'false');
        });

        // En modo lectura el numero sigue siendo interactivo por los chevrones,
        // pero no debe recibir el foco: si no, al tocarlo aparece el cursor de
        // escritura sobre un valor que no se puede tipear.
        document.querySelectorAll('.contador-input').forEach(function (input) {
            input.readOnly = !modoEdicion;
            input.tabIndex = modoEdicion ? 0 : -1;
            ajustarAnchoContador(input);
        });

        if (!modoEdicion) ocultarCeldaActiva();

        toggleModo.textContent = modoEdicion ? 'Salir de edicion' : 'Activar modo edicion';
        actualizarTodoMeta();
    }

    /* Marca los nodos recien insertados para que el CSS los anime al entrar
       y limpia la clase cuando termina, asi no se reanima en cada repaint. */
    function animarEntrada(nodes, className) {
        if (!nodes.length) return;

        nodes.forEach(function (node) {
            node.classList.add(className);
        });

        window.setTimeout(function () {
            nodes.forEach(function (node) {
                node.classList.remove(className);
            });
        }, 420);
    }

    function agregarFila(tabla) {
        var headers = Array.prototype.slice.call(tabla.querySelectorAll('thead th'));
        var tr = document.createElement('tr');

        headers.forEach(function (th) {
            tr.insertAdjacentHTML('beforeend', generarTd(th.dataset.type, th.dataset.unit));
        });

        tabla.querySelector('tbody').appendChild(tr);
        animarEntrada([tr], 'is-entering');
        actualizarAtributosEditables();
        actualizarMeta(tabla.closest('.tabla-wrapper'));
    }

    function eliminarFila(tr, tabla) {
        if (!tr || !tabla) return;

        if (tabla.querySelectorAll('tbody tr').length <= 1) {
            mostrarPopup('La tabla debe tener al menos una fila', 'error');
            return;
        }

        var wrapper = tabla.closest('.tabla-wrapper');

        ocultarCeldaActiva();
        tr.classList.add('is-leaving');

        window.setTimeout(function () {
            tr.remove();
            actualizarMeta(wrapper);
        }, 240);

        mostrarPopup('Fila eliminada', 'info');
    }

    function agregarColumna(tabla) {
        var thCount = tabla.querySelectorAll('thead th').length + 1;
        var headerRow = tabla.querySelector('thead tr');
        var nuevos = [];

        headerRow.insertAdjacentHTML('beforeend', generarTh('Columna ' + thCount, 'texto', ''));
        nuevos.push(headerRow.lastElementChild);

        tabla.querySelectorAll('tbody tr').forEach(function (tr) {
            tr.insertAdjacentHTML('beforeend', generarTd('texto', ''));
            nuevos.push(tr.lastElementChild);
        });

        animarEntrada(nuevos, 'is-entering-col');
        actualizarAtributosEditables();
        actualizarMeta(tabla.closest('.tabla-wrapper'));
    }

    function crearNodoHtml(html) {
        var template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstElementChild;
    }

    function insertarColumna(tabla, index) {
        var thCount = tabla.querySelectorAll('thead th').length + 1;
        var headerRow = tabla.querySelector('thead tr');
        var newTh = crearNodoHtml(generarTh('Columna ' + thCount, 'texto', ''));
        var nuevos = [newTh];

        headerRow.insertBefore(newTh, headerRow.children[index] || null);

        tabla.querySelectorAll('tbody tr').forEach(function (tr) {
            var newTd = crearNodoHtml(generarTd('texto', '', '', 'Insertar texto'));
            tr.insertBefore(newTd, tr.children[index] || null);
            nuevos.push(newTd);
        });

        animarEntrada(nuevos, 'is-entering-col');
        actualizarAtributosEditables();
        actualizarMeta(tabla.closest('.tabla-wrapper'));
    }

    function abrirMenuColumna(button) {
        columnaMenuActiva = button.closest('th');
        if (!columnaMenuActiva || !columnMenu) return;

        // Un solo desplegable abierto por vez: el del contenedor se cierra.
        toggleMenu(false);

        if (columnMenuTimer) {
            window.clearTimeout(columnMenuTimer);
            columnMenuTimer = null;
        }

        var rect = button.getBoundingClientRect();
        columnMenu.hidden = false;
        columnMenu.style.left = Math.max(12, Math.min(rect.left, window.innerWidth - columnMenu.offsetWidth - 12)) + 'px';
        columnMenu.style.top = Math.min(rect.bottom + 6, window.innerHeight - columnMenu.offsetHeight - 12) + 'px';

        // El menu nace transparente y encogido: hay que fijar la posicion
        // antes de prender .is-open para que la entrada anime desde ahi.
        void columnMenu.offsetWidth;
        columnMenu.classList.add('is-open');

        marcarBotonColumna(button);
    }

    // El boton de la columna abierta queda resaltado mientras dura el menu.
    function marcarBotonColumna(button) {
        document.querySelectorAll('.col-menu-btn[aria-expanded="true"]').forEach(function (btn) {
            if (btn !== button) btn.setAttribute('aria-expanded', 'false');
        });

        if (button) button.setAttribute('aria-expanded', 'true');
    }

    function cerrarMenuColumna() {
        columnaMenuActiva = null;
        marcarBotonColumna(null);
        if (!columnMenu || columnMenu.hidden) return;

        columnMenu.classList.remove('is-open');
        if (columnHighlight) columnHighlight.hide();

        columnMenuTimer = window.setTimeout(function () {
            columnMenu.hidden = true;
            columnMenuTimer = null;
        }, 160);
    }

    function ejecutarAccionColumna(action) {
        if (!columnaMenuActiva) return;

        var tabla = columnaMenuActiva.closest('table');
        var headerRow = columnaMenuActiva.parentElement;
        var colIndex = Array.prototype.indexOf.call(headerRow.children, columnaMenuActiva);

        if (action === 'properties') {
            var target = columnaMenuActiva.querySelector('.col-menu-btn');
            cerrarMenuColumna();
            abrirModalPropiedades(target);
            return;
        }

        if (action === 'rename') {
            var name = columnaMenuActiva.querySelector('.col-name');
            cerrarMenuColumna();
            if (name) {
                name.setAttribute('contenteditable', 'true');
                name.focus();
                var range = document.createRange();
                range.selectNodeContents(name);
                var selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            }
            return;
        }

        if (action === 'insert-left') insertarColumna(tabla, colIndex);
        if (action === 'insert-right') insertarColumna(tabla, colIndex + 1);

        if (action === 'delete') {
            if (headerRow.children.length <= 1) {
                mostrarPopup('La tabla debe tener al menos una columna', 'error');
                cerrarMenuColumna();
                return;
            }

            columnaMenuActiva.remove();
            tabla.querySelectorAll('tbody tr').forEach(function (tr) {
                if (tr.children[colIndex]) tr.children[colIndex].remove();
            });
            mostrarPopup('Columna eliminada', 'info');
            actualizarMeta(tabla.closest('.tabla-wrapper'));
        }

        cerrarMenuColumna();
    }

    function pedirEliminarTabla(wrapper) {
        if (document.querySelectorAll('.tabla-wrapper').length <= 1) {
            mostrarPopup('No se pueden eliminar todas las tablas. Debe quedar al menos una.', 'error');
            return;
        }

        tablaPendienteEliminar = wrapper;
        if (confirmMessage) confirmMessage.textContent = 'Seguro que queres eliminar esta tabla?';
        abrirCapa(confirmDialog);
    }

    function cerrarConfirmacion() {
        cerrarCapa(confirmDialog);
        tablaPendienteEliminar = null;
    }

    function confirmarEliminarTabla() {
        var wrapper = tablaPendienteEliminar;
        if (!wrapper) return;

        var siguiente = wrapper.nextSibling;
        cerrarConfirmacion();
        wrapper.remove();

        mostrarPopup('Tabla eliminada', 'info', 'Revertir', function () {
            if (siguiente && siguiente.parentNode === tablasContainer) {
                tablasContainer.insertBefore(wrapper, siguiente);
            } else {
                tablasContainer.appendChild(wrapper);
            }
            actualizarAtributosEditables();
            mostrarPopup('Tabla restaurada', 'success');
        });
    }

    function cambiarContador(button) {
        var input = button.parentElement.querySelector('input');
        var delta = parseInt(button.dataset.counterDelta, 10) || 0;
        var value = parseInt(input.value, 10) || 0;

        value += delta;
        if (value < 0) value = 0;
        input.value = value;
        ajustarAnchoContador(input);
    }

    /* El campo crece con la cantidad de digitos: con un ancho fijo el segundo
       numero quedaba cortado. La unidad, que se achica sola, cede el espacio. */
    function ajustarAnchoContador(input) {
        var digitos = Math.max(1, String(input.value).length);
        input.style.width = (digitos + 0.7) + 'ch';
    }

    function ajustarAnchoContadores(raiz) {
        (raiz || document).querySelectorAll('.contador-input').forEach(ajustarAnchoContador);
    }

    function validarContador(input) {
        var crudo = input.value;
        var caret = input.selectionStart;

        // Solo digitos. Y el cero inicial se va en cuanto se tipea encima:
        // arrancando de "0", escribir 1 dejaba "01" en pantalla.
        var limpio = crudo.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
        if (limpio === '') limpio = '0';

        if (limpio !== crudo) {
            var quitados = crudo.length - limpio.length;
            input.value = limpio;

            // El caret se corre tantos lugares como caracteres se borraron
            // antes de el, para no mandarlo al principio en cada tecla.
            var destino = Math.max(0, (caret === null ? limpio.length : caret) - quitados);
            try { input.setSelectionRange(destino, destino); } catch (e) {}
        }

        ajustarAnchoContador(input);
    }

    function tipoSeleccionado() {
        for (var i = 0; i < tipoRadios.length; i++) {
            if (tipoRadios[i].checked) return tipoRadios[i].value;
        }
        return 'texto';
    }

    function abrirModalPropiedades(button) {
        colActiva = button.closest('th');
        if (!colActiva) return;

        var tipoActual = colActiva.dataset.type || 'texto';
        var unidadActual = colActiva.dataset.unit || 'unidades';
        var nombreCol = colActiva.querySelector('.col-name');

        tipoRadios.forEach(function (radio) {
            radio.checked = radio.value === tipoActual;
        });

        if (propSubtitle) {
            var nombre = nombreCol ? nombreCol.textContent.trim() : '';
            propSubtitle.textContent = nombre
                ? 'Configurando "' + nombre + '"'
                : 'Configurando la columna';
        }

        inputUnidad.value = unidadActual;
        configContador.hidden = tipoActual !== 'contador';
        abrirCapa(modal);
    }

    function cerrarModal() {
        cerrarCapa(modal);
        colActiva = null;
    }

    function guardarPropiedades() {
        if (!colActiva) return;

        var nuevoTipo = tipoSeleccionado();
        var nuevaUnidad = inputUnidad.value.trim() || 'unidades';
        var colIndex = Array.prototype.indexOf.call(colActiva.parentElement.children, colActiva);
        var tabla = colActiva.closest('table');

        colActiva.dataset.type = nuevoTipo;
        colActiva.dataset.unit = nuevaUnidad;
        colActiva.querySelector('[data-col-icon]').textContent = iconoTipo(nuevoTipo);

        tabla.querySelectorAll('tbody tr').forEach(function (fila) {
            var celda = fila.children[colIndex];
            if (!celda) return;

            if (nuevoTipo === 'contador') {
                if (celda.querySelector('.contador-wrapper')) {
                    celda.querySelector('.unidad-label').textContent = nuevaUnidad;
                } else {
                    celda.innerHTML = generarHtmlContador(nuevaUnidad);
                }
            } else if (celda.querySelector('.contador-wrapper')) {
                celda.innerHTML = '<div class="cell-content" data-editable="true"></div>';
            }
        });

        actualizarAtributosEditables();
        cerrarModal();
    }

    function toggleMenu(show) {
        var next = typeof show === 'boolean' ? show : !menuGlobal.classList.contains('active');
        menuGlobal.classList.toggle('active', next);
        menuButton.setAttribute('aria-expanded', next ? 'true' : 'false');
        if (!next && menuGlobalHighlight) menuGlobalHighlight.hide();
    }

    menuButton.addEventListener('click', function (event) {
        event.stopPropagation();
        cerrarMenuColumna();
        toggleMenu();
    });

    document.addEventListener('click', function (event) {
        if (!menuGlobal.contains(event.target) && event.target !== menuButton) {
            toggleMenu(false);
        }

        if (
            columnMenu &&
            !columnMenu.hidden &&
            !columnMenu.contains(event.target) &&
            !event.target.closest('[data-action="column-menu"]')
        ) {
            cerrarMenuColumna();
        }
    });

    toggleModo.addEventListener('click', function () {
        modoEdicion = !modoEdicion;
        actualizarAtributosEditables();
        toggleMenu(false);
    });

    nuevaTabla.addEventListener('click', function () {
        crearNuevaTabla();
        toggleMenu(false);
    });

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            procesarExcel(fileInput.files[0]);
        });

        dropZone.addEventListener('dragover', function (event) {
            event.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', function () {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', function (event) {
            event.preventDefault();
            dropZone.classList.remove('dragover');
            if (event.dataTransfer.files.length) {
                procesarExcel(event.dataTransfer.files[0]);
            }
        });
    }

    if (columnMenu) {
        columnMenu.addEventListener('click', function (event) {
            event.stopPropagation();
            var button = event.target.closest('[data-column-action]');
            if (button) ejecutarAccionColumna(button.dataset.columnAction);
        });
    }

    tipoRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            configContador.hidden = tipoSeleccionado() !== 'contador';
        });
    });

    guardarProp.addEventListener('click', guardarPropiedades);
    cancelarProp.addEventListener('click', cerrarModal);
    if (cerrarProp) cerrarProp.addEventListener('click', cerrarModal);
    if (confirmDelete) confirmDelete.addEventListener('click', confirmarEliminarTabla);
    if (cancelDelete) cancelDelete.addEventListener('click', cerrarConfirmacion);
    overlay.addEventListener('click', function () {
        if (confirmDialog && !confirmDialog.hidden) {
            cerrarConfirmacion();
            return;
        }
        cerrarModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && confirmDialog && !confirmDialog.hidden) cerrarConfirmacion();
        if (event.key === 'Escape' && !modal.hidden) cerrarModal();
    });

    tablasContainer.addEventListener('click', function (event) {
        var counterButton = event.target.closest('[data-counter-delta]');
        if (counterButton) {
            cambiarContador(counterButton);
            return;
        }

        var actionButton = event.target.closest('[data-action]');
        if (!actionButton) return;

        var wrapper = actionButton.closest('.tabla-wrapper');
        var tabla = wrapper ? wrapper.querySelector('table') : null;
        var action = actionButton.dataset.action;

        if (action === 'column-menu') {
            abrirMenuColumna(actionButton);
        } else if (action === 'delete-row' && tabla) {
            eliminarFila(actionButton.closest('tr'), tabla);
        } else if (action === 'add-row' && tabla) {
            agregarFila(tabla);
        } else if (action === 'add-col' && tabla) {
            agregarColumna(tabla);
        } else if (action === 'delete-table' && wrapper) {
            pedirEliminarTabla(wrapper);
        }
    });

    tablasContainer.addEventListener('input', function (event) {
        if (event.target.classList.contains('contador-input')) {
            validarContador(event.target);
        }
    });

    tablasContainer.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target.classList.contains('cell-content')) {
            event.preventDefault();
            event.target.blur();
        }
    });

    columnHighlight = montarHighlight(columnMenu, '[data-column-action]');
    menuGlobalHighlight = montarHighlight(menuGlobal, '.dropdown-item');

    /* ── Recuadro de la celda en edicion ──────────────────────────────────
       En vez de prender un borde por celda hay un solo recuadro por tabla
       que viaja hasta la celda enfocada, igual que el highlight del menu.
       Las celdas de contador quedan afuera: ahi el borde molesta. */
    tablasContainer.addEventListener('focusin', function (event) {
        var editable = event.target.closest('[contenteditable="true"]');
        var celda = editable ? editable.closest('td') : null;

        // Solo texto editable dentro de una celda del cuerpo: los titulos de
        // columna y de tabla, los botones y los contadores no llevan recuadro.
        if (!celda || celda.querySelector('.contador-wrapper')) {
            ocultarCeldaActiva();
            return;
        }

        moverCeldaActiva(celda);
    });

    tablasContainer.addEventListener('focusout', function () {
        window.setTimeout(function () {
            var activo = document.activeElement;
            if (!activo || !tablasContainer.contains(activo)) ocultarCeldaActiva();
        }, 0);
    });

    window.addEventListener('resize', ocultarCeldaActiva);

    crearNuevaTabla();
})();
</script>
<?= $this->endSection() ?>
