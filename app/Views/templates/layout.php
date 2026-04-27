<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Prevention Control System') ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
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
    (function () {
        const THEME_KEY = 'pcs-theme';
        const body = document.body;

        function applyTheme(theme) {
            const selected = theme === 'dark' ? 'dark' : 'light';
            body.setAttribute('data-theme', selected);

            document.querySelectorAll('[data-theme-toggle-input]').forEach(function (toggleInput) {
                toggleInput.checked = selected === 'dark';
            });
        }

        const savedTheme = localStorage.getItem(THEME_KEY);
        if (savedTheme) {
            applyTheme(savedTheme);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            applyTheme('dark');
        } else {
            applyTheme('light');
        }

        document.querySelectorAll('[data-theme-toggle-input]').forEach(function (toggleInput) {
            toggleInput.addEventListener('change', function () {
                const nextTheme = toggleInput.checked ? 'dark' : 'light';
                localStorage.setItem(THEME_KEY, nextTheme);
                applyTheme(nextTheme);
            });
        });

    })();

    (function () {
        const sidebar = document.querySelector('[data-sidebar]');
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

        if (!sidebar || !sidebarToggle) {
            return;
        }

        sidebarToggle.addEventListener('change', function () {
            sidebar.classList.toggle('is-collapsed', sidebarToggle.checked);
        });
    })();

    (function () {
        const menus = Array.from(document.querySelectorAll('.user-menu'));
        const closeTimers = new WeakMap();
        const closeDelay = 170;

        function closeMenu(menu) {
            if (!menu || !menu.open || menu.classList.contains('is-closing')) {
                return;
            }

            menu.classList.remove('is-open');
            menu.classList.add('is-closing');

            const closeTimer = window.setTimeout(function () {
                menu.open = false;
                menu.classList.remove('is-closing');
                closeTimers.delete(menu);
            }, closeDelay);

            closeTimers.set(menu, closeTimer);
        }

        function openMenu(menu) {
            menus.forEach(function (otherMenu) {
                if (otherMenu !== menu) {
                    closeMenu(otherMenu);
                }
            });

            if (menu.open && menu.classList.contains('is-open')) {
                closeMenu(menu);
                return;
            }

            if (closeTimers.has(menu)) {
                window.clearTimeout(closeTimers.get(menu));
                closeTimers.delete(menu);
            }

            menu.classList.remove('is-closing');
            menu.open = true;

            window.requestAnimationFrame(function () {
                menu.classList.add('is-open');
            });
        }

        menus.forEach(function (menu) {
            const summary = menu.querySelector('summary');

            if (!summary) {
                return;
            }

            summary.addEventListener('click', function (event) {
                event.preventDefault();
                openMenu(menu);
            });
        });

        document.addEventListener('click', function (event) {
            menus.forEach(function (menu) {
                if (menu.open && !menu.contains(event.target)) {
                    closeMenu(menu);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                menus.forEach(closeMenu);
            }
        });
    })();
    </script>
</body>
</html>
