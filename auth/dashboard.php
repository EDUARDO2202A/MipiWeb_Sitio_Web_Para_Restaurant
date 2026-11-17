<?php
declare(strict_types=1);
require_once __DIR__ . '/sesion.php';
$usuario = exigirLogin();
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel | MipiWeb</title></head><body>
<h1>Bienvenido, <?= h((string)$usuario['nombre']) ?></h1>
<p>Cuenta: <?= h((string)$usuario['correo']) ?></p>
<p>Rol: <?= h((string)$usuario['rol']) ?></p>
<form action="logout.php" method="post">
<input type="hidden" name="csrf" value="<?= h(tokenCsrf()) ?>">
<button type="submit">Cerrar sesión</button>
</form>
</body></html>
