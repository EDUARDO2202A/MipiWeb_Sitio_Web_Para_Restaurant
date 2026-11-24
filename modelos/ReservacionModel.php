
<?php

require_once __DIR__ . '/conexion.php';

class ReservacionModel {

    private $pdo;

    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
    }

    // Guardar una nueva solicitud de reservación
    public function crear(
        string $nombre,
        string $telefono,
        string $fechaHora,
        int $personas
    ): bool {

        $sql = "INSERT INTO reservaciones
                (nombre_contacto, telefono, fecha_hora,
                 num_personas, estado)
                VALUES
                (:nombre, :telefono, :fecha_hora,
                 :personas, :estado)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nombre' => $nombre,
            ':telefono' => $telefono,
            ':fecha_hora' => $fechaHora,
            ':personas' => $personas,
            ':estado' => 'pendiente'
        ]);
    }

    // Consultar las reservaciones registradas
    public function listar(): array {

        $sql = "SELECT id_reservacion,
                       nombre_contacto,
                       telefono,
                       fecha_hora,
                       num_personas,
                       estado
                FROM reservaciones
                ORDER BY fecha_hora ASC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }
}
