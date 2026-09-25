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
      <a href="<?= base_url() ?>#plantillas" data-nav-section="plantillas">Vistas</a>
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
      Monitoreá tu industria con un sistema
      <span class="hero-title-em">diseñado según tus métricas.</span>
    </h1>

    <p class="hero-sub">
      PrevCS conecta placas ESP32 con sensores de temperatura, vibración y RPM a un panel
      web. Creás un espacio para tu planta, elegís qué medir y te avisa cuando un motor
      sale del rango que definiste.
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
      <li><span>ESP32 + WiFi</span></li>
      <li><span>Alertas por umbral</span></li>
      <li><span>Historial en CSV</span></li>
      <li><span>Espacios ilimitados</span></li>
    </ul>
  </div>

  <!-- Floating dashboard composition -->
  <div class="hero-dashboard-preview" aria-hidden="true">
    <div class="hdp-stack">
      <article class="hdp-card hdp-main">
        <header class="hdp-main-head">
          <div class="hdp-main-title">
            <span class="hdp-pill">ejemplo</span>
            <strong>Espacio · Prensas</strong>
          </div>
          <div class="hdp-main-meta">
            <span class="live-dot"></span>
            <span class="mono">live · 12:48:02</span>
          </div>
        </header>

        <div class="hdp-kpis">
          <div class="hdp-kpi">
            <span class="hdp-kpi-label">Temperatura motor</span>
            <span class="hdp-kpi-value mono"><span data-counter data-to="71.4" data-decimals="1">0</span><em>°C</em></span>
          <span class="hdp-kpi-delta up"><svg class="trend-icon" viewBox="0 0 12 12" aria-hidden="true"><path d="m2 8 4-4 4 4Z" fill="currentColor"/></svg>máx. 60</span>
          </div>
          <div class="hdp-kpi">
            <span class="hdp-kpi-label">Vibración</span>
            <span class="hdp-kpi-value mono"><span data-counter data-to="12" data-decimals="0">0</span><em>eventos</em></span>
          <span class="hdp-kpi-delta flat"><svg class="trend-icon" viewBox="0 0 12 12" aria-hidden="true"><rect x="2" y="5.25" width="8" height="1.5" rx=".75" fill="currentColor"/></svg>normal</span>
          </div>
          <div class="hdp-kpi">
            <span class="hdp-kpi-label">RPM</span>
            <span class="hdp-kpi-value mono"><span data-counter data-to="1450" data-decimals="0">0</span><em>rev/min</em></span>
          <span class="hdp-kpi-delta flat"><svg class="trend-icon" viewBox="0 0 12 12" aria-hidden="true"><rect x="2" y="5.25" width="8" height="1.5" rx=".75" fill="currentColor"/></svg>andando</span>
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
            <button class="hdp-tab active">Temperatura</button>
            <button class="hdp-tab">Vibración</button>
            <button class="hdp-tab">RPM</button>
          </div>
          <span class="hdp-foot-meta mono">3 sensores · 1 alerta</span>
        </footer>
      </article>

      <article class="hdp-card hdp-float hdp-alert">
        <header>
          <span class="hdp-alert-icon">!</span>
          <strong>Temperatura por encima del límite</strong>
          <span class="mono">12:47</span>
        </header>
        <p>El motor de la prensa marcó <b>71.4 °C</b>, arriba del máximo de 60 °C. Se avisó a todo el espacio.</p>
        <div class="hdp-alert-actions">
          <button class="hdp-mini">Ver placa</button>
          <button class="hdp-mini">Ver historial</button>
        </div>
      </article>

      <article class="hdp-card hdp-float hdp-sensor">
        <div class="hdp-sensor-ico">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M12 4v8l5 3" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round"/></svg>
        </div>
        <div>
          <span class="hdp-sensor-name">DS18B20 · sonda</span>
          <span class="hdp-sensor-meta mono">ESP32 Prensas · GPIO27</span>
        </div>
        <span class="hdp-sensor-val mono"><span data-counter data-to="71.4" data-decimals="1">0</span>°</span>
      </article>

      <article class="hdp-card hdp-float hdp-metric">
        <span class="hdp-metric-label">Lecturas 24 h</span>
        <span class="hdp-metric-val mono"><span data-counter data-to="4320" data-decimals="0">0</span><em>u</em></span>
        <span class="hdp-spark"><svg viewBox="0 0 80 24" preserveAspectRatio="none"><polyline points="0,18 8,14 16,17 24,9 32,13 40,6 48,11 56,4 64,8 72,2 80,5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></span>
        <span class="hdp-metric-delta up mono">cada 60 s</span>
      </article>

      <article class="hdp-card hdp-float hdp-rec">
        <span class="hdp-rec-tag">Stock</span>
        <p>Quedan <b>2 rodamientos 6204</b>, en el mínimo tolerable. Se avisó al espacio.</p>
        <button class="hdp-rec-cta">Ver stock <svg class="ui-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h10m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
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
      <span>● temperatura</span><span>● vibración</span><span>● rpm</span><span>● alertas</span>
      <span>● historial</span><span>● csv</span><span>● stock</span><span>● tareas</span>
      <span>● calendario</span><span>● notificaciones</span><span>● roles</span><span>● esp32</span>
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
    <aside class="solution-aside">
      <span class="section-eyebrow mono">02 · La plataforma</span>
      <h2 class="section-title">
        PrevCS entiende que necesitás medir
        <span class="muted">y arma el sistema con vos.</span>
      </h2>
      <p class="section-lede">
        No empezamos por los sensores. Empezamos por tu operación. PrevCS te acompaña
        desde el espacio inicial hasta la primera alerta.
      </p>

      <ul class="solution-list">
        <li data-step="01"><b>Espacio por empresa.</b> Cada planta o sector tiene su espacio, con sus placas, su equipo y sus datos.</li>
        <li data-step="02"><b>Métricas primero.</b> Al crear el espacio elegís qué medir y cada ESP32 nueva nace con esos sensores.</li>
        <li data-step="03"><b>Visualización en vivo.</b> Gráficos que se actualizan solos cada 15 segundos, con filtro por fechas.</li>
        <li data-step="04"><b>Alertas por umbral.</b> Definís el mínimo y el máximo de cada sensor. Si una lectura se sale, avisa a todo el equipo y la placa suena.</li>
        <li data-step="05"><b>Historial exportable.</b> Todas las lecturas quedan guardadas y se descargan en CSV.</li>
        <li data-step="06"><b>Mantenimiento en un lugar.</b> Tablero de tareas por máquina, stock de repuestos con avisos y calendario con recordatorios.</li>
      </ul>
    </aside>

    <div class="solution-canvas">
      <div class="solution-bento">
        <div class="sb sb-1">
          <span class="sb-tag">espacio</span>
          <strong>Tu planta, por espacios.</strong>
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
            <li class="warn"><span class="dot"></span> Vibración por encima del límite <em>hace 12s</em></li>
            <li><span class="dot"></span> Rodamientos en el mínimo de stock <em>hace 4m</em></li>
            <li><span class="dot"></span> Temperatura volvió a la normalidad <em>hace 18m</em></li>
          </ul>
        </div>

        <div class="sb sb-3">
          <span class="sb-tag">métricas</span>
          <div class="sb-chips">
            <span class="chip on">Temperatura</span>
            <span class="chip on">Vibración</span>
            <span class="chip on">RPM</span>
            <span class="chip">Stock</span>
            <span class="chip">Tareas</span>
            <span class="chip">Calendario</span>
          </div>
        </div>

        <div class="sb sb-4">
          <span class="sb-tag">kit por máquina</span>
          <p>Lo que lleva cada <b>placa PrevCS</b>:</p>
          <ul class="sb-rec">
            <li>ESP32 con LCD y buzzer</li>
            <li>DS18B20 · SW-420 · LM393</li>
            <li>Vinculación por código QR</li>
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
          <span class="sb-foot mono">una lectura por minuto · exportable a CSV</span>
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
      <p>Email y contraseña. Confirmás el correo con un código y listo.</p>
      <div class="how-art how-art-1">
        <div class="ha-input"><span class="mono">email</span><span class="cursor">|</span></div>
        <div class="ha-input ha-input-2"><span class="mono">password</span></div>
        <div class="ha-btn">Crear cuenta</div>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">02</span><h3>Armá tu espacio</h3>
      <p>Empresa, nombre y sector. Después sumás a tu equipo con una invitación por correo.</p>
      <div class="how-art how-art-2">
        <span class="ha-node">Caldera</span><span class="ha-node n2">Prensa</span>
        <span class="ha-node">Almacén</span><span class="ha-node">Despacho</span>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">03</span><h3>Elegí qué medir</h3>
      <p>Temperatura, vibración y RPM. Cada placa que sumes nace con esos sensores.</p>
      <div class="how-art how-art-3">
        <span class="chip on">Temperatura</span><span class="chip on">Vibración</span>
        <span class="chip on">RPM</span>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">04</span><h3>Vinculá la ESP32</h3>
      <p>Escaneás el QR, la placa se conecta a tu WiFi y se vincula sola con su token.</p>
      <div class="how-art how-art-4">
        <div class="ha-pill">DS18B20<span class="mono">temp</span></div>
        <div class="ha-pill">SW-420<span class="mono">vibración</span></div>
        <div class="ha-pill">LM393<span class="mono">rpm</span></div>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">05</span><h3>Definí los límites</h3>
      <p>Mínimo y máximo por sensor. Si una lectura se sale, la web avisa y la placa suena.</p>
      <div class="how-art how-art-5">
        <span class="ha-tile"></span><span class="ha-tile big"></span><span class="ha-tile"></span>
        <span class="ha-tile"></span><span class="ha-tile wide"></span>
      </div>
    </li>
    <li class="how-step">
      <span class="how-num mono">06</span><h3>Instalá y monitoreá</h3>
      <p>Encendés la placa y cada minuto llega un lote de lecturas a tu panel.</p>
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
    <span class="section-eyebrow mono">04 · Qué mide y qué gestiona</span>
    <h2 class="section-title">
      Empezá por lo que querés saber.
      <span class="muted">Esto es lo que PrevCS hace hoy.</span>
    </h2>
  </div>

  <div class="metrics-grid">
    <?php
    $metricsList = [
      ['Temperatura',   '°C',   'Sonda DS18B20 en la carcasa del motor. Detecta recalentamientos.',          '71.4 °C',        1],
      ['Vibración',     '~',    'Sensor SW-420: cuenta los eventos de vibración en cada ventana de medición.', '12 eventos',     1],
      ['RPM',           'rpm',  'Sensor LM393 sobre el eje: velocidad y si el motor está andando o detenido.', '1450 rev/min',   1],
      ['Alertas',       '!',    'Mínimo y máximo por sensor. Avisa en la campana del panel, en el LCD y con el buzzer.', 'por umbral', 0],
      ['Historial',     'csv',  'Gráficos con filtro por fecha y descarga de todas las lecturas en CSV.',       '1 por minuto',   0],
      ['Stock',         'u',    'Tablas de repuestos por espacio, importables desde Excel, con mínimos y avisos.', '2 u · mínimo', 0],
      ['Mantenimiento', '<svg class="metric-glyph-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M15.5 7.5A6 6 0 1 0 16 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m12.5 4.5 3 3 2-3" fill="currentColor"/></svg>', 'Tablero de tareas por máquina y calendario con recordatorios.', '3 tareas', 0],
      ['Equipo',        '@',    'Invitaciones por correo, roles de admin y supervisor, permisos por placa.',   'roles',          0],
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
    <span class="section-eyebrow mono">05 · El kit de sensores</span>
    <h2 class="section-title">
      Componentes simples,
      <span class="muted">que PrevCS ya sabe leer.</span>
    </h2>
    <p class="section-lede small">
      Cada placa usa piezas fáciles de conseguir. El firmware viene listo y se configura desde el celular.
    </p>
  </div>

  <div class="sensor-carousel" data-marquee>
    <div class="sensor-track">
      <?php
      $sensors = [
        ['T°', 'DS18B20',  'temperatura · 1-wire',   false],
        ['~',  'SW-420',   'vibración digital',      false],
        ['rpm','LM393',    'velocidad del eje',      false],
        ['ESP32', 'Placa principal', 'WiFi · envía cada 60 s', true],
        ['LCD','LCD1602',  'estado en pantalla · I2C', false],
        ['!',  'Buzzer',   'aviso sonoro de alertas', false],
      ];
      for ($pass = 0; $pass < 2; $pass++):
        foreach ($sensors as [$g, $name, $meta, $hero]):
      ?>
        <?php if ($hero): ?>
        <article class="sensor-card sensor-hero sensor-hero--esp">
          <div class="sensor-hero__img-wrap">
            <span class="sensor-hero__img-fallback" style="display:grid">
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
    <span class="section-eyebrow mono">06 · Armá tu kit</span>
    <h2 class="section-title">
      Tocá las métricas que te interesan.
      <span class="muted">Te mostramos qué necesitás.</span>
    </h2>
  </div>

  <div class="recommender" id="recommender">
    <div class="rec-input">
      <span class="rec-label mono">elegí tus métricas</span>
      <div class="rec-chips">
        <button class="chip on" data-rec="temperatura">Temperatura</button>
        <button class="chip on" data-rec="vibracion">Vibración</button>
        <button class="chip" data-rec="rpm">RPM</button>
      </div>
    </div>

    <div class="rec-arrow" aria-hidden="true">
      <svg class="ui-icon" viewBox="0 0 20 20"><path d="M4 10h10m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>

    <div class="rec-output">
      <span class="rec-label mono">tu kit PrevCS</span>
      <div class="rec-summary">
        <div class="rec-block"><h4>Componentes</h4><p class="rec-list" id="rec-sensors"></p></div>
        <div class="rec-block"><h4>Lo que vas a ver</h4><p class="rec-list" id="rec-templates"></p></div>
        <div class="rec-block"><h4>Alertas</h4><p class="rec-list" id="rec-alerts"></p></div>
      </div>
      <a class="l-btn l-btn--solid" href="<?= base_url('reg-log') . '?form=register' ?>">
        Crear cuenta
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </div>
</section>


<!-- ───────────────────────────── TEMPLATES ─────────── -->
<section id="plantillas" class="templates-section" data-screen-label="08 Plantillas">
  <div class="section-head">
    <span class="section-eyebrow mono">07 · Vistas del panel</span>
    <h2 class="section-title">
      Nada que diseñar.
      <span class="muted">Cada vista viene armada.</span>
    </h2>
  </div>

  <div class="template-carousel" id="templateCarousel">
    <button class="tc-nav prev" aria-label="Anterior">
      <svg class="ui-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="m12 5-5 5 5 5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="tc-viewport">
      <div class="tc-track">
        <article class="template-card">
          <span class="tc-tag mono">line</span><h4>Gráfico por sensor</h4>
          <svg viewBox="0 0 240 100" class="tc-svg" preserveAspectRatio="none">
            <defs><linearGradient id="tg1" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".22"/><stop offset="100%" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
            <path d="M0,72 C30,60 50,68 80,48 C110,28 140,52 170,38 C200,24 220,32 240,28 L240,100 L0,100 Z" fill="url(#tg1)"/>
            <path d="M0,72 C30,60 50,68 80,48 C110,28 140,52 170,38 C200,24 220,32 240,28" fill="none" stroke="currentColor" stroke-width="1.6"/>
          </svg>
          <span class="tc-foot mono">temperatura · filtro por fecha</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">stock</span><h4>Stock de repuestos</h4>
          <div class="tc-table mono">
            <div><span>6204</span><span>rodamientos</span><span class="warn">2 u</span></div>
            <div><span>A-42</span><span>correas</span><span>9 u</span></div>
            <div><span>SAE40</span><span>aceite</span><span>18 l</span></div>
            <div><span>F-10</span><span>filtros</span><span>6 u</span></div>
          </div>
          <span class="tc-foot mono">importable desde excel</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">kpi</span><h4>Panel principal</h4>
          <div class="tc-kpis">
            <div><span class="mono">ESPACIOS</span><b>2</b></div>
            <div><span class="mono">ESP32</span><b>4</b></div>
            <div><span class="mono">LECTURAS 24H</span><b>4320</b></div>
            <div><span class="mono">ALERTAS</span><b>1</b></div>
          </div>
          <span class="tc-foot mono">resumen de tus espacios</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">table</span><h4>Historial de lecturas</h4>
          <div class="tc-table mono">
            <div><span>12:47</span><span>71.4 °C</span><span class="warn"><?= $trendIcon('up') ?></span></div>
            <div><span>12:46</span><span>58.9 °C</span><span>—</span></div>
            <div><span>12:45</span><span>57.2 °C</span><span>—</span></div>
            <div><span>12:44</span><span>56.8 °C</span><span>—</span></div>
          </div>
          <span class="tc-foot mono">descargable en csv</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">alerts</span><h4>Campana de avisos</h4>
          <ul class="tc-alerts">
            <li class="crit"><span class="dot"></span> Temperatura sobre el límite</li>
            <li class="warn"><span class="dot"></span> Rodamientos en el mínimo</li>
            <li><span class="dot"></span> Recordatorio: service motor</li>
          </ul>
          <span class="tc-foot mono">para todo el espacio</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">estado</span><h4>Estado del motor</h4>
          <svg viewBox="0 0 100 60" class="tc-svg">
            <path d="M10,55 A40,40 0 0 1 90,55" fill="none" stroke="currentColor" stroke-opacity=".15" stroke-width="8" stroke-linecap="round"/>
            <path d="M10,55 A40,40 0 0 1 75,22" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round"/>
            <circle cx="75" cy="22" r="3" fill="currentColor"/>
            <text x="50" y="50" text-anchor="middle" font-size="11" font-family="Geist Mono" fill="currentColor">normal</text>
          </svg>
          <span class="tc-foot mono">según el rango del sensor</span>
        </article>
        <article class="template-card">
          <span class="tc-tag mono">calendario</span><h4>Mantenimiento</h4>
          <ul class="tc-timeline">
            <li><span class="mono">may 02</span><span>Cambio rodamiento eje B</span></li>
            <li><span class="mono">may 14</span><span>Lubricación prensa #4</span></li>
            <li class="next"><span class="mono">may 28</span><span>Service motor extractor</span></li>
          </ul>
          <span class="tc-foot mono">con recordatorios</span>
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
    <span class="section-eyebrow mono">08 · Así se ve un espacio</span>
    <h2 class="section-title">
      Así se siente abrir PrevCS
      <span class="muted">un martes a las 12:48.</span>
    </h2>
  </div>

  <div class="dp-stage">
    <header class="dp-bar">
      <div class="dp-bar-left">
        <span class="dp-app-logo"><span></span><span></span><span></span><span class="live"></span></span>
        <span class="dp-bar-ws">Espacio de ejemplo <em class="mono">/ Prensas</em></span>
      </div>
      <nav class="dp-bar-nav mono">
        <span class="on">Panel</span><span>Mis espacios</span><span>Stock</span><span>Calendario</span><span>Ayuda</span>
      </nav>
      <div class="dp-bar-right">
        <span class="live-dot"></span><span class="mono">2 placas online</span>
      </div>
    </header>

    <div class="dp-grid">
      <article class="dp-card dp-card-big">
        <header><strong>Temperatura · motor prensa</strong><span class="mono">en vivo</span></header>
        <div class="dp-big-val mono"><span data-counter data-to="71.4" data-decimals="1">0</span>°C <span class="up mono small"><?= $trendIcon('up') ?>1.2°</span></div>
        <svg viewBox="0 0 600 160" preserveAspectRatio="none" class="dp-big-svg">
          <defs><linearGradient id="dpg" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".25"/><stop offset="100%" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
          <path d="M0,120 C50,110 100,118 150,90 C200,60 250,100 300,72 C350,44 400,80 450,58 C500,40 550,52 600,40 L600,160 L0,160 Z" fill="url(#dpg)"/>
          <path d="M0,120 C50,110 100,118 150,90 C200,60 250,100 300,72 C350,44 400,80 450,58 C500,40 550,52 600,40" fill="none" stroke="currentColor" stroke-width="1.8"/>
        </svg>
      </article>
      <article class="dp-card">
        <header><strong>Notificaciones</strong><span class="mono">3 nuevas</span></header>
        <ul class="dp-alerts">
          <li class="crit"><span class="dot"></span> Temperatura sobre el límite<em class="mono">12:47</em></li>
          <li class="warn"><span class="dot"></span> Rodamientos en el mínimo<em class="mono">12:42</em></li>
          <li><span class="dot"></span> Vibración volvió a la normalidad<em class="mono">12:30</em></li>
        </ul>
      </article>
      <article class="dp-card dp-kpi">
        <span class="mono">Lecturas 24 h</span>
        <b class="kpi-number"><span data-counter data-to="4320" data-decimals="0">0</span></b>
        <span class="up mono small"><?= $trendIcon('up') ?>1 por minuto</span>
      </article>
      <article class="dp-card dp-kpi">
        <span class="mono">RPM motor</span>
        <b><span data-counter data-to="1450" data-decimals="0">0</span> <em>rev/min</em></b>
        <span class="up mono small"><?= $trendIcon('up') ?>andando</span>
      </article>
      <article class="dp-card">
        <header><strong>Vibración · últimas mediciones</strong><span class="mono">eventos</span></header>
        <svg viewBox="0 0 240 80" preserveAspectRatio="none" class="dp-bars">
          <g fill="currentColor"><rect x="6" y="34" width="14" height="44"/><rect x="26" y="22" width="14" height="56"/><rect x="46" y="38" width="14" height="40"/><rect x="66" y="14" width="14" height="64"/><rect x="86" y="26" width="14" height="52"/><rect x="106" y="20" width="14" height="58"/><rect x="126" y="10" width="14" height="68"/><rect x="146" y="22" width="14" height="56"/><rect x="166" y="6" width="14" height="72"/><rect x="186" y="18" width="14" height="60"/><rect x="206" y="14" width="14" height="64"/></g>
        </svg>
      </article>
      <article class="dp-card dp-card-wide">
        <header><strong>Sensores recientes</strong><span class="mono">placa · valor · tendencia</span></header>
        <ul class="dp-sensors mono">
          <li><span>PRENSA · TEMP</span><span>71.4 °C</span><span class="up"><?= $trendIcon('up') ?></span></li>
          <li><span>PRENSA · VIBR</span><span>12 eventos</span><span class="warn"><?= $trendIcon('up') ?></span></li>
          <li><span>PRENSA · RPM</span><span>1450 rev/min</span><span>—</span></li>
          <li><span>EXTRACTOR · TEMP</span><span>48.2 °C</span><span class="down"><?= $trendIcon('down') ?></span></li>
          <li><span>EXTRACTOR · VIBR</span><span>3 eventos</span><span>—</span></li>
          <li><span>EXTRACTOR · RPM</span><span>0 rev/min</span><span class="down"><?= $trendIcon('down') ?></span></li>
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
      ['01', 'Aceiteras', 'Prensas, extractores y bombas. Temperatura y vibración de los motores para anticipar fallas.', ['temperatura', 'vibración', 'rpm']],
      ['02', 'Agroindustria', 'Secaderos, norias y cintas movidas por motores eléctricos: saber si andan y si calientan de más.', ['temperatura', 'rpm', 'alertas']],
      ['03', 'Manufactura', 'Líneas de producción: estado de cada motor, tareas de mantenimiento y repuestos en stock.', ['vibración', 'tareas', 'stock']],
      ['04', 'Logística', 'Cintas transportadoras y equipos de depósito, con avisos a todo el equipo cuando algo se sale de rango.', ['rpm', 'temperatura', 'alertas']],
      ['05', 'Talleres y metalmecánica', 'Máquinas-herramienta y compresores: vibración, marcha del motor y services agendados.', ['vibración', 'rpm', 'calendario']],
      ['06', 'Plantas alimenticias', 'Mezcladores, bombas y hornos: control térmico de motores con historial exportable.', ['temperatura', 'historial', 'alertas']],
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

  <aside class="case-example">
    <div class="ce-left">
      <span class="section-eyebrow mono">ejemplo ilustrativo</span>
      <h3>Una aceitera con PrevCS</h3>
      <p>
        No es un cliente real: es un ejemplo de cómo se organizaría una aceitera. Dos espacios
        (prensas y extracción), una placa ESP32 por motor y los tres sensores del kit en cada una.
      </p>
      <ul class="ce-stats mono">
        <li><b><span data-counter data-to="2" data-decimals="0">0</span></b><span>espacios</span></li>
        <li><b><span data-counter data-to="4" data-decimals="0">0</span></b><span>placas ESP32</span></li>
        <li><b><span data-counter data-to="12" data-decimals="0">0</span></b><span>sensores</span></li>
      </ul>
    </div>
    <div class="ce-right">
      <div class="ce-tag mono"><span>espacio de ejemplo</span><span class="live-dot"></span></div>
      <div class="ce-board">
        <div class="ce-row"><span>Prensa #4 · temp.</span><span class="mono">71.4 °C</span><span class="up"><?= $trendIcon('up') ?></span></div>
        <div class="ce-row"><span>Prensa #4 · vibración</span><span class="mono">12 eventos</span><span class="warn"><?= $trendIcon('up') ?></span></div>
        <div class="ce-row"><span>Prensa #4 · RPM</span><span class="mono">1450 rev/min</span><span>—</span></div>
        <div class="ce-row"><span>Extractor · temp.</span><span class="mono">48.2 °C</span><span class="down"><?= $trendIcon('down') ?></span></div>
        <div class="ce-row"><span>Rodamientos 6204</span><span class="mono">2 u</span><span class="down"><?= $trendIcon('down') ?></span></div>
      </div>
    </div>
  </aside>
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
          Monitoreo de motores, alertas y gestión del mantenimiento para toda tu planta, sin límites de espacios ni de placas.
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
          Espacios y miembros <b>ilimitados</b>
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Placas ESP32 <b>sin tope</b> por espacio
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Alertas por umbral en la web y en la placa
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Stock, tareas y calendario de mantenimiento
        </li>
        <li data-anim>
          <span class="price-check" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          Historial completo exportable a CSV
        </li>
      </ul>

      <a class="l-btn l-btn--solid l-btn--lg price-cta" href="<?= base_url('pago') ?>">
        Comprar ahora
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <span class="price-note">Sin permanencia · cancelás desde tu cuenta</span>
    </article>
  </div>
</section>


<!-- ───────────────────────────── CTA ─────────── -->
<section class="cta-section" data-screen-label="12 CTA final">
  <div class="cta-inner">
    <span class="section-eyebrow mono">11 · Empezar</span>
    <h2 class="cta-title">
      Construí un sistema de monitoreo
      <span class="muted">adaptado a tu industria.</span>
    </h2>
    <p class="cta-sub">
      Creás tu espacio en cinco minutos: elegís qué medir, vinculás la placa
      y empezás a ver los datos.
    </p>
    <div class="hero-actions center">
      <a class="l-btn l-btn--solid l-btn--lg" href="<?= base_url('reg-log') . '?form=register' ?>">
        Crear cuenta
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a class="l-btn l-btn--line l-btn--lg" href="#como-funciona">
        <span class="l-play-dot"></span> Ver cómo funciona
      </a>
    </div>
    <div class="cta-marquee" aria-hidden="true">
      <div class="cta-track">
        <?php for ($i = 0; $i < 2; $i++): ?>
          <span>monitoreo de motores</span><span>·</span>
          <span>gráficos en vivo</span><span>·</span>
          <span>alertas por umbral</span><span>·</span>
          <span>historial en csv</span><span>·</span>
          <span>stock y mantenimiento</span><span>·</span>
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
    </div>

    <div class="ft-cols">
      <div>
        <h5>Plataforma</h5>
        <a href="#funcionalidades">Funcionalidades</a>
        <a href="#como-funciona">Cómo funciona</a>
        <a href="#metricas">Métricas</a>
        <a href="#plantillas">Vistas</a>
      </div>
      <div>
        <h5>Sensores</h5>
        <a href="#sensores">Kit de sensores</a>
        <a href="#recommender">Armá tu kit</a>
        <a href="<?= base_url('ayuda') ?>">Centro de ayuda</a>
        <a href="<?= base_url('portfolio') ?>">Portfolio</a>
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
        <a href="mailto:preventivecontrolsystem.saas@gmail.com">preventivecontrolsystem.saas@gmail.com</a>
        <a href="<?= base_url('ayuda') ?>">Soporte</a>
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
<script src="<?= base_url('js/landing.js') ?>?v=20260924-1" defer></script>
<script src="<?= base_url('js/pricing.js') ?>?v=20260610-1" defer></script>
<?= $this->endSection() ?>
