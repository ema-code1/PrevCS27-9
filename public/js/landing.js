/* PrevCS landing — scroll, counters, live chart, recommender, carousel, parallax */

(() => {
  'use strict';

  /* ---------- Sticky header scrolled state ---------- */
  const header = document.querySelector('.landing-header');
  const onScroll = () => header && header.classList.toggle('scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Reveal on scroll ---------- */
  const revealSelectors = [
    '.section-head', '.problem-card', '.metric-card', '.sensor-card',
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
    const RECS = {
      temperatura:  { s: ['DS18B20 — sonda temperatura', 'DHT22 — ambiente'],         t: ['Historico termico 24h', 'Mapa de calor por area'],      a: ['Umbral &gt; 75 °C', 'Tendencia ascendente sostenida'] },
      vibracion:    { s: ['SW-420 — vibracion digital', 'ADXL345 — acelerometro'],    t: ['Espectro de vibracion', 'Predictivo de motores'],       a: ['Pico fuera de banda', 'Desbalance progresivo'] },
      humedad:      { s: ['DHT22 — humedad relativa', 'HIH-6130 — alta precision'],   t: ['Humedad por sala', 'Confort y materia prima'],          a: ['Humedad &gt; 80%', 'Caida brusca'] },
      energia:      { s: ['SCT-013 — corriente AC', 'PZEM-004T — energia total'],     t: ['Consumo por linea', 'Comparativa de turnos'],           a: ['Pico de consumo', 'Factor de potencia bajo'] },
      produccion:   { s: ['HC-SR04 — conteo por nivel', 'E18-D80 — proximidad'],      t: ['OEE en vivo', 'Produccion por turno'],                  a: ['Caida de rendimiento', 'Parada prolongada'] },
      stock:        { s: ['HC-SR04 — nivel ultrasonido', 'KY025 — magnetico'],         t: ['Niveles de silos', 'Stock por insumo'],                 a: ['Stock &lt; minimo', 'Reposicion sugerida'] },
      mantenimiento:{ s: ['ESP32 — gateway central', 'SW-420 — vibracion'],           t: ['Timeline mantenimiento', 'Predictivo OEE'],             a: ['Horas de uso superadas', 'Patron de fallo previo'] },
      presion:      { s: ['BMP280 — presion barometrica', 'MPX5700 — alta presion'],  t: ['Presion por circuito', 'Curva de operacion'],           a: ['Sobrepresion', 'Caida por fuga'] }
    };

    const chips = recRoot.querySelectorAll('[data-rec]');
    const $s = recRoot.querySelector('#rec-sensors');
    const $t = recRoot.querySelector('#rec-templates');
    const $a = recRoot.querySelector('#rec-alerts');

    const update = () => {
      const active = [...chips].filter(c => c.classList.contains('on')).map(c => c.dataset.rec);
      const setS = new Set(), setT = new Set(), setA = new Set();
      if (active.length === 0) {
        const empty = '<li>Elegi al menos una metrica…</li>';
        $s.innerHTML = empty; $t.innerHTML = empty; $a.innerHTML = empty;
        return;
      }
      active.forEach(k => {
        const r = RECS[k]; if (!r) return;
        r.s.forEach(x => setS.add(x));
        r.t.forEach(x => setT.add(x));
        r.a.forEach(x => setA.add(x));
      });
      if (active.length > 0) setS.add('Modulo ESP32 — gateway WiFi/MQTT');

      const render = (set, host) => {
        host.innerHTML = '';
        [...set].slice(0, 6).forEach((x, i) => {
          const li = document.createElement('li');
          li.innerHTML = x;
          li.style.animationDelay = (i * 60) + 'ms';
          host.appendChild(li);
        });
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
    let timer = null;

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
      const cardWidth = cards[0] ? cards[0].getBoundingClientRect().width + 14 : 0;
      track.style.transform = `translateX(${-index * cardWidth}px)`;
    };
    const stopAuto = () => { if (timer) { clearInterval(timer); timer = null; } };
    const startAuto = () => {
      stopAuto();
      timer = setInterval(() => {
        const max = getMax();
        index = (index + 1) > max ? 0 : index + 1;
        update();
      }, 4200);
    };
    const goPrev = (e) => {
      if (e) { e.preventDefault(); e.stopPropagation(); }
      stopAuto();
      const max = getMax();
      index = index <= 0 ? max : index - 1;  // loop al final si pasa por 0
      update();
      setTimeout(startAuto, 6000);
    };
    const goNext = (e) => {
      if (e) { e.preventDefault(); e.stopPropagation(); }
      stopAuto();
      const max = getMax();
      index = index >= max ? 0 : index + 1;  // loop al inicio si pasa el max
      update();
      setTimeout(startAuto, 6000);
    };

    if (prev) prev.addEventListener('click', goPrev);
    if (next) next.addEventListener('click', goNext);

    tc.addEventListener('mouseenter', stopAuto);
    tc.addEventListener('mouseleave', startAuto);
    window.addEventListener('resize', update);

    update();
    startAuto();
  }

  /* ---------- Hero floating cards parallax ---------- */
  const floats = document.querySelectorAll('.hdp-float');
  const hero = document.querySelector('.hero-section');
  if (floats.length && hero && window.matchMedia('(min-width: 980px)').matches) {
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
        f.style.translate = `${tx * depth}px ${ty * depth}px`;
      });
      requestAnimationFrame(loop);
    };
    loop();
  }

  /* ---------- Hero main card subtle tilt with scroll ---------- */
  const main = document.querySelector('.hdp-main');
  if (main) {
    window.addEventListener('scroll', () => {
      const r = main.getBoundingClientRect();
      const center = r.top + r.height / 2;
      const vh = window.innerHeight;
      const t = Math.max(-1, Math.min(1, (center - vh / 2) / vh));
      main.style.transform = `rotateY(${-3 + t * 2}deg) rotateX(${2 - t * 2}deg) translateY(${t * -8}px)`;
    }, { passive: true });
  }
})();
