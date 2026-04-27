<?= $this->extend('templates/layout') ?>

<?= $this->section('content') ?>
<main class="page page--dashboard">
    <?= $this->include('templates/sidebar') ?>

    <section class="content-area">
        <header class="dashboard-head card">
            <div>
                <p class="dashboard-head__eyebrow">Sistema de gestion de mantenimiento</p>
                <h1>Dashboard</h1>
                <p class="dashboard-head__text">Resumen general de planta para decision operativa en tiempo real.</p>
            </div>
            <?php if (session('isLoggedIn') === true): ?>
                <a class="logout-link" href="<?= base_url('logout') ?>">Cerrar sesion</a>
            <?php endif; ?>
        </header>

        <section class="kpi-grid">
            <article class="kpi-card card">
                <p>TOTAL ELEMENTOS</p>
                <strong>128</strong>
            </article>
            <article class="kpi-card card">
                <p>MTTO. VENCIDO</p>
                <strong>8</strong>
            </article>
            <article class="kpi-card card">
                <p>PROXIMOS MTTO.</p>
                <strong>21</strong>
            </article>
            <article class="kpi-card card">
                <p>ALERTAS IOT</p>
                <strong>4</strong>
            </article>
            <article class="kpi-card card">
                <p>GASTO TOTAL MTTO.</p>
                <strong>$65.0k</strong>
            </article>
        </section>

        <div class="section-switch card" role="tablist" aria-label="Secciones principales">
            <label class="section-switch__radio" role="tab" aria-selected="true">
                <input type="radio" name="section-switch" value="principal" data-section-target="principal" checked>
                <span class="section-switch__name">Principal</span>
            </label>
            <label class="section-switch__radio" role="tab" aria-selected="false">
                <input type="radio" name="section-switch" value="graficos" data-section-target="graficos">
                <span class="section-switch__name">Gráficos</span>
            </label>
            <label class="section-switch__radio" role="tab" aria-selected="false">
                <input type="radio" name="section-switch" value="lista" data-section-target="lista">
                <span class="section-switch__name">Lista de elementos</span>
            </label>
        </div>

        <section class="section-pane is-active" data-section-pane="principal">
            <div class="dashboard-grid">
                <section class="panel panel--urgencias card">
                    <div class="panel__header">
                        <h3>Urgencias</h3>
                        <span class="badge badge--error">8 vencidos</span>
                    </div>
                    <div class="event-list">
                        <article class="event-item"><span class="dot dot--error"></span><strong>Prensa Hidraulica</strong><small>MAQ-001</small><span class="chip chip--danger">Vencido</span></article>
                        <article class="event-item"><span class="dot dot--error"></span><strong>Centrifuga secundaria</strong><small>MAQ-002</small><span class="chip chip--danger">Vencido</span></article>
                        <article class="event-item"><span class="dot dot--error"></span><strong>Caldera Industrial</strong><small>MAQ-003</small><span class="chip chip--danger">Vencido</span></article>
                        <article class="event-item"><span class="dot dot--error"></span><strong>Taladro banco</strong><small>HER-881</small><span class="chip chip--danger">Vencido</span></article>
                    </div>
                </section>

                <section class="panel panel--actividad card">
                    <div class="panel__header">
                        <h3>Actividad reciente</h3>
                        <span class="badge badge--info">Actualizado</span>
                    </div>
                    <div class="event-list event-list--activity">
                        <article class="event-item"><span class="dot dot--error"></span><strong>Reemplazo de rodamiento</strong><small>Centrifuga 2024-12-18</small><span>$2800</span></article>
                        <article class="event-item"><span class="dot dot--error"></span><strong>Falla en sistema hidraulico</strong><small>Autoelevador 2024-11-28</small><span>$5500</span></article>
                        <article class="event-item"><span class="dot dot--success"></span><strong>Cambio de aceite</strong><small>Prensa principal 2024-11-15</small><span>$1200</span></article>
                        <article class="event-item"><span class="dot dot--success"></span><strong>Limpieza de tubos</strong><small>Caldera industrial 2024-10-01</small><span>$2200</span></article>
                    </div>
                </section>

                <section class="panel panel--gastos card">
                    <div class="panel__header">
                        <h3>Gasto por categoria</h3>
                        <span class="badge badge--warning">Mensual</span>
                    </div>
                    <div class="bar-group">
                        <div class="bar-row"><span>Maquinaria</span><div class="bar"><i style="width: 88%"></i></div></div>
                        <div class="bar-row"><span>Herramientas</span><div class="bar"><i style="width: 45%"></i></div></div>
                        <div class="bar-row"><span>Transporte</span><div class="bar"><i style="width: 56%"></i></div></div>
                        <div class="bar-row"><span>Insumos</span><div class="bar"><i style="width: 32%"></i></div></div>
                    </div>
                </section>

                <section class="panel panel--full card">
                    <div class="panel__header">
                        <h3>Resumen ejecutivo</h3>
                        <span class="badge badge--success">Operacion estable</span>
                    </div>
                    <div class="summary-grid">
                        <article class="summary-item">
                            <strong>Equipo</strong>
                            <p>Checklists cargados y consistentes en la mayoria de sectores.</p>
                        </article>
                        <article class="summary-item">
                            <strong>Mantenimiento</strong>
                            <p>Dos controles quedan pendientes de validacion final.</p>
                        </article>
                        <article class="summary-item">
                            <strong>Trazabilidad</strong>
                            <p>Documentacion al dia con una sola observacion menor.</p>
                        </article>
                    </div>
                </section>
            </div>
        </section>

        <section class="section-pane" data-section-pane="graficos">
            <div class="analytics-grid">
                <section class="panel card chart-card chart-card--large">
                    <div class="panel__header">
                        <h3>Tendencia semanal</h3>
                        <span class="badge badge--info">7 dias</span>
                    </div>
                    <div class="chart-lines" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="chart-area" aria-hidden="true">
                        <span class="chart-area__fill"></span>
                    </div>
                </section>

                <section class="panel card chart-card">
                    <div class="panel__header">
                        <h3>Distribucion</h3>
                        <span class="badge badge--success">Balanceado</span>
                    </div>
                    <div class="donut-chart" aria-hidden="true"></div>
                </section>
            </div>
        </section>

        <section class="section-pane" data-section-pane="lista">
            <section class="panel card list-card">
                <div class="panel__header">
                    <h3>Lista de elementos</h3>
                    <span class="badge badge--info">12 items</span>
                </div>

                <div class="list-table">
                    <article class="list-row">
                        <span class="list-row__status is-ok"></span>
                        <strong>Sensor de temperatura</strong>
                        <span>Sector A</span>
                        <span>Hoy</span>
                    </article>
                    <article class="list-row">
                        <span class="list-row__status is-warn"></span>
                        <strong>Valvula de presion</strong>
                        <span>Sector C</span>
                        <span>Manana</span>
                    </article>
                    <article class="list-row">
                        <span class="list-row__status is-ok"></span>
                        <strong>Panel electrico</strong>
                        <span>Sector B</span>
                        <span>Hoy</span>
                    </article>
                    <article class="list-row">
                        <span class="list-row__status is-alert"></span>
                        <strong>Registro de auditoria</strong>
                        <span>Administracion</span>
                        <span>Pendiente</span>
                    </article>
                </div>
            </section>
        </section>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const switchers = document.querySelectorAll('[data-section-target]');
    const panes = document.querySelectorAll('[data-section-pane]');
    let paneTransitionTimer = null;

    function updateSwitchState(target) {
        switchers.forEach((input) => {
            const isActive = input.dataset.sectionTarget === target;
            input.checked = isActive;
            input.closest('.section-switch__radio')?.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function activatePane(target) {
        const nextPane = Array.from(panes).find((pane) => pane.dataset.sectionPane === target);
        const activePane = Array.from(panes).find((pane) => pane.classList.contains('is-active'));

        updateSwitchState(target);

        if (!nextPane || activePane === nextPane) {
            return;
        }

        if (paneTransitionTimer) {
            clearTimeout(paneTransitionTimer);
            panes.forEach((pane) => pane.classList.remove('is-exiting'));
        }

        if (!activePane) {
            nextPane.classList.add('is-active');
            return;
        }

        activePane.classList.add('is-exiting');

        paneTransitionTimer = window.setTimeout(() => {
            activePane.classList.remove('is-active', 'is-exiting');
            nextPane.classList.add('is-active');
            paneTransitionTimer = null;
        }, 150);
    }

    switchers.forEach((input) => {
        input.addEventListener('change', () => activatePane(input.dataset.sectionTarget));
    });
});
</script>
<?= $this->endSection() ?>
