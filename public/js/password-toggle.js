/* ─── Password visibility toggle ───────────────────────────────────────────
   Añade un botón con icono de ojo a cada input[type="password"] para alternar
   entre "ver contraseña" y ocultarla. Se incluye globalmente desde el layout,
   así que cubre todas las vistas (login/registro, onboarding, reset, cuenta,
   espacios, token de ESP32...) y cualquier campo de contraseña futuro.
   --------------------------------------------------------------------------- */
(function () {
  'use strict';

  var EYE =
    '<svg class="pcs-pw-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' +
    ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"' +
    ' stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>' +
    '<circle cx="12" cy="12" r="3"/></svg>';

  var EYE_OFF =
    '<svg class="pcs-pw-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"' +
    ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"' +
    ' stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94' +
    'M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19' +
    'm-6.72-1.07a3 3 0 1 1-4.24-4.24"/>' +
    '<line x1="1" y1="1" x2="23" y2="23"/></svg>';

  var SHOW = 'Mostrar contraseña';
  var HIDE = 'Ocultar contraseña';

  function enhance(input) {
    if (!input || input.dataset.pcsPwDone === '1' || input.type !== 'password') return;
    input.dataset.pcsPwDone = '1';

    var wrap = document.createElement('span');
    wrap.className = 'pcs-pw-wrap';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pcs-pw-toggle';
    btn.setAttribute('aria-label', SHOW);
    btn.setAttribute('title', SHOW);
    btn.innerHTML = EYE + EYE_OFF;

    btn.addEventListener('click', function () {
      var reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      btn.classList.toggle('is-visible', reveal);
      btn.setAttribute('aria-label', reveal ? HIDE : SHOW);
      btn.setAttribute('title', reveal ? HIDE : SHOW);
      // Devuelve el foco al campo sin desplazar la página.
      try { input.focus({ preventScroll: true }); } catch (e) { input.focus(); }
    });

    wrap.appendChild(btn);
  }

  function init() {
    var inputs = document.querySelectorAll('input[type="password"]');
    for (var i = 0; i < inputs.length; i++) enhance(inputs[i]);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
