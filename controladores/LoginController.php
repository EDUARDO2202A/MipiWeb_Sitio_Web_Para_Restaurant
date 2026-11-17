<?php
session_start();
require_once __DIR__ . '/../modelos/UsuarioModel.php';

// Cerrar sesión
if (isset($_GET['accion']) && $_GET['accion'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: ../vistas/login_pos.php');
    exit;
}

// Iniciar sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['contrasena'] ?? '';

    if ($usuario === '' || $password === '') {
        header('Location: ../vistas/login_pos.php?error=campos');
        exit;
    }

    $modelo = new UsuarioModel();
    $datos = $modelo->obtenerPorUsuario($usuario);

    if (!$datos || !filter_var($datos['activo'], FILTER_VALIDATE_BOOLEAN)) {
        header('Location: ../vistas/login_pos.php?error=inactivo');
        exit;
    }

    if (password_verify($password, $datos['contrasena_hash'])) {
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = $datos['id_usuario'];
        $_SESSION['nombre']     = $datos['nombre'] . ' ' . $datos['apellidos'];
        $_SESSION['usuario']    = $datos['usuario'];
        $_SESSION['id_rol']     = $datos['id_rol'];
        $_SESSION['rol']        = strtolower($datos['rol']);

        header('Location: ../vistas/panel_pos.php');
        exit;
    }

    header('Location: ../vistas/login_pos.php?error=credenciales');
    exit;
}