<?php
$activeMenu = $activeMenu ?? 'panel';

// Espacios del usuario para el desplegable de Stock. Se resuelve acá (y no en
// cada controller) porque el sidebar se incluye desde muchas vistas; si algún
// controller ya lo pasó, se respeta.
$sidebarSpaces = $sidebarSpaces ?? null;
if ($sidebarSpaces === null) {
    $sidebarUserId = (int) session('user_id');
    $sidebarSpaces = $sidebarUserId > 0
        ? (new \App\Models\IndustryUserModel())->industriesForUser($sidebarUserId)
        : [];
}
$activeStockId = (int) ($activeStockIndustryId ?? 0);
?>
<div class="sidebar-backdrop" data-sidebar-backdrop aria-hidden="true"></div>
<aside class="sidebar" data-sidebar>
    <div class="sidebar__head">
        <h2 class="sidebar__title">Navegacion</h2>
        <label class="burger sidebar__toggle" for="burger">
            <input type="checkbox" id="burger" data-sidebar-toggle aria-label="Contraer menu lateral">
            <span></span><span></span><span></span>
        </label>
    </div>

    <nav class="sidebar__nav" aria-label="Menu principal">
        <a href="<?= base_url('principal') ?>" class="menu-link<?= $activeMenu === 'panel' ? ' is-active' : '' ?>">
            <span class="menu-link__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 11.5 12 5l8 6.5v7.5a1 1 0 0 1-1 1h-4.5v-5h-5v5H5a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="menu-link__label">Panel</span>
        </a>

        <a href="<?= base_url('registros-iot') ?>" class="menu-link<?= $activeMenu === 'registros-iot' ? ' is-active' : '' ?>">
            <span class="menu-link__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="4.5" y="5" width="15" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="menu-link__label">Mis espacios</span>
        </a>

        <?php // Stock no navega: despliega la lista de espacios accesibles. ?>
        <button type="button"
                class="menu-link menu-link--toggle<?= $activeMenu === 'stock' ? ' is-open' : '' ?>"
                data-stock-toggle
                aria-expanded="<?= $activeMenu === 'stock' ? 'true' : 'false' ?>"
                aria-controls="sidebar-stock-panel">
            <span class="menu-link__icon" aria-hidden="true">
                <!-- Heroicons outline · cube -->
                <svg viewBox="0 0 24 24" fill="none"><path d="M21 7.5 12 12m0 0L3 7.5M12 12v9.75M21 7.5v9l-9 5.25L3 16.5v-9L12 2.25 21 7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="menu-link__label">Stock</span>
            <span class="menu-link__chevron" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        </button>

        <div class="menu-sub<?= $activeMenu === 'stock' ? ' is-open' : '' ?>" id="sidebar-stock-panel" data-stock-panel>
            <div class="menu-sub__inner">
                <?php if ($sidebarSpaces === []): ?>
                    <p class="menu-sub__empty">Todavía no tenés espacios.</p>
                <?php else: ?>
                    <?php foreach ($sidebarSpaces as $space): ?>
                        <a class="menu-sub__link<?= $activeStockId === (int) $space['id'] ? ' is-active' : '' ?>"
                           href="<?= base_url('espacios/' . $space['id'] . '/stock') ?>">
                            <span class="menu-sub__dot" aria-hidden="true"></span>
                            <span class="menu-sub__label"><?= esc($space['company_name'] ?: $space['space_name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="sidebar__user-slot" data-sidebar-user-slot></div>

    <div class="sidebar__foot" aria-hidden="true">
        <span class="dot"></span>
        <span>IoT</span>
    </div>
</aside>
