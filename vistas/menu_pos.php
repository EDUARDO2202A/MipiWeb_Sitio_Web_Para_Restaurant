<?php
require_once __DIR__ . '/../config/verificar_sesion.php';
require_once __DIR__ . '/../modelos/MenuModel.php';

verificarSesion(['administrador']);

$modelo     = new MenuModel();
$categorias = $modelo->listarCategorias();
$productos  = $modelo->listarProductos();

$editCat  = isset($_GET['editar_cat']) ? $modelo->obtenerCategoria((int)$_GET['editar_cat']) : null;
$editProd = isset($_GET['editar_prod']) ? $modelo->obtenerProducto((int)$_GET['editar_prod']) : null;

$oks = [
    'cat_creada'   => 'Categoría creada.',
    'cat_editada'  => 'Categoría actualizada.',
    'prod_creado'  => 'Producto agregado al menú.',
    'prod_editado' => 'Producto actualizado.',
    'prod_estado'  => 'Disponibilidad actualizada.',
];
$errores = [
    'campos' => 'Revisa los campos obligatorios y que el precio no sea negativo.',
    'bd'     => 'Error en base de datos (posible categoría duplicada).',
];
$msgOk    = $oks[$_GET['ok'] ?? ''] ?? '';
$msgError = $errores[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb POS - Gestión del Menú</title>
    <link rel="stylesheet" href="assets/css/menu_pos.css">
</head>

<body class="menu-pos">
    <div class="top">
        <h1>Gestión del Menú</h1>
        <a href="panel_pos.php">← Volver al panel</a>
    </div>

    <?php if ($msgOk): ?><div class="ok"><?= htmlspecialchars($msgOk) ?></div><?php endif; ?>
    <?php if ($msgError): ?><div class="error"><?= htmlspecialchars($msgError) ?></div><?php endif; ?>

    <!-- SECCIÓN CATEGORÍAS -->
    <div class="box">
        <h3><?= $editCat ? 'Editar categoría' : 'Nueva categoría' ?></h3>
        <form action="../controladores/MenuController.php" method="POST">
            <input type="hidden" name="accion" value="<?= $editCat ? 'editar_categoria' : 'crear_categoria' ?>">
            <?php if ($editCat): ?>
                <input type="hidden" name="id_categoria" value="<?= (int)$editCat['id_categoria'] ?>">
            <?php endif; ?>
            <div class="grid">
                <div>
                    <label>Nombre</label>
                    <input type="text" name="nombre" required value="<?= htmlspecialchars($editCat['nombre'] ?? '') ?>">
                </div>
                <div>
                    <label>Área de preparación</label>
                    <select name="area" required>
                        <option value="cocina" <?= ($editCat && $editCat['area'] === 'cocina') ? 'selected' : '' ?>>Cocina</option>
                        <option value="barra" <?= ($editCat && $editCat['area'] === 'barra') ? 'selected' : '' ?>>Barra</option>
                    </select>
                </div>
            </div>
            
             <button type="submit">
             <?= $editCat ? 'Guardar categoría' : 'Agregar categoría' ?>
             </button>

             <?php if ($editCat): ?>
             <a href="menu_pos.php" class="cancelar">Cancelar</a>
            <?php endif; ?>

</form>


        <table>
            <thead><tr><th>ID</th><th>Categoría</th><th>Área</th><th>Acción</th></tr></thead>
            <tbody>
                <?php foreach ($categorias as $c): ?>
                    <tr>
                        <td><?= (int)$c['id_categoria'] ?></td>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($c['area'])) ?></td>
                        <td><a href="menu_pos.php?editar_cat=<?= (int)$c['id_categoria'] ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- SECCIÓN PRODUCTOS -->
    <div class="box">
        <h3><?= $editProd ? 'Editar producto' : 'Nuevo producto' ?></h3>
        <form action="../controladores/MenuController.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="<?= $editProd ? 'editar_producto' : 'crear_producto' ?>">
            <?php if ($editProd): ?>
                <input type="hidden" name="id_producto" value="<?= (int)$editProd['id_producto'] ?>">
            <?php endif; ?>
            <div class="grid">
                <div>
                    <label>Categoría</label>
                    <select name="id_categoria" required>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= (int)$c['id_categoria'] ?>" <?= ($editProd && (int)$editProd['id_categoria'] === (int)$c['id_categoria']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Nombre del platillo/bebida</label>
                    <input type="text" name="nombre" required value="<?= htmlspecialchars($editProd['nombre'] ?? '') ?>">
                </div>
                <div>
                    <label>Precio ($)</label>
                    <input type="number" name="precio" step="0.01" min="0" required value="<?= htmlspecialchars($editProd['precio'] ?? '') ?>">
                </div>
                <div>
                    <label>Descripción</label>
                    <input type="text" name="descripcion" value="<?= htmlspecialchars($editProd['descripcion'] ?? '') ?>">
                </div>
                <div>
                    <label>Imagen (opcional)</label>
                    <input type="file" name="imagen" accept="image/*">
                </div>
            </div>
            <button type="submit"><?= $editProd ? 'Guardar producto' : 'Agregar producto' ?></button>
            <?php if ($editProd): ?><a href="menu_pos.php" style="margin-left:1rem;">Cancelar</a><?php endif; ?>
        </form>

        <table>
            <thead>
                <tr><th>Foto</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <?php $disp = filter_var($p['disponible'], FILTER_VALIDATE_BOOLEAN); ?>
                    <tr>
                        <td>
                            <?php if (!empty($p['imagen'])): ?>
                                <img class="thumb" src="../<?= htmlspecialchars($p['imagen']) ?>" alt="">
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($p['nombre']) ?></strong><br>
                            <small><?= htmlspecialchars($p['descripcion'] ?? '') ?></small>
                        </td>
                        <td><?= htmlspecialchars($p['categoria']) ?></td>
                        <td>$<?= number_format((float)$p['precio'], 2) ?></td>
                        <td class="<?= $disp ? 'on' : 'off' ?>"><?= $disp ? 'Disponible' : 'Agotado' ?></td>
                        <td>
                            <a href="menu_pos.php?editar_prod=<?= (int)$p['id_producto'] ?>">Editar</a> |
                            <a href="../controladores/MenuController.php?accion=toggle_producto&id=<?= (int)$p['id_producto'] ?>">
                                <?= $disp ? 'Pausar' : 'Activar' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>