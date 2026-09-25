/* =====================================================================
   PrevCS · IoT Sensor Charts
   Lógica compartida por registros-iot/espacio.php y esp32/detalle.php.
   - Cambio de sensor (section-switch) con persistencia de pestaña (F5).
   - Carga de la serie del sensor desde la API (con rango de fechas opcional).
   - Dibujo del gráfico de línea con ejes: grilla vertical + marcas/labels
     en el eje Y (Valores) y el eje X (Tiempo).
   - Redibujado incremental: los nodos del SVG se reutilizan entre refrescos
     (solo se actualizan atributos) para que el "live" no parpadee.
   - Tooltip con el valor exacto al pasar el mouse por cada punto.
   - Integración con el date range selector (js/date-range-picker.js).
   ===================================================================== */
(function () {
  'use strict';

  var SVGNS = 'http://www.w3.org/2000/svg';
  // Área de ploteo dentro del viewBox 640×360 (coincide con la grilla del SVG)
  var X0 = 78, X1 = 600, Y0 = 48, Y1 = 304;
  var LEVELS = 4;             // 5 líneas horizontales → 4 intervalos en Y
  var X_TICKS = 6;            // marcas máximas en el eje X

  function el(tag, attrs) {
    var e = document.createElementNS(SVGNS, tag);
    for (var k in attrs) e.setAttribute(k, attrs[k]);
    return e;
  }

  function setAttrs(node, attrs) {
    for (var k in attrs) node.setAttribute(k, attrs[k]);
  }

  // Formatea un valor del eje Y de forma compacta (sin ceros sobrantes).
  function fmtVal(v) {
    var a = Math.abs(v), s;
    if (a >= 1000) s = (v / 1000).toFixed(a >= 10000 ? 0 : 1) + 'k';
    else if (a >= 100) s = v.toFixed(0);
    else if (a >= 1) s = v.toFixed(1);
    else s = v.toFixed(2);
    return s.replace(/\.0+($|k)/, '$1').replace(/(\.\d*?)0+($|k)/, '$1$2');
  }

  // Deja exactamente `count` hijos <tag> dentro de `parent`, reutilizando los
  // existentes. Al no recrear nodos en cada refresco no se reinician las
  // animaciones de entrada del CSS → el gráfico se actualiza sin parpadear.
  function syncChildren(parent, tag, count, className) {
    var nodes = [], i;
    for (i = 0; i < parent.children.length; i++) {
      if (parent.children[i].tagName === tag) nodes.push(parent.children[i]);
    }
    while (nodes.length < count) {
      var node = el(tag, className ? { 'class': className } : {});
      parent.appendChild(node);
      nodes.push(node);
    }
    while (nodes.length > count) parent.removeChild(nodes.pop());
    return nodes;
  }

  function hideTooltip(grid) {
    if (grid._tip) grid._tip.classList.remove('is-visible');
  }

  /* Líneas punteadas del rango de alerta (mín./máx. del sensor). El grupo
     se crea la primera vez: el SVG de la vista no lo trae. */
  function limitsLayer(grid) {
    var layer = grid.querySelector('[data-sensor-limits]');
    if (layer) return layer;
    var svg = grid.querySelector('.iot-line-chart');
    var line = grid.querySelector('[data-sensor-line]');
    layer = el('g', { 'class': 'iot-line-chart__limits', 'data-sensor-limits': '' });
    svg.insertBefore(layer, line);
    return layer;
  }

  function hasLimit(v) { return v !== null && v !== undefined && v !== ''; }

  function drawLimits(grid, limits, min, max) {
    var layer = limitsLayer(grid);
    var list = [];
    if (limits && hasLimit(limits.max)) list.push({ v: Number(limits.max), t: 'máx.' });
    if (limits && hasLimit(limits.min)) list.push({ v: Number(limits.min), t: 'mín.' });
    var count = min === null ? 0 : list.length;
    var lines = syncChildren(layer, 'line', count, 'iot-line-chart__limit');
    var texts = syncChildren(layer, 'text', count, 'iot-line-chart__limit-label');
    if (!count) return;
    list.forEach(function (item, i) {
      var y = Y1 - (Y1 - Y0) * ((item.v - min) / (max - min));
      setAttrs(lines[i], { x1: X0, y1: y, x2: X1, y2: y });
      setAttrs(texts[i], { x: X1, y: y - 5, 'text-anchor': 'end' });
      texts[i].textContent = item.t + ' ' + fmtVal(item.v);
    });
  }

  function drawLine(grid, points, limits) {
    var line = grid.querySelector('[data-sensor-line]');
    var dots = grid.querySelector('[data-sensor-points]');
    var empty = grid.querySelector('[data-sensor-empty]');
    var vGrid = grid.querySelector('[data-grid-v]');
    var yAxis = grid.querySelector('[data-axis-y]');
    var xAxis = grid.querySelector('[data-axis-x]');

    hideTooltip(grid);

    if (!points.length) {
      line.setAttribute('points', '');
      syncChildren(dots, 'circle', 0);
      syncChildren(vGrid, 'line', 0);
      syncChildren(yAxis, 'line', 0);
      syncChildren(yAxis, 'text', 0);
      syncChildren(xAxis, 'line', 0);
      syncChildren(xAxis, 'text', 0);
      drawLimits(grid, limits, null, null);
      if (empty) empty.hidden = false;
      return;
    }
    if (empty) empty.hidden = true;

    var values = points.map(function (p) { return p.value; });
    // El rango del eje incluye los límites de alerta: así la línea punteada
    // siempre se ve y queda claro cuánto falta para cruzarla.
    if (limits && hasLimit(limits.max)) values = values.concat([Number(limits.max)]);
    if (limits && hasLimit(limits.min)) values = values.concat([Number(limits.min)]);
    var min = Math.min.apply(null, values);
    var max = Math.max.apply(null, values);
    if (min === max) { min -= 1; max += 1; }

    var n = points.length;
    var coords = points.map(function (p, i) {
      var x = n === 1 ? (X0 + X1) / 2 : X0 + (X1 - X0) * (i / (n - 1));
      var y = Y1 - (Y1 - Y0) * ((p.value - min) / (max - min));
      return [x, y];
    });

    /* ── Eje Y: marcas + valores alineados a la grilla horizontal ── */
    var yTicks = syncChildren(yAxis, 'line', LEVELS + 1, 'iot-line-chart__tick');
    var yTexts = syncChildren(yAxis, 'text', LEVELS + 1);
    for (var l = 0; l <= LEVELS; l++) {
      var yy = Y0 + (Y1 - Y0) * (l / LEVELS);
      var val = max - (max - min) * (l / LEVELS);
      setAttrs(yTicks[l], { x1: X0 - 6, y1: yy, x2: X0, y2: yy });
      setAttrs(yTexts[l], { x: X0 - 11, y: yy + 3.4, 'text-anchor': 'end' });
      yTexts[l].textContent = fmtVal(val);
    }

    /* ── Eje X: líneas verticales + marcas de tiempo ── */
    var ticks = Math.min(X_TICKS, n);
    var tickIdx = [], seen = {};
    for (var t = 0; t < ticks; t++) {
      var idx = ticks === 1 ? 0 : Math.round(t * (n - 1) / (ticks - 1));
      if (seen[idx]) continue;
      seen[idx] = true;
      tickIdx.push(idx);
    }
    var vLines = syncChildren(vGrid, 'line', tickIdx.length);
    var xTicksEls = syncChildren(xAxis, 'line', tickIdx.length, 'iot-line-chart__tick');
    var xTexts = syncChildren(xAxis, 'text', tickIdx.length);
    tickIdx.forEach(function (pIdx, i) {
      var cx = coords[pIdx][0];
      setAttrs(vLines[i], { x1: cx, y1: Y0, x2: cx, y2: Y1 });
      setAttrs(xTicksEls[i], { x1: cx, y1: Y1, x2: cx, y2: Y1 + 5 });
      setAttrs(xTexts[i], { x: cx, y: Y1 + 18, 'text-anchor': 'middle' });
      xTexts[i].textContent = points[pIdx].label || '';
    });

    /* ── Serie + puntos (con data para el tooltip) ── */
    line.setAttribute('points', coords.map(function (c) {
      return c[0].toFixed(1) + ',' + c[1].toFixed(1);
    }).join(' '));
    var circles = syncChildren(dots, 'circle', n, 'iot-line-chart__point');
    coords.forEach(function (c, i) {
      setAttrs(circles[i], {
        cx: c[0].toFixed(1),
        cy: c[1].toFixed(1),
        r: 3,
        'data-value': points[i].value,
        'data-when': points[i].at || points[i].label || ''
      });
      // Lecturas fuera de rango en rojo.
      circles[i].classList.toggle('is-alert', !!points[i].state && points[i].state !== 'ok');
    });

    drawLimits(grid, limits, min, max);
  }

  /* ── Tarjeta "Estado": motor ilustrado + badge según la última lectura ── */
  var STATE_LABELS = { high: 'Sobre el límite', low: 'Bajo el límite', ok: 'Normal' };
  var STATE_BADGES = { high: 'badge--error', low: 'badge--warning', ok: 'badge--success' };
  var STATE_IMAGES = {
    temperature: { high: 'temperature-high', low: 'temperature-low' },
    vibration: { high: 'vibration-high', low: 'vibration-medium' },
    rpm: { high: 'rpm-high', low: 'rpm-low' }
  };

  function fmtLimit(v, unit) { return fmtVal(Number(v)) + (unit ? ' ' + unit : ''); }

  function updateState(grid, data) {
    var card = grid.querySelector('[data-sensor-state]');
    if (!card) return;
    var state = data.state || 'none';
    var unit = grid.dataset.sensorUnit || '';
    card.setAttribute('data-sensor-state', state);

    var badge = card.querySelector('[data-sensor-state-badge]');
    if (badge) {
      badge.className = 'badge ' + (STATE_BADGES[state] || 'badge--info');
      badge.textContent = STATE_LABELS[state] || 'Sin datos';
    }

    var code = card.dataset.sensorCode || '';
    var name = (STATE_IMAGES[code] && STATE_IMAGES[code][state]) || 'motor-ok';
    var base = card.dataset.imagesBase || '';
    card.querySelectorAll('[data-sensor-state-img]').forEach(function (img) {
      var src = base + name + (img.dataset.sensorStateImg === 'dark' ? '-dark' : '') + '.png';
      if (img.getAttribute('src') !== src) img.setAttribute('src', src);
    });

    var range = card.querySelector('[data-sensor-state-range]');
    if (range) {
      var parts = [];
      if (hasLimit(data.alert_min)) parts.push('mín. ' + fmtLimit(data.alert_min, unit));
      if (hasLimit(data.alert_max)) parts.push('máx. ' + fmtLimit(data.alert_max, unit));
      range.textContent = parts.length ? 'Rango aceptable: ' + parts.join(' · ') + '.' : 'Sin límites de alerta configurados.';
    }
  }

  /* ── Tooltip flotante: valor exacto + hora del punto ── */
  function initTooltip(grid) {
    var chart = grid.querySelector('.iot-chart');
    if (!chart) return;

    var tip = document.createElement('div');
    tip.className = 'iot-chart__tooltip';
    var tipVal = document.createElement('strong');
    var tipWhen = document.createElement('span');
    tip.appendChild(tipVal);
    tip.appendChild(tipWhen);
    chart.appendChild(tip);
    grid._tip = tip;

    var unit = grid.dataset.sensorUnit || '';

    chart.addEventListener('mouseover', function (e) {
      var c = e.target.closest ? e.target.closest('circle[data-value]') : null;
      if (!c) return;

      tipVal.textContent = c.getAttribute('data-value') + (unit ? ' ' + unit : '');
      var when = c.getAttribute('data-when') || '';
      tipWhen.textContent = when;
      tipWhen.hidden = !when;

      // Posición del punto relativa al contenedor del gráfico, clampeada
      // para que el tooltip no se salga de la card.
      var cRect = c.getBoundingClientRect();
      var hRect = chart.getBoundingClientRect();
      var x = cRect.left + cRect.width / 2 - hRect.left;
      var y = cRect.top - hRect.top;
      var half = tip.offsetWidth / 2 + 4;
      x = Math.max(half, Math.min(hRect.width - half, x));
      tip.style.left = x + 'px';
      tip.style.top = y + 'px';
      tip.classList.add('is-visible');
    });

    chart.addEventListener('mouseout', function (e) {
      if (e.target.closest && e.target.closest('circle[data-value]')) {
        tip.classList.remove('is-visible');
      }
    });
  }

  // API pública mínima (re-render programático / pruebas).
  window.IotCharts = { drawLine: drawLine };

  document.addEventListener('DOMContentLoaded', function () {
    /* ── Section switch ── */
    var switchers = document.querySelectorAll('[data-section-target]');
    var panes = document.querySelectorAll('[data-section-pane]');

    // Persistencia de la pestaña activa: F5 vuelve a donde estaba el usuario.
    var TAB_KEY = 'prevcs:sensor-tab:' + location.pathname;
    function rememberTab(target) {
      try { sessionStorage.setItem(TAB_KEY, target); } catch (err) { /* sin storage */ }
    }

    function activatePane(target) {
      panes.forEach(function (p) { p.classList.toggle('is-active', p.dataset.sectionPane === target); });
      switchers.forEach(function (input) {
        var isActive = input.dataset.sectionTarget === target;
        input.checked = isActive;
        var lbl = input.closest('.section-switch__radio');
        if (lbl) lbl.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      rememberTab(target);
      loadSensor(target);
    }
    switchers.forEach(function (input) {
      input.addEventListener('change', function () { activatePane(input.dataset.sectionTarget); });
    });

    /* ── Carga de la serie de un sensor ── */
    var loaded = new Set();

    function setSkeleton(grid, on) {
      var chart = grid.querySelector('.iot-chart');
      var skel = grid.querySelector('[data-sensor-skeleton]');
      if (chart) chart.classList.toggle('is-skeleton', on);
      if (skel) skel.hidden = !on;
    }

    function setError(grid, on) {
      var chart = grid.querySelector('.iot-chart');
      var err = grid.querySelector('[data-sensor-error]');
      if (chart) chart.classList.toggle('has-error', on);
      if (err) err.hidden = !on;
    }

    function loadSensor(target, force, isPoll) {
      var grid = document.querySelector('[data-section-pane="' + target + '"] .iot-grid');
      if (!grid) return;
      if (loaded.has(target) && !force) return;
      var firstLoad = !loaded.has(target);
      loaded.add(target);

      var sensorId = grid.dataset.sensorId;
      var unit = grid.dataset.sensorUnit || '';
      var code = grid.dataset.sensorCode || '';
      var range = grid._range;

      var url = grid.dataset.readingsUrl + '?sensor=' + encodeURIComponent(sensorId);
      if (range && range.from && range.to) {
        url += '&from=' + encodeURIComponent(range.from) + '&to=' + encodeURIComponent(range.to);
      }

      var card = grid.querySelector('.iot-chart-card');
      // El refresco en vivo no atenúa el gráfico ni muestra skeleton: solo
      // pisa los datos cuando llegan (sin parpadeo).
      if (card && !isPoll) card.classList.add('is-loading');
      if (!isPoll) {
        setError(grid, false);
        if (firstLoad) setSkeleton(grid, true);
      }

      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function (data) {
          setSkeleton(grid, false);
          setError(grid, false);

          var points = data.points || [];
          drawLine(grid, points, { min: data.alert_min, max: data.alert_max });
          updateState(grid, data);

          // El CSV respeta el rango de fechas elegido.
          var csv = grid.querySelector('[data-sensor-csv]');
          if (csv) {
            var csvUrl = grid.dataset.readingsUrl + '?sensor=' + encodeURIComponent(sensorId) + '&format=csv';
            if (range && range.from && range.to) {
              csvUrl += '&from=' + encodeURIComponent(range.from) + '&to=' + encodeURIComponent(range.to);
            }
            csv.setAttribute('href', csvUrl);
          }

          var latestEl = grid.querySelector('[data-sensor-latest]');
          var latestAtEl = grid.querySelector('[data-sensor-latest-at]');
          var obsEl = grid.querySelector('[data-sensor-observation]');
          var histEl = grid.querySelector('[data-sensor-history]');
          var rangeNote = (range && range.from) ? ' (rango filtrado)' : '';

          if (data.latest !== null && data.latest !== undefined) {
            if (latestEl) latestEl.textContent = data.latest + ' ' + unit;
            if (latestAtEl && points.length) {
              latestAtEl.textContent = points[points.length - 1].at || points[points.length - 1].label || '—';
            }
            var obs = 'Última medición: ' + data.latest + ' ' + unit + '. ' +
              points.length + ' lecturas' + rangeNote + '.';
            if (code === 'rpm') {
              obs += ' Motor: ' + (Number(data.latest) > 0 ? 'ANDANDO ▶' : 'DETENIDO ⏸');
            }
            if (data.state === 'high') {
              obs += ' ⚠ Supera el máximo de ' + fmtLimit(data.alert_max, unit) + ': se avisó a todo el espacio.';
            } else if (data.state === 'low') {
              obs += ' ⚠ Está debajo del mínimo de ' + fmtLimit(data.alert_min, unit) + ': se avisó a todo el espacio.';
            }
            if (obsEl) obsEl.textContent = obs;
          } else if (obsEl) {
            obsEl.textContent = range && range.from
              ? 'Sin lecturas en el rango seleccionado.'
              : 'Sin lecturas todavía para este sensor.';
          }

          // "Última: <ts>" del encabezado de la placa (vista detalle):
          // sin filtro de rango, la cola de la serie es la última lectura real.
          var headLast = document.querySelector('[data-device-last-seen]');
          if (headLast && points.length && !(range && range.from)) {
            headLast.textContent = points[points.length - 1].at || headLast.textContent;
          }

          if (histEl) {
            if (!points.length) {
              histEl.innerHTML = '<p class="text-muted">Sin historial.</p>';
            } else {
              histEl.innerHTML = points.slice(-50).reverse().map(function (p) {
                var alert = p.state && p.state !== 'ok';
                return '<div class="esp32-history__row' + (alert ? ' is-alert' : '') + '"><span class="mono">' + p.label +
                  '</span><strong>' + p.value + ' ' + unit + (alert ? ' ⚠' : '') + '</strong></div>';
              }).join('');
            }
          }
        })
        .catch(function () {
          loaded.delete(target);
          // Un fallo del refresco en vivo no rompe lo ya dibujado: se
          // reintenta solo en el próximo ciclo.
          if (isPoll) return;
          setSkeleton(grid, false);
          setError(grid, true);
          var histEl = grid.querySelector('[data-sensor-history]');
          if (histEl) histEl.innerHTML = '<p class="text-muted">No se pudieron cargar las lecturas.</p>';
        })
        .finally(function () { if (card) card.classList.remove('is-loading'); });
    }

    /* ── Reintentar tras un error de red ── */
    document.querySelectorAll('[data-sensor-retry]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var pane = btn.closest('[data-section-pane]');
        if (pane) loadSensor(pane.dataset.sectionPane, true);
      });
    });

    /* ── "Live" de verdad: refresco periódico del sensor visible ── */
    var POLL_MS = 15000;
    setInterval(function () {
      if (document.hidden) return;
      var active = document.querySelector('.section-pane.is-active');
      if (!active) return;
      var grid = active.querySelector('.iot-grid');
      // Con un rango histórico aplicado no hay nada "vivo" que refrescar.
      if (!grid || grid._range) return;
      loadSensor(active.dataset.sectionPane, true, true);
    }, POLL_MS);

    /* ── Date range selector → recarga la serie del sensor ── */
    document.querySelectorAll('.iot-grid').forEach(function (grid) {
      initTooltip(grid);

      var drp = grid.querySelector('[data-date-range]');
      if (!drp) return;
      var paneOf = function () {
        var pane = grid.closest('[data-section-pane]');
        return pane ? pane.dataset.sectionPane : null;
      };
      drp.addEventListener('drp:apply', function (e) {
        grid._range = { from: e.detail.from, to: e.detail.to };
        var t = paneOf(); if (t) loadSensor(t, true);
      });
      drp.addEventListener('drp:clear', function () {
        grid._range = null;
        var t = paneOf(); if (t) loadSensor(t, true);
      });
    });

    /* ── Pestaña inicial: la guardada (si existe) o la activa por defecto ── */
    var savedTab = null;
    try { savedTab = sessionStorage.getItem(TAB_KEY); } catch (err) { /* sin storage */ }
    var savedInput = savedTab
      ? document.querySelector('[data-section-target="' + savedTab + '"]')
      : null;

    if (savedInput && !savedInput.checked) {
      // El change burbujea hasta .section-switch, así la píldora del
      // switch (prevcs-ui.js) también se recoloca sola.
      savedInput.checked = true;
      savedInput.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
      var first = document.querySelector('.section-pane.is-active');
      if (first) loadSensor(first.dataset.sectionPane);
    }
  });
})();
