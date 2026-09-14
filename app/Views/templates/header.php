<?php
$isLoggedIn   = session('isLoggedIn') === true;
$headerHref   = $isLoggedIn ? base_url('principal') : base_url('/');
$menuDisplay  = session('user_name') ?? 'Usuario';

// Foto de perfil para la pildora de usuario. Se cachea en sesion para no
// pegarle a la base en cada request: AdminAccount la refresca al guardar.
$avatarFile = $isLoggedIn ? session('user_foto') : null;
if ($isLoggedIn && $avatarFile === null) {
    $avatarRow  = (new \App\Models\UserModel())->find((int) session('user_id'));
    $avatarFile = trim((string) ($avatarRow['foto_perfil'] ?? ''));
    session()->set('user_foto', $avatarFile);
}
$avatarFile = trim((string) $avatarFile);
$avatarUrl  = $avatarFile !== '' ? base_url('uploads/avatars/' . rawurlencode($avatarFile)) : '';

// Iniciales de respaldo cuando todavia no hay foto cargada.
$avatarInitials = '';
foreach (array_slice(preg_split('/\s+/', trim($menuDisplay), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2) as $part) {
    $avatarInitials .= mb_strtoupper(mb_substr($part, 0, 1));
}

// Notificaciones: la vista/controller puede pasarlas; si no, se cargan aca
// mismo (mismo patron que el avatar de arriba) para que el panel llegue
// poblado en el primer render de cualquier pagina, sin tocar cada controller.
// Cada item: level, title, text, date, read.
if (! isset($notifications) && $isLoggedIn) {
    $notifications = (new \App\Models\NotificationModel())->feedFor((int) session('user_id'));
}

$notifications = array_values($notifications ?? []);
$notifUnread   = 0;
foreach ($notifications as $notification) {
    if (empty($notification['read'])) {
        $notifUnread++;
    }
}

// El nivel decide el color del punto: 1 agotado, 2 bajo el minimo, 3 aviso
// propio del usuario.
$notifClass = static fn ($level): string => 'notif-item--' . ([1 => 'error', 2 => 'warning', 3 => 'info'][(int) $level] ?? 'warning');
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
        </span>
    </a>

    <div class="topbar__actions">
        <?php if ($isLoggedIn): ?>
            <details class="user-menu notif-menu">
                <summary class="notif-bell" aria-label="Abrir notificaciones">
                    <span class="notif-bell__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M18 9a6 6 0 1 0-12 0c0 4.2-1 5.6-1.7 6.4a.8.8 0 0 0 .6 1.3h14.2a.8.8 0 0 0 .6-1.3C19 14.6 18 13.2 18 9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M10 20a2.2 2.2 0 0 0 4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <?php if ($notifUnread > 0): ?>
                        <span class="notif-bell__badge" aria-label="<?= esc($notifUnread) ?> notificaciones sin leer"><?= esc($notifUnread > 99 ? '99+' : $notifUnread) ?></span>
                    <?php endif; ?>
                </summary>
                <div class="user-menu__panel notif-panel">
                    <div class="notif-panel__head">
                        <span class="notif-panel__title">Notificaciones</span>
                        <div class="notif-panel__actions">
                            <button type="button" class="notif-panel__action" data-notif-read-all<?= $notifications === [] ? ' hidden' : '' ?>>Marcar todo como leído</button>
                            <button type="button" class="notif-panel__clear" data-notif-clear-all<?= $notifications === [] ? ' hidden' : '' ?> aria-label="Borrar todo el historial de notificaciones" title="Borrar todo el historial de notificaciones">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="notif-panel__list">
                        <?php if ($notifications === []): ?>
                            <p class="notif-empty">No hay notificaciones</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php $isUnread = empty($notification['read']); ?>
                                <div class="user-menu__item notif-item <?= $notifClass($notification['level'] ?? 2) ?><?= $isUnread ? ' is-unread' : '' ?>" data-notif-id="<?= esc($notification['id'] ?? '') ?>">
                                    <span class="notif-item__dot" aria-hidden="true"></span>
                                    <span class="notif-item__body">
                                        <span class="notif-item__head">
                                            <span class="notif-item__title"><?= esc($notification['title'] ?? 'Notificación') ?></span>
                                            <span class="notif-item__date"><?= esc($notification['date'] ?? '') ?></span>
                                        </span>
                                        <?php if (($notification['text'] ?? '') !== ''): ?>
                                            <span class="notif-item__text"><?= esc($notification['text']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </details>

            <details class="user-menu" data-user-menu>
                <summary class="user-chip" aria-label="Abrir menu de usuario">
                    <span class="user-chip__icon<?= $avatarUrl !== '' ? ' has-img' : '' ?>" aria-hidden="true">
                        <?php if ($avatarUrl !== ''): ?>
                            <img src="<?= esc($avatarUrl) ?>" alt="">
                        <?php elseif ($avatarInitials !== ''): ?>
                            <span class="user-chip__icon-fallback"><?= esc($avatarInitials) ?></span>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M4 20c1.6-4 5-6 8-6s6.4 2 8 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        <?php endif; ?>
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
