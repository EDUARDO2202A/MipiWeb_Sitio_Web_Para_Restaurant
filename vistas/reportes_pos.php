<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/ReporteModel.php';

verificarSesion(['administrador']);

$hoy         = date('Y-m-d');
$inicioMes   = date('Y-m-01');
$fechaInicio = $_GET['inicio'] ?? $inicioMes;
$fechaFin    = $_GET['fin'] ?? $hoy;

$modelo      = new ReporteModel();
$resumen     = $modelo->resumenGeneral($fechaInicio, $fechaFin);
$porDia      = $modelo->ventasPorDia($fechaInicio, $fechaFin);
$porTurno    = $modelo->ventasPorTurno($fechaInicio, $fechaFin);
$porProducto = $modelo->ventasPorProducto($fechaInicio, $fechaFin);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Reportes de Ventas</title>
    <link rel="stylesheet" href="assets/css/reportes.css">
</head>
<body>
    <header class="top-bar">
        <h1>Reportes de Ventas</h1>
        <a href="panel_pos.php">← Volver al panel</a>
    </header>

    <!-- FILTRO POR FECHAS -->
    <section class="card">
        <form method="GET" action="reportes_pos.php" class="form-inline">
            <label>Desde:
                <input type="date" name="inicio" value="<?= htmlspecialchars($fechaInicio) ?>" required>
            </label>
            <label>Hasta:
                <input type="date" name="fin" value="<?= htmlspecialchars($fechaFin) ?>" required>
            </label>
            <button type="submit">Filtrar</button>
        </form>
    </section>

    <!-- RESUMEN GENERAL -->
    <section class="resumen-grid">
        <article class="card">
            <h3>Total cobrado (Pagos)</h3>
            <p><strong>$<?= number_format($resumen['total_cobrado'], 2) ?></strong></p>
            <small><?= $resumen['num_pagos'] ?> pagos registrados</small>
        </article>
        <article class="card">
            <h3>Total en pedidos (no cancelados)</h3>
            <p><strong>$<?= number_format($resumen['total_pedidos'], 2) ?></strong></p>
            <small><?= $resumen['num_pedidos'] ?> pedidos</small>
        </article>
    </section>

    <!-- 1. VENTAS POR DÍA -->
    <section class="card">
        <h2>Ventas por día</h2>
        <?php if (empty($porDia)): ?>
            <p>No hay pagos registrados en este rango de fechas.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Pagos</th>
                        <th>Efectivo</th>
                        <th>Tarjeta</th>
                        <th>Transferencia</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porDia as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['dia']) ?></td>
                            <td><?= (int)$d['cantidad_pagos'] ?></td>
                            <td>$<?= number_format((float)$d['efectivo'], 2) ?></td>
                            <td>$<?= number_format((float)$d['tarjeta'], 2) ?></td>
                            <td>$<?= number_format((float)$d['transferencia'], 2) ?></td>
                            <td><strong>$<?= number_format((float)$d['total'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- 2. VENTAS POR TURNO -->
    <section class="card">
        <h2>Ventas por turno de caja</h2>
        <?php if (empty($porTurno)): ?>
            <p>No hay turnos de caja registrados en este rango de fechas.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Turno</th>
                        <th>Cajero</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Estado</th>
                        <th>Pagos</th>
                        <th>Total cobrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porTurno as $t): ?>
                        <tr>
                            <td>#<?= (int)$t['id_turno'] ?></td>
                            <td><?= htmlspecialchars($t['cajero']) ?></td>
                            <td><?= htmlspecialchars($t['fecha_apertura']) ?></td>
                            <td><?= htmlspecialchars($t['fecha_cierre'] ?? 'En curso') ?></td>
                            <td><?= htmlspecialchars(ucfirst($t['estado'])) ?></td>
                            <td><?= (int)$t['cantidad_pagos'] ?></td>
                            <td><strong>$<?= number_format((float)$t['total_cobrado'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- 3. VENTAS POR PRODUCTO -->
    <section class="card">
        <h2>Ventas por producto</h2>
        <?php if (empty($porProducto)): ?>
            <p>No hay productos vendidos en este rango de fechas.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Producto</th>
                        <th>Unidades vendidas</th>
                        <th>Total generado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porProducto as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['categoria']) ?></td>
                            <td><?= htmlspecialchars($p['producto']) ?></td>
                            <td><?= (int)$p['unidades_vendidas'] ?></td>
                            <td><strong>$<?= number_format((float)$p['total_generado'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</body>
</html>