/* =====================================================================
   PrevCS · EventManager — calendario de eventos en JS plano
   Port del componente React "event-manager" (shadcn) al stack del
   proyecto: sin React, sin Tailwind, estilos en css/event-manager.css.

   Uso:
     var cal = new EventManager(elemento, {
       events: [...],              // { id, title, description, startTime,
                                   //   endTime, color, category, tags }
       categories: [...], availableTags: [...],
       defaultView: 'month',       // month | week | day | list
       readOnly: false,
       onEventCreate: function (data)      { return Promise<evento> },
       onEventUpdate: function (id, data)  { return Promise<evento> },
       onEventDelete: function (id)        { return Promise }
     });

   Los callbacks pueden devolver una promesa. Crear/editar/borrar esperan la
   respuesta (si falla, el diálogo queda abierto con el error); arrastrar un
   evento se aplica al toque y se revierte si el guardado falla.
   ===================================================================== */
(function (window, document) {
  'use strict';

  var DEFAULT_COLORS = [
    { name: 'Azul',    value: 'blue',   hex: '#3B82F6' },
    { name: 'Verde',   value: 'green',  hex: '#16A34A' },
    { name: 'Violeta', value: 'purple', hex: '#8B5CF6' },
    { name: 'Naranja', value: 'orange', hex: '#EA7317' },
    { name: 'Rosa',    value: 'pink',   hex: '#DB2777' },
    { name: 'Rojo',    value: 'red',    hex: '#DC3A2F' }
  ];

  var VIEWS = [
    { value: 'month', label: 'Mes',    icon: 'calendar' },
    { value: 'week',  label: 'Semana', icon: 'grid' },
    { value: 'day',   label: 'Día',    icon: 'clock' },
    { value: 'list',  label: 'Lista',  icon: 'list' }
  ];

  /* Hora a la que arrancan las vistas de semana y día (se puede subir). */
  var SCROLL_HOUR = 7;

  var ICONS = {
    chevronLeft: '<path d="m15 18-6-6 6-6"/>',
    chevronRight: '<path d="m9 18 6-6-6-6"/>',
    plus: '<path d="M5 12h14M12 5v14"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    grid: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    filter: '<path d="M22 3H2l8 9.46V19l4 2v-8.54z"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    check: '<path d="M20 6 9 17l-5-5"/>'
  };

  /* ── Helpers de DOM ──────────────────────────────────────────────── */
  function h(tag, attrs, children) {
    var el = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (key) {
        var value = attrs[key];
        if (value === null || value === undefined || value === false) return;
        if (key === 'class') el.className = value;
        else if (key === 'text') el.textContent = value;
        else if (key.indexOf('on') === 0) el.addEventListener(key.slice(2), value);
        else el.setAttribute(key, value === true ? '' : value);
      });
    }
    append(el, children);
    return el;
  }

  function append(el, children) {
    if (children === null || children === undefined || children === false) return;
    if (!Array.isArray(children)) children = [children];
    children.forEach(function (child) {
      if (child === null || child === undefined || child === false) return;
      if (Array.isArray(child)) { append(el, child); return; }
      el.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
    });
  }

  function icon(name) {
    var span = document.createElement('span');
    span.className = 'em-icon';
    span.setAttribute('aria-hidden', 'true');
    span.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + ICONS[name] + '</svg>';
    return span;
  }

  function swatch(hex, cls) {
    var dot = h('span', { class: cls || 'em-swatch', 'aria-hidden': 'true' });
    dot.style.setProperty('--em-c', hex);
    return dot;
  }

  /* ── Helpers de fecha ────────────────────────────────────────────── */
  function startOfDay(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
  function addDays(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function sameDay(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
  }
  function startOfWeek(d, weekStartsOn) {
    var day = startOfDay(d);
    return addDays(day, -((day.getDay() - weekStartsOn + 7) % 7));
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function dayKey(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function toInputValue(d) {
    return d ? dayKey(d) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()) : '';
  }
  /* "YYYY-MM-DDTHH:MM" → Date en hora local (sin corrimiento de zona). */
  function parseDate(value) {
    if (value instanceof Date) return new Date(value.getTime());
    var m = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/.exec(String(value || ''));
    if (m) return new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]);
    var d = new Date(value);
    return isNaN(d.getTime()) ? null : d;
  }
  function capitalize(text) { return text ? text.charAt(0).toUpperCase() + text.slice(1) : text; }

  function normalize(ev) {
    return {
      id: String(ev.id),
      title: ev.title || '',
      description: ev.description || '',
      startTime: parseDate(ev.startTime),
      endTime: parseDate(ev.endTime),
      color: ev.color,
      category: ev.category || '',
      tags: Array.isArray(ev.tags) ? ev.tags.slice() : []
    };
  }

  function clone(ev) {
    var copy = normalize(ev);
    copy.startTime = new Date(ev.startTime.getTime());
    copy.endTime = new Date(ev.endTime.getTime());
    return copy;
  }

  /* Lo que viaja a los callbacks: fechas como "YYYY-MM-DDTHH:MM". */
  function serialize(ev) {
    return {
      id: ev.id,
      title: ev.title,
      description: ev.description,
      startTime: toInputValue(ev.startTime),
      endTime: toInputValue(ev.endTime),
      color: ev.color,
      category: ev.category,
      tags: ev.tags.slice()
    };
  }

  function resolved(value) {
    return value && typeof value.then === 'function' ? value : Promise.resolve(value);
  }

  /* =================================================================
     Componente
     ================================================================= */
  function EventManager(root, options) {
    if (!(this instanceof EventManager)) return new EventManager(root, options);
    var o = options || {};

    this.root = typeof root === 'string' ? document.querySelector(root) : root;
    if (!this.root) throw new Error('EventManager: no se encontró el contenedor.');

    this.opts = {
      categories: o.categories || ['Reunión', 'Tarea', 'Recordatorio', 'Personal'],
      colors: o.colors || DEFAULT_COLORS,
      availableTags: o.availableTags || ['Importante', 'Urgente', 'Trabajo', 'Personal', 'Equipo', 'Cliente'],
      locale: o.locale || 'es-AR',
      weekStartsOn: o.weekStartsOn === undefined ? 1 : o.weekStartsOn,
      readOnly: !!o.readOnly,
      onEventCreate: o.onEventCreate,
      onEventUpdate: o.onEventUpdate,
      onEventDelete: o.onEventDelete
    };

    this.state = {
      events: (o.events || []).map(normalize).filter(function (ev) { return ev.startTime && ev.endTime; }),
      currentDate: startOfDay(new Date()),
      view: o.defaultView || 'month',
      search: '',
      filters: { colors: [], tags: [], categories: [] }
    };

    this.editing = null;   // evento abierto en el diálogo (null = creando)
    this.dragId = null;
    this.busy = false;
    this.filterUi = {};
    this.lastScrollKey = null;

    this.build();
    this.render();
  }

  /* ── API pública ─────────────────────────────────────────────────── */
  EventManager.prototype.getEvents = function () { return this.state.events.map(serialize); };
  EventManager.prototype.setEvents = function (events) {
    this.state.events = (events || []).map(normalize);
    this.renderBody();
  };
  EventManager.prototype.setView = function (view) {
    this.state.view = view;
    this.render();
  };

  EventManager.prototype.color = function (value) {
    var colors = this.opts.colors;
    for (var i = 0; i < colors.length; i++) if (colors[i].value === value) return colors[i];
    return colors[0];
  };

  EventManager.prototype.fmtTime = function (d) {
    return d.toLocaleTimeString(this.opts.locale, { hour: '2-digit', minute: '2-digit' });
  };

  /* =================================================================
     Estructura fija (se arma una vez)
     ================================================================= */
  EventManager.prototype.build = function () {
    var self = this;
    var o = this.opts;

    this.root.classList.add('em');
    this.root.textContent = '';

    /* Cabecera: título + navegación | vistas + nuevo evento */
    this.titleEl = h('h2', { class: 'em-title', 'aria-live': 'polite' });
    this.prevBtn = h('button', { type: 'button', class: 'em-btn em-btn--icon', 'aria-label': 'Anterior', onclick: function () { self.navigate(-1); } }, icon('chevronLeft'));
    this.todayBtn = h('button', { type: 'button', class: 'em-btn', text: 'Hoy', onclick: function () { self.state.currentDate = startOfDay(new Date()); self.render(); } });
    this.nextBtn = h('button', { type: 'button', class: 'em-btn em-btn--icon', 'aria-label': 'Siguiente', onclick: function () { self.navigate(1); } }, icon('chevronRight'));

    this.viewSelect = h('select', { class: 'em-view-select', 'aria-label': 'Vista del calendario' },
      VIEWS.map(function (v) { return h('option', { value: v.value, text: 'Vista: ' + v.label }); }));
    this.viewSelect.addEventListener('change', function () { self.setView(self.viewSelect.value); });

    this.viewButtons = VIEWS.map(function (v) {
      return h('button', {
        type: 'button', class: 'em-seg__btn', 'data-view': v.value, 'aria-pressed': 'false',
        onclick: function () { self.setView(v.value); }
      }, [icon(v.icon), h('span', { text: v.label })]);
    });

    var newBtn = o.readOnly ? null : h('button', {
      type: 'button', class: 'em-btn em-btn--primary em-new',
      onclick: function () { self.openDialog(null); }
    }, [icon('plus'), h('span', { text: 'Nuevo evento' })]);

    var header = h('div', { class: 'em-header' }, [
      h('div', { class: 'em-header__main' }, [
        this.titleEl,
        h('div', { class: 'em-nav' }, [this.prevBtn, this.todayBtn, this.nextBtn])
      ]),
      h('div', { class: 'em-header__actions' }, [
        this.viewSelect,
        h('div', { class: 'em-seg', role: 'group', 'aria-label': 'Vista del calendario' }, this.viewButtons),
        newBtn
      ])
    ]);

    /* Búsqueda */
    this.searchInput = h('input', { type: 'search', class: 'em-search__input', placeholder: 'Buscar eventos...', 'aria-label': 'Buscar eventos' });
    this.searchClear = h('button', { type: 'button', class: 'em-search__clear', 'aria-label': 'Limpiar búsqueda', hidden: true }, icon('x'));
    this.searchInput.addEventListener('input', function () {
      self.state.search = self.searchInput.value;
      self.searchClear.hidden = !self.state.search;
      self.renderBody();
    });
    this.searchClear.addEventListener('click', function () {
      self.searchInput.value = '';
      self.state.search = '';
      self.searchClear.hidden = true;
      self.searchInput.focus();
      self.renderBody();
    });
    var search = h('div', { class: 'em-search' }, [icon('search'), this.searchInput, this.searchClear]);

    /* Filtros */
    this.clearFiltersBtn = h('button', {
      type: 'button', class: 'em-btn em-btn--ghost', hidden: true,
      onclick: function () { self.clearFilters(); }
    }, [icon('x'), h('span', { text: 'Limpiar' })]);

    var filters = h('div', { class: 'em-filters' }, [
      this.buildFilter('colors', 'Colores', 'Filtrar por color', o.colors.map(function (c) {
        return { value: c.value, label: c.name, hex: c.hex };
      })),
      this.buildFilter('tags', 'Etiquetas', 'Filtrar por etiqueta', o.availableTags.map(function (t) {
        return { value: t, label: t };
      })),
      this.buildFilter('categories', 'Categorías', 'Filtrar por categoría', o.categories.map(function (c) {
        return { value: c, label: c };
      })),
      this.clearFiltersBtn
    ]);

    this.chipsEl = h('div', { class: 'em-chips', hidden: true });
    this.bodyEl = h('div', { class: 'em-body' });

    append(this.root, [header, search, filters, this.chipsEl, this.bodyEl, this.buildDialog()]);

    /* Cerrar menús de filtro al hacer clic afuera o con Escape */
    document.addEventListener('click', function (e) {
      if (!self.root.contains(e.target) || !e.target.closest('.em-filter')) self.closeMenus();
    });
    this.root.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && self.root.querySelector('.em-filter.is-open')) {
        var open = self.root.querySelector('.em-filter.is-open .em-filter__trigger');
        self.closeMenus();
        if (open) open.focus();
      }
    });
  };

  EventManager.prototype.buildFilter = function (key, label, heading, items) {
    var self = this;
    var badge = h('span', { class: 'em-count', hidden: true });
    var trigger = h('button', {
      type: 'button', class: 'em-btn em-filter__trigger', 'aria-haspopup': 'true', 'aria-expanded': 'false'
    }, [icon('filter'), h('span', { text: label }), badge]);

    var inputs = [];
    var menu = h('div', { class: 'em-menu', role: 'group', 'aria-label': heading, hidden: true }, [
      h('p', { class: 'em-menu__label', text: heading })
    ]);

    items.forEach(function (item) {
      var input = h('input', { type: 'checkbox', value: item.value, class: 'em-menu__input' });
      input.addEventListener('change', function () { self.toggleFilter(key, item.value, input.checked); });
      inputs.push(input);
      menu.appendChild(h('label', { class: 'em-menu__item' }, [
        input,
        h('span', { class: 'em-menu__check' }, icon('check')),
        item.hex ? swatch(item.hex) : null,
        h('span', { text: item.label })
      ]));
    });

    var wrap = h('div', { class: 'em-filter' }, [trigger, menu]);
    trigger.addEventListener('click', function () {
      var open = !wrap.classList.contains('is-open');
      self.closeMenus();
      if (open) {
        wrap.classList.add('is-open');
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
      }
    });

    this.filterUi[key] = { wrap: wrap, trigger: trigger, menu: menu, badge: badge, inputs: inputs, items: items };
    return wrap;
  };

  EventManager.prototype.closeMenus = function () {
    Object.keys(this.filterUi).forEach(function (key) {
      var ui = this.filterUi[key];
      ui.wrap.classList.remove('is-open');
      ui.menu.hidden = true;
      ui.trigger.setAttribute('aria-expanded', 'false');
    }, this);
  };

  EventManager.prototype.toggleFilter = function (key, value, on) {
    var list = this.state.filters[key].filter(function (v) { return v !== value; });
    if (on) list.push(value);
    this.state.filters[key] = list;
    this.syncFilters();
    this.renderBody();
  };

  EventManager.prototype.clearFilters = function () {
    this.state.filters = { colors: [], tags: [], categories: [] };
    this.state.search = '';
    this.searchInput.value = '';
    this.searchClear.hidden = true;
    this.syncFilters();
    this.renderBody();
  };

  /* =================================================================
     Render
     ================================================================= */
  EventManager.prototype.render = function () {
    var s = this.state;
    var locale = this.opts.locale;

    var title;
    if (s.view === 'month') {
      title = capitalize(s.currentDate.toLocaleDateString(locale, { month: 'long', year: 'numeric' }));
    } else if (s.view === 'week') {
      var start = startOfWeek(s.currentDate, this.opts.weekStartsOn);
      title = 'Semana del ' + start.toLocaleDateString(locale, { day: 'numeric', month: 'short' });
    } else if (s.view === 'day') {
      title = capitalize(s.currentDate.toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
    } else {
      title = 'Todos los eventos';
    }
    this.titleEl.textContent = title;

    var isList = s.view === 'list';
    this.prevBtn.disabled = isList;
    this.nextBtn.disabled = isList;
    this.todayBtn.disabled = isList;

    this.viewSelect.value = s.view;
    this.viewButtons.forEach(function (btn) {
      var active = btn.getAttribute('data-view') === s.view;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    this.syncFilters();
    this.renderBody();
  };

  EventManager.prototype.syncFilters = function () {
    var self = this;
    var f = this.state.filters;
    var active = f.colors.length + f.tags.length + f.categories.length;

    Object.keys(this.filterUi).forEach(function (key) {
      var ui = self.filterUi[key];
      var selected = f[key];
      ui.inputs.forEach(function (input) { input.checked = selected.indexOf(input.value) !== -1; });
      ui.badge.hidden = selected.length === 0;
      ui.badge.textContent = String(selected.length);
      ui.trigger.classList.toggle('has-value', selected.length > 0);
    });

    this.clearFiltersBtn.hidden = active === 0;

    /* Chips de filtros activos */
    this.chipsEl.textContent = '';
    this.chipsEl.hidden = active === 0;
    if (!active) return;

    this.chipsEl.appendChild(h('span', { class: 'em-chips__label', text: 'Filtros activos:' }));
    ['colors', 'tags', 'categories'].forEach(function (key) {
      f[key].forEach(function (value) {
        var item = self.filterUi[key].items.filter(function (it) { return it.value === value; })[0];
        if (!item) return;
        self.chipsEl.appendChild(h('span', { class: 'em-badge em-badge--secondary' }, [
          item.hex ? swatch(item.hex, 'em-swatch em-swatch--round') : null,
          h('span', { text: item.label }),
          h('button', {
            type: 'button', class: 'em-badge__remove', 'aria-label': 'Quitar filtro ' + item.label,
            onclick: function () { self.toggleFilter(key, value, false); }
          }, icon('x'))
        ]));
      });
    });
  };

  EventManager.prototype.filteredEvents = function () {
    var s = this.state;
    var query = s.search.trim().toLowerCase();
    var f = s.filters;

    return s.events.filter(function (ev) {
      if (query) {
        var haystack = [ev.title, ev.description, ev.category].concat(ev.tags).join(' ').toLowerCase();
        if (haystack.indexOf(query) === -1) return false;
      }
      if (f.colors.length && f.colors.indexOf(ev.color) === -1) return false;
      if (f.tags.length && !ev.tags.some(function (t) { return f.tags.indexOf(t) !== -1; })) return false;
      if (f.categories.length && f.categories.indexOf(ev.category) === -1) return false;
      return true;
    });
  };

  EventManager.prototype.renderBody = function () {
    var s = this.state;
    var previous = this.bodyEl.querySelector('.em-scroll');
    var previousTop = previous ? previous.scrollTop : 0;

    var events = this.filteredEvents().sort(function (a, b) { return a.startTime - b.startTime; });
    var content;
    if (s.view === 'month') content = this.renderMonth(events);
    else if (s.view === 'week') content = this.renderWeek(events);
    else if (s.view === 'day') content = this.renderDay(events);
    else content = this.renderList(events);

    this.bodyEl.textContent = '';
    this.bodyEl.appendChild(content);

    /* En semana/día: al cambiar de vista o de fecha se baja a SCROLL_HOUR;
       si solo cambió un filtro se conserva el scroll. */
    var scroller = this.bodyEl.querySelector('.em-scroll');
    if (scroller) {
      var key = s.view + ':' + dayKey(s.currentDate);
      if (key === this.lastScrollKey) {
        scroller.scrollTop = previousTop;
      } else {
        var row = scroller.querySelector('[data-hour="' + SCROLL_HOUR + '"]');
        var head = scroller.querySelector('.em-time__head');
        scroller.scrollTop = row ? row.offsetTop - (head ? head.offsetHeight : 0) : 0;
      }
      this.lastScrollKey = key;
    } else {
      this.lastScrollKey = null;
    }
  };

  EventManager.prototype.navigate = function (direction) {
    var s = this.state;
    var d = s.currentDate;
    if (s.view === 'month') {
      /* Arrancar del día 1 evita que el 31 salte dos meses. */
      var target = new Date(d.getFullYear(), d.getMonth() + direction, 1);
      var lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
      target.setDate(Math.min(d.getDate(), lastDay));
      s.currentDate = target;
    } else if (s.view === 'week') {
      s.currentDate = addDays(d, 7 * direction);
    } else if (s.view === 'day') {
      s.currentDate = addDays(d, direction);
    }
    this.render();
  };

  /* ── Tarjetas de evento ──────────────────────────────────────────── */
  EventManager.prototype.makeInteractive = function (el, ev) {
    var self = this;
    el.setAttribute('role', 'button');
    el.setAttribute('tabindex', '0');
    el.setAttribute('data-id', ev.id);
    el.addEventListener('click', function (e) { e.stopPropagation(); self.openDialog(ev.id); });
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); self.openDialog(ev.id); }
    });

    if (this.opts.readOnly) return;
    el.setAttribute('draggable', 'true');
    el.addEventListener('dragstart', function (e) {
      self.dragId = ev.id;
      self.root.classList.add('is-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', ev.id); } catch (err) { /* IE */ }
      }
    });
    el.addEventListener('dragend', function () {
      self.dragId = null;
      self.root.classList.remove('is-dragging');
      self.root.querySelectorAll('.is-drop-target').forEach(function (n) { n.classList.remove('is-drop-target'); });
    });
  };

  EventManager.prototype.makeDropTarget = function (el, date, hour) {
    var self = this;
    if (this.opts.readOnly) return;
    el.addEventListener('dragover', function (e) {
      if (!self.dragId) return;
      e.preventDefault();
      el.classList.add('is-drop-target');
    });
    el.addEventListener('dragleave', function (e) {
      if (!el.contains(e.relatedTarget)) el.classList.remove('is-drop-target');
    });
    el.addEventListener('drop', function (e) {
      e.preventDefault();
      el.classList.remove('is-drop-target');
      if (self.dragId) self.moveEvent(self.dragId, date, hour);
    });
  };

  EventManager.prototype.durationLabel = function (ev) {
    var minutes = Math.round((ev.endTime - ev.startTime) / 60000);
    var hours = Math.floor(minutes / 60);
    var rest = minutes % 60;
    return hours > 0 ? hours + ' h' + (rest ? ' ' + rest + ' min' : '') : rest + ' min';
  };

  EventManager.prototype.badges = function (ev) {
    var out = [];
    if (ev.category) out.push(h('span', { class: 'em-badge em-badge--secondary', text: ev.category }));
    ev.tags.forEach(function (tag) { out.push(h('span', { class: 'em-badge em-badge--outline', text: tag })); });
    return out.length ? h('div', { class: 'em-badges' }, out) : null;
  };

  /* Popover con el detalle; se muestra con :hover / :focus-within (CSS). */
  EventManager.prototype.popover = function (ev, alignEnd) {
    var color = this.color(ev.color);
    return h('div', { class: 'em-pop' + (alignEnd ? ' em-pop--end' : ''), 'aria-hidden': 'true' }, [
      h('div', { class: 'em-pop__head' }, [
        h('p', { class: 'em-pop__title', text: ev.title }),
        swatch(color.hex, 'em-swatch em-swatch--round')
      ]),
      ev.description ? h('p', { class: 'em-pop__desc', text: ev.description }) : null,
      h('p', { class: 'em-pop__time' }, [
        icon('clock'),
        h('span', { text: this.fmtTime(ev.startTime) + ' – ' + this.fmtTime(ev.endTime) }),
        h('span', { class: 'em-muted', text: '(' + this.durationLabel(ev) + ')' })
      ]),
      this.badges(ev)
    ]);
  };

  EventManager.prototype.chip = function (ev, alignEnd) {
    var chip = h('div', { class: 'em-event' }, h('span', { class: 'em-event__title', text: ev.title }));
    chip.style.setProperty('--em-c', this.color(ev.color).hex);
    chip.setAttribute('aria-label', ev.title + ', ' + this.fmtTime(ev.startTime));
    this.makeInteractive(chip, ev);
    return h('div', { class: 'em-event-wrap' }, [chip, this.popover(ev, alignEnd)]);
  };

  /* ── Vista mes ───────────────────────────────────────────────────── */
  EventManager.prototype.renderMonth = function (events) {
    var self = this;
    var cur = this.state.currentDate;
    var locale = this.opts.locale;
    var start = startOfWeek(new Date(cur.getFullYear(), cur.getMonth(), 1), this.opts.weekStartsOn);
    var today = new Date();

    var byDay = {};
    events.forEach(function (ev) {
      var key = dayKey(ev.startTime);
      (byDay[key] = byDay[key] || []).push(ev);
    });

    var head = h('div', { class: 'em-month__head' });
    for (var i = 0; i < 7; i++) {
      var wd = addDays(start, i);
      head.appendChild(h('div', { class: 'em-month__weekday' }, [
        h('span', { class: 'em-wide', text: capitalize(wd.toLocaleDateString(locale, { weekday: 'short' }).replace('.', '')) }),
        h('span', { class: 'em-narrow', text: wd.toLocaleDateString(locale, { weekday: 'narrow' }).toUpperCase() })
      ]));
    }

    var grid = h('div', { class: 'em-month__grid' });
    for (var n = 0; n < 42; n++) {
      (function (day, index) {
        var dayEvents = byDay[dayKey(day)] || [];
        var alignEnd = index % 7 >= 4;
        var cell = h('div', {
          class: 'em-month__cell' +
            (day.getMonth() !== cur.getMonth() ? ' is-outside' : '') +
            (sameDay(day, today) ? ' is-today' : '')
        }, [
          h('span', { class: 'em-month__num', text: String(day.getDate()) }),
          h('div', { class: 'em-month__events' }, [
            dayEvents.slice(0, 3).map(function (ev) { return self.chip(ev, alignEnd); }),
            dayEvents.length > 3 ? h('button', {
              type: 'button', class: 'em-more', text: '+' + (dayEvents.length - 3) + ' más',
              onclick: function () { self.state.currentDate = startOfDay(day); self.setView('day'); }
            }) : null
          ])
        ]);
        self.makeDropTarget(cell, day, null);
        grid.appendChild(cell);
      })(addDays(start, n), n);
    }

    return h('div', { class: 'em-card em-month' }, [head, grid]);
  };

  /* ── Vistas con horas (semana y día) ─────────────────────────────── */
  EventManager.prototype.renderTimeGrid = function (days, events, detailed) {
    var self = this;
    var locale = this.opts.locale;
    var today = new Date();

    var bySlot = {};
    events.forEach(function (ev) {
      var key = dayKey(ev.startTime) + '@' + ev.startTime.getHours();
      (bySlot[key] = bySlot[key] || []).push(ev);
    });

    var grid = h('div', { class: 'em-time' + (detailed ? ' em-time--day' : ' em-time--week') });
    grid.style.setProperty('--em-cols', String(days.length));

    if (!detailed) {
      grid.appendChild(h('div', { class: 'em-time__head em-time__corner', text: 'Hora' }));
      days.forEach(function (day) {
        grid.appendChild(h('div', { class: 'em-time__head' + (sameDay(day, today) ? ' is-today' : '') }, [
          h('span', { class: 'em-wide', text: capitalize(day.toLocaleDateString(locale, { weekday: 'short' }).replace('.', '')) }),
          h('span', { class: 'em-narrow', text: day.toLocaleDateString(locale, { weekday: 'narrow' }).toUpperCase() }),
          h('span', { class: 'em-time__date', text: day.toLocaleDateString(locale, { day: 'numeric', month: 'short' }).replace('.', '') })
        ]));
      });
    }

    for (var hour = 0; hour < 24; hour++) {
      grid.appendChild(h('div', { class: 'em-time__label', 'data-hour': String(hour), text: pad(hour) + ':00' }));
      days.forEach(function (day, index) {
        var slotEvents = bySlot[dayKey(day) + '@' + hour] || [];
        var slot = h('div', { class: 'em-time__slot' }, slotEvents.map(function (ev) {
          return detailed ? self.detailedCard(ev) : self.chip(ev, index >= days.length - 3);
        }));
        self.makeDropTarget(slot, day, hour);
        grid.appendChild(slot);
      });
    }

    return h('div', { class: 'em-card em-scroll' }, grid);
  };

  EventManager.prototype.renderWeek = function (events) {
    var start = startOfWeek(this.state.currentDate, this.opts.weekStartsOn);
    var days = [];
    for (var i = 0; i < 7; i++) days.push(addDays(start, i));
    return this.renderTimeGrid(days, events, false);
  };

  EventManager.prototype.renderDay = function (events) {
    return this.renderTimeGrid([this.state.currentDate], events, true);
  };

  EventManager.prototype.detailedCard = function (ev) {
    var card = h('div', { class: 'em-detail' }, [
      h('p', { class: 'em-detail__title', text: ev.title }),
      ev.description ? h('p', { class: 'em-detail__desc', text: ev.description }) : null,
      h('p', { class: 'em-detail__time' }, [icon('clock'), h('span', { text: this.fmtTime(ev.startTime) + ' – ' + this.fmtTime(ev.endTime) })]),
      this.badges(ev)
    ]);
    card.style.setProperty('--em-c', this.color(ev.color).hex);
    this.makeInteractive(card, ev);
    return card;
  };

  /* ── Vista lista ─────────────────────────────────────────────────── */
  EventManager.prototype.renderList = function (events) {
    var self = this;
    var locale = this.opts.locale;
    var wrap = h('div', { class: 'em-card em-list' });

    if (!events.length) {
      wrap.appendChild(h('p', { class: 'em-empty', text: 'No se encontraron eventos.' }));
      return wrap;
    }

    var groups = [];
    var current = null;
    events.forEach(function (ev) {
      var key = dayKey(ev.startTime);
      if (!current || current.key !== key) {
        current = { key: key, date: ev.startTime, items: [] };
        groups.push(current);
      }
      current.items.push(ev);
    });

    groups.forEach(function (group) {
      wrap.appendChild(h('section', { class: 'em-list__group' }, [
        h('h3', { class: 'em-list__date', text: capitalize(group.date.toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })) }),
        group.items.map(function (ev) {
          var item = h('div', { class: 'em-list__item' }, [
            swatch(self.color(ev.color).hex, 'em-swatch em-swatch--round em-list__dot'),
            h('div', { class: 'em-list__main' }, [
              h('div', { class: 'em-list__top' }, [
                h('div', { class: 'em-list__text' }, [
                  h('p', { class: 'em-list__title', text: ev.title }),
                  ev.description ? h('p', { class: 'em-list__desc', text: ev.description }) : null
                ]),
                ev.category ? h('span', { class: 'em-badge em-badge--secondary', text: ev.category }) : null
              ]),
              h('div', { class: 'em-list__meta' }, [
                h('span', { class: 'em-list__time' }, [icon('clock'), h('span', { text: self.fmtTime(ev.startTime) + ' – ' + self.fmtTime(ev.endTime) })]),
                ev.tags.length ? h('span', { class: 'em-badges' }, ev.tags.map(function (tag) {
                  return h('span', { class: 'em-badge em-badge--outline', text: tag });
                })) : null
              ])
            ])
          ]);
          self.makeInteractive(item, ev);
          item.removeAttribute('draggable');
          return item;
        })
      ]));
    });

    return wrap;
  };

  /* =================================================================
     Mover (drag & drop)
     ================================================================= */
  EventManager.prototype.moveEvent = function (id, date, hour) {
    var self = this;
    var events = this.state.events;
    var index = -1;
    for (var i = 0; i < events.length; i++) if (events[i].id === id) index = i;
    if (index === -1) return;

    var original = events[index];
    var duration = original.endTime - original.startTime;
    var start = new Date(date.getFullYear(), date.getMonth(), date.getDate(),
      hour === null ? original.startTime.getHours() : hour,
      hour === null ? original.startTime.getMinutes() : 0);
    if (start.getTime() === original.startTime.getTime()) return;

    var moved = clone(original);
    moved.startTime = start;
    moved.endTime = new Date(start.getTime() + duration);
    events[index] = moved;
    this.renderBody();

    if (typeof this.opts.onEventUpdate !== 'function') return;
    resolved(this.opts.onEventUpdate(id, serialize(moved))).then(function (saved) {
      if (saved && saved.id) self.replaceEvent(normalize(saved));
    }).catch(function () {
      self.replaceEvent(original);
    });
  };

  EventManager.prototype.replaceEvent = function (ev) {
    var events = this.state.events;
    for (var i = 0; i < events.length; i++) {
      if (events[i].id === ev.id) { events[i] = ev; break; }
    }
    this.renderBody();
  };

  /* =================================================================
     Diálogo crear / editar
     ================================================================= */
  EventManager.prototype.buildDialog = function () {
    var self = this;
    var o = this.opts;
    var uid = 'em' + Math.random().toString(36).slice(2, 8);

    function field(label, id, control, extraClass) {
      return h('div', { class: 'em-field' + (extraClass ? ' ' + extraClass : '') }, [
        h('label', { class: 'em-label', for: id, text: label }),
        control
      ]);
    }

    this.f = {
      title: h('input', { id: uid + '-title', type: 'text', maxlength: '200', placeholder: 'Título del evento', required: true }),
      description: h('textarea', { id: uid + '-desc', rows: '3', maxlength: '2000', placeholder: 'Descripción del evento' }),
      start: h('input', { id: uid + '-start', type: 'datetime-local', required: true }),
      end: h('input', { id: uid + '-end', type: 'datetime-local', required: true }),
      category: h('select', { id: uid + '-cat' }, [h('option', { value: '', text: 'Sin categoría' })].concat(
        o.categories.map(function (c) { return h('option', { value: c, text: c }); })))
    };

    /* Si el inicio pasa al fin, el fin se corre manteniendo la duración. */
    var lastStart = null;
    this.f.start.addEventListener('focus', function () { lastStart = parseDate(self.f.start.value); });
    this.f.start.addEventListener('change', function () {
      var start = parseDate(self.f.start.value);
      var end = parseDate(self.f.end.value);
      if (!start) return;
      var duration = lastStart && end && end > lastStart ? end - lastStart : 3600000;
      if (!end || end <= start || lastStart) self.f.end.value = toInputValue(new Date(start.getTime() + duration));
      lastStart = start;
    });

    this.colorInputs = o.colors.map(function (c) {
      return h('input', { type: 'radio', name: uid + '-color', value: c.value, class: 'em-color__input', 'aria-label': c.name });
    });
    var colors = h('div', { class: 'em-colors', role: 'radiogroup', 'aria-labelledby': uid + '-color-label' },
      o.colors.map(function (c, i) {
        var dot = h('label', { class: 'em-color', title: c.name }, [self.colorInputs[i], h('span', { class: 'em-color__dot' })]);
        dot.style.setProperty('--em-c', c.hex);
        return dot;
      }));

    this.tagButtons = o.availableTags.map(function (tag) {
      return h('button', {
        type: 'button', class: 'em-tag', 'aria-pressed': 'false', text: tag,
        onclick: function (e) {
          var btn = e.currentTarget;
          btn.setAttribute('aria-pressed', btn.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
        }
      });
    });

    this.dialogTitle = h('h2', { class: 'em-dialog__title', id: uid + '-dlg-title' });
    this.dialogDesc = h('p', { class: 'em-dialog__desc', id: uid + '-dlg-desc' });
    this.errorEl = h('p', { class: 'em-error', role: 'alert', hidden: true });
    this.deleteBtn = h('button', { type: 'button', class: 'em-btn em-btn--danger em-dialog__delete', onclick: function () { self.handleDelete(); } });
    this.cancelBtn = h('button', { type: 'button', class: 'em-btn', text: 'Cancelar', onclick: function () { self.closeDialog(); } });
    this.saveBtn = h('button', { type: 'submit', class: 'em-btn em-btn--primary' });

    this.form = h('form', { class: 'em-dialog__form', novalidate: true }, [
      h('header', { class: 'em-dialog__head' }, [
        h('div', null, [this.dialogTitle, this.dialogDesc]),
        h('button', { type: 'button', class: 'em-btn em-btn--icon em-btn--ghost', 'aria-label': 'Cerrar', onclick: function () { self.closeDialog(); } }, icon('x'))
      ]),
      h('div', { class: 'em-dialog__body' }, [
        field('Título', this.f.title.id, this.f.title),
        field('Descripción', this.f.description.id, this.f.description),
        h('div', { class: 'em-row' }, [
          field('Inicio', this.f.start.id, this.f.start),
          field('Fin', this.f.end.id, this.f.end)
        ]),
        h('div', { class: 'em-row' }, [
          field('Categoría', this.f.category.id, this.f.category),
          h('div', { class: 'em-field' }, [h('span', { class: 'em-label', id: uid + '-color-label', text: 'Color' }), colors])
        ]),
        h('div', { class: 'em-field' }, [
          h('span', { class: 'em-label', text: 'Etiquetas' }),
          h('div', { class: 'em-tags', role: 'group', 'aria-label': 'Etiquetas' }, this.tagButtons)
        ]),
        this.errorEl
      ]),
      h('footer', { class: 'em-dialog__foot' }, [this.deleteBtn, this.cancelBtn, this.saveBtn])
    ]);

    this.form.addEventListener('submit', function (e) { e.preventDefault(); self.handleSave(); });

    this.dialog = h('dialog', { class: 'em-dialog', 'aria-labelledby': uid + '-dlg-title', 'aria-describedby': uid + '-dlg-desc' }, this.form);
    /* Clic en el fondo cierra (el form ocupa toda la caja del dialog). */
    this.dialog.addEventListener('click', function (e) { if (e.target === self.dialog) self.closeDialog(); });
    this.dialog.addEventListener('cancel', function (e) { if (self.busy) e.preventDefault(); });

    return this.dialog;
  };

  EventManager.prototype.openDialog = function (id) {
    var o = this.opts;
    var ev = null;
    if (id !== null) {
      ev = this.state.events.filter(function (e) { return e.id === id; })[0] || null;
      if (!ev) return;
    }
    this.editing = ev;
    var readOnly = o.readOnly;

    if (ev) {
      this.dialogTitle.textContent = readOnly ? 'Detalle del evento' : 'Editar evento';
      this.dialogDesc.textContent = readOnly ? 'Solo lectura: no tenés permiso de edición en este espacio.' : 'Mirá y modificá los datos del evento.';
    } else {
      this.dialogTitle.textContent = 'Nuevo evento';
      this.dialogDesc.textContent = 'Agregá un evento al calendario del espacio.';
    }

    /* Crear: arranca en la próxima hora en punto del día que se está viendo. */
    var start, end;
    if (ev) {
      start = ev.startTime; end = ev.endTime;
    } else {
      var now = new Date();
      var base = this.state.view === 'list' ? now : this.state.currentDate;
      start = new Date(base.getFullYear(), base.getMonth(), base.getDate(), Math.min(now.getHours() + 1, 23), 0);
      end = new Date(start.getTime() + 3600000);
    }

    this.f.title.value = ev ? ev.title : '';
    this.f.description.value = ev ? ev.description : '';
    this.f.start.value = toInputValue(start);
    this.f.end.value = toInputValue(end);
    this.f.category.value = ev ? ev.category : (o.categories[0] || '');
    var colorValue = ev ? this.color(ev.color).value : o.colors[0].value;
    this.colorInputs.forEach(function (input) { input.checked = input.value === colorValue; });
    this.tagButtons.forEach(function (btn) {
      btn.setAttribute('aria-pressed', ev && ev.tags.indexOf(btn.textContent) !== -1 ? 'true' : 'false');
    });

    var controls = [this.f.title, this.f.description, this.f.start, this.f.end, this.f.category]
      .concat(this.colorInputs, this.tagButtons);
    controls.forEach(function (c) { c.disabled = readOnly; });

    this.deleteBtn.hidden = readOnly || !ev;
    this.deleteBtn.textContent = 'Eliminar';
    this.deleteBtn.removeAttribute('data-armed');
    this.saveBtn.hidden = readOnly;
    this.saveBtn.textContent = ev ? 'Guardar' : 'Crear';
    this.cancelBtn.textContent = readOnly ? 'Cerrar' : 'Cancelar';
    this.showError('');
    this.setBusy(false);

    this.closeMenus();
    if (typeof this.dialog.showModal === 'function') this.dialog.showModal();
    else this.dialog.setAttribute('open', '');
    if (!readOnly) this.f.title.focus();
  };

  EventManager.prototype.closeDialog = function () {
    if (this.busy) return;
    if (typeof this.dialog.close === 'function') this.dialog.close();
    else this.dialog.removeAttribute('open');
    this.editing = null;
  };

  EventManager.prototype.showError = function (message) {
    this.errorEl.textContent = message;
    this.errorEl.hidden = !message;
  };

  EventManager.prototype.setBusy = function (busy) {
    this.busy = busy;
    this.saveBtn.disabled = busy;
    this.deleteBtn.disabled = busy;
    this.cancelBtn.disabled = busy;
    this.form.classList.toggle('is-busy', busy);
  };

  EventManager.prototype.readForm = function () {
    var checked = this.colorInputs.filter(function (input) { return input.checked; })[0];
    return {
      id: this.editing ? this.editing.id : '',
      title: this.f.title.value.trim(),
      description: this.f.description.value.trim(),
      startTime: parseDate(this.f.start.value),
      endTime: parseDate(this.f.end.value),
      color: checked ? checked.value : this.opts.colors[0].value,
      category: this.f.category.value,
      tags: this.tagButtons.filter(function (btn) { return btn.getAttribute('aria-pressed') === 'true'; })
        .map(function (btn) { return btn.textContent; })
    };
  };

  EventManager.prototype.handleSave = function () {
    var self = this;
    if (this.busy || this.opts.readOnly) return;

    var data = this.readForm();
    if (!data.title) { this.showError('El evento necesita un título.'); this.f.title.focus(); return; }
    if (!data.startTime || !data.endTime) { this.showError('Elegí la fecha y hora de inicio y de fin.'); return; }
    if (data.endTime <= data.startTime) { this.showError('El fin tiene que ser posterior al inicio.'); this.f.end.focus(); return; }

    var editing = this.editing;
    var callback = editing ? this.opts.onEventUpdate : this.opts.onEventCreate;
    var payload = serialize(data);

    this.showError('');
    this.setBusy(true);

    var request = typeof callback === 'function'
      ? (editing ? callback(editing.id, payload) : callback(payload))
      : null;

    resolved(request).then(function (saved) {
      var ev = saved && saved.id ? normalize(saved) : data;
      if (!ev.id) ev.id = 'ev-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

      if (editing) {
        self.state.events = self.state.events.map(function (e) { return e.id === editing.id ? ev : e; });
      } else {
        self.state.events.push(ev);
      }
      self.setBusy(false);
      self.closeDialog();
      self.renderBody();
    }).catch(function (error) {
      self.setBusy(false);
      self.showError((error && error.message) || 'No se pudo guardar el evento.');
    });
  };

  /* Borrar pide un segundo clic: el primero arma el botón. */
  EventManager.prototype.handleDelete = function () {
    var self = this;
    var editing = this.editing;
    if (this.busy || !editing || this.opts.readOnly) return;

    if (!this.deleteBtn.hasAttribute('data-armed')) {
      this.deleteBtn.setAttribute('data-armed', '');
      this.deleteBtn.textContent = '¿Eliminar? Confirmar';
      return;
    }

    this.showError('');
    this.setBusy(true);
    var request = typeof this.opts.onEventDelete === 'function' ? this.opts.onEventDelete(editing.id) : null;

    resolved(request).then(function () {
      self.state.events = self.state.events.filter(function (e) { return e.id !== editing.id; });
      self.setBusy(false);
      self.closeDialog();
      self.renderBody();
    }).catch(function (error) {
      self.setBusy(false);
      self.deleteBtn.removeAttribute('data-armed');
      self.deleteBtn.textContent = 'Eliminar';
      self.showError((error && error.message) || 'No se pudo eliminar el evento.');
    });
  };

  EventManager.DEFAULT_COLORS = DEFAULT_COLORS;
  window.EventManager = EventManager;
})(window, document);
