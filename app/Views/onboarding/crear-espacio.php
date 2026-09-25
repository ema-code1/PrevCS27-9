<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/onboarding.css') ?>?v=20260612-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = $errors ?? [];
$oldMetrics = (array) old('metrics', []);
$chartLabels = [
    'line'     => 'Grafico de linea',
    'area'     => 'Grafico de area',
    'gauge'    => 'Medidor',
    'bars'     => 'Barras',
    'timeline' => 'Linea de tiempo',
];
?>
<main class="auth-page">
    <div class="auth-nebula" aria-hidden="true">
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
    </div>

    <section class="auth-panel auth-panel--wide">
        <div class="auth-card card onboarding-card">
            <?php if (session('error')): ?>
                <div class="auth-alert auth-alert--error"><?= esc(session('error')) ?></div>
            <?php endif; ?>
            <?php if ($errors !== []): ?>
                <div class="auth-alert auth-alert--error">
                    <?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="onboarding-head">
                <h1>Crear espacio de industria</h1>
                <p>Al terminar te guiamos para vincular la ESP32.</p>
            </div>

            <div class="wizard-stepper">
                <span class="wizard-stepper__line" data-step-line aria-hidden="true">
                    <span class="wizard-stepper__fill" data-step-line-fill></span>
                </span>
                <ol class="wizard-steps" data-wizard-indicator>
                    <li class="wizard-steps__item is-active" data-step-dot="1">
                        <span class="wizard-steps__label">Espacio</span>
                        <span class="wizard-steps__dot">1</span>
                    </li>
                    <li class="wizard-steps__item" data-step-dot="2">
                        <span class="wizard-steps__label">Seguridad</span>
                        <span class="wizard-steps__dot">2</span>
                    </li>
                    <li class="wizard-steps__item" data-step-dot="3">
                        <span class="wizard-steps__label">Sensores</span>
                        <span class="wizard-steps__dot">3</span>
                    </li>
                </ol>
            </div>

            <form action="<?= base_url('crear-espacio') ?>" method="post" class="auth-form" data-wizard-form>
                <?= csrf_field() ?>

                <div class="wizard-track" data-wizard-track>
                <section class="wizard-pane is-active" data-step-pane="1">
                    <label class="auth-field">
                        <span class="auth-field__label">Nombre de la empresa / industria</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">E</span>
                            <input type="text" name="company_name" placeholder="Ej: San Jose Metalurgica" value="<?= esc(old('company_name')) ?>" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Nombre del espacio</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">#</span>
                            <input type="text" name="space_name" placeholder="Ej: Planta Norte" value="<?= esc(old('space_name')) ?>" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Rubro o sector general</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">S</span>
                            <input type="text" name="sector" placeholder="Ej: Metalurgia, alimenticia, textil" value="<?= esc(old('sector')) ?>">
                        </span>
                    </label>

                    <div class="wizard-nav">
                        <span></span>
                        <button type="button" class="auth-submit wizard-next" data-wizard-next>Continuar</button>
                    </div>
                </section>

                <section class="wizard-pane" data-step-pane="2">
                    <p class="wizard-hint">Esta clave sirve para unir supervisores y para ver datos sensibles, como tokens.</p>

                    <label class="auth-field">
                        <span class="auth-field__label">Clave del espacio</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password" placeholder="Mínimo 6 caracteres" minlength="6" required>
                        </span>
                    </label>

                    <label class="auth-field">
                        <span class="auth-field__label">Repetir clave</span>
                        <span class="auth-field__control">
                            <span class="auth-field__icon">*</span>
                            <input type="password" name="space_password_confirm" placeholder="Repetí la clave" minlength="6" required>
                        </span>
                    </label>

                    <div class="wizard-nav">
                        <button type="button" class="auth-link wizard-prev" data-wizard-prev>Volver</button>
                        <button type="button" class="auth-submit wizard-next" data-wizard-next>Continuar</button>
                    </div>
                </section>

                <section class="wizard-pane" data-step-pane="3">
                    <p class="wizard-hint">Elegí una vez. Cada ESP32 nueva tendra estos sensores creados solos.</p>

                    <div class="metric-grid">
                        <?php foreach ($sensorTypes as $type): ?>
                            <label class="metric-option">
                                <input type="checkbox" name="metrics[]" value="<?= esc($type['code']) ?>"
                                    data-metric-name="<?= esc($type['name']) ?>"
                                    data-metric-chart="<?= esc($type['default_chart']) ?>"
                                    <?= in_array($type['code'], $oldMetrics, true) ? 'checked' : '' ?>>
                                <span class="metric-option__box">
                                    <span class="metric-option__name"><?= esc($type['name']) ?></span>
                                    <?php if (!empty($type['unit'])): ?>
                                        <span class="metric-option__unit"><?= esc($type['unit']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="chart-suggestions" data-chart-suggestions>
                        <p class="chart-suggestions__empty" data-chart-empty>Elegí al menos un sensor.</p>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="auth-link wizard-prev" data-wizard-prev>Volver</button>
                        <button type="submit" class="auth-submit">Crear espacio</button>
                    </div>
                </section>
                </div>
            </form>
        </div>

        <p class="auth-foot">
            Preferis unirte a un espacio existente? <a href="<?= base_url('unirse-espacio') ?>">Unirme a un espacio</a>
        </p>
    </section>
</main>

<script type="application/json" data-chart-labels>
<?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>
</script>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="<?= base_url('js/prevcs-ui.js') ?>?v=20260820-1" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-wizard-form]');
    if (!form) return;

    const gsapReady = typeof window.gsap !== 'undefined';
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const animate = gsapReady && !reduceMotion;

    const track = form.querySelector('[data-wizard-track]');
    const panes = Array.from(form.querySelectorAll('[data-step-pane]'));
    const dots = Array.from(document.querySelectorAll('[data-step-dot]'));
    const stepper = document.querySelector('.wizard-stepper');
    const stepLine = document.querySelector('[data-step-line]');
    const stepFill = document.querySelector('[data-step-line-fill]');
    const chartLabels = JSON.parse(document.querySelector('[data-chart-labels]').textContent || '{}');
    let current = 1;
    let busy = false;

    const paneByStep = (step) => panes.find((pane) => Number(pane.dataset.stepPane) === step);

    if (animate && track) track.classList.add('wizard-track--gsap');

    /* ── Línea de progreso del stepper (calculada sobre la posición real de los puntos) ── */
    function layoutStepLine() {
        if (!stepper || !stepLine || dots.length < 2) return;
        const spans = dots.map((dot) => dot.querySelector('.wizard-steps__dot'));
        const base = stepper.getBoundingClientRect();
        const first = spans[0].getBoundingClientRect();
        const last = spans[spans.length - 1].getBoundingClientRect();
        const startX = first.left + first.width / 2 - base.left;
        const endX = last.left + last.width / 2 - base.left;
        const centerY = first.top + first.height / 2 - base.top;
        stepLine.style.left = startX + 'px';
        stepLine.style.top = centerY + 'px';
        stepLine.style.width = Math.max(0, endX - startX) + 'px';
        updateStepFill(false);
    }

    function updateStepFill(withMotion) {
        if (!stepFill || dots.length < 2) return;
        const spans = dots.map((dot) => dot.querySelector('.wizard-steps__dot'));
        const base = stepper.getBoundingClientRect();
        const startX = spans[0].getBoundingClientRect().left + spans[0].getBoundingClientRect().width / 2 - base.left;
        const curRect = spans[current - 1].getBoundingClientRect();
        const curX = curRect.left + curRect.width / 2 - base.left;
        const targetWidth = Math.max(0, curX - startX);
        if (withMotion && animate) {
            gsap.to(stepFill, { width: targetWidth, duration: 0.5, ease: 'power3.inOut' });
        } else {
            stepFill.style.width = targetWidth + 'px';
        }
    }

    function updateDots() {
        dots.forEach((dot) => dot.classList.toggle('is-active', Number(dot.dataset.stepDot) <= current));
    }

    /* ── Transición entre pasos (slide horizontal con GSAP) ── */
    function goTo(step, dir) {
        const target = Math.max(1, Math.min(panes.length, step));
        if (target === current || busy) return;
        const direction = dir || (target > current ? 1 : -1);
        const outgoing = paneByStep(current);
        const incoming = paneByStep(target);

        current = target;
        updateDots();
        updateStepFill(true);
        if (target === 3) renderSuggestions(false);

        if (!animate) {
            outgoing.classList.remove('is-active');
            incoming.classList.add('is-active');
            return;
        }

        busy = true;
        track.classList.add('is-sliding');
        incoming.classList.add('is-active', 'is-animating');
        outgoing.classList.add('is-animating');
        const h0 = outgoing.offsetHeight;
        const h1 = incoming.offsetHeight;
        track.style.height = h0 + 'px';

        gsap.timeline({
            defaults: { duration: 0.5, ease: 'power3.inOut' },
            onComplete: () => {
                outgoing.classList.remove('is-active', 'is-animating');
                incoming.classList.remove('is-animating');
                gsap.set([outgoing, incoming], { clearProps: 'all' });
                track.style.height = '';
                track.classList.remove('is-sliding');
                busy = false;
            },
        })
            .to(track, { height: h1 }, 0)
            .fromTo(outgoing, { xPercent: 0, autoAlpha: 1 }, { xPercent: -direction * 100, autoAlpha: 0 }, 0)
            .fromTo(incoming, { xPercent: direction * 100, autoAlpha: 0 }, { xPercent: 0, autoAlpha: 1 }, 0);
    }

    /* ── Sugerencias de gráficos (aparecen / desaparecen suavemente) ── */
    function buildRow(input) {
        const chart = input.dataset.metricChart || 'line';
        const row = document.createElement('div');
        row.className = 'chart-suggestion';
        row.dataset.suggestion = input.value;
        row.innerHTML = '<strong>' + input.dataset.metricName + '</strong>' +
            '<span class="chart-suggestion__chart">' + (chartLabels[chart] || chart) + '</span>';
        return row;
    }

    function renderSuggestions(withMotion) {
        const box = form.querySelector('[data-chart-suggestions]');
        const checked = Array.from(form.querySelectorAll('input[name="metrics[]"]:checked'));
        const wanted = checked.map((input) => input.value);
        const doAnim = withMotion !== false && animate;

        let empty = box.querySelector('[data-chart-empty]');
        if (checked.length === 0 && !empty) {
            empty = document.createElement('p');
            empty.className = 'chart-suggestions__empty';
            empty.dataset.chartEmpty = '';
            empty.textContent = 'Elegí al menos un sensor.';
            box.appendChild(empty);
            if (doAnim) gsap.from(empty, { autoAlpha: 0, duration: 0.3 });
        } else if (checked.length > 0 && empty) {
            empty.remove();
        }

        const existing = new Map(
            Array.from(box.querySelectorAll('[data-suggestion]')).map((row) => [row.dataset.suggestion, row])
        );

        existing.forEach((row, code) => {
            if (wanted.includes(code)) return;
            if (doAnim) {
                gsap.to(row, {
                    height: 0, autoAlpha: 0, marginTop: 0, paddingTop: 0, paddingBottom: 0,
                    duration: 0.3, ease: 'power2.in', overflow: 'hidden',
                    onComplete: () => row.remove(),
                });
            } else {
                row.remove();
            }
        });

        checked.forEach((input) => {
            if (existing.has(input.value)) return;
            const row = buildRow(input);
            box.appendChild(row);
            if (doAnim) {
                gsap.set(row, { overflow: 'hidden' });
                gsap.from(row, {
                    height: 0, autoAlpha: 0, marginTop: 0, duration: 0.35, ease: 'power2.out',
                    clearProps: 'height,overflow,autoAlpha,marginTop',
                });
            }
        });
    }

    function show(step) {
        current = Math.max(1, Math.min(panes.length, step));
        panes.forEach((pane) => pane.classList.toggle('is-active', Number(pane.dataset.stepPane) === current));
        updateDots();
        renderSuggestions(false);
    }

    function validateCurrent() {
        const pane = panes.find((item) => Number(item.dataset.stepPane) === current);
        const fields = Array.from(pane.querySelectorAll('input[required]'));

        for (const field of fields) {
            if (!field.reportValidity()) return false;
        }

        if (current === 2) {
            const password = pane.querySelector('[name="space_password"]');
            const confirm = pane.querySelector('[name="space_password_confirm"]');
            if (password && confirm && password.value !== confirm.value) {
                confirm.setCustomValidity('Las claves no coinciden.');
                confirm.reportValidity();
                confirm.setCustomValidity('');
                return false;
            }
        }

        if (current === 3 && form.querySelectorAll('input[name="metrics[]"]:checked').length === 0) {
            form.querySelector('input[name="metrics[]"]')?.focus();
            return false;
        }

        return true;
    }

    form.querySelectorAll('[data-wizard-next]').forEach((button) => {
        button.addEventListener('click', () => { if (validateCurrent()) goTo(current + 1, 1); });
    });

    form.querySelectorAll('[data-wizard-prev]').forEach((button) => {
        button.addEventListener('click', () => goTo(current - 1, -1));
    });

    /* ── Enter: avanza de paso / hace submit solo si el paso actual esta completo ── */
    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.target.tagName === 'BUTTON' || event.target.tagName === 'TEXTAREA') return;
        event.preventDefault();
        if (busy) return;

        if (validateCurrent()) {
            if (current < panes.length) {
                goTo(current + 1, 1);
            } else if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        } else if (window.PrevcsToast) {
            window.PrevcsToast.show('Completá los datos de este paso antes de continuar.', 'error');
        }
    });

    form.querySelectorAll('input[name="metrics[]"]').forEach((input) => {
        input.addEventListener('change', () => renderSuggestions(true));
    });

    const companyInput = form.querySelector('[name="company_name"]');
    const uidPreview = form.querySelector('[data-uid-preview]');
    function updateUidPreview() {
        const words = (companyInput.value || '').trim().split(/\s+/).filter(Boolean);
        let initials = words.map((word) => word[0]).join('').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 3);
        if (!initials) initials = 'XX';
        if (initials.length === 1) initials += 'X';
        uidPreview.textContent = initials + '-IND-XXXXXX';
    }

    if (companyInput && uidPreview) {
        companyInput.addEventListener('input', updateUidPreview);
        updateUidPreview();
    }

    show(1);
    layoutStepLine();

    let resizeRaf = null;
    window.addEventListener('resize', () => {
        if (resizeRaf) cancelAnimationFrame(resizeRaf);
        resizeRaf = requestAnimationFrame(layoutStepLine);
    });
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(layoutStepLine);
    }
});
</script>
<?= $this->endSection() ?>
