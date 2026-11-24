
<?php

require_once __DIR__ . '/conexion.php';

class ReservacionModel {

    private $pdo;

    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
    }

    // Guardar una nueva solicitud
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

    // Listar todas las reservaciones
    public function listar(): array {

        $sql = "SELECT id_reservacion,
                       nombre_contacto,
                       telefono,
                       fecha_hora,
                       num_personas,
                       estado
                FROM reservaciones
                ORDER BY fecha_hora ASC";

        return $this->pdo->query($sql)->fetchAll();
    }

    // Listar mesas
    public function listarMesas(): array {

        $sql = "SELECT id_mesa, numero, capacidad, estado
                FROM mesas
                ORDER BY numero ASC";

        return $this->pdo->query($sql)->fetchAll();
    }

    // Confirmar o rechazar reservaciones
    public function gestionar(
        int $idReservacion,
        string $accion,
        ?int $idMesa = null
    ): void {

        if (!in_array($accion, ['confirmar', 'rechazar'], true)) {
            throw new RuntimeException('Acción inválida.');
        }

        $this->pdo->beginTransaction();

        try {

            // 1. Buscar y bloquear la reservación
            $stmt = $this->pdo->prepare(
                "SELECT id_reservacion, fecha_hora,
                        num_personas, estado
                 FROM reservaciones
                 WHERE id_reservacion = :id
                 FOR UPDATE"
            );

            $stmt->execute([':id' => $idReservacion]);
            $reserva = $stmt->fetch();

            if (!$reserva || $reserva['estado'] !== 'pendiente') {
                throw new RuntimeException(
                    'La reservación no existe o ya fue procesada.'
                );
            }

            // 2. RECHAZAR
            if ($accion === 'rechazar') {

                $stmt = $this->pdo->prepare(
                    "UPDATE reservaciones
                     SET estado = 'cancelada',
                         id_mesa = NULL
                     WHERE id_reservacion = :id"
                );

                $stmt->execute([
                    ':id' => $idReservacion
                ]);

            } else {

                // 3. CONFIRMAR

                // Validar que la reservación sea futura
                $zona = new DateTimeZone('America/Merida');

                $fechaReserva = new DateTimeImmutable(
                    $reserva['fecha_hora'],
                    $zona
                );

                if ($fechaReserva <= new DateTimeImmutable('now', $zona)) {
                    throw new RuntimeException(
                        'No se puede confirmar una reservación pasada.'
                    );
                }

                // Comprobar que se seleccionó una mesa
                if ($idMesa === null || $idMesa <= 0) {
                    throw new RuntimeException(
                        'Selecciona una mesa.'
                    );
                }

                // Buscar y bloquear la mesa
                $stmt = $this->pdo->prepare(
                    "SELECT id_mesa, capacidad
                     FROM mesas
                     WHERE id_mesa = :id
                     FOR UPDATE"
                );

                $stmt->execute([':id' => $idMesa]);
                $mesa = $stmt->fetch();

                if (!$mesa) {
                    throw new RuntimeException(
                        'La mesa seleccionada no existe.'
                    );
                }

                // Verificar capacidad
                if ((int)$mesa['capacidad'] < (int)$reserva['num_personas']) {
                    throw new RuntimeException(
                        'La mesa no tiene capacidad suficiente.'
                    );
                }

                // Verificar disponibilidad (duración: 2 horas)
                $inicio = $reserva['fecha_hora'];

                $fin = $fechaReserva
                    ->modify('+2 hours')
                    ->format('Y-m-d H:i:s');

                $stmt = $this->pdo->prepare(
                    "SELECT COUNT(*)
                     FROM reservaciones
                     WHERE id_mesa = :mesa
                       AND estado = 'confirmada'
                       AND fecha_hora < CAST(:fin AS timestamp)
                       AND fecha_hora + INTERVAL '2 hours'
                           > CAST(:inicio AS timestamp)"
                );

                $stmt->execute([
                    ':mesa' => $idMesa,
                    ':inicio' => $inicio,
                    ':fin' => $fin
                ]);

                if ((int)$stmt->fetchColumn() > 0) {
                    throw new RuntimeException(
                        'La mesa ya está reservada en ese horario.'
                    );
                }

                // Confirmar y asignar mesa
                $stmt = $this->pdo->prepare(
                    "UPDATE reservaciones
                     SET estado = 'confirmada',
                         id_mesa = :mesa
                     WHERE id_reservacion = :id"
                );

                $stmt->execute([
                    ':mesa' => $idMesa,
                    ':id' => $idReservacion
                ]);
            }

            // 4. Guardar cambios
            $this->pdo->commit();

        } catch (Throwable $e) {

            $this->pdo->rollBack();
            throw $e;
        }
    }
}
