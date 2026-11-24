<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/PedidoModel.php';

verificarSesion(['administrador', 'cajero', 'mesero']);

$modelo    = new PedidoModel();
$mesas     = $modelo->listarMesas();
$productos = $modelo->listarProductosDisponibles();
$pedidos   = $modelo->listarPedidosActivos();

$oks = [
    'mesa_creada'   => 'Mesa agregada.',
    'mesa_estado'   => 'Estado de la mesa actualizado.',
    'pedido_creado' => 'Pedido registrado correctamente.',
    'pedido_estado' => 'Estado del pedido actualizado.',
];
$errores = [
    'mesa_campos'   => 'Ingresa un número y capacidad válidos para la mesa.',
    'pedido_campos' => 'Tipo de pedido inválido.',
    'falta_mesa'    => 'Selecciona una mesa para pedidos en mesa.',
    'sin_productos' => 'Elige al menos un producto con cantidad mayor a 0.',
    'bd'            => 'Error al guardar en base de datos (posible número de mesa duplicado).',
];
$msgOk    = $oks[$_GET['ok'] ?? ''] ?? '';
$msgError = $errores[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Pedidos y Mesas</title>
    <link rel="stylesheet" href="assets/css/pedidos.css">
</head>
<body>
    <header class="top-bar">
        <h1>Pedidos y Mesas</h1>
        <a href="panel_pos.php">← Volver al panel</a>
    </header>

    <?php if ($msgOk): ?><p class="msg-ok"><?= htmlspecialchars($msgOk) ?></p><?php endif; ?>
    <?php if ($msgError): ?><p class="msg-error"><?= htmlspecialchars($msgError) ?></p><?php endif; ?>

    <!-- SECCIÓN MESAS -->
    <section class="card">
        <h2>Mesas</h2>

        <form action="../controladores/PedidoController.php" method="POST" class="form-inline">
            <input type="hidden" name="accion" value="crear_mesa">
            <label>Número de mesa:
                <input type="number" name="numero" min="1" required>
            </label>
            <label>Capacidad:
                <input type="number" name="capacidad" min="1" required>
            </label>
            <button type="submit">Agregar mesa</button>
        </form>

        <?php if (empty($mesas)): ?>
            <p>No hay mesas registradas todavía.</p>
        <?php else: ?>
            <div class="mesas-grid">
                <?php foreach ($mesas as $m): ?>
                    <article class="mesa-item mesa-<?= htmlspecialchars($m['estado']) ?>">
                        <h3>Mesa #<?= (int)$m['numero'] ?></h3>
                        <p>Capacidad: <?= (int)$m['capacidad'] ?> personas</p>
                        <p>Estado: <strong><?= htmlspecialchars(ucfirst($m['estado'])) ?></strong></p>

                        <form action="../controladores/PedidoController.php" method="POST">
                            <input type="hidden" name="accion" value="estado_mesa">
                            <input type="hidden" name="id_mesa" value="<?= (int)$m['id_mesa'] ?>">
                            <select name="estado">
                                <option value="libre" <?= $m['estado'] === 'libre' ? 'selected' : '' ?>>Libre</option>
                                <option value="ocupada" <?= $m['estado'] === 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
                                <option value="reservada" <?= $m['estado'] === 'reservada' ? 'selected' : '' ?>>Reservada</option>
                            </select>
                            <button type="submit">Cambiar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- SECCIÓN NUEVO PEDIDO -->
    <section class="card">
        <h2>Nuevo Pedido</h2>
        <form action="../controladores/PedidoController.php" method="POST">
            <input type="hidden" name="accion" value="crear_pedido">

            <div>
                <label>Tipo de pedido:
                    <select name="tipo" required>
                        <option value="mesa">En mesa</option>
                        <option value="para_llevar">Para llevar</option>
                    </select>
                </label>

                <label>Mesa (solo si es en mesa):
                    <select name="id_mesa">
                        <option value="">-- Seleccionar mesa --</option>
                        <?php foreach ($mesas as $m): ?>
                            <option value="<?= (int)$m['id_mesa'] ?>">
                                Mesa #<?= (int)$m['numero'] ?> (<?= htmlspecialchars($m['estado']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Notas generales:
                    <input type="text" name="notas" placeholder="Ej. Cuenta junta, prioridad...">
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
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['categoria']) ?></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td>$<?= number_format((float)$p['precio'], 2) ?></td>
                            <td>
                                <input type="number" name="cantidad[<?= (int)$p['id_producto'] ?>]" min="0" value="0">
                            </td>
                            <td>
                                <input type="text" name="observaciones[<?= (int)$p['id_producto'] ?>]" placeholder="Ej. Sin cebolla">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit">Registrar pedido</button>
        </form>
    </section>

    <!-- SECCIÓN PEDIDOS ACTIVOS -->
    <section class="card">
        <h2>Pedidos Activos</h2>
        <?php if (empty($pedidos)): ?>
            <p>No hay pedidos activos en este momento.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo / Mesa</th>
                        <th>Detalle</th>
                        <th>Notas</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $ped): ?>
                        <?php $detalles = $modelo->obtenerDetallesPedido((int)$ped['id_pedido']); ?>
                        <tr>
                            <td>#<?= (int)$ped['id_pedido'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars(str_replace('_', ' ', ucfirst($ped['tipo']))) ?></strong><br>
                                <?php if ($ped['numero_mesa']): ?>
                                    Mesa #<?= (int)$ped['numero_mesa'] ?><br>
                                <?php endif; ?>
                                <small><?= htmlspecialchars($ped['mesero'] ?? 'Pedido en línea') ?></small>
                            </td>
                            <td>
                                <ul>
                                    <?php foreach ($detalles as $d): ?>
                                        <li>
                                            <?= (int)$d['cantidad'] ?>x <?= htmlspecialchars($d['producto']) ?>
                                            ($<?= number_format((float)$d['precio_unitario'], 2) ?>)
                                            <?php if (!empty($d['observaciones'])): ?>
                                                - <em><?= htmlspecialchars($d['observaciones']) ?></em>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td><?= htmlspecialchars($ped['notas'] ?? '—') ?></td>
                            <td><strong>$<?= number_format((float)$ped['total'], 2) ?></strong></td>
                            <td>
                                <form action="../controladores/PedidoController.php" method="POST">
                                    <input type="hidden" name="accion" value="estado_pedido">
                                    <input type="hidden" name="id_pedido" value="<?= (int)$ped['id_pedido'] ?>">
                                    <select name="estado">
                                        <option value="recibido" <?= $ped['estado'] === 'recibido' ? 'selected' : '' ?>>Recibido</option>
                                        <option value="en_preparacion" <?= $ped['estado'] === 'en_preparacion' ? 'selected' : '' ?>>En preparación</option>
                                        <option value="listo" <?= $ped['estado'] === 'listo' ? 'selected' : '' ?>>Listo</option>
                                        <option value="entregado" <?= $ped['estado'] === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                        <option value="cancelado" <?= $ped['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                    <button type="submit">Actualizar</button>
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