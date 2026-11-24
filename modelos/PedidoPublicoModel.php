
<?php

require_once __DIR__ . '/conexion.php';

class PedidoPublicoModel {

    private PDO $pdo;

    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
    }

    // Obtener productos disponibles del restaurante
    public function listarProductos(): array {

        $sql = "SELECT
                    p.id_producto,
                    p.nombre,
                    p.descripcion,
                    p.precio,
                    c.nombre AS categoria
                FROM productos p
                INNER JOIN categorias c
                    ON p.id_categoria = c.id_categoria
                WHERE p.disponible = TRUE
                ORDER BY c.nombre, p.nombre";

        return $this->pdo->query($sql)->fetchAll();
    }

    // Registrar un pedido a domicilio
    public function crearPedidoDomicilio(
        string $nombre,
        string $telefono,
        string $direccion,
        string $referencias,
        array $productos
    ): int {

        // Validaciones básicas
        if (
            trim($nombre) === '' ||
            mb_strlen($nombre) > 120 ||
            !preg_match('/^[0-9]{10}$/', $telefono) ||
            trim($direccion) === '' ||
            mb_strlen($direccion) > 500 ||
            mb_strlen($referencias) > 500 ||
            empty($productos) ||
            count($productos) > 50
        ) {
            throw new RuntimeException(
                'Los datos del pedido no son válidos.'
            );
        }

        // Iniciar transacción
        $this->pdo->beginTransaction();

        try {

            $total = 0;
            $detalles = [];

            // Consultar precios reales en Supabase
            $sqlProducto = "SELECT id_producto, precio
                            FROM productos
                            WHERE id_producto = :id
                              AND disponible = TRUE";

            $stmtProducto = $this->pdo->prepare($sqlProducto);

            foreach ($productos as $producto) {

                $idProducto = filter_var(
                    $producto['id_producto'] ?? null,
                    FILTER_VALIDATE_INT
                );

                $cantidad = filter_var(
                    $producto['cantidad'] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (
                    !$idProducto ||
                    !$cantidad ||
                    $cantidad < 1 ||
                    $cantidad > 50
                ) {
                    throw new RuntimeException(
                        'Cantidad o producto inválido.'
                    );
                }

                // Buscar el producto en la base de datos
                $stmtProducto->execute([
                    ':id' => $idProducto
                ]);

                $datos = $stmtProducto->fetch();

                if (!$datos) {
                    throw new RuntimeException(
                        'Uno de los productos no está disponible.'
                    );
                }

                $precio = (float)$datos['precio'];

                if ($precio < 0) {
                    throw new RuntimeException(
                        'Precio de producto inválido.'
                    );
                }

                // Calcular importe con precios del servidor
                $subtotal = round($precio * $cantidad, 2);
                $total = round($total + $subtotal, 2);

                $detalles[] = [
                    'id_producto' => $idProducto,
                    'cantidad' => $cantidad,
                    'precio' => $precio
                ];
            }

            // 1. Guardar pedido general
            $sqlPedido = "INSERT INTO pedidos
                          (tipo, id_mesa, id_cliente,
                           id_usuario, estado, total)
                          VALUES
                          ('en_linea', NULL, NULL,
                           NULL, 'recibido', :total)
                          RETURNING id_pedido";

            $stmtPedido = $this->pdo->prepare($sqlPedido);

            $stmtPedido->execute([
                ':total' => $total
            ]);

            $idPedido = (int)$stmtPedido->fetchColumn();

            // 2. Guardar los productos del pedido
            $sqlDetalle = "INSERT INTO detalle_pedido
                           (id_pedido, id_producto,
                            cantidad, precio_unitario)
                           VALUES
                           (:pedido, :producto,
                            :cantidad, :precio)";

            $stmtDetalle = $this->pdo->prepare($sqlDetalle);

            foreach ($detalles as $detalle) {

                $stmtDetalle->execute([
                    ':pedido' => $idPedido,
                    ':producto' => $detalle['id_producto'],
                    ':cantidad' => $detalle['cantidad'],
                    ':precio' => $detalle['precio']
                ]);
            }

            // 3. Guardar información de entrega
            $sqlEntrega = "INSERT INTO entregas_domicilio
                           (id_pedido, nombre_contacto,
                            telefono, direccion, referencias)
                           VALUES
                           (:pedido, :nombre,
                            :telefono, :direccion, :referencias)";

            $stmtEntrega = $this->pdo->prepare($sqlEntrega);

            $stmtEntrega->execute([
                ':pedido' => $idPedido,
                ':nombre' => trim($nombre),
                ':telefono' => $telefono,
                ':direccion' => trim($direccion),
                ':referencias' => trim($referencias)
            ]);

            // Confirmar todos los registros
            $this->pdo->commit();

            return $idPedido;

        } catch (Throwable $e) {

            $this->pdo->rollBack();
            throw $e;
        }
    }
}
