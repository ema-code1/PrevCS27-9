/* =====================================================================
   PrevCS · IoT Sensor Charts
   Lógica compartida por registros-iot/espacio.php y esp32/detalle.php.
   - Cambio de sensor (section-switch).
   - Carga de la serie del sensor desde la API (con rango de fechas opcional).
   - Dibujo del gráfico de línea con ejes: grilla vertical + marcas/labels
     en el eje Y (Valores) y el eje X (Tiempo).
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

  // Formatea un valor del eje Y de forma compacta (sin ceros sobrantes).
  function fmtVal(v) {
    var a = Math.abs(v), s;
    if (a >= 1000) s = (v / 1000).toFixed(a >= 10000 ? 0 : 1) + 'k';
    else if (a >= 100) s = v.toFixed(0);
    else if (a >= 1) s = v.toFixed(1);
    else s = v.toFixed(2);
    return s.replace(/\.0+($|k)/, '$1').replace(/(\.\d*?)0+($|k)/, '$1$2');
  }

  function clear(node) { while (node && node.firstChild) node.removeChild(node.firstChild); }

  function drawLine(grid, points) {
    var line = grid.querySelector('[data-sensor-line]');
    var dots = grid.querySelector('[data-sensor-points]');
    var empty = grid.querySelector('[data-sensor-empty]');
    var vGrid = grid.querySelector('[data-grid-v]');
    var yAxis = grid.querySelector('[data-axis-y]');
    var xAxis = grid.querySelector('[data-axis-x]');

    clear(dots); clear(vGrid); clear(yAxis); clear(xAxis);

    if (!points.length) {
      line.setAttribute('points', '');
      if (empty) empty.hidden = false;
      return;
    }
    if (empty) empty.hidden = true;

    var values = points.map(function (p) { return p.value; });
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
    for (var l = 0; l <= LEVELS; l++) {
      var yy = Y0 + (Y1 - Y0) * (l / LEVELS);
      var val = max - (max - min) * (l / LEVELS);
      yAxis.appendChild(el('line', { x1: X0 - 6, y1: yy, x2: X0, y2: yy, 'class': 'iot-line-chart__tick' }));
      var ty = el('text', { x: X0 - 11, y: yy + 3.4, 'text-anchor': 'end' });
      ty.textContent = fmtVal(val);
      yAxis.appendChild(ty);
    }

    /* ── Eje X: líneas verticales + marcas de tiempo ── */
    var ticks = Math.min(X_TICKS, n);
    var seen = {};
    for (var t = 0; t < ticks; t++) {
      var idx = ticks === 1 ? 0 : Math.round(t * (n - 1) / (ticks - 1));
      if (seen[idx]) continue;
      seen[idx] = true;
      var cx = coords[idx][0];
      vGrid.appendChild(el('line', { x1: cx, y1: Y0, x2: cx, y2: Y1 }));
      xAxis.appendChild(el('line', { x1: cx, y1: Y1, x2: cx, y2: Y1 + 5, 'class': 'iot-line-chart__tick' }));
      var tx = el('text', { x: cx, y: Y1 + 18, 'text-anchor': 'middle' });
      tx.textContent = points[idx].label || '';
      xAxis.appendChild(tx);
    }

    /* ── Serie + puntos ── */
    line.setAttribute('points', coords.map(function (c) {
      return c[0].toFixed(1) + ',' + c[1].toFixed(1);
    }).join(' '));
    coords.forEach(function (c) {
      var circle = el('circle', { cx: c[0].toFixed(1), cy: c[1].toFixed(1), r: 3, 'class': 'iot-line-chart__point' });
      dots.appendChild(circle);
    });
  }

  // API pública mínima (re-render programático / pruebas).
  window.IotCharts = { drawLine: drawLine };

  document.addEventListener('DOMContentLoaded', function () {
    /* ── Section switch ── */
    var switchers = document.querySelectorAll('[data-section-target]');
    var panes = document.querySelectorAll('[data-section-pane]');

    function activatePane(target) {
      panes.forEach(function (p) { p.classList.toggle('is-active', p.dataset.sectionPane === target); });
      switchers.forEach(function (input) {
        var isActive = input.dataset.sectionTarget === target;
        input.checked = isActive;
        var lbl = input.closest('.section-switch__radio');
        if (lbl) lbl.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      loadSensor(target);
    }
    switchers.forEach(function (input) {
      input.addEventListener('change', function () { activatePane(input.dataset.sectionTarget); });
    });

    /* ── Carga de la serie de un sensor ── */
    var loaded = new Set();

    function loadSensor(target, force) {
      var grid = document.querySelector('[data-section-pane="' + target + '"] .iot-grid');
      if (!grid) return;
      if (loaded.has(target) && !force) return;
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
      if (card) card.classList.add('is-loading');

      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function (data) {
          var points = data.points || [];
          drawLine(grid, points);

          var latestEl = grid.querySelector('[data-sensor-latest]');
          var obsEl = grid.querySelector('[data-sensor-observation]');
          var histEl = grid.querySelector('[data-sensor-history]');
          var rangeNote = (range && range.from) ? ' (rango filtrado)' : '';

          if (data.latest !== null && data.latest !== undefined) {
            if (latestEl) latestEl.textContent = data.latest + ' ' + unit;
            var obs = 'Última medición: ' + data.latest + ' ' + unit + '. ' +
              points.length + ' lecturas' + rangeNote + '.';
            if (code === 'rpm') {
              obs += ' Motor: ' + (Number(data.latest) > 0 ? 'ANDANDO ▶' : 'DETENIDO ⏸');
            }
            if (obsEl) obsEl.textContent = obs;
          } else if (obsEl) {
            obsEl.textContent = range && range.from
              ? 'Sin lecturas en el rango seleccionado.'
              : 'Sin lecturas todavía para este sensor.';
          }

          if (histEl) {
            if (!points.length) {
              histEl.innerHTML = '<p class="text-muted">Sin historial.</p>';
            } else {
              histEl.innerHTML = points.slice(-12).reverse().map(function (p) {
                return '<div class="esp32-history__row"><span class="mono">' + p.label +
                  '</span><strong>' + p.value + ' ' + unit + '</strong></div>';
              }).join('');
            }
          }
        })
        .catch(function () {
          loaded.delete(target);
          var histEl = grid.querySelector('[data-sensor-history]');
          if (histEl) histEl.innerHTML = '<p class="text-muted">No se pudieron cargar las lecturas.</p>';
        })
        .finally(function () { if (card) card.classList.remove('is-loading'); });
    }

    /* ── Date range selector → recarga la serie del sensor ── */
    document.querySelectorAll('.iot-grid').forEach(function (grid) {
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

    var first = document.querySelector('.section-pane.is-active');
    if (first) loadSensor(first.dataset.sectionPane);
  });
})();
