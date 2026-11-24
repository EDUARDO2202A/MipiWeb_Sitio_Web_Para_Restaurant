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
    <link rel="stylesheet" href="assets/css/panel.css">
</head>
<body>
    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
    <p>Rol actual: <strong><?= htmlspecialchars(ucfirst($rol)) ?></strong></p>

    <ul>
        <?php if ($rol === 'administrador'): ?>
            <li><a href="menu_pos.php">Gestión del menú (categorías y productos)</a></li>
            <li><a href="usuarios_pos.php">Gestión de usuarios y roles</a></li>
            <li><a href="reportes_pos.php">Reportes de ventas</a></li>
        <?php endif; ?>

        <?php if ($rol === 'administrador' || $rol === 'cajero'): ?>
            <li><a href="caja_pos.php">Turnos de caja y cobros</a></li>
            <li>
    <a href="reservaciones_pos.php">
        Gestión de reservaciones
    </a>
</li>
        <?php endif; ?>

        <li><a href="pedidos.php">Pedidos y mesas</a></li>
    </ul>

    <a href="../controladores/LoginController.php?accion=logout">Cerrar sesión</a>
</body>
</html>