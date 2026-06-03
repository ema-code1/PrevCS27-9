<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/registros-iot.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
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

        <?php if ($industries === []): ?>
            <section class="panel card esp32-empty">
                <h3>Todavía no tenés espacios</h3>
                <p>Creá un espacio para tu planta o máquina y asociá una ESP32 para empezar a ver registros.</p>
                <a href="<?= base_url('crear-espacio') ?>" class="btn btn--primary">Crear espacio</a>
            </section>
        <?php else: ?>
            <div class="esp32-grid">
                <?php foreach ($industries as $ind): ?>
                    <article class="esp32-card card">
                        <div class="esp32-card__head">
                            <h3><?= esc($ind['space_name']) ?></h3>
                            <span class="badge <?= $ind['role'] === 'admin_principal' ? 'badge--success' : 'badge--info' ?>">
                                <?= $ind['role'] === 'admin_principal' ? 'Admin' : 'Supervisor' ?>
                            </span>
                        </div>
                        <p class="esp32-card__sub"><?= esc($ind['company_name']) ?><?= !empty($ind['sector']) ? ' · ' . esc($ind['sector']) : '' ?></p>
                        <p class="text-muted mono"><?= (int) $ind['device_count'] ?> ESP32 · <?= esc($ind['industry_uid']) ?></p>
                        <div class="esp32-card__actions">
                            <a href="<?= base_url('registros-iot/' . $ind['id']) ?>" class="btn btn--primary">Registros IoT</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>
