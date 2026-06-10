<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/esp32.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Dispositivos vinculados</p>
                <h1>Mis ESP32</h1>
                <p class="dashboard-head__text">Placás ESP32 de las industrias a las que pertenecés.</p>
            </div>
            <div class="dashboard-head__meta">
                <a class="btn btn--primary" href="<?= base_url('esp32/agregar') ?>">Añadir ESP</a>
            </div>
        </header>

        <?php if (session('success')): ?>
            <div class="auth-alert auth-alert--success"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <?php if (! empty($newToken)): ?>
            <div class="esp32-token-banner card">
                <div>
                    <strong>Token de "<?= esc($newDevice) ?>"</strong>
                    <p>Guardá este token en el firmware de tu ESP32. Lo necesitás para enviar lecturas.</p>
                </div>
                <code class="esp32-token"><?= esc($newToken) ?></code>
            </div>
        <?php endif; ?>

        <?php if ($devices === []): ?>
            <section class="panel card esp32-empty">
                <h3>Todavía no hay ESP32</h3>
                <p>Vinculá tu primera placa para empezar a recibir lecturas IoT.</p>
                <a class="btn btn--primary" href="<?= base_url('esp32/agregar') ?>">Añadir ESP</a>
            </section>
        <?php else: ?>
            <section class="esp32-grid">
                <?php foreach ($devices as $d): ?>
                    <a class="esp32-card card" href="<?= base_url('esp32/' . $d['id']) ?>">
                        <div class="esp32-card__head">
                            <h3><?= esc($d['name']) ?></h3>
                            <span class="badge <?= ($d['status'] ?? '') === 'online' ? 'badge--success' : 'badge--info' ?>">
                                <span class="dot <?= ($d['status'] ?? '') === 'online' ? 'dot--live' : '' ?>"></span>
                                <?= esc($d['status'] ?? 'offline') ?>
                            </span>
                        </div>
                        <code class="esp32-card__mac"><?= ! empty($d['mac_address']) ? esc($d['mac_address']) : 'Sin vincular' ?></code>
                        <dl class="esp32-card__meta">
                            <div><dt>Industria</dt><dd><?= esc($d['company_name']) ?></dd></div>
                            <div><dt>Sector</dt><dd><?= esc($d['sector'] ?? '—') ?></dd></div>
                            <div><dt>Última lectura</dt><dd class="mono"><?= esc($d['last_seen_at'] ?? '—') ?></dd></div>
                        </dl>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>
