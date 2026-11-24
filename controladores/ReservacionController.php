
<?php

session_start();

require_once __DIR__ . '/../modelos/ReservacionModel.php';

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/inicio.php#reservar');
    exit;
}

// Verificar el token de seguridad del formulario
$token = $_POST['csrf'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_reserva']) ||
    !hash_equals($_SESSION['csrf_reserva'], $token)
) {
    header('Location: ../vistas/inicio.php?reserva=error#reservar');
    exit;
}

// Recibir datos del formulario
$nombre = trim($_POST['nombre_contacto'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';

$personas = filter_var(
    $_POST['num_personas'] ?? null,
    FILTER_VALIDATE_INT
);

// Validar los datos
if (
    $nombre === '' ||
    mb_strlen($nombre) > 120 ||
    !preg_match('/^[0-9]{10}$/', $telefono) ||
    $personas === false ||
    $personas < 1 ||
    $personas > 12
) {
    header('Location: ../vistas/inicio.php?reserva=error#reservar');
    exit;
}

// Validar fecha y hora
$zona = new DateTimeZone('America/Merida');

$fechaHora = DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i',
    $fecha . ' ' . $hora,
    $zona
);

if (
    !$fechaHora ||
    $fechaHora->format('Y-m-d H:i') !== $fecha . ' ' . $hora ||
    $fechaHora <= new DateTimeImmutable('now', $zona)
) {
    header('Location: ../vistas/inicio.php?reserva=error#reservar');
    exit;
}

// Guardar reservación mediante el modelo
try {

    $modelo = new ReservacionModel();

    $guardado = $modelo->crear(
        $nombre,
        $telefono,
        $fechaHora->format('Y-m-d H:i:s'),
        $personas
    );

    if ($guardado) {
        unset($_SESSION['csrf_reserva']);

        header('Location: ../vistas/inicio.php?reserva=ok#reservar');
        exit;
    }

} catch (Throwable $e) {
    error_log('Error al guardar reservación: ' . $e->getMessage());
}

header('Location: ../vistas/inicio.php?reserva=error#reservar');
exit;
