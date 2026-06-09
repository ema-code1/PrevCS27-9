<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/verification-code.css') ?>?v=20260609-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="auth-page account-created-page">
    <section class="account-created">
        <div class="account-created__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="m7 12.5 3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1>Tu cuenta fue creada</h1>
        <p>Correo verificado. Ya podes crear tu espacio y empezar.</p>
        <a class="auth-submit account-created__button" href="<?= base_url('crear-espacio') ?>">Continuar</a>
        <span>Ya tenes acceso a las funciones de tu cuenta.</span>
    </section>
</main>
<?= $this->endSection() ?>
