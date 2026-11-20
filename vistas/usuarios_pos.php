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
    <style>
        body { font-family: sans-serif; margin: 2rem; background: #f8f9fa; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .box { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.08); margin-bottom: 1.5rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
        label { display: block; font-size: .85rem; font-weight: bold; margin-bottom: .3rem; }
        input, select { width: 100%; padding: .5rem; box-sizing: border-box; }
        button { padding: .6rem 1.2rem; background: #a8321b; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: .7rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f1f5f9; }
        .ok { background: #dcfce7; color: #166534; padding: .7rem; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #fee2e2; color: #991b1b; padding: .7rem; border-radius: 4px; margin-bottom: 1rem; }
        .badge-on { color: #166534; font-weight: bold; }
        .badge-off { color: #991b1b; font-weight: bold; }
        a { color: #a8321b; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
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
            <a href="usuarios_pos.php" style="margin-left: 1rem;">Cancelar edición</a>
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