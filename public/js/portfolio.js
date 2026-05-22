/* PrevCS portfolio — scrollspy, smooth scroll, reveals, Chart.js charts, copy link */

(() => {
  'use strict';

  /* ---------- Header scrolled state ---------- */
  const header = document.querySelector('.landing-header');
  const onScroll = () => header && header.classList.toggle('scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Smooth anchor scroll within portfolio ---------- */
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const id = a.getAttribute('href').slice(1);
    if (!id) return;
    const target = document.getElementById(id);
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    history.replaceState(null, '', '#' + id);
  });

  /* ---------- Scrollspy: highlight active section in mega menu ---------- */
  const sectionIds = [
    'pagina-principal', 'plan-operativo', 'imagen-corporativa',
    'quienes-somos', 'acerca-de-prevcs', 'analisis-competencia', 'analisis-mercado'
  ];
  const sections = sectionIds
    .map(id => document.getElementById(id))
    .filter(Boolean);

  const megaItems = new Map();
  document.querySelectorAll('.hd-portfolio-mega [data-section-link]').forEach(a => {
    megaItems.set(a.dataset.sectionLink, a);
  });

  const setActive = (id) => {
    megaItems.forEach((a, key) => a.classList.toggle('hpm-item--current', key === id));
  };

  if ('IntersectionObserver' in window && sections.length) {
    const so = new IntersectionObserver((entries) => {
      entries.forEach(en => {
        if (en.isIntersecting) setActive(en.target.id);
      });
    }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
    sections.forEach(s => so.observe(s));
  }

  /* ---------- Reveal on scroll ---------- */
  const revealEls = document.querySelectorAll(
    '.pf-section-head, .pf-plan-card, .pf-brand-card, .pf-member-card, .pf-about-card, ' +
    '.pf-foda-card, .pf-mk-card, .pf-chart-card, .pf-reflexion-card, ' +
    '.pf-hero-head > *, .pf-hero-meta > div, .pf-closing-inner > *'
  );
  revealEls.forEach(el => el.setAttribute('data-pf-reveal', ''));
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(en => {
        if (en.isIntersecting) {
          en.target.classList.add('in');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(el => io.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('in'));
  }

  /* ---------- Copy form link ---------- */
  const FORM_LINK = 'https://docs.google.com/forms/d/e/1FAIpQLSe8SOVPspfusSJ0TT7QcdSGnLbcb2MOWjdHmdk4HOlrMVANHA/viewform';
  document.querySelectorAll('[data-copy-form-link]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const label = btn.querySelector('[data-copy-label]');
      const original = label ? label.textContent : '';
      try {
        await navigator.clipboard.writeText(FORM_LINK);
        if (label) label.textContent = 'Copiado ✓';
      } catch (err) {
        if (label) label.textContent = 'Error al copiar';
      }
      setTimeout(() => { if (label) label.textContent = original; }, 1800);
    });
  });

  /* =================================================================
     CHARTS — Chart.js consumiendo /api/form-metrics
     ================================================================= */
  const chartsRoot = document.querySelector('[data-charts]');
  if (chartsRoot && window.Chart) {
    initCharts(chartsRoot);
  } else if (chartsRoot) {
    // Chart.js todavia no cargo (defer race). Esperamos a load.
    window.addEventListener('load', () => {
      if (window.Chart) initCharts(chartsRoot);
    }, { once: true });
  }

  function initCharts(root) {
    const url = root.dataset.metricsUrl || '/api/form-metrics';
    const charts = new Map(); // sectionKey → Chart instance
    let lastPayload = null;   // cache del ultimo payload para re-render al cambiar tema

    // Paleta de colores que toma los tokens del proyecto.
    // Reactiva al tema: cada vez que cambia data-theme en body, re-leemos.
    function readTokens() {
      const css = (name) => getComputedStyle(document.body).getPropertyValue(name).trim();
      return {
        accent:   resolveColor(css('--accent')   || '#5a7339'),
        accent2:  resolveColor(css('--accent-2') || '#7a8e5a'),
        warning:  resolveColor(css('--warning')  || '#c89432'),
        success:  resolveColor(css('--success')  || '#5fa05f'),
        error:    resolveColor(css('--error')    || '#c75646'),
        info:     resolveColor(css('--info')     || '#5a7339'),
        hair:     resolveColor(css('--hair-2')   || 'rgba(0,0,0,.14)'),
        ink:      resolveColor(css('--text-primary')   || '#2A2C32'),
        muted:    resolveColor(css('--text-muted')     || '#6F7278'),
        faint:    resolveColor(css('--text-faint')     || '#9A9CA1'),
        bgCard:   resolveColor(css('--bg-card')        || '#FCFBF7'),
      };
    }

    // Paleta de SEGMENTOS — orden visualmente diferenciado, derivado de tokens.
    function segmentColors(t) {
      return [t.accent, t.warning, t.success, t.accent2, t.error, t.info];
    }

    function fetchAndRender() {
      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
        .then(payload => { lastPayload = payload; renderAll(payload); })
        .catch(err => {
          console.warn('[portfolio] No se pudo cargar metricas:', err);
          renderEmpty();
        });
    }

    function renderEmpty() {
      // Si falla la API, dibujamos un mensaje claro dentro de cada canvas.
      root.querySelectorAll('[data-chart-canvas]').forEach(canvas => {
        const ctx = canvas.getContext('2d');
        const rect = canvas.getBoundingClientRect();
        canvas.width  = rect.width;
        canvas.height = rect.height;
        ctx.fillStyle = readTokens().faint;
        ctx.font = '12px ui-monospace, monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('sin datos', rect.width / 2, rect.height / 2);
      });
    }

    function renderAll(payload) {
      // Meta header (total + fecha)
      const totalEl   = document.querySelector('[data-charts-total]');
      const updatedEl = document.querySelector('[data-charts-updated]');
      if (totalEl)   totalEl.textContent = String(payload.total ?? 0);
      if (updatedEl) updatedEl.textContent = formatDate(payload.lastUpdate);

      const tokens = readTokens();

      // Defaults globales — Chart.js v4 los usa cuando no hay override explícito.
      // Esto garantiza que TODOS los textos (legend, tooltip, axis) usen el ink
      // del tema activo aunque algunos componentes no lean nuestras options.
      if (window.Chart && Chart.defaults) {
        Chart.defaults.color       = tokens.ink;
        Chart.defaults.borderColor = tokens.hair;
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily || 'system-ui, sans-serif';
      }

      Object.entries(payload.questions || {}).forEach(([key, q]) => {
        const canvas = root.querySelector(`canvas[data-chart-canvas="${key}"]`);
        if (!canvas) return;

        // Construir labels y data en el orden definido por el backend.
        const labels = [];
        const data   = [];
        const order  = Array.isArray(q.order) && q.order.length ? q.order : Object.keys(q.counts || {});
        order.forEach(label => {
          const v = (q.counts && q.counts[label]) || 0;
          labels.push(label);
          data.push(v);
        });

        // Si hay valores presentes que NO esten en `order`, los agregamos al final.
        Object.keys(q.counts || {}).forEach(label => {
          if (!order.includes(label)) {
            labels.push(label);
            data.push(q.counts[label]);
          }
        });

        const colors = segmentColors(tokens).slice(0, labels.length);

        // Si el chart ya existe, lo destruimos para repintar con los tokens correctos.
        if (charts.has(key)) {
          charts.get(key).destroy();
        }

        const chart = new Chart(canvas, {
          type: 'doughnut',
          data: {
            labels,
            datasets: [{
              data,
              backgroundColor: colors,
              borderColor:     tokens.bgCard,
              borderWidth:     2,
              hoverOffset:     6,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 600, easing: 'easeOutCubic' },
            cutout: '58%',
            plugins: {
              legend: {
                position: 'right',
                align: 'center',
                labels: {
                  color: tokens.ink,
                  font: { family: getComputedStyle(document.body).fontFamily, size: 12 },
                  boxWidth: 10,
                  boxHeight: 10,
                  padding: 10,
                  usePointStyle: true,
                  generateLabels: (c) => {
                    const ds = c.data.datasets[0] || { data: [] };
                    const total = (ds.data || []).reduce((a, b) => a + Number(b || 0), 0) || 1;
                    return (c.data.labels || []).map((label, i) => {
                      const value = Number(ds.data[i] || 0);
                      const pct = total ? Math.round((value / total) * 100) : 0;
                      return {
                        text: `${truncate(label, 32)} — ${pct}%`,
                        fillStyle: ds.backgroundColor[i],
                        strokeStyle: ds.backgroundColor[i],
                        lineWidth: 0,
                        // Color del texto del label — necesario en v4 cuando
                        // generateLabels custom: Chart.js no aplica labels.color
                        // automaticamente si no se especifica por-label.
                        fontColor: tokens.ink,
                        hidden: false,
                        index: i,
                      };
                    });
                  },
                },
              },
              tooltip: {
                backgroundColor: tokens.ink,
                titleColor: tokens.bgCard,
                bodyColor: tokens.bgCard,
                borderColor: tokens.hair,
                borderWidth: 1,
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                  label: (ctx) => {
                    const ds = ctx.dataset.data || [];
                    const total = ds.reduce((a, b) => a + Number(b || 0), 0) || 1;
                    const v = Number(ctx.parsed || 0);
                    const pct = ((v / total) * 100).toFixed(1).replace(/\.0$/, '');
                    return `${v} respuestas · ${pct}%`;
                  },
                  title: (items) => items.length ? items[0].label : '',
                },
              },
            },
          },
        });

        charts.set(key, chart);
      });
    }

    // Re-render cuando cambia el tema. Destruimos y recreamos los charts para
    // que Chart.js recompute todos los colores derivados de las CSS vars (no
    // alcanza con mutar options — la libreria cachea muchos valores).
    const themeObserver = new MutationObserver(() => {
      if (lastPayload) renderAll(lastPayload);
    });
    themeObserver.observe(document.body, { attributes: true, attributeFilter: ['data-theme'] });

    // Render inicial
    fetchAndRender();
  }

  /* ---------- Helpers ---------- */
  function truncate(s, n) {
    if (typeof s !== 'string') return s;
    return s.length <= n ? s : s.slice(0, n - 1) + '…';
  }
  function formatDate(iso) {
    if (!iso) return '—';
    try {
      const d = new Date(iso);
      const pad = (n) => String(n).padStart(2, '0');
      return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    } catch { return '—'; }
  }
  /**
   * Resuelve un color de CSS (incluido oklch()) a un RGB que Chart.js puede usar
   * en backgroundColor/borderColor. Usa un span temporal para que el navegador
   * normalice el formato.
   */
  function resolveColor(value) {
    const v = (value || '').trim();
    if (!v) return '#5a7339';
    if (v.startsWith('#') || v.startsWith('rgb') || v.startsWith('hsl')) return v;
    // oklch / oklab / named colors — resolver via DOM
    const tmp = document.createElement('span');
    tmp.style.color = v;
    tmp.style.display = 'none';
    document.body.appendChild(tmp);
    const resolved = getComputedStyle(tmp).color;
    document.body.removeChild(tmp);
    return resolved || '#5a7339';
  }
})();
