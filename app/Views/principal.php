<?php
// principal.php
// Página principal del sistema.
// Más adelante, el header puede extraerse a un template, por ejemplo:
// require_once __DIR__ . '/partials/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Prevention Control System | Principal</title>

  <!-- Hoja global -->
  <link rel="stylesheet" href="styles.css" />

  <!-- Ejemplo para sumar otra hoja particular de esta vista -->
  <!-- <link rel="stylesheet" href="principal.css" /> -->
</head>
<body class="theme-light">
  <div class="app-shell">

    <!-- HEADER | luego podés mover este bloque a un template -->
    <header class="topbar">
      <div class="brand">
        <div class="brand__orb"></div>

        <div class="brand__text">
          <span>Prevention</span>
          <span>Control</span>
          <span>System</span>
        </div>
      </div>

      <button class="user-chip" type="button" aria-label="Perfil de usuario">
        <span class="user-chip__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
            <path d="M4 20c1.6-4 5-6 8-6s6.4 2 8 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </span>
        <span class="user-chip__label">Usuario</span>
      </button>
    </header>

    <div class="top-strip"></div>

    <main class="page">
      <aside class="sidebar card card--dark">
        <div class="sidebar__head">
          <h2>Menú de acción</h2>
          <button class="sidebar__toggle" type="button" aria-label="Contraer menú">‹</button>
        </div>

        <nav class="sidebar__nav" aria-label="Menú principal">
          <a href="#" class="menu-link">
            <span class="menu-link__icon">(icon)</span>
            <span>Apartado</span>
          </a>

          <a href="#" class="menu-link">
            <span class="menu-link__icon">(icon)</span>
            <span>Apartado</span>
          </a>

          <a href="#" class="menu-link">
            <span class="menu-link__icon">(icon)</span>
            <span>Apartado</span>
          </a>

          <a href="#" class="menu-link">
            <span class="menu-link__icon">(icon)</span>
            <span>Apartado</span>
          </a>
        </nav>
      </aside>

      <section class="content-area">
        <div class="tabbar-wrap">
          <nav class="tabbar" aria-label="Secciones principales">
            <button class="tabbar__item is-active" type="button">Principal</button>
            <button class="tabbar__item" type="button">Graficos</button>
            <button class="tabbar__item" type="button">Elements List</button>
          </nav>
        </div>

        <div class="dashboard-grid">
          <section class="panel panel--small card" aria-label="Tarjeta pequeña"></section>
          <section class="panel panel--wide card" aria-label="Tarjeta horizontal"></section>
          <section class="panel panel--full card" aria-label="Tarjeta grande"></section>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
