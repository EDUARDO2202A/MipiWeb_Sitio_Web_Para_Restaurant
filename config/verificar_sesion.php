<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarSesion(array $rolesPermitidos = []): void {
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ../vistas/login_pos.php');
        exit;
    }

    if (!empty($rolesPermitidos) && !in_array($_SESSION['rol'], $rolesPermitidos, true)) {
        http_response_code(403);
        echo '<h2>Acceso denegado</h2><p>No tienes permisos para ver esta sección.</p><a href="../vistas/panel_pos.php">Volver al panel</a>';
        exit;
    }
}