<?php
declare(strict_types=1);
require_once __DIR__ . '/sesion.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !hash_equals(tokenCsrf(), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(405);
    exit('Solicitud no permitida');
}
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
