<?php
require_once __DIR__ . '/conexion.php';

class ReporteModel {
    private $pdo;

    public function __construct() {
        $db = new Conexion();
        $this->pdo = $db->conectar();
    }

    public function resumenGeneral(string $inicio, string $fin): array {
        $sqlPagos = "SELECT COUNT(*) AS num_pagos, COALESCE(SUM(monto), 0) AS total_cobrado
                     FROM pagos
                     WHERE DATE(fecha_pago) BETWEEN :inicio AND :fin";
        $stmtP = $this->pdo->prepare($sqlPagos);
        $stmtP->execute([':inicio' => $inicio, ':fin' => $fin]);
        $pagos = $stmtP->fetch();

        $sqlPedidos = "SELECT COUNT(*) AS num_pedidos, COALESCE(SUM(total), 0) AS total_pedidos
                       FROM pedidos
                       WHERE estado <> 'cancelado' AND DATE(fecha_pedido) BETWEEN :inicio AND :fin";
        $stmtPed = $this->pdo->prepare($sqlPedidos);
        $stmtPed->execute([':inicio' => $inicio, ':fin' => $fin]);
        $pedidos = $stmtPed->fetch();

        return [
            'num_pagos'     => (int)$pagos['num_pagos'],
            'total_cobrado' => (float)$pagos['total_cobrado'],
            'num_pedidos'   => (int)$pedidos['num_pedidos'],
            'total_pedidos' => (float)$pedidos['total_pedidos'],
        ];
    }

    public function ventasPorDia(string $inicio, string $fin): array {
        $sql = "SELECT DATE(fecha_pago) AS dia,
                       COUNT(*) AS cantidad_pagos,
                       COALESCE(SUM(CASE WHEN metodo = 'efectivo' THEN monto ELSE 0 END), 0) AS efectivo,
                       COALESCE(SUM(CASE WHEN metodo = 'tarjeta' THEN monto ELSE 0 END), 0) AS tarjeta,
                       COALESCE(SUM(CASE WHEN metodo = 'transferencia' THEN monto ELSE 0 END), 0) AS transferencia,
                       COALESCE(SUM(monto), 0) AS total
                FROM pagos
                WHERE DATE(fecha_pago) BETWEEN :inicio AND :fin
                GROUP BY DATE(fecha_pago)
                ORDER BY dia DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
        return $stmt->fetchAll();
    }

    public function ventasPorTurno(string $inicio, string $fin): array {
        $sql = "SELECT t.id_turno, t.fecha_apertura, t.fecha_cierre, t.monto_inicial, t.monto_cierre, t.estado,
                       u.nombre || ' ' || u.apellidos AS cajero,
                       COUNT(pg.id_pago) AS cantidad_pagos,
                       COALESCE(SUM(pg.monto), 0) AS total_cobrado
                FROM turnos_caja t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                LEFT JOIN pagos pg ON t.id_turno = pg.id_turno
                WHERE DATE(t.fecha_apertura) BETWEEN :inicio AND :fin
                GROUP BY t.id_turno, u.nombre, u.apellidos
                ORDER BY t.fecha_apertura DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
        return $stmt->fetchAll();
    }

    public function ventasPorProducto(string $inicio, string $fin): array {
        $sql = "SELECT pr.nombre AS producto, c.nombre AS categoria,
                       SUM(d.cantidad) AS unidades_vendidas,
                       SUM(d.cantidad * d.precio_unitario) AS total_generado
                FROM detalle_pedido d
                INNER JOIN pedidos p ON d.id_pedido = p.id_pedido
                INNER JOIN productos pr ON d.id_producto = pr.id_producto
                INNER JOIN categorias c ON pr.id_categoria = c.id_categoria
                WHERE p.estado <> 'cancelado'
                  AND DATE(p.fecha_pedido) BETWEEN :inicio AND :fin
                GROUP BY pr.id_producto, pr.nombre, c.nombre
                ORDER BY unidades_vendidas DESC, total_generado DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
        return $stmt->fetchAll();
    }
}