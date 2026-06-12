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

    /* ── Modales (apertura/cierre con animación de salida) ────────── */
    function syncBody() {
      document.body.classList.toggle('has-modal-open', !!document.querySelector('[data-space-modal]:not([hidden])'));
    }

    function openModal(modal) {
      if (!modal) return;
      modal.classList.remove('is-closing');
      modal.hidden = false;
      syncBody();
    }

    function closeModal(modal) {
      if (!modal || modal.hidden || modal.classList.contains('is-closing')) return;
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
    var confirmModal = document.querySelector('[data-confirm-modal]');
    if (confirmModal) {
      var confirmText = confirmModal.querySelector('[data-confirm-text]');
      var confirmBtn = confirmModal.querySelector('[data-confirm-accept]');
      var pendingForm = null;

      document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
          if (form.dataset.confirmed === '1') return; // ya confirmado
          event.preventDefault();
          pendingForm = form;
          if (confirmText) confirmText.textContent = form.getAttribute('data-confirm');
          openModal(confirmModal);
        });
      });

      if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
          if (!pendingForm) return;
          pendingForm.dataset.confirmed = '1';
          var form = pendingForm;
          pendingForm = null;
          closeModal(confirmModal);
          form.requestSubmit ? form.requestSubmit() : form.submit();
        });
      }
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
  });
})();
