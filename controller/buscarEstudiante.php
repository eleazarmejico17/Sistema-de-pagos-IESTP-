<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/bienestar-registroController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$dni = filter_input(INPUT_GET, 'dni', FILTER_SANITIZE_STRING);

if (empty($dni) || strlen($dni) !== 8) {
    echo json_encode(['success' => false, 'message' => 'DNI inválido']);
    exit;
}

try {
    $ctrl = new BienestarRegistroController();
    $estudiante = $ctrl->buscarEstudiante($dni);
    
    if ($estudiante) {
        echo json_encode([
            'success' => true,
            'estudiante' => $estudiante
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró un estudiante con ese DNI'
        ]);
    }
} catch (Throwable $e) {
    error_log('Error al buscar estudiante: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar el estudiante: ' . $e->getMessage()
    ]);
}

