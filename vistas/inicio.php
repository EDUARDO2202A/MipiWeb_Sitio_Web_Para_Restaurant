<?php

require_once "../modelos/conexion.php";

$conexion = new Conexion();
$db= $conexion -> conectar();

$stmtRest = $db->prepare("SELECT * FROM restaurante LIMIT 1");
$stmtRest->execute();
$restaurante = $stmtRest->fetch();


$sqlProductos = "SELECT p.*, c.nombre AS categoria 
                 FROM productos p 
                 INNER JOIN categorias c ON p.id_categoria = c.id_categoria 
                 WHERE p.disponible = TRUE";
$stmtProd = $db->prepare($sqlProductos);
$stmtProd->execute();
$platillos = $stmtProd->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($restaurante['nombre'] ?? 'MipiWeb'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
  <!-- SECCIÓN PORTADA (HERO) -->
  <section id="inicio" class="portada">
    <div class="contenedor portada-grid">
      
      <!-- Columna 1: Textos y Botones -->
      <div class="portada-info">
        <h1><?= htmlspecialchars($restaurante['nombre'] ?? 'Nuestro Restaurante'); ?></h1>
        <p><?= htmlspecialchars($restaurante['descripcion'] ?? 'Bienvenido a nuestro rincón gastronómico.'); ?></p>
        
        <div class="portada-botones">
          <a href="#reservar" class="btn btn-primario">Reservar Mesa</a>
          <a href="#domicilio" class="btn btn-secundario">Pedir a Domicilio</a>
        </div>
      </div>

      <!-- Columna 2: Imagen del Restaurante -->
      <div class="portada-imagen">
        <img src="<?= htmlspecialchars($restaurante['logo'] ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1000&auto=format&fit=crop'); ?>" 
             alt="Instalaciones del restaurante">
      </div>

    </div>
  </section>

 

</body>
</html>