<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$dni = filter_input(INPUT_GET, 'dni', FILTER_SANITIZE_STRING);

if (empty($dni) || strlen($dni) !== 8 || !ctype_digit($dni)) {
    echo json_encode(['success' => false, 'message' => 'DNI inválido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = $db->prepare("
        SELECT 
            e.id,
            e.dni_est,
            CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre_completo,
            e.cel_est AS telefono
        FROM estudiante e
        WHERE e.dni_est = :dni
        LIMIT 1
    ");
    
    $sql->execute([':dni' => $dni]);
    $estudiante = $sql->fetch(PDO::FETCH_ASSOC);
    
    if ($estudiante) {
        echo json_encode([
            'success' => true,
            'estudiante' => [
                'id' => $estudiante['id'],
                'dni' => $estudiante['dni_est'],
                'nombre_completo' => trim($estudiante['nombre_completo']),
                'telefono' => $estudiante['telefono'] ?? ''
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró un estudiante con ese DNI'
        ]);
    }
} catch (Throwable $e) {
    error_log('Error al buscar estudiante: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar el estudiante'
    ]);
}

