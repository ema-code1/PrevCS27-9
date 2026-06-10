<?php
$activeMenu = $activeMenu ?? 'panel';
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

        <a href="<?= base_url('esp32') ?>" class="menu-link<?= $activeMenu === 'esp32' ? ' is-active' : '' ?>">
            <span class="menu-link__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="6" y="4.5" width="12" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M9 4.5V3M15 4.5V3M9 21v-1.5M15 21v-1.5M6 9H4.5M6 13H4.5M19.5 9H18M19.5 13H18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M9.5 9.5h5M9.5 12h5M9.5 14.5h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="menu-link__label">Mis ESP32</span>
        </a>

        <a href="<?= base_url('registros-iot') ?>" class="menu-link<?= $activeMenu === 'registros-iot' ? ' is-active' : '' ?>">
            <span class="menu-link__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="4.5" y="5" width="15" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="menu-link__label">Espacios</span>
        </a>
    </nav>

    <div class="sidebar__user-slot" data-sidebar-user-slot></div>

    <div class="sidebar__foot" aria-hidden="true">
        <span class="dot"></span>
        <span>IoT</span>
    </div>
</aside>
