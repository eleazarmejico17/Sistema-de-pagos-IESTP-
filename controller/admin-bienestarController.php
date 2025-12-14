<?php
require_once __DIR__ . '/../models/admin-bienestarModel.php';

class BienestarController {

    public function listar() {
        $emp = new Empleado();
        return $emp->getAll();
    }


    public function crear($post, $files) {
        $emp = new Empleado();
        return $emp->create($post, $files);
    }


    public function eliminar($id) {
        $emp = new Empleado();
        return $emp->delete($id);
    }
}
