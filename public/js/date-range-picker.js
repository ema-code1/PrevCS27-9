/* =====================================================================
   PrevCS · Date Range Selector (vanilla, MVC-friendly)
   Adaptación del componente React de 21st.dev a JS puro, sin build step.
   - Selección de rango (start → end) con preview en hover.
   - Inputs Start / End editables + botón Apply + zona horaria.
   - Transiciones suaves; colores tomados de las design tokens (styles.css).

   Uso:
     <div class="drp" data-date-range></div>
   El componente se auto-inicializa y emite eventos en el elemento raíz:
     'drp:apply' → detail { start: Date, end: Date, from: 'Y-m-d H:i:s', to: '…' }
     'drp:clear' → (sin detail)
   ===================================================================== */
(function () {
  'use strict';

  var MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  var MONTHS_ABBR = ['ene', 'feb', 'mar', 'abr', 'may', 'jun',
    'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  // Semana arranca en lunes (igual que el original): L M X J V S D
  var WEEKDAYS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
  var DOW = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];

  /* ── date helpers (sin dependencias) ──────────────────────────── */
  function startOfDay(d) { var x = new Date(d); x.setHours(0, 0, 0, 0); return x; }
  function endOfDay(d) { var x = new Date(d); x.setHours(23, 59, 59, 999); return x; }
  function startOfMonth(d) { return new Date(d.getFullYear(), d.getMonth(), 1); }
  function addMonths(d, n) { return new Date(d.getFullYear(), d.getMonth() + n, 1); }
  function addDays(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function isSameDay(a, b) {
    return a && b && a.getFullYear() === b.getFullYear() &&
      a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
  }
  function isSameMonth(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
  }
  function isToday(d) { return isSameDay(d, new Date()); }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  // lunes = 0 … domingo = 6
  function weekdayMon(d) { return (d.getDay() + 6) % 7; }

  function fmtInput(d) {
    if (!d) return '';
    return pad(d.getDate()) + ' ' + MONTHS_ABBR[d.getMonth()] + ' ' + d.getFullYear();
  }
  function fmtTime(d) { return d ? pad(d.getHours()) + ':' + pad(d.getMinutes()) : ''; }
  function fmtSQL(d) {
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
      ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
  }
  // "09 jun 2026" → Date  (también acepta "9 jun 2026" y "2026-06-09")
  function parseInput(str) {
    if (!str) return null;
    str = str.trim().toLowerCase();
    var iso = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (iso) {
      var di = new Date(+iso[1], +iso[2] - 1, +iso[3]);
      return isNaN(di) ? null : di;
    }
    var m = str.match(/^(\d{1,2})\s+([a-záéíóú]+)\.?\s+(\d{4})$/);
    if (!m) return null;
    var idx = MONTHS_ABBR.indexOf(m[2].slice(0, 3));
    if (idx < 0) return null;
    var d = new Date(+m[3], idx, +m[1]);
    return isNaN(d) ? null : d;
  }

  function svgIcon(paths, attrs) {
    var extra = attrs || '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
      'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" ' + extra + '>' +
      paths + '</svg>';
  }
  var ICON_CAL = svgIcon('<rect x="3" y="4.5" width="18" height="16" rx="2.5"/>' +
    '<line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2.5" x2="8" y2="6.5"/>' +
    '<line x1="16" y1="2.5" x2="16" y2="6.5"/>', 'class="drp-trigger__cal"');
  var ICON_CLOSE = svgIcon('<line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/>');
  var ICON_PREV = svgIcon('<path d="m14 6-6 6 6 6"/>');
  var ICON_NEXT = svgIcon('<path d="m10 6 6 6-6 6"/>');
  var ICON_CHEV = svgIcon('<path d="m6 9 6 6 6-6"/>', 'class="drp-trigger__chev"');

  /* ── componente ───────────────────────────────────────────────── */
  function DateRangePicker(root) {
    this.root = root;
    this.start = null;
    this.end = null;
    this.hover = null;
    this.selecting = false;
    this.open = false;
    this.view = startOfMonth(new Date());
    this.tz = (Intl.DateTimeFormat().resolvedOptions().timeZone) || 'Local';
    this._build();
    this._bind();
    this.renderTrigger();
    this.renderCalendar();
  }

  DateRangePicker.prototype._build = function () {
    var r = this.root;
    r.classList.add('drp');
    r.innerHTML =
      '<button type="button" class="drp-trigger" aria-haspopup="dialog" aria-expanded="false">' +
        ICON_CAL +
        '<span class="drp-trigger__label" data-label></span>' +
        '<span class="drp-trigger__clear" data-clear role="button" tabindex="0" aria-label="Limpiar rango" hidden>' + ICON_CLOSE + '</span>' +
        ICON_CHEV +
      '</button>' +
      '<div class="drp-pop" data-pop hidden role="dialog" aria-label="Seleccionar rango de fechas">' +
        '<div class="drp-pop__inner">' +
          '<div class="drp-cal">' +
            '<div class="drp-cal__head">' +
              '<h4 class="drp-cal__title" data-title></h4>' +
              '<div class="drp-cal__nav">' +
                '<button type="button" class="drp-cal__arrow" data-prev aria-label="Mes anterior">' + ICON_PREV + '</button>' +
                '<button type="button" class="drp-cal__arrow" data-next aria-label="Mes siguiente">' + ICON_NEXT + '</button>' +
              '</div>' +
            '</div>' +
            '<div class="drp-cal__weekdays">' +
              WEEKDAYS.map(function (w) { return '<span>' + w + '</span>'; }).join('') +
            '</div>' +
            '<div class="drp-cal__grid" data-grid></div>' +
          '</div>' +
          '<div class="drp-side">' +
            '<div class="drp-side__fields">' +
              '<label class="drp-field"><span>Desde</span>' +
                '<input type="text" data-start inputmode="text" autocomplete="off" placeholder="09 jun 2026"></label>' +
              '<label class="drp-field"><span>Hasta</span>' +
                '<input type="text" data-end inputmode="text" autocomplete="off" placeholder="09 jun 2026"></label>' +
            '</div>' +
            '<div class="drp-side__foot">' +
              '<button type="button" class="drp-apply" data-apply>Aplicar <span class="drp-apply__kbd">↵</span></button>' +
              '<span class="drp-tz">Local (' + this.tz + ')</span>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';

    this.$trigger = r.querySelector('.drp-trigger');
    this.$label = r.querySelector('[data-label]');
    this.$clear = r.querySelector('[data-clear]');
    this.$pop = r.querySelector('[data-pop]');
    this.$title = r.querySelector('[data-title]');
    this.$grid = r.querySelector('[data-grid]');
    this.$prev = r.querySelector('[data-prev]');
    this.$next = r.querySelector('[data-next]');
    this.$startInput = r.querySelector('[data-start]');
    this.$endInput = r.querySelector('[data-end]');
    this.$apply = r.querySelector('[data-apply]');
  };

  DateRangePicker.prototype._bind = function () {
    var self = this;

    this.$trigger.addEventListener('click', function (e) {
      // No togglear si se clickeó la "x" de limpiar
      if (e.target.closest('[data-clear]')) return;
      self.toggle();
    });
    this.$clear.addEventListener('click', function (e) {
      e.stopPropagation();
      self.clear();
    });
    this.$clear.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); e.stopPropagation(); self.clear(); }
    });
    this.$prev.addEventListener('click', function () { self.shiftMonth(-1); });
    this.$next.addEventListener('click', function () { self.shiftMonth(1); });
    this.$apply.addEventListener('click', function () { self.apply(); });

    this.$startInput.addEventListener('change', function () { self.onInput('start'); });
    this.$endInput.addEventListener('change', function () { self.onInput('end'); });
    [this.$startInput, this.$endInput].forEach(function (inp) {
      inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') self.apply(); });
    });

    // delegación de clicks/hover en días
    this.$grid.addEventListener('click', function (e) {
      var btn = e.target.closest('.drp-day');
      if (btn && btn.dataset.ts) self.onDayClick(new Date(+btn.dataset.ts));
    });
    this.$grid.addEventListener('mouseover', function (e) {
      var btn = e.target.closest('.drp-day');
      if (btn && btn.dataset.ts) self.onDayHover(new Date(+btn.dataset.ts));
    });

    // cerrar al clickear fuera / Escape
    this._onDocClick = function (e) {
      if (self.open && !self.root.contains(e.target)) self.close();
    };
    this._onKey = function (e) { if (e.key === 'Escape' && self.open) self.close(); };
    document.addEventListener('mousedown', this._onDocClick);
    document.addEventListener('keydown', this._onKey);
  };

  /* ── apertura / cierre ────────────────────────────────────────── */
  DateRangePicker.prototype.toggle = function () { this.open ? this.close() : this.openPop(); };
  DateRangePicker.prototype.openPop = function () {
    this.open = true;
    if (this.start) this.view = startOfMonth(this.start);
    this.$pop.hidden = false;
    // fuerza reflow para que la animación de entrada corra
    void this.$pop.offsetWidth;
    this.$pop.classList.add('is-open');
    this.$trigger.setAttribute('aria-expanded', 'true');
    this.syncInputs();
    this.renderCalendar();
  };
  DateRangePicker.prototype.close = function () {
    if (!this.open) return;
    this.open = false;
    this.$trigger.setAttribute('aria-expanded', 'false');
    this.$pop.classList.remove('is-open');
    this.$pop.classList.add('is-closing');
    var pop = this.$pop;
    window.setTimeout(function () {
      pop.hidden = true;
      pop.classList.remove('is-closing');
    }, 160);
  };

  DateRangePicker.prototype.shiftMonth = function (n) {
    this.view = addMonths(this.view, n);
    var cal = this.root.querySelector('.drp-cal');
    cal.classList.remove('is-fwd', 'is-bwd');
    void cal.offsetWidth;
    cal.classList.add(n > 0 ? 'is-fwd' : 'is-bwd');
    this.renderCalendar();
  };

  /* ── selección ───────────────────────────────────────────────── */
  DateRangePicker.prototype.onDayClick = function (day) {
    if (!this.start || (this.start && this.end)) {
      this.start = startOfDay(day);
      this.end = null;
      this.hover = day;
      this.selecting = true;
    } else if (this.selecting) {
      if (day >= this.start) {
        this.end = endOfDay(day);
      } else {
        this.end = endOfDay(this.start);
        this.start = startOfDay(day);
      }
      this.selecting = false;
      this.hover = null;
    }
    this.syncInputs();
    this.renderTrigger();
    this.renderCalendar();
  };

  DateRangePicker.prototype.onDayHover = function (day) {
    if (this.start && !this.end) {
      this.hover = day;
      this.paintRange();
    }
  };

  DateRangePicker.prototype.onInput = function (which) {
    var inp = which === 'start' ? this.$startInput : this.$endInput;
    var parsed = parseInput(inp.value);
    if (!parsed) { inp.classList.add('is-error'); return; }
    inp.classList.remove('is-error');
    if (which === 'start') {
      this.start = startOfDay(parsed);
      if (this.end && this.end < this.start) this.end = endOfDay(parsed);
    } else {
      this.end = endOfDay(parsed);
      if (this.start && this.end < this.start) this.start = startOfDay(parsed);
    }
    this.selecting = false;
    this.view = startOfMonth(parsed);
    this.renderTrigger();
    this.renderCalendar();
  };

  DateRangePicker.prototype.syncInputs = function () {
    this.$startInput.value = fmtInput(this.start);
    this.$endInput.value = fmtInput(this.end);
    this.$startInput.classList.remove('is-error');
    this.$endInput.classList.remove('is-error');
  };

  /* ── render: trigger ─────────────────────────────────────────── */
  DateRangePicker.prototype.renderTrigger = function () {
    var label, hasRange = this.start && this.end;
    if (hasRange) {
      if (isSameDay(this.start, this.end)) {
        label = DOW[this.start.getDay()] + ', ' + this.start.getDate() + ' ' + MONTHS_ABBR[this.start.getMonth()];
      } else {
        var s = this.start.getDate() + ' ' + MONTHS_ABBR[this.start.getMonth()];
        var e = this.end.getDate() + ' ' + MONTHS_ABBR[this.end.getMonth()];
        var t = fmtTime(this.end);
        label = s + ' – ' + e + (t && t !== '00:00' ? ', ' + t : '');
      }
    } else if (this.start) {
      label = 'Desde ' + fmtInput(this.start);
    } else {
      label = 'Seleccionar rango';
    }
    this.$label.textContent = label;
    this.$label.classList.toggle('is-placeholder', !hasRange && !this.start);
    this.$clear.hidden = !this.start;
    this.root.classList.toggle('is-set', !!hasRange);
  };

  /* ── render: calendario ──────────────────────────────────────── */
  DateRangePicker.prototype.renderCalendar = function () {
    this.$title.textContent = MONTHS[this.view.getMonth()].charAt(0).toUpperCase() +
      MONTHS[this.view.getMonth()].slice(1) + ' ' + this.view.getFullYear();

    var first = startOfMonth(this.view);
    var day = addDays(first, -weekdayMon(first));
    var last = new Date(this.view.getFullYear(), this.view.getMonth() + 1, 0);
    var end = addDays(last, 6 - weekdayMon(last));

    var html = '';
    for (var d = new Date(day); d <= end; d = addDays(d, 1)) {
      var muted = !isSameMonth(d, this.view);
      html += '<button type="button" class="drp-day' + (muted ? ' is-muted' : '') +
        (isToday(d) ? ' is-today' : '') + '" data-ts="' + d.getTime() + '">' +
        '<span class="drp-day__n">' + d.getDate() + '</span></button>';
    }
    this.$grid.innerHTML = html;
    this.paintRange();
  };

  // Pinta start/end/in-range/preview sin re-crear los nodos (transición fluida).
  DateRangePicker.prototype.paintRange = function () {
    var self = this;
    var rangeEnd = this.end || (this.selecting ? this.hover : null);
    var lo = this.start, hi = rangeEnd;
    if (lo && hi && hi < lo) { var tmp = lo; lo = hi; hi = tmp; }

    this.$grid.querySelectorAll('.drp-day').forEach(function (btn) {
      var d = new Date(+btn.dataset.ts);
      var isStart = self.start && isSameDay(d, self.start);
      var isEnd = rangeEnd && isSameDay(d, rangeEnd);
      var inRange = lo && hi && d > startOfDay(lo) && d < startOfDay(hi);
      btn.classList.toggle('is-start', !!isStart);
      btn.classList.toggle('is-end', !!isEnd);
      btn.classList.toggle('is-range', !!inRange);
      btn.classList.toggle('is-edge', !!(isStart || isEnd));
    });
  };

  /* ── apply / clear ───────────────────────────────────────────── */
  DateRangePicker.prototype.apply = function () {
    // permite "día único": si hay start sin end, se asume el mismo día
    if (this.start && !this.end) this.end = endOfDay(this.start);
    if (!this.start || !this.end) { this.close(); return; }

    this.renderTrigger();
    this.root.dispatchEvent(new CustomEvent('drp:apply', {
      bubbles: true,
      detail: {
        start: this.start,
        end: this.end,
        from: fmtSQL(this.start),
        to: fmtSQL(this.end)
      }
    }));
    this.close();
  };

  DateRangePicker.prototype.clear = function () {
    this.start = this.end = this.hover = null;
    this.selecting = false;
    this.syncInputs();
    this.renderTrigger();
    this.renderCalendar();
    this.root.dispatchEvent(new CustomEvent('drp:clear', { bubbles: true }));
  };

  DateRangePicker.prototype.destroy = function () {
    document.removeEventListener('mousedown', this._onDocClick);
    document.removeEventListener('keydown', this._onKey);
  };

  /* ── auto-init ───────────────────────────────────────────────── */
  function init(root) {
    if (root.__drp) return root.__drp;
    root.__drp = new DateRangePicker(root);
    return root.__drp;
  }
  function initAll(scope) {
    (scope || document).querySelectorAll('[data-date-range]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(); });
  } else {
    initAll();
  }

  window.DateRangePicker = DateRangePicker;
  window.initDateRangePickers = initAll;
})();
