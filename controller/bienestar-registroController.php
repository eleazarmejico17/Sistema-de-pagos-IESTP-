<?php
require_once __DIR__ . '/../models/bienestar-beneficiariosModel.php';
require_once __DIR__ . '/../models/bienestar-resolucionesModel.php';

class BienestarRegistroController {
    private $beneficiarioModel;
    private $resolucionModel;

    public function __construct() {
        $this->beneficiarioModel = new BeneficiarioModel();
        $this->resolucionModel = new ResolucionModel();
    }

    public function buscarEstudiante($dni) {
        return $this->beneficiarioModel->buscarEstudiantePorDNI($dni);
    }

    
    public function crearBeneficiario($data) {
        try {
            return $this->beneficiarioModel->crear($data);
        } catch (Exception $e) {
            error_log("Error en crearBeneficiario: " . $e->getMessage());
            throw $e;
        }
    }

    public function crearResolucion($data, $files = []) {
        // Asociar el creador (empleado) según sesión
        try {
            if (!isset($data['creado_por']) || (int)$data['creado_por'] <= 0) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                $db = Conexion::getInstance()->getConnection();
                $empleadoId = null;

                $usuarioIdSesion = (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0);
                $usuarioSesion = (string)($_SESSION['usuario'] ?? '');

                if ($usuarioIdSesion > 0) {
                    $stmtUser = $db->prepare('SELECT estuempleado FROM usuarios WHERE id = :id LIMIT 1');
                    $stmtUser->execute([':id' => $usuarioIdSesion]);
                    $u = $stmtUser->fetch(PDO::FETCH_ASSOC);
                    if ($u && !empty($u['estuempleado'])) {
                        $empleadoId = (int)$u['estuempleado'];
                    }
                } elseif ($usuarioSesion !== '') {
                    $stmtUser = $db->prepare('SELECT estuempleado FROM usuarios WHERE usuario = :usuario LIMIT 1');
                    $stmtUser->execute([':usuario' => $usuarioSesion]);
                    $u = $stmtUser->fetch(PDO::FETCH_ASSOC);
                    if ($u && !empty($u['estuempleado'])) {
                        $empleadoId = (int)$u['estuempleado'];
                    }
                }

                if ($empleadoId !== null && $empleadoId > 0) {
                    $data['creado_por'] = $empleadoId;
                }
            }
        } catch (Throwable $e) {
            // No impedir creación si no se puede resolver el creador
        }

        return $this->resolucionModel->crear($data, $files);
    }

    public function listarBeneficiarios() {
        return $this->beneficiarioModel->listar();
    }

    public function listarResoluciones() {
        return $this->resolucionModel->listar();
    }
}
