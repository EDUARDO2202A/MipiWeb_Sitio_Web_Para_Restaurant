<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('mipiweb_session');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_start();
}
function usuarioActual(): ?array {
    if (!isset($_SESSION['usuario'], $_SESSION['ultima_actividad'])) return null;
    if (time() - (int)$_SESSION['ultima_actividad'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $_SESSION['ultima_actividad'] = time();
    return $_SESSION['usuario'];
}
function exigirLogin(): array {
    $usuario = usuarioActual();
    if ($usuario === null) {
        header('Location: login.php');
        exit;
    }
    return $usuario;
}
function tokenCsrf(): string {
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
