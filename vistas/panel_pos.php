<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login_pos.php');
    exit;
}
$rol = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Panel</title>
</head>
<body style="font-family: sans-serif; padding: 2rem;">
    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
    <p>Rol actual: <strong><?= htmlspecialchars(ucfirst($rol)) ?></strong></p>

    <ul>
        <?php if ($rol === 'administrador'): ?>
            <li><a href="menu_pos.php">Gestión del menú (categorías y productos)</a></li>
            <li><a href="usuarios_pos.php">Gestión de usuarios y roles</a></li>
            <li>Reportes de ventas</li>
        <?php endif; ?>

        <?php if ($rol === 'administrador' || $rol === 'cajero'): ?>
            <li>Turnos de caja y cobros</li>
            <li>Reservaciones</li>
        <?php endif; ?>

        <li>Pedidos y mesas</li>
    </ul>

    <a href="../controladores/LoginController.php?accion=logout">Cerrar sesión</a>
</body>
</html>