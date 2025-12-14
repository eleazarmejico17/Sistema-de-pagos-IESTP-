<?php
require_once __DIR__ . '/../models/admin-tipo-pagoModel.php';

class TipoPagoController {

    public function listar() {
        $tp = new TipoPago();
        return $tp->getAll();
    }

    public function crear($post) {
        $tp = new TipoPago();

        $data = [
            'nombre'      => $post['nombre'],
            'descripcion' => $post['descripcion']
        ];

        return $tp->create($data);
    }

    public function eliminar($id) {
        $tp = new TipoPago();
        return $tp->delete($id);
    }
}


// ======== DESPACHADOR (igual que tus otros módulos) ==========
if (isset($_GET['accion'])) {

    $controller = new TipoPagoController();

    switch ($_GET['accion']) {

        case 'crear':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ok = $controller->crear($_POST);

                header("Location: ../views/includes/admin/admin-tipo-pago.php?msg=" . ($ok ? "ok" : "error"));
                exit();
            }
            break;

        case 'eliminar':
            if (isset($_GET['id'])) {
                $controller->eliminar($_GET['id']);
                header("Location: ../views/includes/admin/admin-tipo-pago.php?msg=deleted");
                exit();
            }
            break;

        default:
            echo "Acción no válida";
            break;
    }
}
