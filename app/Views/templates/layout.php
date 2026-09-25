<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?php /* Nombre del campo oculto que escribe csrf_field(). Se publica acá
            para que al rotar el hash se puedan refrescar de una los inputs
            de los formularios que quedaron en la página. */ ?>
    <meta name="csrf-field" content="<?= csrf_token() ?>">
    <title><?= esc($title ?? 'PrevCS · Preventive Control System') ?></title>

    <script>
    /* ─── CSRF compartido ───────────────────────────────────────────────
       Security.php tiene $regenerate = true: cada POST invalida el hash
       anterior. Con un token cacheado por módulo, la campana del header le
       pisaba el suyo a stock y a kanban y el guardado siguiente fallaba.
       Acá vive el único hash vigente de la página, y queue() serializa
       TODAS las mutaciones: dos POST en paralelo harían fallar el segundo.

       Va inline en el head y no en prevcs-ui.js porque ese archivo es
       defer y el JS de stock es inline: correría antes. ────────────── */
    (function () {
        var meta = document.querySelector('meta[name="csrf-hash"]');
        var token = meta ? (meta.getAttribute('content') || '') : '';
        var chain = Promise.resolve();

        window.PrevcsCsrf = {
            get: function () { return token; },

            set: function (hash) {
                if (!hash) return token;
                token = hash;
                if (meta) meta.setAttribute('content', hash);

                /* Los formularios que quedaron en la página traen el hash
                   viejo en su input oculto. Si no se refrescan, el primer
                   envío normal después de un POST por fetch se rechaza. */
                var fieldMeta = document.querySelector('meta[name="csrf-field"]');
                var field = fieldMeta ? fieldMeta.getAttribute('content') : '';
                if (field) {
                    document.querySelectorAll('input[name="' + field + '"]').forEach(function (input) {
                        input.value = hash;
                    });
                }

                return token;
            },

            /* Semilla desde un data-* de la vista. No pisa un hash más nuevo
               que ya haya llegado por respuesta. */
            seed: function (hash) {
                if (hash && !token) window.PrevcsCsrf.set(hash);
                return token;
            },

            /* fn devuelve una promesa; se encola detrás de lo pendiente y la
               cadena sobrevive a los errores. */
            queue: function (fn) {
                var task = chain.then(fn);
                chain = task.catch(function () {});
                return task;
            }
        };
    })();
    </script>

    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('images/prevcs-favicon-32-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('images/prevcs-favicon-32-32x32.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('images/prevcs-favicon-32-32x32.png') ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= base_url('images/prevcs-favicon-32-32x32.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>?v=20260820-1">
    <link rel="stylesheet" href="<?= base_url('css/header.css') ?>?v=20260924-1">
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>?v=20260915-1">
    <link rel="stylesheet" href="<?= base_url('css/password-toggle.css') ?>?v=20260612-1">
    <link rel="stylesheet" href="<?= base_url('css/toast.css') ?>?v=20260831-2">
    <?= $this->renderSection('pageStyles') ?>

    <!-- Lenis — smooth scroll global para todas las vistas -->
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.20/dist/lenis.min.js" defer></script>
    <script src="<?= base_url('js/smooth-scroll.js') ?>?v=20260918-1" defer></script>
    <!-- Toggle de visibilidad para inputs de contraseña (icono de ojo) -->
    <script src="<?= base_url('js/password-toggle.js') ?>?v=20260608-1" defer></script>
</head>
<body class="<?= esc($bodyClass ?? 'theme-light') ?>" data-theme="light">
    <div class="app-shell">
        <?php if (($showHeader ?? true) === true): ?>
            <?= $this->include('templates/header') ?>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
        <?= $this->renderSection('pageScripts') ?>
    </div>

    <script>
    /* ─── Theme switch (persist in localStorage, sync prefers-color-scheme) ── */
    (function () {
        const THEME_KEY = 'pcs-theme';
        const body = document.body;

        function applyTheme(theme) {
            const selected = theme === 'dark' ? 'dark' : 'light';
            body.setAttribute('data-theme', selected);
            document.querySelectorAll('[data-theme-toggle-input]').forEach(function (input) {
                input.checked = selected === 'dark';
            });
        }

        const saved = localStorage.getItem(THEME_KEY);
        if (saved) {
            applyTheme(saved);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            applyTheme('dark');
        } else {
            applyTheme('light');
        }

        document.querySelectorAll('[data-theme-toggle-input]').forEach(function (input) {
            input.addEventListener('change', function () {
                const next = input.checked ? 'dark' : 'light';
                localStorage.setItem(THEME_KEY, next);
                applyTheme(next);
            });
        });
    })();

    /* ─── Sidebar collapse (desktop) ──────────────────────────────────── */
    (function () {
        const sidebar = document.querySelector('[data-sidebar]');
        const toggle = document.querySelector('[data-sidebar-toggle]');
        if (!sidebar || !toggle) return;
        toggle.addEventListener('change', function () {
            sidebar.classList.toggle('is-collapsed', toggle.checked);
        });
    })();

    /* ─── Highlight de hover que se desliza entre items ───────────────────
       Mismo efecto en el menú lateral y en el dropdown del header. */
    (function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const containers = document.querySelectorAll('.sidebar__nav, .sidebar__foot, .user-menu__panel:not(.notif-panel), .notif-panel__list');

        containers.forEach(function (container) {
            const hl = document.createElement('span');
            hl.className = 'nav-hl';
            hl.setAttribute('aria-hidden', 'true');
            container.appendChild(hl);
            container.classList.add('has-hl');

            const selector = '.menu-link, .menu-sub__link, .user-menu__item';
            let visible = false;

            function moveTo(target) {
                const box = target.getBoundingClientRect();
                const base = container.getBoundingClientRect();
                // Al aparecer, colocar sin transición para que no viaje desde
                // la esquina superior izquierda del contenedor.
                if (!visible) {
                    hl.classList.add('is-placing');
                }
                hl.style.width = box.width + 'px';
                hl.style.height = box.height + 'px';
                hl.style.transform = 'translate3d(' + (box.left - base.left) + 'px,' + (box.top - base.top) + 'px,0)';
                hl.style.borderRadius = getComputedStyle(target).borderRadius;
                if (!visible) {
                    void hl.offsetWidth; // reflow: fija la posición sin animar
                    hl.classList.remove('is-placing');
                    visible = true;
                }
                hl.classList.add('is-visible');
            }

            function hide() {
                visible = false;
                hl.classList.remove('is-visible');
            }

            container.addEventListener('mouseover', function (e) {
                const target = e.target.closest(selector);
                if (target && container.contains(target)) moveTo(target);
            });
            container.addEventListener('mouseleave', hide);
            // Un item que se oculta (sublista colapsada) no debe dejar el
            // highlight flotando sobre la nada.
            container.addEventListener('focusout', function () { if (!container.matches(':hover')) hide(); });
            window.addEventListener('resize', hide);
        });
    })();

    /* ─── Stock: despliegue de espacios en el sidebar ─────────────────── */
    (function () {
        const toggle = document.querySelector('[data-stock-toggle]');
        const panel = document.querySelector('[data-stock-panel]');
        if (!toggle || !panel) return;
        const KEY = 'pcs-stock-open';
        const sidebar = document.querySelector('[data-sidebar]');

        function setOpen(open) {
            toggle.classList.toggle('is-open', open);
            panel.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        // El estado inicial lo marca el server (página de stock activa); si no,
        // se recuerda la última elección del usuario.
        if (!panel.classList.contains('is-open')) {
            setOpen(localStorage.getItem(KEY) === '1');
        }

        toggle.addEventListener('click', function () {
            // Con el sidebar colapsado (desktop) primero hay que expandirlo,
            // si no la sublista queda oculta por CSS.
            const collapsed = sidebar && sidebar.classList.contains('is-collapsed')
                && !window.matchMedia('(max-width: 900px)').matches;
            if (collapsed) {
                const collapseToggle = document.querySelector('[data-sidebar-toggle]');
                if (collapseToggle) {
                    collapseToggle.checked = false;
                    collapseToggle.dispatchEvent(new Event('change'));
                }
            }
            const next = collapsed ? true : !panel.classList.contains('is-open');
            setOpen(next);
            localStorage.setItem(KEY, next ? '1' : '0');
        });
    })();

    /* ─── User menu placement: topbar (desktop) ↔ sidebar foot (mobile) ─ */
    (function () {
        const userMenu = document.querySelector('.topbar [data-user-menu], .sidebar [data-sidebar-user-slot] [data-user-menu]');
        if (!userMenu) return;
        const topbarHome = document.querySelector('.topbar__actions');
        const sidebarHome = document.querySelector('[data-sidebar-user-slot]');
        if (!topbarHome || !sidebarHome) return;

        const mq = window.matchMedia('(max-width: 900px)');
        function place(matches) {
            const targetHome = matches ? sidebarHome : topbarHome;
            if (targetHome.contains(userMenu)) return;
            // Close before re-parenting so transitions don't get stranded.
            if (userMenu.open) {
                userMenu.open = false;
                userMenu.classList.remove('is-open', 'is-closing');
            }
            targetHome.appendChild(userMenu);
            userMenu.classList.toggle('user-menu--in-sidebar', matches);
        }
        place(mq.matches);
        const handler = function (e) { place(e.matches); };
        if (mq.addEventListener) mq.addEventListener('change', handler);
        else mq.addListener(handler);
    })();

    /* ─── Sidebar drawer (mobile) ─────────────────────────────────────── */
    (function () {
        const sidebar = document.querySelector('[data-sidebar]');
        const mobileToggle = document.querySelector('[data-sidebar-mobile-toggle]');
        const backdrop = document.querySelector('[data-sidebar-backdrop]');
        if (!sidebar || !mobileToggle || !backdrop) return;

        const body = document.body;
        const mq = window.matchMedia('(max-width: 900px)');

        function setOpen(open) {
            sidebar.classList.toggle('is-drawer-open', open);
            backdrop.classList.toggle('is-active', open);
            body.classList.toggle('is-drawer-open', open);
            mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        mobileToggle.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('is-drawer-open'));
        });
        backdrop.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('is-drawer-open')) setOpen(false);
        });
        // El toggle de Stock no navega: no debe cerrar el drawer.
        sidebar.querySelectorAll('.menu-link:not([data-stock-toggle]), .menu-sub__link').forEach(function (link) {
            link.addEventListener('click', function () { setOpen(false); });
        });
        // Snap back to a clean state when crossing the desktop breakpoint.
        const handleMq = function (e) { if (!e.matches) setOpen(false); };
        if (mq.addEventListener) mq.addEventListener('change', handleMq);
        else mq.addListener(handleMq);
    })();

    /* ─── Topbar scrolled state ──────────────────────────────────────── */
    (function () {
        const topbar = document.querySelector('.topbar');
        if (!topbar) return;
        const onScroll = () => topbar.classList.toggle('is-scrolled', window.scrollY > 6);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();

    /* ─── User menu (animated open/close) ────────────────────────────── */
    (function () {
        const menus = Array.from(document.querySelectorAll('.user-menu'));
        const closeTimers = new WeakMap();
        const closeDelay = 170;

        function closeMenu(menu) {
            if (!menu || !menu.open || menu.classList.contains('is-closing')) return;
            menu.classList.remove('is-open');
            menu.classList.add('is-closing');
            const t = window.setTimeout(function () {
                menu.open = false;
                menu.classList.remove('is-closing');
                closeTimers.delete(menu);
            }, closeDelay);
            closeTimers.set(menu, t);
        }

        function openMenu(menu) {
            menus.forEach(function (m) { if (m !== menu) closeMenu(m); });
            if (menu.open && menu.classList.contains('is-open')) { closeMenu(menu); return; }
            if (closeTimers.has(menu)) { window.clearTimeout(closeTimers.get(menu)); closeTimers.delete(menu); }
            menu.classList.remove('is-closing');
            menu.open = true;
            window.requestAnimationFrame(function () { menu.classList.add('is-open'); });
        }

        menus.forEach(function (menu) {
            const summary = menu.querySelector('summary');
            if (!summary) return;
            summary.addEventListener('click', function (e) {
                e.preventDefault();
                openMenu(menu);
            });
        });

        document.addEventListener('click', function (e) {
            menus.forEach(function (menu) {
                if (menu.open && !menu.contains(e.target)) closeMenu(menu);
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') menus.forEach(closeMenu);
        });
    })();

    /* ─── Notificaciones ──────────────────────────────────────────────── */
    (function () {
        const menu = document.querySelector('.notif-menu');
        if (!menu) return;
        const button = menu.querySelector('[data-notif-read-all]');
        const clearButton = menu.querySelector('[data-notif-clear-all]');
        const list = menu.querySelector('.notif-panel__list');
        const summary = menu.querySelector('.notif-bell');
        const feedUrl = '<?= base_url('notificaciones') ?>';
        const readUrl = '<?= base_url('notificaciones/leidas') ?>';
        const clearUrl = '<?= base_url('notificaciones/borrar') ?>';

        function unreadCount() {
            return menu.querySelectorAll('.notif-item.is-unread').length;
        }

        function updateBadge(count) {
            if (!summary) return;

            if (typeof count !== 'number') count = unreadCount();
            let badge = summary.querySelector('.notif-bell__badge');

            if (count <= 0) {
                if (badge) badge.remove();
                return;
            }

            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notif-bell__badge';
                summary.appendChild(badge);
            }

            badge.textContent = count > 99 ? '99+' : String(count);
            badge.setAttribute('aria-label', count + ' notificaciones sin leer');
        }

        function updateEmpty() {
            if (!list) return;

            const hasItems = !!list.querySelector('.notif-item');
            let empty = list.querySelector('.notif-empty');

            if (hasItems) {
                if (empty) empty.remove();
                if (button) button.hidden = false;
                if (clearButton) clearButton.hidden = false;
                return;
            }

            if (!empty) {
                empty = document.createElement('p');
                empty.className = 'notif-empty';
                empty.textContent = 'No hay notificaciones';
                list.appendChild(empty);
            }
            if (button) button.hidden = true;
            if (clearButton) clearButton.hidden = true;
        }

        /* El nivel decide el color: 1 agotado, 2 bajo el minimo, 3 aviso propio. */
        function levelClass(level) {
            return { 1: 'error', 2: 'warning', 3: 'info' }[Number(level)] || 'warning';
        }

        function createItem(notification) {
            const item = document.createElement('div');
            item.className = 'user-menu__item notif-item notif-item--' + levelClass(notification.level) + (notification.read ? '' : ' is-unread');
            item.dataset.notifId = notification.id || '';

            const dot = document.createElement('span');
            dot.className = 'notif-item__dot';
            dot.setAttribute('aria-hidden', 'true');

            const body = document.createElement('span');
            body.className = 'notif-item__body';

            const head = document.createElement('span');
            head.className = 'notif-item__head';

            const title = document.createElement('span');
            title.className = 'notif-item__title';
            title.textContent = notification.title || 'Notificación';

            const date = document.createElement('span');
            date.className = 'notif-item__date';
            date.textContent = notification.date || '';

            const text = document.createElement('span');
            text.className = 'notif-item__text';
            text.textContent = notification.text || '';

            head.appendChild(title);
            head.appendChild(date);
            body.appendChild(head);
            if (notification.text) body.appendChild(text);
            item.appendChild(dot);
            item.appendChild(body);

            return item;
        }

        /* Repinta el panel con lo que dice el servidor. El primer render ya
           viene poblado desde header.php; esto lo mantiene al día para que una
           baja de stock hecha por otro miembro del espacio también aparezca. */
        function render(items, unread) {
            if (!list) return;

            list.querySelectorAll('.notif-item').forEach(function (node) { node.remove(); });
            items.forEach(function (notification) {
                list.appendChild(createItem(notification));
            });

            updateEmpty();
            updateBadge(unread);
        }

        let refreshing = false;

        function refresh() {
            if (refreshing) return Promise.resolve();
            refreshing = true;

            // GET a propósito: no pasa por el filtro CSRF, así el polling no le
            // rota el token a la página que esté abierta.
            return fetch(feedUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.ok ? response.json() : null;
            }).then(function (data) {
                if (data && data.ok) render(data.items || [], data.unread || 0);
            }).catch(function () {
                /* Sin conexión el panel se queda con lo último que tenía. */
            }).then(function () {
                refreshing = false;
            });
        }

        updateEmpty();
        updateBadge();

        if (button) button.addEventListener('click', function () {
            menu.querySelectorAll('.notif-item.is-unread').forEach(function (item) {
                item.classList.remove('is-unread');
            });
            updateBadge(0);

            window.PrevcsCsrf.queue(function () {
                return fetch(readUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': window.PrevcsCsrf.get(),
                        'Content-Type': 'application/json'
                    },
                    body: '{}'
                }).then(function (response) {
                    return response.json().catch(function () { return {}; });
                }).then(function (data) {
                    // El hash nuevo se publica para todos: sin esto, el próximo
                    // guardado de stock o kanban fallaría.
                    if (data && data.csrf) window.PrevcsCsrf.set(data.csrf);
                });
            }).catch(function () { refresh(); });
        });

        if (clearButton) clearButton.addEventListener('click', function () {
            clearButton.disabled = true;

            window.PrevcsCsrf.queue(function () {
                return fetch(clearUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': window.PrevcsCsrf.get(),
                        'Content-Type': 'application/json'
                    },
                    body: '{}'
                }).then(function (response) {
                    return response.json().catch(function () { return {}; });
                }).then(function (data) {
                    if (data && data.csrf) window.PrevcsCsrf.set(data.csrf);
                    if (!data || !data.ok) throw new Error('No se pudieron borrar las notificaciones.');

                    list.querySelectorAll('.notif-item').forEach(function (item) { item.remove(); });
                    updateEmpty();
                    updateBadge(0);
                });
            }).catch(function () {
                refresh();
            }).then(function () {
                clearButton.disabled = false;
            });
        });

        // Cada minuto, y al volver a la pestaña: es lo que hace que te llegue el
        // aviso de otro miembro sin recargar.
        window.setInterval(refresh, 60000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refresh();
        });

        window.PrevcsNotifications = { refresh: refresh, updateBadge: updateBadge };
    })();
    </script>
</body>
</html>
