
<?php

session_start();

require_once __DIR__ . '/../modelos/PedidoPublicoModel.php';


$pagina = '../vistas/pedido_domicilio.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $pagina");
    exit;
}


$token = $_POST['csrf_pedido'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_pedido']) ||
    !hash_equals($_SESSION['csrf_pedido'], $token)
) {
    header("Location: $pagina?error=seguridad");
    exit;
}


$nombre = $_POST['nombre'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$referencias = $_POST['referencias'] ?? '';


if (
    !is_string($nombre) ||
    !is_string($telefono) ||
    !is_string($direccion) ||
    !is_string($referencias)
) {
    header("Location: $pagina?error=datos");
    exit;
}

$nombre = trim($nombre);
$telefono = trim($telefono);
$direccion = trim($direccion);
$referencias = trim($referencias);

if (
    $nombre === '' ||
    mb_strlen($nombre) > 120 ||
    !preg_match('/^[0-9]{10}$/', $telefono) ||
    $direccion === '' ||
    mb_strlen($direccion) > 500 ||
    mb_strlen($referencias) > 500
) {
    header("Location: $pagina?error=datos");
    exit;
}


$carritoJSON = $_POST['carrito'] ?? '';

if (
    !is_string($carritoJSON) ||
    strlen($carritoJSON) > 10000
) {
    header("Location: $pagina?error=carrito");
    exit;
}

$carrito = json_decode($carritoJSON, true);



if (
    !is_array($carrito) ||
    array_keys($carrito) !== range(0, count($carrito) - 1) ||
    count($carrito) < 1 ||
    count($carrito) > 20
) {
    header("Location: $pagina?error=carrito");
    exit;
}



$productos = [];
$idsUsados = [];

foreach ($carrito as $item) {

    if (!is_array($item)) {
        header("Location: $pagina?error=carrito");
        exit;
    }

    $idProducto = filter_var(
        $item['id_producto'] ?? null,
        FILTER_VALIDATE_INT
    );

    $cantidad = filter_var(
        $item['cantidad'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        !$idProducto ||
        $idProducto <= 0 ||
        !$cantidad ||
        $cantidad < 1 ||
        $cantidad > 50 ||
        isset($idsUsados[$idProducto])
    ) {
        header("Location: $pagina?error=carrito");
        exit;
    }

    $idsUsados[$idProducto] = true;

    $productos[] = [
        'id_producto' => $idProducto,
        'cantidad' => $cantidad
    ];
}


try {

    $modelo = new PedidoPublicoModel();

    $idPedido = $modelo->crearPedidoDomicilio(
        $nombre,
        $telefono,
        $direccion,
        $referencias,
        $productos
    );

  
    unset($_SESSION['csrf_pedido']);

    $_SESSION['pedido_confirmado'] = $idPedido;

    header("Location: $pagina?ok=pedido");
    exit;

} catch (Throwable $e) {

    error_log(
        'Error en pedido a domicilio: ' . $e->getMessage()
    );

    header("Location: $pagina?error=operacion");
    exit;
}
