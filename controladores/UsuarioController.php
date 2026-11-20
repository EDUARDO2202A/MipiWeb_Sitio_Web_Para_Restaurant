<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/UsuarioModel.php';

verificarSesion(['administrador']);

$modelo = new UsuarioModel();
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $idRol     = (int)($_POST['id_rol'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $usuario   = trim($_POST['usuario'] ?? '');
        $password  = $_POST['contrasena'] ?? '';

        if ($idRol <= 0 || $nombre === '' || $apellidos === '' || $usuario === '' || $password === '') {
            header('Location: ../vistas/usuarios_pos.php?error=campos');
            exit;
        }

        $modelo->crear($idRol, $nombre, $apellidos, $usuario, $password);
        header('Location: ../vistas/usuarios_pos.php?ok=creado');
        exit;
    }

    if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id        = (int)($_POST['id_usuario'] ?? 0);
        $idRol     = (int)($_POST['id_rol'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $usuario   = trim($_POST['usuario'] ?? '');
        $password  = $_POST['contrasena'] ?? '';

        if ($id <= 0 || $idRol <= 0 || $nombre === '' || $apellidos === '' || $usuario === '') {
            header('Location: ../vistas/usuarios_pos.php?error=campos');
            exit;
        }

        $modelo->actualizar($id, $idRol, $nombre, $apellidos, $usuario, $password);
        header('Location: ../vistas/usuarios_pos.php?ok=editado');
        exit;
    }

    if ($accion === 'toggle') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === (int)$_SESSION['id_usuario']) {
            header('Location: ../vistas/usuarios_pos.php?error=propio');
            exit;
        }
        if ($id > 0) {
            $modelo->cambiarEstado($id);
        }
        header('Location: ../vistas/usuarios_pos.php?ok=estado');
        exit;
    }
} catch (PDOException $e) {
    header('Location: ../vistas/usuarios_pos.php?error=duplicado');
    exit;
}

header('Location: ../vistas/usuarios_pos.php');
exit;