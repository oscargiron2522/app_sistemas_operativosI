<?php
$page = isset($_GET['page']) ? preg_replace('/[^a-z]/', '', $_GET['page']) : 'dashboard';
$allowed = ['dashboard','procesos','cpu','planificacion','interbloqueos','almacenamiento','usuarios','terminal','reportes'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
require __DIR__ . '/includes/header.php';
switch ($page) {
case 'procesos': require __DIR__ . '/pages/procesos.php'; break;
case 'cpu': require __DIR__ . '/pages/cpu.php'; break;
case 'planificacion': require __DIR__ . '/pages/planificacion.php'; break;
case 'interbloqueos': require __DIR__ . '/pages/interbloqueos.php'; break;
case 'almacenamiento': require __DIR__ . '/pages/almacenamiento.php'; break;
case 'usuarios': require __DIR__ . '/pages/usuarios.php'; break;
case 'terminal': require __DIR__ . '/pages/terminal.php'; break;
case 'reportes': require __DIR__ . '/pages/reportes.php'; break;
default: require __DIR__ . '/pages/dashboard.php';
}
require __DIR__ . '/includes/footer.php';
