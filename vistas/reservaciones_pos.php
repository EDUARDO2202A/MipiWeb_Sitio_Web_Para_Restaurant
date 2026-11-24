
<?php

require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/ReservacionModel.php';

verificarSesion(['administrador', 'cajero']);

// Generar token de seguridad
if (empty($_SESSION['csrf_reserva_admin'])) {
    $_SESSION['csrf_reserva_admin'] = bin2hex(random_bytes(32));
}

$modelo = new ReservacionModel();

$reservaciones = $modelo->listar();
$mesas = $modelo->listarMesas();

// Mensajes
$mensajesOk = [
    'confirmar' => 'Reservación confirmada correctamente.',
    'rechazar' => 'Reservación rechazada correctamente.'
];

$mensajesError = [
    'seguridad' => 'Solicitud de seguridad inválida.',
    'datos' => 'Los datos enviados son incorrectos.',
    'operacion' => 'No se pudo completar la operación. Revisa la disponibilidad y capacidad de la mesa.'
];

$msgOk = $mensajesOk[$_GET['ok'] ?? ''] ?? '';
$msgError = $mensajesError[$_GET['error'] ?? ''] ?? '';

function e($valor): string {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>MipiWeb POS - Reservaciones</title>

    <link
        rel="stylesheet"
        href="assets/css/reservaciones_pos.css">
</head>

<body class="reservaciones-pos">

    <header class="encabezado">

        <div>
            <h1>Gestión de reservaciones</h1>
            <p>Administra las solicitudes de tus clientes.</p>
        </div>

        <a href="panel_pos.php" class="volver">
            Volver al panel
        </a>

    </header>

    <?php if ($msgOk): ?>
        <div class="mensaje exito" role="status">
            <?= e($msgOk) ?>
        </div>
    <?php endif; ?>

    <?php if ($msgError): ?>
        <div class="mensaje error" role="alert">
            <?= e($msgError) ?>
        </div>
    <?php endif; ?>

    <section class="contenedor-tabla">

        <h2>Solicitudes recibidas</h2>

        <?php if (empty($reservaciones)): ?>

            <p>No hay reservaciones registradas.</p>

        <?php else: ?>

            <div class="tabla-scroll">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Fecha y hora</th>
                            <th>Personas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($reservaciones as $r): ?>

                            <tr>

                                <td>
                                    #<?= (int)$r['id_reservacion'] ?>
                                </td>

                                <td>
                                    <?= e($r['nombre_contacto']) ?>
                                </td>

                                <td>
                                    <?= e($r['telefono']) ?>
                                </td>

                                <td>
                                    <?= e($r['fecha_hora']) ?>
                                </td>

                                <td>
                                    <?= (int)$r['num_personas'] ?>
                                </td>

                                <td>
                                    <span class="estado">
                                        <?= e(ucfirst($r['estado'])) ?>
                                    </span>
                                </td>

                                <td>

                                    <?php if ($r['estado'] === 'pendiente'): ?>

                                        <!-- CONFIRMAR -->

                                        <form
                                            action="../controladores/ReservacionAdminController.php"
                                            method="POST"
                                            class="form-accion">

                                            <input
                                                type="hidden"
                                                name="csrf"
                                                value="<?= e($_SESSION['csrf_reserva_admin']) ?>">

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="confirmar">

                                            <input
                                                type="hidden"
                                                name="id_reservacion"
                                                value="<?= (int)$r['id_reservacion'] ?>">

                                            <select
                                                name="id_mesa"
                                                aria-label="Mesa para reservación <?= (int)$r['id_reservacion'] ?>"
                                                required>

                                                <option value="">
                                                    Seleccionar mesa
                                                </option>

                                                <?php foreach ($mesas as $mesa): ?>

                                                    <?php if (
                                                        (int)$mesa['capacidad']
                                                        >= (int)$r['num_personas']
                                                    ): ?>

                                                        <option
                                                            value="<?= (int)$mesa['id_mesa'] ?>">

                                                            Mesa <?= (int)$mesa['numero'] ?>
                                                            (<?= (int)$mesa['capacidad'] ?> personas)

                                                        </option>

                                                    <?php endif; ?>

                                                <?php endforeach; ?>

                                            </select>

                                            <button
                                                type="submit"
                                                class="btn-confirmar">

                                                Confirmar

                                            </button>

                                        </form>


                                        <!-- RECHAZAR -->

                                        <form
                                            action="../controladores/ReservacionAdminController.php"
                                            method="POST"
                                            class="form-accion">

                                            <input
                                                type="hidden"
                                                name="csrf"
                                                value="<?= e($_SESSION['csrf_reserva_admin']) ?>">

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="rechazar">

                                            <input
                                                type="hidden"
                                                name="id_reservacion"
                                                value="<?= (int)$r['id_reservacion'] ?>">

                                            <button
                                                type="submit"
                                                class="btn-rechazar">

                                                Rechazar

                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span class="sin-acciones">
                                            Procesada
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</body>
</html>
