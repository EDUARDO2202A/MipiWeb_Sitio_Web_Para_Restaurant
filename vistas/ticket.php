<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/CajaModel.php';

verificarSesion(['administrador', 'cajero']);

$idPedido = (int)($_GET['id_pedido'] ?? 0);
$modelo   = new CajaModel();
$datos    = $idPedido > 0 ? $modelo->obtenerDatosTicket($idPedido) : null;

if (!$datos) {
    echo '<p>Pedido no encontrado.</p><a href="caja_pos.php">Volver a caja</a>';
    exit;
}

$rest    = $datos['restaurante'];
$pedido  = $datos['pedido'];
$totalPagado = 0.0;
foreach ($datos['pagos'] as $pg) {
    $totalPagado += (float)$pg['monto'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Pedido #<?= (int)$pedido['id_pedido'] ?></title>
    <link rel="stylesheet" href="assets/css/ticket.css">
</head>
<body>
    <main class="ticket">
        <header>
            <h1><?= htmlspecialchars($rest['nombre']) ?></h1>
            <?php if (!empty($rest['direccion'])): ?>
                <p><?= htmlspecialchars($rest['direccion']) ?></p>
            <?php endif; ?>
            <?php if (!empty($rest['telefono'])): ?>
                <p>Tel: <?= htmlspecialchars($rest['telefono']) ?></p>
            <?php endif; ?>
        </header>

        <section>
            <p><strong>Ticket Pedido #<?= (int)$pedido['id_pedido'] ?></strong></p>
            <p>Fecha: <?= htmlspecialchars($pedido['fecha_pedido']) ?></p>
            <p>
                Tipo: <?= htmlspecialchars(str_replace('_', ' ', ucfirst($pedido['tipo']))) ?>
                <?= $pedido['numero_mesa'] ? ' | Mesa #' . (int)$pedido['numero_mesa'] : '' ?>
            </p>
            <?php if (!empty($pedido['atendio'])): ?>
                <p>Atendió: <?= htmlspecialchars($pedido['atendio']) ?></p>
            <?php endif; ?>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Cant</th>
                    <th>Producto</th>
                    <th>Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos['detalles'] as $d): ?>
                    <tr>
                        <td><?= (int)$d['cantidad'] ?></td>
                        <td><?= htmlspecialchars($d['producto']) ?></td>
                        <td>$<?= number_format((float)$d['cantidad'] * (float)$d['precio_unitario'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <section class="totales">
            <p><strong>Total del pedido: $<?= number_format((float)$pedido['total'], 2) ?></strong></p>
            <?php foreach ($datos['pagos'] as $pg): ?>
                <p>Pago (<?= htmlspecialchars(ucfirst($pg['metodo'])) ?>): $<?= number_format((float)$pg['monto'], 2) ?></p>
            <?php endforeach; ?>
            <p><strong>Total pagado: $<?= number_format($totalPagado, 2) ?></strong></p>
        </section>

        <footer class="acciones-ticket">
            <button type="button" onclick="window.print()">Imprimir ticket</button>
            <a href="caja_pos.php">← Volver a caja</a>
        </footer>
    </main>
</body>
</html>