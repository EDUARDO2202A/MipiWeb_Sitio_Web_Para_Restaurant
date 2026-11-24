<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/CajaModel.php';

verificarSesion(['administrador', 'cajero']);

$modelo = new CajaModel();
$accion = $_POST['accion'] ?? '';

try {
    if ($accion === 'abrir_turno' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $montoInicial = (float)($_POST['monto_inicial'] ?? -1);
        if ($montoInicial < 0) {
            header('Location: ../vistas/caja_pos.php?error=monto');
            exit;
        }
        $modelo->abrirTurno((int)$_SESSION['id_usuario'], $montoInicial);
        header('Location: ../vistas/caja_pos.php?ok=turno_abierto');
        exit;
    }

    if ($accion === 'cerrar_turno' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $idTurno     = (int)($_POST['id_turno'] ?? 0);
        $montoCierre = (float)($_POST['monto_cierre'] ?? -1);
        if ($idTurno <= 0 || $montoCierre < 0) {
            header('Location: ../vistas/caja_pos.php?error=monto');
            exit;
        }
        $modelo->cerrarTurno($idTurno, $montoCierre);
        header('Location: ../vistas/caja_pos.php?ok=turno_cerrado');
        exit;
    }

    if ($accion === 'registrar_pago' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $turno = $modelo->obtenerTurnoAbierto();
        if (!$turno) {
            header('Location: ../vistas/caja_pos.php?error=sin_turno');
            exit;
        }

        $idPedido = (int)($_POST['id_pedido'] ?? 0);
        $metodo   = $_POST['metodo'] ?? '';
        $monto    = (float)($_POST['monto'] ?? 0);

        if ($idPedido <= 0 || $monto <= 0 || !in_array($metodo, ['efectivo', 'tarjeta', 'transferencia'], true)) {
            header('Location: ../vistas/caja_pos.php?error=pago_invalido');
            exit;
        }

        $modelo->registrarPago($idPedido, (int)$turno['id_turno'], $metodo, $monto);
        header('Location: ../vistas/caja_pos.php?ok=pagado&ticket=' . $idPedido);
        exit;
    }
} catch (Throwable $e) {
    header('Location: ../vistas/caja_pos.php?error=bd');
    exit;
}

header('Location: ../vistas/caja_pos.php');
exit;