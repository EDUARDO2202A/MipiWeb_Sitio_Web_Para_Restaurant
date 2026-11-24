
<?php
session_start();

// Token de seguridad para reservaciones
if (empty($_SESSION['csrf_reserva'])) {
    $_SESSION['csrf_reserva'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../modelos/conexion.php';

$conexion = new Conexion();
$db = $conexion->conectar();

// Información del restaurante
$stmtRest = $db->prepare("SELECT * FROM restaurante LIMIT 1");
$stmtRest->execute();
$restaurante = $stmtRest->fetch();

// Productos disponibles
$sqlProductos = "SELECT p.*, c.nombre AS categoria
                 FROM productos p
                 INNER JOIN categorias c
                    ON p.id_categoria = c.id_categoria
                 WHERE p.disponible = TRUE
                 ORDER BY p.id_producto
                 LIMIT 6";

$stmtProd = $db->prepare($sqlProductos);
$stmtProd->execute();
$platillos = $stmtProd->fetchAll();

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$estadoReserva = $_GET['reserva'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= escapar($restaurante['nombre'] ?? 'MipiWeb') ?>
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/inicio.css">

    <!-- JavaScript -->
    <script src="../assets/inicio.js" defer></script>
</head>

<body class="inicio-page">

    <!-- ===== BARRA DE NAVEGACIÓN ===== -->

    <header class="navbar" id="navbar">

        <nav class="nav-contenedor"
             aria-label="Navegación principal">

            <a href="#inicio" class="nav-logo">
                MIPI IWEB
            </a>

            <button
                class="nav-toggle"
                id="nav-toggle"
                type="button"
                aria-label="Abrir menú"
                aria-expanded="false"
                aria-controls="nav-enlaces">

                <span></span>
                <span></span>
                <span></span>

            </button>

            <div class="nav-enlaces" id="nav-enlaces">

                <a href="#inicio">Inicio</a>

                <a href="#menu">Menú</a>

                <a href="#nosotros">Nosotros</a>

                <a href="#reservar" class="nav-reservar">
                    Reservar mesa
                </a>

            </div>

        </nav>

    </header>


    <!-- ===== CONTENIDO PRINCIPAL ===== -->

    <main class="pagina-principal">


        <!-- ===== PORTADA ===== -->

        <section id="inicio" class="hero">

            <div class="hero-contenido">

                <span class="etiqueta">
                    BIENVENIDO A MIPI IWEB
                </span>

                <h1>
                    <?= escapar(
                        $restaurante['nombre'] ?? 'MipiWeb'
                    ) ?>
                </h1>

                <p class="hero-descripcion">
                    <?= escapar(
                        $restaurante['descripcion']
                        ?? 'Cocina para uno, cocina para todos.'
                    ) ?>
                </p>

                <div class="hero-botones">

                    <a href="#menu"
                       class="boton boton-principal">
                        Explorar menú
                    </a>
                   
                    <a href="pedido_domicilio.php"
                         class="boton boton-secundario">
                         Pedir a domicilio
                    </a>

                    <a href="#nosotros"
                       class="boton boton-secundario">
                        Conócenos
                    </a>

                </div>

                <span class="indicador-scroll">
                    Desliza para descubrir ↓
                </span>

            </div>

        </section>


        <!-- ===== MENÚ ===== -->

        <section id="menu" class="seccion-menu">

            <div class="contenedor">

                <div class="encabezado-seccion revelar">

                    <span class="etiqueta-seccion">
                        NUESTROS SABORES
                    </span>

                    <h2>Descubre nuestro menú</h2>

                    <p>
                        Platillos preparados para disfrutar
                        y compartir.
                    </p>

                </div>

                <div class="productos-grid">

                    <?php foreach ($platillos as $producto): ?>

                        <article class="producto-card revelar">

                            <div class="producto-decoracion">

                                <span>
                                    <?= escapar(
                                        $producto['categoria']
                                    ) ?>
                                </span>

                            </div>

                            <div class="producto-info">

                                <h3>
                                    <?= escapar(
                                        $producto['nombre']
                                    ) ?>
                                </h3>

                                <p>
                                    <?= escapar(
                                        $producto['descripcion'] ?? ''
                                    ) ?>
                                </p>

                                <strong>
                                    $<?= number_format(
                                        (float)$producto['precio'],
                                        2
                                    ) ?>
                                </strong>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <?php if (empty($platillos)): ?>

                    <p class="mensaje-vacio">
                        Próximamente tendremos nuevos platillos.
                    </p>

                <?php endif; ?>

            </div>

        </section>


        <!-- ===== RESERVACIONES ===== -->

        <section id="reservar" class="seccion-reservaciones">

            <div class="contenedor reservas-contenido">

                <div class="reservas-encabezado revelar">

                    <span class="reservas-etiqueta">
                        RESERVACIONES
                    </span>

                    <h2>Una mesa te espera.</h2>

                    <p>
                        Los mejores momentos se comparten.
                        Solicita tu mesa y disfruta con nosotros.
                    </p>

                </div>


                <!-- MENSAJES DE RESULTADO -->

                <?php if ($estadoReserva === 'ok'): ?>

                    <p class="reserva-exito" role="status">
                        ¡Solicitud enviada correctamente!
                        Tu reservación está pendiente de
                        confirmación por el restaurante.
                    </p>

                <?php elseif ($estadoReserva === 'error'): ?>

                    <p class="reserva-error" role="alert">
                        No se pudo registrar tu reservación.
                        Revisa tus datos e inténtalo nuevamente.
                    </p>

                <?php endif; ?>


                <!-- FORMULARIO DE RESERVACIÓN -->

                <form
                    class="form-reservacion revelar"
                    id="form-reservacion"
                    action="../controladores/ReservacionController.php"
                    method="POST">

                    <!-- TOKEN DE SEGURIDAD -->

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= escapar(
                            $_SESSION['csrf_reserva']
                        ) ?>">


                    <!-- NOMBRE -->

                    <div class="campo-reserva">

                        <label for="nombre_contacto">
                            Nombre completo
                        </label>

                        <input
                            type="text"
                            id="nombre_contacto"
                            name="nombre_contacto"
                            placeholder="Escribe tu nombre completo"
                            maxlength="120"
                            autocomplete="name"
                            required>

                    </div>


                    <!-- TELÉFONO -->

                    <div class="campo-reserva">

                        <label for="telefono">
                            Teléfono
                        </label>

                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            placeholder="Ej. 9861234567"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            minlength="10"
                            maxlength="10"
                            required>

                    </div>


                    <!-- FECHA Y HORA -->

                    <div class="reservas-grid">

                        <div class="campo-reserva">

                            <label for="fecha">
                                Fecha
                            </label>

                            <input
                                type="date"
                                id="fecha"
                                name="fecha"
                                min="<?= date('Y-m-d') ?>"
                                required>

                        </div>

                        <div class="campo-reserva">

                            <label for="hora">
                                Hora
                            </label>

                            <input
                                type="time"
                                id="hora"
                                name="hora"
                                required>

                        </div>

                    </div>


                    <!-- NÚMERO DE PERSONAS -->

                    <div class="campo-reserva">

                        <label for="num_personas">
                            Número de personas
                        </label>

                        <select
                            id="num_personas"
                            name="num_personas"
                            required>

                            <option value="">
                                Selecciona una opción
                            </option>

                            <?php for ($i = 1; $i <= 12; $i++): ?>

                                <option value="<?= $i ?>">
                                    <?= $i ?>
                                    persona<?= $i > 1 ? 's' : '' ?>
                                </option>

                            <?php endfor; ?>

                        </select>

                    </div>


                    <!-- BOTÓN PARA ENVIAR -->

                    <button
                        type="submit"
                        class="boton-reservar">

                        Solicitar reservación

                    </button>


                    <!-- AVISO -->

                    <p class="reservas-nota">
                        Las reservaciones están sujetas
                        a disponibilidad y confirmación
                        del restaurante.
                    </p>

                </form>

            </div>

        </section>


        <!-- ===== SOBRE NOSOTROS ===== -->

        <section id="nosotros" class="seccion-nosotros">

            <div class="contenedor nosotros-contenido revelar">

                <span class="etiqueta-seccion">
                    NUESTRA HISTORIA
                </span>

                <h2>Más que comida, momentos.</h2>

                <p>
                    <?= escapar(
                        !empty($restaurante['historia'])
                            ? $restaurante['historia']
                            : 'Un espacio donde la buena comida reúne a las personas.'
                    ) ?>
                </p>

                <a href="#inicio"
                   class="boton boton-principal">
                    Volver al inicio
                </a>

            </div>

        </section>

    </main>


    <!-- ===== PIE DE PÁGINA ===== -->

    <footer class="pie-pagina">

        <p>
            <?= escapar(
                $restaurante['nombre'] ?? 'MipiWeb'
            ) ?>
            · Creado por equipo de diseño MIPI_SD
        </p>

    </footer>

</body>
</html>
