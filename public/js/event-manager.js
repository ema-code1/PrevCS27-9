/* =====================================================================
   PrevCS · EventManager — calendario de eventos en JS plano
   Port del componente React "event-manager" (shadcn) al stack del
   proyecto: sin React, sin Tailwind, estilos en css/event-manager.css.

   Uso:
     var cal = new EventManager(elemento, {
       events: [...],              // { id, title, description, startTime,
                                   //   endTime, color, category, tags,
                                   //   assignees: [userId], notify }
       categories: [...],
       availableTags: [...],       // etiquetas fijas (no se pueden borrar)
       customTags: [{ id, name }], // etiquetas propias del espacio
       members: [{ id, name, initials, photo, role }],
       notifyOptions: [{ value, label }],
       defaultView: 'month',       // month | week | day | list
       readOnly: false,
       onEventCreate: function (data)      { return Promise<evento> },
       onEventUpdate: function (id, data)  { return Promise<evento> },
       onEventDelete: function (id)        { return Promise },
       onTagCreate:   function (name)      { return Promise<{tags}> },
       onTagDelete:   function (id)        { return Promise<{tags, events}> }
     });

   Los callbacks pueden devolver una promesa. Crear/editar/borrar esperan la
   respuesta (si falla, el diálogo queda abierto con el error); arrastrar un
   evento se aplica al toque y se revierte si el guardado falla.

   Los campos de fecha, categoría, encargados y notificaciones son controles
   propios (EmDateTime / EmSelect) y no controles nativos: el calendario del
   datetime-local y el menú del select los dibuja el navegador y no se pueden
   estilar.
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
    chevronDown: '<path d="m6 9 6 6 6-6"/>',
    plus: '<path d="M5 12h14M12 5v14"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
    bellOff: '<path d="M8.7 3A6 6 0 0 1 18 8c0 7 3 9 3 9H9"/><path d="M6 8a6 6 0 0 1 .4-2.2C5.5 6.8 5 7.9 5 9c0 7-2 8-2 8h3"/><path d="m2 2 20 20"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
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
      tags: Array.isArray(ev.tags) ? ev.tags.slice() : [],
      /* Encargados: ids de usuario. El nombre y el avatar los resuelve el
         componente con la lista de miembros del espacio. */
      assignees: Array.isArray(ev.assignees) ? ev.assignees.map(Number) : [],
      notify: ev.notify || 'none'
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
      tags: ev.tags.slice(),
      assignees: ev.assignees.slice(),
      notify: ev.notify
    };
  }

  function resolved(value) {
    return value && typeof value.then === 'function' ? value : Promise.resolve(value);
  }

  function avatar(person, extraClass) {
    var cls = 'em-avatar' + (extraClass ? ' ' + extraClass : '');
    if (person.photo) {
      return h('img', { class: cls + ' em-avatar--photo', src: person.photo, alt: '', loading: 'lazy' });
    }
    return h('span', { class: cls, 'aria-hidden': 'true', text: person.initials || '?' });
  }

  /* Pila de avatares: hasta `max` caras y un "+N" con el resto. */
  function avatarStack(people, max) {
    var limit = max || 3;
    var shown = people.slice(0, limit);
    var rest = people.length - shown.length;
    return h('span', { class: 'em-avatars' }, [
      shown.map(function (person) { return avatar(person); }),
      rest > 0 ? h('span', { class: 'em-avatar em-avatar--more', 'aria-hidden': 'true', text: '+' + rest }) : null
    ]);
  }

  /* =================================================================
     Popover flotante (selects y campos de fecha)

     Los menús de los campos del diálogo van con position: fixed y
     coordenadas calculadas a mano: el cuerpo del diálogo scrollea, así que un
     absolute quedaría recortado por el overflow. Hay uno abierto a la vez.
     ================================================================= */
  var activeFloat = null;

  /**
   * Coloca el popover pegado a su disparador, en coordenadas de viewport.
   *
   * position: fixed se ancla al viewport salvo que algún ancestro tenga
   * transform, filter o perspective: ahí ese ancestro pasa a ser el bloque
   * contenedor y top/left se miden desde su borde. En vez de adivinar cuál es
   * (al <dialog> le pasa mientras corre su animación de entrada), se mide:
   * con top/left en 0 el popover queda justo en el origen del bloque
   * contenedor, así que su rect en ese momento ES el corrimiento a restar.
   */
  function floatPlace(entry) {
    var pop = entry.pop;
    var rect = entry.trigger.getBoundingClientRect();
    var gap = 6;
    var margin = 10;

    if (entry.matchWidth) pop.style.minWidth = Math.round(rect.width) + 'px';

    pop.style.top = '0px';
    pop.style.left = '0px';
    var base = pop.getBoundingClientRect();
    var height = base.height;
    var width = base.width;

    var below = window.innerHeight - rect.bottom - margin;
    /* Si abajo no entra y arriba hay más aire, se despliega hacia arriba. */
    var up = height > below && (rect.top - margin) > below;

    var top = up ? rect.top - gap - height : rect.bottom + gap;
    top = Math.max(margin, Math.min(top, window.innerHeight - height - margin));
    var left = Math.max(margin, Math.min(rect.left, window.innerWidth - width - margin));

    pop.classList.toggle('is-up', up);
    pop.style.top = Math.round(top - base.top) + 'px';
    pop.style.left = Math.round(left - base.left) + 'px';
  }

  function floatOpen(entry) {
    floatClose();
    activeFloat = entry;
    /* Se mide ya desplegado pero invisible: sin alto no hay dónde ponerlo. */
    entry.pop.hidden = false;
    entry.pop.style.visibility = 'hidden';
    floatPlace(entry);
    entry.pop.style.visibility = '';
    entry.pop.classList.add('is-open');
    entry.trigger.setAttribute('aria-expanded', 'true');
    if (typeof entry.onOpen === 'function') entry.onOpen();

    /* Segunda pasada en el frame siguiente: si el diálogo todavía estaba
       animándose (su transform mueve el bloque contenedor), la primera medida
       queda vieja. También se reposiciona al terminar esa animación. */
    window.requestAnimationFrame(function () {
      if (activeFloat === entry) floatPlace(entry);
    });
  }

  function floatClose(focusTrigger) {
    var entry = activeFloat;
    if (!entry) return;
    activeFloat = null;
    entry.pop.hidden = true;
    entry.pop.classList.remove('is-open', 'is-up');
    entry.trigger.setAttribute('aria-expanded', 'false');
    if (typeof entry.onClose === 'function') entry.onClose();
    if (focusTrigger) entry.trigger.focus();
  }

  function floatIsOpen(pop) {
    return !!activeFloat && activeFloat.pop === pop;
  }

  function floatSync() {
    if (activeFloat) floatPlace(activeFloat);
  }

  window.addEventListener('resize', floatSync);
  window.addEventListener('scroll', floatSync, true);
  document.addEventListener('mousedown', function (e) {
    if (!activeFloat) return;
    if (activeFloat.pop.contains(e.target) || activeFloat.trigger.contains(e.target)) return;
    floatClose();
  });
  /* En captura y con preventDefault: si no, Escape cierra el <dialog> entero
     en vez de cerrar solo el menú que está abierto arriba de él. */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !activeFloat) return;
    e.preventDefault();
    e.stopPropagation();
    floatClose(true);
  }, true);

  /* =================================================================
     Select propio (una o varias opciones)

     Reemplaza al <select> nativo, cuyo menú no se puede estilar. Mismo
     material que los menús de filtro: rol listbox, teclado completo y
     opciones con avatar, color o renglón secundario.
     ================================================================= */
  function EmSelect(cfg) {
    var self = this;
    this.cfg = cfg || {};
    this.multiple = !!this.cfg.multiple;
    this.values = [];
    this.options = [];
    this.optionEls = [];
    this.disabled = false;

    this.valueEl = h('span', { class: 'em-sel__value' });
    this.trigger = h('button', {
      type: 'button',
      class: 'em-sel__trigger',
      id: this.cfg.id || null,
      'aria-haspopup': 'listbox',
      'aria-expanded': 'false',
      'aria-label': this.cfg.ariaLabel || null
    }, [
      this.cfg.icon ? icon(this.cfg.icon) : null,
      this.valueEl,
      h('span', { class: 'em-sel__caret' }, icon('chevronDown'))
    ]);

    this.listEl = h('div', {
      class: 'em-sel__list',
      role: 'listbox',
      'aria-multiselectable': this.multiple ? 'true' : null,
      'aria-label': this.cfg.ariaLabel || 'Opciones'
    });
    this.emptyEl = h('p', { class: 'em-sel__empty', hidden: true, text: this.cfg.emptyText || 'Sin opciones' });

    var parts = [];
    if (this.cfg.searchable) {
      this.searchInput = h('input', {
        type: 'search',
        class: 'em-sel__search-input',
        placeholder: this.cfg.searchPlaceholder || 'Buscar...',
        'aria-label': this.cfg.searchPlaceholder || 'Buscar'
      });
      this.searchInput.addEventListener('input', function () { self.applyFilter(); });
      this.searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); self.focusOption(0, 1); }
      });
      parts.push(h('div', { class: 'em-sel__search' }, [icon('search'), this.searchInput]));
    }
    parts.push(this.listEl, this.emptyEl);
    if (this.cfg.hint) parts.push(h('p', { class: 'em-sel__hint', text: this.cfg.hint }));

    this.menu = h('div', { class: 'em-sel__menu', hidden: true, 'data-lenis-prevent': '' }, parts);
    this.root = h('div', { class: 'em-sel' + (this.multiple ? ' em-sel--multi' : '') }, [this.trigger, this.menu]);

    this.float = {
      trigger: this.trigger,
      pop: this.menu,
      matchWidth: true,
      onOpen: function () {
        self.root.classList.add('is-open');
        if (self.searchInput) { self.searchInput.value = ''; self.applyFilter(); self.searchInput.focus(); }
        else self.focusOption(Math.max(0, self.firstSelectedIndex()), 1);
      },
      onClose: function () { self.root.classList.remove('is-open'); }
    };

    this.trigger.addEventListener('click', function () {
      if (self.disabled) return;
      if (floatIsOpen(self.menu)) floatClose(); else floatOpen(self.float);
    });
    this.trigger.addEventListener('keydown', function (e) {
      if (self.disabled) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (!floatIsOpen(self.menu)) floatOpen(self.float);
      }
    });

    this.setOptions(this.cfg.options || []);
  }

  EmSelect.prototype.setOptions = function (options) {
    var self = this;
    this.options = (options || []).slice();
    this.listEl.textContent = '';

    this.optionEls = this.options.map(function (opt, index) {
      var el = h('div', {
        class: 'em-sel__opt',
        role: 'option',
        tabindex: '-1',
        'aria-selected': 'false',
        'data-value': String(opt.value)
      }, [
        opt.initials || opt.photo ? avatar(opt) : null,
        opt.hex ? swatch(opt.hex, 'em-swatch em-swatch--round') : null,
        opt.icon ? icon(opt.icon) : null,
        h('span', { class: 'em-sel__opt-text' }, [
          h('span', { class: 'em-sel__opt-name', text: opt.label }),
          opt.hint ? h('span', { class: 'em-sel__opt-hint', text: opt.hint }) : null
        ]),
        h('span', { class: 'em-sel__check' }, icon('check'))
      ]);

      el.addEventListener('click', function () { self.pick(opt.value); });
      el.addEventListener('keydown', function (e) { self.onOptionKey(e, index); });
      return el;
    });

    append(this.listEl, this.optionEls);
    /* Los valores que ya no existen se caen solos. */
    this.setValue(this.multiple ? this.values : (this.values[0] === undefined ? '' : this.values[0]));
    this.applyFilter();
  };

  EmSelect.prototype.getValue = function () {
    return this.multiple ? this.values.slice() : (this.values.length ? this.values[0] : '');
  };

  EmSelect.prototype.setValue = function (value) {
    var known = this.options.map(function (opt) { return String(opt.value); });
    var wanted = (this.multiple ? (value || []) : [value === undefined || value === null ? '' : value])
      .map(function (v) { return String(v); })
      .filter(function (v) { return known.indexOf(v) !== -1; });

    this.values = this.multiple ? wanted : wanted.slice(0, 1);
    this.sync();
  };

  EmSelect.prototype.pick = function (value) {
    if (this.disabled) return;
    var key = String(value);

    if (this.multiple) {
      var index = this.values.indexOf(key);
      if (index === -1) this.values.push(key); else this.values.splice(index, 1);
    } else {
      this.values = [key];
    }

    this.sync();
    if (typeof this.cfg.onChange === 'function') this.cfg.onChange(this.getValue());
    /* En simple, elegir cierra; en múltiple se sigue tildando. */
    if (!this.multiple) floatClose(true);
  };

  EmSelect.prototype.selectedOptions = function () {
    var values = this.values;
    return this.options.filter(function (opt) { return values.indexOf(String(opt.value)) !== -1; });
  };

  EmSelect.prototype.firstSelectedIndex = function () {
    var values = this.values;
    for (var i = 0; i < this.options.length; i++) {
      if (values.indexOf(String(this.options[i].value)) !== -1) return i;
    }
    return 0;
  };

  EmSelect.prototype.sync = function () {
    var values = this.values;

    this.optionEls.forEach(function (el) {
      var on = values.indexOf(el.getAttribute('data-value')) !== -1;
      el.classList.toggle('is-on', on);
      el.setAttribute('aria-selected', on ? 'true' : 'false');
    });

    this.valueEl.textContent = '';
    var picked = this.selectedOptions();

    /* Sin nada elegido siempre va el placeholder, incluso con renderValue. */
    if (!picked.length) {
      this.valueEl.appendChild(h('span', {
        class: 'em-sel__placeholder',
        text: this.cfg.placeholder || 'Elegir'
      }));
    } else if (typeof this.cfg.renderValue === 'function') {
      append(this.valueEl, this.cfg.renderValue(picked));
    } else {
      append(this.valueEl, [
        picked[0].hex ? swatch(picked[0].hex, 'em-swatch em-swatch--round') : null,
        h('span', { class: 'em-sel__text', text: picked.map(function (o) { return o.label; }).join(', ') })
      ]);
    }

    this.trigger.classList.toggle('has-value', picked.length > 0);
    this.root.classList.toggle('is-empty', picked.length === 0);
  };

  /** Filtra por texto (solo si el select es buscable). */
  EmSelect.prototype.applyFilter = function () {
    var query = this.searchInput ? this.searchInput.value.trim().toLowerCase() : '';
    var visible = 0;

    this.optionEls.forEach(function (el, index) {
      var opt = this.options[index];
      var hay = (opt.label + ' ' + (opt.hint || '')).toLowerCase();
      var show = !query || hay.indexOf(query) !== -1;
      el.hidden = !show;
      if (show) visible++;
    }, this);

    this.emptyEl.hidden = visible > 0;
    this.emptyEl.textContent = query ? 'Sin resultados' : (this.cfg.emptyText || 'Sin opciones');
    if (floatIsOpen(this.menu)) floatSync();
  };

  EmSelect.prototype.focusOption = function (from, direction) {
    var els = this.optionEls;
    if (!els.length) return;

    for (var i = 0; i < els.length; i++) {
      var index = ((from + i * direction) % els.length + els.length) % els.length;
      if (!els[index].hidden) { els[index].focus(); return; }
    }
  };

  EmSelect.prototype.onOptionKey = function (e, index) {
    if (e.key === 'ArrowDown') { e.preventDefault(); this.focusOption(index + 1, 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); this.focusOption(index - 1, -1); }
    else if (e.key === 'Home') { e.preventDefault(); this.focusOption(0, 1); }
    else if (e.key === 'End') { e.preventDefault(); this.focusOption(this.optionEls.length - 1, -1); }
    else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.pick(this.options[index].value); }
    else if (e.key === 'Tab') { floatClose(); }
  };

  EmSelect.prototype.setDisabled = function (disabled) {
    this.disabled = !!disabled;
    this.trigger.disabled = !!disabled;
    this.root.classList.toggle('is-disabled', !!disabled);
    if (disabled && floatIsOpen(this.menu)) floatClose();
  };

  /* =================================================================
     Rueda scrolleable (hora y minutos)

     Es una lista real con scrollTop propio, no una tira reescrita a mano.
     Eso evita el salto visual: el wheel mueve píxeles continuos y, cuando el
     gesto termina, la fila más cercana se acomoda suave al centro.
     ================================================================= */

  /** Alto de fila de respaldo, para cuando el popover todavía está oculto. */
  var WHEEL_ITEM_FALLBACK = 32;

  /** Cuánto de la rueda gira una muesca del mouse. */
  var WHEEL_SPEED = 0.22;
  var WHEEL_EASE = 0.06;

  function EmWheel(cfg) {
    var self = this;
    this.cfg = cfg || {};
    this.values = [];
    this.valueEls = [];
    this.index = 0;
    this.disabled = false;
    this.snapTimer = null;
    this.frameId = 0;
    this.targetTop = 0;
    this.rawTargetTop = 0;
    this.pointerId = null;
    this.dragTotal = 0;
    this.dragged = false;

    this.strip = h('div', { class: 'em-wheel__strip' });
    this.root = h('div', {
      class: 'em-wheel',
      role: 'spinbutton',
      tabindex: '0',
      'data-lenis-prevent': '',
      'aria-label': this.cfg.label || 'Valor'
    }, this.strip);

    this.root.addEventListener('wheel', function (e) { self.onWheel(e); }, { passive: false, capture: true });
    this.root.addEventListener('scroll', function () { self.onScroll(); }, { passive: true });
    this.root.addEventListener('keydown', function (e) { self.onKey(e); });
    this.root.addEventListener('pointerdown', function (e) { self.onPointerDown(e); });
    this.strip.addEventListener('click', function (e) { self.onClick(e); });

    this.setValues(this.cfg.values || []);
  }

  /* Se mide en vivo: el alto de fila lo decide el CSS y cambia con el zoom. */
  EmWheel.prototype.itemHeight = function () {
    var first = this.valueEls[0];
    var height = first ? first.getBoundingClientRect().height : 0;
    return height > 0 ? height : WHEEL_ITEM_FALLBACK;
  };

  EmWheel.prototype.centerPad = function () {
    var pad = (this.root.clientHeight - this.itemHeight()) / 2;
    return Math.max(0, pad);
  };

  EmWheel.prototype.maxTop = function () {
    return Math.max(0, (this.values.length - 1) * this.itemHeight());
  };

  EmWheel.prototype.topForIndex = function (index) {
    return Math.max(0, Math.min(this.maxTop(), index * this.itemHeight()));
  };

  EmWheel.prototype.setValues = function (values) {
    var current = this.values.length ? this.values[this.index] : null;
    this.values = (values || []).slice();

    var at = this.values.indexOf(current);
    this.index = at === -1 ? 0 : at;
    this.rebuild();
    this.syncSelected();
    this.scrollToIndex(this.index, false);
  };

  EmWheel.prototype.getValue = function () {
    return this.values.length ? this.values[this.index] : null;
  };

  /**
   * false si el valor no está en la lista (el que llama decide qué hacer).
   *
   * Si ya es el elegido no toca nada salvo asegurar el centrado cuando la
   * rueda acaba de desplegarse y recién ahora tiene medidas reales.
   */
  EmWheel.prototype.setValue = function (value) {
    var at = this.values.indexOf(value);
    if (at === -1) return false;
    if (at === this.index) return true;

    this.index = at;
    this.syncSelected();
    this.scrollToIndex(at, false);

    return true;
  };

  EmWheel.prototype.refresh = function () {
    this.scrollToIndex(this.index, false);
  };

  EmWheel.prototype.rebuild = function () {
    var self = this;
    var format = this.cfg.format || String;

    this.strip.textContent = '';
    this.valueEls = [];
    this.strip.appendChild(h('div', { class: 'em-wheel__pad', 'aria-hidden': 'true' }));

    this.values.forEach(function (value, index) {
      var item = h('div', {
        class: 'em-wheel__item',
        role: 'option',
        'data-index': String(index),
        text: format(value)
      });
      self.valueEls.push(item);
      self.strip.appendChild(item);
    });

    this.strip.appendChild(h('div', { class: 'em-wheel__pad', 'aria-hidden': 'true' }));
    this.root.style.setProperty('--em-wheel-pad', this.centerPad() + 'px');
  };

  EmWheel.prototype.syncSelected = function () {
    var format = this.cfg.format || String;
    var value = this.getValue();

    this.root.setAttribute('aria-valuenow', value === null ? '' : String(value));
    this.root.setAttribute('aria-valuetext', value === null ? '' : format(value));

    this.valueEls.forEach(function (item, index) {
      var on = index === this.index;
      item.classList.toggle('is-on', on);
      item.setAttribute('aria-selected', on ? 'true' : 'false');
    }, this);
  };

  EmWheel.prototype.cancelFrame = function () {
    if (!this.frameId) return;
    window.cancelAnimationFrame(this.frameId);
    this.frameId = 0;
  };

  EmWheel.prototype.currentIndexFromScroll = function () {
    if (!this.values.length) return 0;
    return Math.max(0, Math.min(this.values.length - 1, Math.round(this.root.scrollTop / this.itemHeight())));
  };

  EmWheel.prototype.setIndexFromScroll = function (notify) {
    var next = this.currentIndexFromScroll();
    if (next === this.index) return;

    this.index = next;
    this.syncSelected();
    if (notify && typeof this.cfg.onChange === 'function') this.cfg.onChange(this.getValue());
  };

  EmWheel.prototype.scrollToIndex = function (index, animate, preserveRawTarget) {
    this.scrollToTop(this.topForIndex(index), animate, preserveRawTarget);
  };

  EmWheel.prototype.scrollToTop = function (top, animate, preserveRawTarget) {
    var self = this;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    this.cancelFrame();
    window.clearTimeout(this.snapTimer);
    this.root.style.setProperty('--em-wheel-pad', this.centerPad() + 'px');
    this.targetTop = Math.max(0, Math.min(this.maxTop(), top));
    if (!preserveRawTarget) this.rawTargetTop = this.targetTop;

    if (!animate || reduce) {
      this.root.scrollTop = this.targetTop;
      this.setIndexFromScroll(false);
      return;
    }

    this.frameId = window.requestAnimationFrame(function tick() {
      var distance = self.targetTop - self.root.scrollTop;

      if (Math.abs(distance) <= 0.4) {
        self.root.scrollTop = self.targetTop;
        self.frameId = 0;
        self.setIndexFromScroll(true);
        return;
      }

      self.root.scrollTop += distance * WHEEL_EASE;
      self.setIndexFromScroll(true);
      self.frameId = window.requestAnimationFrame(tick);
    });
  };

  EmWheel.prototype.onScroll = function () {
    this.root.style.setProperty('--em-wheel-pad', this.centerPad() + 'px');
    this.setIndexFromScroll(true);
  };

  EmWheel.prototype.snap = function () {
    this.scrollToIndex(this.currentIndexFromScroll(), true);
  };

  EmWheel.prototype.scheduleSnap = function () {
    var self = this;
    window.clearTimeout(this.snapTimer);
    this.snapTimer = window.setTimeout(function () { self.snap(); }, 120);
  };

  /** Gira con animación (clic en una fila, flechas del teclado). */
  EmWheel.prototype.slideBy = function (steps) {
    if (this.disabled || !steps) return;
    this.scrollToIndex(this.index + steps, true);
  };

  EmWheel.prototype.onWheel = function (e) {
    if (this.disabled) return;
    e.preventDefault();
    e.stopPropagation();
    if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

    this.cancelFrame();

    var delta = e.deltaY;
    if (e.deltaMode === 1) delta *= 16;                                  // líneas
    else if (e.deltaMode === 2) delta *= this.root.clientHeight || this.itemHeight() * 5; // páginas

    if (!this.frameId) this.rawTargetTop = this.root.scrollTop;
    this.rawTargetTop = Math.max(0, Math.min(this.maxTop(), this.rawTargetTop + delta * WHEEL_SPEED));
    this.scrollToIndex(Math.round(this.rawTargetTop / this.itemHeight()), true, true);
  };

  EmWheel.prototype.onPointerDown = function (e) {
    var self = this;
    if (this.disabled || (e.button !== undefined && e.button !== 0)) return;

    this.pointerId = e.pointerId;
    this.lastY = e.clientY;
    this.targetTop = this.root.scrollTop;
    this.dragTotal = 0;
    this.dragged = false;
    this.root.classList.add('is-dragging');
    window.clearTimeout(this.snapTimer);
    this.cancelFrame();
    try { this.root.setPointerCapture(e.pointerId); } catch (err) { /* sin captura */ }

    this.onPointerMove = function (ev) {
      if (ev.pointerId !== self.pointerId) return;
      var delta = self.lastY - ev.clientY;
      self.lastY = ev.clientY;
      self.dragTotal += Math.abs(delta);
      /* Con 4 px ya es un arrastre: el clic que viene después no elige fila. */
      if (self.dragTotal > 4) self.dragged = true;
      self.targetTop = Math.max(0, Math.min(self.maxTop(), self.root.scrollTop + delta));
      self.root.scrollTop = self.targetTop;
      self.setIndexFromScroll(true);
    };

    this.onPointerUp = function (ev) {
      if (ev.pointerId !== self.pointerId) return;
      self.endDrag();
    };

    this.root.addEventListener('pointermove', this.onPointerMove);
    this.root.addEventListener('pointerup', this.onPointerUp);
    this.root.addEventListener('pointercancel', this.onPointerUp);
    this.root.focus();
  };

  EmWheel.prototype.endDrag = function () {
    this.root.removeEventListener('pointermove', this.onPointerMove);
    this.root.removeEventListener('pointerup', this.onPointerUp);
    this.root.removeEventListener('pointercancel', this.onPointerUp);
    try { this.root.releasePointerCapture(this.pointerId); } catch (err) { /* ya soltada */ }

    this.pointerId = null;
    this.root.classList.remove('is-dragging');
    this.snap();
  };

  EmWheel.prototype.onClick = function (e) {
    if (this.disabled) return;
    if (this.dragged) { this.dragged = false; return; }

    var item = e.target.closest('.em-wheel__item');
    if (!item) return;

    this.root.focus();
    this.scrollToIndex(Number(item.getAttribute('data-index')) || 0, true);
  };

  EmWheel.prototype.onKey = function (e) {
    if (this.disabled) return;

    var steps = { ArrowUp: -1, ArrowDown: 1, PageUp: -3, PageDown: 3 }[e.key];
    if (steps === undefined) return;

    e.preventDefault();
    this.slideBy(steps);
  };

  EmWheel.prototype.setDisabled = function (disabled) {
    this.disabled = !!disabled;
    this.root.classList.toggle('is-disabled', !!disabled);
    this.root.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    this.root.tabIndex = disabled ? -1 : 0;
  };

  /* =================================================================
     Campo de fecha y hora propio

     El calendario del <input type="datetime-local"> lo dibuja el navegador y
     no se puede estilar, así que el input pasa a ser un botón con un popover
     propio: mes navegable + las dos ruedas de hora y minutos.
     ================================================================= */
  function EmDateTime(cfg) {
    var self = this;
    this.cfg = cfg || {};
    this.locale = this.cfg.locale || 'es-AR';
    this.weekStartsOn = this.cfg.weekStartsOn === undefined ? 1 : this.cfg.weekStartsOn;
    this.step = this.cfg.minuteStep || 5;
    this.value = null;
    this.view = startOfDay(new Date());
    this.disabled = false;
    this.dayEls = [];

    this.dateEl = h('span', { class: 'em-dt__date' });
    this.hourEl = h('span', { class: 'em-dt__hour' });
    this.trigger = h('button', {
      type: 'button',
      class: 'em-dt__trigger',
      id: this.cfg.id || null,
      'aria-haspopup': 'dialog',
      'aria-expanded': 'false',
      'aria-label': this.cfg.ariaLabel || null
    }, [
      icon('calendar'),
      h('span', { class: 'em-dt__label' }, [this.dateEl, this.hourEl]),
      h('span', { class: 'em-sel__caret' }, icon('chevronDown'))
    ]);

    this.monthEl = h('h4', { class: 'em-dt__month' });
    this.prevBtn = h('button', {
      type: 'button', class: 'em-dt__arrow', 'aria-label': 'Mes anterior',
      onclick: function () { self.shiftMonth(-1); }
    }, icon('chevronLeft'));
    this.nextBtn = h('button', {
      type: 'button', class: 'em-dt__arrow', 'aria-label': 'Mes siguiente',
      onclick: function () { self.shiftMonth(1); }
    }, icon('chevronRight'));

    this.weekdaysEl = h('div', { class: 'em-dt__weekdays', 'aria-hidden': 'true' });
    this.gridEl = h('div', { class: 'em-dt__grid', role: 'grid', 'aria-label': 'Día' });
    this.calEl = h('div', { class: 'em-dt__cal' }, [
      h('div', { class: 'em-dt__head' }, [
        this.monthEl,
        h('div', { class: 'em-dt__nav' }, [this.prevBtn, this.nextBtn])
      ]),
      this.weekdaysEl,
      this.gridEl
    ]);

    var hours = [];
    for (var hour = 0; hour < 24; hour++) hours.push(hour);

    this.hourWheel = new EmWheel({
      label: 'Hora',
      values: hours,
      format: pad,
      onChange: function (value) { self.setPart('hour', value); }
    });
    this.minuteWheel = new EmWheel({
      label: 'Minutos',
      values: this.minuteValues(),
      format: pad,
      onChange: function (value) { self.setPart('minute', value); }
    });

    this.todayBtn = h('button', {
      type: 'button', class: 'em-btn em-btn--ghost em-dt__today', text: 'Hoy',
      onclick: function () { self.jumpToday(); }
    });
    this.doneBtn = h('button', {
      type: 'button', class: 'em-btn em-btn--primary em-dt__done', text: 'Listo',
      onclick: function () { floatClose(true); }
    });

    this.pop = h('div', {
      class: 'em-dt__pop', hidden: true, role: 'dialog',
      'data-lenis-prevent': '',
      'aria-label': this.cfg.ariaLabel ? this.cfg.ariaLabel + ': elegir fecha y hora' : 'Elegir fecha y hora'
    }, [
      h('div', { class: 'em-dt__panels' }, [
        this.calEl,
        h('div', { class: 'em-dt__time' }, [
          h('p', { class: 'em-dt__time-label' }, [icon('clock'), h('span', { text: 'Hora' })]),
          h('div', { class: 'em-dt__wheels' }, [
            this.hourWheel.root,
            h('span', { class: 'em-dt__colon', 'aria-hidden': 'true', text: ':' }),
            this.minuteWheel.root
          ])
        ])
      ]),
      h('footer', { class: 'em-dt__foot' }, [this.todayBtn, this.doneBtn])
    ]);

    this.root = h('div', { class: 'em-dt' }, [this.trigger, this.pop]);

    this.float = {
      trigger: this.trigger,
      pop: this.pop,
      matchWidth: false,
      onOpen: function () {
        self.root.classList.add('is-open');
        self.view = startOfDay(self.value || new Date());
        self.renderCalendar();
        self.syncWheels(false, true);
      },
      onClose: function () { self.root.classList.remove('is-open'); }
    };

    this.trigger.addEventListener('click', function () {
      if (self.disabled) return;
      if (floatIsOpen(self.pop)) floatClose(); else floatOpen(self.float);
    });
    this.trigger.addEventListener('keydown', function (e) {
      if (self.disabled) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); if (!floatIsOpen(self.pop)) floatOpen(self.float); }
    });

    this.buildWeekdays();
    this.renderTrigger();
  }

  EmDateTime.prototype.buildWeekdays = function () {
    var start = startOfWeek(new Date(), this.weekStartsOn);
    for (var i = 0; i < 7; i++) {
      var day = addDays(start, i);
      this.weekdaysEl.appendChild(h('span', {
        text: day.toLocaleDateString(this.locale, { weekday: 'narrow' }).toUpperCase()
      }));
    }
  };

  /**
   * Minutos de la rueda: de `step` en `step`, más el minuto exacto que traiga
   * un evento cargado fuera de la grilla (un 17:03 de antes), para que se
   * pueda ver y conservar.
   */
  EmDateTime.prototype.minuteValues = function (extra) {
    var minutes = [];
    for (var minute = 0; minute < 60; minute += this.step) minutes.push(minute);

    if (typeof extra === 'number' && minutes.indexOf(extra) === -1) {
      minutes.push(extra);
      minutes.sort(function (a, b) { return a - b; });
    }

    return minutes;
  };

  EmDateTime.prototype.getValue = function () {
    return this.value ? new Date(this.value.getTime()) : null;
  };

  /**
   * El valor llega como Date o como "YYYY-MM-DDTHH:MM".
   *
   * `silent` (lo usa el diálogo al cargar un evento) es también el momento de
   * recalcular la lista de minutos: girando la rueda no se toca, así que un
   * minuto fuera de la grilla no desaparece de abajo del dedo.
   */
  EmDateTime.prototype.setValue = function (value, silent) {
    var date = value ? parseDate(value) : null;
    this.value = date;
    this.view = startOfDay(date || new Date());

    this.syncWheels(!!silent);
    this.renderTrigger();
    this.renderCalendar();

    if (!silent && typeof this.cfg.onChange === 'function') this.cfg.onChange(this.getValue());
  };

  /** Deja las ruedas en la hora y el minuto del valor actual. */
  EmDateTime.prototype.syncWheels = function (recompute, refresh) {
    var date = this.value || new Date(this.view.getFullYear(), this.view.getMonth(), this.view.getDate(), 9, 0);
    var minute = date.getMinutes();

    if (recompute) this.minuteWheel.setValues(this.minuteValues(minute));

    this.hourWheel.setValue(date.getHours());

    /* Si el minuto no está en la lista (llegó sin recalcular), se recalcula. */
    if (!this.minuteWheel.setValue(minute)) {
      this.minuteWheel.setValues(this.minuteValues(minute));
      this.minuteWheel.setValue(minute);
    }

    if (refresh) {
      this.hourWheel.refresh();
      this.minuteWheel.refresh();
    }
  };

  EmDateTime.prototype.setPart = function (part, value) {
    var base = this.value || new Date(this.view.getFullYear(), this.view.getMonth(), this.view.getDate(), 9, 0);
    var next = new Date(base.getTime());
    if (part === 'hour') next.setHours(value);
    else next.setMinutes(value);
    next.setSeconds(0, 0);
    this.setValue(next);
  };

  EmDateTime.prototype.pickDay = function (day) {
    var base = this.value;
    var next = new Date(
      day.getFullYear(), day.getMonth(), day.getDate(),
      base ? base.getHours() : 9,
      base ? base.getMinutes() : 0
    );
    this.setValue(next);
  };

  EmDateTime.prototype.shiftMonth = function (direction) {
    this.view = new Date(this.view.getFullYear(), this.view.getMonth() + direction, 1);
    this.calEl.classList.remove('is-fwd', 'is-bwd');
    void this.calEl.offsetWidth;
    this.calEl.classList.add(direction > 0 ? 'is-fwd' : 'is-bwd');
    this.renderCalendar();
    if (floatIsOpen(this.pop)) floatSync();
  };

  EmDateTime.prototype.jumpToday = function () {
    var now = new Date();
    var base = this.value;
    this.setValue(new Date(
      now.getFullYear(), now.getMonth(), now.getDate(),
      base ? base.getHours() : now.getHours(),
      base ? base.getMinutes() : 0
    ));
  };

  EmDateTime.prototype.renderTrigger = function () {
    var value = this.value;
    if (!value) {
      this.dateEl.textContent = 'Elegir fecha';
      this.hourEl.textContent = '--:--';
      this.trigger.classList.remove('has-value');
      return;
    }

    this.dateEl.textContent = capitalize(value.toLocaleDateString(this.locale, {
      weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'
    }).replace(/\./g, ''));
    this.hourEl.textContent = pad(value.getHours()) + ':' + pad(value.getMinutes());
    this.trigger.classList.add('has-value');
  };

  EmDateTime.prototype.renderCalendar = function () {
    var self = this;
    var view = this.view;
    var today = new Date();
    var value = this.value;

    this.monthEl.textContent = capitalize(view.toLocaleDateString(this.locale, { month: 'long', year: 'numeric' }));

    var start = startOfWeek(new Date(view.getFullYear(), view.getMonth(), 1), this.weekStartsOn);
    this.gridEl.textContent = '';
    this.dayEls = [];

    for (var i = 0; i < 42; i++) {
      (function (day) {
        var outside = day.getMonth() !== view.getMonth();
        var selected = value && sameDay(day, value);
        var btn = h('button', {
          type: 'button',
          class: 'em-dt__day' + (outside ? ' is-outside' : '') + (sameDay(day, today) ? ' is-today' : '') + (selected ? ' is-selected' : ''),
          'aria-pressed': selected ? 'true' : 'false',
          'aria-label': day.toLocaleDateString(self.locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
          text: String(day.getDate()),
          onclick: function () { self.pickDay(day); }
        });
        btn.addEventListener('keydown', function (e) { self.onDayKey(e, day); });
        self.dayEls.push({ el: btn, day: day });
        self.gridEl.appendChild(btn);
      })(addDays(start, i));
    }

    /* La hora y el minuto los marca la rueda: el valor elegido es el del
       centro, así que no hay nada que sincronizar acá. */
  };

  /** Flechas para moverse por la grilla; el día que se enfoca queda elegido. */
  EmDateTime.prototype.onDayKey = function (e, day) {
    var jump = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7, PageUp: -30, PageDown: 30 }[e.key];
    if (jump === undefined) {
      if (e.key === 'Tab') floatSync();
      return;
    }

    e.preventDefault();
    var target = addDays(day, jump);
    if (target.getMonth() !== this.view.getMonth() || target.getFullYear() !== this.view.getFullYear()) {
      this.view = new Date(target.getFullYear(), target.getMonth(), 1);
    }
    this.pickDay(target);

    var focus = this.dayEls.filter(function (entry) { return sameDay(entry.day, target); })[0];
    if (focus) focus.el.focus();
  };

  EmDateTime.prototype.setDisabled = function (disabled) {
    this.disabled = !!disabled;
    this.trigger.disabled = !!disabled;
    this.hourWheel.setDisabled(disabled);
    this.minuteWheel.setDisabled(disabled);
    this.root.classList.toggle('is-disabled', !!disabled);
    if (disabled && floatIsOpen(this.pop)) floatClose();
  };

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
      customTags: (o.customTags || []).map(function (tag) {
        return { id: String(tag.id), name: tag.name };
      }),
      members: (o.members || []).map(function (member) {
        return {
          id: String(member.id),
          name: member.name || ('Usuario #' + member.id),
          initials: member.initials || '?',
          photo: member.photo || null,
          role: member.role || ''
        };
      }),
      notifyOptions: o.notifyOptions && o.notifyOptions.length ? o.notifyOptions : [{ value: 'none', label: 'No notificar' }],
      notifyTip: o.notifyTip || 'Notificar a los encargados antes de que arranque el evento.',
      locale: o.locale || 'es-AR',
      weekStartsOn: o.weekStartsOn === undefined ? 1 : o.weekStartsOn,
      readOnly: !!o.readOnly,
      onEventCreate: o.onEventCreate,
      onEventUpdate: o.onEventUpdate,
      onEventDelete: o.onEventDelete,
      onTagCreate: o.onTagCreate,
      onTagDelete: o.onTagDelete
    };

    /* id → persona, para ponerle cara a los encargados de cada evento. */
    var map = {};
    this.opts.members.forEach(function (member) { map[member.id] = member; });
    this.memberMap = map;

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

  /* Etiquetas disponibles: primero las fijas, después las del espacio.
     `custom` marca las que se pueden borrar. */
  EventManager.prototype.tagList = function () {
    var out = this.opts.availableTags.map(function (name) {
      return { name: name, id: null, custom: false };
    });

    this.opts.customTags.forEach(function (tag) {
      out.push({ name: tag.name, id: tag.id, custom: true });
    });

    return out;
  };

  EventManager.prototype.tagNames = function () {
    return this.tagList().map(function (tag) { return tag.name; });
  };

  /** Reemplaza las etiquetas propias (después de crear o borrar una). */
  EventManager.prototype.setCustomTags = function (tags) {
    this.opts.customTags = (tags || []).map(function (tag) {
      return { id: String(tag.id), name: tag.name };
    });

    /* Un filtro por una etiqueta que ya no existe se cae. */
    var names = this.tagNames();
    this.state.filters.tags = this.state.filters.tags.filter(function (name) {
      return names.indexOf(name) !== -1;
    });

    this.rebuildTagFilter();
    this.renderTags();
    this.syncFilters();
    this.renderBody();
  };

  /** Encargados de un evento, ya resueltos a personas. */
  EventManager.prototype.people = function (ev) {
    var map = this.memberMap;
    return ev.assignees.map(function (id) {
      return map[String(id)] || { id: String(id), name: 'Usuario #' + id, initials: '?', photo: null };
    });
  };

  EventManager.prototype.notifyLabel = function (value) {
    var match = this.opts.notifyOptions.filter(function (opt) { return opt.value === value; })[0];
    return match ? match.label : '';
  };

  EventManager.prototype.color = function (value) {
    var colors = this.opts.colors;
    for (var i = 0; i < colors.length; i++) if (colors[i].value === value) return colors[i];
    return colors[0];
  };

  /* hour12: false a propósito: el campo de fecha y la base trabajan en 24 h,
     y el navegador formatea es-AR con "p. m." según la versión. */
  EventManager.prototype.fmtTime = function (d) {
    return d.toLocaleTimeString(this.opts.locale, { hour: '2-digit', minute: '2-digit', hour12: false });
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
      this.buildFilter('tags', 'Etiquetas', 'Filtrar por etiqueta', this.tagNames().map(function (t) {
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

    var menu = h('div', { class: 'em-menu', role: 'group', 'aria-label': heading, hidden: true });
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

    this.filterUi[key] = {
      wrap: wrap, trigger: trigger, menu: menu, badge: badge,
      inputs: [], items: [], heading: heading
    };
    this.fillFilterMenu(key, items);

    return wrap;
  };

  /**
   * Contenido del menú de un filtro. Está aparte de buildFilter porque el de
   * etiquetas se vuelve a armar cada vez que se crea o se borra una.
   */
  EventManager.prototype.fillFilterMenu = function (key, items) {
    var self = this;
    var ui = this.filterUi[key];

    ui.items = items;
    ui.inputs = [];
    ui.menu.textContent = '';
    ui.menu.appendChild(h('p', { class: 'em-menu__label', text: ui.heading }));

    items.forEach(function (item) {
      var input = h('input', { type: 'checkbox', value: item.value, class: 'em-menu__input' });
      input.addEventListener('change', function () { self.toggleFilter(key, item.value, input.checked); });
      ui.inputs.push(input);
      ui.menu.appendChild(h('label', { class: 'em-menu__item' }, [
        input,
        h('span', { class: 'em-menu__check' }, icon('check')),
        item.hex ? swatch(item.hex) : null,
        h('span', { text: item.label })
      ]));
    });

    if (!items.length) {
      ui.menu.appendChild(h('p', { class: 'em-menu__empty', text: 'Todavía no hay etiquetas.' }));
    }
  };

  EventManager.prototype.rebuildTagFilter = function () {
    this.fillFilterMenu('tags', this.tagNames().map(function (name) {
      return { value: name, label: name };
    }));
  };

  /* Solo los menús de filtro: los flotantes del diálogo se cierran solos al
     hacer clic afuera (floatClose vive en el listener de mousedown). */
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

    var self = this;

    return s.events.filter(function (ev) {
      if (query) {
        /* El nombre de los encargados también busca: "Juan" trae lo suyo. */
        var haystack = [ev.title, ev.description, ev.category]
          .concat(ev.tags, self.people(ev).map(function (person) { return person.name; }))
          .join(' ').toLowerCase();
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

  /* Renglón de encargados: avatares + nombres (o "N encargados" si son varios). */
  EventManager.prototype.assigneeRow = function (ev, compact) {
    var people = this.people(ev);
    if (!people.length) return null;

    var label = people.length === 1
      ? people[0].name
      : (compact ? people.length + ' encargados' : people.map(function (p) { return p.name; }).join(', '));

    return h('p', { class: 'em-who' }, [
      avatarStack(people, 3),
      h('span', { class: 'em-who__names', text: label })
    ]);
  };

  /* Renglón del recordatorio; nada si el evento no notifica. */
  EventManager.prototype.reminderRow = function (ev) {
    if (!ev.notify || ev.notify === 'none') return null;

    var label = this.notifyLabel(ev.notify);
    if (!label) return null;

    return h('p', { class: 'em-remind' }, [icon('bell'), h('span', { text: label })]);
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
      this.assigneeRow(ev, false),
      this.reminderRow(ev),
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
      this.assigneeRow(ev, true),
      this.reminderRow(ev),
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
                self.assigneeRow(ev, true),
                self.reminderRow(ev),
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

    /* `tip` agrega el iconito con la explicación al lado de la etiqueta. */
    function field(label, id, control, extraClass, tip) {
      return h('div', { class: 'em-field' + (extraClass ? ' ' + extraClass : '') }, [
        h('label', { class: 'em-label', for: id }, [
          h('span', { text: label }),
          tip ? h('span', {
            class: 'em-tip', tabindex: '0', role: 'img',
            'aria-label': tip, 'data-em-tip': tip
          }, icon('info')) : null
        ]),
        control
      ]);
    }

    this.f = {
      title: h('input', { id: uid + '-title', type: 'text', maxlength: '200', placeholder: 'Título del evento', required: true }),
      description: h('textarea', { id: uid + '-desc', rows: '3', maxlength: '2000', placeholder: 'Descripción del evento' })
    };

    /* ── Fechas ──────────────────────────────────────────────────── */
    this.startField = new EmDateTime({
      id: uid + '-start',
      ariaLabel: 'Inicio',
      locale: o.locale,
      weekStartsOn: o.weekStartsOn,
      onChange: function () {
        var previous = self.lastStart;
        self.lastStart = self.startField.getValue();
        self.shiftEnd(previous);
        self.showError('');
      }
    });

    this.endField = new EmDateTime({
      id: uid + '-end',
      ariaLabel: 'Fin',
      locale: o.locale,
      weekStartsOn: o.weekStartsOn,
      onChange: function () { self.showError(''); }
    });

    /* ── Categoría ───────────────────────────────────────────────── */
    this.categorySelect = new EmSelect({
      id: uid + '-cat',
      ariaLabel: 'Categoría',
      placeholder: 'Sin categoría',
      options: [{ value: '', label: 'Sin categoría' }].concat(o.categories.map(function (c) {
        return { value: c, label: c };
      }))
    });

    /* ── Encargados: los miembros del espacio ────────────────────── */
    this.assigneeSelect = new EmSelect({
      id: uid + '-who',
      ariaLabel: 'Encargados',
      multiple: true,
      icon: 'users',
      placeholder: 'Sin encargados',
      emptyText: 'Este espacio todavía no tiene miembros.',
      /* Con pocos miembros el buscador es ruido; con muchos, imprescindible. */
      searchable: o.members.length > 6,
      searchPlaceholder: 'Buscar persona...',
      hint: 'Solo los que tienen acceso a este espacio.',
      options: o.members.map(function (member) {
        return {
          value: member.id, label: member.name, hint: member.role,
          initials: member.initials, photo: member.photo
        };
      }),
      renderValue: function (picked) {
        if (picked.length === 1) {
          return [avatar(picked[0]), h('span', { class: 'em-sel__text', text: picked[0].label })];
        }
        return [
          avatarStack(picked, 3),
          h('span', { class: 'em-sel__text', text: picked.length + ' encargados' })
        ];
      }
    });

    /* ── Recordatorio ────────────────────────────────────────────── */
    this.notifySelect = new EmSelect({
      id: uid + '-notify',
      ariaLabel: 'Notificaciones',
      placeholder: 'No notificar',
      options: o.notifyOptions.map(function (opt) {
        return {
          value: opt.value,
          label: opt.label,
          icon: opt.value === 'none' ? 'bellOff' : 'bell'
        };
      })
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

    /* ── Etiquetas: las fijas, las del espacio y el alta de una nueva ── */
    this.tagButtons = [];
    this.tagsEl = h('div', { class: 'em-tags', role: 'group', 'aria-label': 'Etiquetas' });

    this.tagInput = h('input', {
      type: 'text', maxlength: '40', class: 'em-tagnew__input',
      placeholder: 'Nombre de la etiqueta', 'aria-label': 'Nombre de la etiqueta nueva'
    });
    this.tagSaveBtn = h('button', {
      type: 'button', class: 'em-btn em-btn--primary em-tagnew__save', text: 'Crear',
      onclick: function () { self.createTag(); }
    });
    this.tagCancelBtn = h('button', {
      type: 'button', class: 'em-btn em-btn--ghost', text: 'Cancelar',
      onclick: function () { self.toggleTagForm(false); }
    });
    this.tagFormEl = h('div', { class: 'em-tagnew', hidden: true }, [
      this.tagInput, this.tagSaveBtn, this.tagCancelBtn
    ]);
    this.tagNoteEl = h('p', { class: 'em-tagnew__note', role: 'alert', hidden: true });

    this.tagInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); self.createTag(); }
      else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); self.toggleTagForm(false); }
    });
    this.tagInput.addEventListener('input', function () { self.showTagNote(''); });

    this.renderTags([]);

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
          field('Inicio', uid + '-start', this.startField.root),
          field('Fin', uid + '-end', this.endField.root)
        ]),
        h('div', { class: 'em-row' }, [
          field('Categoría', uid + '-cat', this.categorySelect.root),
          h('div', { class: 'em-field' }, [h('span', { class: 'em-label', id: uid + '-color-label', text: 'Color' }), colors])
        ]),
        h('div', { class: 'em-row' }, [
          field('Encargado(s)', uid + '-who', this.assigneeSelect.root),
          field('Notificaciones', uid + '-notify', this.notifySelect.root, null, o.notifyTip)
        ]),
        h('div', { class: 'em-field' }, [
          h('span', { class: 'em-label', text: 'Etiquetas' }),
          this.tagsEl,
          this.tagFormEl,
          this.tagNoteEl
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
    /* Al terminar la animación de entrada el transform del diálogo se va y el
       bloque contenedor de los popovers vuelve a ser el viewport. */
    this.dialog.addEventListener('animationend', function () { floatSync(); });

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

    this.startField.setValue(start, true);
    this.endField.setValue(end, true);
    /* Referencia para conservar la duración si después se mueve el inicio. */
    this.lastStart = start;

    this.categorySelect.setValue(ev ? ev.category : (o.categories[0] || ''));
    this.assigneeSelect.setValue(ev ? ev.assignees.map(String) : []);
    this.notifySelect.setValue(ev ? ev.notify : 'none');

    var colorValue = ev ? this.color(ev.color).value : o.colors[0].value;
    this.colorInputs.forEach(function (input) { input.checked = input.value === colorValue; });

    this.renderTags(ev ? ev.tags : []);
    this.toggleTagForm(false);

    [this.f.title, this.f.description].concat(this.colorInputs)
      .forEach(function (control) { control.disabled = readOnly; });
    [this.startField, this.endField, this.categorySelect, this.assigneeSelect, this.notifySelect]
      .forEach(function (control) { control.setDisabled(readOnly); });

    this.deleteBtn.hidden = readOnly || !ev;
    this.deleteBtn.textContent = 'Eliminar';
    this.deleteBtn.removeAttribute('data-armed');
    this.saveBtn.hidden = readOnly;
    this.saveBtn.textContent = ev ? 'Guardar' : 'Crear';
    this.cancelBtn.textContent = readOnly ? 'Cerrar' : 'Cancelar';
    this.showError('');
    this.setBusy(false);

    this.closeMenus();
    floatClose();
    if (typeof this.dialog.showModal === 'function') this.dialog.showModal();
    else this.dialog.setAttribute('open', '');
    if (!readOnly) this.f.title.focus();
  };

  EventManager.prototype.closeDialog = function () {
    if (this.busy) return;
    /* Un menú flotante abierto quedaría suelto sobre la página. */
    floatClose();
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
      startTime: this.startField.getValue(),
      endTime: this.endField.getValue(),
      color: checked ? checked.value : this.opts.colors[0].value,
      category: this.categorySelect.getValue(),
      tags: this.selectedTags(),
      assignees: this.assigneeSelect.getValue().map(Number),
      notify: this.notifySelect.getValue() || 'none'
    };
  };

  /* Mueve el fin manteniendo la duración que tenía respecto del inicio
     anterior. Si no había una razonable, deja una hora. */
  EventManager.prototype.shiftEnd = function (previousStart) {
    var start = this.startField.getValue();
    if (!start) return;

    var end = this.endField.getValue();
    var duration = previousStart && end && end > previousStart ? end - previousStart : 3600000;
    this.endField.setValue(new Date(start.getTime() + duration), true);
  };

  /* =================================================================
     Etiquetas del diálogo (fijas + propias del espacio)
     ================================================================= */
  EventManager.prototype.selectedTags = function () {
    return (this.tagButtons || [])
      .filter(function (btn) { return btn.getAttribute('aria-pressed') === 'true'; })
      .map(function (btn) { return btn.getAttribute('data-tag'); });
  };

  /**
   * Dibuja las píldoras. `selected` es la lista de etiquetas marcadas; si no
   * se pasa, se conservan las que estaban (se vuelve a dibujar al crear o
   * borrar una etiqueta con el diálogo abierto).
   */
  EventManager.prototype.renderTags = function (selected) {
    var self = this;
    if (!this.tagsEl) return;

    var picked = selected || this.selectedTags();
    var readOnly = this.opts.readOnly;

    this.tagsEl.textContent = '';
    this.tagButtons = [];
    this.tagAddBtn = null;

    this.tagList().forEach(function (tag) {
      var on = picked.indexOf(tag.name) !== -1;
      var wrap = h('span', { class: 'em-tag-wrap' + (on ? ' is-on' : '') + (tag.custom ? ' em-tag-wrap--own' : '') });

      var btn = h('button', {
        type: 'button', class: 'em-tag', 'data-tag': tag.name, text: tag.name,
        'aria-pressed': on ? 'true' : 'false',
        title: tag.custom ? 'Etiqueta de este espacio' : 'Etiqueta fija',
        onclick: function () {
          var next = btn.getAttribute('aria-pressed') !== 'true';
          btn.setAttribute('aria-pressed', next ? 'true' : 'false');
          wrap.classList.toggle('is-on', next);
        }
      });
      btn.disabled = readOnly;
      self.tagButtons.push(btn);
      wrap.appendChild(btn);

      /* Las fijas son del sistema: solo se borran las del espacio. */
      if (tag.custom && !readOnly && typeof self.opts.onTagDelete === 'function') {
        var remove = h('button', {
          type: 'button', class: 'em-tag__remove',
          'aria-label': 'Borrar la etiqueta ' + tag.name,
          title: 'Borrar la etiqueta ' + tag.name,
          onclick: function () { self.removeTag(tag, remove); }
        }, icon('x'));
        wrap.appendChild(remove);
      }

      self.tagsEl.appendChild(wrap);
    });

    if (!readOnly && typeof this.opts.onTagCreate === 'function') {
      this.tagAddBtn = h('button', {
        type: 'button', class: 'em-tag em-tag--add',
        onclick: function () { self.toggleTagForm(true); }
      }, [icon('plus'), h('span', { text: 'Nueva etiqueta' })]);
      this.tagsEl.appendChild(this.tagAddBtn);
    }
  };

  EventManager.prototype.selectTag = function (name, on) {
    this.tagButtons.forEach(function (btn) {
      if (btn.getAttribute('data-tag') !== name) return;
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      if (btn.parentNode) btn.parentNode.classList.toggle('is-on', !!on);
    });
  };

  EventManager.prototype.toggleTagForm = function (open) {
    this.showTagNote('');
    this.tagFormEl.hidden = !open;
    this.tagInput.value = '';
    this.tagBusy(false);
    if (this.tagAddBtn) this.tagAddBtn.hidden = !!open;
    if (open) this.tagInput.focus();
  };

  EventManager.prototype.showTagNote = function (message, kind) {
    this.tagNoteEl.textContent = message || '';
    this.tagNoteEl.hidden = !message;
    this.tagNoteEl.classList.toggle('is-danger', kind === 'danger');
  };

  EventManager.prototype.tagBusy = function (busy) {
    this.tagSaveBtn.disabled = !!busy;
    this.tagCancelBtn.disabled = !!busy;
    this.tagInput.disabled = !!busy;
  };

  EventManager.prototype.createTag = function () {
    var self = this;
    if (typeof this.opts.onTagCreate !== 'function') return;

    var name = this.tagInput.value.trim().replace(/\s+/g, ' ');
    if (!name) {
      this.showTagNote('Escribí un nombre para la etiqueta.', 'danger');
      this.tagInput.focus();
      return;
    }

    var repeated = this.tagNames().filter(function (existing) {
      return existing.toLowerCase() === name.toLowerCase();
    })[0];
    if (repeated) {
      this.showTagNote('Ya existe la etiqueta "' + repeated + '".', 'danger');
      return;
    }

    this.tagBusy(true);
    resolved(this.opts.onTagCreate(name)).then(function (result) {
      var tags = result && result.tags
        ? result.tags
        : self.opts.customTags.concat([{ id: (result && result.tag && result.tag.id) || name, name: name }]);

      /* setCustomTags redibuja conservando lo que estaba marcado. */
      self.setCustomTags(tags);
      /* La etiqueta recién creada queda puesta en el evento que se edita. */
      self.selectTag(name, true);
      self.toggleTagForm(false);
    }).catch(function (error) {
      self.tagBusy(false);
      self.showTagNote((error && error.message) || 'No se pudo crear la etiqueta.', 'danger');
    });
  };

  /* Borrar pide dos clics: el primero arma la cruz, como el botón de eliminar
     del diálogo. La etiqueta se va de todo el espacio, no solo del evento. */
  EventManager.prototype.removeTag = function (tag, btn) {
    var self = this;
    if (this.busy || typeof this.opts.onTagDelete !== 'function') return;

    if (btn.getAttribute('data-armed') !== 'true') {
      this.tagsEl.querySelectorAll('.em-tag__remove[data-armed]').forEach(function (node) {
        node.removeAttribute('data-armed');
      });
      btn.setAttribute('data-armed', 'true');
      this.showTagNote('Tocá otra vez la cruz para borrar "' + tag.name + '" de todo el espacio.', 'danger');
      window.setTimeout(function () {
        if (!btn.parentNode) return;
        btn.removeAttribute('data-armed');
        if (!self.tagFormEl.hidden) return;
        self.showTagNote('');
      }, 4000);
      return;
    }

    btn.disabled = true;
    this.showTagNote('Borrando "' + tag.name + '"...');

    resolved(this.opts.onTagDelete(tag.id)).then(function (result) {
      if (result && result.events) self.state.events = (result.events || []).map(normalize);
      self.setCustomTags(result && result.tags ? result.tags : self.opts.customTags.filter(function (t) {
        return t.id !== tag.id;
      }));
      self.showTagNote('');
    }).catch(function (error) {
      btn.disabled = false;
      btn.removeAttribute('data-armed');
      self.showTagNote((error && error.message) || 'No se pudo borrar la etiqueta.', 'danger');
    });
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
  /* El select propio también lo usa la configuración de columnas de stock. */
  window.EmSelect = EmSelect;
})(window, document);
