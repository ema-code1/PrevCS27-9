<?= $this->extend('templates/layout') ?>

<?= $this->section('content') ?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Monitoreo de dispositivos</p>
                <h1>Registros IoT</h1>
                <p class="dashboard-head__text">Lecturas recientes de sensores y eventos conectados al sistema.</p>
            </div>
        </header>

        <section class="kpi-grid">
            <article class="kpi-card card">
                <p>DISPOSITIVOS</p>
                <strong>18</strong>
            </article>
            <article class="kpi-card card">
                <p>LECTURAS HOY</p>
                <strong>342</strong>
            </article>
            <article class="kpi-card card">
                <p>ALERTAS</p>
                <strong>4</strong>
            </article>
            <article class="kpi-card card">
                <p>ONLINE</p>
                <strong>16</strong>
            </article>
            <article class="kpi-card card">
                <p>ULTIMA SYNC</p>
                <strong>2m</strong>
            </article>
        </section>

        <section class="panel card list-card">
            <div class="panel__header">
                <h3>Ultimos registros</h3>
                <span class="badge badge--info">IoT</span>
            </div>

            <div class="list-table">
                <article class="list-row">
                    <span class="list-row__status is-ok"></span>
                    <strong>Sensor de temperatura</strong>
                    <span>Sector A</span>
                    <span>23.8 C</span>
                </article>
                <article class="list-row">
                    <span class="list-row__status is-warn"></span>
                    <strong>Valvula de presion</strong>
                    <span>Sector C</span>
                    <span>7.4 bar</span>
                </article>
                <article class="list-row">
                    <span class="list-row__status is-ok"></span>
                    <strong>Medidor de vibracion</strong>
                    <span>Linea 2</span>
                    <span>Normal</span>
                </article>
                <article class="list-row">
                    <span class="list-row__status is-alert"></span>
                    <strong>Sensor de humedad</strong>
                    <span>Deposito</span>
                    <span>Fuera de rango</span>
                </article>
            </div>
        </section>
    </section>
</main>
<?= $this->endSection() ?>
