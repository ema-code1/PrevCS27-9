/* =====================================================================
   PrevCS · Administrar cuenta
   - Despliega el bloque "Cambiar contraseña" desde el botón de la fila
     del email.
   - Foto de perfil: el círculo abre el explorador de archivos, valida
     peso y formato, y recorta la imagen a un cuadrado centrado antes de
     mandarla. El servidor vuelve a recortarla cuando tiene GD, así que la
     foto queda cuadrada resuelva lo que resuelva el navegador.
   ===================================================================== */
(function () {
  'use strict';

  /* ── Panel de contraseña ─────────────────────────────────────────── */
  (function () {
    var toggle = document.querySelector('[data-password-toggle]');
    var panel  = document.querySelector('[data-password-panel]');

    if (!toggle || !panel) return;

    toggle.addEventListener('click', function () {
      var opening = panel.hasAttribute('hidden');

      if (opening) {
        panel.removeAttribute('hidden');
        var first = panel.querySelector('input');
        if (first) first.focus();
      } else {
        panel.setAttribute('hidden', '');
        // Cerrar equivale a "no la voy a cambiar": limpiamos para que el
        // backend no crea que hay un cambio de contraseña en curso.
        Array.prototype.forEach.call(panel.querySelectorAll('input'), function (input) {
          input.value = '';
        });
      }

      toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
    });
  })();

  /* ── Foto de perfil ──────────────────────────────────────────────── */
  (function () {
    var picker = document.querySelector('[data-avatar-picker]');
    if (!picker) return;

    var input    = picker.querySelector('[data-avatar-input]');
    var preview  = picker.querySelector('[data-avatar-preview]');
    var initials = picker.querySelector('[data-avatar-initials]');
    if (!input || !preview) return;

    var ALLOWED  = ['image/png', 'image/jpeg', 'image/webp'];
    var SIDE     = 512; // mismo lado que usa el recorte del servidor
    var maxBytes = parseInt(input.getAttribute('data-avatar-max'), 10) || (5 * 1024 * 1024);
    var maxLabel = (Math.round((maxBytes / (1024 * 1024)) * 10) / 10)
      .toString()
      .replace('.', ',');

    var notice    = null;
    var objectUrl = null;

    function clearNotice() {
      if (notice && notice.parentNode) notice.parentNode.removeChild(notice);
      notice = null;
    }

    function report(message) {
      clearNotice();

      var form = document.querySelector('.content-area form');
      if (!form || !form.parentNode) return;

      notice = document.createElement('div');
      notice.className = 'acct-alert acct-alert--error';
      notice.setAttribute('role', 'alert');
      notice.textContent = message;
      form.parentNode.insertBefore(notice, form);
    }

    function show(url) {
      preview.src = url;
      preview.alt = 'Foto de perfil';
      preview.hidden = false;
      if (initials) initials.hidden = true;
    }

    /* Recorte cuadrado centrado antes de subir. El servidor vuelve a
       recortar si tiene GD; esto asegura el cuadrado igual y de paso sube
       mucho menos peso. Si algo no está disponible, se manda el original. */
    function squareCrop(file, done) {
      if (!window.URL || !window.DataTransfer || !document.createElement('canvas').toBlob) {
        done(null);
        return;
      }

      var url = URL.createObjectURL(file);
      var img = new Image();

      img.onload = function () {
        var side = Math.min(img.naturalWidth, img.naturalHeight);
        if (!side) {
          URL.revokeObjectURL(url);
          done(null);
          return;
        }

        var target = Math.min(side, SIDE);
        var canvas = document.createElement('canvas');
        canvas.width  = target;
        canvas.height = target;

        canvas.getContext('2d').drawImage(
          img,
          (img.naturalWidth - side) / 2,
          (img.naturalHeight - side) / 2,
          side,
          side,
          0,
          0,
          target,
          target
        );

        URL.revokeObjectURL(url);

        // JPG no soporta transparencia: PNG y WEBP se mantienen como están.
        var type = file.type === 'image/png' || file.type === 'image/webp' ? file.type : 'image/jpeg';

        canvas.toBlob(function (blob) {
          if (!blob) {
            done(null);
            return;
          }

          try {
            var name = 'avatar.' + (type === 'image/png' ? 'png' : (type === 'image/webp' ? 'webp' : 'jpg'));
            var transfer = new DataTransfer();
            transfer.items.add(new File([blob], name, { type: type }));
            done(transfer.files);
          } catch (e) {
            done(null);
          }
        }, type, 0.92);
      };

      img.onerror = function () {
        URL.revokeObjectURL(url);
        done(null);
      };

      img.src = url;
    }

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;

      if (ALLOWED.indexOf(file.type) === -1) {
        input.value = '';
        report('Formato no permitido: usá JPG, PNG o WEBP.');
        return;
      }

      if (file.size > maxBytes) {
        input.value = '';
        report('La imagen supera los ' + maxLabel + ' MB. Elegí una más liviana.');
        return;
      }

      clearNotice();

      squareCrop(file, function (files) {
        if (files) input.files = files;

        var current = input.files && input.files[0] ? input.files[0] : file;

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(current);
        show(objectUrl);
      });
    });

    window.addEventListener('pagehide', function () {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    });
  })();
})();
