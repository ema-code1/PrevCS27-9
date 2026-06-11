<?= $this->extend('templates/layout') ?>

<?php $showHeader = false; ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/pago.css') ?>?v=20260611-3">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ───────────────────────── TOPBAR mínima ───────────────────────── -->
<header class="pay-topbar">
  <a class="hd-logo" href="<?= base_url() ?>" aria-label="PrevCS, inicio">
    <span class="hd-logo-mark" aria-hidden="true">
      <span class="hd-logo-dot"></span>
      <span class="hd-logo-dot"></span>
      <span class="hd-logo-dot"></span>
      <span class="hd-logo-dot live"></span>
    </span>
    <span>PrevCS</span>
  </a>

  <a class="pay-back" href="<?= base_url() ?>#precio">
    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10 4 6 8l4 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Volver al plan
  </a>
</header>

<main class="pay-main">
  <!-- Indicador de pasos del flujo -->
  <ol class="pay-steps" aria-label="Progreso de la compra">
    <li class="is-active"><span class="pay-step-dot">1</span> Pago</li>
    <li><span class="pay-step-dot">2</span> Registro</li>
    <li><span class="pay-step-dot">3</span> Checkout</li>
    <li><span class="pay-step-dot">4</span> Dashboard</li>
  </ol>

  <div class="pay-grid">

    <!-- ───────────── Columna izquierda: formulario ───────────── -->
    <section class="pay-form-col" data-anim>
      <div class="pay-block-head">
        <span class="section-eyebrow mono">01 · Tus datos</span>
        <h1 class="pay-title">Completá tu compra</h1>
        <p class="pay-lede">Te toma menos de un minuto. Después creás tu cuenta y entrás al dashboard.</p>
      </div>

<?php
        $isLoggedIn = $isLoggedIn ?? false;
        $userName   = $userName ?? '';
        $userEmail  = $userEmail ?? '';
      ?>
      <form id="payForm" class="pay-form" novalidate>
        <div class="pay-field">
          <label for="pay-name">Nombre completo</label>
          <input type="text" id="pay-name" name="name"
                 value="<?= esc($userName, 'attr') ?>"
                 placeholder="Ej. Ana Pérez" autocomplete="name" required>
          <?php if ($isLoggedIn && $userName !== ''): ?>
            <small class="pay-field-hint">Tomado de tu cuenta. Modificalo si querés usar otro nombre.</small>
          <?php endif; ?>
        </div>

        <div class="pay-field">
          <label for="pay-email">Email</label>
          <input type="email" id="pay-email" name="email"
                 value="<?= esc($userEmail, 'attr') ?>"
                 placeholder="tu@empresa.com" autocomplete="email" required>
          <?php if ($isLoggedIn && $userEmail !== ''): ?>
            <small class="pay-field-hint">Usaremos el email de tu cuenta salvo que lo cambies acá.</small>
          <?php endif; ?>
        </div>

        <!-- ───────────── Método de pago ───────────── -->
        <fieldset class="pay-methods">
          <legend class="section-eyebrow mono">02 · Método de pago</legend>

          <div class="pay-method-grid">
            <label class="pay-method" data-method="mercadopago">
              <input type="radio" name="method" value="mercadopago" checked>
              <span class="pay-method-inner">
                <span class="pay-logo-chip">
                  <img src="https://cdn.jsdelivr.net/npm/simple-icons@v13/icons/mercadopago.svg"
                       alt="Mercado Pago" loading="lazy" width="34" height="34">
                </span>
                <span class="pay-method-text">
                  <strong>Mercado Pago</strong>
                  <small>Tarjeta, dinero en cuenta o efectivo</small>
                </span>
                <span class="pay-method-check" aria-hidden="true">
                  <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
              </span>
            </label>

            <label class="pay-method" data-method="paypal">
              <input type="radio" name="method" value="paypal">
              <span class="pay-method-inner">
                <span class="pay-logo-chip">
                  <img src="https://cdn.jsdelivr.net/npm/simple-icons@v13/icons/paypal.svg"
                       alt="PayPal" loading="lazy" width="30" height="30">
                </span>
                <span class="pay-method-text">
                  <strong>PayPal</strong>
                  <small>Pagá con tu saldo o tarjeta internacional</small>
                </span>
                <span class="pay-method-check" aria-hidden="true">
                  <svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
              </span>
            </label>
          </div>
        </fieldset>

        <button type="submit" class="pay-submit" id="payConfirm">
          <span class="pay-submit-label">Confirmar pago</span>
          <span class="pay-spinner" aria-hidden="true"></span>
          <svg class="pay-submit-arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        <p class="pay-secure mono">
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 7V5.5a4 4 0 0 1 8 0V7M3.5 7h9A1.5 1.5 0 0 1 14 8.5v4A1.5 1.5 0 0 1 12.5 14h-9A1.5 1.5 0 0 1 2 12.5v-4A1.5 1.5 0 0 1 3.5 7Z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Pago simulado · sin cargos reales (demo académica)
        </p>
      </form>
    </section>

    <!-- ───────────── Columna derecha: resumen ───────────── -->
    <aside class="pay-summary-col" data-anim>
      <div class="pay-summary">
        <span class="pay-summary-tag mono">Resumen del pedido</span>

        <div class="pay-summary-plan">
          <div class="pay-summary-icon" aria-hidden="true">
            <span class="hd-logo-mark">
              <span class="hd-logo-dot"></span><span class="hd-logo-dot"></span>
              <span class="hd-logo-dot"></span><span class="hd-logo-dot live"></span>
            </span>
          </div>
          <div>
            <strong>PrevCS Pro</strong>
            <small>Suscripción mensual · plan único</small>
          </div>
        </div>

        <ul class="pay-summary-list">
          <li><svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Workspaces ilimitados</li>
          <li><svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Sensores sin tope</li>
          <li><svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Alertas en tiempo real</li>
          <li><svg viewBox="0 0 16 16" fill="none"><path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Soporte prioritario</li>
        </ul>

        <div class="pay-summary-rows">
          <div class="pay-row"><span>Subtotal</span><span class="mono">USD $49.00</span></div>
          <div class="pay-row"><span>Impuestos</span><span class="mono">USD $0.00</span></div>
          <div class="pay-row pay-row--total">
            <span>Total <em>/ mes</em></span>
            <span class="mono pay-total">USD $49.00</span>
          </div>
        </div>

        <span class="pay-guarantee mono">
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5 13.5 4v4c0 3.5-2.3 5.6-5.5 6.5C4.8 13.6 2.5 11.5 2.5 8V4L8 1.5Z" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>
          Garantía de 30 días · cancelás cuando quieras
        </span>
      </div>
    </aside>

  </div>
</main>

<!-- ───────────── Modal: pago confirmado ───────────── -->
<div class="pay-modal" id="payModal" role="dialog" aria-modal="true" aria-labelledby="payModalTitle" aria-hidden="true">
  <div class="pay-modal-overlay" data-modal-close></div>
  <div class="pay-modal-card">
    <span class="pay-modal-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5 10 17.5 19 6.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <h2 class="pay-modal-title" id="payModalTitle">¡Pago confirmado!</h2>
    <p class="pay-modal-text">Recibimos tu pago de <strong>USD&nbsp;$49.00</strong>. El siguiente paso es crear tu cuenta para entrar al dashboard.</p>

    <div class="pay-modal-actions">
      <a class="pay-modal-btn pay-modal-btn--solid" id="payModalContinue" href="<?= base_url('principal') ?>">
        Continuar
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a class="pay-modal-btn pay-modal-btn--line" href="#" aria-disabled="true" title="Disponible próximamente">
        <svg viewBox="0 0 16 16" fill="none"><path d="M4 2.5h6l2.5 2.5v8.5a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/><path d="M6 7.5h4M6 10h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Consultar factura
      </a>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
<script src="<?= base_url('js/pago.js') ?>?v=20260611-3" defer></script>
<?= $this->endSection() ?>
