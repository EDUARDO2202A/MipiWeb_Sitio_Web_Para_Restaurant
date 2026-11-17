<?php
declare(strict_types=1);
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/../modelos/conexion.php';
if (usuarioActual() !== null) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(tokenCsrf(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Solicitud no válida. Recarga la página.';
    } else {
        $correo = strtolower(trim((string)($_POST['correo'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'Correo o contraseña incorrectos.';
        } else {
            try {
                $db = (new Conexion())->conectar();
                $stmt = $db->prepare('SELECT id,nombre,correo,rol,password_hash,intentos_fallidos,bloqueado_hasta FROM usuarios WHERE correo = :correo');
                $stmt->execute(['correo' => $correo]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
                $bloqueado = $u && $u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time();
                $valido = $u && !$bloqueado && password_verify($password, $u['password_hash']);
                if ($valido) {
                    $db->prepare('UPDATE usuarios SET intentos_fallidos=0,bloqueado_hasta=NULL WHERE id=:id')->execute(['id'=>$u['id']]);
                    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                        $db->prepare('UPDATE usuarios SET password_hash=:hash WHERE id=:id')->execute([
                            'hash'=>password_hash($password, PASSWORD_DEFAULT), 'id'=>$u['id']
                        ]);
                    }
                    session_regenerate_id(true);
                    $_SESSION['usuario'] = [
                        'id'=>$u['id'], 'nombre'=>$u['nombre'], 'correo'=>$u['correo'], 'rol'=>$u['rol']
                    ];
                    $_SESSION['ultima_actividad'] = time();
                    header('Location: dashboard.php');
                    exit;
                }
                if ($u && !$bloqueado) {
                    $intentos = (int)$u['intentos_fallidos'] + 1;
                    $db->prepare("UPDATE usuarios SET intentos_fallidos=:n,
                       bloqueado_hasta=CASE WHEN :n2 >= 5 THEN NOW()+INTERVAL '15 minutes' ELSE NULL END
                       WHERE id=:id")->execute(['n'=>$intentos,'n2'=>$intentos,'id'=>$u['id']]);
                }
                $error = 'Correo o contraseña incorrectos, o cuenta temporalmente bloqueada.';
            } catch (Throwable $e) {
                error_log('MipiWeb login: ' . $e->getMessage());
                $error = 'No fue posible iniciar sesión. Intenta de nuevo.';
            }
        }
    }
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión | MipiWeb</title>
<style>
body{font-family:system-ui,sans-serif;background:#f7f7f7;display:grid;place-items:center;min-height:100vh;margin:0}
main{background:white;border:1px solid #ddd;border-radius:12px;padding:28px;width:min(350px,85vw)}
label{display:block;margin:16px 0 6px}input{box-sizing:border-box;width:100%;padding:10px}
button{width:100%;padding:12px;margin-top:20px;cursor:pointer}
.error{color:#9b1c1c}
</style></head><body><main><h1>MipiWeb</h1><h2>Iniciar sesión</h2>
<?php if ($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= h(tokenCsrf()) ?>">
<label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" autocomplete="username" required maxlength="255">
<label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required>
<button type="submit">Entrar</button>
</form></main></body></html>
