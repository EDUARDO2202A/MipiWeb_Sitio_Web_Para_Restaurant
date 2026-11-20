<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/MenuModel.php';

verificarSesion(['administrador']);

$modelo = new MenuModel();
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

function subirImagen(): ?string {
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }
    $carpeta = __DIR__ . '/../vistas/assets/img/productos';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    $nombreArchivo = uniqid('prod_') . '.' . $ext;
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $carpeta . '/' . $nombreArchivo)) {
        return 'vistas/assets/img/productos/' . $nombreArchivo;
    }
    return null;
}

try {
    if ($accion === 'crear_categoria' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre'] ?? '');
        $area   = $_POST['area'] ?? 'cocina';
        if ($nombre === '' || !in_array($area, ['cocina', 'barra'], true)) {
            header('Location: ../vistas/menu_pos.php?error=campos');
            exit;
        }
        $modelo->crearCategoria($nombre, $area);
        header('Location: ../vistas/menu_pos.php?ok=cat_creada');
        exit;
    }

    if ($accion === 'editar_categoria' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id     = (int)($_POST['id_categoria'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $area   = $_POST['area'] ?? 'cocina';
        if ($id <= 0 || $nombre === '' || !in_array($area, ['cocina', 'barra'], true)) {
            header('Location: ../vistas/menu_pos.php?error=campos');
            exit;
        }
        $modelo->actualizarCategoria($id, $nombre, $area);
        header('Location: ../vistas/menu_pos.php?ok=cat_editada');
        exit;
    }

    if ($accion === 'crear_producto' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $idCat  = (int)($_POST['id_categoria'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');
        $precio = (float)($_POST['precio'] ?? -1);

        if ($idCat <= 0 || $nombre === '' || $precio < 0) {
            header('Location: ../vistas/menu_pos.php?error=campos');
            exit;
        }
        $imagen = subirImagen();
        $modelo->crearProducto($idCat, $nombre, $desc, $precio, $imagen);
        header('Location: ../vistas/menu_pos.php?ok=prod_creado');
        exit;
    }

    if ($accion === 'editar_producto' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id     = (int)($_POST['id_producto'] ?? 0);
        $idCat  = (int)($_POST['id_categoria'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');
        $precio = (float)($_POST['precio'] ?? -1);

        if ($id <= 0 || $idCat <= 0 || $nombre === '' || $precio < 0) {
            header('Location: ../vistas/menu_pos.php?error=campos');
            exit;
        }
        $imagen = subirImagen();
        $modelo->actualizarProducto($id, $idCat, $nombre, $desc, $precio, $imagen);
        header('Location: ../vistas/menu_pos.php?ok=prod_editado');
        exit;
    }

    if ($accion === 'toggle_producto') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $modelo->cambiarDisponibilidad($id);
        }
        header('Location: ../vistas/menu_pos.php?ok=prod_estado');
        exit;
    }
} catch (PDOException $e) {
    header('Location: ../vistas/menu_pos.php?error=bd');
    exit;
}

header('Location: ../vistas/menu_pos.php');
exit;