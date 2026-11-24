
<?php

require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/PedidoModel.php';

verificarSesion(['administrador', 'cajero', 'mesero']);

$modelo = new PedidoModel();

$mesas = $modelo->listarMesas();
$productos = $modelo->listarProductosDisponibles();
$pedidos = $modelo->listarPedidosActivos();

$oks = [
    'mesa_creada' => 'Mesa agregada correctamente.',
    'mesa_estado' => 'Estado de la mesa actualizado.',
    'pedido_creado' => 'Pedido registrado correctamente.',
    'pedido_estado' => 'Estado del pedido actualizado.'
];

$errores = [
    'mesa_campos' => 'Ingresa un número y capacidad válidos.',
    'pedido_campos' => 'Tipo de pedido inválido.',
    'falta_mesa' => 'Selecciona una mesa.',
    'sin_productos' => 'Selecciona al menos un producto.',
    'bd' => 'Ocurrió un error al guardar en la base de datos.'
];

$msgOk = $oks[$_GET['ok'] ?? ''] ?? '';
$msgError = $errores[$_GET['error'] ?? ''] ?? '';

function escaparPedido($valor): string {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>MipiWeb POS - Pedidos y Mesas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap"
      rel="stylesheet">

    <link rel="stylesheet" href="assets/css/pedidos.css">
</head>

<body>

<header class="top-bar">

    <h1>Pedidos y Mesas</h1>

    <a href="panel_pos.php">
        ← Volver al panel
    </a>

</header>

<?php if ($msgOk): ?>
    <p class="msg-ok">
        <?= escaparPedido($msgOk) ?>
    </p>
<?php endif; ?>

<?php if ($msgError): ?>
    <p class="msg-error">
        <?= escaparPedido($msgError) ?>
    </p>
<?php endif; ?>


<!-- ====================================== -->
<!-- SECCIÓN DE MESAS -->
<!-- ====================================== -->

<section class="card">

    <h2>Mesas</h2>

    <form
        action="../controladores/PedidoController.php"
        method="POST"
        class="form-inline">

        <input
            type="hidden"
            name="accion"
            value="crear_mesa">

        <label>
            Número de mesa:

            <input
                type="number"
                name="numero"
                min="1"
                required>
        </label>

        <label>
            Capacidad:

            <input
                type="number"
                name="capacidad"
                min="1"
                required>
        </label>

        <button type="submit">
            Agregar mesa
        </button>

    </form>

    <?php if (empty($mesas)): ?>

        <p>No hay mesas registradas todavía.</p>

    <?php else: ?>

        <div class="mesas-grid">

            <?php foreach ($mesas as $mesa): ?>

                <article class="mesa-item mesa-<?= escaparPedido($mesa['estado']) ?>">

                    <h3>
                        Mesa #<?= (int)$mesa['numero'] ?>
                    </h3>

                    <p>
                        Capacidad:
                        <?= (int)$mesa['capacidad'] ?>
                        personas
                    </p>

                    <p>
                        Estado:
                        <strong>
                            <?= escaparPedido(ucfirst($mesa['estado'])) ?>
                        </strong>
                    </p>

                    <form
                        action="../controladores/PedidoController.php"
                        method="POST">

                        <input
                            type="hidden"
                            name="accion"
                            value="estado_mesa">

                        <input
                            type="hidden"
                            name="id_mesa"
                            value="<?= (int)$mesa['id_mesa'] ?>">

                        <select name="estado">

                            <option value="libre"
                                <?= $mesa['estado'] === 'libre' ? 'selected' : '' ?>>
                                Libre
                            </option>

                            <option value="ocupada"
                                <?= $mesa['estado'] === 'ocupada' ? 'selected' : '' ?>>
                                Ocupada
                            </option>

                            <option value="reservada"
                                <?= $mesa['estado'] === 'reservada' ? 'selected' : '' ?>>
                                Reservada
                            </option>

                        </select>

                        <button type="submit">
                            Cambiar
                        </button>

                    </form>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<!-- ====================================== -->
<!-- REGISTRAR NUEVO PEDIDO -->
<!-- ====================================== -->

<section class="card">

    <h2>Nuevo Pedido</h2>

    <form
        action="../controladores/PedidoController.php"
        method="POST">

        <input
            type="hidden"
            name="accion"
            value="crear_pedido">

        <div>

            <label>
                Tipo de pedido:

                <select name="tipo" required>
                    <option value="mesa">
                        En mesa
                    </option>

                    <option value="para_llevar">
                        Para llevar
                    </option>
                </select>

            </label>

            <label>
                Mesa (solo si es pedido en mesa):

                <select name="id_mesa">

                    <option value="">
                        -- Seleccionar mesa --
                    </option>

                    <?php foreach ($mesas as $mesa): ?>

                        <option value="<?= (int)$mesa['id_mesa'] ?>">

                            Mesa #<?= (int)$mesa['numero'] ?>
                            (<?= escaparPedido($mesa['estado']) ?>)

                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>
                Notas generales:

                <input
                    type="text"
                    name="notas"
                    placeholder="Ej. Sin picante, prioridad...">
            </label>

        </div>

        <table>

            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Observaciones</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($productos as $producto): ?>

                    <tr>

                        <td>
                            <?= escaparPedido($producto['categoria']) ?>
                        </td>

                        <td>
                            <?= escaparPedido($producto['nombre']) ?>
                        </td>

                        <td>
                            $<?= number_format(
                                (float)$producto['precio'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <input
                                type="number"
                                name="cantidad[<?= (int)$producto['id_producto'] ?>]"
                                min="0"
                                value="0">
                        </td>

                        <td>
                            <input
                                type="text"
                                name="observaciones[<?= (int)$producto['id_producto'] ?>]"
                                placeholder="Ej. Sin cebolla">
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

        <button type="submit">
            Registrar pedido
        </button>

    </form>

</section>


<!-- ====================================== -->
<!-- PEDIDOS ACTIVOS -->
<!-- ====================================== -->

<section class="card">

    <h2>Pedidos Activos</h2>

    <?php if (empty($pedidos)): ?>

        <p>No hay pedidos activos actualmente.</p>

    <?php else: ?>

        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo / Mesa / Entrega</th>
                    <th>Detalle</th>
                    <th>Notas</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($pedidos as $ped): ?>

                    <?php
                        $detalles = $modelo->obtenerDetallesPedido(
                            (int)$ped['id_pedido']
                        );
                    ?>

                    <tr>

                        <!-- ID -->
                        <td>
                            #<?= (int)$ped['id_pedido'] ?>
                        </td>

                        <!-- TIPO Y DATOS DE ENTREGA -->
                        <td>

                            <strong>
                                <?php
                                    if ($ped['tipo'] === 'en_linea') {
                                        echo 'A domicilio';
                                    } elseif ($ped['tipo'] === 'mesa') {
                                        echo 'En mesa';
                                    } else {
                                        echo 'Para llevar';
                                    }
                                ?>
                            </strong>

                            <br>

                            <?php if (!empty($ped['numero_mesa'])): ?>

                                Mesa #<?= (int)$ped['numero_mesa'] ?>

                                <br>

                            <?php endif; ?>

                            <?php if ($ped['tipo'] === 'en_linea'): ?>

                                <div class="datos-entrega">

                                    <h4>Datos de entrega</h4>

                                    <p>
                                        <strong>Cliente:</strong>
                                        <?= escaparPedido(
                                            $ped['nombre_contacto'] ?? 'No registrado'
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Teléfono:</strong>
                                        <?= escaparPedido(
                                            $ped['telefono'] ?? 'No registrado'
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Dirección:</strong>
                                        <?= nl2br(escaparPedido(
                                            $ped['direccion'] ?? 'No registrada'
                                        )) ?>
                                    </p>

                                    <?php if (!empty($ped['referencias'])): ?>

                                        <p>
                                            <strong>Referencias:</strong>
                                            <?= nl2br(escaparPedido(
                                                $ped['referencias']
                                            )) ?>
                                        </p>

                                    <?php endif; ?>

                                </div>

                            <?php else: ?>

                                <small>
                                    <?= escaparPedido(
                                        $ped['mesero'] ?? 'Sin asignar'
                                    ) ?>
                                </small>

                            <?php endif; ?>

                        </td>

                        <!-- PRODUCTOS -->
                        <td>

                            <ul>

                                <?php foreach ($detalles as $detalle): ?>

                                    <li>

                                        <?= (int)$detalle['cantidad'] ?>x

                                        <?= escaparPedido(
                                            $detalle['producto']
                                        ) ?>

                                        ($<?= number_format(
                                            (float)$detalle['precio_unitario'],
                                            2
                                        ) ?>)

                                        <?php if (!empty($detalle['observaciones'])): ?>

                                            <em>
                                                <?= escaparPedido(
                                                    $detalle['observaciones']
                                                ) ?>
                                            </em>

                                        <?php endif; ?>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </td>

                        <!-- NOTAS -->
                        <td>
                            <?= escaparPedido($ped['notas'] ?? '—') ?>
                        </td>

                        <!-- TOTAL -->
                        <td>

                            <strong>
                                $<?= number_format(
                                    (float)$ped['total'],
                                    2
                                ) ?>
                            </strong>

                        </td>

                        <!-- CAMBIAR ESTADO -->
                        <td>

                            <form
                                action="../controladores/PedidoController.php"
                                method="POST">

                                <input
                                    type="hidden"
                                    name="accion"
                                    value="estado_pedido">

                                <input
                                    type="hidden"
                                    name="id_pedido"
                                    value="<?= (int)$ped['id_pedido'] ?>">

                                <select name="estado">

                                    <?php
                                        $estados = [
                                            'recibido' => 'Recibido',
                                            'en_preparacion' => 'En preparación',
                                            'listo' => 'Listo',
                                            'entregado' => 'Entregado',
                                            'cancelado' => 'Cancelado'
                                        ];
                                    ?>

                                    <?php foreach ($estados as $valor => $texto): ?>

                                        <option
                                            value="<?= escaparPedido($valor) ?>"
                                            <?= $ped['estado'] === $valor ? 'selected' : '' ?>>

                                            <?= escaparPedido($texto) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button type="submit">
                                    Actualizar
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>

</section>

</body>
</html>
