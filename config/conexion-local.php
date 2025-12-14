<?php
class Conexion {
    private static $instance = null;
    private $pdo;

    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db   = "db_sistema"; // ← cámbialo por tu base de datos

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->pass
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Devuelve siempre la misma instancia compartida
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Conexion();
        }
        return self::$instance;
    }

    // Retorna el objeto PDO
    public function getConnection() {
        return $this->pdo;
    }
}
