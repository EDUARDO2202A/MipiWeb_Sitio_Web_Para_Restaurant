<?php
// modelos/Conexion.php

class Conexion {
    private $host = "localhost";
    private $db_name = "mipiweb";
    private $username = "root";
    private $password = ""; // Si en tu XAMPP le pusiste contraseña a root, escríbela aquí
    private $conn;

    public function conectar() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Muestra el mensaje detallado exacto del error de MySQL
            die("<b style='color:red;'>Error PDO: </b>" . $e->getMessage());
        }

        return $this->conn;
    }
}