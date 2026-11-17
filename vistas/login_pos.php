<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header('Location: panel_pos.php');
    exit;
}

$errores = [
    'campos'       => 'Completa usuario y contraseña.',
    'inactivo'     => 'Usuario no encontrado o desactivado.',
    'credenciales' => 'Usuario o contraseña incorrectos.',
];
$msgError = $errores[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Iniciar sesión</title>

    <link rel="stylesheet" href="assets/css/estilos.css">
</head>

    <body class="login-page">
    <form class="card" action="../controladores/LoginController.php" method="POST">
        <h2>Iniciar Sesión</h2>
        <?php if ($msgError): ?>
            <div class="error"><?= htmlspecialchars($msgError) ?></div>
        <?php endif; ?>

        <label for="usuario">Usuario de trabajo</label>
        <input type="text" id="usuario" name="usuario" required>

        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" required>

        <button type="submit">Entrar</button>
    </form>
    <div class="portada-imagen">
        <img src="<?= htmlspecialchars($restaurante['logo'] ?? 'https://tse1.mm.bing.net/th/id/OIP.jMuVPY4GmoP4S2jJvJQJOwAAAA?r=0&rs=1&pid=ImgDetMain&o=7&rm=3'); ?>" 
             alt="Instalaciones del restaurante">
      </div>
</body>
</html>