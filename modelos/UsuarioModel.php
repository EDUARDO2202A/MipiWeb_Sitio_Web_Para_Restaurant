<?php
require_once __DIR__ . '/conexion.php';

class UsuarioModel {
    private $pdo;

    public function __construct() {
        $db = new Conexion();
        $this->pdo = $db->conectar();
    }

    public function obtenerPorUsuario(string $usuario) {
        $sql = "SELECT u.id_usuario, u.nombre, u.apellidos, u.usuario,
                       u.contrasena_hash, u.activo, r.id_rol, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.usuario = :usuario
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario' => $usuario]);
        return $stmt->fetch();
    }

    public function listarTodos(): array {
        $sql = "SELECT u.id_usuario, u.nombre, u.apellidos, u.usuario, u.activo,
                       r.id_rol, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                ORDER BY u.id_usuario ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function listarRoles(): array {
        return $this->pdo->query("SELECT id_rol, nombre FROM roles ORDER BY id_rol ASC")->fetchAll();
    }

    public function obtenerPorId(int $id) {
        $stmt = $this->pdo->prepare("SELECT id_usuario, id_rol, nombre, apellidos, usuario, activo FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function crear(int $idRol, string $nombre, string $apellidos, string $usuario, string $password): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (id_rol, nombre, apellidos, usuario, contrasena_hash, activo)
                VALUES (:id_rol, :nombre, :apellidos, :usuario, :hash, true)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_rol'    => $idRol,
            ':nombre'    => $nombre,
            ':apellidos' => $apellidos,
            ':usuario'   => $usuario,
            ':hash'      => $hash,
        ]);
    }

    public function actualizar(int $id, int $idRol, string $nombre, string $apellidos, string $usuario, ?string $password = null): bool {
        if ($password !== null && $password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios
                    SET id_rol = :id_rol, nombre = :nombre, apellidos = :apellidos,
                        usuario = :usuario, contrasena_hash = :hash
                    WHERE id_usuario = :id";
            $params = [
                ':id_rol'    => $idRol,
                ':nombre'    => $nombre,
                ':apellidos' => $apellidos,
                ':usuario'   => $usuario,
                ':hash'      => $hash,
                ':id'        => $id,
            ];
        } else {
            $sql = "UPDATE usuarios
                    SET id_rol = :id_rol, nombre = :nombre, apellidos = :apellidos, usuario = :usuario
                    WHERE id_usuario = :id";
            $params = [
                ':id_rol'    => $idRol,
                ':nombre'    => $nombre,
                ':apellidos' => $apellidos,
                ':usuario'   => $usuario,
                ':id'        => $id,
            ];
        }
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function cambiarEstado(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET activo = NOT activo WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id]);
    }
}