<?= $this->extend('templates/layout') ?>

<?= $this->section('content') ?>
<main class="page page--dashboard">
    <aside class="sidebar card card--dark" data-sidebar>
        <div class="sidebar__head">
            <h2 class="sidebar__title">Navegacion</h2>
            <label class="burger sidebar__toggle" for="burger">
                <input type="checkbox" id="burger" data-sidebar-toggle aria-label="Contraer menu lateral">
                <span></span>
                <span></span>
                <span></span>
            </label>
        </div>

        <nav class="sidebar__nav" aria-label="Menu principal">
            <a href="#" class="menu-link is-active">
                <span class="menu-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 11.5 12 5l8 6.5v7.5a1 1 0 0 1-1 1h-4.5v-5h-5v5H5a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="menu-link__label">Panel</span>
            </a>

            <a href="#" class="menu-link">
                <span class="menu-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 18V9m7 9V5m7 13v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="menu-link__label">Indicadores</span>
            </a>

            <a href="#" class="menu-link">
                <span class="menu-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="4.5" y="5" width="15" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="menu-link__label">Registros</span>
            </a>

            <a href="#" class="menu-link">
                <span class="menu-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 4.75a7.25 7.25 0 1 0 0 14.5 7.25 7.25 0 0 0 0-14.5Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 8.5v3.75l2.5 1.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="menu-link__label">Historial</span>
            </a>

            <a href="#" class="menu-link">
                <span class="menu-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 12h14M12 5v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="menu-link__label">Crear OT</span>
            </a>
        </nav>
    </aside>

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

        <?php if (session('isLoggedIn') === true): ?>
            <div class="page-actions">
                <span class="chip chip--success">Sesion activa</span>
            </div>
        <?php endif; ?>

        <div class="section-switch card" role="tablist" aria-label="Secciones principales">
            <button class="section-switch__option is-active" type="button" data-section-target="principal" aria-selected="true">
                Principal
            </button>
            <button class="section-switch__option" type="button" data-section-target="graficos" aria-selected="false">
                Graficos
            </button>
            <button class="section-switch__option" type="button" data-section-target="lista" aria-selected="false">
                Lista de elementos
            </button>
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
    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

    function activatePane(target) {
        panes.forEach((pane) => {
            pane.classList.toggle('is-active', pane.dataset.sectionPane === target);
        });

        switchers.forEach((button) => {
            const isActive = button.dataset.sectionTarget === target;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    switchers.forEach((button) => {
        button.addEventListener('click', () => activatePane(button.dataset.sectionTarget));
    });

    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('change', () => {
            sidebar.classList.toggle('is-collapsed', sidebarToggle.checked);
        });
    }
});
</script>
<?= $this->endSection() ?>
