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
                <p class="dashboard-head__eyebrow">Tus empresas y plantas</p>
                <h1>Mis espacios</h1>
                <p class="dashboard-head__text">Cada espacio representa una empresa. Adentro vinculás una o más ESP32, agrupadas por sector.</p>
            </div>
            <div class="dashboard-head__meta">
                <span class="badge badge--info"><?= count($industries) ?> espacio<?= count($industries) === 1 ? '' : 's' ?></span>
                <a href="<?= base_url('crear-espacio') ?>" class="btn btn--primary">+ Crear espacio</a>
            </div>
        </header>

        <?php if (session('error')): ?>
            <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <?php if ($industries === []): ?>
            <section class="panel card esp32-empty esp32-empty--centered">
                <h3>Creá tu primer espacio</h3>
                <p>Un espacio representa una empresa o planta que gestionás. Es el primer paso: después vinculás tus ESP32 adentro.</p>
                <div class="esp32-empty__actions">
                    <a href="<?= base_url('crear-espacio') ?>" class="btn btn--primary">Crear espacio</a>
                    <a href="<?= base_url('unirse-espacio') ?>" class="btn btn--secondary">Unirme a uno existente</a>
                </div>
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
                        <p class="esp32-card__company">
                            <span class="esp32-card__company-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" width="15" height="15"><rect x="4" y="3" width="11" height="18" rx="1.4" stroke="currentColor" stroke-width="1.7"/><path d="M15 8h5v13h-5" stroke="currentColor" stroke-width="1.7"/><path d="M7 7h4M7 11h4M7 15h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            </span>
                            <?= esc($ind['company_name']) ?>
                        </p>
                        <p class="text-muted mono">
                            <?= !empty($ind['sector']) ? esc($ind['sector']) . ' · ' : '' ?><?= (int) $ind['device_count'] ?> ESP32 · <?= esc($ind['industry_uid']) ?>
                        </p>
                        <div class="esp32-card__actions">
                            <a href="<?= base_url('registros-iot/' . $ind['id']) ?>" class="btn btn--primary">Entrar al espacio</a>
                            <?php if ($ind['role'] === 'admin_principal'): ?>
                                <a href="<?= base_url('esp32/agregar?industry=' . $ind['id']) ?>" class="btn btn--secondary">+ Añadir ESP32</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>
