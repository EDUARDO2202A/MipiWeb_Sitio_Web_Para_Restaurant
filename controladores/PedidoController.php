<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/PedidoModel.php';

verificarSesion(['administrador', 'cajero', 'mesero']);

$modelo = new PedidoModel();
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    if ($accion === 'crear_mesa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $numero    = (int)($_POST['numero'] ?? 0);
        $capacidad = (int)($_POST['capacidad'] ?? 0);

        if ($numero <= 0 || $capacidad <= 0) {
            header('Location: ../vistas/pedidos.php?error=mesa_campos');
            exit;
        }
        $modelo->crearMesa($numero, $capacidad);
        header('Location: ../vistas/pedidos.php?ok=mesa_creada');
        exit;
    }

    if ($accion === 'estado_mesa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $idMesa = (int)($_POST['id_mesa'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        if ($idMesa > 0 && in_array($estado, ['libre', 'ocupada', 'reservada'], true)) {
            $modelo->cambiarEstadoMesa($idMesa, $estado);
        }
        header('Location: ../vistas/pedidos.php?ok=mesa_estado');
        exit;
    }

    if ($accion === 'crear_pedido' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo   = $_POST['tipo'] ?? 'mesa';
        $idMesa = !empty($_POST['id_mesa']) ? (int)$_POST['id_mesa'] : null;
        $notas  = trim($_POST['notas'] ?? '');

        if (!in_array($tipo, ['mesa', 'para_llevar'], true)) {
            header('Location: ../vistas/pedidos.php?error=pedido_campos');
            exit;
        }
        if ($tipo === 'mesa' && !$idMesa) {
            header('Location: ../vistas/pedidos.php?error=falta_mesa');
            exit;
        }

        $cantidades    = $_POST['cantidad'] ?? [];
        $observaciones = $_POST['observaciones'] ?? [];
        $items = [];

        foreach ($cantidades as $idProd => $cant) {
            $cantInt = (int)$cant;
            if ($cantInt > 0) {
                $items[] = [
                    'id_producto'   => (int)$idProd,
                    'cantidad'      => $cantInt,
                    'observaciones' => $observaciones[$idProd] ?? '',
                ];
            }
        }

        if (empty($items)) {
            header('Location: ../vistas/pedidos.php?error=sin_productos');
            exit;
        }

        $modelo->crearPedido($tipo, $idMesa, (int)$_SESSION['id_usuario'], $notas, $items);
        header('Location: ../vistas/pedidos.php?ok=pedido_creado');
        exit;
    }

    if ($accion === 'estado_pedido' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $idPedido = (int)($_POST['id_pedido'] ?? 0);
        $estado   = $_POST['estado'] ?? '';
        $validos  = ['recibido', 'en_preparacion', 'listo', 'entregado', 'cancelado'];

        if ($idPedido > 0 && in_array($estado, $validos, true)) {
            $modelo->cambiarEstadoPedido($idPedido, $estado);
        }
        header('Location: ../vistas/pedidos.php?ok=pedido_estado');
        exit;
    }
} catch (Throwable $e) {
    header('Location: ../vistas/pedidos.php?error=bd');
    exit;
}

header('Location: ../vistas/pedidos.php');
exit;