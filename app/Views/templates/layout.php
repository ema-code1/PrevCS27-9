<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'PrevCS · Prevention Control System') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/header.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>">
    <?= $this->renderSection('pageStyles') ?>
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

    /* ─── Sidebar collapse ────────────────────────────────────────────── */
    (function () {
        const sidebar = document.querySelector('[data-sidebar]');
        const toggle = document.querySelector('[data-sidebar-toggle]');
        if (!sidebar || !toggle) return;
        toggle.addEventListener('change', function () {
            sidebar.classList.toggle('is-collapsed', toggle.checked);
        });
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
    </script>
</body>
</html>
