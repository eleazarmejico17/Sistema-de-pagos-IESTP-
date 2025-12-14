<?php
require_once __DIR__ . '/../config/conexion.php';

class TipoPago {

    private $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    public function getAll() {
        $sql = $this->db->prepare("
            SELECT id, nombre, descripcion
            FROM tipo_pago
            ORDER BY id DESC
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = $this->db->prepare("
            INSERT INTO tipo_pago (nombre, descripcion)
            VALUES (:nombre, :descripcion)
        ");

        return $sql->execute([
            ':nombre'      => $data['nombre'],
            ':descripcion' => $data['descripcion']
        ]);
    }

    public function delete($id) {
        $sql = $this->db->prepare("DELETE FROM tipo_pago WHERE id = :id");
        return $sql->execute([':id' => $id]);
    }
}
