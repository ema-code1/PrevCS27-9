/* =====================================================================
   PrevCS · Stock — zona de importacion (drag & drop)
   Solo acepta planillas de Excel: .xlsx, .xls, .xlsm y .xlsb. La validacion
   va por extension porque el MIME que manda el sistema operativo al soltar
   un archivo es poco confiable (Windows suele mandarlo vacio).
   El archivo se valida y se muestra en la vista; el envio al server queda
   para cuando exista el endpoint de importacion.
   ===================================================================== */
(function () {
  'use strict';

  var EXTENSIONS = ['xlsx', 'xls', 'xlsm', 'xlsb'];

  // Se usan solo para adelantar el rechazo mientras el archivo se arrastra:
  // si el navegador declara un tipo y no es de Excel, la zona se pinta roja.
  var MIME_TYPES = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'application/vnd.ms-excel.sheet.macroEnabled.12',
    'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.template'
  ];

  function extensionOf(name) {
    var dot = String(name).lastIndexOf('.');
    return dot < 0 ? '' : String(name).slice(dot + 1).toLowerCase();
  }

  function isExcel(file) {
    return EXTENSIONS.indexOf(extensionOf(file.name)) !== -1;
  }

  function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function toast(message, type) {
    if (window.PrevcsToast) window.PrevcsToast.show(message, type);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var zone = document.querySelector('[data-dropzone]');
    if (!zone) return;

    var input     = zone.querySelector('[data-dropzone-input]');
    var trigger   = zone.querySelector('[data-dropzone-trigger]');
    var section   = zone.closest('.stock-upload') || document;
    var fileBox   = section.querySelector('[data-dropzone-file]');
    var fileName  = section.querySelector('[data-dropzone-file-name]');
    var fileSize  = section.querySelector('[data-dropzone-file-size]');
    var fileClear = section.querySelector('[data-dropzone-file-remove]');
    var errorBox  = section.querySelector('[data-dropzone-error]');
    var errorText = section.querySelector('[data-dropzone-error-text]');

    // dragenter/dragleave se disparan tambien al pasar sobre los hijos de la
    // zona: se cuentan las entradas para no apagar el resaltado de mas.
    var depth = 0;

    function showError(message) {
      if (errorText) errorText.textContent = message;
      if (errorBox) errorBox.hidden = false;
      toast(message, 'error');
    }

    function clearError() {
      if (errorBox) errorBox.hidden = true;
    }

    function showFile(file) {
      if (fileName) fileName.textContent = file.name;
      if (fileSize) fileSize.textContent = formatSize(file.size);
      if (fileBox) fileBox.hidden = false;
    }

    function resetFile() {
      if (fileBox) fileBox.hidden = true;
      if (input) input.value = '';
    }

    function accept(files) {
      if (!files || files.length === 0) return;

      if (files.length > 1) {
        resetFile();
        showError('Soltá un solo archivo por vez.');
        return;
      }

      var file = files[0];
      if (!isExcel(file)) {
        resetFile();
        showError('"' + file.name + '" no es una planilla de Excel. Aceptamos .xlsx, .xls, .xlsm y .xlsb.');
        return;
      }

      clearError();
      showFile(file);
      toast('Planilla lista: ' + file.name, 'success');
    }

    /* ── Abrir el explorador ──────────────────────────────────────── */
    if (trigger && input) {
      trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        input.click();
      });
    }

    zone.addEventListener('click', function (event) {
      if (input && event.target !== input) input.click();
    });

    if (input) {
      input.addEventListener('change', function () {
        accept(input.files);
      });
    }

    if (fileClear) {
      fileClear.addEventListener('click', function () {
        resetFile();
        clearError();
      });
    }

    /* ── Arrastre sobre la zona ───────────────────────────────────── */
    // Adelanta el rechazo cuando el navegador declara el tipo del archivo
    // arrastrado. Si viene vacio no se puede saber todavia: se deja neutro y
    // decide accept() cuando el archivo cae.
    function draggedLooksInvalid(dt) {
      if (!dt || !dt.items) return false;
      for (var i = 0; i < dt.items.length; i++) {
        var item = dt.items[i];
        if (item.kind !== 'file') return true;
        if (item.type && MIME_TYPES.indexOf(item.type) === -1) return true;
      }
      return false;
    }

    function paint(state) {
      zone.classList.toggle('is-dragging', state === 'ok');
      zone.classList.toggle('is-invalid', state === 'invalid');
      if (trigger) trigger.classList.toggle('is-active', state === 'ok');
    }

    zone.addEventListener('dragenter', function (event) {
      event.preventDefault();
      depth++;
      paint(draggedLooksInvalid(event.dataTransfer) ? 'invalid' : 'ok');
    });

    zone.addEventListener('dragover', function (event) {
      event.preventDefault();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = zone.classList.contains('is-invalid') ? 'none' : 'copy';
      }
    });

    zone.addEventListener('dragleave', function () {
      depth = Math.max(0, depth - 1);
      if (depth === 0) paint(null);
    });

    zone.addEventListener('drop', function (event) {
      event.preventDefault();
      depth = 0;
      paint(null);
      accept(event.dataTransfer ? event.dataTransfer.files : null);
    });

    // Fuera de la zona el navegador abriria el archivo soltado y se perderia
    // la pagina: se cancela el drop en el resto del documento.
    ['dragover', 'drop'].forEach(function (type) {
      document.addEventListener(type, function (event) {
        if (!zone.contains(event.target)) event.preventDefault();
      });
    });
  });
})();
