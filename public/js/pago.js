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
    gsap.from('.pay-form-col', { opacity: 0, y: 28, duration: 0.6, ease: 'power3.out' });
    gsap.from('.pay-summary-col', { opacity: 0, y: 28, duration: 0.6, delay: 0.12, ease: 'power3.out' });
    gsap.to(animEls, { opacity: 1, duration: 0.01 });
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
      btn.classList.add('is-done');
      if (label) label.textContent = '¡Pago confirmado!';

      // Pequeña pausa para que se lea el éxito y luego redirige al registro.
      window.setTimeout(() => { window.location.href = NEXT_URL; }, 900);
    }, 1500);
  });
})();
