
<?php
session_start();

require_once __DIR__ . '/../modelos/PedidoPublicoModel.php';

if (empty($_SESSION['csrf_pedido'])) {
    $_SESSION['csrf_pedido'] = bin2hex(random_bytes(32));
}

function escapar($valor): string {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

$productos = [];
$errorMenu = false;

try {
    $modelo = new PedidoPublicoModel();
    $productos = $modelo->listarProductos();
} catch (Throwable $e) {
    error_log('Error cargando menú: ' . $e->getMessage());
    $errorMenu = true;
}

$mensajeError = $_GET['error'] ?? '';
$pedidoConfirmado = null;

if (
    ($_GET['ok'] ?? '') === 'pedido' &&
    isset($_SESSION['pedido_confirmado'])
) {
    $pedidoConfirmado = (int)$_SESSION['pedido_confirmado'];
    unset($_SESSION['pedido_confirmado']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Pedir a domicilio | MipiWeb</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="assets/css/pedido_domicilio.css">

    <script src="../assets/pedido_domicilio.js" defer></script>
</head>

<body>

<header class="barra">
    <a class="logo" href="inicio.php">MipiWeb</a>
    <a class="volver" href="inicio.php">
        Volver al inicio
    </a>
</header>

<main class="contenedor">

    <section class="encabezado">
        <span class="etiqueta">PEDIDOS A DOMICILIO</span>
        <h1>¿Qué se te antoja hoy?</h1>
        <p>
            Elige tus platillos favoritos y
            prepara tu pedido.
        </p>
    </section>

    <?php if ($pedidoConfirmado !== null): ?>
        <div class="mensaje exito"
             role="status"
             id="pedido-exitoso">
            ¡Pedido #<?= $pedidoConfirmado ?> recibido!
            El restaurante ya puede consultarlo
            para comenzar a procesarlo.
        </div>
    <?php endif; ?>

    <?php if ($mensajeError !== ''): ?>
        <div class="mensaje error" role="alert">
            No pudimos registrar el pedido.
            Verifica tus datos e inténtalo nuevamente.
        </div>
    <?php endif; ?>

    <div class="distribucion">

        <!-- MENÚ -->
        <section class="menu">
            <h2>Selecciona tus platillos</h2>

            <?php if ($errorMenu): ?>

                <p>
                    No se pudo cargar el menú.
                    Intenta actualizar la página.
                </p>

            <?php elseif (empty($productos)): ?>

                <p>
                    No hay productos disponibles
                    por el momento.
                </p>

            <?php else: ?>

                <div class="productos-grid">

                <?php foreach ($productos as $producto): ?>

                    <article class="producto"
                        data-id="<?= (int)$producto['id_producto'] ?>"
                        data-nombre="<?= escapar($producto['nombre']) ?>"
                        data-precio="<?= escapar($producto['precio']) ?>">

                        <span class="categoria">
                            <?= escapar($producto['categoria']) ?>
                        </span>

                        <h3>
                            <?= escapar($producto['nombre']) ?>
                        </h3>

                        <p class="descripcion">
                            <?= escapar($producto['descripcion'] ?? '') ?>
                        </p>

                        <div class="producto-pie">
                            <strong>
                                $<?= number_format(
                                    (float)$producto['precio'],
                                    2
                                ) ?>
                            </strong>

                            <button type="button"
                                    class="agregar">
                                <span aria-hidden="true">+</span>
                                Agregar
                            </button>
                        </div>
                    </article>

                <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <!-- CARRITO -->
        <aside class="panel-carrito">

            <h2>Tu carrito</h2>

            <div id="carrito-items">
                <p class="vacio">
                    Todavía no agregas productos.
                </p>
            </div>

            <div class="total">
                <span>Total de productos</span>
                <strong id="carrito-total">$0.00</strong>
            </div>

            <p class="aviso">
                El total no incluye posibles
                costos de entrega.
            </p>

            <h2>Datos de entrega</h2>

            <form
                id="form-pedido"
                method="POST"
                action="../controladores/PedidoPublicoController.php">

                <input
                    type="hidden"
                    name="csrf_pedido"
                    value="<?= escapar($_SESSION['csrf_pedido']) ?>">

                <input
                    type="hidden"
                    name="carrito"
                    id="carrito-json">

                <label for="nombre">Nombre completo</label>
                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    maxlength="120"
                    autocomplete="name"
                    required>

                <label for="telefono">Teléfono</label>
                <input
                    id="telefono"
                    name="telefono"
                    type="tel"
                    pattern="[0-9]{10}"
                    minlength="10"
                    maxlength="10"
                    inputmode="numeric"
                    placeholder="9861234567"
                    autocomplete="tel"
                    required>

                <label for="direccion">
                    Dirección de entrega
                </label>
                <textarea
                    id="direccion"
                    name="direccion"
                    rows="3"
                    maxlength="500"
                    placeholder="Calle, número, colonia y localidad"
                    required></textarea>

                <label for="referencias">
                    Referencias (opcional)
                </label>
                <textarea
                    id="referencias"
                    name="referencias"
                    rows="2"
                    maxlength="500"
                    placeholder="Color de casa, entre calles..."></textarea>

                <p id="error-carrito"
                   class="error-carrito"
                   role="alert"></p>

                <button
                    type="submit"
                    class="confirmar"
                    id="btn-confirmar">
                    Confirmar pedido
                </button>

            </form>

        </aside>
    </div>

</main>

<footer class="pie">
    MipiWeb · Pedidos a domicilio
</footer>

</body>
</html>
