/* PrevCS · Pricing — entrada animada con GSAP + ScrollTrigger.
   Degrada con gracia si GSAP no cargó o si el usuario pidió menos movimiento. */
(() => {
  'use strict';

  const card = document.querySelector('.price-card');
  if (!card) return;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const hasGSAP = typeof window.gsap !== 'undefined';

  // Fallback: sin GSAP o reduced-motion -> mostrar todo sin animar.
  if (!hasGSAP || reduce) {
    document.querySelectorAll('.price-card[data-anim], .price-features li[data-anim]')
      .forEach((el) => { el.style.opacity = '1'; });
    return;
  }

  const { gsap } = window;
  if (window.ScrollTrigger) gsap.registerPlugin(window.ScrollTrigger);

  const features = gsap.utils.toArray('.price-features li[data-anim]');

  const tl = gsap.timeline({
    defaults: { ease: 'power3.out' },
    scrollTrigger: {
      trigger: card,
      start: 'top 80%',
      once: true,
    },
  });

  // La tarjeta entra con un leve scale + fade.
  tl.fromTo(card,
    { opacity: 0, y: 40, scale: 0.96 },
    { opacity: 1, y: 0, scale: 1, duration: 0.7 }
  );

  // El precio "cuenta" hacia su valor real para reforzar el foco.
  const valueEl = card.querySelector('.price-value');
  if (valueEl) {
    const target = parseInt(valueEl.dataset.to || valueEl.textContent, 10) || 0;
    const counter = { v: 0 };
    tl.to(counter, {
      v: target,
      duration: 0.8,
      ease: 'power2.out',
      onUpdate: () => { valueEl.textContent = Math.round(counter.v); },
    }, '-=0.35');
  }

  // Las características aparecen en cascada.
  tl.to(features, {
    opacity: 1,
    y: 0,
    duration: 0.4,
    stagger: 0.07,
  }, '-=0.5');

  gsap.set(features, { y: 12 });
})();
