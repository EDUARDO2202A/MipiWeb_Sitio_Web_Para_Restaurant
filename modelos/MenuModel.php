<?php
require_once __DIR__ . '/conexion.php';

class MenuModel {
    private $pdo;

    public function __construct() {
        $db = new Conexion();
        $this->pdo = $db->conectar();
    }

    // --- CATEGORÍAS ---
    public function listarCategorias(): array {
        return $this->pdo->query("SELECT id_categoria, nombre, area FROM categorias ORDER BY id_categoria ASC")->fetchAll();
    }

    public function obtenerCategoria(int $id) {
        $stmt = $this->pdo->prepare("SELECT id_categoria, nombre, area FROM categorias WHERE id_categoria = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function crearCategoria(string $nombre, string $area): bool {
        $stmt = $this->pdo->prepare("INSERT INTO categorias (nombre, area) VALUES (:nombre, :area)");
        return $stmt->execute([':nombre' => $nombre, ':area' => $area]);
    }

    public function actualizarCategoria(int $id, string $nombre, string $area): bool {
        $stmt = $this->pdo->prepare("UPDATE categorias SET nombre = :nombre, area = :area WHERE id_categoria = :id");
        return $stmt->execute([':nombre' => $nombre, ':area' => $area, ':id' => $id]);
    }

    // --- PRODUCTOS ---
    public function listarProductos(): array {
        $sql = "SELECT p.id_producto, p.id_categoria, p.nombre, p.descripcion,
                       p.precio, p.imagen, p.disponible, c.nombre AS categoria, c.area
                FROM productos p
                INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                ORDER BY c.nombre ASC, p.nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function obtenerProducto(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM productos WHERE id_producto = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function crearProducto(int $idCat, string $nombre, string $desc, float $precio, ?string $imagen): bool {
        $sql = "INSERT INTO productos (id_categoria, nombre, descripcion, precio, imagen, disponible)
                VALUES (:id_cat, :nombre, :desc, :precio, :imagen, true)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_cat' => $idCat,
            ':nombre' => $nombre,
            ':desc'   => $desc !== '' ? $desc : null,
            ':precio' => $precio,
            ':imagen' => $imagen,
        ]);
    }

    public function actualizarProducto(int $id, int $idCat, string $nombre, string $desc, float $precio, ?string $imagen): bool {
        $sql = "UPDATE productos
                SET id_categoria = :id_cat, nombre = :nombre, descripcion = :desc,
                    precio = :precio, imagen = COALESCE(:imagen, imagen)
                WHERE id_producto = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_cat' => $idCat,
            ':nombre' => $nombre,
            ':desc'   => $desc !== '' ? $desc : null,
            ':precio' => $precio,
            ':imagen' => $imagen,
            ':id'     => $id,
        ]);
    }

    public function cambiarDisponibilidad(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE productos SET disponible = NOT disponible WHERE id_producto = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function listarProductosDisponibles(): array {
        $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen,
                       c.id_categoria, c.nombre AS categoria
                FROM productos p
                INNER JOIN categorias c ON p.id_categoria = c.id_categoria
                WHERE p.disponible = true
                ORDER BY c.nombre ASC, p.nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }
}