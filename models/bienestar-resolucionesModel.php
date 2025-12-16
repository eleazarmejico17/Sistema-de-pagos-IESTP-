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
            'tipo_pago',
            'monto_descuento',
            'ruta_documento',
            'fecha_inicio',
            'fecha_fin'
        ];

        $valores = [
            ':numero_resolucion',
            ':titulo',
            ':texto_respaldo',
            ':tipo_pago',
            ':monto_descuento',
            ':ruta_documento',
            ':fecha_inicio',
            ':fecha_fin'
        ];

        $params = [
            ':numero_resolucion' => $data['numero_resolucion'],
            ':titulo' => $data['titulo'],
            ':texto_respaldo' => $data['texto_respaldo'] ?? null,
            ':tipo_pago' => isset($data['tipo_pago']) && $data['tipo_pago'] !== '' ? (int)$data['tipo_pago'] : null,
            ':monto_descuento' => isset($data['monto_descuento']) && $data['monto_descuento'] !== ''
                ? (float)$data['monto_descuento']
                : null,
            ':ruta_documento' => $rutaDocumento,
            ':fecha_inicio' => $data['fecha_inicio'] ?? null,
            ':fecha_fin' => $data['fecha_fin'] ?? null
        ];

        $campos[] = 'estado';
        $valores[] = ':estado';
        $params[':estado'] = isset($data['estado']) ? (bool)$data['estado'] : false;

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

    public function listarTodas() {
        $sql = $this->db->prepare("
            SELECT 
                r.id,
                r.numero_resolucion,
                r.titulo,
                r.texto_respaldo,
                r.monto_descuento,
                r.tipo_pago,
                r.ruta_documento,
                r.fecha_inicio,
                r.fecha_fin,
                r.creado_en,
                r.estado,
                e.apnom_emp AS creado_por_nombre
            FROM resoluciones r
            LEFT JOIN empleado e ON e.id = r.creado_por
            ORDER BY r.creado_en DESC
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPendientes() {
        $sql = $this->db->prepare("
            SELECT 
                r.id,
                r.numero_resolucion,
                r.titulo,
                r.texto_respaldo,
                r.monto_descuento,
                r.tipo_pago,
                r.ruta_documento,
                r.fecha_inicio,
                r.fecha_fin,
                r.creado_en,
                r.estado,
                e.apnom_emp AS creado_por_nombre
            FROM resoluciones r
            LEFT JOIN empleado e ON e.id = r.creado_por
            WHERE r.estado = false
            ORDER BY r.creado_en DESC
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarCampos($id, $data) {
        $set = [];
        $params = [':id' => (int)$id];

        if (array_key_exists('monto_descuento', $data)) {
            $set[] = 'monto_descuento = :monto_descuento';
            $params[':monto_descuento'] = $data['monto_descuento'] !== null ? (float)$data['monto_descuento'] : null;
        }

        if (array_key_exists('fecha_fin', $data)) {
            $set[] = 'fecha_fin = :fecha_fin';
            $params[':fecha_fin'] = $data['fecha_fin'] !== '' ? $data['fecha_fin'] : null;
        }

        if (empty($set)) {
            return false;
        }

        $sql = $this->db->prepare('UPDATE resoluciones SET ' . implode(', ', $set) . ' WHERE id = :id');
        return $sql->execute($params);
    }

    public function cambiarEstado($id, $estado) {
        $sql = $this->db->prepare('UPDATE resoluciones SET estado = :estado WHERE id = :id');
        return $sql->execute([
            ':estado' => (bool)$estado,
            ':id' => (int)$id
        ]);
    }

    public function eliminar($id) {
        $sql = $this->db->prepare('DELETE FROM resoluciones WHERE id = :id');
        return $sql->execute([':id' => (int)$id]);
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
