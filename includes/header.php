<?php
require_once __DIR__ . '/functions.php';
$currentPage = $page ?? 'dashboard';
[$heading, $subtitle] = pageTitle($currentPage);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($heading) ?> | LinuxCore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="index.php">
            <div class="brand-mark"><span>LN</span></div>
            <div class="brand-name">Linux<span>Core</span></div>
        </a>
        <nav class="navigation">
            <?php foreach (navItems() as $key => $item): ?>
                <a class="nav-link <?= $currentPage === $key ? 'active' : '' ?>" href="index.php?page=<?= e($key) ?>">
                    <i class="fa-solid <?= e($item[1]) ?>"></i><span><?= e($item[0]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Mostrar u ocultar menú"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-actions">
                <button class="user-menu" type="button"><i class="fa-solid fa-user"></i><span>admin</span><i class="fa-solid fa-chevron-down small-chevron"></i></button>
                <span class="top-divider"></span>
                <a class="logout" href="login.php"><i class="fa-solid fa-right-from-bracket"></i><span>Cerrar sesión</span></a>
            </div>
        </header>
        <section class="content">
            <?php if ($currentPage !== 'dashboard'): ?>
                <div class="page-heading">
                    <div><h1><?= e($heading) ?></h1><p><?= e($subtitle) ?></p></div>
                    <?php if ($currentPage === 'usuarios'): ?><button class="btn btn-primary" data-modal="userModal"><i class="fa-solid fa-plus"></i> Nuevo usuario</button><?php endif; ?>
                    <?php if ($currentPage === 'reportes'): ?><button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir reporte</button><?php endif; ?>
                </div>
            <?php endif; ?>
