<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="auth-page">
    <section class="auth-panel">
        <div class="auth-card card">
            <?php if (session('error')): ?>
                <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
            <?php endif; ?>
            <section class="auth-pane is-active">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow">Zona de peligro</p>
                    <h1>Eliminar espacio</h1>
                    <p>Última confirmación para eliminar <?= esc($spaceName ?? 'este espacio') ?> con todo lo que tiene dentro.</p>
                </div>

                <form action="<?= base_url('espacios/eliminar/' . ($token ?? '')) ?>" method="post" class="auth-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="auth-submit auth-submit--danger">Eliminar definitivamente</button>
                </form>
            </section>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
