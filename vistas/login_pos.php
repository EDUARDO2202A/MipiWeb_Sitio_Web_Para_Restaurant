<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header('Location: panel_pos.php');
    exit;
}

$errores = [
    'campos'       => 'Completa usuario y contraseña.',
    'inactivo'     => 'Usuario no encontrado o desactivado.',
    'credenciales' => 'Usuario o contraseña incorrectos.',
];
$msgError = $errores[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Iniciar sesión</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; display: grid; place-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; width: 100%; max-width: 340px; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        label { display: block; margin-top: 1rem; font-weight: bold; font-size: .9rem; }
        input { width: 100%; padding: .6rem; margin-top: .3rem; box-sizing: border-box; }
        button { width: 100%; padding: .7rem; margin-top: 1.5rem; background: #a8321b; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error { color: #b91c1c; background: #fee2e2; padding: .6rem; border-radius: 4px; font-size: .85rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <form class="card" action="../controladores/LoginController.php" method="POST">
        <h2>Punto de Venta</h2>
        <?php if ($msgError): ?>
            <div class="error"><?= htmlspecialchars($msgError) ?></div>
        <?php endif; ?>

        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" required>

        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" required>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>