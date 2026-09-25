/* PrevCS landing — scroll, counters, live chart, recommender, carousel, parallax */

(() => {
  'use strict';

  /* ---------- Sticky header scrolled state ---------- */
  const header = document.querySelector('.landing-header');
  const onScroll = () => header && header.classList.toggle('scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Sliding navigation pill ---------- */
  const sectionNav = document.querySelector('[data-section-nav]');
  if (sectionNav) {
    const pill = sectionNav.querySelector('.hd-nav-pill');
    const links = [...sectionNav.querySelectorAll('[data-nav-section]')];
    const sections = links
      .map((link) => ({ link, section: document.getElementById(link.dataset.navSection) }))
      .filter((item) => item.section);

    const movePill = (link) => {
      if (!pill || !link) return;
      const navRect = sectionNav.getBoundingClientRect();
      const linkRect = link.getBoundingClientRect();
      pill.style.width = `${linkRect.width}px`;
      pill.style.transform = `translate3d(${linkRect.left - navRect.left - 4}px, 0, 0)`;
      pill.classList.add('is-visible');
      links.forEach((item) => {
        const active = item === link;
        item.classList.toggle('is-active', active);
        if (active) item.setAttribute('aria-current', 'location');
        else item.removeAttribute('aria-current');
      });
    };

    const updateActiveSection = () => {
      const marker = Math.min(window.innerHeight * 0.38, 300);
      let current = sections[0];
      sections.forEach((item) => {
        if (item.section.getBoundingClientRect().top <= marker) current = item;
      });
      if (current) movePill(current.link);
    };

    links.forEach((link) => link.addEventListener('click', () => movePill(link)));
    window.addEventListener('scroll', updateActiveSection, { passive: true });
    window.addEventListener('resize', updateActiveSection, { passive: true });
    updateActiveSection();
  }

  /* ---------- Reveal on scroll ---------- */
  const revealSelectors = [
    '.section-head', '.problem-card', '.sensor-card',
    '.template-card', '.case-card', '.sb', '.how-step',
    '.hero-content > *', '.hero-dashboard-preview',
    '.ce-left', '.ce-right', '.cta-inner > *', '.recommender > *'
  ];
  const revealEls = document.querySelectorAll(revealSelectors.join(','));
  revealEls.forEach((el, i) => {
    el.setAttribute('data-reveal', '');
    if (i % 4 !== 0) el.setAttribute('data-delay', String(i % 4));
  });
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
  revealEls.forEach((el) => io.observe(el));

  /* ---------- Counters ---------- */
  const counters = document.querySelectorAll('[data-counter]');
  const animateCounter = (el) => {
    const target = parseFloat(el.dataset.to);
    const decimals = parseInt(el.dataset.decimals || '0', 10);
    const duration = 1400;
    const start = performance.now();
    const fmt = (v) => decimals > 0 ? v.toFixed(decimals) : Math.round(v).toLocaleString('es-AR');
    const step = (t) => {
      const p = Math.min(1, (t - start) / duration);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(target * eased);
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  };
  const cio = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) { animateCounter(e.target); cio.unobserve(e.target); }
    });
  }, { threshold: 0.4 });
  counters.forEach((el) => cio.observe(el));

  /* ---------- Hero live chart ---------- */
  const chart = document.querySelector('[data-chart-live]');
  if (chart) {
    const W = 600, H = 180;
    const N = 80;
    const data = [];
    let baseline = 70;
    for (let i = 0; i < N; i++) { baseline += (Math.random() - 0.5) * 4; data.push(baseline); }

    const line = chart.querySelector('.hdp-chart-line');
    const area = chart.querySelector('.hdp-chart-area');
    const dot  = chart.querySelector('.hdp-chart-dot');

    const render = () => {
      const min = Math.min(...data) - 6;
      const max = Math.max(...data) + 6;
      const scaleY = (v) => H - ((v - min) / (max - min)) * (H - 16) - 8;
      const dx = W / (N - 1);
      let d = '';
      for (let i = 0; i < N; i++) {
        const x = i * dx, y = scaleY(data[i]);
        d += (i === 0 ? `M${x},${y}` : ` L${x},${y}`);
      }
      line.setAttribute('d', d);
      area.setAttribute('d', d + ` L${W},${H} L0,${H} Z`);
      const lastX = (N - 1) * dx, lastY = scaleY(data[N - 1]);
      dot.setAttribute('transform', `translate(${lastX},${lastY})`);
    };
    render();

    setInterval(() => {
      baseline += (Math.random() - 0.5) * 3;
      baseline = Math.max(55, Math.min(85, baseline));
      data.push(baseline); data.shift();
      render();
    }, 1500);
  }

  /* ---------- Recommender ---------- */
  const recRoot = document.getElementById('recommender');
  if (recRoot) {
    // Lo que el kit de PrevCS mide hoy: cada métrica con su sensor real, lo
    // que se ve en el panel y el límite de alerta con el que nace el sensor.
    const RECS = {
      temperatura: { s: ['DS18B20 — sonda de temperatura'], t: ['Gráfico en vivo de temperatura'],  a: ['Máximo sugerido: 60 °C'] },
      vibracion:   { s: ['SW-420 — vibración digital'],     t: ['Eventos de vibración por minuto'], a: ['Máximo sugerido: 50 eventos'] },
      rpm:         { s: ['LM393 — sensor de RPM'],          t: ['Motor andando o detenido'],        a: ['Mínimo y máximo a elección'] }
    };

    const chips = recRoot.querySelectorAll('[data-rec]');
    const $s = recRoot.querySelector('#rec-sensors');
    const $t = recRoot.querySelector('#rec-templates');
    const $a = recRoot.querySelector('#rec-alerts');

    const update = () => {
      const active = [...chips].filter(c => c.classList.contains('on')).map(c => c.dataset.rec);
      const setS = new Set(), setT = new Set(), setA = new Set();
      if (active.length === 0) {
        const empty = 'Elegí al menos una métrica.';
        $s.textContent = empty; $t.textContent = empty; $a.textContent = empty;
        return;
      }
      active.forEach(k => {
        const r = RECS[k]; if (!r) return;
        r.s.slice(0, 1).forEach(x => setS.add(x));
        r.t.slice(0, 1).forEach(x => setT.add(x));
        r.a.slice(0, 1).forEach(x => setA.add(x));
      });
      if (active.length > 0) setS.add('ESP32 — placa WiFi con pantalla LCD y buzzer');

      const render = (set, host) => {
        const values = [...set].slice(0, 4).map((value) => {
          const parser = document.createElement('span');
          parser.innerHTML = value;
          return parser.textContent;
        });
        host.textContent = values.join(', ');
        host.classList.remove('is-updating');
        requestAnimationFrame(() => host.classList.add('is-updating'));
      };
      render(setS, $s); render(setT, $t); render(setA, $a);
    };

    chips.forEach(c => c.addEventListener('click', () => {
      c.classList.toggle('on');
      update();
    }));
    update();
  }

  /* ---------- Template carousel ---------- */
  const tc = document.getElementById('templateCarousel');
  if (tc) {
    const track = tc.querySelector('.tc-track');
    const prev  = tc.querySelector('.prev');
    const next  = tc.querySelector('.next');
    const cards = track ? track.children : [];
    let index = 0;

    const perView = () => {
      const w = window.innerWidth;
      if (w < 600) return 1;
      if (w < 900) return 2;
      return 3;
    };
    const getMax = () => Math.max(0, cards.length - perView());
    const update = () => {
      const max = getMax();
      index = Math.min(Math.max(0, index), max);
      tc.dataset.carouselMax = String(max);
      const gap = parseFloat(getComputedStyle(track).gap) || 0;
      const cardWidth = cards[0] ? cards[0].getBoundingClientRect().width + gap : 0;
      track.style.transform = `translate3d(${-index * cardWidth}px, 0, 0)`;
      tc.dataset.carouselIndex = String(index);
      prev.disabled = index === 0;
      next.disabled = index === max;
      prev.setAttribute('aria-disabled', String(index === 0));
      next.setAttribute('aria-disabled', String(index === max));
      prev.classList.toggle('is-disabled', index === 0);
      next.classList.toggle('is-disabled', index === max);
      tc.classList.toggle('is-at-start', index === 0);
      tc.classList.toggle('is-at-end', index === max);
    };
    const goPrev = (e) => {
      if (e) { e.preventDefault(); e.stopPropagation(); }
      index = Math.max(0, index - 1);
      update();
    };
    const goNext = (e) => {
      if (e) { e.preventDefault(); e.stopPropagation(); }
      const max = getMax();
      index = Math.min(max, index + 1);
      update();
    };

    tc.addEventListener('click', (event) => {
      const button = event.target.closest('.tc-nav');
      if (!button || button.disabled || button.classList.contains('is-disabled')) return;
      if (button.classList.contains('prev') && tc.classList.contains('is-at-start')) return;
      if (button.classList.contains('next') && tc.classList.contains('is-at-end')) return;
      if (button.classList.contains('prev')) goPrev(event);
      else goNext(event);
    });
    tc.dataset.carouselReady = 'true';

    let pointerStart = null;
    tc.addEventListener('pointerdown', (event) => {
      if (event.pointerType === 'mouse') return;
      pointerStart = event.clientX;
    });
    tc.addEventListener('pointerup', (event) => {
      if (pointerStart === null) return;
      const delta = event.clientX - pointerStart;
      pointerStart = null;
      if (Math.abs(delta) < 35) return;
      if (delta < 0) goNext(event);
      else goPrev(event);
    });
    tc.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') goPrev(event);
      if (event.key === 'ArrowRight') goNext(event);
    });
    window.addEventListener('resize', update, { passive: true });

    update();
  }

  /* ---------- Hero floating cards parallax ---------- */
  const floats = document.querySelectorAll('.hdp-float');
  const hero = document.querySelector('.hero-section');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (floats.length && hero && !reduceMotion && window.matchMedia('(min-width: 980px)').matches) {
    let mx = 0, my = 0, tx = 0, ty = 0;
    hero.addEventListener('mousemove', (e) => {
      const r = hero.getBoundingClientRect();
      mx = (e.clientX - r.left - r.width / 2) / r.width;
      my = (e.clientY - r.top - r.height / 2) / r.height;
    });
    const loop = () => {
      tx += (mx - tx) * 0.08;
      ty += (my - ty) * 0.08;
      floats.forEach((f, i) => {
        const depth = (i + 1) * 6;
        f.style.setProperty('--parallax-x', `${tx * depth}px`);
        f.style.setProperty('--parallax-y', `${ty * depth}px`);
      });
      requestAnimationFrame(loop);
    };
    loop();
  }

  /* ---------- Hero main card subtle tilt with scroll ---------- */
  const main = document.querySelector('.hdp-main');
  if (main && !reduceMotion) {
    window.addEventListener('scroll', () => {
      const r = main.getBoundingClientRect();
      const center = r.top + r.height / 2;
      const vh = window.innerHeight;
      const t = Math.max(-1, Math.min(1, (center - vh / 2) / vh));
      main.style.transform = `rotateY(${-3 + t * 2}deg) rotateX(${2 - t * 2}deg) translateY(${t * -8}px)`;
    }, { passive: true });
  }

  /* ---------- GSAP: restrained metrics reveal ---------- */
  const initGsap = () => {
    if (typeof window.gsap === 'undefined' || typeof window.ScrollTrigger === 'undefined') return;
    window.gsap.registerPlugin(window.ScrollTrigger);
    if (window.PCSLenis && typeof window.PCSLenis.on === 'function') {
      window.PCSLenis.on('scroll', window.ScrollTrigger.update);
    }
    if (!reduceMotion) {
      window.gsap.from('[data-gsap-section="metrics"] .metric-card', {
        scrollTrigger: {
          trigger: '[data-gsap-section="metrics"]',
          start: 'top 76%',
          once: true
        },
        y: 18,
        opacity: 0,
        duration: 0.52,
        stagger: 0.045,
        ease: 'power2.out',
        clearProps: 'transform,opacity'
      });
    }
    window.ScrollTrigger.refresh();

    window.setTimeout(() => {
      if (typeof window.ScrollTrigger !== 'undefined') return;
      document.querySelectorAll('[data-gsap-section="metrics"] .metric-card').forEach((card) => {
        card.style.removeProperty('opacity');
        card.style.removeProperty('transform');
        card.style.removeProperty('visibility');
      });
    }, 1200);
  };
  if (document.readyState === 'complete') initGsap();
  else window.addEventListener('load', initGsap, { once: true });
})();
