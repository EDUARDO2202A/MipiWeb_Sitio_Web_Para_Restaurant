<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/CajaModel.php';

verificarSesion(['administrador', 'cajero']);

$modelo      = new CajaModel();
$turno       = $modelo->obtenerTurnoAbierto();
$resumen     = $turno ? $modelo->resumenTurno((int)$turno['id_turno']) : null;
$pagosTurno  = $turno ? $modelo->listarPagosTurno((int)$turno['id_turno']) : [];
$porCobrar   = $modelo->listarPedidosPorCobrar();
$historial   = $modelo->listarHistorialTurnos();

$oks = [
    'turno_abierto' => 'Turno de caja abierto correctamente.',
    'turno_cerrado' => 'Turno de caja cerrado correctamente.',
    'pagado'        => 'Pago registrado correctamente.',
];
$errores = [
    'monto'         => 'Ingresa un monto válido (mayor o igual a 0).',
    'sin_turno'     => 'Debes abrir un turno de caja antes de cobrar.',
    'pago_invalido' => 'Revisa el método de pago y el monto mayor a 0.',
    'bd'            => 'Error al guardar en base de datos.',
];
$msgOk    = $oks[$_GET['ok'] ?? ''] ?? '';
$msgError = $errores[$_GET['error'] ?? ''] ?? '';
$ticketId = isset($_GET['ticket']) ? (int)$_GET['ticket'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Caja y Cobros</title>
    <link rel="stylesheet" href="assets/css/caja.css">
</head>
<body>
    <header class="top-bar">
        <h1>Turnos de Caja y Cobros</h1>
        <a href="panel_pos.php">← Volver al panel</a>
    </header>

    <?php if ($msgOk): ?>
        <p class="msg-ok">
            <?= htmlspecialchars($msgOk) ?>
            <?php if ($ticketId > 0): ?>
                <a href="ticket.php?id_pedido=<?= $ticketId ?>">Ver / Imprimir Ticket #<?= $ticketId ?></a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <?php if ($msgError): ?><p class="msg-error"><?= htmlspecialchars($msgError) ?></p><?php endif; ?>

    <!-- SECCIÓN TURNO ACTUAL (F10) -->
    <section class="card">
        <h2>Turno de Caja</h2>

        <?php if (!$turno): ?>
            <p>No hay ningún turno abierto actualmente.</p>
            <form action="../controladores/CajaController.php" method="POST" class="form-inline">
                <input type="hidden" name="accion" value="abrir_turno">
                <label>Monto inicial ($):
                    <input type="number" name="monto_inicial" step="0.01" min="0" value="0.00" required>
                </label>
                <button type="submit">Abrir turno</button>
            </form>
        <?php else: ?>
            <?php $esperadoEfectivo = (float)$turno['monto_inicial'] + (float)$resumen['efectivo']; ?>
            <p>
                <strong>Turno #<?= (int)$turno['id_turno'] ?></strong> |
                Cajero: <?= htmlspecialchars($turno['cajero']) ?> |
                Apertura: <?= htmlspecialchars($turno['fecha_apertura']) ?>
            </p>
            <ul>
                <li>Monto inicial: <strong>$<?= number_format((float)$turno['monto_inicial'], 2) ?></strong></li>
                <li>Cobrado en efectivo: <strong>$<?= number_format((float)$resumen['efectivo'], 2) ?></strong></li>
                <li>Cobrado con tarjeta: <strong>$<?= number_format((float)$resumen['tarjeta'], 2) ?></strong></li>
                <li>Cobrado por transferencia: <strong>$<?= number_format((float)$resumen['transferencia'], 2) ?></strong></li>
                <li>Total cobrado en el turno: <strong>$<?= number_format((float)$resumen['total'], 2) ?></strong></li>
                <li>Efectivo esperado en caja: <strong>$<?= number_format($esperadoEfectivo, 2) ?></strong></li>
            </ul>

            <form action="../controladores/CajaController.php" method="POST" class="form-inline">
                <input type="hidden" name="accion" value="cerrar_turno">
                <input type="hidden" name="id_turno" value="<?= (int)$turno['id_turno'] ?>">
                <label>Monto de cierre contado ($):
                    <input type="number" name="monto_cierre" step="0.01" min="0" value="<?= number_format($esperadoEfectivo, 2, '.', '') ?>" required>
                </label>
                <button type="submit">Cerrar turno</button>
            </form>
        <?php endif; ?>
    </section>

    <!-- SECCIÓN PEDIDOS POR COBRAR (F11) -->
    <section class="card">
        <h2>Pedidos pendientes de cobro</h2>
        <?php if (empty($porCobrar)): ?>
            <p>No hay pedidos pendientes por cobrar.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Tipo / Mesa</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Restante</th>
                        <th>Registrar pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porCobrar as $p): ?>
                        <?php $restante = max(0, (float)$p['total'] - (float)$p['total_pagado']); ?>
                        <tr>
                            <td>#<?= (int)$p['id_pedido'] ?></td>
                            <td>
                                <?= htmlspecialchars(str_replace('_', ' ', ucfirst($p['tipo']))) ?>
                                <?= $p['numero_mesa'] ? ' - Mesa #' . (int)$p['numero_mesa'] : '' ?>
                            </td>
                            <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($p['estado']))) ?></td>
                            <td>$<?= number_format((float)$p['total'], 2) ?></td>
                            <td>$<?= number_format((float)$p['total_pagado'], 2) ?></td>
                            <td><strong>$<?= number_format($restante, 2) ?></strong></td>
                            <td>
                                <?php if ($turno): ?>
                                    <form action="../controladores/CajaController.php" method="POST" class="form-inline">
                                        <input type="hidden" name="accion" value="registrar_pago">
                                        <input type="hidden" name="id_pedido" value="<?= (int)$p['id_pedido'] ?>">
                                        <select name="metodo" required>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="tarjeta">Tarjeta</option>
                                            <option value="transferencia">Transferencia</option>
                                        </select>
                                        <input type="number" name="monto" step="0.01" min="0.01" value="<?= number_format($restante, 2, '.', '') ?>" required>
                                        <button type="submit">Cobrar</button>
                                    </form>
                                <?php else: ?>
                                    <span>Abre un turno para cobrar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- PAGOS DEL TURNO ACTUAL -->
    <?php if ($turno): ?>
        <section class="card">
            <h2>Cobros del turno actual</h2>
            <?php if (empty($pagosTurno)): ?>
                <p>Aún no se han registrado cobros en este turno.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pago</th>
                            <th>Pedido</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagosTurno as $pg): ?>
                            <tr>
                                <td>#<?= (int)$pg['id_pago'] ?></td>
                                <td>#<?= (int)$pg['id_pedido'] ?></td>
                                <td><?= htmlspecialchars(ucfirst($pg['metodo'])) ?></td>
                                <td>$<?= number_format((float)$pg['monto'], 2) ?></td>
                                <td><?= htmlspecialchars($pg['fecha_pago']) ?></td>
                                <td><a href="ticket.php?id_pedido=<?= (int)$pg['id_pedido'] ?>">Ver ticket</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- HISTORIAL DE TURNOS -->
    <section class="card">
        <h2>Últimos turnos</h2>
        <table>
            <thead>
                <tr>
                    <th>Turno</th>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th>Inicial</th>
                    <th>Cobrado</th>
                    <th>Cierre</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $h): ?>
                    <tr>
                        <td>#<?= (int)$h['id_turno'] ?></td>
                        <td><?= htmlspecialchars($h['cajero']) ?></td>
                        <td><?= htmlspecialchars($h['fecha_apertura']) ?></td>
                        <td><?= htmlspecialchars($h['fecha_cierre'] ?? '—') ?></td>
                        <td>$<?= number_format((float)$h['monto_inicial'], 2) ?></td>
                        <td>$<?= number_format((float)$h['total_cobrado'], 2) ?></td>
                        <td><?= $h['monto_cierre'] !== null ? '$' . number_format((float)$h['monto_cierre'], 2) : '—' ?></td>
                        <td><?= htmlspecialchars(ucfirst($h['estado'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>