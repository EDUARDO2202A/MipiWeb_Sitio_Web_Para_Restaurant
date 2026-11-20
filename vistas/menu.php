<?php
require_once __DIR__ . '/../modelos/MenuModel.php';

$modelo    = new MenuModel();
$productos = $modelo->listarProductosDisponibles();

// Agrupar productos por nombre de categoría
$menuPorCategoria = [];
foreach ($productos as $p) {
    $menuPorCategoria[$p['categoria']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MipiWeb - Menú</title>
</head>
<body style="font-family: sans-serif; margin: 2rem;">
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <h1>Nuestro Menú</h1>
        <a href="inicio.php">← Volver al inicio</a>
    </header>

    <?php if (empty($menuPorCategoria)): ?>
        <p>Por el momento no hay productos disponibles en el menú.</p>
    <?php else: ?>
        <?php foreach ($menuPorCategoria as $categoria => $items): ?>
            <section style="margin-top: 2rem;">
                <h2><?= htmlspecialchars($categoria) ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                    <?php foreach ($items as $item): ?>
                        <article style="border: 1px solid #ddd; padding: 1rem; border-radius: 6px;">
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="../<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>" style="width: 100%; height: 140px; object-fit: cover; border-radius: 4px;">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($item['nombre']) ?></h3>
                            <?php if (!empty($item['descripcion'])): ?>
                                <p><?= htmlspecialchars($item['descripcion']) ?></p>
                            <?php endif; ?>
                            <strong>$<?= number_format((float)$item['precio'], 2) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>