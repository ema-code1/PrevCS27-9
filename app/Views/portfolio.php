<?= $this->extend('templates/layout') ?>

<?php
// Portfolio reuses the landing's custom header; hide the default app topbar.
$showHeader = false;
?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/landing.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/portfolio.css') ?>">
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
      <a href="<?= base_url() ?>#como-funciona">Como funciona</a>
      <a href="<?= base_url() ?>#metricas">Metricas</a>
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
                <span class="hpm-item-text"><strong>Pagina principal</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#quienes-somos" role="menuitem" data-section-link="quienes-somos">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="9" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="17" cy="10" r="2.4" stroke="currentColor" stroke-width="1.5"/><path d="M3 19c1-3 3.5-4.5 6-4.5s5 1.5 6 4.5M14.5 19c.8-2.2 2.5-3.5 4.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Quienes somos</strong></span>
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
              <span class="hpm-col-label mono">Investigacion</span>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#analisis-mercado" role="menuitem" data-section-link="analisis-mercado">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M4 19V5M4 19h16M8 15v-4M12 15V9M16 15v-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Analisis de mercado</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#analisis-competencia" role="menuitem" data-section-link="analisis-competencia">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="m9 9 6 1.5L13.5 16 7 14.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Analisis de la competencia</strong></span>
              </a>
            </div>
          </div>

        </div>
      </div>

      <a href="<?= base_url() ?>#contacto">Contacto</a>
    </nav>

    <div class="hd-cta">
      <a class="l-btn l-btn--ghost" href="<?= base_url('login') ?>">Iniciar sesion</a>
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
    <section id="pagina-principal" class="pf-section pf-section--hero" data-screen-label="01 Pagina principal">
      <div class="pf-hero-bg" aria-hidden="true">
        <div class="pf-hero-grid"></div>
        <div class="pf-hero-glow pf-hero-glow-1"></div>
        <div class="pf-hero-glow pf-hero-glow-2"></div>
      </div>

      <header class="pf-hero-head">
        <span class="pf-tag mono">
          <span class="pf-tag-dot"></span>
          Portfolio · 7º Año "B" · Instituto Tecnico Rio Tercero
        </span>
        <h1 class="pf-hero-title">
          Portfolio de Emprendimientos
          <span class="pf-hero-serif">desarrollado por etapas durante el ciclo lectivo 2026.</span>
        </h1>

        <div class="pf-hero-actions">
          <a class="l-btn l-btn--line" href="#acerca-de-prevcs">
            Conoce PrevCS
          </a>
        </div>
      </header>
    </section>

    <!-- ============================================================= -->
    <!-- 02 · PLAN OPERATIVO                                            -->
    <!-- ============================================================= -->
    <section id="plan-operativo" class="pf-section" data-screen-label="02 Plan operativo">
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono">02 · Plan operativo</span>
        <h2 class="pf-section-title">
          Como vamos a operar el emprendimiento
          <span class="pf-muted">paso a paso, con tiempos y responsables.</span>
        </h2>
        <p class="pf-section-lede">
          Apartado preparado para completarse cuando lo trabajemos en clase. Por ahora se mantiene
          la estructura con bloques para objetivos, recursos, etapas, responsables y tiempos.
        </p>
        <span class="pf-status pf-status--todo">
          <span class="pf-status-dot"></span> Pendiente — se completara mas adelante
        </span>
      </header>

      <div class="pf-plan-grid">
        <article class="pf-plan-card" data-plan="objetivos">
          <span class="pf-plan-num mono">01</span>
          <h3>Objetivos</h3>
          <p class="pf-placeholder-text">Reservado para los objetivos generales y especificos del emprendimiento.</p>
          <ul class="pf-bullet-list pf-placeholder">
            <li>Objetivo general — <em>placeholder</em></li>
            <li>Objetivos especificos — <em>placeholder</em></li>
            <li>Resultados esperados — <em>placeholder</em></li>
          </ul>
        </article>

        <article class="pf-plan-card" data-plan="recursos">
          <span class="pf-plan-num mono">02</span>
          <h3>Recursos</h3>
          <p class="pf-placeholder-text">Tecnologicos, humanos, materiales y financieros que necesita el proyecto.</p>
          <ul class="pf-bullet-list pf-placeholder">
            <li>Hardware (sensores, gateways) — <em>placeholder</em></li>
            <li>Software (CodeIgniter, MySQL, Chart.js) — <em>placeholder</em></li>
            <li>Equipo de trabajo — <em>placeholder</em></li>
          </ul>
        </article>

        <article class="pf-plan-card" data-plan="etapas">
          <span class="pf-plan-num mono">03</span>
          <h3>Etapas</h3>
          <p class="pf-placeholder-text">Hoja de ruta dividida en fases medibles, con hitos por cada etapa.</p>
          <ol class="pf-timeline pf-placeholder">
            <li><span class="pf-tl-dot"></span><b>Fase 1.</b> Investigacion y validacion</li>
            <li><span class="pf-tl-dot"></span><b>Fase 2.</b> Prototipo funcional</li>
            <li><span class="pf-tl-dot"></span><b>Fase 3.</b> Pilotaje con cliente</li>
            <li><span class="pf-tl-dot"></span><b>Fase 4.</b> Lanzamiento</li>
          </ol>
        </article>

        <article class="pf-plan-card" data-plan="responsables">
          <span class="pf-plan-num mono">04</span>
          <h3>Responsables</h3>
          <p class="pf-placeholder-text">Quien lidera cada area y como se distribuyen las tareas del equipo.</p>
          <ul class="pf-bullet-list pf-placeholder">
            <li>Lider del proyecto — <em>placeholder</em></li>
            <li>Responsable tecnico — <em>placeholder</em></li>
            <li>Responsable comercial — <em>placeholder</em></li>
          </ul>
        </article>

        <article class="pf-plan-card pf-plan-card--wide" data-plan="tiempos">
          <span class="pf-plan-num mono">05</span>
          <h3>Tiempos</h3>
          <p class="pf-placeholder-text">Cronograma estimado por fase. Se completara con fechas concretas.</p>
          <div class="pf-gantt pf-placeholder" aria-hidden="true">
            <div class="pf-gantt-row"><span>Fase 1</span><span class="pf-gantt-bar" style="--from:0; --to:25;"></span></div>
            <div class="pf-gantt-row"><span>Fase 2</span><span class="pf-gantt-bar" style="--from:20; --to:55;"></span></div>
            <div class="pf-gantt-row"><span>Fase 3</span><span class="pf-gantt-bar" style="--from:50; --to:80;"></span></div>
            <div class="pf-gantt-row"><span>Fase 4</span><span class="pf-gantt-bar" style="--from:75; --to:100;"></span></div>
          </div>
          <div class="pf-gantt-scale mono"><span>inicio</span><span>medio</span><span>cierre</span></div>
        </article>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 03 · IMAGEN CORPORATIVA                                        -->
    <!-- ============================================================= -->
    <section id="imagen-corporativa" class="pf-section" data-screen-label="03 Imagen corporativa">
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono">03 · Imagen corporativa</span>
        <h2 class="pf-section-title">
          La identidad visual del proyecto.
          <span class="pf-muted">Marca, paleta y aplicaciones.</span>
        </h2>
        <p class="pf-section-lede">
          Apartado preparado con la identidad actual derivada de la plataforma. Cuando definamos
          la version final del manual de marca, los bloques se reemplazan con los assets oficiales.
        </p>
        <span class="pf-status pf-status--partial">
          <span class="pf-status-dot"></span> Parcial — derivado del proyecto, pendiente manual oficial
        </span>
      </header>

      <div class="pf-brand-grid">
        <article class="pf-brand-card pf-brand-card--logo">
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

        <article class="pf-brand-card pf-brand-card--palette">
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

        <article class="pf-brand-card pf-brand-card--type">
          <span class="pf-brand-label mono">tipografia</span>
          <h4>Sistema tipografico</h4>
          <div class="pf-type-stack">
            <div class="pf-type-row"><b style="font-family: var(--font-sans); font-size: 22px; letter-spacing: -0.03em;">Geist Sans</b><span class="mono">interfaz y contenidos</span></div>
            <div class="pf-type-row"><b class="mono" style="font-size: 18px;">Geist Mono</b><span class="mono">datos, etiquetas y meta</span></div>
            <div class="pf-type-row"><b class="serif" style="font-size: 24px;">Instrument Serif</b><span class="mono">enfasis editorial</span></div>
          </div>
        </article>

        <article class="pf-brand-card pf-brand-card--voice">
          <span class="pf-brand-label mono">voz</span>
          <h4>Tono de comunicacion</h4>
          <ul class="pf-bullet-list">
            <li><b>Claro.</b> Sin jerga innecesaria. La industria ya tiene suficiente.</li>
            <li><b>Concreto.</b> Datos sobre adjetivos. Promesas verificables.</li>
            <li><b>Cercano.</b> Hablamos con el operario, no por encima del operario.</li>
          </ul>
        </article>

        <article class="pf-brand-card pf-brand-card--apps">
          <span class="pf-brand-label mono">aplicaciones</span>
          <h4>Aplicaciones de marca</h4>
          <p class="pf-placeholder-text">Espacio reservado para mockups de aplicaciones: banner, QR para Expo Demostrativa 2026, tarjeta, papeleria, social.</p>
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
    <section id="quienes-somos" class="pf-section" data-screen-label="04 Quienes somos">
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono">04 · Quienes somos</span>
        <h2 class="pf-section-title">
          El equipo detras del proyecto.
          <span class="pf-muted">Foto, rol y trayectoria.</span>
        </h2>
        <p class="pf-section-lede">
          Espacios reservados para los integrantes del grupo de tesina. La descripcion definitiva se
          carga cuando trabajemos este apartado parcialmente en clase.
        </p>
        <span class="pf-status pf-status--partial">
          <span class="pf-status-dot"></span> Parcial — estructura lista, fotos y bios pendientes
        </span>
      </header>

      <div class="pf-team-grid">
        <article class="pf-member-card">
          <div class="pf-member-photo" aria-hidden="true">
            <span class="pf-member-initials">JM</span>
            <span class="pf-member-photo-tag mono">foto</span>
          </div>
          <div class="pf-member-body">
            <span class="mono pf-member-role">integrante 01</span>
            <h3 class="pf-member-name">Jose Mangold</h3>
            <p class="pf-member-desc">Desarrollador full-stack del proyecto. Lidera la implementacion tecnica de PrevCS.</p>
            <ul class="pf-member-tags mono">
              <li>backend</li><li>frontend</li><li>IoT</li>
            </ul>
          </div>
        </article>

        <article class="pf-member-card pf-member-card--placeholder">
          <div class="pf-member-photo" aria-hidden="true">
            <span class="pf-member-initials">??</span>
            <span class="pf-member-photo-tag mono">foto</span>
          </div>
          <div class="pf-member-body">
            <span class="mono pf-member-role">integrante 02</span>
            <h3 class="pf-member-name">Companero/a de tesina</h3>
            <p class="pf-member-desc pf-placeholder-text">Espacio reservado para nombre, rol y breve presentacion del compañero de tesina.</p>
            <ul class="pf-member-tags mono">
              <li>rol</li><li>area</li><li>aporte</li>
            </ul>
          </div>
        </article>

        <article class="pf-member-card pf-member-card--mentor">
          <div class="pf-member-photo pf-member-photo--mentor" aria-hidden="true">
            <span class="pf-member-initials">MD</span>
            <span class="pf-member-photo-tag mono">foto</span>
          </div>
          <div class="pf-member-body">
            <span class="mono pf-member-role">profesora · materia Emprendimientos</span>
            <h3 class="pf-member-name">Mayra Dell'Oso</h3>
            <p class="pf-member-desc">Acompana y evalua el desarrollo del portfolio durante el ciclo lectivo 2026.</p>
            <ul class="pf-member-tags mono">
              <li>guia</li><li>evaluacion</li><li>tutoria</li>
            </ul>
          </div>
        </article>
      </div>

      <div class="pf-team-foot">
        <span class="mono">7º Año "B"</span>
        <span class="mono">· Instituto Tecnico Rio Tercero ·</span>
        <span class="mono">ciclo 2026</span>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 05 · ACERCA DE PREVCS                                          -->
    <!-- ============================================================= -->
    <section id="acerca-de-prevcs" class="pf-section" data-screen-label="05 Acerca de PrevCS">
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono">05 · Acerca de PrevCS</span>
        <h2 class="pf-section-title">
          Que es PrevCS y a quien va dirigido.
          <span class="pf-muted">Proposito, problema y propuesta de valor.</span>
        </h2>
        <p class="pf-section-lede">
          PrevCS — Prevention Control System — es una plataforma de monitoreo industrial personalizada
          que ayuda a una empresa a definir <b>que datos medir</b> antes de comprar sensores. Empezamos
          por la operacion, no por el hardware.
        </p>
        <span class="pf-status pf-status--partial">
          <span class="pf-status-dot"></span> Parcial — completado con lo que sabemos hoy del proyecto
        </span>
      </header>

      <div class="pf-about-grid">
        <article class="pf-about-card pf-about-card--lead">
          <span class="mono pf-about-tag">descripcion</span>
          <h3>Una plataforma que entiende la planta antes que los sensores.</h3>
          <p>
            PrevCS guia paso a paso: workspace por empresa, eleccion de metricas (temperatura,
            vibracion, energia, produccion, stock, mantenimiento), recomendacion de sensores compatibles,
            armado de dashboards en vivo y configuracion de alertas accionables.
          </p>
        </article>

        <article class="pf-about-card">
          <span class="mono pf-about-tag">proposito</span>
          <h4>Para que existe</h4>
          <p>
            Reducir las paradas no planificadas y las decisiones tomadas "por instinto"
            en industria pyme. Llevar la operacion industrial de planillas dispersas a una
            sola pantalla en tiempo real.
          </p>
        </article>

        <article class="pf-about-card">
          <span class="mono pf-about-tag">problema</span>
          <h4>Que resuelve</h4>
          <ul class="pf-bullet-list">
            <li>Datos dispersos entre cuadernos, planillas y PLCs sueltos.</li>
            <li>Mantenimiento reactivo (se repara cuando ya rompio).</li>
            <li>Sensores comprados por catalogo sin saber si miden lo necesario.</li>
            <li>Dashboards lindos pero sin lectura accionable.</li>
          </ul>
        </article>

        <article class="pf-about-card">
          <span class="mono pf-about-tag">valor</span>
          <h4>Propuesta de valor</h4>
          <ul class="pf-bullet-list">
            <li><b>Metricas primero.</b> Decidis que queres saber; los sensores vienen despues.</li>
            <li><b>Recomendaciones inteligentes.</b> PrevCS sugiere set de sensores y plantillas.</li>
            <li><b>Workspace propio.</b> Configurado a la empresa, no un panel generico.</li>
            <li><b>Alertas y predictivo.</b> Por umbral, tendencia o correlacion entre metricas.</li>
          </ul>
        </article>

        <article class="pf-about-card pf-about-card--stats">
          <span class="mono pf-about-tag">cobertura</span>
          <h4>Que mide PrevCS</h4>
          <ul class="pf-stat-grid mono">
            <li><b>8+</b><span>metricas base</span></li>
            <li><b>10+</b><span>sensores soportados</span></li>
            <li><b>6</b><span>industrias objetivo</span></li>
            <li><b>24/7</b><span>monitoreo en vivo</span></li>
          </ul>
        </article>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 06 · ANALISIS DE LA COMPETENCIA                                -->
    <!-- ============================================================= -->
    <section id="analisis-competencia" class="pf-section" data-screen-label="06 Analisis competencia">
      <header class="pf-section-head">
        <span class="pf-section-eyebrow mono">06 · Analisis de la competencia</span>
        <h2 class="pf-section-title">
          Quienes mas estan resolviendo este problema
          <span class="pf-muted">y como nos diferenciamos.</span>
        </h2>
      </header>

      <!-- 6.1 · Introduccion editorial (sin card, texto abierto) -->
      <div class="pf-editorial" data-pf-reveal>
        <span class="pf-editorial-rule" aria-hidden="true"></span>
        <p class="pf-editorial-lead">
          En el marco del desarrollo de nuestra tesina titulada <b>"PrevCS — Sistema Inteligente
          de Gestion de Mantenimiento Industrial"</b>, mapeamos las principales plataformas que
          compiten en gestion de mantenimiento, monitoreo IoT y administracion de activos.
        </p>
        <p>
          El objetivo es comprender como funcionan las soluciones que hoy se usan en la industria,
          identificar fortalezas y limitaciones, y establecer con claridad las ventajas competitivas
          que ofrece nuestro proyecto frente a sistemas comerciales ya existentes.
        </p>
        <p>
          PrevCS busca brindar una solucion moderna, accesible e integrada para industrias y plantas
          productivas — gestiona maquinaria, herramientas e insumos mediante mantenimiento preventivo,
          control de stock, analisis de datos y monitoreo en tiempo real a traves de dispositivos
          IoT como ESP32.
        </p>
      </div>

      <!-- 6.2 · Stat strip "vs mercado" (numeros editoriales, sin card) -->
      <div class="pf-vs-strip" data-pf-reveal>
        <div>
          <span class="pf-vs-num">3</span>
          <span class="pf-vs-label">plataformas analizadas</span>
        </div>
        <div>
          <span class="pf-vs-num">20</span>
          <span class="pf-vs-label">criterios comparados</span>
        </div>
        <div>
          <span class="pf-vs-num pf-vs-num--accent">1</span>
          <span class="pf-vs-label">propuesta local / PyME</span>
        </div>
        <div>
          <span class="pf-vs-num">$$$</span>
          <span class="pf-vs-label">de high-end a accesible</span>
        </div>
      </div>

      <!-- 6.3 · Cuadro comparativo (responsive con overflow aislado) -->
      <span class="pf-comp-hint mono" aria-hidden="true">
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h12M10 4l4 4-4 4M6 12l-4-4 4-4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Desliza para ver toda la tabla
      </span>
      <div class="pf-comp-table-wrap" data-comp-scroll>
        <table class="pf-comp-table">
          <thead>
            <tr>
              <th>Caracteristica</th>
              <th>MaintainX</th>
              <th>Fracttal One</th>
              <th>Oracle NetSuite</th>
              <th>PrevCS</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><b>Localizacion</b></td>
              <td>Estados Unidos</td>
              <td>Chile</td>
              <td>Estados Unidos</td>
              <td class="pf-comp-self-cell"><b>Argentina</b></td>
            </tr>
            <tr>
              <td><b>Productos y servicios</b></td>
              <td>Gestion de mantenimiento y ordenes de trabajo</td>
              <td>CMMS + IoT + analisis industrial</td>
              <td>ERP empresarial con mantenimiento y stock</td>
              <td class="pf-comp-self-cell"><b>Gestion de mantenimiento, stock e IoT industrial</b></td>
            </tr>
            <tr>
              <td><b>Precio de venta</b></td>
              <td>Suscripcion mensual</td>
              <td>Planes empresariales pagos</td>
              <td>Alto costo corporativo</td>
              <td class="pf-comp-self-cell"><b>Bajo costo y adaptable</b></td>
            </tr>
            <tr>
              <td><b>Medios de pago</b></td>
              <td>Tarjeta / suscripcion online</td>
              <td>Facturacion empresarial</td>
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
              <td><b>Tecnologia</b></td>
              <td>Cloud y automatizacion</td>
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
              <td>Empresas industriales y tecnicas</td>
              <td>Industrias latinoamericanas</td>
              <td>Grandes empresas y corporaciones</td>
              <td class="pf-comp-self-cell"><b>PyMEs e industrias locales</b></td>
            </tr>
            <tr>
              <td><b>Atencion al cliente</b></td>
              <td>Soporte online</td>
              <td>Soporte tecnico empresarial</td>
              <td>Soporte corporativo</td>
              <td class="pf-comp-self-cell"><b>Atencion personalizada</b></td>
            </tr>
            <tr>
              <td><b>Cumplimiento de entrega</b></td>
              <td>Alto</td>
              <td>Alto</td>
              <td>Alto</td>
              <td class="pf-comp-self-cell"><b>Desarrollo adaptable y rapido</b></td>
            </tr>
            <tr>
              <td><b>Publicidad</b></td>
              <td>Marketing digital</td>
              <td>Publicidad corporativa</td>
              <td>Publicidad empresarial global</td>
              <td class="pf-comp-self-cell"><b>Difusion academica y local</b></td>
            </tr>
            <tr>
              <td><b>Puntos fuertes</b></td>
              <td>Facilidad de uso</td>
              <td>Integracion IoT</td>
              <td>Gran integracion empresarial</td>
              <td class="pf-comp-self-cell"><b>Bajo costo + personalizacion</b></td>
            </tr>
            <tr>
              <td><b>Puntos debiles</b></td>
              <td>Funciones limitadas en versiones basicas</td>
              <td>Complejidad tecnica</td>
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
              <td><b>Bajo consumo energetico</b></td>
              <td>Dependencia cloud</td>
              <td>Requiere infraestructura</td>
              <td>Alto consumo empresarial</td>
              <td class="pf-comp-self-cell"><b>ESP32 de bajo consumo</b></td>
            </tr>
            <tr>
              <td><b>Reduccion de residuos</b></td>
              <td>Parcial</td>
              <td>Parcial</td>
              <td>Parcial</td>
              <td class="pf-comp-self-cell"><b>Reduce mantenimiento innecesario</b></td>
            </tr>
            <tr>
              <td><b>Packaging ecologico</b></td>
              <td>No aplica</td>
              <td>No aplica</td>
              <td>No aplica</td>
              <td class="pf-comp-self-cell">No aplica</td>
            </tr>
            <tr>
              <td><b>Reparacion o reutilizacion</b></td>
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
              <td>Mayor automatizacion</td>
              <td>Mayor capacidad empresarial</td>
              <td class="pf-comp-self-cell"><b>Menor costo y menor consumo</b></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 6.4 · Pull-quote: que hace distinto a PrevCS -->
      <div class="pf-pullquote" data-pf-reveal>
        <span class="pf-pullquote-mark" aria-hidden="true">&ldquo;</span>
        <div class="pf-pullquote-body">
          <q>
            PrevCS integra en una sola plataforma lo que la competencia ofrece por separado —
            mantenimiento, stock y monitoreo IoT — pensado para industrias medianas y pequenas
            que necesitan una solucion funcional sin afrontar costos extremadamente elevados.
          </q>
          <span class="pf-pullquote-meta">6.4 · diferencias frente a la competencia</span>
        </div>
      </div>

      <!-- 6.5 · Por que elegirian PrevCS — numbered editorial block -->
      <div class="pf-numbered-block" data-pf-reveal>
        <div>
          <span class="pf-numbered-block-num">06</span>
          <span>razones de adopcion</span>
        </div>
        <div>
          <h4>Por que elegirian PrevCS</h4>
          <p>
            Frente a plataformas con herramientas avanzadas pero que requieren infraestructura
            compleja, capacitacion especializada y altos costos mensuales, los clientes optarian
            por PrevCS por seis razones concretas:
          </p>
          <ul class="pf-bullet-list">
            <li>Menor costo de implementacion.</li>
            <li>Sistema mas simple e intuitivo.</li>
            <li>Posibilidad de personalizacion.</li>
            <li>Integracion con sensores IoT.</li>
            <li>Control centralizado de maquinaria y stock.</li>
            <li>Facilidad de adaptacion a plantas industriales locales.</li>
          </ul>
        </div>
      </div>

      <!-- 6.6 · Ventajas + Beneficios — split layout sin chrome de card -->
      <div class="pf-split" data-pf-reveal>
        <section>
          <span class="pf-split-eyebrow mono">6.6 · ventajas competitivas</span>
          <h4>Principales ventajas</h4>
          <ul class="pf-bullet-list">
            <li>Integracion de mantenimiento + stock + IoT.</li>
            <li>Bajo costo operativo.</li>
            <li>Interfaz moderna y visual.</li>
            <li>Sistema adaptable a cada planta.</li>
            <li>Monitoreo en tiempo real.</li>
            <li>Alertas automaticas preventivas.</li>
            <li>Orientacion especifica al sector industrial local.</li>
          </ul>
        </section>
        <section>
          <span class="pf-split-eyebrow mono">6.7 · beneficios concretos</span>
          <h4>Lo que cambia en la planta</h4>
          <p>PrevCS aporta beneficios operativos que muchas empresas actualmente no poseen:</p>
          <ul class="pf-bullet-list">
            <li>Seguimiento automatico de mantenimientos.</li>
            <li>Reduccion de fallas inesperadas.</li>
            <li>Control eficiente de insumos.</li>
            <li>Historial centralizado de intervenciones.</li>
            <li>Alertas inteligentes mediante sensores.</li>
            <li>Mejor organizacion operativa.</li>
            <li>Reduccion de tiempos muertos en planta.</li>
          </ul>
        </section>
      </div>

      <!-- 6.8 · Sustentabilidad — full-bleed callout (rompe el ancho del main) -->
      <aside class="pf-sustain-callout" data-pf-reveal>
        <div class="pf-sustain-callout-inner">
          <div>
            <span class="pf-sustain-callout-eyebrow">6.8 · sustentabilidad</span>
            <h3>La sustentabilidad como diferencial competitivo.</h3>
            <p>
              Al digitalizar procesos y registros, PrevCS reduce el uso de papel y documentacion
              fisica dentro de la planta — y suma una capa de eficiencia energetica que la
              competencia trata como funcionalidad opcional.
            </p>
          </div>
          <ul>
            <li>Utiliza dispositivos ESP32 de bajo consumo energetico.</li>
            <li>Reduce desperdicios mediante mantenimiento preventivo.</li>
            <li>Disminuye reemplazos innecesarios de maquinaria.</li>
            <li>Favorece la reutilizacion y reparacion de componentes.</li>
            <li>Optimiza recursos industriales.</li>
            <li>Reduce fallas y consumo excesivo de materiales.</li>
          </ul>
        </div>
      </aside>

      <!-- 6.9 · Conclusion final — texto editorial de cierre -->
      <div class="pf-editorial pf-editorial--wide" data-pf-reveal>
        <span class="pf-editorial-rule" aria-hidden="true"></span>
        <p class="pf-editorial-lead">
          PrevCS posee caracteristicas competitivas solidas frente a distintas plataformas del
          mercado actual.
        </p>
        <p>
          Si bien empresas como <b>MaintainX</b>, <b>Fracttal One</b> y <b>Oracle NetSuite</b>
          cuentan con amplia trayectoria e infraestructura empresarial, PrevCS busca destacarse
          mediante una propuesta mas accesible, adaptable y orientada a las necesidades reales de
          industrias locales y PyMEs.
        </p>
        <p>
          La integracion entre mantenimiento industrial, gestion de stock y monitoreo IoT en
          tiempo real permite ofrecer una solucion moderna, eficiente y sustentable, capaz de
          optimizar recursos y mejorar significativamente el control operativo dentro de una
          planta industrial.
        </p>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- 07 · ANALISIS DE MERCADO  (PRIORITARIO — TP Nº 2)              -->
    <!-- ============================================================= -->
    <section id="analisis-mercado" class="pf-section pf-section--focus" data-screen-label="07 Analisis de mercado">
      <header class="pf-section-head pf-section-head--focus">
        <span class="pf-section-eyebrow mono">07 · Analisis de mercado</span>
        <h2 class="pf-section-title">
          Analisis de mercado.
          <span class="pf-muted">Encuesta, graficos y reflexion sobre sustentabilidad.</span>
        </h2>
        <p class="pf-section-lede">
          Investigacion de mercado a usuarios y consumidores potenciales del producto,
          con foco en validar decisiones del proyecto.
        </p>
      </header>

      <!-- 7.3 · Objetivo de la encuesta -->
      <article class="pf-mk-card">
        <span class="pf-mk-tag mono">7.3 · objetivo de la encuesta</span>
        <h4>Que queremos averiguar</h4>
        <p>
          Validar decisiones del producto (precio, dise&ntilde;o, utilidad, sustentabilidad) y
          entender que tan claro queda el valor de PrevCS para usuarios potenciales que no
          trabajan en planta industrial directamente.
        </p>
        <ul class="pf-bullet-list">
          <li>Que tan entendible es la propuesta para el publico no industrial.</li>
          <li>Que metricas creen mas valiosas.</li>
          <li>Cuanto pesa la sustentabilidad en la decision de uso.</li>
        </ul>
      </article>

      <!-- 7.4 · Publico consultado y segmentacion -->
      <article class="pf-mk-card">
        <span class="pf-mk-tag mono">7.4 · publico consultado y segmentacion</span>
        <h4>Datos del encuestado</h4>
        <p>
          Encuesta abierta a usuarios o consumidores potenciales. Los datos del encuestado se
          segmentan segun: pertenencia o no a la escuela, edad, genero y nivel socio-economico.
          La segmentacion definitiva se vera reflejada en los graficos cuando se importen las
          respuestas reales desde Google Sheets.
        </p>
        <ul class="pf-segmentation">
          <li><span class="mono">criterio</span><b>Pertenencia a la escuela</b></li>
          <li><span class="mono">criterio</span><b>Edad</b></li>
          <li><span class="mono">criterio</span><b>Genero</b></li>
          <li><span class="mono">criterio</span><b>Nivel socio-economico</b></li>
        </ul>
        <p class="pf-note mono">
          nota tecnica · los datos importados se almacenan en MySQL en la tabla <b>form_answers</b>
          (ver "Integracion tecnica" mas abajo).
        </p>
      </article>

      <!-- 7.5 · Cuestionario / tipo de encuesta -->
      <article class="pf-mk-card">
        <span class="pf-mk-tag mono">7.5 · cuestionario</span>
        <h4>Tipo de encuesta · abierta</h4>
        <p>
          Encuesta abierta orientada a usuarios y consumidores potenciales, distribuida via
          Google Forms. Incluye al menos una pregunta vinculada a la sustentabilidad del producto.
        </p>

        <!-- ───────── GOOGLE FORMS EMBED ─────────────────────────────
             AQUI VA EL IFRAME DEL FORMULARIO DE GOOGLE FORMS.
             Para reemplazar por otro formulario, cambiar el atributo `src`
             del <iframe> debajo conservando ?embedded=true.
        ────────────────────────────────────────────────────────────── -->
        <div class="pf-form-wrap">
          <div class="pf-form-head">
            <div>
              <span class="mono pf-form-eyebrow">formulario en vivo</span>
              <span class="pf-form-title">Cuestionario PrevCS · Google Forms</span>
            </div>
            <div class="pf-form-actions">
              <a class="l-btn l-btn--line" href="https://docs.google.com/forms/d/e/1FAIpQLSe8SOVPspfusSJ0TT7QcdSGnLbcb2MOWjdHmdk4HOlrMVANHA/viewform" target="_blank" rel="noopener">
                Abrir en pestaña nueva
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M7 3H3v10h10V9M9 2h5v5M14 2 8 8" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </a>
              <button class="l-btn l-btn--ghost" type="button" data-copy-form-link aria-label="Copiar link del formulario">
                <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="5" y="5" width="9" height="9" rx="1.6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M11 5V3a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h2" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
                <span data-copy-label>Copiar link</span>
              </button>
            </div>
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

          <div class="pf-form-foot mono">
            <span><span class="pf-tag-dot"></span> integracion publica · sin sesion requerida</span>
            <span>docs.google.com/forms</span>
          </div>
        </div>
      </article>

      <!-- 7.6 · Sustentabilidad -->
      <article class="pf-mk-card pf-mk-card--sustain">
        <span class="pf-mk-tag mono">7.6 · pregunta vinculada a sustentabilidad</span>
        <h4>Eje de sustentabilidad del cuestionario</h4>
        <p>
          La encuesta incluye al menos una pregunta especifica para entender cuanto pesa la
          sustentabilidad en la decision de uso del producto, y como podriamos incorporar
          mejoras al producto a partir de esos resultados.
        </p>
        <div class="pf-sustain-points">
          <div><span class="mono">eje</span><b>Importancia que le asignan a la sustentabilidad</b></div>
          <div><span class="mono">eje</span><b>Influencia en la decision de uso o compra</b></div>
          <div><span class="mono">eje</span><b>Decisiones posibles sobre el producto a partir de los resultados</b></div>
        </div>
      </article>

      <!-- 7.8 · Graficos (Chart.js consumiendo /api/form-metrics) -->
      <article class="pf-mk-card pf-mk-card--charts">
        <span class="pf-mk-tag mono">7.8 · graficos de respuestas</span>
        <h4>Graficos de la encuesta</h4>
        <p>
          Los graficos se generan con <b>Chart.js</b> a partir de los datos de
          <code>form_answers</code> en MySQL, importados desde el Google Sheets publicado.
          El endpoint que consumen es
          <code><?= base_url('api/form-metrics') ?></code> (publico).
        </p>

        <div class="pf-charts-meta mono" data-charts-meta>
          <span data-charts-total>—</span> respuestas totales
          <span class="pf-charts-sep">·</span>
          actualizado <span data-charts-updated>—</span>
        </div>

        <div class="pf-charts-grid" data-charts data-metrics-url="<?= base_url('api/form-metrics') ?>">
          <article class="pf-chart-card" data-chart="rol_empresa">
            <header><span class="mono">torta</span><strong>Rol en la empresa</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="rol_empresa" aria-label="Grafico de roles en la empresa"></canvas></div>
            <footer class="mono">distribucion · 10 respuestas</footer>
          </article>

          <article class="pf-chart-card" data-chart="conoce_similar">
            <header><span class="mono">torta</span><strong>Conoce u opero algun producto similar?</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="conoce_similar" aria-label="Grafico de conocimiento previo de productos similares"></canvas></div>
            <footer class="mono">si / no</footer>
          </article>

          <article class="pf-chart-card" data-chart="funcion_util">
            <header><span class="mono">torta</span><strong>Funcion mas util para la empresa</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="funcion_util" aria-label="Grafico de funcion mas util percibida"></canvas></div>
            <footer class="mono">stock · mantenimiento · graficos · sensor</footer>
          </article>

          <article class="pf-chart-card" data-chart="nivel_mejora">
            <header><span class="mono">torta</span><strong>Nivel de mejora estimado</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="nivel_mejora" aria-label="Grafico de nivel de mejora estimado"></canvas></div>
            <footer class="mono">bajo · medio · alto</footer>
          </article>

          <article class="pf-chart-card pf-chart-card--sustain" data-chart="es_ecologico">
            <header><span class="mono">torta</span><strong>Interes en producto ecologico</strong></header>
            <div class="pf-chart-canvas-wrap"><canvas data-chart-canvas="es_ecologico" aria-label="Grafico de interes en producto ecologico"></canvas></div>
            <footer class="mono">pregunta de sustentabilidad</footer>
          </article>
        </div>

        <!-- Analisis textual de los resultados, justo debajo de los graficos. -->
        <div class="pf-results-analysis">
          <span class="pf-results-eyebrow mono">analisis de los resultados obtenidos</span>
          <h5 class="pf-results-title">Conclusiones</h5>

          <p>
            La encuesta muestra que el <b>90% de los respondientes tiene contacto directo con
            la operacion industrial</b> — un <b>50% se identifica como Operador</b> y un
            <b>40% como Administracion</b>, mientras que solo el <b>10% ocupa un rol Gerencial</b>.
            Este perfil valida que la propuesta de PrevCS conecta con los usuarios que efectivamente
            conviven con la plataforma dia a dia, y no solo con quienes toman la decision de compra.
          </p>

          <p>
            A continuacion, observamos que <b>el 70% de los encuestados conoce u opero algun
            producto similar</b>, contra un <b>30% que no</b>. Esa familiaridad previa indica
            que el mercado entiende la categoria: PrevCS no necesita "inventar el problema",
            sino diferenciarse dentro de un terreno ya conocido por la mayoria.
          </p>

          <p>
            Al preguntar que funcion del producto consideran <b>mas util para la empresa</b>,
            las respuestas se inclinaron fuertemente hacia <b>Gestion de Mantenimiento (50%)</b>,
            seguida por <b>Graficos (20%)</b> y <b>Sensor que monitoree un motor y presente
            datos en tiempo real (20%)</b>, dejando a <b>Stock con un 10%</b>. Esto confirma que
            el corazon percibido del producto es el mantenimiento predictivo asistido por datos,
            mientras que la visualizacion y el sensado en tiempo real lo complementan como
            herramientas operativas.
          </p>

          <p>
            Sobre el <b>nivel de mejora estimado al adquirir esta tecnologia</b>, el
            <b>80% lo considera Alto</b> y el <b>20% restante lo ubica en Medio</b> — ningun
            encuestado eligio Bajo. La validacion del impacto percibido es contundente: la
            propuesta de valor se entiende y, ademas, se espera un retorno significativo.
          </p>

          <p>
            Finalmente, en cuanto a la dimension ecologica del producto, el <b>90% manifesto
            interes explicito</b> y el <b>10% se mostro indiferente</b>. No hubo respuestas
            negativas. Esto sera desarrollado con mayor profundidad en la reflexion sobre
            sustentabilidad mas abajo.
          </p>
        </div>
      </article>

      <!-- 7.12 · Reflexion sustentabilidad -->
      <article class="pf-mk-card pf-mk-card--sustain pf-mk-card--reflexion">
        <span class="pf-mk-tag mono">7.12 · reflexion sobre sustentabilidad</span>
        <h4>Reflexion sobre sustentabilidad</h4>
        <p>
          La consigna pide pensar la sustentabilidad en tres ejes — la importancia que le
          asignan los encuestados, su influencia en la decision de uso, y las decisiones que
          podriamos tomar sobre el producto a partir de esos resultados.
        </p>

        <div class="pf-reflexion-grid">
          <article class="pf-reflexion-card">
            <span class="mono">eje 1</span>
            <h5>Importancia asignada a la sustentabilidad</h5>
            <p>
              <b>9 de cada 10 encuestados</b> respondieron que les interesa que el desarrollo
              y funcionamiento del producto sea ecologico, y el <b>10% restante se declaro
              indiferente</b> — <b>sin respuestas negativas</b>. La sustentabilidad no es un
              atributo opcional para nuestro publico: forma parte del estandar minimo esperado.
            </p>
          </article>

          <article class="pf-reflexion-card">
            <span class="mono">eje 2</span>
            <h5>Influencia en la decision de uso o compra</h5>
            <p>
              Con un consenso tan amplio (<b>90% si + 10% indiferente</b>), la sustentabilidad
              pasa de ser un factor de imagen a un <b>criterio efectivo de adopcion</b>. Un
              producto industrial que no la contemple difícilmente pueda competir en este
              mercado, sobre todo cuando los propios operarios — que son la mayoria de la
              muestra — la asumen como expectativa.
            </p>
          </article>

          <article class="pf-reflexion-card">
            <span class="mono">eje 3</span>
            <h5>Decisiones posibles sobre PrevCS</h5>
            <ul class="pf-bullet-list">
              <li>
                <b>Comunicar el ahorro derivado del mantenimiento predictivo.</b> Menos paradas
                y menos paros forzados implican menos consumo y menor desperdicio de recursos:
                ese impacto debe explicitarse en la propuesta de valor.
              </li>
              <li>
                <b>Incorporar sensores de eficiencia energetica</b> (corriente, consumo, factor
                de potencia) al set inicial de recomendaciones del workspace, para que la
                dimension sustentable este desde el primer minuto de uso.
              </li>
              <li>
                <b>Sumar un panel de "huella operativa"</b> donde el cliente vea kWh ahorrados,
                paradas evitadas y vida util extendida de los motores monitoreados.
              </li>
              <li>
                <b>Alinear los materiales de difusion</b> (banner, QR de la Expo Demostrativa,
                redes) con el angulo de sustentabilidad, no solo con el angulo operativo.
              </li>
            </ul>
          </article>
        </div>
      </article>
    </section>

    <!-- ============================================================= -->
    <!-- CIERRE                                                          -->
    <!-- ============================================================= -->
    <section class="pf-section pf-closing">
      <div class="pf-closing-inner">
        <span class="pf-section-eyebrow mono">cierre</span>
        <h2 class="pf-section-title">
          Portfolio en construccion.
          <span class="pf-muted">Cada clase suma una pieza mas.</span>
        </h2>
        <p class="pf-section-lede">
          El portfolio se completa progresivamente durante el ciclo lectivo 2026. Para la
          Expo Demostrativa, esta vista se accede desde un QR vinculado a esta URL publica.
        </p>
        <div class="pf-hero-actions">
          <a class="l-btn l-btn--solid" href="#pagina-principal">Volver al inicio</a>
          <a class="l-btn l-btn--line" href="<?= base_url() ?>">Ir a la landing de PrevCS</a>
        </div>
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
      <p>Plataforma de monitoreo industrial personalizada. Metricas primero, sensores despues.</p>
    </div>

    <div class="ft-cols">
      <div>
        <h5>Portfolio</h5>
        <a href="#pagina-principal">Pagina principal</a>
        <a href="#plan-operativo">Plan Operativo</a>
        <a href="#imagen-corporativa">Imagen corporativa</a>
        <a href="#quienes-somos">Quienes somos</a>
      </div>
      <div>
        <h5>Investigacion</h5>
        <a href="#acerca-de-prevcs">Acerca de PrevCS</a>
        <a href="#analisis-competencia">Analisis competencia</a>
        <a href="#analisis-mercado">Analisis de mercado</a>
      </div>
      <div>
        <h5>Plataforma</h5>
        <a href="<?= base_url() ?>#funcionalidades">Funcionalidades</a>
        <a href="<?= base_url() ?>#como-funciona">Como funciona</a>
        <a href="<?= base_url() ?>#metricas">Metricas</a>
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
    <span class="mono">© <?= date('Y') ?> PrevCS · portfolio publico · Emprendimientos 7º "B"</span>
    <span class="mono">Instituto Tecnico Rio Tercero · ciclo 2026</span>
  </div>
</footer>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<!-- Chart.js v4 — UMD desde CDN (no requiere npm) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<script src="<?= base_url('js/portfolio.js') ?>" defer></script>
<?= $this->endSection() ?>
