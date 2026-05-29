<?= $this->extend('templates/layout') ?>

<?php
// Portfolio reuses the landing's custom header; hide the default app topbar.
$showHeader = false;

/**
 * Inline line-icon helper — keeps the markup DRY and consistent with the
 * existing stroke style (currentColor, 1.6 stroke). Usage: <?= $icon('leaf') ?>
 */
$icon = function (string $name, string $cls = 'pf-ico'): string {
    $p = [
        'target'    => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1.3" fill="currentColor" stroke="none"/>',
        'scale'     => '<path d="M12 4v16M5 20h14"/><path d="M12 6 4.5 9 7 14h-5z" transform="translate(0 0)"/><path d="M3 9h6l-2.5 5L4 14"/><path d="M15 9h6l-2.5 5"/><path d="M3.2 14a2.8 2.8 0 0 0 5.6 0M15.2 14a2.8 2.8 0 0 0 5.6 0"/>',
        'check'     => '<path d="M4.5 12.5 9.5 17.5 19.5 6.5"/>',
        'spark'     => '<path d="M12 3v5M12 16v5M3 12h5M16 12h5M6.3 6.3l3 3M14.7 14.7l3 3M17.7 6.3l-3 3M9.3 14.7l-3 3"/>',
        'leaf'      => '<path d="M5 19C5 11 11 5 20 5c0 9-6 15-15 15z"/><path d="M9 15c2-4 5-6 8.5-7"/>',
        'chart'     => '<path d="M5 4v15a1 1 0 0 0 1 1h15"/><path d="M9 16v-4M13.5 16V9M18 16V7"/>',
        'clipboard' => '<path d="M9 4.5h6v3H9z"/><path d="M15 6h3v14H6V6h3"/><path d="M9.5 12h5M9.5 15.5h3.5"/>',
        'bulb'      => '<path d="M9.2 14.5a5.5 5.5 0 1 1 5.6 0c-.8.6-1.1 1.2-1.1 2.5h-3.4c0-1.3-.3-1.9-1.1-2.5z"/><path d="M10 20h4M10.5 17.5h3"/>',
        'shield'    => '<path d="M12 3 5 6v6c0 4.5 3 7 7 9 4-2 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'bolt'      => '<path d="M13 3 5.5 13H11l-1 8 8.5-11H13z"/>',
        'recycle'   => '<path d="M9 7 7 4 4 9l3 .5"/><path d="m16.5 8 1.5 3 3-1.5"/><path d="m13 19 3 1 1.5-4.7"/><path d="M7 9.5 4 9l1 6 4-1"/><path d="m11 4 5 .5-.5 5"/><path d="M20 11.5 21 17l-5.5 1"/>',
        'trend'     => '<path d="M4 16 9.5 10.5 13 14l7-8"/><path d="M15 6h5v5"/>',
        'layers'    => '<path d="M12 3 3.5 7.5 12 12l8.5-4.5z"/><path d="M3.5 13 12 17.5 20.5 13"/>',
        'cpu'       => '<rect x="8" y="8" width="8" height="8" rx="1.2"/><path d="M10 3.5v3M14 3.5v3M10 17.5v3M14 17.5v3M3.5 10h3M3.5 14h3M17.5 10h3M17.5 14h3"/>',
        'money'     => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5h.01M18 14.5h.01"/>',
        'gauge'     => '<path d="M4.5 18a8 8 0 1 1 15 0"/><path d="M12 14.5 16 9"/><circle cx="12" cy="15" r="1.2" fill="currentColor" stroke="none"/>',
        'bell'      => '<path d="M6 16.5V11a6 6 0 1 1 12 0v5.5l1.8 1.8H4.2z"/><path d="M9.7 20.5a2.5 2.5 0 0 0 4.6 0"/>',
        'clock'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/>',
        'users'     => '<circle cx="9" cy="9" r="3.2"/><path d="M3.2 19.5c.6-3.2 3-5 5.8-5s5.2 1.8 5.8 5"/><path d="M16 6.2a3.2 3.2 0 0 1 0 6.1M17.2 14.6c2.2.3 3.9 2 4.3 4.9"/>',
        'pin'       => '<path d="M12 21s7-6.4 7-12a7 7 0 1 0-14 0c0 5.6 7 12 7 12z"/><circle cx="12" cy="9" r="2.3"/>',
        'route'     => '<circle cx="6" cy="18" r="2.4"/><circle cx="18" cy="6" r="2.4"/><path d="M8.4 18H14a4 4 0 0 0 0-8H9a4 4 0 0 1 0-8h.6" transform="translate(0 0)"/>',
        'eye'       => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'lock'      => '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/><circle cx="12" cy="15" r="1.1" fill="currentColor" stroke="none"/>',
        'db'        => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v12c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12c0 1.7 3.1 3 7 3s7-1.3 7-3"/>',
        'arrow'     => '<path d="M4 12h15M13 6l6 6-6 6"/>',
    ][$name] ?? '';
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
};
?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/landing.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/portfolio.css') ?>?v=20260529-8">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ───────────────────────────── HEADER (mismo que landing.php) ───────────────────── -->
<header class="landing-header" data-screen-label="00 Header">
  <div class="hd-inner">
    <a class="hd-logo" href="<?= base_url() ?>#top" aria-label="PrevCS, inicio">
      <span class="hd-logo-mark" aria-hidden="true">
        <span class="hd-logo-dot"></span>
        <span class="hd-logo-dot"></span>
        <span class="hd-logo-dot"></span>
        <span class="hd-logo-dot live"></span>
      </span>
      <span>PrevCS</span>
    </a>

    <nav class="hd-nav" aria-label="Principal">
      <a href="<?= base_url() ?>#funcionalidades">Funcionalidades</a>
      <a href="<?= base_url() ?>#como-funciona">Cómo funciona</a>
      <a href="<?= base_url() ?>#metricas">Métricas</a>
      <a href="<?= base_url() ?>#sensores">Sensores</a>
      <a href="<?= base_url() ?>#plantillas">Plantillas</a>
      <a href="<?= base_url() ?>#casos">Casos de uso</a>

      <div class="hd-portfolio hd-portfolio--current" data-portfolio-menu>
        <a class="hd-portfolio-trigger" href="<?= base_url('portfolio') ?>" aria-haspopup="true" aria-expanded="false">
          Portfolio
          <svg class="hd-portfolio-caret" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true">
            <path d="M2 3.6 5 6.6 8 3.6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>

        <div class="hd-portfolio-mega" role="menu" aria-label="Portfolio">
          <div class="hpm-grid">
            <div class="hpm-col">
              <span class="hpm-col-label mono">Identidad</span>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#pagina-principal" role="menuitem" data-section-link="pagina-principal">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Página principal</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#quienes-somos" role="menuitem" data-section-link="quienes-somos">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="9" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="17" cy="10" r="2.4" stroke="currentColor" stroke-width="1.5"/><path d="M3 19c1-3 3.5-4.5 6-4.5s5 1.5 6 4.5M14.5 19c.8-2.2 2.5-3.5 4.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Quiénes somos</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#acerca-de-prevcs" role="menuitem" data-section-link="acerca-de-prevcs">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Acerca de PrevCS</strong></span>
              </a>
            </div>

            <div class="hpm-col">
              <span class="hpm-col-label mono">Estrategia</span>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#imagen-corporativa" role="menuitem" data-section-link="imagen-corporativa">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 1 9-9c0 2.5-2 3-4 3h-2a2 2 0 0 0-2 2c0 1.5 1 2-1 4z" stroke="currentColor" stroke-width="1.5"/><circle cx="7.5" cy="11" r="1" fill="currentColor"/><circle cx="11" cy="7.5" r="1" fill="currentColor"/><circle cx="15" cy="8" r="1" fill="currentColor"/><circle cx="17.5" cy="12" r="1" fill="currentColor"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Imagen corporativa</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#plan-operativo" role="menuitem" data-section-link="plan-operativo">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 9h18M8 13h5M8 16h7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Plan Operativo</strong></span>
              </a>
            </div>

            <div class="hpm-col">
              <span class="hpm-col-label mono">Investigación</span>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#analisis-mercado" role="menuitem" data-section-link="analisis-mercado">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M4 19V5M4 19h16M8 15v-4M12 15V9M16 15v-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Análisis de mercado</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#analisis-competencia" role="menuitem" data-section-link="analisis-competencia">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="m9 9 6 1.5L13.5 16 7 14.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Análisis de la competencia</strong></span>
              </a>
            </div>
          </div>

        </div>
      </div>

      <a href="<?= base_url() ?>#contacto">Contacto</a>
    </nav>

    <div class="hd-cta">
      <a class="l-btn l-btn--ghost" href="<?= base_url('login') ?>">Iniciar sesión</a>
      <a class="l-btn l-btn--solid" href="<?= base_url('register') ?>">
        Crear cuenta
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <label class="theme-switch theme-switch--compact" aria-label="Cambiar tema">
        <input type="checkbox" class="theme-switch__checkbox" data-theme-toggle-input>
        <div class="theme-switch__container">
          <div class="theme-switch__clouds"></div>
          <div class="theme-switch__stars-container"></div>
          <div class="theme-switch__circle-container">
            <div class="theme-switch__sun-moon-container">
              <div class="theme-switch__moon">
                <div class="theme-switch__spot"></div>
                <div class="theme-switch__spot"></div>
                <div class="theme-switch__spot"></div>
              </div>
            </div>
          </div>
        </div>
      </label>
    </div>
  </div>
</header>

<main id="top" class="pf-main">

  <div class="pf-stage">

    <!-- ============================================================= -->
    <!-- 01 · PAGINA PRINCIPAL                                          -->
    <!-- ============================================================= -->
    <section id="pagina-principal" class="pf-section pf-section--hero" data-screen-label="01 Página principal">
      <div class="pf-hero-bg" aria-hidden="true">
        <div class="pf-hero-grid"></div>
        <div class="pf-hero-glow pf-hero-glow-1"></div>
        <div class="pf-hero-glow pf-hero-glow-2"></div>
      </div>

      <div class="pf-hero-layout">
        <header class="pf-hero-head">
          <span class="pf-tag mono">
            <span class="pf-tag-dot"></span>
            Portfolio · 7º Año "B" · Instituto Técnico Río Tercero
          </span>
          <h1 class="pf-hero-title">
            Portfolio de Emprendimientos
            <span class="pf-hero-serif">desarrollado por etapas durante el ciclo lectivo 2026.</span>
          </h1>

          <div class="pf-hero-actions">
            <a class="l-btn l-btn--line" href="#acerca-de-prevcs">Conocé PrevCS</a>
            <a class="pf-hero-scroll mono" href="#plan-operativo">
              Deslizá para recorrer <?= $icon('arrow', 'pf-ico pf-ico--sm') ?>
            </a>
          </div>
        </header>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 02 · PLAN OPERATIVO                                            -->
    <!-- ============================================================= -->
    <section id="plan-operativo" class="pf-section" data-screen-label="02 Plan operativo">
      <span class="pf-gutter-index mono" data-parallax="30" aria-hidden="true">02</span>
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono" data-reveal="left"><?= $icon('route', 'pf-ico pf-ico--sm') ?> 02 · Plan operativo</span>
        <h2 class="pf-section-title" data-reveal>
          Cómo vamos a operar el emprendimiento
          <span class="pf-muted">paso a paso, con tiempos y responsables.</span>
        </h2>
        <p class="pf-section-lede" data-reveal>
          Apartado preparado para completarse cuando lo trabajemos en clase. Por ahora se mantiene
          la estructura con bloques para objetivos, recursos, etapas, responsables y tiempos.
        </p>
        <span class="pf-status pf-status--todo" data-reveal>
          <span class="pf-status-dot"></span> Pendiente — se completará más adelante
        </span>
      </header>

      <div class="pf-steps" role="list">
        <div class="pf-steps-track" aria-hidden="true">
          <div class="pf-steps-line"></div>
        </div>

        <article class="pf-step" role="listitem" data-plan="objetivos" data-reveal>
          <span class="pf-step-num mono">01</span>
          <div class="pf-step-body">
            <h3><?= $icon('target', 'pf-ico pf-ico--head') ?> Objetivos</h3>
            <p class="pf-placeholder-text">Reservado para los objetivos generales y específicos del emprendimiento.</p>
            <ul class="pf-bullet-list pf-placeholder">
              <li>Objetivo general — <em>placeholder</em></li>
              <li>Objetivos específicos — <em>placeholder</em></li>
              <li>Resultados esperados — <em>placeholder</em></li>
            </ul>
          </div>
        </article>

        <article class="pf-step" role="listitem" data-plan="recursos" data-reveal>
          <span class="pf-step-num mono">02</span>
          <div class="pf-step-body">
            <h3><?= $icon('layers', 'pf-ico pf-ico--head') ?> Recursos</h3>
            <p class="pf-placeholder-text">Tecnológicos, humanos, materiales y financieros que necesita el proyecto.</p>
            <ul class="pf-bullet-list pf-placeholder">
              <li>Hardware (sensores, gateways) — <em>placeholder</em></li>
              <li>Software (CodeIgniter, MySQL, Chart.js) — <em>placeholder</em></li>
              <li>Equipo de trabajo — <em>placeholder</em></li>
            </ul>
          </div>
        </article>

        <article class="pf-step" role="listitem" data-plan="etapas" data-reveal>
          <span class="pf-step-num mono">03</span>
          <div class="pf-step-body">
            <h3><?= $icon('route', 'pf-ico pf-ico--head') ?> Etapas</h3>
            <p class="pf-placeholder-text">Hoja de ruta dividida en fases medibles, con hitos por cada etapa.</p>
            <ol class="pf-timeline pf-placeholder">
              <li><span class="pf-tl-dot"></span><b>Fase 1.</b> Investigación y validación</li>
              <li><span class="pf-tl-dot"></span><b>Fase 2.</b> Prototipo funcional</li>
              <li><span class="pf-tl-dot"></span><b>Fase 3.</b> Pilotaje con cliente</li>
              <li><span class="pf-tl-dot"></span><b>Fase 4.</b> Lanzamiento</li>
            </ol>
          </div>
        </article>

        <article class="pf-step" role="listitem" data-plan="responsables" data-reveal>
          <span class="pf-step-num mono">04</span>
          <div class="pf-step-body">
            <h3><?= $icon('users', 'pf-ico pf-ico--head') ?> Responsables</h3>
            <p class="pf-placeholder-text">Quién lidera cada área y cómo se distribuyen las tareas del equipo.</p>
            <ul class="pf-bullet-list pf-placeholder">
              <li>Líder del proyecto — <em>placeholder</em></li>
              <li>Responsable técnico — <em>placeholder</em></li>
              <li>Responsable comercial — <em>placeholder</em></li>
            </ul>
          </div>
        </article>

        <article class="pf-step" role="listitem" data-plan="tiempos" data-reveal>
          <span class="pf-step-num mono">05</span>
          <div class="pf-step-body">
            <h3><?= $icon('clock', 'pf-ico pf-ico--head') ?> Tiempos</h3>
            <p class="pf-placeholder-text">Cronograma estimado por fase. Se completará con fechas concretas.</p>
            <div class="pf-gantt pf-placeholder" aria-hidden="true">
              <div class="pf-gantt-row"><span>Fase 1</span><span class="pf-gantt-bar" style="--from:0; --to:25;"></span></div>
              <div class="pf-gantt-row"><span>Fase 2</span><span class="pf-gantt-bar" style="--from:20; --to:55;"></span></div>
              <div class="pf-gantt-row"><span>Fase 3</span><span class="pf-gantt-bar" style="--from:50; --to:80;"></span></div>
              <div class="pf-gantt-row"><span>Fase 4</span><span class="pf-gantt-bar" style="--from:75; --to:100;"></span></div>
            </div>
            <div class="pf-gantt-scale mono"><span>inicio</span><span>medio</span><span>cierre</span></div>
          </div>
        </article>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 03 · IMAGEN CORPORATIVA                                        -->
    <!-- ============================================================= -->
    <section id="imagen-corporativa" class="pf-section" data-screen-label="03 Imagen corporativa">
      <span class="pf-gutter-index mono" data-parallax="30" aria-hidden="true">03</span>
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono" data-reveal="left"><?= $icon('spark', 'pf-ico pf-ico--sm') ?> 03 · Imagen corporativa</span>
        <h2 class="pf-section-title" data-reveal>
          La identidad visual del proyecto.
          <span class="pf-muted">Marca, paleta y aplicaciones.</span>
        </h2>
        <p class="pf-section-lede" data-reveal>
          Apartado preparado con la identidad actual derivada de la plataforma. Cuando definamos
          la versión final del manual de marca, los bloques se reemplazan con los assets oficiales.
        </p>
        <span class="pf-status pf-status--partial" data-reveal>
          <span class="pf-status-dot"></span> Parcial — derivado del proyecto, pendiente manual oficial
        </span>
      </header>

      <div class="pf-brand-grid">
        <article class="pf-brand-card pf-brand-card--logo" data-reveal>
          <span class="pf-brand-label mono">logo</span>
          <div class="pf-brand-logo">
            <span class="hd-logo-mark" aria-hidden="true">
              <span class="hd-logo-dot"></span><span class="hd-logo-dot"></span>
              <span class="hd-logo-dot"></span><span class="hd-logo-dot live"></span>
            </span>
            <span class="pf-brand-logo-text">PrevCS</span>
          </div>
          <p>Marca de la plataforma. Cuatro puntos como sensores activos, uno en estado <em>live</em>.</p>
          <div class="pf-brand-mark-variants">
            <span class="pf-brand-variant pf-brand-variant--light">claro</span>
            <span class="pf-brand-variant pf-brand-variant--dark">oscuro</span>
            <span class="pf-brand-variant pf-brand-variant--mono">mono</span>
          </div>
        </article>

        <article class="pf-brand-card pf-brand-card--palette" data-reveal>
          <span class="pf-brand-label mono">paleta</span>
          <h4>Colores principales</h4>
          <ul class="pf-palette">
            <li>
              <span class="pf-swatch" style="background: var(--accent);"></span>
              <span class="pf-swatch-label"><b>Moss</b><span class="mono">accent</span></span>
            </li>
            <li>
              <span class="pf-swatch" style="background: var(--bg);"></span>
              <span class="pf-swatch-label"><b>Ivory</b><span class="mono">background</span></span>
            </li>
            <li>
              <span class="pf-swatch" style="background: var(--ink);"></span>
              <span class="pf-swatch-label"><b>Ink</b><span class="mono">primary text</span></span>
            </li>
            <li>
              <span class="pf-swatch" style="background: var(--warning);"></span>
              <span class="pf-swatch-label"><b>Amber</b><span class="mono">warning</span></span>
            </li>
            <li>
              <span class="pf-swatch" style="background: var(--success);"></span>
              <span class="pf-swatch-label"><b>Live</b><span class="mono">success</span></span>
            </li>
            <li>
              <span class="pf-swatch" style="background: var(--error);"></span>
              <span class="pf-swatch-label"><b>Coral</b><span class="mono">error</span></span>
            </li>
          </ul>
        </article>

        <article class="pf-brand-card pf-brand-card--type" data-reveal>
          <span class="pf-brand-label mono">tipografía</span>
          <h4>Sistema tipográfico</h4>
          <div class="pf-type-stack">
            <div class="pf-type-row"><b style="font-family: var(--font-sans); font-size: 22px; letter-spacing: -0.03em;">Geist Sans</b><span class="mono">interfaz y contenidos</span></div>
            <div class="pf-type-row"><b class="mono" style="font-size: 18px;">Geist Mono</b><span class="mono">datos, etiquetas y meta</span></div>
            <div class="pf-type-row"><b class="serif" style="font-size: 24px;">Instrument Serif</b><span class="mono">énfasis editorial</span></div>
          </div>
        </article>

        <article class="pf-brand-card pf-brand-card--voice" data-reveal>
          <span class="pf-brand-label mono">voz</span>
          <h4>Tono de comunicación</h4>
          <ul class="pf-bullet-list">
            <li><b>Claro.</b> Sin jerga innecesaria. La industria ya tiene suficiente.</li>
            <li><b>Concreto.</b> Datos sobre adjetivos. Promesas verificables.</li>
            <li><b>Cercano.</b> Hablamos con el operario, no por encima del operario.</li>
          </ul>
        </article>

        <article class="pf-brand-card pf-brand-card--apps" data-reveal>
          <span class="pf-brand-label mono">aplicaciones</span>
          <h4>Aplicaciones de marca</h4>
          <p class="pf-placeholder-text">Espacio reservado para mockups de aplicaciones: banner, QR para Expo Demostrativa 2026, tarjeta, papelería, social.</p>
          <div class="pf-apps-row">
            <span class="pf-app-tile"><span class="mono">banner</span></span>
            <span class="pf-app-tile"><span class="mono">QR expo</span></span>
            <span class="pf-app-tile"><span class="mono">tarjeta</span></span>
            <span class="pf-app-tile"><span class="mono">social</span></span>
          </div>
        </article>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 04 · QUIENES SOMOS                                             -->
    <!-- ============================================================= -->
    <section id="quienes-somos" class="pf-section" data-screen-label="04 Quiénes somos">
      <span class="pf-gutter-index mono" data-parallax="30" aria-hidden="true">04</span>
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono" data-reveal="left"><?= $icon('users', 'pf-ico pf-ico--sm') ?> 04 · Quiénes somos</span>
        <h2 class="pf-section-title" data-reveal>
          El equipo detrás del proyecto.
          <span class="pf-muted">Foto, rol y trayectoria.</span>
        </h2>
        <p class="pf-section-lede" data-reveal>
          Espacios reservados para los integrantes del grupo de tesina. La descripción definitiva se
          carga cuando trabajemos este apartado parcialmente en clase.
        </p>
        <span class="pf-status pf-status--partial" data-reveal>
          <span class="pf-status-dot"></span> Parcial — estructura lista, fotos y bios pendientes
        </span>
      </header>

      <div class="pf-team-grid">
        <article class="pf-member-card" data-reveal>
          <div class="pf-member-photo" aria-hidden="true">
            <span class="pf-member-initials">JM</span>
            <span class="pf-member-photo-tag mono">foto</span>
          </div>
          <div class="pf-member-body">
            <span class="mono pf-member-role">integrante 01</span>
            <h3 class="pf-member-name">José Mangold</h3>
            <p class="pf-member-desc">Desarrollador full-stack del proyecto. Lidera la implementación técnica de PrevCS.</p>
            <ul class="pf-member-tags mono">
              <li>backend</li><li>frontend</li><li>IoT</li>
            </ul>
          </div>
        </article>

        <article class="pf-member-card pf-member-card--placeholder" data-reveal>
          <div class="pf-member-photo" aria-hidden="true">
            <span class="pf-member-initials">??</span>
            <span class="pf-member-photo-tag mono">foto</span>
          </div>
          <div class="pf-member-body">
            <span class="mono pf-member-role">integrante 02</span>
            <h3 class="pf-member-name">Compañero/a de tesina</h3>
            <p class="pf-member-desc pf-placeholder-text">Espacio reservado para nombre, rol y breve presentación del compañero de tesina.</p>
            <ul class="pf-member-tags mono">
              <li>rol</li><li>area</li><li>aporte</li>
            </ul>
          </div>
        </article>

        <article class="pf-member-card pf-member-card--mentor" data-reveal>
          <div class="pf-member-photo pf-member-photo--mentor" aria-hidden="true">
            <span class="pf-member-initials">MD</span>
            <span class="pf-member-photo-tag mono">foto</span>
          </div>
          <div class="pf-member-body">
            <span class="mono pf-member-role">profesora · materia Emprendimientos</span>
            <h3 class="pf-member-name">Mayra Dell'Oso</h3>
            <p class="pf-member-desc">Acompaña y evalúa el desarrollo del portfolio durante el ciclo lectivo 2026.</p>
            <ul class="pf-member-tags mono">
              <li>guía</li><li>evaluación</li><li>tutoría</li>
            </ul>
          </div>
        </article>
      </div>

      <div class="pf-team-foot" data-reveal>
        <span class="mono">7º Año "B"</span>
        <span class="mono">· Instituto Técnico Río Tercero ·</span>
        <span class="mono">ciclo 2026</span>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 05 · ACERCA DE PREVCS                                          -->
    <!-- ============================================================= -->
    <section id="acerca-de-prevcs" class="pf-section" data-screen-label="05 Acerca de PrevCS">

      <div class="pf-acerca-layout">
        <!-- Left: sticky section head (stays in view while body scrolls) -->
        <div class="pf-section-head pf-acerca-head">
          <span class="pf-section-eyebrow mono" data-reveal="left"><?= $icon('bulb', 'pf-ico pf-ico--sm') ?> 05 · Acerca de PrevCS</span>
          <h2 class="pf-section-title" data-reveal>
            Qué es PrevCS y a quién va dirigido.
            <span class="pf-muted">Propósito, problema y propuesta de valor.</span>
          </h2>
          <span class="pf-status pf-status--partial" data-reveal>
            <span class="pf-status-dot"></span> Parcial — completado con lo que sabemos hoy
          </span>
        </div>

        <!-- Right: scrollable body -->
        <div class="pf-about-body">

          <!-- Lead statement (open, no card) -->
          <div class="pf-about-lead">
            <p class="pf-editorial-lead" data-reveal>
              Una plataforma que entiende la planta antes que los sensores.
            </p>
            <p data-reveal>
              PrevCS guía paso a paso: workspace por empresa, elección de métricas (temperatura,
              vibración, energía, producción, stock, mantenimiento), recomendación de sensores compatibles,
              armado de dashboards en vivo y configuración de alertas accionables.
            </p>
          </div>

          <!-- Editorial items (open layout, no card chrome) -->
          <div class="pf-about-items">
            <div class="pf-about-item" data-reveal>
              <span class="mono pf-about-tag"><?= $icon('target', 'pf-ico pf-ico--sm') ?> propósito</span>
              <h4>Para qué existe</h4>
              <p>
                Reducir las paradas no planificadas y las decisiones tomadas "por instinto"
                en industria pyme. Llevar la operación industrial de planillas dispersas a una
                sola pantalla en tiempo real.
              </p>
            </div>

            <div class="pf-about-item" data-reveal>
              <span class="mono pf-about-tag"><?= $icon('bell', 'pf-ico pf-ico--sm') ?> problema</span>
              <h4>Qué resuelve</h4>
              <ul class="pf-feature-list">
                <li><?= $icon('layers') ?><span>Datos dispersos entre cuadernos, planillas y PLCs sueltos.</span></li>
                <li><?= $icon('bell') ?><span>Mantenimiento reactivo (se repara cuando ya rompió).</span></li>
                <li><?= $icon('cpu') ?><span>Sensores comprados por catálogo sin saber qué miden.</span></li>
                <li><?= $icon('eye') ?><span>Dashboards lindos pero sin lectura accionable.</span></li>
              </ul>
            </div>

            <div class="pf-about-item pf-about-item--full" data-reveal>
              <span class="mono pf-about-tag"><?= $icon('check', 'pf-ico pf-ico--sm') ?> valor</span>
              <h4>Propuesta de valor</h4>
              <ul class="pf-feature-list pf-feature-list--2col">
                <li><?= $icon('gauge') ?><span><b>Métricas primero.</b> Decidís qué querés saber; los sensores vienen después.</span></li>
                <li><?= $icon('bulb') ?><span><b>Recomendaciones inteligentes.</b> PrevCS sugiere set de sensores y plantillas.</span></li>
                <li><?= $icon('layers') ?><span><b>Workspace propio.</b> Configurado a la empresa, no un panel genérico.</span></li>
                <li><?= $icon('bell') ?><span><b>Alertas y predictivo.</b> Por umbral, tendencia o correlación entre métricas.</span></li>
              </ul>
            </div>
          </div>
        </div><!-- /.pf-about-body -->
      </div><!-- /.pf-acerca-layout -->
    </section>

    <!-- ============================================================= -->
    <!-- 06 · ANALISIS DE LA COMPETENCIA                                -->
    <!-- ============================================================= -->
    <section id="analisis-competencia" class="pf-section" data-screen-label="06 Análisis competencia">
      <span class="pf-gutter-index mono" data-parallax="30" aria-hidden="true">06</span>
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono" data-reveal="left"><?= $icon('scale', 'pf-ico pf-ico--sm') ?> 06 · Análisis de la competencia</span>
        <h2 class="pf-section-title" data-reveal>
          Quiénes más están resolviendo este problema
          <span class="pf-muted">y cómo nos diferenciamos.</span>
        </h2>
      </header>

      <!-- 6.1 · Introduccion editorial (texto abierto, sin imagen) -->
      <div class="pf-editorial pf-editorial--wide" data-reveal>
        <span class="pf-sub-eyebrow mono"><?= $icon('pin', 'pf-ico pf-ico--sm') ?> 6.1 · contexto</span>
        <span class="pf-editorial-rule" aria-hidden="true"></span>
        <p class="pf-editorial-lead">
          En el marco del desarrollo de nuestra tesina <b>"PrevCS — Sistema Inteligente de
          Gestión de Mantenimiento Industrial"</b>, mapeamos las plataformas que compiten en
          mantenimiento, monitoreo IoT y administración de activos.
        </p>
        <p>
          El objetivo es comprender cómo funcionan las soluciones que hoy se usan en la industria,
          identificar fortalezas y limitaciones, y establecer con claridad las ventajas competitivas
          de nuestro proyecto frente a sistemas comerciales ya existentes.
        </p>
        <p>
          PrevCS busca una solución moderna, accesible e integrada para industrias y plantas
          productivas — gestiona maquinaria, herramientas e insumos mediante mantenimiento preventivo,
          control de stock, análisis de datos y monitoreo en tiempo real con dispositivos IoT como ESP32.
        </p>
      </div>

      <!-- 6.2 · Cuadro comparativo (responsive con overflow horizontal) -->
      <div class="pf-block" data-reveal>
        <span class="pf-sub-eyebrow mono"><?= $icon('scale', 'pf-ico pf-ico--sm') ?> 6.2 · cuadro comparativo</span>
        <h3 class="pf-block-title">PrevCS frente al mercado, criterio por criterio.</h3>
        <span class="pf-comp-hint mono" aria-hidden="true">
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M10 4l4 4-4 4M6 12l-4-4 4-4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Deslizá para ver toda la tabla
        </span>
        <div class="pf-comp-table-wrap" data-comp-scroll>
          <table class="pf-comp-table">
            <thead>
              <tr>
                <th>Característica</th>
                <th>MaintainX</th>
                <th>Fracttal One</th>
                <th>Oracle NetSuite</th>
                <th>PrevCS</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><b>Localización</b></td>
                <td>Estados Unidos</td>
                <td>Chile</td>
                <td>Estados Unidos</td>
                <td class="pf-comp-self-cell"><b>Argentina</b></td>
              </tr>
              <tr>
                <td><b>Productos y servicios</b></td>
                <td>Gestión de mantenimiento y órdenes de trabajo</td>
                <td>CMMS + IoT + análisis industrial</td>
                <td>ERP empresarial con mantenimiento y stock</td>
                <td class="pf-comp-self-cell"><b>Gestión de mantenimiento, stock e IoT industrial</b></td>
              </tr>
              <tr>
                <td><b>Precio de venta</b></td>
                <td>Suscripción mensual</td>
                <td>Planes empresariales pagos</td>
                <td>Alto costo corporativo</td>
                <td class="pf-comp-self-cell"><b>Bajo costo y adaptable</b></td>
              </tr>
              <tr>
                <td><b>Medios de pago</b></td>
                <td>Tarjeta / suscripción online</td>
                <td>Facturación empresarial</td>
                <td>Contratos corporativos</td>
                <td class="pf-comp-self-cell"><b>Flexible y adaptable</b></td>
              </tr>
              <tr>
                <td><b>Materia prima / infraestructura</b></td>
                <td>Software cloud</td>
                <td>Cloud + IoT</td>
                <td>Infraestructura ERP cloud</td>
                <td class="pf-comp-self-cell"><b>Web + ESP32 + MySQL</b></td>
              </tr>
              <tr>
                <td><b>Marca</b></td>
                <td>Reconocida en mantenimiento industrial</td>
                <td>Fuerte presencia en LATAM</td>
                <td>Marca corporativa global</td>
                <td class="pf-comp-self-cell"><b>Proyecto innovador escolar-industrial</b></td>
              </tr>
              <tr>
                <td><b>Tecnología</b></td>
                <td>Cloud y automatización</td>
                <td>IoT, IA y monitoreo</td>
                <td>ERP integral empresarial</td>
                <td class="pf-comp-self-cell"><b>MVC + MySQL + ESP32 + IoT</b></td>
              </tr>
              <tr>
                <td><b>Embalaje</b></td>
                <td>Servicio digital</td>
                <td>Servicio digital</td>
                <td>Servicio digital</td>
                <td class="pf-comp-self-cell"><b>Servicio digital</b></td>
              </tr>
              <tr>
                <td><b>Clientes principales</b></td>
                <td>Empresas industriales y técnicas</td>
                <td>Industrias latinoamericanas</td>
                <td>Grandes empresas y corporaciones</td>
                <td class="pf-comp-self-cell"><b>PyMEs e industrias locales</b></td>
              </tr>
              <tr>
                <td><b>Atención al cliente</b></td>
                <td>Soporte online</td>
                <td>Soporte técnico empresarial</td>
                <td>Soporte corporativo</td>
                <td class="pf-comp-self-cell"><b>Atención personalizada</b></td>
              </tr>
              <tr>
                <td><b>Cumplimiento de entrega</b></td>
                <td>Alto</td>
                <td>Alto</td>
                <td>Alto</td>
                <td class="pf-comp-self-cell"><b>Desarrollo adaptable y rápido</b></td>
              </tr>
              <tr>
                <td><b>Publicidad</b></td>
                <td>Marketing digital</td>
                <td>Publicidad corporativa</td>
                <td>Publicidad empresarial global</td>
                <td class="pf-comp-self-cell"><b>Difusión académica y local</b></td>
              </tr>
              <tr>
                <td><b>Puntos fuertes</b></td>
                <td>Facilidad de uso</td>
                <td>Integración IoT</td>
                <td>Gran integración empresarial</td>
                <td class="pf-comp-self-cell"><b>Bajo costo + personalización</b></td>
              </tr>
              <tr>
                <td><b>Puntos débiles</b></td>
                <td>Funciones limitadas en versiones básicas</td>
                <td>Complejidad técnica</td>
                <td>Muy costoso y complejo</td>
                <td class="pf-comp-self-cell"><b>Proyecto en desarrollo</b></td>
              </tr>
              <tr>
                <td><b>Materiales reciclables</b></td>
                <td>No aplica</td>
                <td>No aplica</td>
                <td>No aplica</td>
                <td class="pf-comp-self-cell"><b>Uso eficiente de hardware</b></td>
              </tr>
              <tr>
                <td><b>Bajo consumo energético</b></td>
                <td>Dependencia cloud</td>
                <td>Requiere infraestructura</td>
                <td>Alto consumo empresarial</td>
                <td class="pf-comp-self-cell"><b>ESP32 de bajo consumo</b></td>
              </tr>
              <tr>
                <td><b>Reducción de residuos</b></td>
                <td>Parcial</td>
                <td>Parcial</td>
                <td>Parcial</td>
                <td class="pf-comp-self-cell"><b>Reduce mantenimiento innecesario</b></td>
              </tr>
              <tr>
                <td><b>Packaging ecológico</b></td>
                <td>No aplica</td>
                <td>No aplica</td>
                <td>No aplica</td>
                <td class="pf-comp-self-cell">No aplica</td>
              </tr>
              <tr>
                <td><b>Reparación o reutilización</b></td>
                <td>Limitada</td>
                <td>Media</td>
                <td>Baja</td>
                <td class="pf-comp-self-cell"><b>Favorece mantenimiento preventivo</b></td>
              </tr>
              <tr>
                <td><b>Impacto ambiental</b></td>
                <td>Moderado</td>
                <td>Moderado</td>
                <td>Alto consumo de recursos</td>
                <td class="pf-comp-self-cell"><b>Menor impacto operativo</b></td>
              </tr>
              <tr>
                <td><b>Ventaja sustentable frente a PrevCS</b></td>
                <td>Mayor estabilidad</td>
                <td>Mayor automatización</td>
                <td>Mayor capacidad empresarial</td>
                <td class="pf-comp-self-cell"><b>Menor costo y menor consumo</b></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 6.3 · Pull-quote: que hace distinto a PrevCS -->
      <div class="pf-pullquote" data-reveal>
        <span class="pf-pullquote-mark" aria-hidden="true">&ldquo;</span>
        <div class="pf-pullquote-body">
          <q>
            PrevCS integra en una sola plataforma lo que la competencia ofrece por separado —
            mantenimiento, stock y monitoreo IoT — pensado para industrias medianas y pequeñas
            que necesitan una solución funcional sin costos extremadamente elevados.
          </q>
          <span class="pf-pullquote-meta">6.3 · diferencias frente a la competencia</span>
        </div>
      </div>

      <!-- 6.4 / 6.5 / 6.6 · Adopción, ventajas y beneficios -->
      <div class="pf-adopt-stack" data-adopt-stack>
        <article class="pf-adopt-card" data-reveal="right">
          <span class="pf-adopt-num mono">6.4</span>
          <span class="pf-sub-eyebrow mono"><?= $icon('check', 'pf-ico pf-ico--sm') ?> razones de adopción</span>
          <h4>Por qué elegirían PrevCS</h4>
          <p>
            Frente a plataformas con herramientas avanzadas pero que requieren infraestructura
            compleja, capacitación especializada y altos costos mensuales, los clientes optarían
            por PrevCS por seis razones concretas:
          </p>
          <ul class="pf-feature-list">
            <li><?= $icon('money') ?><span>Menor costo de implementación.</span></li>
            <li><?= $icon('bulb') ?><span>Sistema más simple e intuitivo.</span></li>
            <li><?= $icon('layers') ?><span>Posibilidad de personalización.</span></li>
            <li><?= $icon('cpu') ?><span>Integración con sensores IoT.</span></li>
            <li><?= $icon('gauge') ?><span>Control centralizado de maquinaria y stock.</span></li>
            <li><?= $icon('shield') ?><span>Adaptación a plantas industriales locales.</span></li>
          </ul>
        </article>

        <article class="pf-adopt-card" data-reveal="right">
          <span class="pf-adopt-num mono">6.5</span>
          <span class="pf-sub-eyebrow mono"><?= $icon('check', 'pf-ico pf-ico--sm') ?> ventajas competitivas</span>
          <h4>Principales ventajas</h4>
          <ul class="pf-feature-list">
            <li><?= $icon('layers') ?><span>Integración de mantenimiento + stock + IoT.</span></li>
            <li><?= $icon('money') ?><span>Bajo costo operativo.</span></li>
            <li><?= $icon('spark') ?><span>Interfaz moderna y visual.</span></li>
            <li><?= $icon('shield') ?><span>Sistema adaptable a cada planta.</span></li>
            <li><?= $icon('gauge') ?><span>Monitoreo en tiempo real.</span></li>
            <li><?= $icon('bell') ?><span>Alertas automáticas preventivas.</span></li>
            <li><?= $icon('pin') ?><span>Orientación al sector industrial local.</span></li>
          </ul>
        </article>

        <article class="pf-adopt-card" data-reveal="right">
          <span class="pf-adopt-num mono">6.6</span>
          <span class="pf-sub-eyebrow mono"><?= $icon('spark', 'pf-ico pf-ico--sm') ?> beneficios concretos</span>
          <h4>Lo que cambia en la planta</h4>
          <ul class="pf-feature-list">
            <li><?= $icon('check') ?><span>Seguimiento automático de mantenimientos.</span></li>
            <li><?= $icon('shield') ?><span>Reducción de fallas inesperadas.</span></li>
            <li><?= $icon('layers') ?><span>Control eficiente de insumos.</span></li>
            <li><?= $icon('db') ?><span>Historial centralizado de intervenciones.</span></li>
            <li><?= $icon('bell') ?><span>Alertas inteligentes mediante sensores.</span></li>
            <li><?= $icon('route') ?><span>Mejor organización operativa.</span></li>
            <li><?= $icon('clock') ?><span>Reducción de tiempos muertos en planta.</span></li>
          </ul>
        </article>

      </div>

      <!-- 6.7 · Sustentabilidad — full-bleed callout (rompe el ancho del main) -->
      <aside class="pf-sustain-callout" data-reveal>
        <div class="pf-sustain-callout-inner">
          <div>
            <span class="pf-sustain-callout-eyebrow"><?= $icon('leaf', 'pf-ico pf-ico--sm') ?> 6.7 · sustentabilidad</span>
            <h3>La sustentabilidad como diferencial competitivo.</h3>
            <p>
              Al digitalizar procesos y registros, PrevCS reduce el uso de papel y documentación
              física dentro de la planta — y suma una capa de eficiencia energética que la
              competencia trata como funcionalidad opcional.
            </p>
          </div>
          <ul class="pf-feature-list">
            <li><?= $icon('bolt') ?><span>Dispositivos ESP32 de bajo consumo energético.</span></li>
            <li><?= $icon('recycle') ?><span>Reduce desperdicios mediante mantenimiento preventivo.</span></li>
            <li><?= $icon('shield') ?><span>Disminuye reemplazos innecesarios de maquinaria.</span></li>
            <li><?= $icon('check') ?><span>Favorece la reutilización y reparación de componentes.</span></li>
            <li><?= $icon('gauge') ?><span>Optimiza recursos industriales.</span></li>
            <li><?= $icon('leaf') ?><span>Reduce fallas y consumo excesivo de materiales.</span></li>
          </ul>
        </div>
      </aside>

      <!-- 6.8 · Conclusion final — texto editorial de cierre -->
      <div class="pf-editorial pf-editorial--wide pf-editorial--center" data-reveal>
        <span class="pf-sub-eyebrow mono"><?= $icon('check', 'pf-ico pf-ico--sm') ?> 6.8 · conclusión</span>
        <span class="pf-editorial-rule" aria-hidden="true"></span>
        <p class="pf-editorial-lead">
          PrevCS posee características competitivas sólidas frente a las plataformas del mercado actual.
        </p>
        <p>
          Si bien <b>MaintainX</b>, <b>Fracttal One</b> y <b>Oracle NetSuite</b> cuentan con amplia
          trayectoria e infraestructura empresarial, PrevCS busca destacarse con una propuesta más
          accesible, adaptable y orientada a las necesidades reales de industrias locales y PyMEs.
        </p>
        <p>
          La integración entre mantenimiento industrial, gestión de stock y monitoreo IoT en tiempo
          real permite ofrecer una solución moderna, eficiente y sustentable, capaz de optimizar
          recursos y mejorar el control operativo dentro de una planta industrial.
        </p>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 07 · ANALISIS DE MERCADO  (PRIORITARIO — TP Nº 2)             -->
    <!-- ============================================================= -->
    <section id="analisis-mercado" class="pf-section pf-section--focus" data-screen-label="07 Análisis de mercado">
      <span class="pf-gutter-index mono" data-parallax="30" aria-hidden="true">07</span>
      <header class="pf-section-head pf-section-head--focus">
        <span class="pf-section-eyebrow mono" data-reveal="left"><?= $icon('chart', 'pf-ico pf-ico--sm') ?> 07 · Análisis de mercado</span>
        <h2 class="pf-section-title" data-reveal>
          Análisis de mercado.
          <span class="pf-muted">Encuesta, gráficos y reflexión sobre sustentabilidad.</span>
        </h2>
        <p class="pf-section-lede" data-reveal>
          Investigación de mercado a usuarios y consumidores potenciales del producto,
          con foco en validar las decisiones del proyecto.
        </p>
      </header>

      <!-- 7.1 · Objetivo de la encuesta (texto abierto, sin card) -->
      <div class="pf-block" data-reveal>
        <span class="pf-sub-eyebrow mono"><?= $icon('target', 'pf-ico pf-ico--sm') ?> 7.1 · objetivo de la encuesta</span>
        <h3 class="pf-block-title">Qué queremos averiguar.</h3>
        <p class="pf-block-lede">
          Queremos saber qué necesita el usuario, qué funciones le sirven más y si PrevCS se
          entiende como una solución útil para la planta.
        </p>
      </div>

      <!-- ───────── GOOGLE FORMS EMBED ─────────────────────────────
           El iframe del formulario vive dentro de .pf-form-device.
           Para reemplazarlo por otro formulario, cambiar el `src`
           del <iframe> conservando ?embedded=true.
      ────────────────────────────────────────────────────────────── -->
      <div class="pf-formblock" data-reveal>
        <div class="pf-formblock-aside">
          <span class="mono pf-form-eyebrow"><?= $icon('clipboard', 'pf-ico pf-ico--sm') ?> cuestionario</span>
          <h4 class="pf-formblock-title">Cuestionario PrevCS</h4>
          <p class="pf-formblock-desc">
            Nos ayudas a saber más sobre lo que te hace falta.
          </p>
          <div class="pf-form-actions">
            <a class="l-btn l-btn--solid" href="https://docs.google.com/forms/d/e/1FAIpQLSe8SOVPspfusSJ0TT7QcdSGnLbcb2MOWjdHmdk4HOlrMVANHA/viewform" target="_blank" rel="noopener">
              Abrir en pestaña nueva
              <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M7 3H3v10h10V9M9 2h5v5M14 2 8 8" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <button class="l-btn l-btn--line" type="button" data-copy-form-link aria-label="Copiar link del formulario">
              <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="5" y="5" width="9" height="9" rx="1.6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M11 5V3a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h2" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
              <span data-copy-label>Copiar link</span>
            </button>
          </div>
        </div>

        <div class="pf-form-device">
          <div class="pf-form-device-bar mono" aria-hidden="true">
            <span class="pf-form-dots"><i></i><i></i><i></i></span>
            <span class="pf-form-url">docs.google.com/forms</span>
            <span class="pf-form-secure"><?= $icon('lock', 'pf-ico pf-ico--sm') ?></span>
          </div>
          <div class="pf-form-frame">
            <iframe
              src="https://docs.google.com/forms/d/e/1FAIpQLSe8SOVPspfusSJ0TT7QcdSGnLbcb2MOWjdHmdk4HOlrMVANHA/viewform?embedded=true"
              width="100%"
              height="720"
              frameborder="0"
              marginheight="0"
              marginwidth="0"
              loading="lazy"
              title="Cuestionario PrevCS — Google Forms">Cargando…</iframe>
          </div>
        </div>
      </div>

      <!-- Gráficos de respuestas -->
      <div class="pf-block" data-reveal>
        <span class="pf-sub-eyebrow mono"><?= $icon('chart', 'pf-ico pf-ico--sm') ?> gráficos de respuestas</span>
        <h3 class="pf-block-title">Gráficos de la encuesta.</h3>

        <div class="pf-charts-grid" data-charts data-metrics-url="<?= base_url('api/form-metrics') ?>">
          <article class="pf-chart-card" data-chart="rol_empresa">
            <header><span class="mono">torta</span><strong>Rol en la empresa</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="rol_empresa" aria-label="Gráfico de roles en la empresa"></canvas></div>
            <footer class="mono">distribución</footer>
          </article>

          <article class="pf-chart-card" data-chart="conoce_similar">
            <header><span class="mono">torta</span><strong>¿Conoce u operó algún producto similar?</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="conoce_similar" aria-label="Gráfico de conocimiento previo de productos similares"></canvas></div>
            <footer class="mono">sí / no</footer>
          </article>

          <article class="pf-chart-card" data-chart="funcion_util">
            <header><span class="mono">torta</span><strong>Función más útil para la empresa</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="funcion_util" aria-label="Gráfico de función más útil percibida"></canvas></div>
            <footer class="mono">stock · mantenimiento · gráficos · sensor</footer>
          </article>

          <article class="pf-chart-card" data-chart="nivel_mejora">
            <header><span class="mono">torta</span><strong>Nivel de mejora estimado</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="nivel_mejora" aria-label="Gráfico de nivel de mejora estimado"></canvas></div>
            <footer class="mono">bajo · medio · alto</footer>
          </article>

          <article class="pf-chart-card pf-chart-card--sustain" data-chart="es_ecologico">
            <header><span class="mono">torta</span><strong>Interés en producto ecológico</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="es_ecologico" aria-label="Gráfico de interés en producto ecológico"></canvas></div>
            <footer class="mono">pregunta de sustentabilidad</footer>
          </article>
        </div>

        <!-- Analisis textual de los resultados, en cards stackeables (GSAP reveal + sticky). -->
        <div class="pf-results-analysis">
          <span class="pf-results-eyebrow mono"><?= $icon('eye', 'pf-ico pf-ico--sm') ?> análisis de los resultados obtenidos</span>
          <h5 class="pf-results-title">Conclusiones</h5>

          <div class="pf-stack" data-stack>
            <article class="pf-stack-card" style="--i:0" data-reveal="right">
              <span class="pf-stack-num mono">01</span>
              <p>
                La encuesta muestra que el <b>90% de los respondientes tiene contacto directo con
                la operación industrial</b> — un <b>50% se identifica como Operador</b> y un
                <b>40% como Administración</b>, mientras que solo el <b>10% ocupa un rol Gerencial</b>.
                Este perfil valida que la propuesta de PrevCS conecta con los usuarios que efectivamente
                conviven con la plataforma día a día.
              </p>
            </article>

            <article class="pf-stack-card" style="--i:1" data-reveal="right">
              <span class="pf-stack-num mono">02</span>
              <p>
                Además, <b>el 70% de los encuestados conoce u operó algún producto similar</b>, contra
                un <b>30% que no</b>. Esa familiaridad previa indica que el mercado entiende la categoría:
                PrevCS no necesita "inventar el problema", sino diferenciarse dentro de un terreno conocido.
              </p>
            </article>

            <article class="pf-stack-card" style="--i:2" data-reveal="right">
              <span class="pf-stack-num mono">03</span>
              <p>
                Al preguntar qué función consideran <b>más útil para la empresa</b>, las respuestas se
                inclinaron hacia <b>Gestión de Mantenimiento (50%)</b>, seguida por <b>Gráficos (20%)</b>
                y <b>Sensor en tiempo real (20%)</b>, dejando a <b>Stock con un 10%</b>. El corazón
                percibido del producto es el mantenimiento predictivo asistido por datos.
              </p>
            </article>

            <article class="pf-stack-card" style="--i:3" data-reveal="right">
              <span class="pf-stack-num mono">04</span>
              <p>
                Sobre el <b>nivel de mejora estimado</b>, el <b>80% lo considera Alto</b> y el <b>20%
                restante lo ubica en Medio</b> — ningún encuestado eligió Bajo. La validación del impacto
                percibido es contundente.
              </p>
            </article>

            <article class="pf-stack-card" style="--i:4" data-reveal="right">
              <span class="pf-stack-num mono">05</span>
              <p>
                Finalmente, en la dimensión ecológica, el <b>90% manifestó interés explícito</b> y el
                <b>10% se mostró indiferente</b>. No hubo respuestas negativas. Esto se desarrolla con
                mayor profundidad en la reflexión de más abajo.
              </p>
            </article>

          </div>
        </div>
      </div>

      <!-- 7.5 · Reflexión sobre sustentabilidad -->
      <div class="pf-block" data-reveal>
        <span class="pf-sub-eyebrow pf-sub-eyebrow--sustain mono"><?= $icon('leaf', 'pf-ico pf-ico--sm') ?> 7.5 · reflexión sobre sustentabilidad</span>
        <h3 class="pf-block-title">Reflexión sobre sustentabilidad.</h3>
        <p class="pf-block-lede">
          Pensamos la sustentabilidad en tres ejes — la importancia que le asignan los encuestados,
          su influencia en la decisión de uso, y las decisiones que podríamos tomar sobre el producto
          a partir de esos resultados.
        </p>
      </div>

      <div class="pf-ejes" data-ejes>
        <div class="pf-ejes-path" aria-hidden="true">
          <svg viewBox="0 0 1000 760" preserveAspectRatio="none">
            <path class="pf-ejes-path-base" d="M110 70 C250 70 300 170 430 170 C590 170 725 175 820 235 C900 285 760 355 535 390 C370 420 285 455 250 520 C315 640 540 615 750 665" />
            <path class="pf-ejes-path-line" pathLength="1" d="M110 70 C250 70 300 170 430 170 C590 170 725 175 820 235 C900 285 760 355 535 390 C370 420 285 455 250 520 C315 640 540 615 750 665" />
          </svg>
        </div>

        <article class="pf-eje pf-eje--one" data-reveal="right">
          <span class="pf-eje-dot" aria-hidden="true"></span>
          <div class="pf-eje-card">
            <span class="mono pf-eje-tag"><?= $icon('leaf', 'pf-ico pf-ico--sm') ?> eje 1</span>
            <h5>Importancia asignada a la sustentabilidad</h5>
            <p>
              <b>9 de cada 10 encuestados</b> respondieron que les interesa que el producto sea
              ecológico, y el <b>10% restante se declaró indiferente</b> — <b>sin respuestas
              negativas</b>. La sustentabilidad forma parte del estándar mínimo esperado.
            </p>
          </div>
        </article>

        <article class="pf-eje pf-eje--two" data-reveal="left">
          <span class="pf-eje-dot" aria-hidden="true"></span>
          <div class="pf-eje-card">
            <span class="mono pf-eje-tag"><?= $icon('scale', 'pf-ico pf-ico--sm') ?> eje 2</span>
            <h5>Influencia en la decisión de uso o compra</h5>
            <p>
              Con un consenso tan amplio (<b>90% sí + 10% indiferente</b>), la sustentabilidad pasa
              de ser un factor de imagen a un <b>criterio efectivo de adopción</b>, sobre todo cuando
              los propios operarios la asumen como expectativa.
            </p>
          </div>
        </article>

        <article class="pf-eje pf-eje--three" data-reveal="right">
          <span class="pf-eje-dot" aria-hidden="true"></span>
          <div class="pf-eje-card">
            <span class="mono pf-eje-tag"><?= $icon('bulb', 'pf-ico pf-ico--sm') ?> eje 3</span>
            <h5>Decisiones posibles sobre PrevCS</h5>
            <ul class="pf-feature-list">
              <li><?= $icon('money') ?><span><b>Comunicar el ahorro</b> del mantenimiento predictivo: menos paradas, menos consumo.</span></li>
              <li><?= $icon('bolt') ?><span><b>Sensores de eficiencia energética</b> en el set inicial recomendado.</span></li>
              <li><?= $icon('gauge') ?><span><b>Panel de "huella operativa"</b>: kWh ahorrados y paradas evitadas.</span></li>
              <li><?= $icon('spark') ?><span><b>Alinear la difusión</b> con el ángulo de sustentabilidad.</span></li>
            </ul>
          </div>
        </article>
      </div>
    </section>
  </div>
</main>

<!-- ───────────────────────────── FOOTER (mismo que landing.php) ───────────── -->
<footer id="contacto" class="footer">
  <div class="ft-top">
    <div class="ft-brand">
      <a class="hd-logo" href="<?= base_url() ?>" aria-label="PrevCS, inicio">
        <span class="hd-logo-mark"><span class="hd-logo-dot"></span><span class="hd-logo-dot"></span><span class="hd-logo-dot"></span><span class="hd-logo-dot live"></span></span>
        <span>PrevCS</span>
      </a>
      <p>Plataforma de monitoreo industrial personalizada. Métricas primero, sensores después.</p>
    </div>

    <div class="ft-cols">
      <div>
        <h5>Portfolio</h5>
        <a href="#pagina-principal">Página principal</a>
        <a href="#plan-operativo">Plan Operativo</a>
        <a href="#imagen-corporativa">Imagen corporativa</a>
        <a href="#quienes-somos">Quiénes somos</a>
      </div>
      <div>
        <h5>Investigación</h5>
        <a href="#acerca-de-prevcs">Acerca de PrevCS</a>
        <a href="#analisis-competencia">Análisis competencia</a>
        <a href="#analisis-mercado">Análisis de mercado</a>
      </div>
      <div>
        <h5>Plataforma</h5>
        <a href="<?= base_url() ?>#funcionalidades">Funcionalidades</a>
        <a href="<?= base_url() ?>#como-funciona">Cómo funciona</a>
        <a href="<?= base_url() ?>#metricas">Métricas</a>
        <a href="<?= base_url() ?>#sensores">Sensores</a>
      </div>
      <div>
        <h5>Contacto</h5>
        <a href="mailto:mayradelloso@itr3.edu.ar">mayradelloso@itr3.edu.ar</a>
        <a href="mailto:hola@prevcs.com">hola@prevcs.com</a>
      </div>
    </div>
  </div>

  <div class="ft-bottom">
    <span class="mono">© <?= date('Y') ?> PrevCS · portfolio público · Emprendimientos 7º "B"</span>
    <span class="mono">Instituto Técnico Río Tercero · ciclo 2026</span>
  </div>
</footer>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<!-- GSAP + ScrollTrigger — must load before portfolio.js (Lenis is loaded by the layout) -->
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
<script src="<?= base_url('js/portfolio.js') ?>?v=20260529-9" defer></script>
<?= $this->endSection() ?>
