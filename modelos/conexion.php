<?php
// modelos/conexion.php

class Conexion {
    private $host = "aws-0-us-east-2.pooler.supabase.com"; 
    private $port = "6543";
    private $db_name = "postgres";
    private $username = "postgres.uktecacavnheesznaqnz";
    private $password = "Contraseña12.";
    private $conn;

    public function conectar() {
        $this->conn = null;

        try {
            // Agregamos sslmode=require
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";sslmode=require";
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true // <-- Cambiado a TRUE para el puerto 6543 de Supabase
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión a Supabase: " . $e->getMessage());
        }

        return $this->conn;
    }
}