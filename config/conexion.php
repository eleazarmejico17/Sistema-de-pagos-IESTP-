<?php
/**
 * Archivo de conexión a base de datos
 * Soporta conexión local y de producción con detección automática
 */

class Database {
    private static $instance = null;
    private $con = null;

    private function __construct() {
        // Detectar si estamos en entorno local o producción
        $isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || 
                   strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                   strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

        if ($isLocal) {
            // Configuración para desarrollo local
            $dbHost = getenv('DB_HOST') ?: 'localhost';
            $dbName = getenv('DB_NAME') ?: 'db_sistema';
            $dbUser = getenv('DB_USER') ?: 'root';
            $dbPass = getenv('DB_PASS') ?: '';
        } else {
            // Configuración para producción
            $dbHost = getenv('DB_HOST') ?: '50.31.174.34';
            $dbName = getenv('DB_NAME') ?: 'wxwdrnht_integrado_db';
            $dbUser = getenv('DB_USER') ?: 'wxwdrnht_wxwdrnht_integrado_db';
            $dbPass = getenv('DB_PASS') ?: 'integrado_db2025.';
        }

        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

        try {
            $this->con = new PDO($dsn, $dbUser, $dbPass);
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (Throwable $e) {
            error_log("❌ Database Connection Error: " . $e->getMessage());
            
            // En producción, mostrar mensaje genérico; en desarrollo, mostrar error completo
            if ($isLocal) {
                die("Error de conexión: " . $e->getMessage());
            } else {
                die("Error de conexión. Inténtelo más tarde.");
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->con === null) {
            throw new Exception("No hay conexión disponible.");
        }
        return $this->con;
    }

    public static function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public function executeQuery($sql, $params = []) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Throwable $e) {
            error_log("⚠ SQL Query Error: " . $e->getMessage());
            throw new Exception("Error al ejecutar la consulta.");
        }
    }
    
    public function testConnection() {
        try {
            $this->getConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Clase Conexion - Alias para compatibilidad con código existente
 * Usa la misma instancia que Database para mantener una sola conexión
 */
class Conexion {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Reutilizar la conexión de Database
        $this->pdo = Database::getInstance()->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Conexion();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}
?>
