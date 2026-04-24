<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Prevention Control System') ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@NaN,0,1,0&icon_names=settings" />
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
    </script>
</body>
</html>
