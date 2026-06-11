/* PrevCS · Vista de Pago
   - Entrada animada con GSAP (degrada con gracia sin GSAP / reduced-motion).
   - Selección de método de pago.
   - "Confirmar pago" simula el cobro y redirige al registro. */
(() => {
  'use strict';

  // Siguiente paso del flujo: Pago -> Registro (resuelto contra la URL actual).
  const NEXT_URL = new URL('reg-log?form=register', window.location.href).href;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const hasGSAP = typeof window.gsap !== 'undefined';

  /* ---------- Entrada animada ---------- */
  const animEls = document.querySelectorAll('[data-anim]');
  if (!hasGSAP || reduce) {
    animEls.forEach((el) => { el.style.opacity = '1'; });
  } else {
    const { gsap } = window;
    gsap.fromTo('.pay-form-col',
      { opacity: 0, y: 28 },
      { opacity: 1, y: 0, duration: 0.6, ease: 'power3.out' });
    gsap.fromTo('.pay-summary-col',
      { opacity: 0, y: 28 },
      { opacity: 1, y: 0, duration: 0.6, delay: 0.12, ease: 'power3.out' });
    gsap.from('.pay-method', {
      opacity: 0, y: 16, duration: 0.45, delay: 0.25, stagger: 0.1, ease: 'power2.out',
    });
  }

  /* ---------- Micro-interacción al elegir método ---------- */
  const methods = document.querySelectorAll('.pay-method input[type="radio"]');
  methods.forEach((input) => {
    input.addEventListener('change', () => {
      if (!hasGSAP || reduce) return;
      const inner = input.closest('.pay-method')?.querySelector('.pay-method-inner');
      if (inner) {
        window.gsap.fromTo(inner,
          { scale: 0.98 },
          { scale: 1, duration: 0.3, ease: 'back.out(2)' });
      }
    });
  });

  /* ---------- Simulación del pago ---------- */
  const form = document.getElementById('payForm');
  const btn = document.getElementById('payConfirm');
  if (!form || !btn) return;

  const label = btn.querySelector('.pay-submit-label');

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (btn.classList.contains('is-loading') || btn.classList.contains('is-done')) return;

    // Validación nativa mínima (nombre + email).
    if (!form.checkValidity()) {
      form.reportValidity();
      if (hasGSAP && !reduce) {
        window.gsap.fromTo(form,
          { x: -6 },
          { x: 0, duration: 0.4, ease: 'elastic.out(1, 0.4)' });
      }
      return;
    }

    // Estado: procesando.
    btn.classList.add('is-loading');
    btn.disabled = true;
    if (label) label.textContent = 'Procesando…';

    // Simula la llamada al proveedor de pago.
    window.setTimeout(() => {
      btn.classList.remove('is-loading');
      if (label) label.textContent = 'Confirmar pago';
      openModal();
    }, 1500);
  });

  /* ---------- Modal de pago confirmado ---------- */
  const modal = document.getElementById('payModal');

  function openModal() {
    if (!modal) {
      window.location.href = NEXT_URL;
      return;
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (hasGSAP && !reduce) {
      window.gsap.fromTo('.pay-modal-icon',
        { scale: 0.4, rotate: -12 },
        { scale: 1, rotate: 0, duration: 0.5, ease: 'back.out(2)', delay: 0.05 });
    }
    modal.querySelector('#payModalContinue')?.focus();
  }

  // El overlay no cierra el modal: el pago ya se confirmó y queremos guiar al registro.
  // (El único cierre real es continuar al registro.)
  document.getElementById('payModalContinue')?.addEventListener('click', () => {
    document.body.style.overflow = '';
  });
})();
