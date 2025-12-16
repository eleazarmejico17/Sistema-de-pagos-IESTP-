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

    $numero = trim((string)($payload['numero_resolucion'] ?? ''));
    $titulo = trim((string)($payload['titulo'] ?? ''));
    $texto = trim((string)($payload['texto_respaldo'] ?? ''));
    $tipoPago = isset($payload['tipo_pago']) && $payload['tipo_pago'] !== '' ? (int)$payload['tipo_pago'] : null;

    $monto = $payload['monto_descuento'] ?? null;
    if ($monto === '') {
        $monto = null;
    }
    if ($monto !== null) {
        if (!is_numeric($monto)) {
            throw new Exception('Monto inválido');
        }
        $monto = (float)$monto;
        if ($monto < 0) {
            throw new Exception('Monto inválido');
        }
    }

    $fechaFin = (string)($payload['fecha_fin'] ?? '');
    if ($fechaFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        throw new Exception('Fecha fin inválida');
    }

    $estado = (bool)($payload['estado'] ?? false);

    if ($numero === '' || $titulo === '' || empty($tipoPago)) {
        throw new Exception('Campos obligatorios incompletos');
    }

    $model = new ResolucionModel();
    $ok = $model->crear([
        'numero_resolucion' => $numero,
        'titulo' => $titulo,
        'texto_respaldo' => $texto,
        'tipo_pago' => $tipoPago,
        'monto_descuento' => $monto,
        'fecha_inicio' => null,
        'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
        'estado' => $estado,
    ], []);

    echo json_encode(['success' => (bool)$ok]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
