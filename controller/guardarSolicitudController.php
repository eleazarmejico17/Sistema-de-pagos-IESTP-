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

            if (
                empty($datos['dni']) ||
                empty($datos['nombre']) ||
                empty($datos['telefono']) ||
                empty($datos['tipo']) || // id de resolución seleccionada
                empty($datos['fecha']) ||
                empty($datos['descripcion'])
            ) {
                throw new Exception("Campos incompletos");
            }

            // Buscar ID de estudiante por DNI
            $stmtEst = $this->db->prepare("SELECT id FROM estudiante WHERE dni_est = :dni LIMIT 1");
            $stmtEst->execute([':dni' => trim($datos['dni'])]);
            $est = $stmtEst->fetch(PDO::FETCH_ASSOC);

            if (!$est) {
                throw new Exception("No se encontró un estudiante con ese DNI");
            }

            $estudianteId = (int)$est['id'];

            // Archivos de evidencia (se guardan en columna foto)
            $archivos = $this->subirArchivos();

            // Insertar en tabla 'solicitudes' (plural), acorde a tu script SQL
            $sql = "INSERT INTO solicitudes 
                    (estudiante, resoluciones, tipo_solicitud, descripcion, estado, fecha_solicitud, observaciones, foto)
                    VALUES (:estudiante, :resoluciones, :tipo_solicitud, :descripcion, 'pendiente', :fecha_solicitud, NULL, :foto)";

            $params = [
                ':estudiante'      => $estudianteId,
                ':resoluciones'    => (int)$datos['tipo'], // id de la resolución seleccionada
                ':tipo_solicitud'  => 'Descuento',         // etiqueta general, puedes cambiarla luego
                ':descripcion'     => trim($datos['descripcion']),
                ':fecha_solicitud' => $datos['fecha'],
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

        $carpeta = "../uploads/solicitudes/";
        if (!file_exists($carpeta)) mkdir($carpeta,0777,true);

        $lista = [];

        foreach ($_FILES['archivo']['name'] as $i=>$nombre){

            if ($_FILES['archivo']['error'][$i]==0){

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
