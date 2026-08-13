<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/standalone.css') ?>?v=20260807-3">
<link rel="stylesheet" href="<?= base_url('css/stock.css') ?>?v=20260807-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$tables = $tables ?? [];

// Iconito de cabecera por tipo de columna (misma idea que las tablas de
// Notion: el tipo del campo se lee antes que el nombre).
$columnIcons = [
    'text'   => '<path d="M5 6h14M5 12h9M5 18h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    'number' => '<path d="M9 4 7 20M17 4l-2 16M4 9h16M3 15h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    'tag'    => '<path d="M3 12.5V4.5a1.5 1.5 0 0 1 1.5-1.5h8l8.5 8.5-9.5 9.5L3 12.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.3" fill="currentColor"/>',
    'chip'   => '<rect x="3" y="7" width="18" height="10" rx="3" stroke="currentColor" stroke-width="1.8"/>',
    'date'   => '<rect x="3.5" y="5" width="17" height="15" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M3.5 10h17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
];
?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area standalone standalone--embedded">
        <div class="standalone__bar">
            <a class="btn btn--secondary standalone__back" href="<?= base_url('registros-iot') ?>">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 5 7 10l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver
            </a>
        </div>

        <header class="standalone__head">
            <p class="standalone__eyebrow">Stock del espacio</p>
            <h1><?= esc($industry['company_name']) ?></h1>
            <p><?= esc($industry['space_name']) ?><?= ! empty($industry['sector']) ? ' - ' . esc($industry['sector']) : '' ?></p>
        </header>

        <div class="stock-layout">
            <!-- Card blanco: adentro va el contenedor de tablas. Puede haber
                 una o varias, pero la unidad es la empresa: una tabla junta
                 todo el stock de la empresa. -->
            <section class="card stock-card">
                <header class="stock-card__head">
                    <div>
                        <h2>Lista de stock</h2>
                        <p>Una tabla por empresa: acá se concentra todo el stock, sin importar desde qué espacio se haya cargado.</p>
                    </div>
                    <span class="stock-card__badge"><?= count($tables) ?> <?= count($tables) === 1 ? 'tabla' : 'tablas' ?></span>
                </header>

                <div class="stock-tables" data-stock-tables>
                    <?php foreach ($tables as $table): ?>
                        <?php $columns = $table['columns']; $rows = $table['rows']; ?>
                        <article class="stock-table-block" id="tabla-<?= esc($table['id'], 'attr') ?>">
                            <header class="stock-table-block__head">
                                <span class="stock-table-block__icon" aria-hidden="true">
                                    <!-- Heroicons outline · building-office -->
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M3.75 21h16.5M4.5 3h9.75v18H4.5V3Zm9.75 6H19.5v12h-5.25M7.5 6.75h3M7.5 10.5h3M7.5 14.25h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <h3><?= esc($table['title']) ?></h3>
                                <span class="stock-table-block__meta"><?= count($rows) ?> <?= count($rows) === 1 ? 'item' : 'items' ?></span>
                            </header>

                            <div class="stock-table-scroll">
                                <table class="stock-table">
                                    <thead>
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                                <th scope="col">
                                                    <span class="stock-table__th">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><?= $columnIcons[$column['icon']] ?? $columnIcons['text'] ?></svg>
                                                        <?= esc($column['label']) ?>
                                                    </span>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($rows === []): ?>
                                            <tr class="stock-table__empty">
                                                <td colspan="<?= count($columns) ?>">
                                                    <span class="stock-table__empty-inner">
                                                        <strong>Todavía no hay stock cargado</strong>
                                                        <span>Importá una planilla de Excel o agregá los items a mano y van a aparecer en esta tabla.</span>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($rows as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $cell): ?>
                                                        <?php if (is_array($cell) && isset($cell['chip'])): ?>
                                                            <td><span class="stock-chip stock-chip--<?= esc($cell['tone'] ?? 'ok', 'attr') ?>"><?= esc($cell['chip']) ?></span></td>
                                                        <?php else: ?>
                                                            <td<?= is_array($cell) && ! empty($cell['num']) ? ' class="stock-table__td--num"' : '' ?>><?= esc(is_array($cell) ? ($cell['text'] ?? '') : $cell) ?></td>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="stock-table__new">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                                Agregar item
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Importacion: la zona solo acepta planillas de Excel. -->
            <section class="card stock-upload" aria-labelledby="stock-upload-title">
                <div class="stock-upload__head">
                    <h2 id="stock-upload-title">Importar stock desde Excel</h2>
                    <p>Arrastrá la planilla con los items y se carga en la tabla de la empresa. Solo se aceptan archivos de Excel.</p>
                </div>

                <div class="dropzone" data-dropzone>
                    <input type="file"
                           id="stock-file"
                           class="dropzone__input"
                           accept=".xlsx,.xls,.xlsm,.xlsb,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/vnd.ms-excel.sheet.macroEnabled.12,application/vnd.ms-excel.sheet.binary.macroEnabled.12"
                           hidden
                           data-dropzone-input>

                    <!-- Boton carpeta: uiverse.io/simontheonlyone/happy-baboon-60 (MIT) -->
                    <button type="button" class="folder-btn" data-dropzone-trigger aria-label="Elegir una planilla de Excel">
                        <span class="folder-btn__box" aria-hidden="true">
                            <span class="folder-btn__part folder-btn__part--body"></span>
                            <span class="folder-btn__part folder-btn__part--tab"></span>
                            <span class="folder-btn__part folder-btn__part--sheet"></span>
                            <span class="folder-btn__part folder-btn__part--line"></span>
                        </span>
                        <span class="folder-btn__bar" aria-hidden="true"></span>
                        <span class="folder-btn__tip" aria-hidden="true">Buscar archivo</span>
                    </button>

                    <p class="dropzone__title">Arrastrá tu planilla acá</p>
                    <p class="dropzone__hint">o <span class="dropzone__pick">elegí un archivo</span> desde tu computadora</p>
                    <p class="dropzone__formats">.xlsx · .xls · .xlsm · .xlsb</p>
                </div>

                <div class="dropzone__file" data-dropzone-file hidden>
                    <span class="dropzone__file-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M14 3v5h5M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9.5 11.5 5 5m0-5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    </span>
                    <span class="dropzone__file-name" data-dropzone-file-name></span>
                    <span class="dropzone__file-size" data-dropzone-file-size></span>
                    <button type="button" class="dropzone__file-remove" data-dropzone-file-remove aria-label="Quitar el archivo">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </div>

                <p class="dropzone__error" data-dropzone-error role="alert" hidden>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7.5v5M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <span data-dropzone-error-text></span>
                </p>
            </section>
        </div>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260806-1" defer></script>
<script src="<?= base_url('js/stock.js') ?>?v=20260807-1" defer></script>
<?= $this->endSection() ?>
