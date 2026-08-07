<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/standalone.css') ?>?v=20260807-2">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
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

        <section class="standalone__empty">
            <div class="standalone__empty-inner">
                <span class="standalone__empty-icon" aria-hidden="true">
                    <!-- Heroicons outline · cube -->
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 7.5 12 12m0 0L3 7.5M12 12v9.75M21 7.5v9l-9 5.25L3 16.5v-9L12 2.25 21 7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <h2>Todavía no hay stock cargado</h2>
                <p>Cada espacio tiene su propia lista de stock. Cuando empieces a cargar items van a aparecer acá.</p>
            </div>
        </section>
    </section>
</main>
<?= $this->endSection() ?>
