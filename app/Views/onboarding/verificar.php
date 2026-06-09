<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/onboarding.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="auth-page">
    <div class="auth-nebula" aria-hidden="true">
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
    </div>

    <section class="auth-panel">
        <div class="auth-card card onboarding-card onboarding-card--center">
            <div class="onboarding-check" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8 12.5l2.5 2.5L16 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <div class="auth-pane__header">
                <p class="auth-pane__eyebrow">Cuenta confirmada</p>
                <h1>Hola, <?= esc($userName) ?></h1>
                <p>Tu cuenta quedó lista. El último paso es crear tu espacio de industria para empezar a vincular tus ESP32 y sensores.</p>
            </div>

            <a class="auth-submit onboarding-cta" href="<?= base_url('crear-espacio') ?>">Crear mi espacio</a>

            <p class="auth-foot onboarding-foot">
                ¿Te invitaron a una industria existente?
                <a href="<?= base_url('unirse-espacio') ?>">Unirme a un espacio</a>
            </p>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
