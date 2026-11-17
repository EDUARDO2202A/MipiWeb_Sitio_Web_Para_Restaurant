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
}