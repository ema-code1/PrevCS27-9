<?php
$isLoggedIn   = session('isLoggedIn') === true;
$headerHref   = $isLoggedIn ? base_url('principal') : base_url('/');
$menuDisplay  = session('user_name') ?? 'Usuario';
?>
<header class="topbar">
    <?php if ($isLoggedIn): ?>
    <button type="button" class="topbar-burger" data-sidebar-mobile-toggle aria-label="Abrir menu de navegacion" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
    <?php endif; ?>
    <a class="brand" href="<?= esc($headerHref) ?>" aria-label="Ir al inicio">
        <span class="brand__logo" aria-hidden="true">
            <span></span><span></span><span></span><span></span>
        </span>
        <span class="brand__text">
            <span>PrevCS</span>
            <span>Preventive Control System</span>
        </span>
    </a>

    <div class="topbar__actions">
        <?php if ($isLoggedIn): ?>
            <details class="user-menu">
                <summary class="user-chip" aria-label="Abrir menu de usuario">
                    <span class="user-chip__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M4 20c1.6-4 5-6 8-6s6.4 2 8 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="session-dot" data-tooltip="Sesión activa" tabindex="0" aria-label="Sesión activa"></span>
                    <span class="user-chip__label"><?= esc($menuDisplay) ?></span>
                </summary>
                <div class="user-menu__panel">
                    <a class="user-menu__item" href="<?= base_url('admin-account') ?>">
                        <span class="user-menu__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        </span>
                        <span>Configuracion</span>
                    </a>

                    <div class="user-menu__item user-menu__item--theme">
                        <span class="user-menu__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
                            </svg>
                        </span>
                        <span>Modo oscuro</span>
                        <label class="theme-switch theme-switch--compact">
                            <input type="checkbox" class="theme-switch__checkbox" data-theme-toggle-input aria-label="Cambiar tema claro/oscuro">
                            <div class="theme-switch__container">
                                <div class="theme-switch__clouds"></div>
                                <div class="theme-switch__stars-container"></div>
                                <div class="theme-switch__circle-container">
                                    <div class="theme-switch__sun-moon-container">
                                        <div class="theme-switch__moon">
                                            <div class="theme-switch__spot"></div>
                                            <div class="theme-switch__spot"></div>
                                            <div class="theme-switch__spot"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- "Administrar cuenta" y "Ayuda" viven ahora en el pie del
                         menú de navegación (templates/sidebar.php). -->

                    <a class="user-menu__item user-menu__item--danger" href="<?= base_url('logout') ?>">
                        <span class="user-menu__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M15 12H3m12 0-3-3m3 3-3 3M9 5h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>Cerrar sesión</span>
                    </a>
                </div>
            </details>
        <?php else: ?>
            <?php if (!($hideAuthButtons ?? false)): ?>
            <a class="btn btn--secondary topbar-login" href="<?= esc($headerHref) ?>">Iniciar sesión</a>
            <a class="btn btn--primary topbar-login" href="<?= base_url('register') ?>">Crear cuenta</a>
            <?php endif; ?>
            <details class="user-menu">
                <summary class="topbar-gear" aria-label="Abrir menu de configuracion">
                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </summary>
                <div class="user-menu__panel">
                    <div class="user-menu__item user-menu__item--theme">
                        <span class="user-menu__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
                            </svg>
                        </span>
                        <span>Modo oscuro</span>
                        <label class="theme-switch theme-switch--compact">
                            <input type="checkbox" class="theme-switch__checkbox" data-theme-toggle-input aria-label="Cambiar tema claro/oscuro">
                            <div class="theme-switch__container">
                                <div class="theme-switch__clouds"></div>
                                <div class="theme-switch__stars-container"></div>
                                <div class="theme-switch__circle-container">
                                    <div class="theme-switch__sun-moon-container">
                                        <div class="theme-switch__moon">
                                            <div class="theme-switch__spot"></div>
                                            <div class="theme-switch__spot"></div>
                                            <div class="theme-switch__spot"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <a class="user-menu__item" href="<?= base_url('/#como-funciona') ?>">
                        <span class="user-menu__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1.9-1.1 1.6V14M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <span>Ayuda</span>
                    </a>
                </div>
            </details>
        <?php endif; ?>
    </div>
</header>
