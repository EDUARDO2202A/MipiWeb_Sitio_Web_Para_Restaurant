<?php
require_once __DIR__ . '/conexion.php';

class CajaModel {
    private $pdo;

    public function __construct() {
        $db = new Conexion();
        $this->pdo = $db->conectar();
    }

   
    public function obtenerTurnoAbierto() {
        $sql = "SELECT t.*, u.nombre || ' ' || u.apellidos AS cajero
                FROM turnos_caja t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                WHERE t.estado = 'abierto'
                ORDER BY t.fecha_apertura DESC
                LIMIT 1";
        return $this->pdo->query($sql)->fetch();
    }

    public function abrirTurno(int $idUsuario, float $montoInicial): bool {
        if ($this->obtenerTurnoAbierto()) {
            return false;
        }
        $sql = "INSERT INTO turnos_caja (id_usuario, fecha_apertura, monto_inicial, estado)
                VALUES (:id_usuario, CURRENT_TIMESTAMP, :monto_inicial, 'abierto')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_usuario'    => $idUsuario,
            ':monto_inicial' => $montoInicial,
        ]);
    }

    public function cerrarTurno(int $idTurno, float $montoCierre): bool {
        $sql = "UPDATE turnos_caja
                SET fecha_cierre = CURRENT_TIMESTAMP,
                    monto_cierre = :monto_cierre,
                    estado = 'cerrado'
                WHERE id_turno = :id_turno AND estado = 'abierto'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':monto_cierre' => $montoCierre,
            ':id_turno'     => $idTurno,
        ]);
    }

    public function resumenTurno(int $idTurno): array {
        $sql = "SELECT
                    COUNT(*) AS cantidad_pagos,
                    COALESCE(SUM(CASE WHEN metodo = 'efectivo' THEN monto ELSE 0 END), 0) AS efectivo,
                    COALESCE(SUM(CASE WHEN metodo = 'tarjeta' THEN monto ELSE 0 END), 0) AS tarjeta,
                    COALESCE(SUM(CASE WHEN metodo = 'transferencia' THEN monto ELSE 0 END), 0) AS transferencia,
                    COALESCE(SUM(monto), 0) AS total
                FROM pagos
                WHERE id_turno = :id_turno";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_turno' => $idTurno]);
        return $stmt->fetch();
    }

    public function listarHistorialTurnos(): array {
        $sql = "SELECT t.id_turno, t.fecha_apertura, t.fecha_cierre, t.monto_inicial, t.monto_cierre, t.estado,
                       u.nombre || ' ' || u.apellidos AS cajero,
                       COALESCE(SUM(pg.monto), 0) AS total_cobrado
                FROM turnos_caja t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                LEFT JOIN pagos pg ON t.id_turno = pg.id_turno
                GROUP BY t.id_turno, u.nombre, u.apellidos
                ORDER BY t.fecha_apertura DESC
                LIMIT 10";
        return $this->pdo->query($sql)->fetchAll();
    }


    public function listarPedidosPorCobrar(): array {
        $sql = "SELECT p.id_pedido, p.tipo, p.estado, p.fecha_pedido, p.total,
                       m.numero AS numero_mesa,
                       COALESCE(SUM(pg.monto), 0) AS total_pagado
                FROM pedidos p
                LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
                LEFT JOIN pagos pg ON p.id_pedido = pg.id_pedido
                WHERE p.estado <> 'cancelado'
                GROUP BY p.id_pedido, m.numero
                HAVING COALESCE(SUM(pg.monto), 0) < p.total
                ORDER BY p.fecha_pedido ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function listarPagosTurno(int $idTurno): array {
        $sql = "SELECT pg.id_pago, pg.id_pedido, pg.metodo, pg.monto, pg.fecha_pago,
                       p.tipo, m.numero AS numero_mesa
                FROM pagos pg
                INNER JOIN pedidos p ON pg.id_pedido = p.id_pedido
                LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
                WHERE pg.id_turno = :id_turno
                ORDER BY pg.fecha_pago DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_turno' => $idTurno]);
        return $stmt->fetchAll();
    }

    public function registrarPago(int $idPedido, int $idTurno, string $metodo, float $monto): bool {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO pagos (id_pedido, id_turno, metodo, monto) VALUES (:id_pedido, :id_turno, :metodo, :monto)"
            );
            $stmt->execute([
                ':id_pedido' => $idPedido,
                ':id_turno'  => $idTurno,
                ':metodo'    => $metodo,
                ':monto'     => $monto,
            ]);

            $stmtUpd = $this->pdo->prepare(
                "UPDATE pedidos SET id_turno = :id_turno WHERE id_pedido = :id_pedido AND id_turno IS NULL"
            );
            $stmtUpd->execute([
                ':id_turno'  => $idTurno,
                ':id_pedido' => $idPedido,
            ]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function obtenerDatosTicket(int $idPedido): ?array {
        $stmtPed = $this->pdo->prepare(
            "SELECT p.*, m.numero AS numero_mesa, u.nombre AS atendio
             FROM pedidos p
             LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
             LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
             WHERE p.id_pedido = :id"
        );
        $stmtPed->execute([':id' => $idPedido]);
        $pedido = $stmtPed->fetch();
        if (!$pedido) {
            return null;
        }

        $stmtDet = $this->pdo->prepare(
            "SELECT d.cantidad, d.precio_unitario, d.observaciones, pr.nombre AS producto
             FROM detalle_pedido d
             INNER JOIN productos pr ON d.id_producto = pr.id_producto
             WHERE d.id_pedido = :id"
        );
        $stmtDet->execute([':id' => $idPedido]);

        $stmtPagos = $this->pdo->prepare(
            "SELECT metodo, monto, fecha_pago FROM pagos WHERE id_pedido = :id ORDER BY fecha_pago ASC"
        );
        $stmtPagos->execute([':id' => $idPedido]);

        $restaurante = $this->pdo->query("SELECT nombre, direccion, telefono FROM restaurante LIMIT 1")->fetch();

        return [
            'restaurante' => $restaurante ?: ['nombre' => 'MipiWeb Restaurante', 'direccion' => '', 'telefono' => ''],
            'pedido'      => $pedido,
            'detalles'    => $stmtDet->fetchAll(),
            'pagos'       => $stmtPagos->fetchAll(),
        ];
    }
}