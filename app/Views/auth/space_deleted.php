<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="auth-page">
    <section class="auth-panel">
        <div class="auth-card card">
            <section class="auth-pane is-active">
                <div class="auth-pane__header">
                    <p class="auth-pane__eyebrow">Listo</p>
                    <h1>El espacio ha sido eliminado</h1>
                    <p>Ya puedes cerrar la página.</p>
                </div>
            </section>
        </div>
    </section>
</main>
<script>
try {
    localStorage.setItem('prevcs.space.deleted', String(<?= (int) ($industryId ?? 0) ?>));
} catch (error) {}
</script>
<?= $this->endSection() ?>
