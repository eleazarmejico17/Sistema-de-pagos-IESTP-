<?php
require_once __DIR__ . '/../config/conexion.php';

class ResolucionModel {
    private $db;
    private $uploadDir;

    public function __construct() {
        // ✅ CORREGIDO
        $this->db = Conexion::getInstance()->getConnection();
        $this->uploadDir = dirname(__DIR__) . '/uploads/resoluciones';
    }

    public function crear($data, $files = []) {
        $rutaDocumento = null;

        if (!empty($files['documento']['name']) && $files['documento']['error'] === UPLOAD_ERR_OK) {

            $this->ensureUploadDir();

            $ext = strtolower(pathinfo($files['documento']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx'];

            if (in_array($ext, $allowed, true)) {

                $nombreArchivo = uniqid("res_", true) . "." . $ext;
                $destino = $this->uploadDir . DIRECTORY_SEPARATOR . $nombreArchivo;

                if (move_uploaded_file($files['documento']['tmp_name'], $destino)) {
                    $rutaDocumento = 'uploads/resoluciones/' . $nombreArchivo;
                }
            }
        }

        $creadoPor = $data['creado_por'] ?? null;

        $campos = [
            'numero_resolucion',
            'titulo',
            'texto_respaldo',
            'monto_descuento',
            'ruta_documento',
            'fecha_inicio',
            'fecha_fin'
        ];

        $valores = [
            ':numero_resolucion',
            ':titulo',
            ':texto_respaldo',
            ':monto_descuento',
            ':ruta_documento',
            ':fecha_inicio',
            ':fecha_fin'
        ];

        $params = [
            ':numero_resolucion' => $data['numero_resolucion'],
            ':titulo' => $data['titulo'],
            ':texto_respaldo' => $data['texto_respaldo'] ?? null,
            ':monto_descuento' => isset($data['monto_descuento']) && $data['monto_descuento'] !== ''
                ? (float)$data['monto_descuento']
                : null,
            ':ruta_documento' => $rutaDocumento,
            ':fecha_inicio' => $data['fecha_inicio'] ?? null,
            ':fecha_fin' => $data['fecha_fin'] ?? null
        ];

        if ($creadoPor !== null && $creadoPor > 0) {
            $campos[] = 'creado_por';
            $valores[] = ':creado_por';
            $params[':creado_por'] = $creadoPor;
        }

        $sql = $this->db->prepare("
            INSERT INTO resoluciones (" . implode(', ', $campos) . ")
            VALUES (" . implode(', ', $valores) . ")
        ");

        return $sql->execute($params);
    }

    public function listar() {
        $sql = $this->db->prepare("
            SELECT 
                r.id,
                r.numero_resolucion,
                r.titulo,
                r.texto_respaldo,
                r.ruta_documento,
                r.fecha_inicio,
                r.fecha_fin,
                r.creado_en,
                e.apnom_emp AS creado_por_nombre
            FROM resoluciones r
            LEFT JOIN empleado e ON e.id = r.creado_por
            WHERE r.estado = true
            ORDER BY r.creado_en DESC
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $sql = $this->db->prepare("SELECT * FROM resoluciones WHERE id = :id");
        $sql->execute([':id' => $id]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    private function ensureUploadDir(): void {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }
}
