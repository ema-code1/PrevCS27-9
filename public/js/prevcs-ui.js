/* =====================================================================
   PrevCS · UI compartida (vistas IoT)
   - Modales emergentes con animación de entrada/salida ([data-space-modal]).
   - Kebab de opciones por tarjeta ([data-kebab]).
   - Acordeón de espacios ([data-acc]).
   - Confirmación estilada para acciones destructivas (form[data-confirm]).
   - Indicador deslizante del section-switch (píldora negra).
   ===================================================================== */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    /* ── Kebab (menú de opciones por tarjeta) ─────────────────────── */
    document.querySelectorAll('[data-kebab]').forEach(function (wrap) {
      var button = wrap.querySelector('[data-kebab-button]');
      var menu = wrap.querySelector('[data-kebab-menu]');
      if (!button || !menu) return;

      button.addEventListener('click', function (event) {
        event.stopPropagation();
        var willOpen = menu.hidden;
        document.querySelectorAll('[data-kebab-menu]').forEach(function (m) { m.hidden = true; });
        document.querySelectorAll('[data-kebab-button]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
        menu.hidden = !willOpen;
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });

    document.addEventListener('click', function () {
      document.querySelectorAll('[data-kebab-menu]').forEach(function (m) { m.hidden = true; });
      document.querySelectorAll('[data-kebab-button]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
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
      var toast = document.createElement('div');
      toast.className = 'pv-toast pv-toast--' + (type === 'error' ? 'error' : 'success');
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

    function syncBody() {
      var open = !!document.querySelector('[data-space-modal]:not([hidden])');
      // El bloqueo del scroll vive en <html> (ver space-modal.css): en el
      // body rompería el sticky de la topbar. La clase también se deja en
      // el body para lo que quiera reaccionar a "hay un modal abierto".
      document.documentElement.classList.toggle('has-modal-open', open);
      document.body.classList.toggle('has-modal-open', open);
      // Lenis scrollea con transforms e ignora overflow:hidden: hay que frenarlo.
      if (window.PCSSmoothScroll) {
        if (open) window.PCSSmoothScroll.stop();
        else window.PCSSmoothScroll.start();
      }
    }

    function openModal(modal) {
      if (!modal) return;
      modal.classList.remove('is-closing');
      modal.hidden = false;
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

    /* ── Confirmación estilada para forms destructivos ────────────── */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (form.dataset.confirmed === '1') return; // ya confirmado
        event.preventDefault();
        requestConfirm(form.getAttribute('data-confirm'), function () {
          form.dataset.confirmed = '1';
          form.requestSubmit ? form.requestSubmit() : form.submit();
        });
      });
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

    document.querySelectorAll('select.esp32-select').forEach(function (select) {
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
      });

      wrap.appendChild(trigger);
      wrap.appendChild(list);
      select.addEventListener('change', syncLabel);
      syncLabel();
    });

    document.addEventListener('click', function () { closeAllSelects(null); });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeAllSelects(null);
    });
  });
})();
