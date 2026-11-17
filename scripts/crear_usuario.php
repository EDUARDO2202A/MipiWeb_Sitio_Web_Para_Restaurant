<?php
declare(strict_types=1);
// Solo ejecución CLI: php scripts/crear_usuario.php "Nombre" correo@ejemplo.com empleado
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Prohibido'); }
if ($argc !== 4) {
    fwrite(STDERR, "Uso: php scripts/crear_usuario.php \"Nombre\" correo@ejemplo.com empleado|admin\n");
    exit(1);
}
[, $nombre, $correo, $rol] = $argv;
$nombre = trim($nombre);
$correo = strtolower(trim($correo));
if ($nombre === '' || strlen($nombre) > 120 || !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 255 || !in_array($rol, ['admin', 'empleado'], true)) {
    fwrite(STDERR, "Nombre, correo o rol no válidos.\n");
    exit(1);
}
require_once __DIR__ . '/../modelos/conexion.php';
$password = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
try {
    $db = (new Conexion())->conectar();
    $stmt = $db->prepare('INSERT INTO usuarios(nombre,correo,password_hash,rol) VALUES (:nombre,:correo,:hash,:rol)');
    $stmt->execute([
        'nombre'=>$nombre, 'correo'=>$correo,
        'hash'=>password_hash($password, PASSWORD_DEFAULT), 'rol'=>$rol
    ]);
    echo "Usuario creado: $correo\nContraseña temporal generada: $password\n";
    echo "Guárdala de manera segura y compártela por un canal privado.\n";
} catch (Throwable $e) {
    error_log('Error creando usuario: ' . $e->getMessage());
    fwrite(STDERR, "No se pudo crear el usuario. Verifica la conexión y que el correo no exista.\n");
    exit(1);
}
