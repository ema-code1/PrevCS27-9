<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/standalone.css') ?>?v=20260807-3">
<link rel="stylesheet" href="<?= base_url('css/stock.css') ?>?v=20260820-3">
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
                        <span aria-hidden="true">...</span>
                    </button>

                    <div class="dropdown-menu" id="menu-global">
                        <button class="dropdown-item" id="btn-toggle-modo" type="button">Activar modo edicion</button>
                        <button class="dropdown-item" id="btn-nueva-tabla" type="button">Anadir nueva tabla</button>
                    </div>
                </div>
            </header>

            <div id="tablas-container" class="stock-tables-app"></div>

            <div class="drop-zone" id="drop-zone">
                <p>Solta un Excel o elegi archivo</p>
                <span>.xlsx y .xls</span>
                <input type="file" id="file-input" accept=".xlsx, .xls">
            </div>
        </section>

        <div class="column-menu" id="column-menu" hidden>
            <button type="button" data-column-action="properties">
                <span aria-hidden="true">⌘</span>
                Propiedades de Columna
            </button>
            <button type="button" data-column-action="rename">
                <span aria-hidden="true">⌕</span>
                Renombrar Columna
            </button>
            <button type="button" data-column-action="insert-left">
                <span aria-hidden="true">←</span>
                Insertar a la Izquierda
            </button>
            <button type="button" data-column-action="insert-right">
                <span aria-hidden="true">→</span>
                Insertar a la Derecha
            </button>
            <button type="button" data-column-action="delete" class="is-danger">
                <span aria-hidden="true">⌫</span>
                Eliminar Columna
            </button>
        </div>

        <div class="stock-modal-overlay" id="modal-overlay" hidden></div>
        <section class="stock-modal" id="prop-modal" role="dialog" aria-modal="true" aria-labelledby="prop-modal-title" hidden>
            <h2 id="prop-modal-title">Propiedades de columna</h2>

            <label class="stock-field" for="select-tipo-col">
                <span>Tipo</span>
                <select id="select-tipo-col">
                    <option value="texto">Texto</option>
                    <option value="contador">Contador</option>
                </select>
            </label>

            <label class="stock-field stock-field--counter" id="config-contador" for="input-unidad" hidden>
                <span>Unidad</span>
                <input type="text" id="input-unidad" value="unidades">
            </label>

            <div class="stock-modal__actions">
                <button class="stock-modal__btn stock-modal__btn--ghost" id="btn-cancelar-prop" type="button">Cancelar</button>
                <button class="stock-modal__btn stock-modal__btn--primary" id="btn-guardar-prop" type="button">Guardar</button>
            </div>
        </section>

        <section class="stock-confirm" id="stock-confirm" role="dialog" aria-modal="true" aria-labelledby="stock-confirm-title" hidden>
            <h2 id="stock-confirm-title">Eliminar tabla</h2>
            <p id="stock-confirm-message">Seguro que queres eliminar esta tabla?</p>
            <div class="stock-confirm__actions">
                <button class="stock-confirm__btn" id="btn-cancel-delete" type="button">Cancelar</button>
                <button class="stock-confirm__btn stock-confirm__btn--danger" id="btn-confirm-delete" type="button">Eliminar</button>
            </div>
        </section>

        <div class="stock-popups" id="stock-popups" aria-live="polite" aria-atomic="true"></div>
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
    var selectTipo = document.getElementById('select-tipo-col');
    var configContador = document.getElementById('config-contador');
    var inputUnidad = document.getElementById('input-unidad');
    var guardarProp = document.getElementById('btn-guardar-prop');
    var cancelarProp = document.getElementById('btn-cancelar-prop');
    var dropZone = document.getElementById('drop-zone');
    var fileInput = document.getElementById('file-input');
    var popupHost = document.getElementById('stock-popups');
    var columnMenu = document.getElementById('column-menu');
    var confirmDialog = document.getElementById('stock-confirm');
    var confirmMessage = document.getElementById('stock-confirm-message');
    var confirmDelete = document.getElementById('btn-confirm-delete');
    var cancelDelete = document.getElementById('btn-cancel-delete');
    var columnaMenuActiva = null;
    var tablaPendienteEliminar = null;

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

    function mostrarPopup(mensaje, tipo, accionTexto, accionCallback) {
        if (!popupHost) return;

        var popup = document.createElement('div');
        popup.className = 'stock-popup stock-popup--' + (tipo || 'info');
        popup.innerHTML = '<span>' + escapeHtml(mensaje) + '</span>';

        if (accionTexto && typeof accionCallback === 'function') {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = accionTexto;
            button.addEventListener('click', function () {
                accionCallback();
                popup.remove();
            });
            popup.appendChild(button);
        }

        popupHost.appendChild(popup);

        window.setTimeout(function () {
            if (!popup.isConnected) return;
            popup.classList.add('is-hiding');
            window.setTimeout(function () {
                popup.remove();
            }, 180);
        }, 2800);
    }

    function chevronSvg(direction) {
        var path = direction === 'up' ? 'M6 14l6-6 6 6' : 'M6 10l6 6 6-6';

        return '<svg class="contador-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
            '<path d="' + path + '" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>' +
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
                    '<button class="col-menu-btn" type="button" data-action="column-menu" aria-label="Menu de columna">&#8942;</button>' +
                '</div>' +
            '</th>';
    }

    function generarHtmlContador(unidad) {
        var label = unidad || 'unidades';

        return '' +
            '<div class="contador-wrapper">' +
                '<div class="contador-controls">' +
                    '<button class="contador-btn" type="button" data-counter-delta="1" aria-label="Sumar">' + chevronSvg('up') + '</button>' +
                    '<input type="number" inputmode="numeric" class="contador-input" value="0" min="0">' +
                    '<button class="contador-btn" type="button" data-counter-delta="-1" aria-label="Restar">' + chevronSvg('down') + '</button>' +
                '</div>' +
                '<span class="unidad-label" data-editable-unidad="true">' + escapeHtml(label) + '</span>' +
            '</div>';
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
                        '<svg viewBox="0 0 24 24" fill="none"><rect x="5" y="4" width="14" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 10h14M5 15h14M10 4v16M15 4v16" stroke="currentColor" stroke-width="1.8"/></svg>' +
                    '</span>' +
                    '<h2 class="tabla-titulo" data-editable="true">Tabla Interactiva ' + contadorTablas + '</h2>' +
                    '<span class="tabla-meta" data-table-meta></span>' +
                '</div>' +
                '<div class="tabla-actions">' +
                    '<span class="tabla-mode" data-table-mode>Modo lectura interactivo</span>' +
                    '<button class="btn-eliminar-tabla" type="button" data-action="delete-table">Eliminar</button>' +
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
                        '<svg viewBox="0 0 24 24" fill="none"><rect x="5" y="4" width="14" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 10h14M5 15h14M10 4v16M15 4v16" stroke="currentColor" stroke-width="1.8"/></svg>' +
                    '</span>' +
                    '<h2 class="tabla-titulo" data-editable="true">' + escapeHtml(nombreArchivo.replace(/\.[^/.]+$/, '')) + '</h2>' +
                    '<span class="tabla-meta" data-table-meta></span>' +
                '</div>' +
                '<div class="tabla-actions">' +
                    '<span class="tabla-mode" data-table-mode>Modo lectura interactivo</span>' +
                    '<button class="btn-eliminar-tabla" type="button" data-action="delete-table">Eliminar</button>' +
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

        if (dropZone) {
            dropZone.style.display = modoEdicion ? 'block' : 'none';
        }

        toggleModo.textContent = modoEdicion ? 'Salir de edicion' : 'Activar modo edicion';
        actualizarTodoMeta();
    }

    function agregarFila(tabla) {
        var headers = Array.prototype.slice.call(tabla.querySelectorAll('thead th'));
        var tr = document.createElement('tr');

        headers.forEach(function (th) {
            tr.insertAdjacentHTML('beforeend', generarTd(th.dataset.type, th.dataset.unit));
        });

        tabla.querySelector('tbody').appendChild(tr);
        actualizarAtributosEditables();
        actualizarMeta(tabla.closest('.tabla-wrapper'));
    }

    function agregarColumna(tabla) {
        var thCount = tabla.querySelectorAll('thead th').length + 1;
        tabla.querySelector('thead tr').insertAdjacentHTML('beforeend', generarTh('Columna ' + thCount, 'texto', ''));

        tabla.querySelectorAll('tbody tr').forEach(function (tr) {
            tr.insertAdjacentHTML('beforeend', generarTd('texto', ''));
        });

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

        headerRow.insertBefore(newTh, headerRow.children[index] || null);

        tabla.querySelectorAll('tbody tr').forEach(function (tr) {
            var newTd = crearNodoHtml(generarTd('texto', '', '', 'Insertar texto'));
            tr.insertBefore(newTd, tr.children[index] || null);
        });

        actualizarAtributosEditables();
        actualizarMeta(tabla.closest('.tabla-wrapper'));
    }

    function abrirMenuColumna(button) {
        columnaMenuActiva = button.closest('th');
        if (!columnaMenuActiva || !columnMenu) return;

        var rect = button.getBoundingClientRect();
        columnMenu.hidden = false;
        columnMenu.style.left = Math.min(rect.left - 126, window.innerWidth - 246) + 'px';
        columnMenu.style.top = rect.bottom + 8 + 'px';
    }

    function cerrarMenuColumna() {
        if (columnMenu) columnMenu.hidden = true;
        columnaMenuActiva = null;
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
            mostrarPopup('Debe quedar al menos una tabla', 'error');
            return;
        }

        tablaPendienteEliminar = wrapper;
        if (confirmMessage) confirmMessage.textContent = 'Seguro que queres eliminar esta tabla?';
        if (confirmDialog) confirmDialog.hidden = false;
        if (overlay) overlay.hidden = false;
    }

    function cerrarConfirmacion() {
        if (confirmDialog) confirmDialog.hidden = true;
        if (overlay) overlay.hidden = true;
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
    }

    function validarContador(input) {
        var value = parseInt(input.value, 10);
        if (isNaN(value) || value < 0) input.value = 0;
    }

    function abrirModalPropiedades(button) {
        colActiva = button.closest('th');
        if (!colActiva) return;

        var tipoActual = colActiva.dataset.type || 'texto';
        var unidadActual = colActiva.dataset.unit || 'unidades';

        selectTipo.value = tipoActual;
        inputUnidad.value = unidadActual;
        configContador.hidden = tipoActual !== 'contador';
        modal.hidden = false;
        overlay.hidden = false;
    }

    function cerrarModal() {
        modal.hidden = true;
        overlay.hidden = true;
        colActiva = null;
    }

    function guardarPropiedades() {
        if (!colActiva) return;

        var nuevoTipo = selectTipo.value;
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
    }

    menuButton.addEventListener('click', function (event) {
        event.stopPropagation();
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

    selectTipo.addEventListener('change', function () {
        configContador.hidden = selectTipo.value !== 'contador';
    });

    guardarProp.addEventListener('click', guardarPropiedades);
    cancelarProp.addEventListener('click', cerrarModal);
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

    crearNuevaTabla();
})();
</script>
<?= $this->endSection() ?>
