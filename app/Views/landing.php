<?= $this->extend('templates/layout') ?>

<?php
// Landing has its own header; hide the default app topbar
$showHeader = false;
$trendIcon = static function (string $direction): string {
    $path = $direction === 'down'
        ? '<path d="m2 4 4 4 4-4Z" fill="currentColor"/>'
        : '<path d="m2 8 4-4 4 4Z" fill="currentColor"/>';

    return '<svg class="trend-icon" viewBox="0 0 12 12" aria-hidden="true">' . $path . '</svg>';
};
?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/landing.css') ?>?v=20260608-8">
<link rel="stylesheet" href="<?= base_url('css/pricing.css') ?>?v=20260610-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ───────────────────────────── HEADER ───────────────────────────── -->
<header class="landing-header" data-screen-label="00 Header">
  <div class="hd-inner">
    <a class="hd-logo" href="#top" aria-label="PrevCS, inicio">
      <span class="hd-logo-mark" aria-hidden="true">
        <span class="hd-logo-dot"></span>
        <span class="hd-logo-dot"></span>
        <span class="hd-logo-dot"></span>
        <span class="hd-logo-dot live"></span>
      </span>
      <span>PrevCS</span>
    </a>

    <nav class="hd-nav" aria-label="Principal" data-section-nav>
      <span class="hd-nav-pill" aria-hidden="true"></span>
      <a href="<?= base_url() ?>#funcionalidades" data-nav-section="funcionalidades">Funcionalidades</a>
      <a href="<?= base_url() ?>#como-funciona" data-nav-section="como-funciona">Cómo funciona</a>
      <a href="<?= base_url() ?>#metricas" data-nav-section="metricas">Métricas</a>
      <a href="<?= base_url() ?>#sensores" data-nav-section="sensores">Sensores</a>
      <a href="<?= base_url() ?>#plantillas" data-nav-section="plantillas">Plantillas</a>
      <a href="<?= base_url() ?>#casos" data-nav-section="casos">Casos de uso</a>
      <a href="<?= base_url() ?>#precio" data-nav-section="precio">Precio</a>

      <div class="hd-portfolio" data-portfolio-menu>
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
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#pagina-principal" role="menuitem">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Página principal</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#quienes-somos" role="menuitem">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="9" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="17" cy="10" r="2.4" stroke="currentColor" stroke-width="1.5"/><path d="M3 19c1-3 3.5-4.5 6-4.5s5 1.5 6 4.5M14.5 19c.8-2.2 2.5-3.5 4.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Quiénes somos</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#acerca-de-prevcs" role="menuitem">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Acerca de PrevCS</strong></span>
              </a>
            </div>

            <div class="hpm-col">
              <span class="hpm-col-label mono">Estrategia</span>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#imagen-corporativa" role="menuitem">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 1 9-9c0 2.5-2 3-4 3h-2a2 2 0 0 0-2 2c0 1.5 1 2-1 4z" stroke="currentColor" stroke-width="1.5"/><circle cx="7.5" cy="11" r="1" fill="currentColor"/><circle cx="11" cy="7.5" r="1" fill="currentColor"/><circle cx="15" cy="8" r="1" fill="currentColor"/><circle cx="17.5" cy="12" r="1" fill="currentColor"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Imagen corporativa</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#plan-operativo" role="menuitem">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 9h18M8 13h5M8 16h7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Plan Operativo</strong></span>
              </a>
            </div>

            <div class="hpm-col">
              <span class="hpm-col-label mono">Investigación</span>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#analisis-mercado" role="menuitem">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M4 19V5M4 19h16M8 15v-4M12 15V9M16 15v-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Análisis de mercado</strong></span>
              </a>
              <a class="hpm-item" href="<?= base_url('portfolio') ?>#analisis-competencia" role="menuitem">
                <span class="hpm-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="m9 9 6 1.5L13.5 16 7 14.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </span>
                <span class="hpm-item-text"><strong>Análisis de la competencia</strong></span>
              </a>
            </div>
          </div>

        </div>
      </div>

      <a href="<?= base_url() ?>#contacto" data-nav-section="contacto">Contacto</a>
    </nav>

    <div class="hd-cta">
      <a class="l-btn l-btn--ghost" href="<?= base_url('login') ?>">Iniciar sesión</a>
      <a class="l-btn l-btn--solid" href="<?= base_url('reg-log') . '?form=register' ?>">
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

<main id="top">

<!-- ───────────────────────────── HERO ───────────────────────────── -->
<section class="hero-section" data-screen-label="01 Hero">
  <div class="hero-bg" aria-hidden="true">
    <div class="hero-grid"></div>
    <div class="hero-glow hero-glow-1"></div>
    <div class="hero-glow hero-glow-2"></div>
  </div>

  <div class="hero-content">
    <a class="eyebrow" href="#como-funciona">
      <span class="eyebrow-dot"></span>
      Plataforma de monitoreo industrial
      <svg class="ui-icon eyebrow-arrow" viewBox="0 0 20 20" aria-hidden="true">
        <path d="M4 10h10m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </a>

    <h1 class="hero-title">
      Monitorea tu industria con un sistema
      <span class="hero-title-em">diseñado según tus métricas.</span>
    </h1>

    <p class="hero-sub">
      PrevCS te ayuda a crear un espacio industrial personalizado, elegir qué datos medir
      y recibir recomendaciones de sensores, dashboards y plantillas para monitorear
      tu operación en tiempo real.
    </p>

    <div class="hero-actions">
      <a class="l-btn l-btn--solid l-btn--lg" href="<?= base_url('reg-log') . '?form=register' ?>">
        Crear cuenta
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a class="l-btn l-btn--line l-btn--lg" href="#como-funciona">
        <span class="l-play-dot" aria-hidden="true"></span>
        Ver cómo funciona
      </a>
    </div>

    <ul class="hero-trust">
      <li><span>SOC 2 ready</span></li>
      <li><span>ISA-95 friendly</span></li>
      <li><span>MQTT · Modbus · OPC-UA</span></li>
      <li><span>Workspaces ilimitados</span></li>
    </ul>
  </div>

  <!-- Floating dashboard composition -->
  <div class="hero-dashboard-preview" aria-hidden="true">
    <div class="hdp-stack">
      <article class="hdp-card hdp-main">
        <header class="hdp-main-head">
          <div class="hdp-main-title">
            <span class="hdp-pill">workspace</span>
            <strong>San Jose SRL · Planta 2</strong>
          </div>
          <div class="hdp-main-meta">
            <span class="live-dot"></span>
            <span class="mono">live · 12:48:02</span>
          </div>
        </header>

        <div class="hdp-kpis">
          <div class="hdp-kpi">
            <span class="hdp-kpi-label">Temperatura prensa</span>
            <span class="hdp-kpi-value mono"><span data-counter data-to="71.4" data-decimals="1">0</span><em>°C</em></span>
          <span class="hdp-kpi-delta up"><svg class="trend-icon" viewBox="0 0 12 12" aria-hidden="true"><path d="m2 8 4-4 4 4Z" fill="currentColor"/></svg>1.2°</span>
          </div>
          <div class="hdp-kpi">
            <span class="hdp-kpi-label">Vibración eje B</span>
            <span class="hdp-kpi-value mono"><span data-counter data-to="4.8" data-decimals="1">0</span><em>mm/s</em></span>
          <span class="hdp-kpi-delta flat"><svg class="trend-icon" viewBox="0 0 12 12" aria-hidden="true"><rect x="2" y="5.25" width="8" height="1.5" rx=".75" fill="currentColor"/></svg>estable</span>
          </div>
          <div class="hdp-kpi">
            <span class="hdp-kpi-label">Consumo línea 3</span>
            <span class="hdp-kpi-value mono"><span data-counter data-to="38.2" data-decimals="1">0</span><em>kW</em></span>
          <span class="hdp-kpi-delta down"><svg class="trend-icon" viewBox="0 0 12 12" aria-hidden="true"><path d="m2 4 4 4 4-4Z" fill="currentColor"/></svg>4.6%</span>
          </div>
        </div>

        <div class="hdp-chart" data-chart-live>
          <svg viewBox="0 0 600 180" preserveAspectRatio="none" class="hdp-chart-svg">
            <defs>
              <linearGradient id="lineGrad" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="currentColor" stop-opacity=".25"/>
                <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <g class="hdp-chart-grid">
              <line x1="0" y1="45"  x2="600" y2="45"  /><line x1="0" y1="90"  x2="600" y2="90"  />
              <line x1="0" y1="135" x2="600" y2="135" />
            </g>
            <path class="hdp-chart-area" d=""></path>
            <path class="hdp-chart-line" d=""></path>
            <g class="hdp-chart-dot"><circle r="4"></circle><circle r="9" class="hdp-chart-pulse"></circle></g>
          </svg>
          <div class="hdp-chart-labels mono">
            <span>00:00</span><span>06:00</span><span>12:00</span><span>18:00</span><span>ahora</span>
          </div>
        </div>

        <footer class="hdp-main-foot">
          <div class="hdp-tabs">
            <button class="hdp-tab active">Línea de prensa</button>
            <button class="hdp-tab">Caldera</button>
            <button class="hdp-tab">Extracción</button>
          </div>
          <span class="hdp-foot-meta mono">14 sensores · 3 alertas</span>
        </footer>
      </article>

      <article class="hdp-card hdp-float hdp-alert">
        <header>
          <span class="hdp-alert-icon">!</span>
          <strong>Alerta térmica</strong>
          <span class="mono">12:47</span>
        </header>
        <p>Prensa #4 superó <b>72 °C</b> durante 90s. Revisión recomendada.</p>
        <div class="hdp-alert-actions">
          <button class="hdp-mini">Asignar</button>
          <button class="hdp-mini">Silenciar 30m</button>
        </div>
      </article>

      <article class="hdp-card hdp-float hdp-sensor">
        <div class="hdp-sensor-ico">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M12 4v8l5 3" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round"/></svg>
        </div>
        <div>
          <span class="hdp-sensor-name">DS18B20 · sonda</span>
          <span class="hdp-sensor-meta mono">PRESA-04 · canal A</span>
        </div>
        <span class="hdp-sensor-val mono"><span data-counter data-to="71.4" data-decimals="1">0</span>°</span>
      </article>

      <article class="hdp-card hdp-float hdp-metric">
        <span class="hdp-metric-label">Producción / hora</span>
        <span class="hdp-metric-val mono"><span data-counter data-to="1820" data-decimals="0">0</span><em>u</em></span>
        <span class="hdp-spark"><svg viewBox="0 0 80 24" preserveAspectRatio="none"><polyline points="0,18 8,14 16,17 24,9 32,13 40,6 48,11 56,4 64,8 72,2 80,5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></span>
        <span class="hdp-metric-delta up mono">+12.4%</span>
      </article>

      <article class="hdp-card hdp-float hdp-rec">
        <span class="hdp-rec-tag">Sugerencia</span>
        <p>Suma un <b>sensor de vibración SW-420</b> al eje B para anticipar fallas.</p>
        <button class="hdp-rec-cta">Añadir al workspace <svg class="ui-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h10m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
      </article>
    </div>
  </div>

  <a class="hero-scroll" href="#problema" aria-label="Bajar">
    <span class="mono">scroll</span>
    <span class="hero-scroll-bar"><i></i></span>
  </a>
</section>


<!-- MARQUEE -->
<section class="signals-strip" aria-hidden="true">
  <div class="signals-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span>● temperatura</span><span>● vibración</span><span>● humedad</span><span>● corriente</span>
      <span>● presión</span><span>● proximidad</span><span>● nivel</span><span>● producción</span>
      <span>● mantenimiento</span><span>● energía</span><span>● stock</span><span>● alertas</span>
    <?php endfor; ?>
  </div>
</section>


<!-- ───────────────────────────── PROBLEM ─────────────────────────── -->
<section id="problema" class="problem-section" data-screen-label="02 Problema">
  <div class="section-head">
    <span class="section-eyebrow mono">01 · El estado actual</span>
    <h2 class="section-title">
      La operación industrial se decide
      <span class="muted">a ciegas o a las apuradas.</span>
    </h2>
  </div>

  <div class="problem-grid">
    <article class="problem-card"><span class="problem-num mono">i</span><h3>Datos dispersos</h3><p>Planillas, cuadernos, PLCs sueltos. Nadie ve la planta entera al mismo tiempo.</p></article>
    <article class="problem-card"><span class="problem-num mono">ii</span><h3>Mantenimiento reactivo</h3><p>Se repara cuando ya rompió. Cada parada imprevista cuesta turnos completos.</p></article>
    <article class="problem-card"><span class="problem-num mono">iii</span><h3>Sensores mal elegidos</h3><p>Se compran equipos por catálogo, sin saber si miden lo que la operación necesita.</p></article>
    <article class="problem-card"><span class="problem-num mono">iv</span><h3>Dashboards que no dicen nada</h3><p>Gráficos lindos, decisiones lentas. Los datos están, pero la lectura no llega.</p></article>
    <article class="problem-card"><span class="problem-num mono">v</span><h3>Decisiones sin contexto</h3><p>Sin histórico, sin alertas, sin correlación. La planta funciona por instinto.</p></article>
    <article class="problem-card"><span class="problem-num mono">vi</span><h3>Excel como sistema</h3><p>Una hoja por turno, un archivo por encargado. Lo crítico vive en un .xlsx local.</p></article>
  </div>
</section>


<!-- ───────────────────────────── SOLUTION ───────── -->
<section id="funcionalidades" class="solution-section" data-screen-label="03 Solucion">
  <div class="solution-inner">
    <asíde class="solution-asíde">
      <span class="section-eyebrow mono">02 · La plataforma</span>
      <h2 class="section-title">
        PrevCS entiende que necesitás medir
        <span class="muted">y arma el sistema con vos.</span>
      </h2>
      <p class="section-lede">
        No empezamos por los sensores. Empezamos por tu operación. PrevCS guía cada paso
        — del workspace inicial a la primera alerta en tiempo real.
      </p>

      <ul class="solution-list">
        <li data-step="01"><b>Workspace por empresa.</b> Un espacio configurado a tu medida, no un panel genérico.</li>
        <li data-step="02"><b>Métricas primero.</b> Elegís qué querés saber. PrevCS traduce eso en sensores.</li>
        <li data-step="03"><b>Recomendaciónes inteligentes.</b> Sensores, dashboards y plantillas sugeridos.</li>
        <li data-step="04"><b>Visualización en vivo.</b> Datos reales en tu workspace, no en planillas.</li>
        <li data-step="05"><b>Alertas accionables.</b> Por umbral, tendencia o correlación entre métricas.</li>
        <li data-step="06"><b>Decisiones con histórico.</b> Reportes, comparativas, ciclos de mantenimiento.</li>
      </ul>
    </asíde>

    <div class="solution-canvas">
      <div class="solution-bento">
        <div class="sb sb-1">
          <span class="sb-tag">workspace</span>
          <strong>Tu planta, mapeada.</strong>
          <div class="sb-mini-map">
            <span class="sb-zone" style="--x:8%;--y:18%;">Caldera</span>
            <span class="sb-zone active" style="--x:42%;--y:32%;">Prensa</span>
            <span class="sb-zone" style="--x:72%;--y:22%;">Extracción</span>
            <span class="sb-zone" style="--x:18%;--y:64%;">Estiba</span>
            <span class="sb-zone" style="--x:55%;--y:70%;">Almacén</span>
            <span class="sb-zone" style="--x:82%;--y:58%;">Despacho</span>
            <svg class="sb-lines" viewBox="0 0 100 100" preserveAspectRatio="none">
              <path d="M10,20 C30,30 35,30 44,33"/>
              <path d="M44,33 C58,30 65,25 74,24"/>
              <path d="M44,33 C40,50 22,60 20,64"/>
              <path d="M44,33 C50,55 55,68 57,70"/>
              <path d="M57,70 C70,65 78,60 84,58"/>
            </svg>
          </div>
        </div>

        <div class="sb sb-2">
          <span class="sb-tag">alertas</span>
          <ul class="sb-alerts">
            <li class="warn"><span class="dot"></span> Vibración eje B sobre umbral <em>hace 12s</em></li>
            <li><span class="dot"></span> Stock aceite crudo &lt; 18% <em>hace 4m</em></li>
            <li><span class="dot"></span> Caldera nominal <em>hace 18m</em></li>
          </ul>
        </div>

        <div class="sb sb-3">
          <span class="sb-tag">métricas</span>
          <div class="sb-chips">
            <span class="chip on">Temperatura</span>
            <span class="chip on">Vibración</span>
            <span class="chip">Humedad</span>
            <span class="chip on">Energía</span>
            <span class="chip">Stock</span>
            <span class="chip on">Mantenimiento</span>
            <span class="chip">Producción</span>
            <span class="chip">Presión</span>
          </div>
        </div>

        <div class="sb sb-4">
          <span class="sb-tag">recomendación</span>
          <p>Para <b>vibración + mantenimiento</b> sugerimos:</p>
          <ul class="sb-rec">
            <li>Sensor SW-420 ×3</li>
            <li>Módulo ESP32 gateway</li>
            <li>Plantilla "Mantto. predictivo"</li>
          </ul>
        </div>

        <div class="sb sb-5">
          <span class="sb-tag">histórico</span>
          <svg class="sb-bars" viewBox="0 0 200 70" preserveAspectRatio="none">
            <g>
              <rect x="4"  y="40" width="14" height="26"/>
              <rect x="22" y="28" width="14" height="38"/>
              <rect x="40" y="34" width="14" height="32"/>
              <rect x="58" y="18" width="14" height="48"/>
              <rect x="76" y="22" width="14" height="44"/>
              <rect x="94" y="12" width="14" height="54"/>
              <rect x="112" y="20" width="14" height="46"/>
              <rect x="130" y="8"  width="14" height="58"/>
              <rect x="148" y="14" width="14" height="52"/>
              <rect x="166" y="4"  width="14" height="62"/>
              <rect x="184" y="10" width="14" height="56"/>
            </g>
          </svg>
          <span class="sb-foot mono">+18.4% eficiencia · últimos 30 días</span>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ───────────────────────────── HOW IT WORKS ──────────── -->
<section id="como-funciona" class="how-section" data-screen-label="04 Cómo funciona">
  <div class="section-head section-head-dark">
    <span class="section-eyebrow mono">03 · Cómo funciona</span>
    <h2 class="section-title">
      De una hoja en blanco
      <span class="muted">a una planta monitoreada, en seis pasos.</span>
    </h2>
  </div>

  <ol class="how-track">
    <li class="how-step">
      <span class="how-num mono">01</span><h3>Creá tu cuenta</h3>
      <p>Email, contraseña y listo. Sin onboarding eterno ni llamadas de venta.</p>
      <div class="how-art how-art-1">
        <div class="ha-input"><span class="mono">email</span><span class="cursor">|</span></div>
        <div class="ha-input ha-input-2"><span class="mono">password</span></div>
        <div class="ha-btn">Crear cuenta</div>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">02</span><h3>Armá tu workspace</h3>
      <p>Nombre, rubro, sedes y áreas. Tu planta, así como vos la divides.</p>
      <div class="how-art how-art-2">
        <span class="ha-node">Caldera</span><span class="ha-node n2">Prensa</span>
        <span class="ha-node">Almacén</span><span class="ha-node">Despacho</span>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">03</span><h3>Elegí qué medir</h3>
      <p>Temperatura, vibración, energía, stock. Lo que importa en tu operación.</p>
      <div class="how-art how-art-3">
        <span class="chip on">Temperatura</span><span class="chip on">Vibración</span>
        <span class="chip">Humedad</span><span class="chip on">Energía</span><span class="chip">Producción</span>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">04</span><h3>Recibí sensores sugeridos</h3>
      <p>PrevCS traduce tus métricas en un set concreto de sensores y módulos.</p>
      <div class="how-art how-art-4">
        <div class="ha-pill">DS18B20<span class="mono">temp</span></div>
        <div class="ha-pill">SW-420<span class="mono">vibración</span></div>
        <div class="ha-pill">ESP32<span class="mono">gateway</span></div>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">05</span><h3>Personalizá dashboards</h3>
      <p>Plantillas sugeridas que podés mover, recortar y combinar a tu gusto.</p>
      <div class="how-art how-art-5">
        <span class="ha-tile"></span><span class="ha-tile big"></span><span class="ha-tile"></span>
        <span class="ha-tile"></span><span class="ha-tile wide"></span>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">06</span><h3>Instalá y monitoreá</h3>
      <p>Encendés los sensores, abrís tu workspace y empieza a llegar la data.</p>
      <div class="how-art how-art-6">
        <span class="ha-live"><span class="live-dot"></span><span class="mono">streaming · 12:48</span></span>
        <svg viewBox="0 0 120 40" preserveAspectRatio="none" class="ha-spark"><polyline points="0,30 12,22 24,28 36,16 48,20 60,10 72,18 84,8 96,14 108,4 120,8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
      </div>
    </li>
  </ol>
</section>


<!-- ───────────────────────────── METRICS ─────────── -->
<section id="metricas" class="metrics-section" data-screen-label="05 Métricas" data-gsap-section="metrics">
  <div class="section-head">
    <span class="section-eyebrow mono">04 · Métricas disponibles</span>
    <h2 class="section-title">
      Empezá por lo que querés saber.
      <span class="muted">Los sensores vienen después.</span>
    </h2>
  </div>

  <div class="metrics-grid">
    <?php
    $metricsList = [
      ['Temperatura',   '°C',   'Procesos térmicos, calderas, cámaras de frío, prensado.',         '71.4°C',     1],
      ['Vibración',     'Hz',   'Motores, rodamientos, ejes. Anticipa fallas antes del paro.',     '4.8 mm/s',   1],
      ['Humedad',       '%H',   'Materias primas, depósitos, salas de proceso, cámaras.',          '42%',        0],
      ['Energía',       'kW',   'Consumo por línea, por turno, por máquina. Caza el desperdicio.', '38.2 kW',    1],
      ['Producción',    'u/h',  'Volumen real, rendimiento por turno, comparativas históricas.',   '1820 u/h',   0],
      ['Stock',         'kg',   'Insumos, materia prima, niveles en silos y tanques.',             '74%',        0],
      ['Mantenimiento', '<svg class="metric-glyph-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M15.5 7.5A6 6 0 1 0 16 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m12.5 4.5 3 3 2-3" fill="currentColor"/></svg>', 'Ciclos, horas de uso, próximos servicios, predictivo.', '12 días', 0],
      ['Alertas',       '!',    'Por umbral, tendencia o combinación. WhatsApp, mail o panel.',    '3 activas',  0],
    ];
    foreach ($metricsList as [$name, $glyph, $desc, $val, $dec]):
    ?>
    <article class="metric-card">
      <div class="metric-icon"><span class="mono"><?= $glyph ?></span></div>
      <h3><?= $name ?></h3>
      <p><?= $desc ?></p>
      <span class="metric-val mono"><?= $val ?></span>
    </article>
    <?php endforeach; ?>
  </div>
</section>


<!-- ───────────────────────────── SENSORS ─────────── -->
<section id="sensores" class="sensors-section" data-screen-label="06 Sensores">
  <div class="section-head">
    <span class="section-eyebrow mono">05 · Sensores compatibles</span>
    <h2 class="section-title">
      Una biblioteca de sensores
      <span class="muted">que PrevCS sabe leer.</span>
    </h2>
    <p class="section-lede small">
      No vendemos hardware. Te decimos cuáles necesitás y los conectamos a tu workspace.
    </p>
  </div>

  <div class="sensor-carousel" data-marquee>
    <div class="sensor-track">
      <?php
      $sensors = [
        ['T°', 'DS18B20',  'temperatura · 1-wire', false],
        ['H%', 'DHT22',    'humedad + temp',       false],
        ['~',  'SW-420',   'vibración digital',    false],
        ['A',  'SCT-013',  'corriente AC',         false],
        ['ESP32', 'Módulo principal', 'gateway WiFi/MQTT', true],
        ['<svg class="sensor-glyph-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h10m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'E18-D80', 'proximidad IR', false],
        ['<svg class="sensor-glyph-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 16V6m-4 4 4-4 4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'HC-SR04', 'nivel ultrasonido', false],
        ['N',  'Reed-KY025','magnético',           false],
        ['P',  'BMP280',   'presión',              false],
        ['CO₂','MH-Z19',   'calidad de aire',      false],
      ];
      for ($pass = 0; $pass < 2; $pass++):
        foreach ($sensors as [$g, $name, $meta, $hero]):
      ?>
        <?php if ($hero): ?>
        <article class="sensor-card sensor-hero sensor-hero--esp">
          <div class="sensor-hero__img-wrap">
            <img src="<?= base_url('images/esp32.png') ?>" alt="ESP32" class="sensor-hero__img" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
            <span class="sensor-hero__img-fallback" style="display:none">
              <svg viewBox="0 0 80 80" aria-hidden="true" fill="none">
                <rect x="18" y="18" width="44" height="44" rx="4" fill="#fff" fill-opacity=".18"/>
                <rect x="24" y="24" width="32" height="32" rx="2" fill="#fff" fill-opacity=".1" stroke="#fff" stroke-opacity=".3" stroke-width="1"/>
                <rect x="30" y="30" width="20" height="20" rx="2" fill="#fff" fill-opacity=".85"/>
                <circle cx="40" cy="40" r="6" fill="#E8202F"/>
                <circle cx="40" cy="40" r="3.5" fill="#E8202F" stroke="#fff" stroke-width="1"/>
              </svg>
            </span>
          </div>
          <div>
            <strong><?= $name ?></strong>
            <span class="mono"><?= $meta ?></span>
          </div>
        </article>
        <?php else: ?>
        <article class="sensor-card">
          <span class="s-glyph"><?= $g ?></span>
          <div><strong><?= $name ?></strong><span class="mono"><?= $meta ?></span></div>
        </article>
        <?php endif; ?>
      <?php endforeach; endfor; ?>
    </div>
  </div>
</section>


<!-- ───────────────────────────── RECOMMENDER ─────────── -->
<section class="recommender-section" data-screen-label="07 Recomendador">
  <div class="section-head">
    <span class="section-eyebrow mono">06 · Recomendación inteligente</span>
    <h2 class="section-title">
      Tocá las métricas que te interesan.
      <span class="muted">PrevCS arma tu set.</span>
    </h2>
  </div>

  <div class="recommender" id="recommender">
    <div class="rec-input">
      <span class="rec-label mono">elegí tus métricas</span>
      <div class="rec-chips">
        <button class="chip on" data-rec="temperatura">Temperatura</button>
        <button class="chip on" data-rec="vibracion">Vibración</button>
        <button class="chip" data-rec="humedad">Humedad</button>
        <button class="chip" data-rec="energia">Energía</button>
        <button class="chip" data-rec="produccion">Producción</button>
        <button class="chip" data-rec="stock">Stock</button>
        <button class="chip on" data-rec="mantenimiento">Mantenimiento</button>
        <button class="chip" data-rec="presion">Presión</button>
      </div>
    </div>

    <div class="rec-arrow" aria-hidden="true">
      <svg class="ui-icon" viewBox="0 0 20 20"><path d="M4 10h10m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>

    <div class="rec-output">
      <span class="rec-label mono">PrevCS te recomienda</span>
      <div class="rec-summary">
        <div class="rec-block"><h4>Sensores</h4><p class="rec-list" id="rec-sensors"></p></div>
        <div class="rec-block"><h4>Plantillas</h4><p class="rec-list" id="rec-templates"></p></div>
        <div class="rec-block"><h4>Alertas</h4><p class="rec-list" id="rec-alerts"></p></div>
      </div>
      <a class="l-btn l-btn--solid" href="<?= base_url('reg-log') . '?form=register' ?>">
        Crear cuenta con este set
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </div>
</section>


<!-- ───────────────────────────── TEMPLATES ─────────── -->
<section id="plantillas" class="templates-section" data-screen-label="08 Plantillas">
  <div class="section-head">
    <span class="section-eyebrow mono">07 · Plantillas y gráficos</span>
    <h2 class="section-title">
      No diseñes desde cero.
      <span class="muted">Empezá con una plantilla y editála.</span>
    </h2>
  </div>

  <div class="template-carousel" id="templateCarousel">
    <button class="tc-nav prev" aria-label="Anterior">
      <svg class="ui-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="m12 5-5 5 5 5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="tc-viewport">
      <div class="tc-track">
        <article class="template-card">
          <span class="tc-tag mono">line</span><h4>Histórico de proceso</h4>
          <svg viewBox="0 0 240 100" class="tc-svg" preserveAspectRatio="none">
            <defs><linearGradient id="tg1" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".22"/><stop offset="100%" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
            <path d="M0,72 C30,60 50,68 80,48 C110,28 140,52 170,38 C200,24 220,32 240,28 L240,100 L0,100 Z" fill="url(#tg1)"/>
            <path d="M0,72 C30,60 50,68 80,48 C110,28 140,52 170,38 C200,24 220,32 240,28" fill="none" stroke="currentColor" stroke-width="1.6"/>
          </svg>
          <span class="tc-foot mono">temperatura · 24h</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">bar</span><h4>Producción por turno</h4>
          <svg viewBox="0 0 240 100" class="tc-svg" preserveAspectRatio="none">
            <g fill="currentColor"><rect x="6" y="38" width="20" height="56"/><rect x="34" y="20" width="20" height="74"/><rect x="62" y="46" width="20" height="48"/><rect x="90" y="14" width="20" height="80"/><rect x="118" y="28" width="20" height="66"/><rect x="146" y="36" width="20" height="58"/><rect x="174" y="10" width="20" height="84"/><rect x="202" y="24" width="20" height="70"/></g>
          </svg>
          <span class="tc-foot mono">turnos · semana</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">donut</span><h4>Mezcla de consumo</h4>
          <svg viewBox="0 0 100 100" class="tc-svg tc-donut">
            <circle r="42" cx="50" cy="50" fill="none" stroke="currentColor" stroke-opacity=".1" stroke-width="14"/>
            <circle r="42" cx="50" cy="50" fill="none" stroke="currentColor" stroke-width="14" stroke-dasharray="180 263" transform="rotate(-90 50 50)"/>
            <circle r="42" cx="50" cy="50" fill="none" stroke="currentColor" stroke-opacity=".55" stroke-width="14" stroke-dasharray="50 263" stroke-dashoffset="-180" transform="rotate(-90 50 50)"/>
            <text x="50" y="52" text-anchor="middle" font-size="13" font-family="Geist Mono" fill="currentColor">68%</text>
          </svg>
          <span class="tc-foot mono">energía · línea 3</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">kpi</span><h4>Tarjetas KPI</h4>
          <div class="tc-kpis">
            <div><span class="mono">OEE</span><b>82%</b></div>
            <div><span class="mono">MTBF</span><b>148h</b></div>
            <div><span class="mono">MTTR</span><b>22m</b></div>
            <div><span class="mono">SCRAP</span><b>1.4%</b></div>
          </div>
          <span class="tc-foot mono">indicadores mensuales</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">table</span><h4>Registro de eventos</h4>
          <div class="tc-table mono">
            <div><span>12:47</span><span>vibración B</span><span class="warn"><?= $trendIcon('up') ?></span></div>
            <div><span>12:32</span><span>temp prensa</span><span>—</span></div>
            <div><span>12:11</span><span>stock crudo</span><span class="warn"><?= $trendIcon('down') ?></span></div>
            <div><span>11:58</span><span>caldera</span><span>—</span></div>
          </div>
          <span class="tc-foot mono">últimos eventos</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">alerts</span><h4>Panel de alertas</h4>
          <ul class="tc-alerts">
            <li class="warn"><span class="dot"></span> Vibración eje B</li>
            <li><span class="dot"></span> Caldera nominal</li>
            <li class="crit"><span class="dot"></span> Stock crítico crudo</li>
          </ul>
          <span class="tc-foot mono">en vivo</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">gauge</span><h4>Estado de equipo</h4>
          <svg viewBox="0 0 100 60" class="tc-svg">
            <path d="M10,55 A40,40 0 0 1 90,55" fill="none" stroke="currentColor" stroke-opacity=".15" stroke-width="8" stroke-linecap="round"/>
            <path d="M10,55 A40,40 0 0 1 75,22" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round"/>
            <circle cx="75" cy="22" r="3" fill="currentColor"/>
            <text x="50" y="50" text-anchor="middle" font-size="12" font-family="Geist Mono" fill="currentColor">78%</text>
          </svg>
          <span class="tc-foot mono">prensa #4</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">timeline</span><h4>Mantenimiento</h4>
          <ul class="tc-timeline">
            <li><span class="mono">may 02</span><span>Cambio rodamiento eje B</span></li>
            <li><span class="mono">may 14</span><span>Lubricación prensa #4</span></li>
            <li class="next"><span class="mono">may 28</span><span>Servicio caldera</span></li>
          </ul>
          <span class="tc-foot mono">próximos servicios</span>
        </article>
      </div>
    </div>
    <button class="tc-nav next" aria-label="Siguiente">
      <svg class="ui-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="m8 5 5 5-5 5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
  </div>
</section>


<!-- ───────────────────────────── DASHBOARD PREVIEW ─────────── -->
<section class="dashboard-preview-section" data-screen-label="09 Dashboard preview">
  <div class="dp-bg" aria-hidden="true"></div>

  <div class="section-head section-head-dark">
    <span class="section-eyebrow mono">08 · Preview del workspace</span>
    <h2 class="section-title">
      Así se siente abrir PrevCS
      <span class="muted">un martes a las 12:48.</span>
    </h2>
  </div>

  <div class="dp-stage">
    <header class="dp-bar">
      <div class="dp-bar-left">
        <span class="dp-app-logo"><span></span><span></span><span></span><span class="live"></span></span>
        <span class="dp-bar-ws">San Jose SRL <em class="mono">/ Planta 2</em></span>
      </div>
      <nav class="dp-bar-nav mono">
        <span class="on">Resumen</span><span>Sensores</span><span>Alertas</span><span>Reportes</span><span>Ajustes</span>
      </nav>
      <div class="dp-bar-right">
        <span class="live-dot"></span><span class="mono">14 sensores online</span>
      </div>
    </header>

    <div class="dp-grid">
      <article class="dp-card dp-card-big">
        <header><strong>Temperatura prensa #4</strong><span class="mono">live · 24h</span></header>
        <div class="dp-big-val mono"><span data-counter data-to="71.4" data-decimals="1">0</span>°C <span class="up mono small"><?= $trendIcon('up') ?>1.2°</span></div>
        <svg viewBox="0 0 600 160" preserveAspectRatio="none" class="dp-big-svg">
          <defs><linearGradient id="dpg" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".25"/><stop offset="100%" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
          <path d="M0,120 C50,110 100,118 150,90 C200,60 250,100 300,72 C350,44 400,80 450,58 C500,40 550,52 600,40 L600,160 L0,160 Z" fill="url(#dpg)"/>
          <path d="M0,120 C50,110 100,118 150,90 C200,60 250,100 300,72 C350,44 400,80 450,58 C500,40 550,52 600,40" fill="none" stroke="currentColor" stroke-width="1.8"/>
        </svg>
      </article>
      <article class="dp-card">
        <header><strong>Alertas</strong><span class="mono">3 activas</span></header>
        <ul class="dp-alerts">
          <li class="warn"><span class="dot"></span> Vibración eje B<em class="mono">12:47</em></li>
          <li class="crit"><span class="dot"></span> Stock crudo &lt; 18%<em class="mono">12:42</em></li>
          <li><span class="dot"></span> Caldera nominal<em class="mono">12:30</em></li>
        </ul>
      </article>
      <article class="dp-card dp-kpi">
        <span class="mono">OEE</span>
        <b class="kpi-number"><span data-counter data-to="82" data-decimals="0">0</span><em class="kpi-unit">%</em></b>
        <span class="up mono small"><?= $trendIcon('up') ?>3 pts</span>
      </article>
      <article class="dp-card dp-kpi">
        <span class="mono">Consumo / h</span>
        <b><span data-counter data-to="38.2" data-decimals="1">0</span> <em>kW</em></b>
        <span class="down mono small"><?= $trendIcon('down') ?>4.6%</span>
      </article>
      <article class="dp-card">
        <header><strong>Producción / turno</strong><span class="mono">u/h</span></header>
        <svg viewBox="0 0 240 80" preserveAspectRatio="none" class="dp-bars">
          <g fill="currentColor"><rect x="6" y="34" width="14" height="44"/><rect x="26" y="22" width="14" height="56"/><rect x="46" y="38" width="14" height="40"/><rect x="66" y="14" width="14" height="64"/><rect x="86" y="26" width="14" height="52"/><rect x="106" y="20" width="14" height="58"/><rect x="126" y="10" width="14" height="68"/><rect x="146" y="22" width="14" height="56"/><rect x="166" y="6" width="14" height="72"/><rect x="186" y="18" width="14" height="60"/><rect x="206" y="14" width="14" height="64"/></g>
        </svg>
      </article>
      <article class="dp-card dp-card-wide">
        <header><strong>Sensores activos</strong><span class="mono">canal · valor · tendencia</span></header>
        <ul class="dp-sensors mono">
          <li><span>PRESA-04</span><span>71.4°C</span><span class="up"><?= $trendIcon('up') ?></span></li>
          <li><span>VIBR-B</span><span>4.8 mm/s</span><span class="warn"><?= $trendIcon('up') ?></span></li>
          <li><span>ENER-L3</span><span>38.2 kW</span><span class="down"><?= $trendIcon('down') ?></span></li>
          <li><span>HUM-D</span><span>42%</span><span>—</span></li>
          <li><span>PROD-A</span><span>1820 u/h</span><span class="up"><?= $trendIcon('up') ?></span></li>
          <li><span>STOCK-X</span><span>74%</span><span class="down"><?= $trendIcon('down') ?></span></li>
        </ul>
      </article>
    </div>
  </div>
</section>


<!-- ───────────────────────────── CASES ─────────── -->
<section id="casos" class="cases-section" data-screen-label="10 Casos de uso">
  <div class="section-head">
    <span class="section-eyebrow mono">09 · Casos de uso</span>
    <h2 class="section-title">
      Una plataforma.
      <span class="muted">Muchas industrias.</span>
    </h2>
  </div>

  <div class="cases-grid">
    <?php
    $cases = [
      ['01', 'Aceiteras', 'Prensado, extraccion, calderas, cámaras. Control térmico y predictivo de motores.', ['temperatura', 'vibración', 'energía']],
      ['02', 'Agroindustria', 'Silos, secaderos, balanzas. Humedad, niveles y consumo de gas o energía.', ['humedad', 'nivel', 'energía']],
      ['03', 'Manufactura', 'Líneas de producción, OEE en vivo, paradas, mantenimiento preventivo.', ['producción', 'mantenimiento', 'alertas']],
      ['04', 'Logística', 'Cámaras de frío, depósitos, tiempos por sector, control de proximidad.', ['temperatura', 'proximidad', 'stock']],
      ['05', 'Talleres y metalmecánica', 'Máquinas-herramienta, compresores, hornos. Vibración, energía y horas de uso.', ['vibración', 'energía', 'mantenimiento']],
      ['06', 'Plantas alimenticias', 'Camaras, hornos, mezcladores, lavadoras. Temperatura y trazabilidad de proceso.', ['temperatura', 'humedad', 'producción']],
    ];
    foreach ($cases as [$num, $name, $desc, $tags]):
    ?>
    <article class="case-card">
      <span class="case-num mono"><?= $num ?></span>
      <h3><?= $name ?></h3>
      <p><?= $desc ?></p>
      <ul class="case-tags mono"><?php foreach ($tags as $t): ?><li><?= $t ?></li><?php endforeach; ?></ul>
    </article>
    <?php endforeach; ?>
  </div>

  <asíde class="case-example">
    <div class="ce-left">
      <span class="section-eyebrow mono">ejemplo</span>
      <h3>San Jose SRL · aceitera</h3>
      <p>
        San Jose SRL no es PrevCS — es un ejemplo de empresa cliente con su propio workspace
        dentro de la plataforma. Eligieron 4 métricas, conectaron 14 sensores y armaron 6 dashboards.
      </p>
      <ul class="ce-stats mono">
        <li><b><span data-counter data-to="14" data-decimals="0">0</span></b><span>sensores activos</span></li>
        <li><b><span data-counter data-to="6" data-decimals="0">0</span></b><span>dashboards</span></li>
        <li><b class="stat-number"><span data-counter data-to="18" data-decimals="0">0</span><em>%</em></b><span>menos paradas</span></li>
      </ul>
    </div>
    <div class="ce-right">
      <div class="ce-tag mono"><span>workspace cliente</span><span class="live-dot"></span></div>
      <div class="ce-board">
        <div class="ce-row"><span>Caldera</span><span class="mono">98.2°C</span><span class="up"><?= $trendIcon('up') ?></span></div>
        <div class="ce-row"><span>Prensa #4</span><span class="mono">71.4°C</span><span class="up"><?= $trendIcon('up') ?></span></div>
        <div class="ce-row"><span>Vibración B</span><span class="mono">4.8 mm/s</span><span class="warn"><?= $trendIcon('up') ?></span></div>
        <div class="ce-row"><span>Stock crudo</span><span class="mono">74%</span><span class="down"><?= $trendIcon('down') ?></span></div>
        <div class="ce-row"><span>Energía L3</span><span class="mono">38.2 kW</span><span class="down"><?= $trendIcon('down') ?></span></div>
      </div>
    </div>
  </asíde>
</section>


<!-- ───────────────────────────── PRICING ─────────── -->
<section id="precio" class="pricing-section" data-screen-label="11 Precio">
  <div class="section-head" style="text-align:center;align-items:center;">
    <span class="section-eyebrow mono">10 · Precio</span>
    <h2 class="section-title" style="text-align:center;">
      Un solo plan.
      <span class="muted">Todo incluido.</span>
    </h2>
  </div>

  <div class="pricing-wrap">
    <article class="price-card" data-anim>
      <span class="price-badge">
        <span class="dot" aria-hidden="true"></span>
        Plan único
      </span>

      <div class="price-head">
        <span class="price-plan-name">PrevCS Pro</span>
        <span class="price-plan-desc">
          Todo lo que tu planta necesita para monitorear en tiempo real, sin límites ni sorpresas.
        </span>
      </div>

      <div class="price-amount">
        <span class="price-currency"><?= esc($planCurrency ?? 'ARS') ?>&nbsp;$</span>
        <span class="price-value" data-to="<?= esc((string) (int) round($planAmount ?? 0), 'attr') ?>"><?= esc($planAmountLabel ?? '') ?></span>
        <span class="price-period">/ mes</span>
      </div>

      <div class="price-divider" aria-hidden="true"></div>

      <ul class="price-features">
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Workspaces y sedes <b>ilimitados</b>
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Sensores ESP32 <b>sin tope</b> por espacio
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Alertas en tiempo real por umbral y tendencia
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Dashboards y plantillas personalizables
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Histórico ilimitado y soporte prioritario
        </li>
      </ul>

      <a class="l-btn l-btn--solid l-btn--lg price-cta" href="<?= base_url('pago') ?>">
        Comprar ahora
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <span class="price-note">Sin permanencia · cancelás cuando quieras</span>
    </article>
  </div>
</section>


<!-- ───────────────────────────── CTA ─────────── -->
<section class="cta-section" data-screen-label="12 CTA final">
  <div class="cta-inner">
    <span class="section-eyebrow mono">10 · Empezar</span>
    <h2 class="cta-title">
      Construí un sistema de monitoreo
      <span class="muted">adaptado a tu industria.</span>
    </h2>
    <p class="cta-sub">
      Hacés el primer workspace en cinco minutos. Elegí métricas, recibí recomendaciones,
      ponelo en marcha.
    </p>
    <div class="hero-actions center">
      <a class="l-btn l-btn--solid l-btn--lg" href="<?= base_url('reg-log') . '?form=register' ?>">
        Crear cuenta
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a class="l-btn l-btn--line l-btn--lg" href="#como-funciona">
        <span class="l-play-dot"></span> Ver demo
      </a>
    </div>
    <div class="cta-marquee" aria-hidden="true">
      <div class="cta-track">
        <?php for ($i = 0; $i < 2; $i++): ?>
          <span>monitoreo industrial</span><span>·</span>
          <span>sensores recomendados</span><span>·</span>
          <span>dashboards en vivo</span><span>·</span>
          <span>alertas accionables</span><span>·</span>
          <span>workspaces personalizados</span><span>·</span>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</section>

</main>

<!-- ───────────────────────────── FOOTER ───────────── -->
<footer id="contacto" class="footer" data-screen-label="13 Footer">
  <div class="ft-top">
    <div class="ft-brand">
      <a class="hd-logo" href="#top" aria-label="PrevCS, inicio">
        <span class="hd-logo-mark"><span class="hd-logo-dot"></span><span class="hd-logo-dot"></span><span class="hd-logo-dot"></span><span class="hd-logo-dot live"></span></span>
        <span>PrevCS</span>
      </a>
      <p>Plataforma de monitoreo industrial personalizada. Métricas primero, sensores después.</p>
      <form class="ft-news" onsubmit="event.preventDefault(); this.querySelector('button').textContent='Listo ✓';">
        <input type="email" placeholder="tu@empresa.com" aria-label="email"/>
        <button type="submit">Avisame del lanzamiento <svg class="ui-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h10m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
      </form>
    </div>

    <div class="ft-cols">
      <div>
        <h5>Plataforma</h5>
        <a href="#funcionalidades">Funcionalidades</a>
        <a href="#como-funciona">Cómo funciona</a>
        <a href="#metricas">Métricas</a>
        <a href="#plantillas">Plantillas</a>
      </div>
      <div>
        <h5>Sensores</h5>
        <a href="#sensores">Compatibles</a>
        <a href="#sensores">Gateway ESP32</a>
        <a href="#sensores">Protocolos</a>
        <a href="#sensores">Documentación</a>
      </div>
      <div>
        <h5>Casos</h5>
        <a href="#casos">Aceiteras</a>
        <a href="#casos">Agroindustria</a>
        <a href="#casos">Manufactura</a>
        <a href="#casos">Logística</a>
      </div>
      <div>
        <h5>Contacto</h5>
        <a href="mailto:hola@prevcs.com">hola@prevcs.com</a>
        <a href="#">LinkedIn</a>
        <a href="#">Instagram</a>
        <a href="#">Soporte</a>
      </div>
    </div>
  </div>

  <div class="ft-bottom">
    <span class="mono">© <?= date('Y') ?> PrevCS · todos los derechos reservados</span>
    <span class="mono">Medir es prevenir</span>
  </div>
</footer>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
<script src="<?= base_url('js/landing.js') ?>?v=20260608-9" defer></script>
<script src="<?= base_url('js/pricing.js') ?>?v=20260610-1" defer></script>
<?= $this->endSection() ?>
