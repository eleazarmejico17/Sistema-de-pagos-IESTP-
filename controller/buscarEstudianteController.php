<?php
require_once '../config/conexion.php';
header('Content-Type: application/json');

try {

    if (!isset($_POST['search'])) {
        throw new Exception("Sin búsqueda");
    }

    $search = trim($_POST['search']);
    if (strlen($search) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    $sql = "SELECT 
                dni_est,
                nom_est,
                ap_est,
                am_est,
                cel_est,
                mailp_est
            FROM estudiantes
            WHERE 
                dni_est LIKE :busqueda OR
                nom_est LIKE :busqueda OR
                ap_est LIKE :busqueda OR
                am_est LIKE :busqueda
            LIMIT 10";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':busqueda' => "%$search%"
    ]);

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $resultados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
