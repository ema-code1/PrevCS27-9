<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Prevention Control System') ?></title>
    <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="<?= esc($bodyClass ?? 'theme-light') ?>">
    <div class="app-shell">
        <?php if (($showHeader ?? true) === true): ?>
            <?= $this->include('templates/header') ?>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
        <?= $this->renderSection('pageScripts') ?>
    </div>
</body>
</html>
