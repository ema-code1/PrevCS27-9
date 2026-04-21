<?= $this->extend('templates/layout') ?>

<?= $this->section('content') ?>
<main class="page">
    <aside class="sidebar card card--dark" data-sidebar>
        <div class="sidebar__head">
            <h2 class="sidebar__title">Menu de accion</h2>
            <button class="sidebar__toggle" type="button" aria-label="Contraer menu" aria-expanded="true" data-sidebar-toggle>
                <span class="sidebar__toggle-icon">></span>
            </button>
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
        </nav>
    </aside>

    <section class="content-area">
        <?php if (session('isLoggedIn') === true): ?>
            <div class="page-actions">
                <a class="logout-link" href="<?= base_url('logout') ?>">Cerrar sesion</a>
            </div>
        <?php endif; ?>

        <div class="section-switch" role="tablist" aria-label="Secciones principales">
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
            <div class="hero-card card">
                <div class="hero-card__content">
                    <p class="hero-card__eyebrow">Vista general</p>
                    <h1>Todo el control preventivo, en una sola pantalla.</h1>
                    <p>Desde aca podes seguir el estado del sistema, revisar pendientes y abrir accesos rapidos a las tareas mas frecuentes.</p>
                </div>
                <div class="hero-card__stats">
                    <div class="metric-pill">
                        <strong>24</strong>
                        <span>Activos</span>
                    </div>
                    <div class="metric-pill">
                        <strong>07</strong>
                        <span>Alertas</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <section class="panel panel--small card">
                    <div class="panel__header">
                        <h3>Estado diario</h3>
                        <span class="badge badge--success">En ritmo</span>
                    </div>
                    <p class="panel__text">Las revisiones criticas del turno vienen alineadas y sin desfasajes.</p>
                </section>

                <section class="panel panel--wide card">
                    <div class="panel__header">
                        <h3>Seguimiento operativo</h3>
                        <span class="badge badge--info">Actualizado</span>
                    </div>
                    <div class="progress-strip">
                        <span style="width: 72%"></span>
                    </div>
                    <p class="panel__text">El avance semanal esta en 72%, con margen para completar los puntos abiertos antes del cierre.</p>
                </section>

                <section class="panel panel--full card">
                    <div class="panel__header">
                        <h3>Resumen ejecutivo</h3>
                        <span class="badge badge--warning">3 focos</span>
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
        sidebarToggle.addEventListener('click', () => {
            const isCollapsed = sidebar.classList.toggle('is-collapsed');
            sidebarToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        });
    }
});
</script>
<?= $this->endSection() ?>
