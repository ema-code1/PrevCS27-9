/* =====================================================================
   PrevCS · UI compartida (vistas IoT)
   - Modales emergentes con animación de entrada/salida ([data-space-modal]).
   - Scroll con inercia adentro de cualquier ventana abierta.
   - Kebab de opciones por tarjeta ([data-kebab]).
   - Acordeón de espacios ([data-acc]).
   - Confirmación estilada para acciones destructivas (form[data-confirm]).
   - Indicador deslizante del section-switch (píldora negra).
   ===================================================================== */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  /* ── Scroll con inercia dentro de una caja ──────────────────────────
     Lenis maneja el scroll de la página pero no llega a los modales: la
     caja lleva data-lenis-prevent y además Lenis se frena entero mientras
     hay una ventana abierta, así que adentro quedaba el scroll nativo a
     los saltos. Esto le devuelve la misma inercia sin tocar a Lenis: la
     rueda mueve un objetivo y un rAF lo persigue interpolando.

     Devuelve una función para reamarrar el objetivo cuando el contenido de
     la caja cambia (si no, apunta a un alto que ya no existe). Llamarla dos
     veces sobre el mismo nodo no duplica el enganche: devuelve el resync
     que ya tenía.
     ─────────────────────────────────────────────────────────────────── */
  function bindSmoothBox(node) {
    if (!node) return function () {};
    if (node.__pcsSmoothResync) return node.__pcsSmoothResync;
    if (reduceMotion.matches) {
      node.__pcsSmoothResync = function () {};
      return node.__pcsSmoothResync;
    }

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

    /* Una lista con scroll propio adentro (el historial de una tarea, por
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

    node.__pcsSmoothResync = function resync() {
      if (raf) { window.cancelAnimationFrame(raf); raf = 0; }
      target = node.scrollTop;
    };
    return node.__pcsSmoothResync;
  }

  window.PrevcsScrollBox = { bind: bindSmoothBox };

  document.addEventListener('DOMContentLoaded', function () {
    /* Toda ventana emergente scrollea con la misma inercia que la página. */
    document.querySelectorAll('[data-space-modal] .space-modal__body, [data-smooth-box]')
      .forEach(bindSmoothBox);

    /* ── Kebab (menú de opciones por tarjeta) ─────────────────────── */

    /* Un kebab que vive adentro de una caja con scroll (el cuerpo del modal)
       queda recortado por esa caja: el menú de un miembro mide más que la
       fila y el navegador lo corta en el borde, así que "Eliminar usuario"
       no llegaba a verse. Para esos casos el menú se saca del flujo con
       position:fixed y se lo ancla al botón en coordenadas de pantalla, y
       si no entra para abajo se abre para arriba. Los kebabs que no están
       dentro de una caja con scroll siguen con el absolute de la hoja. */
    function clippingBox(node) {
      var current = node.parentElement;
      while (current && current !== document.body) {
        var overflow = window.getComputedStyle(current).overflowY;
        if (overflow === 'auto' || overflow === 'scroll') return current;
        current = current.parentElement;
      }
      return null;
    }

    /* Dónde cae realmente un position:fixed en top:0/left:0 desde acá.

       No siempre es la esquina de la pantalla: cualquier ancestro con
       transform pasa a ser el bloque contenedor de los fixed, y el diálogo
       del modal tiene uno mientras corre su animación de entrada. Se mide
       con un elemento de prueba —y no con el menú— porque el menú trae su
       propia animación con transform y devolvería una posición corrida. */
    /* innerWidth puede venir en 0 en contextos sin pintar (una pestaña de
       fondo, una captura). clientWidth del documento es el que siempre tiene
       un valor usable, y quedarse con el mayor de los dos evita colocar un
       menú contra un ancho inexistente. */
    function viewportWidth() {
      return Math.max(window.innerWidth || 0, document.documentElement.clientWidth || 0);
    }

    function fixedOrigin(node) {
      var probe = document.createElement('div');
      probe.style.cssText = 'position:fixed;top:0;left:0;width:0;height:0;pointer-events:none;visibility:hidden;';
      node.parentNode.appendChild(probe);
      var rect = probe.getBoundingClientRect();
      probe.parentNode.removeChild(probe);

      return { x: rect.left, y: rect.top };
    }

    function placeFloating(button, menu, box) {
      var anchor = button.getBoundingClientRect();
      var gap = 6;

      menu.style.position = 'fixed';
      menu.style.right = 'auto';
      menu.style.top = '0px';
      menu.style.left = '0px';

      // offsetWidth/Height y no el rect: el rect incluye la animación de
      // entrada del propio menú y daría una medida más chica.
      var height = menu.offsetHeight;
      var width = menu.offsetWidth;
      var limit = box.getBoundingClientRect();

      /* Si no hay medidas (la caja todavía no se pintó), colocarlo con esos
         números lo mandaría a una esquina cualquiera. Se deja el absolute de
         la hoja, que al menos lo deja pegado a su botón. */
      if (!width || !height || !limit.width) {
        menu.style.position = '';
        menu.style.right = '';
        menu.style.top = '';
        menu.style.left = '';
        return;
      }

      // Abajo si entra entre el botón y el piso de la caja; si no, arriba.
      var below = anchor.bottom + gap;
      var top = below + height <= limit.bottom ? below : anchor.top - gap - height;

      /* El menú se queda adentro de la caja aunque no entre a ninguno de los
         dos lados: si se lo dejara desbordar, se dibujaría por encima del
         borde del diálogo y parecería suelto en la página. */
      top = Math.min(top, limit.bottom - height - gap);
      top = Math.max(top, limit.top + gap);

      var left = Math.max(gap, Math.min(anchor.right - width, viewportWidth() - width - gap));

      var origin = fixedOrigin(menu);
      menu.style.top = Math.round(top - origin.y) + 'px';
      menu.style.left = Math.round(left - origin.x) + 'px';
    }

    /* Cerrar el submenú lateral de un kebab (el de "Supervisor"). */
    function isFlyoutFamily(sub, except) {
      return !!sub && !!except && (sub === except || sub.contains(except) || except.contains(sub));
    }

    function closeFlyouts(except) {
      document.querySelectorAll('[data-kebab-sub].is-open').forEach(function (sub) {
        if (isFlyoutFamily(sub, except)) return;
        sub.classList.remove('is-open', 'is-flipped');

        var flyout = sub.querySelector('[data-kebab-flyout]');
        if (flyout) {
          flyout.style.position = '';
          flyout.style.top = '';
          flyout.style.left = '';
          flyout.style.right = '';
        }

        var button = sub.querySelector('[data-kebab-sub-button]');
        if (button) button.setAttribute('aria-expanded', 'false');
      });
    }

    function closeKebabs() {
      closeFlyouts(null);
      document.querySelectorAll('[data-kebab-menu]').forEach(function (m) {
        m.hidden = true;
        m.style.position = '';
        m.style.top = '';
        m.style.left = '';
        m.style.right = '';
      });
      document.querySelectorAll('[data-kebab-button]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    }

    /* Los kebabs van por delegación y no enganchados uno por uno: las listas
       del modal se vuelven a dibujar solas cuando se guarda algo, y los nodos
       nuevos no tendrían los listeners de la carga inicial. */
    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-kebab-button]');

      if (!button) {
        if (event.target.closest('[data-kebab]')) return;
        // Clic afuera: se cierra todo lo que esté abierto.
        closeKebabs();
        return;
      }

      event.stopPropagation();

      var wrap = button.closest('[data-kebab]');
      var menu = wrap && wrap.querySelector('[data-kebab-menu]');
      if (!menu) return;

      var willOpen = menu.hidden;
      closeKebabs();
      if (!willOpen) return;

      menu.hidden = false;
      button.setAttribute('aria-expanded', 'true');

      var box = clippingBox(wrap);
      if (box) placeFloating(button, menu, box);
    });

    /* Si la caja con scroll se mueve, el menú flotante se queda donde estaba
       y pierde de vista a su botón. Se lo vuelve a colocar en vez de
       cerrarlo: cerrar obligaba a abrirlo de nuevo por un scroll de dos
       píxeles, y además un evento de scroll encolado justo antes de abrirlo
       lo cerraba apenas aparecía. Si la fila ya se fue de la caja, ahí sí se
       cierra. Va en captura para alcanzar cualquier caja sin engancharse a
       cada una. */
    document.addEventListener('scroll', function () {
      var menu = document.querySelector('[data-kebab-menu]:not([hidden])');
      if (!menu) return;

      var wrap = menu.closest('[data-kebab]');
      var button = wrap && wrap.querySelector('[data-kebab-button]');
      var box = wrap && clippingBox(wrap);
      if (!button || !box) return;

      closeFlyouts(null);

      var anchor = button.getBoundingClientRect();
      var limit = box.getBoundingClientRect();

      if (anchor.bottom < limit.top || anchor.top > limit.bottom) {
        closeKebabs();
        return;
      }

      placeFloating(button, menu, box);
    }, true);

    /* ── Submenú lateral del kebab ────────────────────────────────────
       "Supervisor" abre al costado, como el menú del escritorio de Windows:
       el rol y su alcance se eligen en un solo gesto. Se abre al pasar por
       encima y también al hacer clic o con Enter, para que sirva con
       teclado y en pantallas táctiles, donde no hay hover. */
    function openFlyout(sub) {
      if (sub.classList.contains('is-open')) return;

      closeFlyouts(sub);
      sub.classList.add('is-open');

      var button = sub.querySelector('[data-kebab-sub-button]');
      if (button) button.setAttribute('aria-expanded', 'true');

      var flyout = sub.querySelector('[data-kebab-flyout]');
      if (!flyout) return;

      /* Se ancla a la pantalla, igual que el menú padre. Posicionado como
         hijo quedaba atrapado en el contexto de apilado del menú y lo
         recortaba la caja con scroll del modal: se dibujaba por detrás de
         las tarjetas de abajo. En coordenadas de pantalla siempre queda
         completo y por encima de todo. */
      var anchor = button ? button.getBoundingClientRect() : sub.getBoundingClientRect();
      var pane = sub.parentElement ? sub.parentElement.closest('[data-kebab-flyout], [data-kebab-menu]') : null;
      var box = clippingBox(sub) || document.documentElement;
      var gap = 10;

      flyout.style.position = 'fixed';
      flyout.style.left = '0px';
      flyout.style.right = 'auto';
      flyout.style.top = '0px';

      var width = flyout.offsetWidth;
      var height = flyout.offsetHeight;
      var menuRect = pane ? pane.getBoundingClientRect() : anchor;
      var viewport = viewportWidth();

      // Sin medidas reales no se puede elegir el lado: queda el absolute de
      // la hoja, que lo pone al costado de su fila.
      if (!width || !height || !menuRect.width || !viewport) {
        flyout.style.position = '';
        flyout.style.left = '';
        flyout.style.right = '';
        flyout.style.top = '';
        return;
      }

      /* El kebab está pegado al borde derecho de la ventana, así que a la
         derecha casi nunca hay lugar: se elige el lado que tenga más aire
         en vez de asumir uno. */
      var roomRight = viewport - gap - menuRect.right;
      var roomLeft = menuRect.left - gap;
      var left;

      if (width <= roomRight || roomRight >= roomLeft) {
        left = menuRect.right - gap;
      } else {
        left = menuRect.left + gap - width;
        sub.classList.add('is-flipped');
      }

      left = Math.max(gap, Math.min(left, viewport - width - gap));

      // Alineado con su fila, sin salirse de la caja que lo contiene.
      var limit = box.getBoundingClientRect();
      var top = Math.min(anchor.top - 6, limit.bottom - height - gap);
      top = Math.max(top, limit.top + gap);

      var origin = fixedOrigin(flyout);
      flyout.style.left = Math.round(left - origin.x) + 'px';
      flyout.style.top = Math.round(top - origin.y) + 'px';
    }

    document.addEventListener('mouseover', function (event) {
      var menu = event.target.closest('[data-kebab-menu]');
      if (!menu) return;

      var sub = event.target.closest('[data-kebab-sub]');
      if (sub) openFlyout(sub);
      else closeFlyouts(null); // otra opción del mismo menú: se repliega
    });

    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-kebab-sub-button]');
      if (!button) return;

      // El padre del submenú no ejecuta nada: solo abre las opciones.
      event.preventDefault();
      event.stopPropagation();
      openFlyout(button.closest('[data-kebab-sub]'));
    });

    /* ── Bloques que se despliegan desde un botón ([data-disclosure]) ──
       Mismo gesto que "Cambiar contraseña" en Administrar cuenta: el panel
       arranca oculto y sus campos se vacían al cerrarlo, así cerrar equivale
       a "no lo voy a tocar" y el backend no recibe un cambio a medio hacer.

       Al contraer se espera a que termine la animación de salida antes de
       ocultarlo: con hidden puesto de una, el panel desaparecía de golpe y
       solo se veía animar la apertura. */
    function closeDisclosure(panel, button) {
      if (panel.hidden || panel.dataset.closing === '1') return;

      button.setAttribute('aria-expanded', 'false');
      button.classList.remove('is-open');

      var finish = function () {
        delete panel.dataset.closing;
        panel.classList.remove('is-closing');
        panel.hidden = true;
        panel.querySelectorAll('input').forEach(function (input) {
          if (input.type !== 'hidden') input.value = '';
        });
      };

      if (reduceMotion.matches) {
        finish();
        return;
      }

      panel.dataset.closing = '1';
      panel.classList.add('is-closing');

      var done = false;
      panel.addEventListener('animationend', function () {
        if (done) return;
        done = true;
        finish();
      }, { once: true });
      // Red de seguridad por si la animación no dispara.
      window.setTimeout(function () { if (!done) { done = true; finish(); } }, 320);
    }

    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-disclosure]');
      if (!button) return;

      var panel = document.getElementById(button.getAttribute('data-disclosure'));
      if (!panel) return;

      event.stopPropagation();

      if (panel.hidden || panel.dataset.closing === '1') {
        delete panel.dataset.closing;
        panel.classList.remove('is-closing');
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        button.classList.add('is-open');

        var first = panel.querySelector('input, select, textarea');
        if (first) first.focus();
        return;
      }

      closeDisclosure(panel, button);
    });

    /* Un admin no tiene permiso que elegir: manda sobre todo el espacio. */
    document.addEventListener('change', function (event) {
      var select = event.target.closest('[data-invite-role]');
      if (!select) return;

      var form = select.closest('form');
      var permission = form && form.querySelector('[data-invite-permission]');
      if (permission) permission.hidden = select.value === 'admin_principal';
    });

    /* ── Toasts (esquina superior derecha, con línea de duración) ──── */
    var toastHost = null;

    function toastContainer() {
      if (!toastHost) {
        toastHost = document.createElement('div');
        toastHost.className = 'pv-toasts';
        document.body.appendChild(toastHost);
      }
      return toastHost;
    }

    /* action (opcional): { label, onClick }. Se usa para los "Deshacer"
       que aparecen después de mover, archivar o eliminar algo. */
    function showToast(message, type, duration, action) {
      duration = duration || 3500;
      type = ['success', 'error', 'warning', 'info'].indexOf(type) === -1 ? 'success' : type;
      var toast = document.createElement('div');
      toast.className = 'pv-toast pv-toast--' + type;
      toast.setAttribute('role', 'status');

      var text = document.createElement('div');
      text.className = 'pv-toast__text';
      text.textContent = String(message);

      var bar = document.createElement('span');
      bar.className = 'pv-toast__bar';
      bar.setAttribute('aria-hidden', 'true');
      bar.style.animationDuration = duration + 'ms';

      toast.appendChild(text);

      var timer = null;
      var dismiss = function () {
        window.clearTimeout(timer);
        toast.classList.add('is-leaving');
        window.setTimeout(function () { toast.remove(); }, 260);
      };

      if (action && action.label && typeof action.onClick === 'function') {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'pv-toast__action';
        button.textContent = action.label;
        button.addEventListener('click', function () {
          dismiss();
          action.onClick();
        });
        toast.appendChild(button);
      }

      toast.appendChild(bar);
      toastContainer().appendChild(toast);

      timer = window.setTimeout(dismiss, duration);
      return toast;
    }

    window.PrevcsToast = { show: showToast };

    // Los flash banners del server (.auth-alert) se convierten en toasts.
    document.querySelectorAll('.content-area > .auth-alert').forEach(function (alert) {
      var type = alert.classList.contains('auth-alert--error') ? 'error' : 'success';
      var message = (alert.innerText || alert.textContent || '').trim();
      alert.remove();
      if (message) showToast(message, type);
    });

    /* ── Modales (apertura/cierre con animación de salida) ────────── */
    var confirmModal = document.querySelector('[data-confirm-modal]');
    var pendingAccept = null;

    function requestConfirm(text, onAccept) {
      if (!confirmModal) {
        if (window.confirm(text)) onAccept();
        return;
      }
      pendingAccept = onAccept;
      var confirmText = confirmModal.querySelector('[data-confirm-text]');
      if (confirmText) confirmText.textContent = text;
      openModal(confirmModal);
    }

    /* Bloqueo del scroll de fondo por evento, sin recortar la raíz.

       Recortar <html> (o <body>) manda la página al principio del documento
       de un salto, y con la página movida la topbar y el menú del costado
       —los dos position:sticky— se dibujan en otro lado: parecía que la
       navegación desaparecía al abrir cualquier ventana. Frenando la rueda
       y el arrastre táctil el fondo queda quieto donde estaba. */
    /* Se deja pasar la rueda dentro de la ventana abierta y también dentro
       de cualquier caja que maneje su propio scroll (los menús del tablero
       viven colgados del body, fuera del modal, y tienen que poder
       desplazarse). */
    function ownsScroll(node) {
      if (!node || !node.closest) return false;
      var modal = node.closest('[data-space-modal]');
      if (modal && !modal.hidden) return true;

      return !!node.closest('[data-lenis-prevent], .kb-menu, .pv-toasts');
    }

    function blockBackgroundScroll(event) {
      if (!document.documentElement.classList.contains('has-modal-open')) return;
      if (ownsScroll(event.target)) return;
      event.preventDefault();
    }

    window.addEventListener('wheel', blockBackgroundScroll, { passive: false });
    window.addEventListener('touchmove', blockBackgroundScroll, { passive: false });

    function syncBody() {
      var open = !!document.querySelector('[data-space-modal]:not([hidden])');
      document.documentElement.classList.toggle('has-modal-open', open);
      document.body.classList.toggle('has-modal-open', open);
      // Lenis scrollea por su cuenta: mientras hay una ventana abierta se
      // para, y así deja de responder a la rueda y al touch.
      if (window.PCSSmoothScroll) {
        if (open) window.PCSSmoothScroll.stop();
        else window.PCSSmoothScroll.start();
      }
    }

    function openModal(modal) {
      if (!modal) return;
      modal.classList.remove('is-closing');
      modal.hidden = false;
      // El cuerpo puede haberse creado después del arranque: engancharlo
      // acá es idempotente y garantiza la inercia en cualquier ventana.
      bindSmoothBox(modal.querySelector('.space-modal__body'));
      syncBody();
    }

    function closeModal(modal, force) {
      if (!modal || modal.hidden || modal.classList.contains('is-closing')) return;
      // Aviso de cambios sin guardar antes de cerrar la ventana.
      if (!force && modal !== confirmModal && modal.querySelector('form[data-warn-unsaved][data-dirty="1"]')) {
        requestConfirm('Tenés cambios sin guardar. Si cerrás ahora, se van a perder.', function () {
          closeModal(modal, true);
        });
        return;
      }
      var dialog = modal.querySelector('.space-modal__dialog');
      var finish = function () {
        modal.classList.remove('is-closing');
        modal.hidden = true;
        syncBody();
      };
      if (!dialog || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        finish();
        return;
      }
      modal.classList.add('is-closing');
      var done = false;
      dialog.addEventListener('animationend', function () {
        if (done) return;
        done = true;
        finish();
      }, { once: true });
      // Red de seguridad por si la animación no dispara (estilos pisados, etc.)
      setTimeout(function () { if (!done) { done = true; finish(); } }, 320);
    }

    document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.stopPropagation();
        openModal(document.getElementById(btn.getAttribute('data-open-modal')));
      });
    });

    document.querySelectorAll('[data-modal-dismiss]').forEach(function (el) {
      el.addEventListener('click', function () {
        closeModal(el.closest('[data-space-modal]'));
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        document.querySelectorAll('[data-space-modal]:not([hidden])').forEach(closeModal);
      }
    });

    syncBody();
    window.PrevcsModal = { open: openModal, close: closeModal };

    /* ── Secciones en lectura / edición ([data-space-edit-section]) ─── */
    function resetForm(form) {
      form.reset();
      delete form.dataset.dirty;
      closeSectionPanels(form);
    }

    function closeSectionPanels(root) {
      root.querySelectorAll('[data-disclosure-panel]').forEach(function (panel) {
        panel.hidden = true;
        panel.classList.remove('is-closing');
        delete panel.dataset.closing;
      });
      root.querySelectorAll('[data-disclosure]').forEach(function (button) {
        button.classList.remove('is-open');
        button.setAttribute('aria-expanded', 'false');
      });
    }

    function rememberFormValues(form) {
      form.querySelectorAll('input, textarea').forEach(function (field) {
        field.defaultValue = field.value;
      });
      form.querySelectorAll('select').forEach(function (field) {
        Array.prototype.forEach.call(field.options, function (option) {
          option.defaultSelected = option.selected;
        });
      });
    }

    function setEditMode(section, editing, reset) {
      var view = section.querySelector('[data-view-panel]');
      var form = section.querySelector('[data-edit-panel]');
      var button = section.querySelector('[data-edit-toggle]');

      if (!form || !view) return;
      if (!editing && reset) resetForm(form);
      if (!editing) closeSectionPanels(section);

      section.classList.toggle('is-editing', editing);
      view.hidden = editing;
      form.hidden = !editing;
      if (button) {
        button.classList.toggle('is-open', editing);
        button.setAttribute('aria-expanded', editing ? 'true' : 'false');
      }

      if (editing) {
        window.setTimeout(function () {
          var first = form.querySelector('input, select, textarea');
          if (first) first.focus();
        }, reduceMotion.matches ? 0 : 180);
      }
    }

    function closeEditSection(section, reset) {
      var form = section.querySelector('[data-edit-panel]');
      if (reset && form && form.dataset.dirty === '1') {
        requestConfirm('Tenés cambios sin guardar. Si cancelás ahora, se van a perder.', function () {
          setEditMode(section, false, true);
        });
        return;
      }
      setEditMode(section, false, reset);
    }

    document.addEventListener('click', function (event) {
      var editButton = event.target.closest('[data-edit-toggle]');
      if (editButton) {
        var editSection = editButton.closest('[data-space-edit-section]');
        if (!editSection) return;
        event.preventDefault();
        setEditMode(editSection, true, false);
        return;
      }

      var cancelButton = event.target.closest('[data-edit-cancel]');
      if (cancelButton) {
        var cancelSection = cancelButton.closest('[data-space-edit-section]');
        if (!cancelSection) return;
        event.preventDefault();
        closeEditSection(cancelSection, true);
      }
    });

    /* ── Cambios sin guardar: marca los forms editados ([data-warn-unsaved]) ── */
    document.querySelectorAll('[data-space-modal] form[data-warn-unsaved]').forEach(function (form) {
      form.addEventListener('input', function () { form.dataset.dirty = '1'; });
      form.addEventListener('submit', function () { delete form.dataset.dirty; });
    });

    /* ── Acordeón de espacios ─────────────────────────────────────── */
    document.querySelectorAll('[data-acc]').forEach(function (acc) {
      var head = acc.querySelector('[data-acc-toggle]');
      if (!head) return;

      head.setAttribute('aria-expanded', acc.classList.contains('is-open') ? 'true' : 'false');

      function toggle() {
        var open = acc.classList.toggle('is-open');
        head.setAttribute('aria-expanded', open ? 'true' : 'false');
      }

      head.addEventListener('click', function (event) {
        // Los controles internos (kebab, links, botones del modal) no pliegan el card.
        if (event.target.closest('a, [data-kebab], [data-open-modal]')) return;
        toggle();
      });
      head.addEventListener('keydown', function (event) {
        if ((event.key === 'Enter' || event.key === ' ') && event.target === head) {
          event.preventDefault();
          toggle();
        }
      });
    });

    /* ── Envío de formularios sin recargar ([data-ajax]) ──────────────

       Antes cada acción del modal terminaba en un redirect: el navegador
       recargaba la página entera, la ventana se cerraba y volvía a abrirse
       sola por el ?config=<id>, y en el camino se perdían el scroll y lo
       que estuviera a medio escribir. Ahora el POST va por fetch y el
       servidor contesta con las regiones ya dibujadas de nuevo; el modal
       se queda abierto y solo cambia lo que cambió.

       Va en cola por PrevcsCsrf: Security.php regenera el token en cada
       POST, así que dos envíos en paralelo harían fallar al segundo. */
    function regionRoot(name) {
      return document.querySelector('[data-region="' + name + '"]');
    }

    function swapRegions(regions) {
      if (!regions) return;

      Object.keys(regions).forEach(function (name) {
        var root = regionRoot(name);
        if (!root) return;

        root.innerHTML = regions[name];
        enhanceSelects(root);
      });
    }

    function applyPatch(patch) {
      if (!patch) return;

      Object.keys(patch).forEach(function (selector) {
        document.querySelectorAll(selector).forEach(function (node) {
          if ('value' in node) {
            node.value = patch[selector];
            node.defaultValue = patch[selector];
          } else {
            node.textContent = patch[selector];
          }
        });
      });
    }

    function submitAjax(form) {
      var submitter = form.querySelector('[type="submit"]');
      if (form.dataset.sending === '1') return;
      form.dataset.sending = '1';
      if (submitter) submitter.disabled = true;

      var body = new FormData(form);

      var run = function () {
        // El token viaja por header y no solo en el campo oculto: el del
        // formulario puede haber quedado viejo tras un POST anterior.
        body.set(csrfField(), window.PrevcsCsrf.get());

        return fetch(form.action, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': window.PrevcsCsrf.get()
          },
          body: body
        }).then(function (response) {
          return response.json().catch(function () { return {}; }).then(function (data) {
            if (data && data.csrf) window.PrevcsCsrf.set(data.csrf);
            if (!response.ok || !data || data.ok === false) {
              throw new Error((data && data.message) || 'No se pudieron guardar los cambios.');
            }
            return data;
          });
        });
      };

      window.PrevcsCsrf.queue(run).then(function (data) {
        delete form.dataset.sending;
        delete form.dataset.dirty;
        if (submitter) submitter.disabled = false;

        // Los campos de contraseña no se dejan escritos después de guardar.
        form.querySelectorAll('input[type="password"]').forEach(function (input) { input.value = ''; });
        if (form.querySelector('[data-delete-submit]')) {
          form.reset();
          window.PrevcsCsrf.set(window.PrevcsCsrf.get());
          form.querySelectorAll('[data-delete-submit]').forEach(function (button) {
            button.disabled = true;
          });
        }

        swapRegions(data.regions);
        applyPatch(data.patch);
        if (data.message) showToast(data.message, 'success', 3200);

        /* Lo que la página tenga que acomodar fuera de las regiones (las
           pestañas de sensores de la vista de una placa, por ejemplo) se
           engancha acá y trabaja con la respuesta ya aplicada. */
        document.dispatchEvent(new CustomEvent('prevcs:ajax-done', {
          detail: { form: form, data: data }
        }));

        if (form.hasAttribute('data-edit-panel')) {
          rememberFormValues(form);
          setEditMode(form.closest('[data-space-edit-section]'), false, false);
        }
      }, function (error) {
        delete form.dataset.sending;
        if (submitter) submitter.disabled = false;
        showToast(error.message, 'error', 5000);
      });
    }

    function csrfField() {
      var meta = document.querySelector('meta[name="csrf-field"]');
      return meta ? (meta.getAttribute('content') || 'csrf_test_name') : 'csrf_test_name';
    }

    document.addEventListener('change', function (event) {
      var toggle = event.target.closest('[data-device-access-toggle]');
      if (!toggle) return;

      var url = toggle.getAttribute('data-device-access-url');
      if (!url || toggle.disabled) return;

      var previous = !toggle.checked;
      toggle.disabled = true;

      var run = function () {
        var body = new FormData();
        body.set('allowed', toggle.checked ? '1' : '0');
        body.set(csrfField(), window.PrevcsCsrf.get());

        return fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': window.PrevcsCsrf.get()
          },
          body: body
        }).then(function (response) {
          return response.json().catch(function () { return {}; }).then(function (data) {
            if (data && data.csrf) window.PrevcsCsrf.set(data.csrf);
            if (!response.ok || !data || data.ok === false) {
              throw new Error((data && data.message) || 'No se pudo guardar el permiso.');
            }
            return data;
          });
        });
      };

      window.PrevcsCsrf.queue(run).then(function () {
        toggle.disabled = false;
      }, function () {
        toggle.checked = previous;
        toggle.disabled = false;
      });
    });

    /* Confirmación estilada + envío por fetch, los dos por delegación: las
       listas del modal se redibujan y los nodos nuevos tienen que seguir
       funcionando sin volver a engancharlos. */
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!(form instanceof HTMLFormElement)) return;

      var needsConfirm = form.hasAttribute('data-confirm') && form.dataset.confirmed !== '1';
      var isAjax = form.hasAttribute('data-ajax');

      if (!needsConfirm && !isAjax) return;

      event.preventDefault();

      if (needsConfirm) {
        requestConfirm(form.getAttribute('data-confirm'), function () {
          form.dataset.confirmed = '1';
          if (isAjax) {
            delete form.dataset.confirmed;
            submitAjax(form);
          } else {
            form.requestSubmit ? form.requestSubmit() : form.submit();
          }
        });
        return;
      }

      delete form.dataset.confirmed;
      submitAjax(form);
    });

    if (confirmModal) {
      var confirmBtn = confirmModal.querySelector('[data-confirm-accept]');
      if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
          var accept = pendingAccept;
          pendingAccept = null;
          closeModal(confirmModal);
          if (accept) accept();
        });
      }
      confirmModal.querySelectorAll('[data-modal-dismiss]').forEach(function (el) {
        el.addEventListener('click', function () { pendingAccept = null; });
      });
    }

    /* ── Zona de peligro reutilizable ─────────────────────────────── */
    var COLLAPSE_MS = 340;

    function setCollapsed(panel, open) {
      if (!panel) return;

      var inner = panel.firstElementChild;
      if (inner) inner.style.overflow = 'hidden';
      panel.classList.toggle('is-open', open);

      window.clearTimeout(panel.espTimer);
      if (!open || !inner) return;

      panel.espTimer = window.setTimeout(function () {
        if (panel.classList.contains('is-open')) inner.style.overflow = 'visible';
      }, reduceMotion.matches ? 0 : COLLAPSE_MS);
    }

    function isCollapseOpen(panel) {
      return !!panel && panel.classList.contains('is-open');
    }

    document.addEventListener('click', function (event) {
      var danger = event.target.closest('[data-danger-toggle]');
      if (!danger) return;

      var modal = danger.closest('[data-space-modal]');
      var panelId = danger.getAttribute('aria-controls');
      var panel = panelId ? document.getElementById(panelId) : null;
      if (!panel && modal) panel = modal.querySelector('[data-danger-panel]');
      if (!panel) return;

      var showing = !isCollapseOpen(panel);
      setCollapsed(panel, showing);
      danger.classList.toggle('is-open', showing);
      danger.setAttribute('aria-expanded', showing ? 'true' : 'false');

      if (showing) {
        window.setTimeout(function () {
          var first = panel.querySelector('input, select, textarea');
          if (first) first.focus();
        }, reduceMotion.matches ? 0 : COLLAPSE_MS);
      }
    });

    document.querySelectorAll('[data-danger-panel]').forEach(function (panel) {
      var button = document.querySelector('[aria-controls="' + panel.id + '"]');
      if (button && isCollapseOpen(panel)) {
        button.classList.add('is-open');
        setCollapsed(panel, true);
      }
    });

    document.addEventListener('input', function (event) {
      var field = event.target.closest('[data-delete-name]');
      if (!field) return;

      var submit = field.closest('form').querySelector('[data-delete-submit]');
      if (submit) submit.disabled = field.value.trim() !== field.dataset.expectedName;
    });

    /* ── Section-switch: píldora negra deslizante ─────────────────── */
    document.querySelectorAll('.section-switch').forEach(function (sw) {
      var radios = sw.querySelectorAll('.section-switch__radio');
      if (!radios.length) return;

      var indicator = document.createElement('span');
      indicator.className = 'section-switch__indicator';
      indicator.setAttribute('aria-hidden', 'true');
      sw.prepend(indicator);
      sw.classList.add('section-switch--js');

      function place(animate) {
        var checked = sw.querySelector('input:checked');
        var radio = checked && checked.closest('.section-switch__radio');
        if (!radio) { indicator.style.opacity = '0'; return; }
        indicator.style.opacity = '1';
        if (!animate) indicator.classList.add('is-jump');
        indicator.style.width = radio.offsetWidth + 'px';
        indicator.style.transform = 'translateX(' + radio.offsetLeft + 'px)';
        if (!animate) {
          // Forzar reflow para que el salto inicial no se anime.
          void indicator.offsetWidth;
          indicator.classList.remove('is-jump');
        }
      }

      sw.addEventListener('change', function () { place(true); });
      window.addEventListener('resize', function () { place(false); });
      place(false);
    });

    /* ── Selects estilizados (.esp32-select) ──────────────────────────
       El <select> nativo queda oculto (sigue mandando el valor del form)
       y se reemplaza por un trigger + lista que se despliega y contrae
       con transición suave. ─────────────────────────────────────────── */
    function closeAllSelects(except) {
      document.querySelectorAll('.pv-select.is-open').forEach(function (open) {
        if (open !== except) {
          open.classList.remove('is-open');
          var btn = open.querySelector('.pv-select__trigger');
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    function enhanceSelect(select) {
      if (select.dataset.pvSelectDone === '1') return;
      select.dataset.pvSelectDone = '1';

      var wrap = document.createElement('div');
      wrap.className = 'pv-select';
      select.parentNode.insertBefore(wrap, select);
      wrap.appendChild(select);
      select.tabIndex = -1;
      select.setAttribute('aria-hidden', 'true');

      var trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'pv-select__trigger';
      trigger.setAttribute('aria-haspopup', 'listbox');
      trigger.setAttribute('aria-expanded', 'false');

      var label = document.createElement('span');
      label.className = 'pv-select__label';
      var caret = document.createElement('span');
      caret.className = 'pv-select__caret';
      caret.setAttribute('aria-hidden', 'true');
      trigger.appendChild(label);
      trigger.appendChild(caret);

      var list = document.createElement('ul');
      list.className = 'pv-select__list';
      list.setAttribute('role', 'listbox');

      function syncLabel() {
        var current = select.options[select.selectedIndex];
        var isPlaceholder = !current || current.disabled || current.value === '';
        label.textContent = current ? current.textContent.trim() : '';
        trigger.classList.toggle('is-placeholder', isPlaceholder);
        list.querySelectorAll('.pv-select__option').forEach(function (opt) {
          var selected = opt.dataset.value === select.value && !isPlaceholder;
          opt.classList.toggle('is-selected', selected);
          opt.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
      }

      Array.prototype.forEach.call(select.options, function (option) {
        if (option.disabled && option.value === '') return; // placeholder
        var item = document.createElement('li');
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pv-select__option';
        btn.setAttribute('role', 'option');
        btn.dataset.value = option.value;
        btn.textContent = option.textContent.trim();
        btn.addEventListener('click', function () {
          select.value = option.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          syncLabel();
          wrap.classList.remove('is-open');
          trigger.setAttribute('aria-expanded', 'false');
        });
        item.appendChild(btn);
        list.appendChild(item);
      });

      trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        var willOpen = !wrap.classList.contains('is-open');
        closeAllSelects(null);
        wrap.classList.toggle('is-open', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

        /* Adentro del modal la caja tiene scroll y recorta lo que se sale:
           si la lista no entra para abajo, se despliega hacia arriba. */
        if (!willOpen) {
          wrap.classList.remove('is-up');
          return;
        }

        var box = clippingBox(wrap);
        if (!box) return;

        wrap.classList.remove('is-up');
        if (list.getBoundingClientRect().bottom > box.getBoundingClientRect().bottom) {
          wrap.classList.add('is-up');
        }
      });

      wrap.appendChild(trigger);
      wrap.appendChild(list);
      select.addEventListener('change', syncLabel);
      syncLabel();
    }

    /* Se expone para volver a correrlo sobre las regiones que el servidor
       redibuja: los <select> que llegan en ese HTML todavía son nativos. */
    function enhanceSelects(root) {
      (root || document).querySelectorAll('select.esp32-select, select[data-pcs-select]').forEach(enhanceSelect);
    }

    enhanceSelects(document);
    window.PrevcsSelect = { enhance: enhanceSelects };

    document.addEventListener('click', function () { closeAllSelects(null); });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeAllSelects(null);
    });
  });
})();
