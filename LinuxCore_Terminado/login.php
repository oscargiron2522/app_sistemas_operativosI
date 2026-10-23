<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicio de sesión de demostración. No valida usuarios reales todavía.
    $_SESSION['linuxcore_demo_user'] = trim($_POST['usuario'] ?? 'admin') ?: 'admin';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión | LinuxCore</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-page">
<form class="login-card" method="post">
    <div class="login-brand">
        <div class="brand-mark"><span>LN</span></div>
        <div class="brand-name">Linux<span>Core</span></div>
        <p>Monitor y Administrador Web del Sistema Operativo</p>
    </div>
    <label class="login-input"><i class="fa-solid fa-user"></i><input name="usuario" placeholder="Usuario" required value="admin"></label>
    <label class="login-input"><i class="fa-solid fa-lock"></i><input name="password" type="password" placeholder="Contraseña" required value="admin"></label>
    <button class="btn btn-primary login-submit" type="submit">Iniciar Sesión</button>
    <p class="login-note">Prototipo visual: usa cualquier usuario y contraseña.</p>
</form>
</body>
</html>
