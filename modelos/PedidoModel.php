<?php
require_once __DIR__ . '/conexion.php';

class PedidoModel {
    private $pdo;

    public function __construct() {
        $db = new Conexion();
        $this->pdo = $db->conectar();
    }

    // --- MESAS ---
    public function listarMesas(): array {
        return $this->pdo->query("SELECT id_mesa, numero, capacidad, estado FROM mesas ORDER BY numero ASC")->fetchAll();
    }

    public function crearMesa(int $numero, int $capacidad): bool {
        $stmt = $this->pdo->prepare("INSERT INTO mesas (numero, capacidad, estado) VALUES (:numero, :capacidad, 'libre')");
        return $stmt->execute([':numero' => $numero, ':capacidad' => $capacidad]);
    }

    public function cambiarEstadoMesa(int $idMesa, string $estado): bool {
        $stmt = $this->pdo->prepare("UPDATE mesas SET estado = :estado WHERE id_mesa = :id");
        return $stmt->execute([':estado' => $estado, ':id' => $idMesa]);
    }

    // --- PRODUCTOS DISPONIBLES ---
    public function listarProductosDisponibles(): array {
        $sql = "SELECT p.id_producto, p.nombre, p.precio, c.nombre AS categoria
                FROM productos p
                INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                WHERE p.disponible = true
                ORDER BY c.nombre ASC, p.nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    // --- PEDIDOS ---
    public function crearPedido(string $tipo, ?int $idMesa, int $idUsuario, string $notas, array $items): int {
        $this->pdo->beginTransaction();

        try {
            $total = 0.0;
            $detallesValidos = [];
            $stmtPrecio = $this->pdo->prepare("SELECT precio FROM productos WHERE id_producto = :id AND disponible = true");

            foreach ($items as $item) {
                $idProd = (int)$item['id_producto'];
                $cant   = (int)$item['cantidad'];
                $obs    = trim($item['observaciones'] ?? '');

                if ($cant <= 0) {
                    continue;
                }

                $stmtPrecio->execute([':id' => $idProd]);
                $precio = $stmtPrecio->fetchColumn();

                if ($precio === false) {
                    continue;
                }

                $precioFloat = (float)$precio;
                $total += $precioFloat * $cant;
                $detallesValidos[] = [
                    'id_producto'     => $idProd,
                    'cantidad'        => $cant,
                    'precio_unitario' => $precioFloat,
                    'observaciones'   => $obs !== '' ? $obs : null,
                ];
            }

            if (empty($detallesValidos)) {
                throw new RuntimeException('Sin productos');
            }

            $sqlPedido = "INSERT INTO pedidos (tipo, id_mesa, id_usuario, estado, notas, total)
                          VALUES (:tipo, :id_mesa, :id_usuario, 'recibido', :notas, :total)
                          RETURNING id_pedido";
            $stmtPedido = $this->pdo->prepare($sqlPedido);
            $stmtPedido->execute([
                ':tipo'       => $tipo,
                ':id_mesa'    => $tipo === 'mesa' ? $idMesa : null,
                ':id_usuario' => $idUsuario,
                ':notas'      => $notas !== '' ? $notas : null,
                ':total'      => $total,
            ]);
            $idPedido = (int)$stmtPedido->fetchColumn();

            $sqlDet = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, observaciones)
                       VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :observaciones)";
            $stmtDet = $this->pdo->prepare($sqlDet);

            foreach ($detallesValidos as $d) {
                $stmtDet->execute([
                    ':id_pedido'       => $idPedido,
                    ':id_producto'     => $d['id_producto'],
                    ':cantidad'        => $d['cantidad'],
                    ':precio_unitario' => $d['precio_unitario'],
                    ':observaciones'   => $d['observaciones'],
                ]);
            }

            if ($tipo === 'mesa' && $idMesa !== null) {
                $this->cambiarEstadoMesa($idMesa, 'ocupada');
            }

            $this->pdo->commit();
            return $idPedido;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listarPedidosActivos(): array {
        $sql = "SELECT p.id_pedido, p.tipo, p.id_mesa, p.estado, p.fecha_pedido, p.notas, p.total,
                       m.numero AS numero_mesa,
                       u.nombre AS mesero
                FROM pedidos p
                LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
                LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.estado IN ('recibido', 'en_preparacion', 'listo')
                ORDER BY p.fecha_pedido ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function obtenerDetallesPedido(int $idPedido): array {
        $sql = "SELECT d.cantidad, d.precio_unitario, d.observaciones, pr.nombre AS producto
                FROM detalle_pedido d
                INNER JOIN productos pr ON d.id_producto = pr.id_producto
                WHERE d.id_pedido = :id_pedido";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_pedido' => $idPedido]);
        return $stmt->fetchAll();
    }

    public function cambiarEstadoPedido(int $idPedido, string $estado): bool {
        $stmt = $this->pdo->prepare("UPDATE pedidos SET estado = :estado WHERE id_pedido = :id");
        $ok = $stmt->execute([':estado' => $estado, ':id' => $idPedido]);

        // Si se cancela o entrega, liberar la mesa si no tiene otros pedidos activos
        if ($ok && in_array($estado, ['entregado', 'cancelado'], true)) {
            $stmtMesa = $this->pdo->prepare("SELECT id_mesa FROM pedidos WHERE id_pedido = :id");
            $stmtMesa->execute([':id' => $idPedido]);
            $idMesa = $stmtMesa->fetchColumn();

            if ($idMesa) {
                $stmtActivos = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM pedidos WHERE id_mesa = :id_mesa AND estado IN ('recibido', 'en_preparacion', 'listo')"
                );
                $stmtActivos->execute([':id_mesa' => $idMesa]);
                if ((int)$stmtActivos->fetchColumn() === 0) {
                    $this->cambiarEstadoMesa((int)$idMesa, 'libre');
                }
            }
        }

        return $ok;
    }
}