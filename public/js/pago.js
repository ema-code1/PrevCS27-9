/* PrevCS · Vista de Pago
   - Entrada animada con GSAP (degrada con gracia sin GSAP / reduced-motion).
   - Selección de método de pago.
   - Mercado Pago: suscripción real a PrevCS Pro (Suscripciones con plan). La
     tarjeta se escribe en los campos seguros de MercadoPago.js (iframes) y al
     servidor solo llega un token de un solo uso; el precio lo pone el servidor.
   - PayPal: sigue simulado y lleva al registro (integración pendiente). */
(() => {
  'use strict';

  // Siguiente paso del flujo: Pago -> Registro (resuelto contra la URL actual).
  const NEXT_URL = new URL('reg-log?form=register', window.location.href).href;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const hasGSAP = typeof window.gsap !== 'undefined';

  /* ---------- Entrada animada ---------- */
  const animEls = document.querySelectorAll('[data-anim]');
  if (!hasGSAP || reduce) {
    animEls.forEach((el) => { el.style.opacity = '1'; });
  } else {
    const { gsap } = window;
    gsap.fromTo('.pay-form-col',
      { opacity: 0, y: 28 },
      { opacity: 1, y: 0, duration: 0.6, ease: 'power3.out' });
    gsap.fromTo('.pay-summary-col',
      { opacity: 0, y: 28 },
      { opacity: 1, y: 0, duration: 0.6, delay: 0.12, ease: 'power3.out' });
    gsap.from('.pay-method', {
      opacity: 0, y: 16, duration: 0.45, delay: 0.25, stagger: 0.1, ease: 'power2.out',
    });
  }

  /* ---------- Micro-interacción al elegir método ---------- */
  const methods = document.querySelectorAll('.pay-method input[type="radio"]');
  methods.forEach((input) => {
    input.addEventListener('change', () => {
      if (!hasGSAP || reduce) return;
      const inner = input.closest('.pay-method')?.querySelector('.pay-method-inner');
      if (inner) {
        window.gsap.fromTo(inner,
          { scale: 0.98 },
          { scale: 1, duration: 0.3, ease: 'back.out(2)' });
      }
    });
  });

  const form = document.getElementById('payForm');
  const btn = document.getElementById('payConfirm');
  if (!form || !btn) return;

  const label = btn.querySelector('.pay-submit-label');
  const nameInput = document.getElementById('pay-name');
  const emailInput = document.getElementById('pay-email');
  const cardBlock = document.getElementById('payCardBlock');
  const statusBox = document.getElementById('payStatus');
  const secureNote = document.getElementById('paySecureNote');
  const modal = document.getElementById('payModal');

  const selectedMethod = () => form.querySelector('input[name="method"]:checked')?.value || 'mercadopago';
  const isMercadoPago = () => selectedMethod() === 'mercadopago';
  const idleLabel = () => (isMercadoPago() ? 'Suscribirme' : 'Confirmar pago');

  /* ---------- Mensajes de estado (cargando / pendiente / error) ---------- */
  function showStatus(kind, title, text) {
    if (!statusBox) return;
    statusBox.dataset.kind = kind;
    statusBox.querySelector('.pay-status-title').textContent = title;
    statusBox.querySelector('.pay-status-text').textContent = text || '';
    statusBox.hidden = false;
  }

  function clearStatus() {
    if (statusBox) statusBox.hidden = true;
  }

  function shakeForm() {
    if (!hasGSAP || reduce) return;
    window.gsap.fromTo(form, { x: -6 }, { x: 0, duration: 0.4, ease: 'elastic.out(1, 0.4)' });
  }

  /* ---------- Mercado Pago · campos seguros (CardForm) ---------- */
  const SECURE_HOSTS = [
    { id: 'pay-cardNumber', labelId: 'pay-cardNumber-label' },
    { id: 'pay-expirationDate', labelId: 'pay-expirationDate-label' },
    { id: 'pay-securityCode', labelId: 'pay-securityCode-label' },
  ];

  let cardForm = null;
  let mounting = null;
  let mounted = false;
  let cardReady = false;
  let processing = false;

  async function loadPublicKey() {
    let response;
    try {
      response = await fetch(form.dataset.mpConfigUrl, {
        cache: 'no-store',
        headers: { Accept: 'application/json' },
      });
    } catch {
      throw new Error('No pudimos conectar con el servidor para cargar Mercado Pago. Recargá la página.');
    }

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || !payload.publicKey) {
      throw new Error(payload.error || 'Falta configurar MP_PUBLIC_KEY en el servidor.');
    }
    return payload.publicKey;
  }

  function assertSecureFieldsMounted() {
    const failures = [];

    SECURE_HOSTS.forEach(({ id, labelId }) => {
      const host = document.getElementById(id);
      const hostLabel = document.getElementById(labelId);
      const frames = host ? host.querySelectorAll('iframe') : [];
      const rect = host ? host.getBoundingClientRect() : { width: 0, height: 0 };

      if (!host) failures.push(`${id}: falta el contenedor`);
      else if (frames.length !== 1) failures.push(`${id}: el iframe no se montó exactamente una vez`);
      if (!hostLabel || hostLabel.getClientRects().length === 0) failures.push(`${id}: falta la etiqueta visible`);
      if (!rect.width || !rect.height) failures.push(`${id}: contenedor oculto o sin tamaño`);
      if (host && getComputedStyle(host).pointerEvents === 'none') failures.push(`${id}: contenedor no interactivo`);
      if (frames[0] && getComputedStyle(frames[0]).pointerEvents === 'none') failures.push(`${id}: iframe no interactivo`);
      if (host && host.closest('[inert], [aria-disabled="true"]')) failures.push(`${id}: un contenedor padre está deshabilitado`);
    });

    if (failures.length) {
      console.error('[Mercado Pago] Campos seguros:', failures.join('; '));
      showStatus('error', 'No se pudieron cargar los campos de la tarjeta',
        'Recargá la página. Si usás un bloqueador de anuncios, desactivalo para este sitio.');
      return false;
    }
    return true;
  }

  // Verifica los iframes con el bloque visible. rAF no corre con la pestaña en
  // segundo plano, por eso va acompañado de un setTimeout de respaldo.
  function verifySecureFields() {
    if (!mounted || cardReady || !cardBlock || cardBlock.hidden) return;
    let done = false;
    const check = () => {
      if (done) return;
      done = true;
      if (assertSecureFieldsMounted()) {
        cardReady = true;
        clearStatus();
      }
    };
    requestAnimationFrame(check);
    window.setTimeout(check, 400);
  }

  function mountCardForm() {
    if (cardForm || mounting || !cardBlock || cardBlock.hidden) return;

    mounting = (async () => {
      showStatus('loading', 'Inicializando el pago seguro…', 'Cargando los campos de Mercado Pago.');

      if (typeof window.MercadoPago === 'undefined') {
        showStatus('error', 'No se pudo cargar Mercado Pago',
          'Si usás un bloqueador de anuncios, desactivalo para este sitio y recargá la página.');
        mounting = null;
        return;
      }

      let publicKey;
      try {
        publicKey = await loadPublicKey();
      } catch (error) {
        showStatus('error', 'No se pudo iniciar el pago', error.message);
        mounting = null;
        return;
      }

      const mp = new window.MercadoPago(publicKey);
      cardForm = mp.cardForm({
        amount: form.dataset.amount,
        iframe: true,
        form: {
          id: 'payForm',
          cardNumber: { id: 'pay-cardNumber', placeholder: 'Número de tarjeta' },
          expirationDate: { id: 'pay-expirationDate', placeholder: 'MM/AA' },
          securityCode: { id: 'pay-securityCode', placeholder: 'CVV' },
          cardholderName: { id: 'pay-cardholderName' },
          issuer: { id: 'pay-issuer' },
          installments: { id: 'pay-installments' },
          identificationType: { id: 'pay-identificationType' },
          identificationNumber: { id: 'pay-identificationNumber' },
          cardholderEmail: { id: 'pay-email' },
        },
        callbacks: {
          onFormMounted: (error) => {
            if (error) {
              console.error('[Mercado Pago] onFormMounted:', error);
              showStatus('error', 'No se pudieron cargar los campos de la tarjeta', 'Recargá la página e intentá nuevamente.');
              return;
            }
            mounted = true;
            verifySecureFields();
          },
          onSubmit: (event) => {
            event.preventDefault();
            if (isMercadoPago()) subscribe();
          },
        },
      });

      // No dejar cajas vacías e inutilizables si el SDK quedó bloqueado.
      window.setTimeout(() => {
        const expirationHost = document.getElementById('pay-expirationDate');
        if (isMercadoPago() && !expirationHost?.querySelector('iframe')) {
          showStatus('error', 'No se pudieron cargar los campos de la tarjeta',
            'Recargá la página. Si usás un bloqueador de anuncios, desactivalo para este sitio.');
        }
      }, 8000);
    })();
  }

  // CardForm se monta solo con el bloque ya visible (nunca en display:none).
  // rAF no corre con la pestaña en segundo plano: el setTimeout es respaldo del
  // mismo intento, no un reintento (si falla, se reintenta al volver a elegir MP).
  let mountScheduled = false;
  function scheduleMount() {
    if (mountScheduled || cardForm || mounting) return;
    mountScheduled = true;
    const run = () => {
      if (!mountScheduled) return;
      mountScheduled = false;
      mountCardForm();
    };
    requestAnimationFrame(run);
    window.setTimeout(run, 150);
  }

  function setProcessing(on) {
    processing = on;
    btn.classList.toggle('is-loading', on);
    btn.disabled = on;
    if (label) label.textContent = on ? 'Procesando…' : idleLabel();
  }

  /* ---------- Crear la suscripción (una sola vez por envío) ---------- */
  function subscribe() {
    if (processing || !cardReady || btn.classList.contains('is-done')) return;

    if (!nameInput.checkValidity() || !emailInput.checkValidity()) {
      form.reportValidity();
      shakeForm();
      return;
    }

    const { token } = cardForm.getCardFormData();
    if (!token) {
      showStatus('error', 'Revisá los datos de la tarjeta',
        'Completá número, vencimiento, código de seguridad, nombre y DNI e intentá nuevamente.');
      shakeForm();
      return;
    }

    setProcessing(true);
    showStatus('loading', 'Procesando tu suscripción…', 'No cierres ni recargues esta página.');

    // El CSRF se regenera en cada POST: la mutación va en la cola compartida.
    window.PrevcsCsrf.queue(() => fetch(form.dataset.subscribeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': window.PrevcsCsrf.get(),
      },
      body: JSON.stringify({
        token,
        name: nameInput.value.trim(),
        email: emailInput.value.trim(),
      }),
    }).then(async (response) => {
      const data = await response.json().catch(() => ({}));
      if (data && data.csrf) window.PrevcsCsrf.set(data.csrf);
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Mercado Pago no pudo procesar la suscripción. Intentá nuevamente.');
      }
      return data.subscription;
    }))
      .then(handleSubscription)
      .catch((error) => {
        setProcessing(false);
        showStatus('error', 'No pudimos activar tu suscripción', error.message);
      });
  }

  function handleSubscription(subscription) {
    setProcessing(false);
    const status = subscription && subscription.status;
    const nextStep = form.dataset.loggedIn === '1'
      ? 'Ya podés volver a tu dashboard.'
      : 'El siguiente paso es crear tu cuenta para entrar al dashboard.';

    switch (status) {
      case 'authorized':
        clearStatus();
        btn.classList.add('is-done');
        openModal({
          title: '¡Suscripción activa!',
          text: `Ya estás suscripto a PrevCS Pro por ${form.dataset.price} / mes. ${nextStep}`,
          nextUrl: form.dataset.nextUrl,
        });
        break;
      case 'pending':
        clearStatus();
        btn.classList.add('is-done');
        openModal({
          title: 'Suscripción pendiente',
          text: `Mercado Pago está validando tu medio de pago; la suscripción se activa sola cuando se apruebe. ${nextStep}`,
          nextUrl: form.dataset.nextUrl,
        });
        break;
      case 'paused':
        showStatus('pending', 'Suscripción pausada',
          'Mercado Pago pausó la suscripción. Probá con otra tarjeta o escribinos a soporte.');
        break;
      case 'canceled':
        showStatus('error', 'Suscripción cancelada',
          'Mercado Pago no pudo autorizar la tarjeta. Probá con otra e intentá nuevamente.');
        break;
      default:
        showStatus('error', 'No pudimos confirmar la suscripción', 'Intentá nuevamente en unos minutos.');
    }
  }

  /* ---------- Envío del formulario ---------- */
  form.addEventListener('submit', (e) => {
    if (isMercadoPago()) {
      // Lo resuelve el onSubmit de CardForm (tokeniza y llama a subscribe()).
      if (!cardReady) {
        e.preventDefault();
        showStatus('loading', 'Todavía estamos cargando el pago seguro…', 'Esperá unos segundos e intentá de nuevo.');
      }
      return;
    }

    // PayPal: simulación (sin cambios hasta integrar PayPal).
    e.preventDefault();
    if (btn.classList.contains('is-loading') || btn.classList.contains('is-done')) return;

    // Validación nativa mínima (nombre + email).
    if (!form.checkValidity()) {
      form.reportValidity();
      shakeForm();
      return;
    }

    // Estado: procesando.
    btn.classList.add('is-loading');
    btn.disabled = true;
    if (label) label.textContent = 'Procesando…';

    // Simula la llamada al proveedor de pago.
    window.setTimeout(() => {
      btn.classList.remove('is-loading');
      if (label) label.textContent = 'Confirmar pago';
      openModal();
    }, 1500);
  });

  /* ---------- Cambio de método ---------- */
  function syncMethod() {
    const mp = isMercadoPago();
    if (cardBlock) cardBlock.hidden = !mp;
    if (secureNote) {
      secureNote.textContent = mp
        ? 'Pago seguro con Mercado Pago · cancelás cuando quieras'
        : 'Pago simulado · sin cargos reales (demo académica)';
    }
    if (label && !processing && !btn.classList.contains('is-loading')) label.textContent = idleLabel();

    if (!mp) {
      clearStatus();
      return;
    }

    scheduleMount();
    verifySecureFields();
  }

  methods.forEach((input) => input.addEventListener('change', syncMethod));
  syncMethod();

  /* ---------- Modal de pago confirmado ---------- */
  function openModal(content) {
    if (!modal) {
      window.location.href = (content && content.nextUrl) || NEXT_URL;
      return;
    }
    if (content) {
      modal.querySelector('#payModalTitle').textContent = content.title;
      modal.querySelector('.pay-modal-text').textContent = content.text;
      const continueLink = modal.querySelector('#payModalContinue');
      if (continueLink && content.nextUrl) continueLink.href = content.nextUrl;
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (hasGSAP && !reduce) {
      window.gsap.fromTo('.pay-modal-icon',
        { scale: 0.4, rotate: -12 },
        { scale: 1, rotate: 0, duration: 0.5, ease: 'back.out(2)', delay: 0.05 });
    }
    modal.querySelector('#payModalContinue')?.focus();
  }

  // El overlay no cierra el modal: el pago ya se confirmó y queremos guiar al registro.
  // (El único cierre real es continuar al registro.)
  document.getElementById('payModalContinue')?.addEventListener('click', () => {
    document.body.style.overflow = '';
  });
})();
