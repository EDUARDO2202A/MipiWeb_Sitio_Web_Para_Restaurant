<?php
declare(strict_types=1);

class Conexion {
    public function conectar(): PDO {
        $localFile = __DIR__ . '/../config/local.php';
        $local = is_file($localFile) ? require $localFile : [];
        if (!is_array($local)) throw new RuntimeException('Configuración local no válida');
        $get = static function(string $key) use ($local): string {
            $value = getenv($key);
            return ($value !== false && $value !== '') ? $value : (string)($local[$key] ?? '');
        };
        $host = $get('DB_HOST');
        $port = $get('DB_PORT') ?: '6543';
        $database = $get('DB_NAME') ?: 'postgres';
        $username = $get('DB_USER');
        $password = $get('DB_PASSWORD');
        if ($host === '' || $username === '' || $password === '') {
            throw new RuntimeException('Falta configuración de base de datos; revisa config/local.php');
        }
        try {
            $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode=require";
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true
            ]);
        } catch (PDOException $e) {
            error_log('Error conectando a PostgreSQL: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.', 0, $e);
        }
    }
}
