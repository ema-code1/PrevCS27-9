<?= $this->extend('templates/layout') ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('css/reg-log.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/verification-code.css') ?>?v=20260609-1">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errorMessage = session('error');
$successMessage = session('success');
?>
<main class="auth-page verification-page">
    <div class="auth-nebula" aria-hidden="true">
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
        <div class="auth-nebula__blob"></div>
    </div>

    <section class="auth-panel verification-panel">
        <div class="auth-card card verification-card">
            <div class="verification-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 6.5h16v11H4z" stroke="currentColor" stroke-width="1.7"/>
                    <path d="m5 8 7 5 7-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m15.5 17.2 1.5 1.5 3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <div class="verification-head">
                <p class="auth-pane__eyebrow"><?= esc($eyebrow) ?></p>
                <h1><?= esc($heading) ?></h1>
                <p>Enviamos un codigo a <strong><?= esc($maskedEmail) ?></strong>. Es valido por 5 minutos.</p>
            </div>

            <?php if ($errorMessage): ?>
                <div class="auth-alert auth-alert--error"><?= esc($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($successMessage): ?>
                <div class="auth-alert auth-alert--success"><?= esc($successMessage) ?></div>
            <?php endif; ?>

            <form action="<?= esc($verifyUrl) ?>" method="post" class="verification-form" data-code-form>
                <?= csrf_field() ?>
                <input type="hidden" name="code" data-code-value>

                <div class="verification-inputs" aria-label="Codigo de seis digitos">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]"
                            maxlength="1"
                            autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                            aria-label="Digito <?= $i + 1 ?>"
                            data-code-digit
                            required
                        >
                    <?php endfor; ?>
                </div>

                <button type="submit" class="auth-submit verification-submit" disabled data-code-submit>Continuar</button>
            </form>

            <div class="verification-resend">
                <span>No recibiste el codigo?</span>
                <form action="<?= esc($resendUrl) ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="verification-resend__button" data-resend-button data-retry-after="<?= esc($retryAfter) ?>">
                        Reenviar codigo
                    </button>
                </form>
                <span class="verification-resend__timer" data-resend-timer></span>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
(function () {
    const form = document.querySelector('[data-code-form]');
    if (!form) return;

    const digits = Array.from(form.querySelectorAll('[data-code-digit]'));
    const value = form.querySelector('[data-code-value]');
    const submit = form.querySelector('[data-code-submit]');
    const resend = document.querySelector('[data-resend-button]');
    const timer = document.querySelector('[data-resend-timer]');

    function syncCode() {
        const code = digits.map((input) => input.value).join('');
        value.value = code;
        submit.disabled = code.length !== 6;
    }

    digits.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            if (input.value && digits[index + 1]) digits[index + 1].focus();
            syncCode();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && digits[index - 1]) {
                digits[index - 1].focus();
            }
        });

        input.addEventListener('paste', (event) => {
            const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
            if (!pasted) return;
            event.preventDefault();
            pasted.split('').forEach((number, digitIndex) => {
                if (digits[digitIndex]) digits[digitIndex].value = number;
            });
            digits[Math.min(pasted.length, 6) - 1].focus();
            syncCode();
        });
    });

    let seconds = Number(resend?.dataset.retryAfter || 0);
    function updateTimer() {
        if (!resend || !timer) return;
        resend.disabled = seconds > 0;
        timer.textContent = seconds > 0 ? `Disponible en ${seconds}s` : '';
        if (seconds > 0) {
            seconds -= 1;
            window.setTimeout(updateTimer, 1000);
        }
    }

    syncCode();
    digits[0]?.focus();
    updateTimer();
})();
</script>
<?= $this->endSection() ?>
