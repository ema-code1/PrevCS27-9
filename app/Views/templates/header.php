<?php
$isLoggedIn = session('isLoggedIn') === true;
$headerHref = $isLoggedIn ? base_url('principal') : base_url('login');
$headerLabel = $isLoggedIn ? (session('user_name') ?? 'Usuario') : 'Iniciar sesión';
?>
<header class="topbar">
    <div class="brand">
        <div class="brand__orb"></div>
        <div class="brand__text">
            <span>Prevention</span>
            <span>Control</span>
            <span>System</span>
        </div>
    </div>

    <a class="user-chip" href="<?= esc($headerHref) ?>" aria-label="<?= esc($headerUserLabel ?? $headerLabel) ?>">
        <span class="user-chip__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2" />
                <path d="M4 20c1.6-4 5-6 8-6s6.4 2 8 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
        </span>
        <span class="user-chip__label"><?= esc($headerUserText ?? $headerLabel) ?></span>
    </a>
</header>
