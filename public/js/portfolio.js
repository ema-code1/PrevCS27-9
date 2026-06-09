/* PrevCS · portfolio.js — GSAP ScrollTrigger + scrollspy + charts */
(() => {
  'use strict';

  /* ── Header scrolled state ── */
  const header = document.querySelector('.landing-header');
  const onScroll = () => header && header.classList.toggle('scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  const smoothScroll = () => window.PCSSmoothScroll || null;

  /* ── Smooth anchor scroll ── */
  document.addEventListener('click', (e) => {
    if (e.defaultPrevented || smoothScroll()) return;
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const id = a.getAttribute('href').slice(1);
    if (!id) return;
    const target = document.getElementById(id);
    if (!target) return;
    e.preventDefault();
    scrollToTarget(target);
    history.replaceState(null, '', '#' + id);
  });

  function scrollToTarget(target, immediate = false) {
    const smooth = smoothScroll();
    if (smooth && typeof smooth.scrollTo === 'function') {
      smooth.scrollTo(target, { offset: -84, immediate });
      return;
    }
    target.scrollIntoView({ behavior: immediate ? 'auto' : 'smooth', block: 'start' });
  }

  /* ── Scrollspy ── */
  const sectionIds = [
    'pagina-principal','plan-operativo','imagen-corporativa',
    'quienes-somos','acerca-de-prevcs','analisis-competencia','analisis-mercado'
  ];
  const sections = sectionIds.map(id => document.getElementById(id)).filter(Boolean);
  const megaItems = new Map();
  document.querySelectorAll('.hd-portfolio-mega [data-section-link]').forEach(a => {
    megaItems.set(a.dataset.sectionLink, a);
  });
  const setActive = (id) => {
    megaItems.forEach((a, key) => a.classList.toggle('hpm-item--current', key === id));
  };
  setActive(window.location.hash.slice(1) || 'pagina-principal');
  if (sections.length) {
    let spyFrame = 0;
    const updateSectionSpy = () => {
      const marker = window.innerHeight * 0.46;
      let active = sections[0];
      sections.forEach((section) => {
        if (section.getBoundingClientRect().top <= marker) active = section;
      });
      setActive(active.id);
      spyFrame = 0;
    };
    const requestSectionSpy = () => {
      if (spyFrame) return;
      spyFrame = requestAnimationFrame(updateSectionSpy);
    };
    window.addEventListener('scroll', requestSectionSpy, { passive: true });
    window.addEventListener('resize', requestSectionSpy, { passive: true });
    window.addEventListener('load', requestSectionSpy, { once: true });
    window.setTimeout(requestSectionSpy, 180);
  }

  /* ── Comparison table edge fade ── */
  document.querySelectorAll('[data-comp-scroll]').forEach(wrap => {
    let targetScroll = wrap.scrollLeft;
    let frame = 0;

    const maxScroll = () => Math.max(0, wrap.scrollWidth - wrap.clientWidth);
    const update = () => {
      const atEnd = wrap.scrollLeft + wrap.clientWidth >= wrap.scrollWidth - 2;
      wrap.dataset.scrollEnd = atEnd ? 'true' : 'false';
      if (!frame) targetScroll = wrap.scrollLeft;
    };

    const animate = () => {
      const diff = targetScroll - wrap.scrollLeft;
      if (Math.abs(diff) < 0.5) {
        wrap.scrollLeft = targetScroll;
        frame = 0;
        update();
        return;
      }
      wrap.scrollLeft += diff * 0.22;
      update();
      frame = requestAnimationFrame(animate);
    };

    wrap.addEventListener('wheel', (e) => {
      const max = maxScroll();
      if (max <= 0) return;

      if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;

      const unit = e.deltaMode === 1 ? 18 : (e.deltaMode === 2 ? wrap.clientWidth : 1);
      const delta = e.deltaX * unit;
      const next = Math.max(0, Math.min(max, targetScroll + delta));
      const blockedStart = delta < 0 && wrap.scrollLeft <= 0;
      const blockedEnd = delta > 0 && wrap.scrollLeft >= max - 1;
      if (blockedStart || blockedEnd) return;

      e.preventDefault();
      targetScroll = next;
      if (!frame) frame = requestAnimationFrame(animate);
    }, { passive: false });

    wrap.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });
    update();
  });

  /* ── Copy form link ── */
  const FORM_LINK = 'https://docs.google.com/forms/d/e/1FAIpQLSe8SOVPspfusSJ0TT7QcdSGnLbcb2MOWjdHmdk4HOlrMVANHA/viewform';
  document.querySelectorAll('[data-copy-form-link]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const label = btn.querySelector('[data-copy-label]');
      const original = label ? label.textContent : '';
      try {
        await navigator.clipboard.writeText(FORM_LINK);
        if (label) label.textContent = 'Copiado ✓';
      } catch { if (label) label.textContent = 'Error al copiar'; }
      setTimeout(() => { if (label) label.textContent = original; }, 1800);
    });
  });

  /* ══════════════════════════════════════════════
     GSAP ANIMATIONS
     ══════════════════════════════════════════════ */
  function initGSAP() {
    if (typeof gsap === 'undefined') return;
    if (typeof ScrollTrigger !== 'undefined') gsap.registerPlugin(ScrollTrigger);
    if (window.PCSLenis && typeof window.PCSLenis.on === 'function' && typeof ScrollTrigger !== 'undefined') {
      window.PCSLenis.on('scroll', ScrollTrigger.update);
    }

    /* Hero entrance */
    gsap.timeline({ defaults: { ease: 'power3.out' } })
      .from('.pf-tag',         { y: 28, opacity: 0, duration: 0.7 }, 0.25)
      .from('.pf-hero-title',  { y: 56, opacity: 0, duration: 1.1 }, 0.42)
      .from('.pf-hero-serif',  { y: 24, opacity: 0, duration: 0.9 }, 0.6)
      .from('.pf-hero-actions',{ y: 20, opacity: 0, duration: 0.7 }, 0.85);

    if (typeof ScrollTrigger === 'undefined') return;

    const st = (trigger, start = 'top 87%') => ({
      scrollTrigger: { trigger, start, once: true }
    });

    /* Section eyebrows */
    gsap.utils.toArray('.pf-section-eyebrow').forEach(el => {
      gsap.from(el, { ...st(el, 'top 90%'), x: -24, opacity: 0, duration: 0.6, ease: 'power2.out' });
    });

    /* Section titles */
    gsap.utils.toArray('.pf-section-title').forEach(el => {
      gsap.from(el, { ...st(el, 'top 86%'), y: 36, opacity: 0, duration: 0.9, ease: 'power3.out' });
    });

    /* Section lede */
    gsap.utils.toArray('.pf-section-lede').forEach(el => {
      gsap.from(el, { ...st(el), y: 18, opacity: 0, duration: 0.7, delay: 0.1, ease: 'power2.out' });
    });

    /* Status badges */
    gsap.utils.toArray('.pf-status').forEach(el => {
      gsap.from(el, { ...st(el), x: -16, opacity: 0, duration: 0.5, ease: 'power2.out' });
    });

    /* Steps: line grows on scroll, then each step slides in */
    const stepsLine = document.querySelector('.pf-steps-line');
    if (stepsLine) {
      gsap.from(stepsLine, {
        scrollTrigger: {
          trigger: '.pf-steps',
          start: 'top 72%',
          end: 'bottom 55%',
          scrub: 1.4
        },
        scaleY: 0, transformOrigin: 'top center', ease: 'none'
      });
    }

    gsap.utils.toArray('.pf-step').forEach((el, i) => {
      const num  = el.querySelector('.pf-step-num');
      const body = el.querySelector('.pf-step-body');
      if (num)  gsap.from(num,  { ...st(el, 'top 86%'), opacity: 0, duration: 0.55, ease: 'power2.out' });
      if (body) gsap.from(body, { ...st(el, 'top 86%'), y: 22, opacity: 0, duration: 0.7, delay: 0.12, ease: 'power2.out' });
    });

    /* Brand cards */
    const brandGrid = document.querySelector('.pf-brand-grid');
    if (brandGrid) {
      gsap.from(brandGrid.querySelectorAll(':scope > *'), {
        ...st(brandGrid, 'top 82%'),
        y: 24, opacity: 0, duration: 0.6, stagger: 0.09, ease: 'power2.out'
      });
    }

    /* Team members */
    gsap.utils.toArray('.pf-member-card').forEach((el, i) => {
      gsap.from(el, { ...st(el, 'top 86%'), y: 44, opacity: 0, duration: 0.75, delay: i * 0.12, ease: 'power3.out' });
    });

    /* About lead */
    gsap.utils.toArray('.pf-about-lead p').forEach((el, i) => {
      gsap.from(el, { ...st(el), y: 20, opacity: 0, duration: 0.75, delay: i * 0.1, ease: 'power2.out' });
    });

    /* About items: alternate left/right */
    gsap.utils.toArray('.pf-about-item').forEach((el, i) => {
      gsap.from(el, {
        ...st(el, 'top 84%'),
        x: i % 2 === 0 ? -36 : 36, opacity: 0,
        duration: 0.8, ease: 'power3.out'
      });
    });

    /* About stat numbers */
    gsap.utils.toArray('.pf-about-stat').forEach((el, i) => {
      gsap.from(el, { ...st(el, 'top 88%'), y: 30, opacity: 0, duration: 0.6, delay: i * 0.1, ease: 'power2.out' });
    });

    /* Editorial paragraphs */
    gsap.utils.toArray('.pf-editorial > p').forEach(el => {
      gsap.from(el, { ...st(el), y: 18, opacity: 0, duration: 0.7, ease: 'power2.out' });
    });

    /* Editorial rules grow */
    gsap.utils.toArray('.pf-editorial-rule').forEach(el => {
      gsap.from(el, {
        scrollTrigger: { trigger: el, start: 'top 92%', once: true },
        scaleX: 0, transformOrigin: 'left center', duration: 0.9, ease: 'power2.inOut'
      });
    });

    /* Editorial lead */
    gsap.utils.toArray('.pf-editorial-lead').forEach(el => {
      gsap.from(el, { ...st(el, 'top 84%'), y: 28, opacity: 0, duration: 1, ease: 'power3.out' });
    });

    /* VS stat strip */
    gsap.utils.toArray('.pf-vs-strip > div').forEach((el, i) => {
      gsap.from(el, { ...st(el, 'top 88%'), y: 32, opacity: 0, duration: 0.65, delay: i * 0.11, ease: 'power2.out' });
    });

    /* Pull-quote */
    gsap.utils.toArray('.pf-pullquote').forEach(el => {
      const mark = el.querySelector('.pf-pullquote-mark');
      const q    = el.querySelector('q');
      const meta = el.querySelector('.pf-pullquote-meta');
      if (mark) gsap.from(mark, { ...st(el, 'top 82%'), scale: 0.4, opacity: 0, duration: 0.9, ease: 'back.out(1.4)' });
      if (q)    gsap.from(q,    { ...st(el, 'top 82%'), y: 28, opacity: 0, duration: 1, delay: 0.18, ease: 'power3.out' });
      if (meta) gsap.from(meta, { ...st(el, 'top 82%'), opacity: 0, duration: 0.6, delay: 0.45 });
    });

    /* Numbered block */
    gsap.utils.toArray('.pf-numbered-block').forEach(el => {
      const num  = el.querySelector('.pf-numbered-block-num');
      const rest = el.querySelectorAll('h4, p, li');
      if (num)  gsap.from(num,  { ...st(el, 'top 82%'), y: 36, opacity: 0, duration: 0.85, ease: 'expo.out' });
      if (rest.length) gsap.from(rest, { ...st(el, 'top 82%'), y: 16, opacity: 0, duration: 0.6, stagger: 0.06, delay: 0.15, ease: 'power2.out' });
    });

    /* Split sections */
    gsap.utils.toArray('.pf-split > section').forEach((el, i) => {
      gsap.from(el, { ...st(el, 'top 83%'), y: 30, opacity: 0, duration: 0.8, delay: i * 0.14, ease: 'power2.out' });
    });

    /* Sustain callout list items */
    const scallout = document.querySelector('.pf-sustain-callout');
    if (scallout) {
      gsap.from(scallout.querySelector('h3'), {
        scrollTrigger: { trigger: scallout, start: 'top 80%', once: true },
        y: 24, opacity: 0, duration: 0.8, ease: 'power3.out'
      });
      gsap.from(scallout.querySelectorAll('li'), {
        scrollTrigger: { trigger: scallout, start: 'top 76%', once: true },
        x: 24, opacity: 0, duration: 0.55, stagger: 0.07, delay: 0.2, ease: 'power2.out'
      });
    }

    /* mk-cards */
    gsap.utils.toArray('.pf-mk-card').forEach(el => {
      gsap.from(el, { ...st(el), y: 24, opacity: 0, duration: 0.65, ease: 'power2.out' });
    });

    /* Chart cards stagger */
    const chartsGrid = document.querySelector('.pf-charts-grid');
    if (chartsGrid) {
      gsap.from(chartsGrid.querySelectorAll('.pf-chart-card'), {
        ...st(chartsGrid, 'top 84%'),
        y: 18, opacity: 0, duration: 0.55, stagger: 0.09, ease: 'power2.out'
      });
    }

    /* Result stack: cards enter from the right and settle over the previous one */
    const resultStack = document.querySelector('[data-stack]');
    if (resultStack) {
      gsap.from(resultStack.querySelectorAll('.pf-stack-card'), {
        scrollTrigger: { trigger: resultStack, start: 'top 78%', once: true },
        x: 70, y: 18, opacity: 0,
        duration: 1.05, stagger: 0.13, ease: 'power3.out'
      });
    }

    /* 6.4 / 6.5 / 6.6 — stackable deck (desktop only ≥1024px). */
    const PEEK_PX = 14;
    const SCALE_STEP = 0.025;
    const OPACITY_STEP = 0.18;
    const stackState = (i, active, n) => {
      const d = i - active;
      const ad = Math.abs(d);
      if (d === 0) {
        return { xPercent: -50, yPercent: -50, y: 0, scale: 1, opacity: 1, zIndex: n + 10 };
      }
      const dir = d < 0 ? -1 : 1;
      return {
        xPercent: -50,
        yPercent: -50,
        y: dir * (PEEK_PX * ad),
        scale: Math.max(0.86, 1 - ad * SCALE_STEP),
        opacity: Math.max(0.22, 1 - ad * OPACITY_STEP),
        zIndex: Math.max(1, n - ad)
      };
    };

    const setupDeck = (rootEl, stageEl, cardSelector) => {
      const cards = Array.from(stageEl.querySelectorAll(cardSelector));
      if (!cards.length) return;
      const n = cards.length;
      cards.forEach((c, i) => gsap.set(c, stackState(i, 0, n)));

      const tl = gsap.timeline({
        defaults: { ease: 'power2.inOut' },
        scrollTrigger: {
          trigger: rootEl,
          start: 'top top+=92',
          end: () => `+=${(n - 1) * Math.max(window.innerHeight * 0.42, 280)}`,
          pin: stageEl,
          pinSpacing: true,
          scrub: 0.5,
          anticipatePin: 1,
          invalidateOnRefresh: true
        }
      });

      for (let active = 1; active < n; active++) {
        cards.forEach((c, i) => {
          tl.to(c, { ...stackState(i, active, n), duration: 1 }, active - 1);
        });
      }
    };

    const stackMM = gsap.matchMedia();
    stackMM.add('(min-width: 1024px)', () => {
      const adoptStackEl = document.querySelector('[data-adopt-stack]');
      const adoptStage = adoptStackEl && adoptStackEl.querySelector('[data-adopt-stage]');
      if (adoptStackEl && adoptStage) setupDeck(adoptStackEl, adoptStage, '.pf-adopt-card');
    });

    stackMM.add('(max-width: 1023px)', () => {
      const flowCards = document.querySelectorAll('[data-adopt-stack] .pf-adopt-card');
      flowCards.forEach((c) => gsap.set(c, { clearProps: 'all' }));
      flowCards.forEach((c) => {
        gsap.from(c, {
          scrollTrigger: { trigger: c, start: 'top 86%', once: true },
          y: 22, opacity: 0, duration: 0.6, ease: 'power2.out'
        });
      });
    });

    /* Sustainability path */
    const ejes = document.querySelector('[data-ejes]');
    if (ejes) {
      gsap.utils.toArray('.pf-eje').forEach((el, i) => {
        gsap.from(el, {
          scrollTrigger: { trigger: el, start: 'top 82%', once: true },
          x: el.classList.contains('pf-eje--two') ? -52 : 52,
          y: 16,
          opacity: 0,
          duration: 0.9,
          delay: i * 0.05,
          ease: 'power3.out'
        });
      });
    }

    /* Reflexión cards */
    gsap.utils.toArray('.pf-reflexion-card').forEach((el, i) => {
      gsap.from(el, { ...st(el, 'top 86%'), y: 20, opacity: 0, duration: 0.6, delay: i * 0.1, ease: 'power2.out' });
    });

    /* Closing */
    gsap.utils.toArray('.pf-closing-inner > *').forEach((el, i) => {
      gsap.from(el, { ...st(el, 'top 88%'), y: 22, opacity: 0, duration: 0.65, delay: i * 0.1, ease: 'power2.out' });
    });

    ScrollTrigger.refresh();
    ScrollTrigger.update();
    if (window.location.hash) {
      window.setTimeout(() => {
        const target = document.querySelector(window.location.hash);
        if (!target) return;
        scrollToTarget(target, true);
        ScrollTrigger.refresh();
        ScrollTrigger.update();
      }, 120);
    }

  }

  if (typeof gsap !== 'undefined') {
    initGSAP();
  } else {
    window.addEventListener('load', initGSAP, { once: true });
  }

  /* Safety net: si ScrollTrigger tarda o falla, nada queda invisible. */
  let revealSafetyReady = false;
  function initRevealSafetyNet() {
    if (revealSafetyReady) return;
    revealSafetyReady = true;

    const selector = [
      '[data-reveal]',
      '.pf-section-eyebrow',
      '.pf-section-title',
      '.pf-section-lede',
      '.pf-status',
      '.pf-step-num',
      '.pf-step-body',
      '.pf-editorial > p',
      '.pf-editorial-lead',
      '.pf-chart-card',
      '.pf-stack-card',
      '.pf-eje'
    ].join(',');
    const targets = Array.from(new Set(document.querySelectorAll(selector)));

    const reveal = (el) => {
      el.style.removeProperty('opacity');
      el.style.removeProperty('transform');
      el.style.removeProperty('translate');
      el.style.removeProperty('rotate');
      el.style.removeProperty('scale');
      el.classList.add('is-revealed');
    };

    if (!('IntersectionObserver' in window)) {
      targets.forEach(reveal);
      return;
    }

    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        reveal(entry.target);
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.01 });

    targets.forEach(el => io.observe(el));
  }

  window.addEventListener('load', () => {
    window.setTimeout(initRevealSafetyNet, 300);
  }, { once: true });
  window.setTimeout(initRevealSafetyNet, 800);

  /* ══════════════════════════════════════════════
     CHARTS — Chart.js consumiendo /api/form-metrics
     ══════════════════════════════════════════════ */
  const chartsRoot = document.querySelector('[data-charts]');
  if (chartsRoot) {
    if (window.Chart) initCharts(chartsRoot);
    else window.addEventListener('load', () => { if (window.Chart) initCharts(chartsRoot); }, { once: true });
  }

  function initCharts(root) {
    const url = root.dataset.metricsUrl || '/api/form-metrics';
    const charts = new Map();
    let lastPayload = null;

    function readTokens() {
      const css = (n) => getComputedStyle(document.body).getPropertyValue(n).trim();
      return {
        accent:  resolveColor(css('--accent')        || '#5a7339'),
        accent2: resolveColor(css('--accent-2')      || '#7a8e5a'),
        warning: resolveColor(css('--warning')       || '#c89432'),
        success: resolveColor(css('--success')       || '#5fa05f'),
        error:   resolveColor(css('--error')         || '#c75646'),
        info:    resolveColor(css('--info')          || '#5a7339'),
        hair:    resolveColor(css('--hair-2')        || 'rgba(0,0,0,.14)'),
        ink:     resolveColor(css('--text-primary')  || '#2A2C32'),
        bgCard:  resolveColor(css('--bg-card')       || '#FCFBF7'),
      };
    }

    function segColors(t) { return [t.accent, t.warning, t.success, t.accent2, t.error, t.info]; }

    function fetchAndRender() {
      fetch(url, { headers: { Accept: 'application/json' } })
        .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
        .then(p => { lastPayload = p; renderAll(p); })
        .catch(err => { console.warn('[portfolio] metricas error:', err); renderEmpty(); });
    }

    function renderEmpty() {
      root.querySelectorAll('[data-chart-canvas]').forEach(canvas => {
        const ctx = canvas.getContext('2d');
        const { width, height } = canvas.getBoundingClientRect();
        canvas.width = width; canvas.height = height;
        ctx.fillStyle = readTokens().ink;
        ctx.globalAlpha = 0.3;
        ctx.font = '12px ui-monospace, monospace';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('sin datos', width / 2, height / 2);
      });
    }

    function renderAll(payload) {
      const totalEl   = document.querySelector('[data-charts-total]');
      const updatedEl = document.querySelector('[data-charts-updated]');
      if (totalEl)   totalEl.textContent = String(payload.total ?? 0);
      if (updatedEl) updatedEl.textContent = formatDate(payload.lastUpdate);

      const tok = readTokens();
      if (window.Chart?.defaults) {
        Chart.defaults.color       = tok.ink;
        Chart.defaults.borderColor = tok.hair;
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
      }

      Object.entries(payload.questions || {}).forEach(([key, q]) => {
        const canvas = root.querySelector(`canvas[data-chart-canvas="${key}"]`);
        if (!canvas) return;

        const order = Array.isArray(q.order) && q.order.length ? q.order : Object.keys(q.counts || {});
        const labels = [], data = [];
        order.forEach(l => { labels.push(l); data.push((q.counts?.[l]) || 0); });
        Object.keys(q.counts || {}).forEach(l => {
          if (!order.includes(l)) { labels.push(l); data.push(q.counts[l]); }
        });

        const colors = segColors(tok).slice(0, labels.length);
        if (charts.has(key)) charts.get(key).destroy();

        charts.set(key, new Chart(canvas, {
          type: 'doughnut',
          data: {
            labels,
            datasets: [{ data, backgroundColor: colors, borderColor: tok.bgCard, borderWidth: 2, hoverOffset: 6 }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            animation: { duration: 600, easing: 'easeOutCubic' },
            cutout: '58%',
            plugins: {
              legend: {
                position: 'right', align: 'center',
                labels: {
                  color: tok.ink,
                  font: { family: getComputedStyle(document.body).fontFamily, size: 12 },
                  boxWidth: 10, boxHeight: 10, padding: 10, usePointStyle: true,
                  generateLabels: (c) => {
                    const ds = c.data.datasets[0] || { data: [] };
                    const total = ds.data.reduce((a, b) => a + Number(b || 0), 0) || 1;
                    return c.data.labels.map((lbl, i) => {
                      const v = Number(ds.data[i] || 0);
                      return {
                        text: `${truncate(lbl, 32)} — ${Math.round((v / total) * 100)}%`,
                        fillStyle: ds.backgroundColor[i], strokeStyle: ds.backgroundColor[i],
                        lineWidth: 0, fontColor: tok.ink, hidden: false, index: i
                      };
                    });
                  }
                }
              },
              tooltip: {
                backgroundColor: tok.ink, titleColor: tok.bgCard, bodyColor: tok.bgCard,
                borderColor: tok.hair, borderWidth: 1, padding: 10, cornerRadius: 8,
                displayColors: false,
                callbacks: {
                  label: (ctx) => {
                    const total = ctx.dataset.data.reduce((a, b) => a + Number(b || 0), 0) || 1;
                    const v = Number(ctx.parsed || 0);
                    return `${v} respuestas · ${((v / total) * 100).toFixed(1).replace(/\.0$/, '')}%`;
                  },
                  title: (items) => items[0]?.label ?? ''
                }
              }
            }
          }
        }));
      });
    }

    new MutationObserver(() => { if (lastPayload) renderAll(lastPayload); })
      .observe(document.body, { attributes: true, attributeFilter: ['data-theme'] });

    fetchAndRender();
  }

  /* helpers */
  function truncate(s, n) { return typeof s === 'string' && s.length > n ? s.slice(0, n - 1) + '…' : s; }
  function formatDate(iso) {
    if (!iso) return '—';
    try {
      const d = new Date(iso), p = n => String(n).padStart(2, '0');
      return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()} ${p(d.getHours())}:${p(d.getMinutes())}`;
    } catch { return '—'; }
  }
  function resolveColor(v) {
    v = (v || '').trim();
    if (!v) return '#5a7339';
    if (/^(#|rgb|hsl)/.test(v)) return v;
    const tmp = Object.assign(document.createElement('span'), { style: `color:${v};display:none` });
    document.body.appendChild(tmp);
    const c = getComputedStyle(tmp).color;
    document.body.removeChild(tmp);
    return c || '#5a7339';
  }
})();
