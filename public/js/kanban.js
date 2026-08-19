/* =====================================================================
   PrevCS · Tablero Kanban por ESP32
   ---------------------------------------------------------------------
   Arquitectura (toda vanilla, sin dependencias):

     api      → capa HTTP. Encola los POST porque el CSRF se regenera en
                cada request y dos peticiones en paralelo se pisarían.
     state    → espejo del payload del servidor. El servidor devuelve el
                tablero completo en cada mutación, así la UI no puede
                quedar desincronizada de la base.
     render   → reconciliador con claves: reutiliza los nodos existentes
                (por id) para que las animaciones FLIP y el foco sobrevivan
                a un re-render.
     dnd      → drag and drop con pointer events (mouse + touch), clon
                flotante, hueco reservado y FLIP en los hermanos.

   Todo cambio se aplica primero en local (optimistic UI) y se revierte si
   el servidor falla, mostrando un toast con opción de reintentar.
   ===================================================================== */
(function () {
  'use strict';

  /* ═══════════════════════════════════════════════════════════════════
     Utilidades
     ═══════════════════════════════════════════════════════════════════ */

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
// Crea un nodo con tag, clase y texto. Devuelve el nodo creado.
  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  }
// Limpia un nodo de todos sus hijos. Devuelve el mismo nodo para encadenar.
  function clear(node) {
    while (node.firstChild) node.removeChild(node.firstChild);
    return node;
  }

  // Debounce: devuelve una función que espera a que deje de llamarse durante wait ms para ejecutar fn. Útil para input events que disparan render.
  function debounce(fn, wait) {
    var timer = null;
    return function () {
      var args = arguments, self = this;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () { fn.apply(self, args); }, wait);
    };
  }

  // Clona un objeto o array profundo. JSON no soporta Date ni RegExp, pero ninguno de los dos se usa en el payload del servidor.

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  /* ── Campos que se adaptan al contenido ─────────────────────────────
     Ningún campo del tablero tiene ancho fijo ni manija de redimensionado:
     los textarea crecen en alto y los input en ancho, siempre topados por
     el max-width del CSS. ────────────────────────────────────────────── */
  var measureCanvas = null;
  var MAX_FIELD_WIDTH = 560;


  // Devuelve el ancho en píxeles que ocupa el texto del campo, incluyendo el padding y el borde. Se usa para ajustar el ancho de los input.
  function textWidth(field) {
    if (!measureCanvas) measureCanvas = document.createElement('canvas');
    var ctx = measureCanvas.getContext('2d');
    var style = window.getComputedStyle(field);
    ctx.font = style.fontStyle + ' ' + style.fontWeight + ' ' + style.fontSize + ' ' + style.fontFamily;
    var value = field.value || field.placeholder || '';
    var spacing = parseFloat(style.letterSpacing) || 0;

    return ctx.measureText(value).width + spacing * value.length;
  }

// Ajusta el ancho de un input o el alto de un textarea al contenido. 
// Se llama al input event y al focus para que el campo crezca mientras 
// el usuario escribe. Se llama al load del documento para que los campos 
// ya renderizados se ajusten al valor inicial.  

  function autoSize(field) {
    if (!field) return;

    // Un campo que todavía no entró al documento no se puede medir; se
    // reintenta una sola vez cuando ya esté puesto.
    if (!field.isConnected) {
      window.setTimeout(function () { if (field.isConnected) autoSize(field); }, 0);
      return;
    }

    if (field.tagName === 'TEXTAREA') {
      field.style.height = 'auto';
      field.style.height = field.scrollHeight + 'px';
      return;
    }

    // Los campos con tipo propio (date, number, checkbox…) traen su
    // control nativo y no se miden por el texto.
    if (field.type && field.type !== 'text' && field.type !== 'search') return;

    var style = window.getComputedStyle(field);
    var chrome = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight)
      + parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);

    // El margen extra es solo el que necesita el cursor para no quedar
    // pegado al borde: de más, deja un hueco vacío detrás del texto.
    field.style.width = Math.min(MAX_FIELD_WIDTH, Math.max(44, Math.ceil(textWidth(field)) + chrome + 2)) + 'px';
  }

  /* Agenda trabajo de pintado. requestAnimationFrame no dispara cuando la
     pestaña está en segundo plano, así que se acompaña de un timeout: gana
     el que llegue primero y el otro queda anulado por la bandera. */
  function schedule(fn) {
    var done = false;
    var run = function () {
      if (done) return;
      done = true;
      fn();
    };
    window.requestAnimationFrame(run);
    window.setTimeout(run, 40);
  }

  /* ── Scroll suave dentro de una caja ────────────────────────────────
     Lenis maneja el scroll de la página, pero no llega al modal: la caja
     lleva data-lenis-prevent y además Lenis se frena entero mientras hay
     una ventana abierta, así que adentro quedaba el scroll nativo a los
     saltos. Esto le devuelve la misma inercia sin tocar a Lenis: la rueda
     mueve un objetivo y un rAF lo persigue interpolando.

     Devuelve una función para reamarrar el objetivo cuando el contenido
     de la caja cambia (si no, apunta a un alto que ya no existe).
     ──────────────────────────────────────────────────────────────────── */
  function bindSmoothScroll(node) {
    if (!node || reduceMotion.matches) return function () {};

    var target = 0;
    var raf = 0;
    var EASE = 0.16;

    function step() {
      var delta = target - node.scrollTop;
      if (Math.abs(delta) < 0.5) {
        node.scrollTop = target;
        raf = 0;
        return;
      }
      node.scrollTop += delta * EASE;
      raf = window.requestAnimationFrame(step);
    }

    /* Una lista con scroll propio adentro (el historial de la tarea, por
       ejemplo) se queda con la rueda mientras le quede recorrido. */
    function ownsWheel(from, deltaY) {
      var current = from;
      while (current && current !== node) {
        if (current.scrollHeight > current.clientHeight + 1) {
          var overflow = window.getComputedStyle(current).overflowY;
          if (overflow === 'auto' || overflow === 'scroll') {
            var room = deltaY > 0
              ? current.scrollHeight - current.clientHeight - current.scrollTop
              : current.scrollTop;
            if (room > 1) return true;
          }
        }
        current = current.parentElement;
      }
      return false;
    }

    node.addEventListener('wheel', function (event) {
      if (event.ctrlKey || !event.deltaY) return;

      var limit = Math.max(0, node.scrollHeight - node.clientHeight);
      if (limit <= 0) return;
      if (ownsWheel(event.target, event.deltaY)) return;

      // deltaMode 1 son líneas y 2 son páginas: se pasan a píxeles.
      var amount = event.deltaY;
      if (event.deltaMode === 1) amount *= 18;
      else if (event.deltaMode === 2) amount *= node.clientHeight;

      event.preventDefault();
      target = Math.max(0, Math.min(limit, (raf ? target : node.scrollTop) + amount));
      if (!raf) raf = window.requestAnimationFrame(step);
    }, { passive: false });

    return function resync() {
      if (raf) { window.cancelAnimationFrame(raf); raf = 0; }
      target = node.scrollTop;
    };
  }

  /* Iconos inline. Markup fijo del propio archivo: nunca lleva datos del
     usuario, así que usar innerHTML acá es seguro. */
  var PATHS = {
    plus: '<path d="M10 4v12M4 10h12"/>',
    search: '<circle cx="9" cy="9" r="5.2"/><path d="m13 13 4 4"/>',
    dots: '<circle cx="10" cy="4.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="10" cy="10" r="1.5" fill="currentColor" stroke="none"/><circle cx="10" cy="15.5" r="1.5" fill="currentColor" stroke="none"/>',
    chevron: '<path d="m5 8 5 5 5-5"/>',
    close: '<path d="m5 5 10 10M15 5 5 15"/>',
    check: '<path d="m4 10.5 4 4 8-9"/>',
    clock: '<circle cx="10" cy="10" r="7"/><path d="M10 6v4.3l2.6 1.6"/>',
    calendar: '<rect x="3.2" y="4.5" width="13.6" height="12" rx="2"/><path d="M3.2 8.4h13.6M7 3v3M13 3v3"/>',
    comment: '<path d="M16.5 11.5a2 2 0 0 1-2 2H7l-3.5 3v-11a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2z"/>',
    clip: '<path d="M14.5 9.2 9.7 14a3 3 0 0 1-4.3-4.3l5.4-5.4a2 2 0 1 1 2.8 2.8l-5.3 5.4a1 1 0 0 1-1.4-1.4l4.7-4.7"/>',
    list: '<path d="M7 5.5h10M7 10h10M7 14.5h10M3.5 5.5h.01M3.5 10h.01M3.5 14.5h.01"/>',
    filter: '<path d="M3.5 5h13l-5 5.6V16l-3-1.6v-3.8z"/>',
    sort: '<path d="M6 4v12M6 16l-2.5-2.5M6 16l2.5-2.5M14 16V4M14 4l-2.5 2.5M14 4l2.5 2.5"/>',
    archive: '<rect x="3.2" y="4" width="13.6" height="3.4" rx="1"/><path d="M4.6 7.4v7.4a1.6 1.6 0 0 0 1.6 1.6h7.6a1.6 1.6 0 0 0 1.6-1.6V7.4M8.2 10.6h3.6"/>',
    trash: '<path d="M4.5 6h11M8 6V4.4A1 1 0 0 1 9 3.4h2a1 1 0 0 1 1 1V6M6.2 6l.7 9.2a1.4 1.4 0 0 0 1.4 1.3h3.4a1.4 1.4 0 0 0 1.4-1.3L14.2 6"/>',
    copy: '<rect x="6.5" y="6.5" width="9.5" height="9.5" rx="1.8"/><path d="M13 6.5v-1a1.8 1.8 0 0 0-1.8-1.8H5.8A1.8 1.8 0 0 0 4 5.5v5.4A1.8 1.8 0 0 0 5.8 12.7h1"/>',
    palette: '<path d="M10 3a7 7 0 0 0 0 14c1 0 1.6-.7 1.6-1.5 0-.9-.8-1.4-.8-2.2 0-.7.6-1.3 1.3-1.3H14a3 3 0 0 0 3-3c0-3.3-3.1-6-7-6z"/><circle cx="6.6" cy="8.4" r="1" fill="currentColor" stroke="none"/><circle cx="10" cy="6.4" r="1" fill="currentColor" stroke="none"/><circle cx="13.4" cy="8.4" r="1" fill="currentColor" stroke="none"/>',
    user: '<circle cx="10" cy="7" r="3"/><path d="M4.5 16.5c.6-2.8 2.8-4.3 5.5-4.3s4.9 1.5 5.5 4.3"/>',
    tag: '<path d="M3.6 9.3V4.4A.8.8 0 0 1 4.4 3.6h4.9c.2 0 .4.1.6.2l6.3 6.3a.8.8 0 0 1 0 1.2l-4.9 4.9a.8.8 0 0 1-1.2 0L3.8 9.9a.8.8 0 0 1-.2-.6z"/><circle cx="6.8" cy="6.8" r="1.1"/>',
    flag: '<path d="M5 17V4M5 4.6h8.5l-1.6 2.9 1.6 2.9H5"/>',
    move: '<path d="M10 3v14M3 10h14M10 3 7.6 5.4M10 3l2.4 2.4M10 17l-2.4-2.4M10 17l2.4-2.4M3 10l2.4-2.4M3 10l2.4 2.4M17 10l-2.4-2.4M17 10l-2.4 2.4"/>',
    undo: '<path d="M7 8H4V5M4.3 8a6.2 6.2 0 1 1-.6 4"/>',
    link: '<path d="M8.4 11.6a2.8 2.8 0 0 0 4 0l2.4-2.4a2.8 2.8 0 0 0-4-4l-.8.8"/><path d="M11.6 8.4a2.8 2.8 0 0 0-4 0l-2.4 2.4a2.8 2.8 0 0 0 4 4l.8-.8"/>',
    history: '<path d="M3.6 10a6.4 6.4 0 1 0 1.9-4.5M3.4 3.4v3.2h3.2M10 6.4V10l2.6 1.6"/>',
    collapse: '<path d="m12 5-5 5 5 5"/>',
    note: '<path d="M5 3.6h10a1 1 0 0 1 1 1v10.8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4.6a1 1 0 0 1 1-1z"/><path d="M7 7.4h6M7 10.4h6M7 13.4h3.5"/>',
    broom: '<path d="M4 16.4h12M13 3.6 8.2 8.4M7 7.2l5.4 5.4-2 2a3.4 3.4 0 0 1-4.8 0l-.6-.6a3.4 3.4 0 0 1 0-4.8z"/>'
  };

  function icon(name, className) {
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 20 20');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.6');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    if (className) svg.setAttribute('class', className);
    svg.innerHTML = PATHS[name] || '';
    return svg;
  }

  var PRIORITIES = {
    baja:    { label: 'Baja',    glyph: '↓',  color: 'celeste' },
    media:   { label: 'Media',   glyph: '=',  color: 'gris' },
    alta:    { label: 'Alta',    glyph: '↑',  color: 'naranja' },
    urgente: { label: 'Urgente', glyph: '!!', color: 'rojo' }
  };
  var PRIORITY_ORDER = { urgente: 0, alta: 1, media: 2, baja: 3 };

  var COLOR_NAMES = {
    azul: 'Azul', celeste: 'Celeste', turquesa: 'Turquesa', verde: 'Verde',
    amarillo: 'Amarillo', naranja: 'Naranja', rojo: 'Rojo', rosa: 'Rosa',
    violeta: 'Violeta', gris: 'Gris'
  };

  function formatDate(iso) {
    if (!iso) return '';
    var parts = String(iso).slice(0, 10).split('-');
    if (parts.length !== 3) return String(iso);
    return parts[2] + '/' + parts[1] + '/' + parts[0].slice(2);
  }

  function formatDateTime(value) {
    if (!value) return '';
    var normalised = String(value).replace(' ', 'T');
    var date = new Date(normalised);
    if (isNaN(date.getTime())) return String(value);
    var pad = function (n) { return String(n).padStart(2, '0'); };
    return pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + '/' + String(date.getFullYear()).slice(2) +
      ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
  }

  function formatMinutes(total) {
    total = Number(total) || 0;
    if (total <= 0) return '0m';
    var hours = Math.floor(total / 60), minutes = total % 60;
    if (hours && minutes) return hours + 'h ' + minutes + 'm';
    return hours ? hours + 'h' : minutes + 'm';
  }

  function formatSize(bytes) {
    bytes = Number(bytes) || 0;
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  /* Escribe texto resaltando las coincidencias de la búsqueda. Trabaja con
     nodos de texto, nunca con innerHTML: el contenido del usuario no se
     interpreta como HTML en ningún momento. */
  function writeHighlighted(node, text, needle) {
    clear(node);
    text = text || '';
    if (!needle) { node.textContent = text; return node; }

    var haystack = text.toLowerCase(), term = needle.toLowerCase(), from = 0, at;
    while ((at = haystack.indexOf(term, from)) !== -1) {
      if (at > from) node.appendChild(document.createTextNode(text.slice(from, at)));
      var mark = el('mark', null, text.slice(at, at + term.length));
      node.appendChild(mark);
      from = at + term.length;
    }
    if (from < text.length) node.appendChild(document.createTextNode(text.slice(from)));
    return node;
  }

  /* Pinta las menciones @usuario sin insertar HTML del comentario. */
  function writeWithMentions(node, text) {
    clear(node);
    var pattern = /@[\wÁÉÍÓÚÜÑáéíóúüñ.]{2,40}/g, last = 0, match;
    while ((match = pattern.exec(text)) !== null) {
      if (match.index > last) node.appendChild(document.createTextNode(text.slice(last, match.index)));
      node.appendChild(el('span', 'kb-mention', match[0]));
      last = match.index + match[0].length;
    }
    if (last < text.length) node.appendChild(document.createTextNode(text.slice(last)));
    return node;
  }

  function toast(message, type, action) {
    if (window.PrevcsToast && window.PrevcsToast.show) {
      return window.PrevcsToast.show(message, type || 'success', action ? 6500 : 3200, action);
    }
    return null;
  }

  /* ═══════════════════════════════════════════════════════════════════
     Instancia del tablero
     ═══════════════════════════════════════════════════════════════════ */

  function createBoard(root) {
    var deviceId = root.getAttribute('data-kb-device');
    var baseUrl = root.getAttribute('data-kb-base').replace(/\/$/, '');
    var tokenName = root.getAttribute('data-kb-token-name');

    var state = { board: null, columns: [], tasks: [], labels: [], members: [], colors: [], user_id: 0, today: '' };
    var ui = {
      search: '',
      filters: { priority: [], color: [], label: [], assignee: [], column: [], status: [], due: '' },
      sort: 'manual',
      selectedTaskId: null,
      openTaskId: null,
      detail: null,
      detailPending: false,
      composingColumn: null,
      creatingColumn: false
    };

    /* Los modales viven fuera del root (son hermanos, para que el backdrop
       cubra la pantalla sin quedar recortado por el card del tablero), así
       que se buscan en el documento acotando por la placa. */
    var modal = document.querySelector('[data-kb-modal][data-kb-device="' + deviceId + '"]');
    var confirmModal = document.querySelector('[data-kb-confirm][data-kb-device="' + deviceId + '"]');

    /* ── Referencias del DOM del partial ───────────────────────────── */
    var dom = {
      surface: root.querySelector('[data-kb-surface]'),
      board: root.querySelector('[data-kb-board]'),
      title: root.querySelector('[data-kb-title]'),
      toolbar: root.querySelector('[data-kb-toolbar]'),
      search: root.querySelector('[data-kb-search]'),
      filtersBtn: root.querySelector('[data-kb-filters-btn]'),
      filters: root.querySelector('[data-kb-filters]'),
      sortBtn: root.querySelector('[data-kb-sort]'),
      archiveBtn: root.querySelector('[data-kb-archive]'),
      bgBtn: root.querySelector('[data-kb-bg]'),
      saving: root.querySelector('[data-kb-saving]'),
      offline: root.querySelector('[data-kb-offline]'),
      modal: modal,
      modalBody: modal.querySelector('[data-kb-modal-body]'),
      modalTitle: modal.querySelector('[data-kb-modal-title]'),
      confirm: confirmModal,
      confirmText: confirmModal.querySelector('[data-kb-confirm-text]'),
      confirmAccept: confirmModal.querySelector('[data-kb-confirm-accept]'),
      done: root.querySelector('[data-kb-done]'),
      doneList: root.querySelector('[data-kb-done-list]'),
      doneSearch: root.querySelector('[data-kb-done-search]'),
      doneCount: root.querySelector('[data-kb-done-count]')
    };

    /* ═════════════════════════════════════════════════════════════════
       Capa HTTP
       ═════════════════════════════════════════════════════════════════ */

    var api = {
      token: root.getAttribute('data-kb-token'),
      chain: Promise.resolve(),
      inFlight: 0
    };

    function markSaving(on, ok) {
      if (!dom.saving) return;
      dom.saving.classList.toggle('is-on', on);
      dom.saving.classList.toggle('is-saved', !!ok);
      dom.saving.textContent = on ? (ok ? 'Cambios guardados' : 'Guardando…') : '';
    }

    function send(method, path, body, isForm) {
      var options = { method: method, headers: {}, credentials: 'same-origin' };
      options.headers['X-Requested-With'] = 'XMLHttpRequest';

      if (method !== 'GET') {
        // El CSRF se regenera en cada POST: siempre mandamos el último.
        options.headers['X-CSRF-TOKEN'] = api.token;
        if (isForm) {
          body.append(tokenName, api.token);
          options.body = body;
        } else {
          options.headers['Content-Type'] = 'application/json';
          options.body = JSON.stringify(body || {});
        }
      }

      return fetch(baseUrl + path, options).then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (data) {
          if (data && data.csrf) api.token = data.csrf;
          if (!response.ok || !data || data.ok === false) {
            var error = new Error((data && data.error) || 'No se pudieron guardar los cambios.');
            error.status = response.status;
            throw error;
          }
          return data;
        });
      });
    }

    /* Las mutaciones van en cola: con el CSRF regenerándose, dos POST en
       paralelo harían fallar al segundo. */
    function request(method, path, body, isForm) {
      if (method === 'GET') return send(method, path, body, isForm);

      api.inFlight++;
      markSaving(true, false);

      var run = function () {
        return send(method, path, body, isForm);
      };
      var task = api.chain.then(run, run);

      api.chain = task.catch(function () { /* la cadena sigue viva tras un error */ });

      return task.then(function (data) {
        if (--api.inFlight === 0) {
          markSaving(true, true);
          window.setTimeout(function () { if (api.inFlight === 0) markSaving(false, false); }, 1400);
        }
        return data;
      }, function (error) {
        if (--api.inFlight === 0) markSaving(false, false);
        throw error;
      });
    }

    /**
     * Mutación con UI optimista: aplica el cambio local, dispara el request
     * y, si falla, restaura el estado anterior y ofrece reintentar.
     */
    function mutate(method, path, body, optimistic, options) {
      options = options || {};
      var snapshot = clone({ columns: state.columns, tasks: state.tasks, labels: state.labels, board: state.board });

      if (typeof optimistic === 'function') {
        optimistic();
        render();
      }

      return request(method, path, body).then(function (data) {
        applyPayload(data);
        render();
        if (data.task !== undefined && ui.openTaskId) {
          ui.detail = data.task;
          renderDetail();
        }
        if (options.success) toast(options.success, 'success', options.undo || null);
        if (options.after) options.after(data);
        return data;
      }).catch(function (error) {
        state.columns = snapshot.columns;
        state.tasks = snapshot.tasks;
        state.labels = snapshot.labels;
        state.board = snapshot.board;
        render();
        toast(error.message || 'No se pudieron guardar los cambios.', 'error', {
          label: 'Reintentar',
          onClick: function () { mutate(method, path, body, optimistic, options); }
        });
        throw error;
      });
    }

    function applyPayload(data) {
      if (data.board) state.board = data.board;
      if (data.columns) state.columns = data.columns;
      if (data.tasks) state.tasks = data.tasks;
      if (data.labels) state.labels = data.labels;
      if (data.members) state.members = data.members;
      if (data.colors) state.colors = data.colors;
      if (data.user_id) state.user_id = data.user_id;
      if (data.today) state.today = data.today;
    }

    /* ═════════════════════════════════════════════════════════════════
       Consultas sobre el estado
       ═════════════════════════════════════════════════════════════════ */

    function taskById(id) {
      for (var i = 0; i < state.tasks.length; i++) {
        if (state.tasks[i].id === id) return state.tasks[i];
      }
      return null;
    }

    function columnById(id) {
      for (var i = 0; i < state.columns.length; i++) {
        if (state.columns[i].id === id) return state.columns[i];
      }
      return null;
    }

    function labelById(id) {
      for (var i = 0; i < state.labels.length; i++) {
        if (state.labels[i].id === id) return state.labels[i];
      }
      return null;
    }

    function memberById(id) {
      for (var i = 0; i < state.members.length; i++) {
        if (state.members[i].id === id) return state.members[i];
      }
      return null;
    }

    function tasksOfColumn(columnId) {
      return state.tasks.filter(function (task) { return task.column_id === columnId; })
        .sort(function (a, b) { return a.position - b.position || a.id - b.id; });
    }

    function activeFilterCount() {
      var total = 0, key;
      for (key in ui.filters) {
        if (!Object.prototype.hasOwnProperty.call(ui.filters, key)) continue;
        var value = ui.filters[key];
        total += Array.isArray(value) ? value.length : (value ? 1 : 0);
      }
      return total + (ui.search ? 1 : 0);
    }

    function isOverdue(task) {
      return !!task.due_date && !task.is_completed && task.due_date < state.today;
    }

    function isDueSoon(task) {
      if (!task.due_date || task.is_completed) return false;
      var due = new Date(task.due_date + 'T00:00:00');
      var today = new Date(state.today + 'T00:00:00');
      var days = Math.round((due - today) / 86400000);
      return days >= 0 && days <= 2;
    }

    function matchesFilters(task) {
      var f = ui.filters;

      if (ui.search) {
        var needle = ui.search.toLowerCase();
        var haystack = [task.title, task.description || '', task.code || '', task.status || ''];
        (task.labels || []).forEach(function (id) {
          var label = labelById(id);
          if (label) haystack.push(label.name);
        });
        (task.assignees || []).forEach(function (person) { haystack.push(person.nombre_completo); });
        if (haystack.join(' ').toLowerCase().indexOf(needle) === -1) return false;
      }

      if (f.priority.length && f.priority.indexOf(task.priority) === -1) return false;
      if (f.color.length && f.color.indexOf(task.color || '') === -1) return false;
      if (f.column.length && f.column.indexOf(task.column_id) === -1) return false;
      if (f.status.length && f.status.indexOf(task.status || '') === -1) return false;

      if (f.label.length) {
        var labels = task.labels || [];
        var hasLabel = f.label.some(function (id) { return labels.indexOf(id) !== -1; });
        if (!hasLabel) return false;
      }

      if (f.assignee.length) {
        var ids = (task.assignees || []).map(function (person) { return person.id; });
        // "sin asignar" viaja como 0 dentro del mismo filtro.
        var wantsUnassigned = f.assignee.indexOf(0) !== -1;
        var matches = f.assignee.some(function (id) { return id !== 0 && ids.indexOf(id) !== -1; });
        if (!(matches || (wantsUnassigned && ids.length === 0))) return false;
      }

      if (f.due === 'overdue' && !isOverdue(task)) return false;
      if (f.due === 'soon' && !isDueSoon(task)) return false;
      if (f.due === 'none' && task.due_date) return false;
      if (f.due === 'completed' && !task.is_completed) return false;
      if (f.due === 'pending' && task.is_completed) return false;

      return true;
    }

    function sortTasks(tasks) {
      var sorted = tasks.slice();
      switch (ui.sort) {
        case 'created': sorted.sort(function (a, b) { return a.id - b.id; }); break;
        case 'due': sorted.sort(function (a, b) {
          if (!a.due_date) return 1;
          if (!b.due_date) return -1;
          return a.due_date < b.due_date ? -1 : a.due_date > b.due_date ? 1 : 0;
        }); break;
        case 'priority': sorted.sort(function (a, b) {
          return PRIORITY_ORDER[a.priority] - PRIORITY_ORDER[b.priority];
        }); break;
        case 'name': sorted.sort(function (a, b) { return a.title.localeCompare(b.title, 'es'); }); break;
        case 'updated': sorted.sort(function (a, b) {
          return String(b.updated_at).localeCompare(String(a.updated_at));
        }); break;
        default: break; // manual: ya viene por position
      }
      return sorted;
    }

    /* ═════════════════════════════════════════════════════════════════
       Render — reconciliador con claves + FLIP
       ═════════════════════════════════════════════════════════════════ */

    var flipRects = null;

    function captureRects() {
      if (reduceMotion.matches) return null;
      var map = new Map();
      dom.board.querySelectorAll('[data-kb-col-id], [data-kb-task-id]').forEach(function (node) {
        map.set(node, node.getBoundingClientRect());
      });
      return map;
    }

    var FLIP_MS = 240;
    var FLIP_EASE = 'cubic-bezier(.2,.7,.2,1)';

    /* Anima desde la posición vieja hacia la nueva (First-Last-Invert-Play). */
    function playFlip(before) {
      if (!before) return;
      dom.board.querySelectorAll('[data-kb-col-id], [data-kb-task-id]').forEach(function (node) {
        var previous = before.get(node);
        if (!previous || node.classList.contains('is-ghosted')) return;
        var current = node.getBoundingClientRect();

        var dx = previous.left - current.left, dy = previous.top - current.top;
        if (Math.abs(dx) >= 1 || Math.abs(dy) >= 1) {
          node.animate(
            [{ transform: 'translate(' + dx + 'px,' + dy + 'px)' }, { transform: 'none' }],
            { duration: FLIP_MS, easing: FLIP_EASE }
          );
        }

        // La columna que recibe (o pierde) una tarjeta cambia de alto: sin
        // esto el estirón es un salto, porque una altura automática no
        // transiciona. Se anima entre las dos alturas medidas.
        if (node.classList.contains('kb-col') && Math.abs(previous.height - current.height) >= 1) {
          node.animate(
            [{ height: previous.height + 'px' }, { height: current.height + 'px' }],
            { duration: FLIP_MS, easing: FLIP_EASE }
          );
        }
      });
    }

    var renderQueued = false;

    function render() {
      if (dnd.active) return; // no se re-renderiza en medio de un arrastre
      if (renderQueued) return;
      renderQueued = true;
      schedule(function () {
        renderQueued = false;
        flipRects = captureRects();
        renderHeader();
        renderBoard();
        playFlip(flipRects);
      });
    }

    function renderHeader() {
      if (!state.board) return;
      var background = state.board.background_color || 'azul';
      root.setAttribute('data-kb-color', background);
      // También en la superficie gris: así --kb-c queda definida en el
      // propio elemento que la usa para el tinte, y no depende de la
      // herencia.
      dom.surface.setAttribute('data-kb-color', background);
      if (document.activeElement !== dom.title) {
        dom.title.value = state.board.name || '';
        autoSize(dom.title);
      }

      var count = activeFilterCount();
      var badge = dom.filtersBtn.querySelector('.kb-tool__badge');
      if (count) {
        if (!badge) {
          badge = el('span', 'kb-tool__badge');
          dom.filtersBtn.appendChild(badge);
        }
        badge.textContent = String(count);
      } else if (badge) {
        badge.remove();
      }
      dom.filtersBtn.classList.toggle('is-on', count > 0);
      dom.board.setAttribute('data-kb-sort', ui.sort);
    }

    function renderBoard() {
      dom.board.setAttribute('aria-busy', 'false');

      var existingColumns = new Map();
      dom.board.querySelectorAll('[data-kb-col-id]').forEach(function (node) {
        existingColumns.set(Number(node.getAttribute('data-kb-col-id')), node);
      });

      // Tablero sin columnas: estado vacío con llamada a la acción.
      if (!state.columns.length) {
        clear(dom.board);
        var empty = el('div', 'kb-board-empty');
        empty.appendChild(el('strong', null, 'Todavía no hay columnas'));
        empty.appendChild(el('p', null,
          'Creá la primera columna (por ejemplo «Pendiente») y empezá a cargar las tareas de mantenimiento de esta placa.'));
        var cta = el('button', 'btn btn--primary');
        cta.type = 'button';
        cta.appendChild(icon('plus'));
        cta.appendChild(document.createTextNode('Crear la primera columna'));
        cta.addEventListener('click', function () { startColumnCompose(); });
        empty.appendChild(cta);
        dom.board.appendChild(empty);
        dom.board.appendChild(buildAddColumn());
        return;
      }

      var order = [];
      state.columns.forEach(function (column) {
        var node = existingColumns.get(column.id);
        if (node) {
          existingColumns.delete(column.id);
          patchColumn(node, column);
        } else {
          node = buildColumn(column);
        }
        order.push(node);
      });

      existingColumns.forEach(function (node) { node.remove(); });
      // Además de las columnas viejas hay que barrer el esqueleto de carga
      // y los estados de error: si no, quedan pegados entre las columnas.
      dom.board.querySelectorAll('.kb-board-empty, .kb-add-col, .kb-skeleton, .kb-state')
        .forEach(function (node) { node.remove(); });

      order.forEach(function (node, index) {
        var current = dom.board.children[index];
        if (current !== node) dom.board.insertBefore(node, current || null);
      });

      dom.board.appendChild(buildAddColumn());
    }

    /* ── Columna ───────────────────────────────────────────────────── */

    function buildColumn(column) {
      var node = el('section', 'kb-col');
      node.setAttribute('data-kb-col-id', String(column.id));
      node.setAttribute('role', 'group');

      var head = el('header', 'kb-col__head');
      head.setAttribute('data-kb-col-handle', '');

      // Punto de color: la columna se identifica sin teñir toda la caja.
      var dot = el('span', 'kb-col__dot');
      dot.setAttribute('aria-hidden', 'true');

      var title = el('input', 'kb-col__title');
      title.type = 'text';
      title.setAttribute('data-kb-col-title', '');
      title.setAttribute('maxlength', '120');
      bindInlineEdit(title, function () {
        return (columnById(column.id) || {}).name || '';
      }, function (value) {
        return saveColumn(column.id, { name: value });
      }, 'El nombre de la columna no puede quedar vacío.');

      var count = el('span', 'kb-col__count');
      count.setAttribute('data-kb-col-count', '');

      // Zona libre para agarrar y arrastrar la columna.
      var spacer = el('span', 'kb-col__spacer');

      var menu = el('button', 'kb-icon-btn kb-col__menu-btn');
      menu.type = 'button';
      menu.setAttribute('aria-haspopup', 'menu');
      menu.setAttribute('aria-label', 'Opciones de la columna');
      menu.appendChild(icon('dots'));
      menu.addEventListener('click', function (event) {
        event.stopPropagation();
        openColumnMenu(menu, column.id);
      });

      head.appendChild(dot);
      head.appendChild(title);
      head.appendChild(count);
      head.appendChild(spacer);
      head.appendChild(menu);

      var desc = el('p', 'kb-col__desc');
      desc.setAttribute('data-kb-col-desc', '');

      var list = el('div', 'kb-col__list');
      list.setAttribute('data-kb-list', String(column.id));
      // Lenis maneja el scroll de la página: sin esto la rueda del mouse
      // desplaza el fondo en vez de la lista de tarjetas.
      list.setAttribute('data-lenis-prevent', '');

      var foot = el('div', 'kb-col__foot');
      var add = el('button', 'kb-add');
      add.type = 'button';
      add.setAttribute('data-kb-add-task', '');
      add.appendChild(icon('plus'));
      add.appendChild(document.createTextNode('Añadir una tarea'));
      add.addEventListener('click', function () { startTaskCompose(column.id); });
      foot.appendChild(add);

      node.appendChild(head);
      node.appendChild(desc);
      node.appendChild(list);
      node.appendChild(foot);

      patchColumn(node, column);
      return node;
    }

    function patchColumn(node, column) {
      node.setAttribute('data-kb-color', column.color || 'gris');

      var title = node.querySelector('[data-kb-col-title]');
      if (document.activeElement !== title) {
        title.value = column.name;
        autoSize(title);
      }
      title.setAttribute('aria-label', 'Nombre de la columna ' + column.name);

      var desc = node.querySelector('[data-kb-col-desc]');
      desc.textContent = column.description || '';
      desc.hidden = !column.description;

      var tasks = tasksOfColumn(column.id);
      var visible = sortTasks(tasks.filter(matchesFilters));

      var count = node.querySelector('[data-kb-col-count]');
      var overLimit = !!column.task_limit && tasks.length > column.task_limit;
      // Sobre el límite además del color va un signo, para no depender
      // solo del color al comunicar el aviso.
      count.textContent = (column.task_limit ? tasks.length + '/' + column.task_limit : '(' + tasks.length + ')')
        + (overLimit ? ' !' : '');
      node.classList.toggle('is-over-limit', overLimit);
      count.title = overLimit
        ? 'Superaste el límite de ' + column.task_limit + ' tareas de esta columna'
        : tasks.length + ' tarea' + (tasks.length === 1 ? '' : 's');
      count.setAttribute('aria-label', count.title);

      node.setAttribute('aria-label', column.name + ' · ' + count.title);

      var list = node.querySelector('[data-kb-list]');
      var existing = new Map();
      list.querySelectorAll('[data-kb-task-id]').forEach(function (card) {
        existing.set(Number(card.getAttribute('data-kb-task-id')), card);
      });

      var order = [];
      visible.forEach(function (task) {
        var card = existing.get(task.id);
        if (card) {
          existing.delete(task.id);
          patchCard(card, task);
        } else {
          card = buildCard(task);
        }
        order.push(card);
      });

      existing.forEach(function (card) { card.remove(); });
      list.querySelectorAll('.kb-empty').forEach(function (node) { node.remove(); });

      order.forEach(function (card, index) {
        var current = list.children[index];
        if (current !== card) list.insertBefore(card, current || null);
      });

      // Una columna vacía se deja vacía: alcanza con el botón de añadir.
      // Solo se avisa cuando el vacío lo produjo un filtro, para que no
      // parezca que se perdieron tareas.
      if (!order.length && tasks.length) {
        list.appendChild(el('p', 'kb-empty', 'Ninguna tarea coincide con la búsqueda'));
      }

      // La composición rápida vive al pie de la columna.
      var foot = node.querySelector('.kb-col__foot');
      if (ui.composingColumn === column.id) {
        if (!foot.querySelector('.kb-compose')) {
          clear(foot);
          foot.appendChild(buildTaskCompose(column.id));
        }
      } else if (foot.querySelector('.kb-compose')) {
        clear(foot);
        var add = el('button', 'kb-add');
        add.type = 'button';
        add.appendChild(icon('plus'));
        add.appendChild(document.createTextNode('Añadir una tarea'));
        add.addEventListener('click', function () { startTaskCompose(column.id); });
        foot.appendChild(add);
      }
    }

    /* ── Tarjeta ───────────────────────────────────────────────────── */

    function buildCard(task) {
      var card = el('article', 'kb-card');
      card.setAttribute('data-kb-task-id', String(task.id));
      card.tabIndex = 0;
      card.setAttribute('role', 'button');

      card.appendChild(el('div', 'kb-card__labels'));
      card.appendChild(el('div', 'kb-card__title'));
      card.appendChild(el('p', 'kb-card__desc'));
      card.appendChild(el('div', 'kb-card__badges'));

      var foot = el('div', 'kb-card__foot');
      foot.appendChild(el('div', 'kb-progress'));
      foot.appendChild(el('div', 'kb-avatars'));
      card.appendChild(foot);

      card.addEventListener('click', function (event) {
        if (dnd.moved) return; // fue un arrastre, no un click
        if (event.target.closest('button')) return;
        openTask(task.id);
      });

      card.addEventListener('keydown', function (event) { onCardKey(event, task.id); });

      card.addEventListener('contextmenu', function (event) {
        event.preventDefault();
        openTaskMenu({ getBoundingClientRect: function () {
          return { left: event.clientX, top: event.clientY, bottom: event.clientY, right: event.clientX, width: 0, height: 0 };
        } }, task.id);
      });

      patchCard(card, task);
      return card;
    }

    function patchCard(card, task) {
      if (task.color) card.setAttribute('data-kb-color', task.color);
      else card.removeAttribute('data-kb-color');

      card.classList.toggle('is-completed', !!task.is_completed);
      card.classList.toggle('is-selected', ui.selectedTaskId === task.id);

      /* Etiquetas */
      var labels = card.querySelector('.kb-card__labels');
      clear(labels);
      (task.labels || []).forEach(function (id) {
        var label = labelById(id);
        if (!label) return;
        var chip = el('span', 'kb-label');
        chip.setAttribute('data-kb-color', label.color);
        chip.appendChild(el('span', null, label.name));
        chip.title = label.name;
        labels.appendChild(chip);
      });
      labels.hidden = !labels.childNodes.length;

      /* Título y descripción */
      writeHighlighted(card.querySelector('.kb-card__title'), task.title, ui.search);
      var desc = card.querySelector('.kb-card__desc');
      writeHighlighted(desc, task.description || '', ui.search);
      desc.hidden = !task.description;

      /* Indicadores */
      // El identificador de la tarea (#12) no se muestra en la tarjeta:
      // vive en la vista de detalle y en la búsqueda.
      var badges = card.querySelector('.kb-card__badges');
      clear(badges);

      if (task.priority && task.priority !== 'media') {
        var priority = PRIORITIES[task.priority];
        var chip = el('span', 'kb-badge kb-badge--prio');
        chip.setAttribute('data-kb-color', priority.color);
        chip.appendChild(el('b', null, priority.glyph));
        chip.appendChild(document.createTextNode(priority.label));
        chip.title = 'Prioridad ' + priority.label.toLowerCase();
        badges.appendChild(chip);
      }

      if (task.is_completed) {
        var done = el('span', 'kb-badge kb-badge--done');
        done.appendChild(icon('check'));
        done.appendChild(document.createTextNode('Completada'));
        badges.appendChild(done);
      }

      if (task.due_date) {
        var due = el('span', 'kb-badge');
        if (isOverdue(task)) { due.classList.add('kb-badge--overdue'); due.title = 'Vencida'; }
        else if (isDueSoon(task)) { due.classList.add('kb-badge--due-soon'); due.title = 'Vence pronto'; }
        due.appendChild(icon('calendar'));
        due.appendChild(document.createTextNode(formatDate(task.due_date)));
        badges.appendChild(due);
      }

      if (task.comments) {
        var comments = el('span', 'kb-badge');
        comments.appendChild(icon('comment'));
        comments.appendChild(document.createTextNode(String(task.comments)));
        comments.title = task.comments + ' comentario' + (task.comments === 1 ? '' : 's');
        badges.appendChild(comments);
      }

      if (task.attachments) {
        var files = el('span', 'kb-badge');
        files.appendChild(icon('clip'));
        files.appendChild(document.createTextNode(String(task.attachments)));
        files.title = task.attachments + ' adjunto' + (task.attachments === 1 ? '' : 's');
        badges.appendChild(files);
      }

      if (task.estimated_minutes) {
        var time = el('span', 'kb-badge');
        time.appendChild(icon('clock'));
        time.appendChild(document.createTextNode(formatMinutes(task.estimated_minutes)));
        time.title = 'Tiempo estimado';
        badges.appendChild(time);
      }

      badges.hidden = !badges.childNodes.length;

      /* Avance de checklist */
      var progress = card.querySelector('.kb-progress');
      var checklist = task.checklist || { done: 0, total: 0 };
      clear(progress);
      if (checklist.total) {
        var pct = Math.round((checklist.done / checklist.total) * 100);
        var bar = el('i');
        bar.style.width = pct + '%';
        progress.appendChild(bar);
        progress.classList.toggle('is-full', pct === 100);
        progress.title = 'Checklist: ' + checklist.done + ' de ' + checklist.total;
        progress.hidden = false;
      } else {
        progress.hidden = true;
      }

      /* Responsables */
      var avatars = card.querySelector('.kb-avatars');
      clear(avatars);
      var people = task.assignees || [];
      people.slice(0, 3).forEach(function (person) {
        var avatar = el('span', 'kb-avatar', person.initials);
        avatar.title = person.nombre_completo;
        avatars.appendChild(avatar);
      });
      if (people.length > 3) {
        avatars.appendChild(el('span', 'kb-avatar kb-avatar--more', '+' + (people.length - 3)));
      }
      avatars.hidden = !people.length;

      card.querySelector('.kb-card__foot').hidden = progress.hidden && avatars.hidden;

      var summary = task.title + (task.code ? ' ' + task.code : '');
      card.setAttribute('aria-label', 'Tarea ' + summary + '. Enter para abrir, Espacio para seleccionar y mover con las flechas.');
    }

    /* ── Columna fantasma "añadir" ─────────────────────────────────── */

    function buildAddColumn() {
      var wrap = el('div', 'kb-add-col');

      if (ui.creatingColumn) {
        var form = el('div', 'kb-compose');
        var input = el('input', 'kb-field');
        input.type = 'text';
        input.placeholder = 'Nombre de la columna';
        input.setAttribute('maxlength', '120');
        input.setAttribute('aria-label', 'Nombre de la columna nueva');

        var row = el('div', 'kb-compose__row');
        var save = el('button', 'btn btn--primary btn--sm', 'Añadir');
        save.type = 'button';

        var submit = function () {
          var name = input.value.trim();
          if (!name) { input.focus(); return; }
          ui.creatingColumn = false;
          mutate('POST', '/columnas', { name: name }, null, { success: 'Columna creada' });
        };
        var close = function () {
          if (!ui.creatingColumn) return;
          ui.creatingColumn = false;
          render();
        };

        // El mousedown del botón no debe robarle el foco al campo: si no,
        // el focusout cancelaría el alta antes del click.
        save.addEventListener('mousedown', function (event) { event.preventDefault(); });
        save.addEventListener('click', submit);
        input.addEventListener('keydown', function (event) {
          if (event.key === 'Enter') { event.preventDefault(); submit(); }
          if (event.key === 'Escape') { event.preventDefault(); close(); }
        });
        cancelOnFocusOut(form, close);

        row.appendChild(save);
        form.appendChild(input);
        form.appendChild(row);
        wrap.appendChild(form);
        window.setTimeout(function () { input.focus(); }, 20);
      } else {
        var add = el('button', 'kb-add');
        add.type = 'button';
        add.appendChild(icon('plus'));
        add.appendChild(document.createTextNode('Añadir otra columna'));
        add.addEventListener('click', startColumnCompose);
        wrap.appendChild(add);
      }

      return wrap;
    }

    function startColumnCompose() {
      ui.creatingColumn = true;
      ui.composingColumn = null;
      render();
    }

    /* ── Alta rápida de tarea ──────────────────────────────────────── */

    function buildTaskCompose(columnId) {
      var form = el('div', 'kb-compose');

      // Arranca en una línea y crece con el texto (sin manija de resize).
      var input = el('textarea', 'kb-field');
      input.rows = 1;
      input.placeholder = 'Título de la tarea…';
      input.setAttribute('maxlength', '200');
      input.setAttribute('aria-label', 'Título de la tarea nueva. Enter para guardar, Shift+Enter para saltar de línea.');

      var row = el('div', 'kb-compose__row');
      var save = el('button', 'btn btn--primary btn--sm', 'Añadir tarea');
      save.type = 'button';

      var submit = function () {
        var title = input.value.trim();
        if (!title) { input.focus(); return; }
        input.value = '';
        autoSize(input);
        mutate('POST', '/tareas', { column_id: columnId, title: title }, null, { success: 'Tarea creada' })
          .then(function () {
            // Se deja el campo abierto para cargar varias tareas seguidas.
            window.setTimeout(function () {
              var next = dom.board.querySelector('[data-kb-col-id="' + columnId + '"] .kb-compose textarea');
              if (next) next.focus();
            }, 30);
          });
      };
      var close = function () {
        if (ui.composingColumn !== columnId) return;
        ui.composingColumn = null;
        render();
      };

      // El mousedown del botón no debe robarle el foco al campo: si no,
      // el focusout cancelaría el alta antes del click.
      save.addEventListener('mousedown', function (event) { event.preventDefault(); });
      save.addEventListener('click', submit);
      input.addEventListener('input', function () { autoSize(input); });
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); submit(); }
        if (event.key === 'Escape') { event.preventDefault(); close(); }
      });
      cancelOnFocusOut(form, close);

      row.appendChild(save);
      form.appendChild(input);
      form.appendChild(row);

      window.setTimeout(function () { input.focus(); autoSize(input); }, 20);
      return form;
    }

    function startTaskCompose(columnId) {
      ui.creatingColumn = false;
      ui.composingColumn = columnId;
      render();
    }

    /* ── Edición inline reutilizable ──────────────────────────────────
       El campo se ve como texto y solo entra en modo edición con doble
       clic (o Enter/F2 si se llegó por teclado), para que no se edite
       nada sin querer al pasar por arriba. Enter guarda, Escape cancela.
       ───────────────────────────────────────────────────────────────── */

    function bindInlineEdit(input, getValue, save, emptyMessage) {
      var original = null;

      input.classList.add('kb-inline');
      input.readOnly = true;
      input.title = 'Doble clic para editar';
      autoSize(input);

      function beginEdit() {
        if (!input.readOnly) return;
        original = input.value;
        input.readOnly = false;
        input.focus();
        input.select();
        autoSize(input);
      }

      function endEdit() {
        input.readOnly = true;
        autoSize(input);
      }

      input.addEventListener('dblclick', beginEdit);

      input.addEventListener('keydown', function (event) {
        event.stopPropagation();

        if (input.readOnly) {
          if (event.key === 'Enter' || event.key === 'F2') {
            event.preventDefault();
            beginEdit();
          }
          return;
        }

        if (event.key === 'Enter' && input.tagName !== 'TEXTAREA') {
          event.preventDefault();
          input.blur();
        }
        if (event.key === 'Escape') {
          event.preventDefault();
          input.value = original !== null ? original : getValue();
          input.blur();
        }
      });

      input.addEventListener('input', function () { autoSize(input); });

      input.addEventListener('blur', function () {
        var value = input.value.trim();
        var previous = original !== null ? original : getValue();
        original = null;
        endEdit();

        if (value === String(previous || '').trim()) { input.value = previous || ''; autoSize(input); return; }

        if (!value && emptyMessage) {
          // Título vacío: se restaura el anterior y se avisa.
          input.value = previous || '';
          autoSize(input);
          toast(emptyMessage, 'error');
          return;
        }

        save(value);
      });
    }

    /**
     * Cierra un formulario efímero (alta rápida) cuando el foco se va
     * afuera. Confirmar con el botón no lo dispara porque el botón queda
     * dentro del propio formulario.
     */
    function cancelOnFocusOut(form, close) {
      form.addEventListener('focusout', function (event) {
        if (event.relatedTarget && form.contains(event.relatedTarget)) return;
        // Sin relatedTarget (click en zona sin foco) hay que confirmar en
        // el próximo tick que el foco realmente salió del formulario.
        window.setTimeout(function () {
          if (!form.isConnected) return;
          if (form.contains(document.activeElement)) return;
          close();
        }, 0);
      });
    }

    /* ═════════════════════════════════════════════════════════════════
       Acciones sobre columnas
       ═════════════════════════════════════════════════════════════════ */

    function saveColumn(columnId, changes, options) {
      return mutate('POST', '/columnas/' + columnId, changes, function () {
        var column = columnById(columnId);
        if (column) Object.keys(changes).forEach(function (key) { column[key] = changes[key]; });
      }, options || {});
    }

    function columnAction(columnId, action, options) {
      return mutate('POST', '/columnas/' + columnId + '/accion', { action: action }, null, options || {});
    }

    /* ═════════════════════════════════════════════════════════════════
       Menús contextuales
       ═════════════════════════════════════════════════════════════════ */

    var openMenu = null;
    var openMenuAnchor = null;
    var openMenuAt = 0;

    function closeMenu() {
      if (!openMenu) return;
      var node = openMenu;
      openMenu = null;
      openMenuAnchor = null;
      node.classList.add('is-closing');
      window.setTimeout(function () { node.remove(); }, reduceMotion.matches ? 0 : 140);
    }

    /* Coloca el menú junto al disparador y lo mantiene dentro de la
       pantalla (importante en móvil). */
    function showMenu(anchor, build) {
      // Segundo clic sobre el mismo disparador: cierra, no reabre.
      if (openMenu && openMenuAnchor === anchor) { closeMenu(); return null; }

      closeMenu();

      var menu = el('div', 'kb-menu');
      menu.setAttribute('role', 'menu');
      // Sin esto Lenis se queda con la rueda del mouse y scrollea la
      // página de fondo en vez del menú desplegado.
      menu.setAttribute('data-lenis-prevent', '');
      build(menu);
      document.body.appendChild(menu);
      openMenu = menu;
      openMenuAnchor = anchor;
      // Marca de tiempo del alta: el cierre por clic afuera descarta con
      // esto el mismísimo clic que abrió el menú (ver más abajo).
      openMenuAt = window.performance ? window.performance.now() : Date.now();

      // Se usa el viewport de layout (clientWidth/Height) y no innerWidth:
      // con zoom o en móvil no coinciden y el menú se saldría de pantalla.
      var viewW = document.documentElement.clientWidth;
      var viewH = document.documentElement.clientHeight;

      var rect = anchor.getBoundingClientRect();
      var width = menu.offsetWidth, height = menu.offsetHeight;
      var left = Math.min(rect.left, viewW - width - 10);
      var top = rect.bottom + 6;
      if (top + height > viewH - 10) top = Math.max(10, rect.top - height - 6);
      menu.style.left = Math.max(10, left) + 'px';
      menu.style.top = top + 'px';

      var first = menu.querySelector('.kb-menu__item, .kb-swatch');
      if (first) first.focus();
      return menu;
    }

    function menuItem(menu, label, iconName, onClick, danger) {
      var item = el('button', 'kb-menu__item' + (danger ? ' kb-menu__item--danger' : ''));
      item.type = 'button';
      item.setAttribute('role', 'menuitem');
      if (iconName) item.appendChild(icon(iconName));
      item.appendChild(el('span', null, label));
      item.addEventListener('click', function () { closeMenu(); onClick(); });
      menu.appendChild(item);
      return item;
    }

    function menuSeparator(menu) { menu.appendChild(el('div', 'kb-menu__sep')); }
    function menuLabel(menu, text) { menu.appendChild(el('div', 'kb-menu__label', text)); }

    function colorSwatches(menu, current, onPick, allowNone) {
      var grid = el('div', 'kb-swatches');
      if (allowNone) {
        var none = el('button', 'kb-swatch kb-swatch--none' + (!current ? ' is-on' : ''));
        none.type = 'button';
        none.title = 'Sin color';
        none.setAttribute('aria-label', 'Sin color');
        none.addEventListener('click', function () { closeMenu(); onPick(''); });
        grid.appendChild(none);
      }
      (state.colors || []).forEach(function (color) {
        var swatch = el('button', 'kb-swatch' + (current === color ? ' is-on' : ''));
        swatch.type = 'button';
        swatch.setAttribute('data-kb-color', color);
        swatch.title = COLOR_NAMES[color] || color;
        swatch.setAttribute('aria-label', COLOR_NAMES[color] || color);
        swatch.addEventListener('click', function () { closeMenu(); onPick(color); });
        grid.appendChild(swatch);
      });
      menu.appendChild(grid);
    }

    function openColumnMenu(anchor, columnId) {
      var column = columnById(columnId);
      if (!column) return;

      showMenu(anchor, function (menu) {
        menuItem(menu, 'Renombrar', 'note', function () {
          var input = dom.board.querySelector('[data-kb-col-id="' + columnId + '"] [data-kb-col-title]');
          if (input) { input.focus(); input.select(); }
        });
        menuItem(menu, 'Añadir tarea', 'plus', function () { startTaskCompose(columnId); });

        menuSeparator(menu);
        menuLabel(menu, 'Color de la columna');
        colorSwatches(menu, column.color, function (color) {
          saveColumn(columnId, { color: color });
        }, false);

        menuSeparator(menu);
        menuItem(menu, 'Descripción y ajustes', 'list', function () { openColumnSettings(columnId); });
        menuItem(menu, 'Mover columna', 'move', function () { openColumnMoveMenu(anchor, columnId); });
        menuItem(menu, 'Duplicar columna', 'copy', function () {
          columnAction(columnId, 'duplicate', { success: 'Columna duplicada' });
        });

        menuSeparator(menu);
        menuItem(menu, 'Vaciar columna', 'broom', function () {
          confirmAction('Vaciar «' + column.name + '»',
            'Sus tareas se archivan (podés restaurarlas desde Archivadas). ¿Seguimos?',
            'Vaciar', function () {
              columnAction(columnId, 'empty', { success: 'Columna vaciada' });
            });
        });
        menuItem(menu, 'Archivar columna', 'archive', function () {
          columnAction(columnId, 'archive', {
            success: 'Columna archivada',
            undo: { label: 'Deshacer', onClick: function () { columnAction(columnId, 'restore'); } }
          });
        });
        menuItem(menu, 'Eliminar columna', 'trash', function () {
          confirmAction('Eliminar «' + column.name + '»',
            'Se borran definitivamente la columna, sus tareas y todo lo que cuelga de ellas. Esta acción no se puede deshacer.',
            'Eliminar', function () {
              columnAction(columnId, 'delete', { success: 'Columna eliminada' });
            }, true);
        }, true);
      });
    }

    function openColumnMoveMenu(anchor, columnId) {
      showMenu(anchor, function (menu) {
        menuLabel(menu, 'Mover a la posición');
        state.columns.forEach(function (column, index) {
          var label = (index + 1) + '. ' + (column.id === columnId ? '(acá está)' : 'antes de ' + column.name);
          menuItem(menu, label, null, function () {
            var order = state.columns.map(function (item) { return item.id; })
              .filter(function (id) { return id !== columnId; });
            order.splice(index, 0, columnId);
            reorderColumns(order);
          });
        });
        menuItem(menu, 'Al final', null, function () {
          var order = state.columns.map(function (item) { return item.id; })
            .filter(function (id) { return id !== columnId; });
          order.push(columnId);
          reorderColumns(order);
        });
      });
    }

    /* Ajustes de columna que no entran cómodos en el menú. */
    function openColumnSettings(columnId) {
      var column = columnById(columnId);
      if (!column) return;

      openDialog('Ajustes de «' + column.name + '»', function (body, close) {
        var form = el('div', 'kb-section');

        form.appendChild(el('h5', null, 'Descripción'));
        var desc = el('textarea', 'kb-field');
        desc.value = column.description || '';
        desc.setAttribute('maxlength', '255');
        desc.placeholder = 'Para qué sirve esta columna (opcional)';
        form.appendChild(desc);

        form.appendChild(el('h5', null, 'Estado del flujo asociado'));
        var status = el('input', 'kb-field');
        status.type = 'text';
        status.value = column.status_key || '';
        status.setAttribute('maxlength', '40');
        status.placeholder = 'Ej: en_progreso';
        form.appendChild(status);
        var statusHelp = el('p', 'kb-done__excerpt',
          'Las tareas que caen en esta columna adoptan este estado automáticamente.');
        statusHelp.style.whiteSpace = 'normal';
        form.appendChild(statusHelp);

        var finalRow = el('label', 'kb-check-item');
        var finalBox = el('input');
        finalBox.type = 'checkbox';
        finalBox.checked = !!column.is_final;
        finalRow.appendChild(finalBox);
        finalRow.appendChild(el('span', 'kb-check-item__text',
          'Es una columna final: las tareas que llegan acá se marcan como completadas'));
        form.appendChild(finalRow);

        form.appendChild(el('h5', null, 'Límite de tareas'));
        var limit = el('input', 'kb-field');
        limit.type = 'number';
        limit.min = '1';
        limit.max = '999';
        limit.value = column.task_limit || '';
        limit.placeholder = 'Sin límite';
        form.appendChild(limit);

        var actions = el('div', 'kb-compose__row');
        var save = el('button', 'btn btn--primary btn--sm', 'Guardar');
        save.type = 'button';
        save.addEventListener('click', function () {
          saveColumn(columnId, {
            description: desc.value.trim(),
            status_key: status.value.trim(),
            is_final: finalBox.checked,
            task_limit: limit.value === '' ? null : Number(limit.value)
          }, { success: 'Columna actualizada' });
          close();
        });
        var cancel = el('button', 'btn btn--secondary btn--sm', 'Cancelar');
        cancel.type = 'button';
        cancel.addEventListener('click', close);
        actions.appendChild(save);
        actions.appendChild(cancel);
        form.appendChild(actions);

        body.appendChild(form);
      });
    }

    function reorderColumns(order) {
      mutate('POST', '/columnas/orden', { order: order }, function () {
        state.columns.sort(function (a, b) { return order.indexOf(a.id) - order.indexOf(b.id); });
        state.columns.forEach(function (column, index) { column.position = index; });
      }, {});
    }

    /* ── Menú de tarea ─────────────────────────────────────────────── */

    function openTaskMenu(anchor, taskId) {
      var task = taskById(taskId);
      if (!task) return;

      showMenu(anchor, function (menu) {
        menuItem(menu, 'Abrir', 'list', function () { openTask(taskId); });
        menuItem(menu, 'Editar título', 'note', function () { openTask(taskId, 'title'); });
        menuItem(menu, 'Mover a…', 'move', function () { openTaskMoveMenu(anchor, taskId); });
        menuItem(menu, 'Duplicar', 'copy', function () {
          taskAction(taskId, 'duplicate', { success: 'Tarea duplicada' });
        });

        menuSeparator(menu);
        menuLabel(menu, 'Prioridad');
        Object.keys(PRIORITIES).forEach(function (key) {
          var item = menuItem(menu, PRIORITIES[key].glyph + '  ' + PRIORITIES[key].label, null, function () {
            saveTask(taskId, { priority: key });
          });
          if (task.priority === key) item.appendChild(el('span', 'kb-picker__check', '✓'));
        });

        menuSeparator(menu);
        menuLabel(menu, 'Color de la tarea');
        colorSwatches(menu, task.color, function (color) { saveTask(taskId, { color: color }); }, true);

        menuSeparator(menu);
        menuItem(menu, task.is_completed ? 'Reabrir tarea' : 'Marcar como completada', 'check', function () {
          taskAction(taskId, task.is_completed ? 'reopen' : 'complete', {
            success: task.is_completed ? 'Tarea reabierta' : 'Tarea completada'
          });
        });
        menuItem(menu, 'Archivar', 'archive', function () {
          taskAction(taskId, 'archive', {
            success: 'Tarea archivada',
            undo: { label: 'Deshacer', onClick: function () { taskAction(taskId, 'restore'); } }
          });
        });
        menuItem(menu, 'Eliminar', 'trash', function () {
          confirmAction('Eliminar «' + task.title + '»',
            'Se borra definitivamente, junto a sus comentarios, checklists y adjuntos. No se puede deshacer.',
            'Eliminar', function () {
              taskAction(taskId, 'delete', { success: 'Tarea eliminada', after: closeTask });
            }, true);
        }, true);
      });
    }

    /* Mover por menú: es la alternativa al arrastre en móvil. */
    function openTaskMoveMenu(anchor, taskId) {
      var task = taskById(taskId);
      if (!task) return;

      showMenu(anchor, function (menu) {
        menuLabel(menu, 'Mover a la columna');
        state.columns.forEach(function (column) {
          var item = menuItem(menu, column.name, null, function () {
            moveTask(taskId, column.id, tasksOfColumn(column.id).length);
          });
          if (column.id === task.column_id) item.appendChild(el('span', 'kb-picker__check', '✓'));
        });
      });
    }

    /* Cierre por clic afuera.

       El menú se cuelga de <body>, así que el clic del propio disparador
       sigue burbujeando hasta acá con un target que NO está dentro del
       menú recién creado: sin la guarda de tiempo, el mismo clic que abría
       "Ordenar" (o "Apariencia") lo cerraba en el acto y parecía que el
       desplegable parpadeaba. Los eventos anteriores a la apertura se
       descartan; los siguientes sí cierran. */
    document.addEventListener('click', function (event) {
      if (!openMenu || openMenu.contains(event.target)) return;
      if (event.timeStamp && event.timeStamp <= openMenuAt) return;
      closeMenu();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && openMenu) { closeMenu(); }
    });
    window.addEventListener('resize', closeMenu);

    /* ═════════════════════════════════════════════════════════════════
       Acciones sobre tareas
       ═════════════════════════════════════════════════════════════════ */

    function saveTask(taskId, changes, options) {
      return mutate('POST', '/tareas/' + taskId, changes, function () {
        var task = taskById(taskId);
        if (task) Object.keys(changes).forEach(function (key) { task[key] = changes[key]; });
        if (ui.detail && ui.detail.id === taskId) {
          Object.keys(changes).forEach(function (key) { ui.detail[key] = changes[key]; });
        }
      }, options || {});
    }

    function taskAction(taskId, action, options) {
      return mutate('POST', '/tareas/' + taskId + '/accion', { action: action }, null, options || {});
    }

    function moveTask(taskId, columnId, position, options) {
      var task = taskById(taskId);
      if (!task) return Promise.resolve();

      var from = { column_id: task.column_id, position: task.position };
      var origin = columnById(from.column_id);

      return mutate('POST', '/tareas/' + taskId + '/mover', { column_id: columnId, position: position }, function () {
        var moving = taskById(taskId);
        if (!moving) return;
        // Reacomodo local para que el movimiento se vea al instante.
        tasksOfColumn(columnId).forEach(function (other) {
          if (other.id !== taskId && other.position >= position) other.position += 1;
        });
        moving.column_id = columnId;
        moving.position = position;
      }, Object.assign({
        undo: origin && origin.id !== columnId ? {
          label: 'Deshacer',
          onClick: function () { moveTask(taskId, from.column_id, from.position); }
        } : null
      }, options || {}));
    }

    /* ═════════════════════════════════════════════════════════════════
       Drag and drop
       ═════════════════════════════════════════════════════════════════ */

    var dnd = {
      active: false, moved: false, kind: null, node: null, ghost: null,
      placeholder: null, offsetX: 0, offsetY: 0, startX: 0, startY: 0,
      pointerId: null, holdTimer: null, listId: null, scrollTimer: null
    };

    var DRAG_THRESHOLD = 6;
    var TOUCH_HOLD_MS = 220;

    dom.board.addEventListener('pointerdown', function (event) {
      if (event.button !== undefined && event.button !== 0) return;
      // Un orden automático manda sobre el manual: no dejamos arrastrar.
      if (ui.sort !== 'manual') return;

      // Los controles se quedan con el evento, salvo los campos que todavía
      // están en modo lectura: desde ahí sí se puede arrastrar la columna.
      var control = event.target.closest('button, input, textarea, a, select');
      if (control && !(control.tagName === 'INPUT' && control.readOnly)) return;

      var card = event.target.closest('.kb-card');
      var handle = event.target.closest('[data-kb-col-handle]');

      if (card) prepareDrag(event, 'task', card);
      else if (handle) prepareDrag(event, 'column', handle.closest('.kb-col'));
    });

    function prepareDrag(event, kind, node) {
      if (!node || dnd.active) return;

      dnd.kind = kind;
      dnd.node = node;
      dnd.startX = event.clientX;
      dnd.startY = event.clientY;
      dnd.pointerId = event.pointerId;
      dnd.moved = false;

      var start = function () { beginDrag(event); };

      if (event.pointerType === 'touch') {
        // En táctil se exige una pulsación sostenida para no robar el scroll.
        dnd.holdTimer = window.setTimeout(start, TOUCH_HOLD_MS);
      } else {
        dnd.pendingStart = start;
      }

      window.addEventListener('pointermove', onPointerMove, { passive: false });
      window.addEventListener('pointerup', onPointerUp);
      window.addEventListener('pointercancel', onPointerUp);
    }

    function beginDrag(event) {
      var node = dnd.node;
      if (!node) return;

      var rect = node.getBoundingClientRect();
      dnd.active = true;
      dnd.offsetX = dnd.startX - rect.left;
      dnd.offsetY = dnd.startY - rect.top;

      var ghost = node.cloneNode(true);
      ghost.classList.add('kb-drag-ghost');
      if (dnd.kind === 'column') ghost.classList.add('kb-drag-ghost--col');
      ghost.classList.remove('is-ghosted', 'is-selected');
      ghost.style.width = rect.width + 'px';
      ghost.style.height = rect.height + 'px';
      ghost.setAttribute('aria-hidden', 'true');
      document.body.appendChild(ghost);
      dnd.ghost = ghost;

      var placeholder = el('div', 'kb-placeholder' + (dnd.kind === 'column' ? ' kb-placeholder--col' : ''));
      placeholder.style.width = rect.width + 'px';
      placeholder.style.height = rect.height + 'px';
      if (dnd.kind === 'column') placeholder.style.flex = '0 0 ' + rect.width + 'px';
      node.parentNode.insertBefore(placeholder, node);
      dnd.placeholder = placeholder;

      node.classList.add('is-ghosted');
      document.body.classList.add('kb-dragging');
      dom.board.classList.add('is-dragging');
      closeMenu();

      moveGhost(dnd.startX, dnd.startY, true);
    }

    function moveGhost(x, y, instant) {
      if (!dnd.ghost) return;
      var tilt = dnd.kind === 'task' ? 'rotate(1.6deg)' : 'rotate(.8deg)';
      dnd.ghost.style.transform =
        'translate(' + (x - dnd.offsetX) + 'px,' + (y - dnd.offsetY) + 'px) scale(1.02) ' + (instant ? 'rotate(0deg)' : tilt);
    }

    function onPointerMove(event) {
      if (!dnd.node) return;

      var dx = event.clientX - dnd.startX, dy = event.clientY - dnd.startY;

      if (!dnd.active) {
        if (Math.abs(dx) < DRAG_THRESHOLD && Math.abs(dy) < DRAG_THRESHOLD) return;
        if (dnd.holdTimer) {
          // El dedo se movió antes del hold: era un scroll, no un arrastre.
          window.clearTimeout(dnd.holdTimer);
          dnd.holdTimer = null;
          cleanupDrag();
          return;
        }
        if (dnd.pendingStart) { dnd.pendingStart(); dnd.pendingStart = null; }
        if (!dnd.active) return;
      }

      event.preventDefault();
      dnd.moved = true;
      moveGhost(event.clientX, event.clientY, false);

      if (dnd.kind === 'task') updateTaskDrop(event.clientX, event.clientY);
      else updateColumnDrop(event.clientX);

      autoScroll(event.clientX, event.clientY);
    }

    /* Reubica el hueco animando a los hermanos con FLIP. */
    function relocatePlaceholder(parent, reference) {
      if (dnd.placeholder.parentNode === parent && dnd.placeholder.nextSibling === reference) return;

      var before = captureRects();
      parent.insertBefore(dnd.placeholder, reference);
      playFlip(before);
    }

    function updateTaskDrop(x, y) {
      var under = document.elementFromPoint(x, y);
      if (!under) return;

      var list = under.closest('[data-kb-list]');
      if (!list) {
        var column = under.closest('.kb-col');
        if (column) list = column.querySelector('[data-kb-list]');
      }
      if (!list) return;

      var cards = Array.prototype.filter.call(list.children, function (child) {
        return child.classList.contains('kb-card') && !child.classList.contains('is-ghosted');
      });

      var reference = null;
      for (var i = 0; i < cards.length; i++) {
        var rect = cards[i].getBoundingClientRect();
        if (y < rect.top + rect.height / 2) { reference = cards[i]; break; }
      }

      relocatePlaceholder(list, reference);
      dnd.listId = Number(list.getAttribute('data-kb-list'));
    }

    function updateColumnDrop(x) {
      var columns = Array.prototype.filter.call(dom.board.children, function (child) {
        return child.classList.contains('kb-col') && !child.classList.contains('is-ghosted');
      });

      var reference = null;
      for (var i = 0; i < columns.length; i++) {
        var rect = columns[i].getBoundingClientRect();
        if (x < rect.left + rect.width / 2) { reference = columns[i]; break; }
      }
      if (!reference) {
        var addCol = dom.board.querySelector('.kb-add-col');
        reference = addCol || null;
      }

      relocatePlaceholder(dom.board, reference);
    }

    /* Auto-scroll cuando el puntero llega a los bordes. */
    function autoScroll(x, y) {
      var rect = dom.board.getBoundingClientRect();
      var edge = 64, speed = 0;

      if (x < rect.left + edge) speed = -14;
      else if (x > rect.right - edge) speed = 14;
      if (speed) dom.board.scrollLeft += speed;

      if (dnd.kind === 'task' && dnd.placeholder) {
        var list = dnd.placeholder.parentNode;
        if (list && list.classList.contains('kb-col__list')) {
          var listRect = list.getBoundingClientRect();
          if (y < listRect.top + 34) list.scrollTop -= 12;
          else if (y > listRect.bottom - 34) list.scrollTop += 12;
        }
      }
    }

    function onPointerUp() {
      if (dnd.holdTimer) { window.clearTimeout(dnd.holdTimer); dnd.holdTimer = null; }

      if (!dnd.active) { cleanupDrag(); return; }

      var placeholder = dnd.placeholder;
      var node = dnd.node;
      var ghost = dnd.ghost;
      var kind = dnd.kind;
      var target = placeholder.getBoundingClientRect();

      // Aterrizaje: el clon se desliza hasta el hueco y recién ahí aparece
      // el elemento real en su nueva posición.
      var finish = function () {
        if (ghost) ghost.remove();
        placeholder.parentNode.insertBefore(node, placeholder);
        placeholder.remove();
        node.classList.remove('is-ghosted');
        document.body.classList.remove('kb-dragging');
        dom.board.classList.remove('is-dragging');

        var wasActive = dnd.active;
        dnd.active = false;

        if (wasActive) persistDrag(kind, node);
        // Se limpia moved en el próximo tick para que el click posterior
        // al arrastre no abra la tarea.
        window.setTimeout(function () { dnd.moved = false; }, 0);
      };

      if (ghost && !reduceMotion.matches) {
        ghost.classList.add('is-dropping');
        ghost.style.transform = 'translate(' + target.left + 'px,' + target.top + 'px) scale(1)';
        var done = false;
        ghost.addEventListener('transitionend', function () { if (!done) { done = true; finish(); } }, { once: true });
        window.setTimeout(function () { if (!done) { done = true; finish(); } }, 260);
      } else {
        finish();
      }

      cleanupListeners();
    }

    function persistDrag(kind, node) {
      if (kind === 'column') {
        var order = Array.prototype.filter.call(dom.board.children, function (child) {
          return child.classList.contains('kb-col');
        }).map(function (child) { return Number(child.getAttribute('data-kb-col-id')); });

        var current = state.columns.map(function (column) { return column.id; });
        if (order.join(',') === current.join(',')) return;
        reorderColumns(order);
        return;
      }

      var taskId = Number(node.getAttribute('data-kb-task-id'));
      var list = node.closest('[data-kb-list]');
      if (!list) return;

      var columnId = Number(list.getAttribute('data-kb-list'));
      var siblings = Array.prototype.filter.call(list.children, function (child) {
        return child.classList.contains('kb-card');
      });
      var position = siblings.indexOf(node);
      var task = taskById(taskId);

      if (task && task.column_id === columnId && task.position === position) return;
      moveTask(taskId, columnId, position);
    }

    function cleanupListeners() {
      window.removeEventListener('pointermove', onPointerMove);
      window.removeEventListener('pointerup', onPointerUp);
      window.removeEventListener('pointercancel', onPointerUp);
      dnd.node = null;
      dnd.pendingStart = null;
    }

    function cleanupDrag() {
      cleanupListeners();
      dnd.active = false;
      dnd.kind = null;
    }

    /* ═════════════════════════════════════════════════════════════════
       Teclado: seleccionar y mover sin mouse
       ═════════════════════════════════════════════════════════════════ */

    function onCardKey(event, taskId) {
      var task = taskById(taskId);
      if (!task) return;

      if (event.key === 'Enter') { event.preventDefault(); openTask(taskId); return; }

      if (event.key === ' ' || event.key === 'Spacebar') {
        event.preventDefault();
        ui.selectedTaskId = ui.selectedTaskId === taskId ? null : taskId;
        render();
        toast(ui.selectedTaskId
          ? 'Tarea seleccionada: movela con las flechas, Escape para soltar.'
          : 'Tarea liberada.', 'success');
        return;
      }

      if (event.key === 'Escape' && ui.selectedTaskId === taskId) {
        event.preventDefault();
        ui.selectedTaskId = null;
        render();
        return;
      }

      if (event.key === 'Delete') {
        event.preventDefault();
        confirmAction('Eliminar «' + task.title + '»',
          'Se borra definitivamente. No se puede deshacer.', 'Eliminar', function () {
            taskAction(taskId, 'delete', { success: 'Tarea eliminada' });
          }, true);
        return;
      }

      if (ui.selectedTaskId !== taskId) return;
      if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(event.key) === -1) return;

      event.preventDefault();
      var siblings = tasksOfColumn(task.column_id);
      var index = siblings.findIndex(function (item) { return item.id === taskId; });

      if (event.key === 'ArrowUp' && index > 0) {
        moveTask(taskId, task.column_id, index - 1);
      } else if (event.key === 'ArrowDown' && index < siblings.length - 1) {
        moveTask(taskId, task.column_id, index + 1);
      } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        var columnIndex = state.columns.findIndex(function (column) { return column.id === task.column_id; });
        var nextIndex = columnIndex + (event.key === 'ArrowLeft' ? -1 : 1);
        if (nextIndex < 0 || nextIndex >= state.columns.length) return;
        var target = state.columns[nextIndex];
        moveTask(taskId, target.id, tasksOfColumn(target.id).length);
      }

      // Devolvemos el foco a la tarjeta después del re-render.
      window.setTimeout(function () {
        var card = dom.board.querySelector('[data-kb-task-id="' + taskId + '"]');
        if (card) card.focus();
      }, 60);
    }

    /* ═════════════════════════════════════════════════════════════════
       Diálogos genéricos (confirmación y formularios chicos)
       ═════════════════════════════════════════════════════════════════ */

    var pendingConfirm = null;

    function confirmAction(title, message, acceptLabel, onAccept, danger) {
      pendingConfirm = onAccept;
      dom.confirm.querySelector('[data-kb-confirm-title]').textContent = title;
      dom.confirmText.textContent = message;
      dom.confirmAccept.hidden = false;
      dom.confirmAccept.textContent = acceptLabel || 'Confirmar';
      dom.confirmAccept.className = 'btn ' + (danger ? 'btn--danger' : 'btn--primary');
      openOverlay(dom.confirm);
    }

    dom.confirmAccept.addEventListener('click', function () {
      var accept = pendingConfirm;
      pendingConfirm = null;
      closeOverlay(dom.confirm);
      if (accept) accept();
    });

    dom.confirm.querySelectorAll('[data-kb-dismiss]').forEach(function (node) {
      node.addEventListener('click', function () {
        pendingConfirm = null;
        closeOverlay(dom.confirm);
      });
    });

    /* Diálogo chico reutilizando el mismo modal de confirmación. */
    function openDialog(title, build) {
      dom.confirm.querySelector('[data-kb-confirm-title]').textContent = title;
      var body = dom.confirmText;
      clear(body);
      dom.confirmAccept.hidden = true;
      build(body, function () {
        dom.confirmAccept.hidden = false;
        closeOverlay(dom.confirm);
      });
      openOverlay(dom.confirm);
    }

    function openOverlay(modal) {
      if (window.PrevcsModal) window.PrevcsModal.open(modal);
      else modal.hidden = false;
    }

    function closeOverlay(modal) {
      if (modal === dom.confirm) dom.confirmAccept.hidden = false;
      if (window.PrevcsModal) window.PrevcsModal.close(modal);
      else modal.hidden = true;
    }

    /* ═════════════════════════════════════════════════════════════════
       Modal de detalle de tarea
       ═════════════════════════════════════════════════════════════════ */

    function openTask(taskId, focusField) {
      ui.openTaskId = taskId;
      ui.detail = null;
      clear(dom.modalBody);
      dom.modalBody.scrollTop = 0;
      resyncModalScroll();

      var loading = el('div', 'kb-state');
      loading.appendChild(el('p', null, 'Cargando la tarea…'));
      dom.modalBody.appendChild(loading);
      openOverlay(dom.modal);

      request('GET', '/tareas/' + taskId).then(function (data) {
        if (ui.openTaskId !== taskId) return;
        ui.detail = data.task;
        renderDetail();
        if (focusField === 'title') {
          var title = dom.modalBody.querySelector('.kb-detail__title');
          if (title) { title.focus(); title.select(); }
        }
      }).catch(function (error) {
        clear(dom.modalBody);
        var fail = el('div', 'kb-state');
        fail.appendChild(el('p', null, error.message || 'No se pudo abrir la tarea.'));
        var retry = el('button', 'btn btn--secondary btn--sm', 'Reintentar');
        retry.type = 'button';
        retry.addEventListener('click', function () { openTask(taskId, focusField); });
        fail.appendChild(retry);
        dom.modalBody.appendChild(fail);
      });
    }

    function closeTask() {
      ui.openTaskId = null;
      ui.detail = null;
      closeOverlay(dom.modal);
    }

    dom.modal.querySelectorAll('[data-kb-dismiss]').forEach(function (node) {
      node.addEventListener('click', closeTask);
    });

    /* Inercia en el scroll de las dos ventanas, igual que en la página. */
    var resyncModalScroll = bindSmoothScroll(dom.modalBody);
    bindSmoothScroll(dom.confirm.querySelector('.space-modal__body'));

    /* ═════════════════════════════════════════════════════════════════
       Adjuntos: selector de archivos, Ctrl+V y arrastrar sobre la ventana
       ═════════════════════════════════════════════════════════════════ */

    var MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

    /* Una imagen pegada del portapapeles llega sin nombre útil, y el
       servidor valida por extensión: se le arma uno a partir del tipo. */
    function attachmentName(file) {
      if (file.name && /\.[a-z0-9]{2,5}$/i.test(file.name)) return file.name;
      var byType = {
        'image/png': 'png', 'image/jpeg': 'jpg', 'image/webp': 'webp',
        'image/gif': 'gif', 'image/svg+xml': 'svg'
      };
      return 'captura-' + Date.now() + '.' + (byType[file.type] || 'png');
    }

    /**
     * Sube archivos a una tarea. Lo comparten el botón "Adjuntar archivo",
     * el pegado y el arrastre. Van de a uno porque cada POST renueva el
     * CSRF y dos en paralelo se pisarían.
     */
    function uploadFiles(taskId, files) {
      var queue = Array.prototype.filter.call(files || [], function (file) {
        if (file.size > MAX_UPLOAD_BYTES) {
          toast('«' + (file.name || 'el archivo') + '» supera los 5 MB.', 'error');
          return false;
        }
        return file.size > 0;
      });
      if (!queue.length) return;

      queue.reduce(function (chain, file) {
        return chain.then(function () {
          var form = new FormData();
          form.append('file', file, attachmentName(file));
          return request('POST', '/tareas/' + taskId + '/adjuntos', form, true).then(function (data) {
            applyPayload(data);
            if (ui.openTaskId === taskId) {
              ui.detail = data.task;
              renderDetail();
            }
            render();
          });
        });
      }, Promise.resolve()).then(function () {
        toast(queue.length === 1 ? 'Archivo adjuntado' : queue.length + ' archivos adjuntados', 'success');
      }).catch(function (error) {
        toast(error.message || 'No se pudo subir el archivo.', 'error');
      });
    }

    /* Ctrl+V con una captura en el portapapeles la adjunta a la tarea
       abierta. Si además viene texto y el foco está en un campo, gana el
       texto: ahí el pegado tiene que seguir siendo el de siempre. */
    dom.modal.addEventListener('paste', function (event) {
      if (!ui.openTaskId || dom.modal.hidden) return;

      var data = event.clipboardData;
      if (!data) return;

      var files = Array.prototype.slice.call(data.files || []);
      if (!files.length && data.items) {
        Array.prototype.forEach.call(data.items, function (item) {
          if (item.kind !== 'file') return;
          var file = item.getAsFile();
          if (file) files.push(file);
        });
      }
      if (!files.length) return;

      var editing = document.activeElement;
      var typing = editing && (editing.tagName === 'INPUT' || editing.tagName === 'TEXTAREA');
      if (typing && String(data.getData('text/plain') || '').trim()) return;

      event.preventDefault();
      uploadFiles(ui.openTaskId, files);
    });

    /* Arrastrar archivos desde el escritorio hasta la ventana de la tarea. */
    var dropHint = null;
    var dragDepth = 0;

    function carriesFiles(event) {
      var types = event.dataTransfer && event.dataTransfer.types;
      return !!types && Array.prototype.indexOf.call(types, 'Files') !== -1;
    }

    function showDropHint(on) {
      dom.modal.classList.toggle('is-file-over', on);
      if (on && !dropHint) {
        dropHint = el('div', 'kb-dropnote');
        dropHint.appendChild(icon('clip'));
        dropHint.appendChild(el('span', null, 'Soltá para adjuntar a esta tarea'));
        dom.modal.querySelector('.space-modal__dialog').appendChild(dropHint);
      } else if (!on && dropHint) {
        dropHint.remove();
        dropHint = null;
      }
    }

    dom.modal.addEventListener('dragenter', function (event) {
      if (!ui.openTaskId || !carriesFiles(event)) return;
      event.preventDefault();
      dragDepth++;
      showDropHint(true);
    });

    dom.modal.addEventListener('dragover', function (event) {
      if (!ui.openTaskId || !carriesFiles(event)) return;
      event.preventDefault();
      event.dataTransfer.dropEffect = 'copy';
    });

    dom.modal.addEventListener('dragleave', function () {
      if (--dragDepth > 0) return;
      dragDepth = 0;
      showDropHint(false);
    });

    dom.modal.addEventListener('drop', function (event) {
      if (!ui.openTaskId || !carriesFiles(event)) return;
      event.preventDefault();
      dragDepth = 0;
      showDropHint(false);
      uploadFiles(ui.openTaskId, event.dataTransfer.files);
    });

    // prevcs-ui.js también cierra el modal con Escape; acá solo hace falta
    // soltar el estado para que no quede una tarea "abierta" invisible.
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      if (openMenu) return; // el primer Escape cierra el menú, no el modal
      if (ui.openTaskId && !dom.modal.hidden) closeTask();
      else if (pendingConfirm !== null && !dom.confirm.hidden) {
        pendingConfirm = null;
        closeOverlay(dom.confirm);
      }
    });

    /* Evita perder lo que el usuario está escribiendo: si el foco está en
       un campo del modal, se pospone el re-render hasta que lo suelte. */
    function renderDetail() {
      if (!ui.detail) return;

      var focused = document.activeElement;
      if (focused && dom.modalBody.contains(focused) &&
        (focused.tagName === 'INPUT' || focused.tagName === 'TEXTAREA')) {
        if (!ui.detailPending) {
          ui.detailPending = true;
          focused.addEventListener('blur', function () {
            ui.detailPending = false;
            renderDetail();
          }, { once: true });
        }
        return;
      }

      var task = ui.detail;
      var column = columnById(task.column_id);
      dom.modalTitle.textContent = (task.code ? task.code + ' · ' : '') + task.title;

      clear(dom.modalBody);
      var layout = el('div', 'kb-detail');
      var main = el('div', 'kb-detail__main');
      var side = el('div', 'kb-detail__side');

      /* ── Encabezado ─────────────────────────────────────────────── */
      var head = el('div');
      var title = el('textarea', 'kb-detail__title');
      title.value = task.title;
      title.rows = 1;
      title.setAttribute('maxlength', '200');
      title.setAttribute('aria-label', 'Título de la tarea');
      bindInlineEdit(title, function () { return task.title; }, function (value) {
        return saveTask(task.id, { title: value });
      }, 'El título de la tarea no puede quedar vacío.');
      // Es un textarea para que el título largo se vea entero, pero Enter
      // confirma en vez de meter un salto de línea.
      title.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !title.readOnly) {
          event.preventDefault();
          title.blur();
        }
      });
      head.appendChild(title);
      window.setTimeout(function () { autoSize(title); }, 0);

      var meta = el('div', 'kb-detail__meta');
      if (column) {
        var columnChip = el('span', 'kb-badge');
        columnChip.setAttribute('data-kb-color', column.color);
        columnChip.classList.add('kb-badge--prio');
        columnChip.textContent = column.name;
        meta.appendChild(columnChip);
      }
      if (task.status) meta.appendChild(el('span', 'kb-badge', 'Estado: ' + task.status));
      var priority = PRIORITIES[task.priority];
      var priorityChip = el('span', 'kb-badge kb-badge--prio');
      priorityChip.setAttribute('data-kb-color', priority.color);
      priorityChip.appendChild(el('b', null, priority.glyph));
      priorityChip.appendChild(document.createTextNode(' ' + priority.label));
      meta.appendChild(priorityChip);
      if (task.is_completed) {
        var doneChip = el('span', 'kb-badge kb-badge--done');
        doneChip.appendChild(icon('check'));
        doneChip.appendChild(document.createTextNode('Completada ' + formatDateTime(task.completed_at)));
        meta.appendChild(doneChip);
      }
      meta.appendChild(el('span', 'kb-badge', 'Creada ' + formatDateTime(task.created_at)));
      head.appendChild(meta);
      main.appendChild(head);

      /* ── Descripción ────────────────────────────────────────────── */
      main.appendChild(sectionWithTextarea('Descripción', 'note', task.description || '',
        'Contá qué hay que hacer…', function (value) {
          return saveTask(task.id, { description: value });
        }));

      /* ── Etiquetas y responsables aplicados ─────────────────────── */
      if ((task.labels || []).length || (task.assignee_ids || []).length) {
        var applied = el('div', 'kb-section');
        applied.appendChild(sectionHeading('Etiquetas y responsables', 'tag'));
        var chips = el('div', 'kb-card__badges');

        (task.labels || []).forEach(function (id) {
          var label = labelById(id);
          if (!label) return;
          var chip = el('span', 'kb-badge kb-badge--prio');
          chip.setAttribute('data-kb-color', label.color);
          chip.textContent = label.name;
          chips.appendChild(chip);
        });

        (task.assignee_ids || []).forEach(function (id) {
          var member = memberById(id);
          if (!member) return;
          var chip = el('span', 'kb-badge');
          chip.appendChild(el('span', 'kb-avatar', member.initials));
          chip.appendChild(document.createTextNode(member.nombre_completo));
          chips.appendChild(chip);
        });

        applied.appendChild(chips);
        main.appendChild(applied);
      }

      /* ── Checklists ─────────────────────────────────────────────── */
      main.appendChild(buildChecklists(task));

      /* ── Subtareas ──────────────────────────────────────────────── */
      main.appendChild(buildSubtasks(task));

      /* ── Adjuntos ───────────────────────────────────────────────── */
      main.appendChild(buildAttachments(task));

      /* ── Enlaces ────────────────────────────────────────────────── */
      main.appendChild(buildLinks(task));

      /* ── Notas internas ─────────────────────────────────────────── */
      main.appendChild(sectionWithTextarea('Notas internas', 'note', task.internal_notes || '',
        'Notas que no se muestran en la tarjeta…', function (value) {
          return saveTask(task.id, { internal_notes: value });
        }));

      /* ── Comentarios ────────────────────────────────────────────── */
      main.appendChild(buildComments(task));

      /* ── Historial ──────────────────────────────────────────────── */
      main.appendChild(buildActivity(task));

      /* ── Barra lateral de acciones ──────────────────────────────── */
      side.appendChild(buildSideActions(task));
      side.appendChild(buildSideProperties(task));

      layout.appendChild(main);
      layout.appendChild(side);
      dom.modalBody.appendChild(layout);
      // El alto de la caja acaba de cambiar: el scroll suave tiene que
      // volver a tomar la posición real antes del próximo movimiento.
      resyncModalScroll();
    }

    function sectionHeading(text, iconName) {
      var heading = el('h5');
      if (iconName) heading.appendChild(icon(iconName));
      heading.appendChild(el('span', null, text));
      return heading;
    }

    function sectionWithTextarea(label, iconName, value, placeholder, save) {
      var section = el('div', 'kb-section');
      section.appendChild(sectionHeading(label, iconName));

      var field = el('textarea', 'kb-field');
      field.value = value;
      field.placeholder = placeholder;
      field.setAttribute('maxlength', '5000');
      field.setAttribute('aria-label', label);

      var debounced = debounce(function () { save(field.value.trim()); }, 700);
      field.addEventListener('input', function () { autoSize(field); debounced(); });
      field.addEventListener('blur', function () {
        if (field.value.trim() !== (value || '').trim()) save(field.value.trim());
      });
      field.addEventListener('keydown', function (event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
          event.preventDefault();
          field.blur();
        }
        event.stopPropagation();
      });

      section.appendChild(field);
      // El alto inicial se calcula cuando el campo ya está en el documento.
      window.setTimeout(function () { autoSize(field); }, 0);
      return section;
    }

    function buildChecklists(task) {
      var section = el('div', 'kb-section');
      var heading = sectionHeading('Checklist', 'check');
      var add = el('button', 'kb-link-btn', '+ Añadir checklist');
      add.type = 'button';
      add.addEventListener('click', function () {
        mutate('POST', '/tareas/' + task.id + '/checklists', { title: 'Checklist' }, null, {});
      });
      heading.appendChild(add);
      section.appendChild(heading);

      if (!task.checklists.length) {
        section.appendChild(el('p', 'kb-empty', 'Sin checklists todavía'));
        return section;
      }

      task.checklists.forEach(function (checklist) {
        var block = el('div', 'kb-checklist');
        var head = el('div', 'kb-checklist__head');

        var title = el('input', 'kb-checklist__title');
        title.type = 'text';
        title.value = checklist.title;
        title.setAttribute('maxlength', '150');
        bindInlineEdit(title, function () { return checklist.title; }, function (value) {
          return mutate('POST', '/checklists/' + checklist.id, { title: value }, null, {});
        }, 'El checklist necesita un título.');

        var done = checklist.items.filter(function (item) { return item.is_completed; }).length;
        var pct = checklist.items.length ? Math.round((done / checklist.items.length) * 100) : 0;
        var badge = el('span', 'kb-checklist__pct', done + '/' + checklist.items.length + ' · ' + pct + '%');

        var remove = el('button', 'kb-icon-btn');
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Eliminar checklist');
        remove.appendChild(icon('trash'));
        remove.addEventListener('click', function () {
          confirmAction('Eliminar checklist', 'Se borran también sus ítems.', 'Eliminar', function () {
            mutate('POST', '/checklists/' + checklist.id, { action: 'delete' }, null, {});
          }, true);
        });

        head.appendChild(title);
        head.appendChild(badge);
        head.appendChild(remove);
        block.appendChild(head);

        var bar = el('div', 'kb-progress');
        var fill = el('i');
        fill.style.width = pct + '%';
        bar.appendChild(fill);
        bar.classList.toggle('is-full', pct === 100);
        block.appendChild(bar);

        checklist.items.forEach(function (item) {
          block.appendChild(buildChecklistItem(item));
        });

        var newItem = el('input', 'kb-field');
        newItem.type = 'text';
        newItem.placeholder = 'Añadir un ítem…';
        newItem.setAttribute('maxlength', '255');
        newItem.setAttribute('aria-label', 'Nuevo ítem de checklist');
        newItem.addEventListener('keydown', function (event) {
          event.stopPropagation();
          if (event.key !== 'Enter') return;
          event.preventDefault();
          var content = newItem.value.trim();
          if (!content) return;
          newItem.value = '';
          mutate('POST', '/checklists/' + checklist.id + '/items', { content: content }, null, {});
        });
        block.appendChild(newItem);

        section.appendChild(block);
      });

      return section;
    }

    function buildChecklistItem(item) {
      var row = el('div', 'kb-check-item' + (item.is_completed ? ' is-done' : ''));

      var box = el('input');
      box.type = 'checkbox';
      box.checked = item.is_completed;
      box.setAttribute('aria-label', item.content);
      box.addEventListener('change', function () {
        mutate('POST', '/items/' + item.id, { is_completed: box.checked }, null, {});
      });

      var text = el('input', 'kb-check-item__text');
      text.type = 'text';
      text.value = item.content;
      text.setAttribute('maxlength', '255');
      bindInlineEdit(text, function () { return item.content; }, function (value) {
        return mutate('POST', '/items/' + item.id, { content: value }, null, {});
      }, 'El ítem no puede quedar vacío.');

      var actions = el('div', 'kb-check-item__actions');

      var promote = el('button', 'kb-icon-btn');
      promote.type = 'button';
      promote.title = 'Convertir en tarea';
      promote.setAttribute('aria-label', 'Convertir el ítem en una tarea');
      promote.appendChild(icon('move'));
      promote.addEventListener('click', function () {
        mutate('POST', '/items/' + item.id, { action: 'promote' }, null, { success: 'Ítem convertido en tarea' });
      });

      var remove = el('button', 'kb-icon-btn');
      remove.type = 'button';
      remove.title = 'Eliminar ítem';
      remove.setAttribute('aria-label', 'Eliminar ítem');
      remove.appendChild(icon('trash'));
      remove.addEventListener('click', function () {
        mutate('POST', '/items/' + item.id, { action: 'delete' }, null, {});
      });

      actions.appendChild(promote);
      actions.appendChild(remove);

      row.appendChild(box);
      row.appendChild(text);
      row.appendChild(actions);
      return row;
    }

    function buildSubtasks(task) {
      var section = el('div', 'kb-section');
      var heading = sectionHeading('Subtareas', 'list');
      var add = el('button', 'kb-link-btn', '+ Añadir subtarea');
      add.type = 'button';
      add.addEventListener('click', function () {
        openDialog('Nueva subtarea', function (body, close) {
          var input = el('input', 'kb-field');
          input.type = 'text';
          input.placeholder = 'Título de la subtarea';
          input.setAttribute('maxlength', '200');
          body.appendChild(input);

          var row = el('div', 'kb-compose__row');
          var save = el('button', 'btn btn--primary btn--sm', 'Crear');
          save.type = 'button';
          save.addEventListener('click', function () {
            var title = input.value.trim();
            if (!title) { input.focus(); return; }
            close();
            mutate('POST', '/tareas', {
              column_id: task.column_id, title: title, parent_task_id: task.id
            }, null, { success: 'Subtarea creada', after: function () { openTask(task.id); } });
          });
          row.appendChild(save);
          body.appendChild(row);
          window.setTimeout(function () { input.focus(); }, 30);
        });
      });
      heading.appendChild(add);
      section.appendChild(heading);

      if (!task.subtasks.length) {
        section.appendChild(el('p', 'kb-empty', 'Sin subtareas'));
        return section;
      }

      task.subtasks.forEach(function (subtask) {
        var row = el('div', 'kb-linkrow');
        var open = el('button', 'kb-link-btn', (subtask.code ? subtask.code + ' · ' : '') + subtask.title);
        open.type = 'button';
        open.style.flex = '1';
        open.style.textAlign = 'left';
        open.addEventListener('click', function () { openTask(subtask.id); });
        row.appendChild(open);
        if (subtask.is_completed) {
          var chip = el('span', 'kb-badge kb-badge--done');
          chip.appendChild(icon('check'));
          chip.appendChild(document.createTextNode('Lista'));
          row.appendChild(chip);
        }
        section.appendChild(row);
      });

      return section;
    }

    function buildAttachments(task) {
      var section = el('div', 'kb-section');
      var heading = sectionHeading('Adjuntos', 'clip');

      var input = el('input');
      input.type = 'file';
      input.hidden = true;
      input.multiple = true;
      input.accept = '.jpg,.jpeg,.png,.webp,.gif,.svg,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx';

      var add = el('button', 'kb-link-btn', '+ Adjuntar archivo');
      add.type = 'button';
      add.addEventListener('click', function () { input.click(); });

      input.addEventListener('change', function () {
        var files = Array.prototype.slice.call(input.files || []);
        input.value = '';
        uploadFiles(task.id, files);
      });

      heading.appendChild(add);
      section.appendChild(heading);
      section.appendChild(input);

      if (!task.attachments_list.length) {
        section.appendChild(el('p', 'kb-empty',
          'Sin archivos adjuntos · pegá una imagen con Ctrl+V o arrastrala hasta acá'));
        return section;
      }

      var grid = el('div', 'kb-attachments');
      task.attachments_list.forEach(function (attachment) {
        var box = el('div', 'kb-attachment');
        var link = el('a');
        link.href = attachment.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';

        if (attachment.is_image) {
          var image = el('img');
          image.src = attachment.url;
          image.alt = attachment.name;
          image.loading = 'lazy';
          link.appendChild(image);
        } else {
          var extension = (attachment.name.split('.').pop() || 'file').slice(0, 5);
          link.appendChild(el('div', 'kb-attachment__file', extension));
        }

        box.appendChild(link);
        box.appendChild(el('span', 'kb-attachment__name', attachment.name));
        box.appendChild(el('span', null, formatSize(attachment.size)));

        var remove = el('button', 'kb-attachment__del');
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Eliminar adjunto ' + attachment.name);
        remove.appendChild(icon('close'));
        remove.addEventListener('click', function () {
          confirmAction('Eliminar adjunto', 'Se borra «' + attachment.name + '» del servidor.', 'Eliminar', function () {
            mutate('POST', '/adjuntos/' + attachment.id, {}, null, { success: 'Adjunto eliminado' });
          }, true);
        });
        box.appendChild(remove);

        grid.appendChild(box);
      });

      section.appendChild(grid);
      return section;
    }

    function buildLinks(task) {
      var section = el('div', 'kb-section');
      var heading = sectionHeading('Enlaces', 'link');
      var add = el('button', 'kb-link-btn', '+ Añadir enlace');
      add.type = 'button';
      add.addEventListener('click', function () {
        openDialog('Nuevo enlace', function (body, close) {
          var url = el('input', 'kb-field');
          url.type = 'url';
          url.placeholder = 'https://…';
          var title = el('input', 'kb-field');
          title.type = 'text';
          title.placeholder = 'Título (opcional)';
          body.appendChild(url);
          body.appendChild(title);

          var row = el('div', 'kb-compose__row');
          var save = el('button', 'btn btn--primary btn--sm', 'Añadir');
          save.type = 'button';
          save.addEventListener('click', function () {
            if (!url.value.trim()) { url.focus(); return; }
            close();
            mutate('POST', '/tareas/' + task.id + '/enlaces',
              { url: url.value.trim(), title: title.value.trim() }, null, { success: 'Enlace agregado' });
          });
          row.appendChild(save);
          body.appendChild(row);
          window.setTimeout(function () { url.focus(); }, 30);
        });
      });
      heading.appendChild(add);
      section.appendChild(heading);

      if (!task.links.length) {
        section.appendChild(el('p', 'kb-empty', 'Sin enlaces'));
        return section;
      }

      task.links.forEach(function (item) {
        var row = el('div', 'kb-linkrow');
        row.appendChild(icon('link'));
        var link = el('a', null, item.title || item.url);
        link.href = item.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        row.appendChild(link);

        var remove = el('button', 'kb-icon-btn');
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Eliminar enlace');
        remove.appendChild(icon('close'));
        remove.addEventListener('click', function () {
          mutate('POST', '/enlaces/' + item.id, {}, null, {});
        });
        row.appendChild(remove);
        section.appendChild(row);
      });

      return section;
    }

    function buildComments(task) {
      var section = el('div', 'kb-section');
      section.appendChild(sectionHeading('Comentarios', 'comment'));

      var composer = el('div', 'kb-compose');
      var field = el('textarea', 'kb-field');
      field.rows = 1;
      field.placeholder = 'Escribí un comentario… (usá @nombre para mencionar)';
      field.setAttribute('maxlength', '3000');
      field.setAttribute('aria-label', 'Nuevo comentario');
      field.addEventListener('input', function () { autoSize(field); });
      field.addEventListener('keydown', function (event) {
        event.stopPropagation();
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') { event.preventDefault(); submit(); }
      });

      var row = el('div', 'kb-compose__row');
      var send = el('button', 'btn btn--primary btn--sm', 'Comentar');
      send.type = 'button';
      var hint = el('span', 'kb-compose__hint', 'Ctrl+Enter para enviar');

      function submit(parentId) {
        var content = field.value.trim();
        if (!content) { field.focus(); return; }
        field.value = '';
        mutate('POST', '/tareas/' + task.id + '/comentarios',
          { content: content, parent_comment_id: parentId || null }, null, {});
      }

      send.addEventListener('click', function () { submit(); });
      row.appendChild(send);
      row.appendChild(hint);
      composer.appendChild(field);
      composer.appendChild(row);
      section.appendChild(composer);

      if (!task.comments_list.length) {
        section.appendChild(el('p', 'kb-empty', 'Todavía no hay comentarios'));
        return section;
      }

      task.comments_list.forEach(function (comment) {
        section.appendChild(buildComment(task, comment, field));
      });

      return section;
    }

    function buildComment(task, comment, composer) {
      var row = el('div', 'kb-comment' + (comment.parent_id ? ' kb-comment--reply' : ''));
      row.appendChild(el('div', 'kb-comment__avatar', comment.initials));

      var body = el('div');
      var head = el('div', 'kb-comment__head');
      head.appendChild(el('span', 'kb-comment__author', comment.author));
      head.appendChild(el('span', 'kb-comment__time',
        formatDateTime(comment.created_at) + (comment.edited_at ? ' · editado' : '')));
      body.appendChild(head);

      var text = el('div', 'kb-comment__body');
      writeWithMentions(text, comment.content);
      body.appendChild(text);

      var actions = el('div', 'kb-comment__actions');

      var reply = el('button', 'kb-link-btn', 'Responder');
      reply.type = 'button';
      reply.addEventListener('click', function () {
        composer.value = '@' + comment.author.split(' ')[0] + ' ';
        composer.focus();
      });
      actions.appendChild(reply);

      if (comment.user_id === state.user_id) {
        var edit = el('button', 'kb-link-btn', 'Editar');
        edit.type = 'button';
        edit.addEventListener('click', function () {
          var editor = el('textarea', 'kb-field');
          editor.value = comment.content;
          editor.setAttribute('maxlength', '3000');
          text.replaceWith(editor);
          editor.focus();
          editor.addEventListener('keydown', function (event) {
            event.stopPropagation();
            if (event.key === 'Escape') { renderDetail(); }
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') { editor.blur(); }
          });
          editor.addEventListener('blur', function () {
            var value = editor.value.trim();
            if (!value || value === comment.content) { renderDetail(); return; }
            mutate('POST', '/comentarios/' + comment.id, { content: value }, null, {});
          });
        });
        actions.appendChild(edit);

        var remove = el('button', 'kb-link-btn kb-link-btn--danger', 'Eliminar');
        remove.type = 'button';
        remove.addEventListener('click', function () {
          confirmAction('Eliminar comentario', 'No se puede deshacer.', 'Eliminar', function () {
            mutate('POST', '/comentarios/' + comment.id, { action: 'delete' }, null, {});
          }, true);
        });
        actions.appendChild(remove);
      }

      body.appendChild(actions);
      row.appendChild(body);
      return row;
    }

    function buildActivity(task) {
      var section = el('div', 'kb-section');
      section.appendChild(sectionHeading('Historial', 'history'));

      if (!task.activity.length) {
        section.appendChild(el('p', 'kb-empty', 'Sin movimientos registrados'));
        return section;
      }

      var list = el('div', 'kb-activity');
      list.setAttribute('data-lenis-prevent', '');
      task.activity.forEach(function (entry) {
        var row = el('div', 'kb-activity__row');
        row.appendChild(el('span', 'kb-activity__dot'));
        row.appendChild(el('span', null, entry.author + ' ' + (entry.details || entry.action)));
        row.appendChild(el('span', 'kb-activity__time', formatDateTime(entry.created_at)));
        list.appendChild(row);
      });
      section.appendChild(list);
      return section;
    }

    function buildSideActions(task) {
      var group = el('div', 'kb-section');
      group.appendChild(sectionHeading('Acciones', 'move'));

      var box = el('div', 'kb-side-group');

      box.appendChild(sideButton('Mover a otra columna', 'move', function (event) {
        openTaskMoveMenu(event.currentTarget, task.id);
      }));

      box.appendChild(sideButton(task.is_completed ? 'Reabrir tarea' : 'Marcar completada', 'check', function () {
        taskAction(task.id, task.is_completed ? 'reopen' : 'complete', {
          success: task.is_completed ? 'Tarea reabierta' : 'Tarea completada',
          after: function () { openTask(task.id); }
        });
      }));

      box.appendChild(sideButton('Duplicar', 'copy', function () {
        taskAction(task.id, 'duplicate', { success: 'Tarea duplicada', after: closeTask });
      }));

      box.appendChild(sideButton('Archivar', 'archive', function () {
        taskAction(task.id, 'archive', {
          success: 'Tarea archivada',
          undo: { label: 'Deshacer', onClick: function () { taskAction(task.id, 'restore'); } },
          after: closeTask
        });
      }));

      box.appendChild(sideButton('Eliminar', 'trash', function () {
        confirmAction('Eliminar «' + task.title + '»',
          'Se borra definitivamente, junto a sus comentarios, checklists y adjuntos.', 'Eliminar', function () {
            taskAction(task.id, 'delete', { success: 'Tarea eliminada', after: closeTask });
          }, true);
      }, true));

      group.appendChild(box);
      return group;
    }

    function buildSideProperties(task) {
      var group = el('div', 'kb-section');
      group.appendChild(sectionHeading('Propiedades', 'palette'));

      var box = el('div', 'kb-side-group');

      box.appendChild(sideButton('Color de la tarjeta', 'palette', function (event) {
        showMenu(event.currentTarget, function (menu) {
          menuLabel(menu, 'Color de la tarjeta');
          colorSwatches(menu, task.color, function (color) { saveTask(task.id, { color: color }); }, true);
        });
      }));

      box.appendChild(sideButton('Etiquetas', 'tag', function (event) {
        showMenu(event.currentTarget, function (menu) {
          menuLabel(menu, 'Etiquetas del tablero');
          var picker = el('div', 'kb-picker');

          state.labels.forEach(function (label) {
            var on = (task.labels || []).indexOf(label.id) !== -1;
            var row = el('button', 'kb-picker__row' + (on ? ' is-on' : ''));
            row.type = 'button';
            var swatch = el('span', 'kb-picker__swatch');
            swatch.setAttribute('data-kb-color', label.color);
            row.appendChild(swatch);
            row.appendChild(el('span', null, label.name));
            if (on) row.appendChild(el('span', 'kb-picker__check', '✓'));
            row.addEventListener('click', function () {
              closeMenu();
              mutate('POST', '/tareas/' + task.id + '/etiquetas', { label_id: label.id }, null, {});
            });
            picker.appendChild(row);
          });

          if (!state.labels.length) picker.appendChild(el('p', 'kb-empty', 'Todavía no creaste etiquetas'));
          menu.appendChild(picker);
          menuSeparator(menu);
          menuItem(menu, 'Crear etiqueta nueva', 'plus', openLabelDialog);
        });
      }));

      box.appendChild(sideButton('Responsables', 'user', function (event) {
        showMenu(event.currentTarget, function (menu) {
          menuLabel(menu, 'Miembros del espacio');
          var picker = el('div', 'kb-picker');
          state.members.forEach(function (member) {
            var on = (task.assignee_ids || []).indexOf(member.id) !== -1;
            var row = el('button', 'kb-picker__row' + (on ? ' is-on' : ''));
            row.type = 'button';
            row.appendChild(el('span', 'kb-avatar', member.initials));
            row.appendChild(el('span', null, member.nombre_completo));
            if (on) row.appendChild(el('span', 'kb-picker__check', '✓'));
            row.addEventListener('click', function () {
              closeMenu();
              mutate('POST', '/tareas/' + task.id + '/responsables', { user_id: member.id }, null, {});
            });
            picker.appendChild(row);
          });
          menu.appendChild(picker);
        });
      }));

      box.appendChild(sideButton('Prioridad: ' + PRIORITIES[task.priority].label, 'flag', function (event) {
        showMenu(event.currentTarget, function (menu) {
          menuLabel(menu, 'Prioridad');
          Object.keys(PRIORITIES).forEach(function (key) {
            var item = menuItem(menu, PRIORITIES[key].glyph + '  ' + PRIORITIES[key].label, null, function () {
              saveTask(task.id, { priority: key });
            });
            if (task.priority === key) item.appendChild(el('span', 'kb-picker__check', '✓'));
          });
        });
      }));

      group.appendChild(box);

      /* Fechas y tiempos como campos directos: son los datos que más se
         editan y no vale la pena esconderlos detrás de un menú. */
      var dates = el('div', 'kb-side-group');
      dates.appendChild(dateField('Fecha de inicio', task.start_date, function (value) {
        saveTask(task.id, { start_date: value });
      }));
      dates.appendChild(dateField('Fecha límite', task.due_date, function (value) {
        saveTask(task.id, { due_date: value });
      }));
      dates.appendChild(numberField('Tiempo estimado (min)', task.estimated_minutes, function (value) {
        saveTask(task.id, { estimated_minutes: value });
      }));
      dates.appendChild(numberField('Tiempo registrado (min)', task.logged_minutes, function (value) {
        saveTask(task.id, { logged_minutes: value });
      }));
      group.appendChild(dates);

      return group;
    }

    function sideButton(label, iconName, onClick, danger) {
      var button = el('button', 'kb-side-btn' + (danger ? ' kb-side-btn--danger' : ''));
      button.type = 'button';
      button.appendChild(icon(iconName));
      button.appendChild(el('span', null, label));
      button.addEventListener('click', function (event) { event.stopPropagation(); onClick(event); });
      return button;
    }

    function dateField(label, value, save) {
      var wrap = el('label', 'kb-section');
      wrap.appendChild(el('h5', null, label));
      var input = el('input', 'kb-field');
      input.type = 'date';
      input.value = value || '';
      input.addEventListener('change', function () { save(input.value || null); });
      input.addEventListener('keydown', function (event) { event.stopPropagation(); });
      wrap.appendChild(input);
      return wrap;
    }

    function numberField(label, value, save) {
      var wrap = el('label', 'kb-section');
      wrap.appendChild(el('h5', null, label));
      var input = el('input', 'kb-field');
      input.type = 'number';
      input.min = '0';
      input.value = value === null || value === undefined ? '' : String(value);
      input.addEventListener('change', function () { save(input.value === '' ? null : Number(input.value)); });
      input.addEventListener('keydown', function (event) { event.stopPropagation(); });
      wrap.appendChild(input);
      return wrap;
    }

    function openLabelDialog() {
      openDialog('Nueva etiqueta', function (body, close) {
        var name = el('input', 'kb-field');
        name.type = 'text';
        name.placeholder = 'Nombre de la etiqueta';
        name.setAttribute('maxlength', '60');
        body.appendChild(name);

        var chosen = state.colors[0] || 'azul';
        var grid = el('div', 'kb-swatches');
        (state.colors || []).forEach(function (color) {
          var swatch = el('button', 'kb-swatch' + (color === chosen ? ' is-on' : ''));
          swatch.type = 'button';
          swatch.setAttribute('data-kb-color', color);
          swatch.title = COLOR_NAMES[color] || color;
          swatch.addEventListener('click', function () {
            chosen = color;
            grid.querySelectorAll('.kb-swatch').forEach(function (node) { node.classList.remove('is-on'); });
            swatch.classList.add('is-on');
          });
          grid.appendChild(swatch);
        });
        body.appendChild(grid);

        var row = el('div', 'kb-compose__row');
        var save = el('button', 'btn btn--primary btn--sm', 'Crear');
        save.type = 'button';
        save.addEventListener('click', function () {
          if (!name.value.trim()) { name.focus(); return; }
          close();
          mutate('POST', '/etiquetas', { name: name.value.trim(), color: chosen }, null, { success: 'Etiqueta creada' });
        });
        row.appendChild(save);
        body.appendChild(row);
        window.setTimeout(function () { name.focus(); }, 30);
      });
    }

    /* ═════════════════════════════════════════════════════════════════
       Toolbar: búsqueda, filtros, orden, archivo y fondo
       ═════════════════════════════════════════════════════════════════ */

    dom.search.addEventListener('input', debounce(function () {
      ui.search = dom.search.value.trim();
      render();
    }, 220));

    dom.search.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { dom.search.value = ''; ui.search = ''; render(); }
    });

    dom.filtersBtn.addEventListener('click', function () {
      var open = dom.filters.hidden;
      dom.filters.hidden = !open;
      dom.filtersBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) renderFilters();
    });

    function renderFilters() {
      clear(dom.filters);

      dom.filters.appendChild(filterGroup('Prioridad', Object.keys(PRIORITIES).map(function (key) {
        return { value: key, label: PRIORITIES[key].glyph + ' ' + PRIORITIES[key].label };
      }), 'priority'));

      dom.filters.appendChild(filterGroup('Columna', state.columns.map(function (column) {
        return { value: column.id, label: column.name };
      }), 'column'));

      dom.filters.appendChild(filterGroup('Etiqueta', state.labels.map(function (label) {
        return { value: label.id, label: label.name };
      }), 'label'));

      dom.filters.appendChild(filterGroup('Responsable',
        [{ value: 0, label: 'Sin asignar' }].concat(state.members.map(function (member) {
          return { value: member.id, label: member.nombre_completo };
        })), 'assignee'));

      dom.filters.appendChild(filterGroup('Color', (state.colors || []).map(function (color) {
        return { value: color, label: COLOR_NAMES[color] || color };
      }), 'color'));

      var statuses = [];
      state.tasks.forEach(function (task) {
        if (task.status && statuses.indexOf(task.status) === -1) statuses.push(task.status);
      });
      if (statuses.length) {
        dom.filters.appendChild(filterGroup('Estado', statuses.map(function (status) {
          return { value: status, label: status };
        }), 'status'));
      }

      /* Filtros de fecha/estado: excluyentes entre sí. */
      var dueGroup = el('div', 'kb-filters__group');
      dueGroup.appendChild(el('strong', null, 'Fecha y estado'));
      var dueChips = el('div', 'kb-filters__chips');
      [
        { value: 'overdue', label: 'Vencidas' },
        { value: 'soon', label: 'Vencen pronto' },
        { value: 'none', label: 'Sin fecha' },
        { value: 'completed', label: 'Completadas' },
        { value: 'pending', label: 'Pendientes' }
      ].forEach(function (option) {
        var chip = el('button', 'chip' + (ui.filters.due === option.value ? ' is-on' : ''), option.label);
        chip.type = 'button';
        chip.setAttribute('aria-pressed', ui.filters.due === option.value ? 'true' : 'false');
        chip.addEventListener('click', function () {
          ui.filters.due = ui.filters.due === option.value ? '' : option.value;
          renderFilters();
          render();
        });
        dueChips.appendChild(chip);
      });
      dueGroup.appendChild(dueChips);
      dom.filters.appendChild(dueGroup);

      var foot = el('div', 'kb-filters__foot');
      var reset = el('button', 'kb-tool', 'Limpiar filtros');
      reset.type = 'button';
      reset.addEventListener('click', function () {
        ui.filters = { priority: [], color: [], label: [], assignee: [], column: [], status: [], due: '' };
        ui.search = '';
        dom.search.value = '';
        renderFilters();
        render();
      });
      foot.appendChild(reset);
      dom.filters.appendChild(foot);
    }

    function filterGroup(title, options, key) {
      var group = el('div', 'kb-filters__group');
      group.appendChild(el('strong', null, title));
      var chips = el('div', 'kb-filters__chips');

      if (!options.length) {
        chips.appendChild(el('span', 'kb-compose__hint', 'Sin opciones'));
      }

      options.forEach(function (option) {
        var on = ui.filters[key].indexOf(option.value) !== -1;
        var chip = el('button', 'chip' + (on ? ' is-on' : ''), option.label);
        chip.type = 'button';
        chip.setAttribute('aria-pressed', on ? 'true' : 'false');
        chip.addEventListener('click', function () {
          var at = ui.filters[key].indexOf(option.value);
          if (at === -1) ui.filters[key].push(option.value);
          else ui.filters[key].splice(at, 1);
          renderFilters();
          render();
        });
        chips.appendChild(chip);
      });

      group.appendChild(chips);
      return group;
    }

    dom.sortBtn.addEventListener('click', function () {
      showMenu(dom.sortBtn, function (menu) {
        menuLabel(menu, 'Ordenar tareas por');
        [
          { value: 'manual', label: 'Orden manual (arrastrando)' },
          { value: 'created', label: 'Fecha de creación' },
          { value: 'due', label: 'Fecha límite' },
          { value: 'priority', label: 'Prioridad' },
          { value: 'name', label: 'Nombre' },
          { value: 'updated', label: 'Última actualización' }
        ].forEach(function (option) {
          var item = menuItem(menu, option.label, null, function () {
            ui.sort = option.value;
            dom.sortBtn.classList.toggle('is-on', option.value !== 'manual');
            if (option.value !== 'manual') {
              toast('Con un orden automático el arrastre queda desactivado.', 'success');
            }
            render();
          });
          if (ui.sort === option.value) item.appendChild(el('span', 'kb-picker__check', '✓'));
        });
      });
    });

    dom.bgBtn.addEventListener('click', function () {
      showMenu(dom.bgBtn, function (menu) {
        menuLabel(menu, 'Fondo del tablero');
        colorSwatches(menu, state.board.background_color, function (color) {
          mutate('POST', '/tablero', { background_color: color }, function () {
            state.board.background_color = color;
          }, {});
        }, false);
        menuSeparator(menu);
        menuItem(menu, 'Gestionar etiquetas', 'tag', openLabelManager);
      });
    });

    function openLabelManager() {
      openDialog('Etiquetas del tablero', function (body, close) {
        var list = el('div', 'kb-picker');

        if (!state.labels.length) list.appendChild(el('p', 'kb-empty', 'Todavía no hay etiquetas'));

        state.labels.forEach(function (label) {
          var row = el('div', 'kb-linkrow');
          var swatch = el('span', 'kb-picker__swatch');
          swatch.setAttribute('data-kb-color', label.color);
          row.appendChild(swatch);

          var name = el('input', 'kb-check-item__text');
          name.type = 'text';
          name.value = label.name;
          name.setAttribute('maxlength', '60');
          bindInlineEdit(name, function () { return label.name; }, function (value) {
            return mutate('POST', '/etiquetas/' + label.id, { name: value }, null, {});
          }, 'La etiqueta necesita un nombre.');
          row.appendChild(name);

          var palette = el('button', 'kb-icon-btn');
          palette.type = 'button';
          palette.setAttribute('aria-label', 'Cambiar color');
          palette.appendChild(icon('palette'));
          palette.addEventListener('click', function (event) {
            showMenu(event.currentTarget, function (menu) {
              colorSwatches(menu, label.color, function (color) {
                mutate('POST', '/etiquetas/' + label.id, { color: color }, null, {});
                close();
              }, false);
            });
          });
          row.appendChild(palette);

          var remove = el('button', 'kb-icon-btn');
          remove.type = 'button';
          remove.setAttribute('aria-label', 'Eliminar etiqueta');
          remove.appendChild(icon('trash'));
          remove.addEventListener('click', function () {
            mutate('POST', '/etiquetas/' + label.id, { action: 'delete' }, null, { success: 'Etiqueta eliminada' });
            close();
          });
          row.appendChild(remove);

          list.appendChild(row);
        });

        body.appendChild(list);

        var row = el('div', 'kb-compose__row');
        var add = el('button', 'btn btn--primary btn--sm', 'Nueva etiqueta');
        add.type = 'button';
        add.addEventListener('click', function () { close(); openLabelDialog(); });
        row.appendChild(add);
        body.appendChild(row);
      });
    }

    /* ── Panel de archivados ───────────────────────────────────────── */

    dom.archiveBtn.addEventListener('click', function () {
      openDialog('Elementos archivados', function (body, close) {
        var loading = el('p', 'kb-empty', 'Cargando…');
        body.appendChild(loading);

        request('GET', '/archivo').then(function (data) {
          clear(body);

          if (!data.columns.length && !data.tasks.length) {
            body.appendChild(el('p', 'kb-empty', 'No hay nada archivado'));
            return;
          }

          if (data.columns.length) {
            body.appendChild(sectionHeading('Columnas archivadas', 'archive'));
            data.columns.forEach(function (column) {
              var row = el('div', 'kb-linkrow');
              var swatch = el('span', 'kb-picker__swatch');
              swatch.setAttribute('data-kb-color', column.color);
              row.appendChild(swatch);
              row.appendChild(el('span', null, column.name));
              var restore = el('button', 'kb-link-btn', 'Restaurar');
              restore.type = 'button';
              restore.addEventListener('click', function () {
                close();
                columnAction(column.id, 'restore', { success: 'Columna restaurada' });
              });
              row.appendChild(restore);
              body.appendChild(row);
            });
          }

          if (data.tasks.length) {
            body.appendChild(sectionHeading('Tareas archivadas', 'archive'));
            data.tasks.forEach(function (task) {
              var row = el('div', 'kb-linkrow');
              row.appendChild(el('span', null, (task.code ? task.code + ' · ' : '') + task.title));
              var restore = el('button', 'kb-link-btn', 'Restaurar');
              restore.type = 'button';
              restore.addEventListener('click', function () {
                close();
                taskAction(task.id, 'restore', { success: 'Tarea restaurada' });
              });
              row.appendChild(restore);
              body.appendChild(row);
            });
          }
        }).catch(function (error) {
          clear(body);
          body.appendChild(el('p', 'kb-empty', error.message || 'No se pudo cargar el archivo.'));
        });
      });
    });

    /* ═════════════════════════════════════════════════════════════════
       Panel "Completadas"
       ═════════════════════════════════════════════════════════════════ */

    var doneQuery = '';

    function loadCompleted() {
      var query = doneQuery ? '?q=' + encodeURIComponent(doneQuery) : '';
      request('GET', '/completadas' + query).then(function (data) {
        renderCompleted(data.tasks);
      }).catch(function () {
        clear(dom.doneList);
        dom.doneList.appendChild(el('p', 'kb-empty', 'No se pudieron cargar las tareas completadas.'));
      });
    }

    function renderCompleted(tasks) {
      clear(dom.doneList);
      dom.doneCount.textContent = tasks.length ? String(tasks.length) : '';

      if (!tasks.length) {
        dom.doneList.appendChild(el('p', 'kb-empty',
          doneQuery ? 'Ninguna tarea completada coincide con la búsqueda' : 'Todavía no hay tareas completadas'));
        return;
      }

      tasks.forEach(function (task) {
        var row = el('div', 'kb-done__row');

        var summary = el('button', 'kb-done__summary');
        summary.type = 'button';
        summary.setAttribute('aria-expanded', 'false');

        var name = el('span', 'kb-done__name');
        writeHighlighted(name, (task.code ? task.code + ' · ' : '') + task.title, doneQuery);

        var excerpt = el('span', 'kb-done__excerpt');
        writeHighlighted(excerpt, task.description || 'Sin descripción', doneQuery);

        var who = el('span', 'kb-done__who',
          (task.assignees || []).map(function (person) { return person.nombre_completo; }).join(', ') || '—');

        var when = el('span', 'kb-done__when', formatDate(task.completed_at));

        summary.appendChild(name);
        summary.appendChild(excerpt);
        summary.appendChild(who);
        summary.appendChild(when);
        summary.appendChild(icon('chevron', 'kb-done__chevron'));

        var panel = el('div', 'kb-done__panel');
        var inner = el('div');
        var content = el('div', 'kb-done__body');
        content.appendChild(el('p', null, task.description || 'Esta tarea no tiene descripción.'));

        if ((task.images || []).length) {
          var gallery = el('div', 'kb-done__images');
          task.images.forEach(function (image) {
            var link = el('a');
            link.href = image.url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            var thumb = el('img');
            thumb.src = image.url;
            thumb.alt = image.name;
            thumb.loading = 'lazy';
            link.appendChild(thumb);
            gallery.appendChild(link);
          });
          content.appendChild(gallery);
        }

        var actions = el('div', 'kb-done__actions');
        var open = el('button', 'btn btn--secondary btn--sm', 'Abrir tarea');
        open.type = 'button';
        open.addEventListener('click', function () { openTask(task.id); });
        var reopen = el('button', 'btn btn--secondary btn--sm', 'Reabrir');
        reopen.type = 'button';
        reopen.addEventListener('click', function () {
          taskAction(task.id, 'reopen', { success: 'Tarea reabierta', after: loadCompleted });
        });
        var archive = el('button', 'btn btn--secondary btn--sm', 'Archivar');
        archive.type = 'button';
        archive.addEventListener('click', function () {
          taskAction(task.id, 'archive', { success: 'Tarea archivada', after: loadCompleted });
        });
        actions.appendChild(open);
        actions.appendChild(reopen);
        actions.appendChild(archive);
        content.appendChild(actions);

        inner.appendChild(content);
        panel.appendChild(inner);

        summary.addEventListener('click', function () {
          var open = row.classList.toggle('is-open');
          summary.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        row.appendChild(summary);
        row.appendChild(panel);
        dom.doneList.appendChild(row);
      });
    }

    dom.doneSearch.addEventListener('input', debounce(function () {
      doneQuery = dom.doneSearch.value.trim();
      loadCompleted();
    }, 300));

    /* ═════════════════════════════════════════════════════════════════
       Cabecera del tablero: título y descripción editables
       ═════════════════════════════════════════════════════════════════ */

    bindInlineEdit(dom.title, function () { return state.board ? state.board.name : ''; }, function (value) {
      return mutate('POST', '/tablero', { name: value }, function () { state.board.name = value; }, {});
    }, 'El tablero necesita un nombre.');

    /* El panel de completadas está anclado al pie de la superficie, así
       que se saca del flujo: hay que devolverle ese alto al carril de
       columnas como padding para que no le tape el pie a la última
       tarjeta. Se mide en vivo porque el panel crece al abrir filas. */
    function syncDoneHeight() {
      dom.surface.style.setProperty('--kb-done-h', dom.done.offsetHeight + 'px');
    }

    if (window.ResizeObserver) {
      new ResizeObserver(syncDoneHeight).observe(dom.done);
    } else {
      window.addEventListener('resize', syncDoneHeight);
    }
    syncDoneHeight();

    // Es un textarea para que el nombre largo envuelva y se vea entero,
    // pero Enter confirma en vez de meter un salto de línea.
    dom.title.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !dom.title.readOnly) {
        event.preventDefault();
        dom.title.blur();
      }
    });


    /* ═════════════════════════════════════════════════════════════════
       Conexión
       ═════════════════════════════════════════════════════════════════ */

    function showOffline(off) {
      dom.offline.hidden = false;
      dom.offline.classList.toggle('is-back', !off);
      dom.offline.textContent = off
        ? 'Sin conexión: los cambios no se están guardando.'
        : 'Conexión recuperada. Sincronizando…';
      if (!off) {
        window.setTimeout(function () { dom.offline.hidden = true; }, 2600);
        reload();
      }
    }

    window.addEventListener('offline', function () { showOffline(true); });
    window.addEventListener('online', function () { showOffline(false); });

    /* ═════════════════════════════════════════════════════════════════
       Arranque
       ═════════════════════════════════════════════════════════════════ */

    function reload() {
      return request('GET', '/tablero').then(function (data) {
        applyPayload(data);
        render();
        loadCompleted();
      });
    }

    function boot() {
      dom.board.setAttribute('aria-busy', 'true');
      clear(dom.board);
      var skeleton = el('div', 'kb-skeleton');
      for (var i = 0; i < 3; i++) skeleton.appendChild(el('div', 'kb-skeleton__col'));
      dom.board.appendChild(skeleton);

      reload().catch(function (error) {
        clear(dom.board);
        var fail = el('div', 'kb-state');
        fail.appendChild(el('p', null, error.message || 'No se pudo cargar el tablero.'));
        var retry = el('button', 'btn btn--secondary btn--sm', 'Reintentar');
        retry.type = 'button';
        retry.addEventListener('click', boot);
        fail.appendChild(retry);
        dom.board.appendChild(fail);
      });
    }

    boot();
  }

  /* ═══════════════════════════════════════════════════════════════════
     Arranque de todos los tableros de la página
     ═══════════════════════════════════════════════════════════════════ */

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-kb-root]').forEach(createBoard);
  });
})();
