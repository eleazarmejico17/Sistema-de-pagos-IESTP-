<?php
require_once '../config/conexion.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["mensaje"=>"Datos inválidos"]);
    exit;
}

$id = $data['id'];
$accion = $data['accion'];

try {
    $db = Database::getInstance()->getConnection();

    // SOLO simulamos acción (no hay columna estado)
    $sql = "UPDATE solicitud SET fecha_registro = fecha_registro WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute(['id'=>$id]);

    echo json_encode(["mensaje"=>"Solicitud $accion correctamente ✅"]);

} catch (Exception $e) {
    echo json_encode(["mensaje"=>"Error: ".$e->getMessage()]);
}
