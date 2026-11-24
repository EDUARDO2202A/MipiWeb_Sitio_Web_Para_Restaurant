
<?php

require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/ReservacionModel.php';

// Solo administrador y cajero
verificarSesion(['administrador', 'cajero']);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/reservaciones_pos.php');
    exit;
}

// Comprobar token de seguridad
$token = $_POST['csrf'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_reserva_admin']) ||
    !hash_equals($_SESSION['csrf_reserva_admin'], $token)
) {
    header('Location: ../vistas/reservaciones_pos.php?error=seguridad');
    exit;
}

// Obtener datos
$accion = $_POST['accion'] ?? '';

$idReservacion = filter_var(
    $_POST['id_reservacion'] ?? null,
    FILTER_VALIDATE_INT
);

$idMesa = filter_var(
    $_POST['id_mesa'] ?? null,
    FILTER_VALIDATE_INT
);

// Validaciones
if (
    $idReservacion === false ||
    $idReservacion === null ||
    $idReservacion <= 0 ||
    !in_array($accion, ['confirmar', 'rechazar'], true)
) {
    header('Location: ../vistas/reservaciones_pos.php?error=datos');
    exit;
}

try {

    $modelo = new ReservacionModel();

    $modelo->gestionar(
        $idReservacion,
        $accion,
        $accion === 'confirmar' && $idMesa !== false
            ? $idMesa
            : null
    );

    header(
        'Location: ../vistas/reservaciones_pos.php?ok=' .
        $accion
    );
    exit;

} catch (Throwable $e) {

    // Registrar el error técnico en el servidor
    error_log('Error de reservaciones: ' . $e->getMessage());

    header(
        'Location: ../vistas/reservaciones_pos.php?error=operacion'
    );
    exit;
}
