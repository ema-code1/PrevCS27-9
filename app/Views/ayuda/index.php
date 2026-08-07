<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/standalone.css') ?>?v=20260807-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// La ayuda se abre en una pestaña nueva, así que "Volver" no siempre tiene
// historial detrás: el href es el destino de respaldo y el JS de abajo usa
// history.back() solo cuando hay una página previa en esta pestaña.
$backHref = session('isLoggedIn') === true ? base_url('registros-iot') : base_url('/');
?>
<main class="standalone">
    <div class="standalone__bar">
        <a class="btn btn--secondary standalone__back" href="<?= esc($backHref) ?>" data-help-back>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 5 7 10l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Volver
        </a>
    </div>

    <div class="help">
        <header class="help__head">
            <div class="standalone__head">
                <p class="standalone__eyebrow">Centro de ayuda</p>
                <h1>Problemas frecuentes</h1>
                <p>Respuestas cortas a lo que más nos consultan. Desplegá una pregunta para ver la solución.</p>
            </div>
        </header>

        <nav class="help__nav" aria-label="Secciones de ayuda">
            <a href="#">Guía de inicio</a>
            <a href="#">Dispositivos</a>
            <a href="#">Registros</a>
            <a href="#">Facturación</a>
            <a href="#">Contacto</a>
        </nav>

        <?php foreach ($sections as $section): ?>
            <section class="help__section" id="<?= esc($section['id']) ?>">
                <h2 class="help__section-title"><?= esc($section['title']) ?></h2>

                <div class="help__accordion" data-faq-accordion>
                    <?php foreach ($section['items'] as $item): ?>
                        <details class="faq">
                            <summary class="faq__summary">
                                <span><?= esc($item['q']) ?></span>
                                <span class="faq__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </summary>
                            <div class="faq__panel">
                                <div class="faq__panel-inner">
                                    <p><?= esc($item['a']) ?></p>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── Acordeon: retenemos [open] mientras se cierra para poder animarlo ── */
    var closeDelay = 300;

    document.querySelectorAll('[data-faq-accordion]').forEach(function (accordion) {
        accordion.classList.add('is-enhanced');
        var timers = new WeakMap();

        function close(faq) {
            if (!faq.open || !faq.classList.contains('is-open')) return;
            faq.classList.remove('is-open');
            timers.set(faq, window.setTimeout(function () {
                faq.open = false;
                timers.delete(faq);
            }, closeDelay));
        }

        function open(faq) {
            if (timers.has(faq)) {
                window.clearTimeout(timers.get(faq));
                timers.delete(faq);
            }
            faq.open = true;
            // Reflow sincronico: el panel se mide en 0fr antes de pasar a 1fr,
            // asi la transicion arranca aunque la pestaña no este componiendo.
            void faq.offsetHeight;
            faq.classList.add('is-open');
        }

        accordion.querySelectorAll('.faq').forEach(function (faq) {
            if (faq.open) faq.classList.add('is-open');

            faq.querySelector('.faq__summary').addEventListener('click', function (event) {
                event.preventDefault();
                if (faq.classList.contains('is-open')) {
                    close(faq);
                } else {
                    open(faq);
                }
            });
        });
    });

    /* ── "Volver": history.back() si esta pestaña tiene de dónde volver ── */
    var back = document.querySelector('[data-help-back]');
    if (back) {
        back.addEventListener('click', function (event) {
            if (window.history.length > 1 && document.referrer) {
                event.preventDefault();
                window.history.back();
            }
        });
    }
});
</script>
<?= $this->endSection() ?>
