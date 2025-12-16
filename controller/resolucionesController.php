<?php
header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/../models/bienestar-resolucionesModel.php';

try {
    if (!isset($_SESSION['rol'])) {
        throw new Exception('No autenticado');
    }

    $rol = (string)$_SESSION['rol'];
    if (!in_array($rol, ['admin', 'direccion'], true)) {
        throw new Exception('No autorizado');
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        throw new Exception('JSON inválido');
    }

    $accion = (string)($payload['accion'] ?? '');
    $id = isset($payload['id']) ? (int)$payload['id'] : 0;

    $model = new ResolucionModel();

    if ($accion === 'actualizar_campos') {
        if ($id <= 0) {
            throw new Exception('ID inválido');
        }

        $monto = array_key_exists('monto_descuento', $payload) ? $payload['monto_descuento'] : null;
        $fechaFin = array_key_exists('fecha_fin', $payload) ? (string)$payload['fecha_fin'] : '';

        if ($monto !== null && $monto !== '') {
            if (!is_numeric($monto)) {
                throw new Exception('Monto inválido');
            }
            $monto = (float)$monto;
            if ($monto < 0) {
                throw new Exception('Monto inválido');
            }
        } else {
            $monto = null;
        }

        if ($fechaFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
            throw new Exception('Fecha fin inválida');
        }

        $ok = $model->actualizarCampos($id, [
            'monto_descuento' => $monto,
            'fecha_fin' => $fechaFin,
        ]);

        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    if ($accion === 'cambiar_estado') {
        if ($id <= 0) {
            throw new Exception('ID inválido');
        }
        $estado = (bool)($payload['estado'] ?? false);
        $ok = $model->cambiarEstado($id, $estado);
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    if ($accion === 'eliminar') {
        if ($id <= 0) {
            throw new Exception('ID inválido');
        }
        $force = (bool)($payload['force'] ?? false);
        $resActual = $model->obtenerPorId($id);
        if (!$resActual) {
            throw new Exception('Resolución no encontrada');
        }
        $estadoActual = (bool)($resActual['estado'] ?? false);
        if ($estadoActual === true && $force !== true) {
            throw new Exception('No se puede eliminar una resolución activa sin confirmación');
        }
        $ok = $model->eliminar($id);
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    throw new Exception('Acción no soportada');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
