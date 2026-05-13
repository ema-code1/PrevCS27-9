<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<style>
.landing-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
    min-height: calc(100vh - var(--header-height));
    display: flex;
    flex-direction: column;
    gap: 4rem;
}

.hero {
    text-align: center;
    padding: 6rem 2rem;
    border-radius: var(--radius-xl);
    background: linear-gradient(135deg, var(--bg-card), var(--bg-tab));
    box-shadow: var(--card-shadow);
    border: var(--border-thin) solid var(--border-color);
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(158, 204, 83, 0.05) 0%, transparent 60%);
    pointer-events: none;
}

.hero h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    margin-bottom: 1.25rem;
    color: var(--text-primary);
    font-weight: var(--font-weight-bold);
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.hero h1 span {
    background: linear-gradient(130deg, rgba(230, 242, 120, 0.88) 0%, rgba(158, 204, 83, 0.9) 32%, rgba(71, 128, 49, 0.95) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero p {
    font-size: 1.25rem;
    color: var(--text-muted);
    max-width: 650px;
    margin: 0 auto 2.5rem;
    line-height: 1.6;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem 2.5rem;
    font-size: 1.1rem;
    font-weight: var(--font-weight-semibold);
    background: var(--primary);
    color: #ffffff;
    border-radius: var(--radius-md);
    text-decoration: none;
    transition: background-color var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast);
    box-shadow: 0 4px 14px rgba(74, 96, 45, 0.3);
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 96, 45, 0.4);
    color: #ffffff;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
}

.feature-card {
    padding: 3rem 2.5rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    gap: 1.5rem;
    transition: transform var(--transition-normal), box-shadow var(--transition-normal);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-shadow-hover);
}

.feature-icon {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: var(--radius-md);
    background: linear-gradient(135deg, rgba(158, 204, 83, 0.15), rgba(74, 96, 45, 0.1));
    color: var(--primary);
    display: grid;
    place-items: center;
    font-size: 2.2rem;
    box-shadow: inset 0 0 0 1px rgba(158, 204, 83, 0.2);
}

.feature-card h3 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--text-primary);
    font-weight: var(--font-weight-bold);
}

.feature-card p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.7;
}

.call-to-action {
    text-align: center;
    padding: 5rem 2rem;
    background: var(--bg-card);
    border: var(--border-thin) solid var(--border-color);
    box-shadow: var(--card-shadow);
    border-radius: var(--radius-xl);
}

.call-to-action h2 {
    font-size: 2.5rem;
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-weight: var(--font-weight-bold);
}

.call-to-action p {
    font-size: 1.15rem;
    color: var(--text-muted);
    margin-bottom: 2.5rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="landing-container">
    <section class="hero">
        <h1>Control de Mantenimiento<br><span>Inteligente e Intuitivo</span></h1>
        <p>Una plataforma colaborativa estilo ClickUp diseñada para industrias y empresas. Gestiona el mantenimiento preventivo con plantillas dinámicas, gráficos, checklists y más.</p>
        <a href="<?= base_url('login') ?>" class="btn-primary">
            Empezar ahora
            <span class="material-symbols-rounded">arrow_forward</span>
        </a>
    </section>

    <section class="features-grid">
        <article class="feature-card card">
            <div class="feature-icon">
                <span class="material-symbols-rounded">bar_chart</span>
            </div>
            <h3>Gráficos Dinámicos</h3>
            <p>Visualiza el rendimiento y los gastos de mantenimiento en tiempo real con dashboards interactivos y reportes ejecutivos para la toma de decisiones.</p>
        </article>

        <article class="feature-card card">
            <div class="feature-icon">
                <span class="material-symbols-rounded">space_dashboard</span>
            </div>
            <h3>Cuadros de Mando</h3>
            <p>Organiza tus máquinas, equipos y herramientas en vistas personalizables para tener un control visual absoluto de toda tu operación industrial.</p>
        </article>

        <article class="feature-card card">
            <div class="feature-icon">
                <span class="material-symbols-rounded">checklist</span>
            </div>
            <h3>Todo Lists y Checkboxes</h3>
            <p>Crea listas de verificación y rutinas de mantenimiento con checklists fáciles de usar para estandarizar el trabajo de tus técnicos en campo.</p>
        </article>
    </section>

    <section class="call-to-action card">
        <h2>¿Listo para optimizar tu empresa?</h2>
        <p>Únete a industrias líderes que ya previenen fallas, reducen costos operativos y mejoran la eficiencia de su mantenimiento diario.</p>
        <a href="<?= base_url('reg-log') ?>" class="btn-primary">
            Crear cuenta gratis
        </a>
    </section>
</main>
<?= $this->endSection() ?>
