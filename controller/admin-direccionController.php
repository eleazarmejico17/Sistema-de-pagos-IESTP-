<?php
require_once __DIR__ . '/../config/conexion.php';

class DireccionController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function listar()
    {
        $stmt = $this->pdo->query("SELECT * FROM usuario ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function crear(array $data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO usuario (usuario, password, correo, rol) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['usuario'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['correo'],
            $data['rol']
        ]);
    }

    public function eliminar(int $id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>