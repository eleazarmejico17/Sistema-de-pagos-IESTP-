<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/conexion.php';

class GuardarSolicitudController {

    private $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    public function guardarSolicitud($datos) {

        try {

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $sessionUser = (string)($_SESSION['usuario'] ?? '');
            if ($sessionUser === '' || !preg_match('/^(\d{8})@institutocajas\.edu\.pe$/', $sessionUser, $m)) {
                throw new Exception('Sesión inválida');
            }

            $dniSesion = $m[1];

            if (
                empty($datos['telefono']) ||
                empty($datos['tipo']) || // id de resolución seleccionada
                empty($datos['descripcion'])
            ) {
                throw new Exception("Campos incompletos");
            }

            $telefono = preg_replace('/\D/', '', (string)$datos['telefono']);
            if (!preg_match('/^9\d{8}$/', $telefono)) {
                throw new Exception('Teléfono inválido');
            }

            $descripcion = trim((string)$datos['descripcion']);
            if (mb_strlen($descripcion) < 10) {
                throw new Exception('Descripción muy corta');
            }

            $resolucionId = (int)$datos['tipo'];
            if ($resolucionId <= 0) {
                throw new Exception('Resolución inválida');
            }

            // Buscar ID de estudiante por DNI
            $stmtEst = $this->db->prepare("SELECT id FROM estudiante WHERE dni_est = :dni LIMIT 1");
            $stmtEst->execute([':dni' => $dniSesion]);
            $est = $stmtEst->fetch(PDO::FETCH_ASSOC);

            if (!$est) {
                throw new Exception("No se encontró un estudiante con ese DNI");
            }

            $estudianteId = (int)$est['id'];

            // Validar que la resolución exista y esté vigente/activa
            $stmtRes = $this->db->prepare("SELECT id
                FROM resoluciones
                WHERE id = :id
                  AND estado = true
                  AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
                  AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                LIMIT 1");
            $stmtRes->execute([':id' => $resolucionId]);
            if (!$stmtRes->fetchColumn()) {
                throw new Exception('La resolución seleccionada no está disponible');
            }

            // Archivos de evidencia (se guardan en columna foto)
            $archivos = $this->subirArchivos();

            // Insertar en tabla 'solicitudes' (plural), acorde a tu script SQL
            $sql = "INSERT INTO solicitudes 
                    (estudiante, resoluciones, tipo_solicitud, descripcion, estado, fecha_solicitud, observaciones, foto)
                    VALUES (:estudiante, :resoluciones, :tipo_solicitud, :descripcion, 'pendiente', :fecha_solicitud, NULL, :foto)";

            $params = [
                ':estudiante'      => $estudianteId,
                ':resoluciones'    => $resolucionId, // id de la resolución seleccionada
                ':tipo_solicitud'  => 'Descuento',         // etiqueta general, puedes cambiarla luego
                ':descripcion'     => $descripcion,
                ':fecha_solicitud' => date('Y-m-d H:i:s'),
                ':foto'            => $archivos
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success'=>true,'message'=>'✅ Solicitud registrada correctamente'];

        } catch(Exception $e){
            return ['success'=>false,'error'=>$e->getMessage()];
        }
    }

    private function subirArchivos(){

        if (empty($_FILES['archivo']['name'][0])) return '';

        if (!isset($_FILES['archivo']['name']) || !is_array($_FILES['archivo']['name'])) {
            return '';
        }

        if (count($_FILES['archivo']['name']) > 5) {
            throw new Exception('Máximo 5 archivos');
        }

        $carpeta = "../uploads/solicitudes/";
        if (!file_exists($carpeta)) mkdir($carpeta,0777,true);

        $lista = [];

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        foreach ($_FILES['archivo']['name'] as $i=>$nombre){

            if ($_FILES['archivo']['error'][$i]==0){

                $size = (int)($_FILES['archivo']['size'][$i] ?? 0);
                if ($size > 5 * 1024 * 1024) {
                    throw new Exception('Un archivo excede 5MB');
                }

                $type = (string)($_FILES['archivo']['type'][$i] ?? '');
                if (!in_array($type, $allowedTypes, true)) {
                    throw new Exception('Tipo de archivo no permitido');
                }

                $nuevo = uniqid()."_".basename($nombre);
                $destino = $carpeta.$nuevo;

                if(move_uploaded_file($_FILES['archivo']['tmp_name'][$i],$destino)){
                    $lista[] = $nuevo;
                }
            }
        }

        return implode(",",$lista);
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST'){
    header('Content-Type: application/json');
    $ctrl = new GuardarSolicitudController();
    echo json_encode($ctrl->guardarSolicitud($_POST));
    exit;
}

echo json_encode(['success'=>false,'error'=>'Método no permitido']);
