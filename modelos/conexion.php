<?php
// modelos/Conexion.php

class Conexion {
    private $host = "aws-0-us-east-2.pooler.supabase.com"; // Ej: aws-0-us-east-1.pooler.supabase.com
    private $port = "6543";
    private $db_name = "postgres";
    private $username = "postgres.uktecacavnheesznaqnz";      // El usuario que te da el Pooler
    private $password = "Contraseña12.";       // La contraseña que creaste al hacer el proyecto
    private $conn;

    public function conectar() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión a Supabase: " . $e->getMessage());
        }

        return $this->conn;
    }
}