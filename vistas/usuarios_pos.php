<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/UsuarioModel.php';

verificarSesion(['administrador']);

$modelo   = new UsuarioModel();
$usuarios = $modelo->listarTodos();
$roles    = $modelo->listarRoles();

$editando = null;
if (isset($_GET['editar'])) {
    $editando = $modelo->obtenerPorId((int)$_GET['editar']);
}

$mensajesOk = [
    'creado'  => 'Usuario creado correctamente.',
    'editado' => 'Usuario actualizado correctamente.',
    'estado'  => 'Estado del usuario actualizado.',
];
$mensajesError = [
    'campos'    => 'Completa todos los campos obligatorios.',
    'duplicado' => 'Ese nombre de usuario ya existe.',
    'propio'    => 'No puedes desactivar tu propia cuenta en uso.',
];
$msgOk    = $mensajesOk[$_GET['ok'] ?? ''] ?? '';
$msgError = $mensajesError[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Gestión de Usuarios</title>
    <link rel="stylesheet" href="assets/css/usuarios_pos.css">
   
</head>

<body class="usuarios-pos">
    <div class="top">
        <h1>Gestión de Usuarios</h1>
        <a href="panel_pos.php">← Volver al panel</a>
    </div>

    <?php if ($msgOk): ?><div class="ok"><?= htmlspecialchars($msgOk) ?></div><?php endif; ?>
    <?php if ($msgError): ?><div class="error"><?= htmlspecialchars($msgError) ?></div><?php endif; ?>

    <form class="box" action="../controladores/UsuarioController.php" method="POST">
        <h3><?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
        <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id_usuario" value="<?= (int)$editando['id_usuario'] ?>">
        <?php endif; ?>

        <div class="grid">
            <div>
                <label>Nombre</label>
                <input type="text" name="nombre" required value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>">
            </div>
            <div>
                <label>Apellidos</label>
                <input type="text" name="apellidos" required value="<?= htmlspecialchars($editando['apellidos'] ?? '') ?>">
            </div>
            <div>
                <label>Usuario</label>
                <input type="text" name="usuario" required value="<?= htmlspecialchars($editando['usuario'] ?? '') ?>">
            </div>
            <div>
                <label>Rol</label>
                <select name="id_rol" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id_rol'] ?>" <?= ($editando && (int)$editando['id_rol'] === (int)$r['id_rol']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($r['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><?= $editando ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
                <input type="password" name="contrasena" <?= $editando ? '' : 'required' ?>>
            </div>
        </div>

        <button type="submit"><?= $editando ? 'Guardar cambios' : 'Crear usuario' ?></button>
        <?php if ($editando): ?>
           <a href="usuarios_pos.php" class="cancelar">Cancelar edición</a>
        <?php endif; ?>
    </form>

    <div class="box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre completo</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <?php $activo = filter_var($u['activo'], FILTER_VALIDATE_BOOLEAN); ?>
                    <tr>
                        <td><?= (int)$u['id_usuario'] ?></td>
                        <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></td>
                        <td><?= htmlspecialchars($u['usuario']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($u['rol'])) ?></td>
                        <td class="<?= $activo ? 'badge-on' : 'badge-off' ?>">
                            <?= $activo ? 'Activo' : 'Inactivo' ?>
                        </td>
                        <td>
                            <a href="usuarios_pos.php?editar=<?= (int)$u['id_usuario'] ?>">Editar</a> |
                            <a href="../controladores/UsuarioController.php?accion=toggle&id=<?= (int)$u['id_usuario'] ?>">
                                <?= $activo ? 'Desactivar' : 'Activar' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>