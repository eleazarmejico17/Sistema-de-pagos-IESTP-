<?php
require_once __DIR__ . '/../config/conexion.php';

class Estudiantes {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    // Obtener todos los estudiantes
    public function getAll() {
        $sql = $this->db->prepare("
            SELECT 
                e.id,
                CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre_completo,
                TIMESTAMPDIFF(YEAR, e.fecnac_est, CURDATE()) AS edad,
                e.sex_est AS sexo,
                e.dni_est,
                e.estado
            FROM estudiante e
            ORDER BY e.id DESC
        ");

        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un estudiante por ID
    public function getById($id) {
        $sql = $this->db->prepare("
            SELECT * FROM estudiante WHERE id = :id LIMIT 1
        ");
        $sql->execute([':id' => $id]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    // Crear estudiante
    public function create($data) {
        $sql = $this->db->prepare("
            INSERT INTO estudiante (
                ubdistrito, dni_est, ap_est, am_est, nom_est, sex_est, cel_est,
                ubigeodir_est, ubigeonac_est, dir_est, mailp_est, maili_est,
                fecnac_est, foto_est, estado
            ) VALUES (
                :ubdistrito, :dni_est, :ap_est, :am_est, :nom_est, :sex_est, :cel_est,
                :ubigeodir_est, :ubigeonac_est, :dir_est, :mailp_est, :maili_est,
                :fecnac_est, :foto_est, 1
            )
        ");

        return $sql->execute([
            ':ubdistrito'     => $data['ubdistrito'] ?? null,
            ':dni_est'        => $data['dni_est'],
            ':ap_est'         => $data['ap_est'],
            ':am_est'         => $data['am_est'],
            ':nom_est'        => $data['nom_est'],
            ':sex_est'        => $data['sex_est'],
            ':cel_est'        => $data['cel_est'],
            ':ubigeodir_est'  => $data['ubigeodir_est'] ?? null,
            ':ubigeonac_est'  => $data['ubigeonac_est'] ?? null,
            ':dir_est'        => $data['dir_est'],
            ':mailp_est'      => $data['mailp_est'],
            ':maili_est'      => $data['maili_est'],
            ':fecnac_est'     => $data['fecnac_est'],
            ':foto_est'       => $data['foto_est']
        ]);
    }

    // Actualizar estudiante
    public function update($id, $data) {
        $sql = $this->db->prepare("
            UPDATE estudiante SET
                dni_est = :dni_est,
                ap_est = :ap_est,
                am_est = :am_est,
                nom_est = :nom_est,
                sex_est = :sex_est,
                cel_est = :cel_est,
                dir_est = :dir_est,
                mailp_est = :mailp_est,
                maili_est = :maili_est,
                fecnac_est = :fecnac_est,
                estado = :estado
            WHERE id = :id
        ");

        return $sql->execute([
            ':id' => $id,
            ':dni_est' => $data['dni_est'] ?? null,
            ':ap_est' => $data['ap_est'] ?? null,
            ':am_est' => $data['am_est'] ?? null,
            ':nom_est' => $data['nom_est'] ?? null,
            ':sex_est' => $data['sex_est'] ?? null,
            ':cel_est' => $data['cel_est'] ?? null,
            ':dir_est' => $data['dir_est'] ?? null,
            ':mailp_est' => $data['mailp_est'] ?? null,
            ':maili_est' => $data['maili_est'] ?? null,
            ':fecnac_est' => !empty($data['fecnac_est']) ? $data['fecnac_est'] : null,
            ':estado' => isset($data['estado']) ? (int)$data['estado'] : 1
        ]);
    }

    // Eliminar estudiante
    public function delete($id) {
        $sql = $this->db->prepare("DELETE FROM estudiante WHERE id = :id");
        return $sql->execute([':id' => $id]);
    }
}
