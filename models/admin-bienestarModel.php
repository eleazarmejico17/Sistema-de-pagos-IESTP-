<?php
require_once __DIR__ . '/../config/conexion.php';

class Empleado {
    private $db;
    private $uploadDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = dirname(__DIR__) . '/uploads/empleados';
    }

    public function getAll() {
        $sql = $this->db->prepare("
            SELECT
                id,
                dni_emp,
                apnom_emp AS nombre_completo,
                cel_emp,
                mailp_emp,
                cargo_emp,
                foto_emp,
                estado
            FROM empleado
            ORDER BY id DESC
        ");

        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data, $file) {

        // Manejo de foto
        $fotoNombre = null;
        if (!empty($file['foto_emp']['name']) && $file['foto_emp']['error'] === UPLOAD_ERR_OK) {
            $this->ensureUploadDir();

            $ext = strtolower(pathinfo($file['foto_emp']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed, true)) {
                $fotoNombre = uniqid("emp_", true) . "." . $ext;
                $destino = $this->uploadDir . DIRECTORY_SEPARATOR . $fotoNombre;

                if (!move_uploaded_file($file['foto_emp']['tmp_name'], $destino)) {
                    error_log('No se pudo mover la foto del empleado al directorio destino.');
                    $fotoNombre = null;
                }
            }
        }

        $sql = $this->db->prepare("
            INSERT INTO empleado (
                dni_emp, apnom_emp, sex_emp, cel_emp,
                ubigeodir_emp, ubigeonac_emp, dir_emp,
                mailp_emp, maili_emp, fecnac_emp,
                cargo_emp, cond_emp, id_progest,
                fecinc_emp, foto_emp, estado
            ) VALUES (
                :dni_emp, :apnom_emp, :sex_emp, :cel_emp,
                :ubigeodir_emp, :ubigeonac_emp, :dir_emp,
                :mailp_emp, :maili_emp, :fecnac_emp,
                :cargo_emp, :cond_emp, :id_progest,
                :fecinc_emp, :foto_emp, :estado
            )
        ");

        return $sql->execute([
            ':dni_emp'        => $data['dni_emp'],
            ':apnom_emp'      => $data['apnom_emp'],
            ':sex_emp'        => $data['sex_emp'],
            ':cel_emp'        => $data['cel_emp'],
            ':ubigeodir_emp'  => $data['ubigeodir_emp'],
            ':ubigeonac_emp'  => $data['ubigeonac_emp'],
            ':dir_emp'        => $data['dir_emp'],
            ':mailp_emp'      => $data['mailp_emp'],
            ':maili_emp'      => $data['maili_emp'],
            ':fecnac_emp'     => $data['fecnac_emp'],
            ':cargo_emp'      => $data['cargo_emp'],
            ':cond_emp'       => $data['cond_emp'] ?? null,
            ':id_progest'     => $data['id_progest'] ?? null,
            ':fecinc_emp'     => $data['fecinc_emp'] ?? null,
            ':foto_emp'       => $fotoNombre,
            ':estado'         => isset($data['estado']) ? (int) $data['estado'] : 1
        ]);
    }

    public function delete($id) {

        $query = $this->db->prepare("SELECT foto_emp FROM empleado WHERE id = :id");
        $query->execute([':id' => $id]);
        $emp = $query->fetch(PDO::FETCH_ASSOC);

        if ($emp && !empty($emp['foto_emp'])) {
            $ruta = $this->uploadDir . DIRECTORY_SEPARATOR . $emp['foto_emp'];
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }

        $sql = $this->db->prepare("DELETE FROM empleado WHERE id = :id");
        return $sql->execute([':id' => $id]);
    }
    private function ensureUploadDir(): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }
}
