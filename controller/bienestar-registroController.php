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
        return $this->resolucionModel->crear($data, $files);
    }

    public function listarBeneficiarios() {
        return $this->beneficiarioModel->listar();
    }

    public function listarResoluciones() {
        return $this->resolucionModel->listar();
    }
}

