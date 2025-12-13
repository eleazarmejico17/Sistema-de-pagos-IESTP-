<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexion.php';

try {
    $db = Conexion::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id, numero_resolucion, titulo FROM resoluciones WHERE estado = true ORDER BY creado_en DESC");
    $stmt->execute();
    $resoluciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $resoluciones,
    ]);
} catch (Throwable $e) {
    error_log('Error al listar resoluciones publicas: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'No se pudieron obtener las resoluciones',
    ]);
}
