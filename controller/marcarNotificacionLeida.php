<?php
header('Content-Type: application/json');

try {
    session_start();
    require_once __DIR__ . '/../config/conexion.php';

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        throw new Exception("JSON inválido");
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $usuarioId = $_SESSION['usuario']['id'] ?? null;
    
    if (!$usuarioId) {
        throw new Exception("Usuario no autenticado");
    }

    // Si se solicita marcar todas como leídas
    if (isset($data['todas']) && $data['todas'] === true) {
        $sql = "UPDATE notificaciones 
                SET leido = 1 
                WHERE usuario_id = :usuario_id AND leido = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        
        echo json_encode([
            "success" => true,
            "message" => "Todas las notificaciones marcadas como leídas"
        ]);
    } else {
        // Marcar una notificación específica como leída
        $notificacionId = $data['notificacion_id'] ?? null;
        
        if (!$notificacionId) {
            throw new Exception("ID de notificación requerido");
        }

        $sql = "UPDATE notificaciones 
                SET leido = 1 
                WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $notificacionId,
            ':usuario_id' => $usuarioId
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Notificación marcada como leída"
        ]);
    }

} catch (PDOException $e) {
    error_log('Error PDO en marcarNotificacionLeida: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error de base de datos. Por favor, contacta al administrador."
    ]);
} catch (Exception $e) {
    error_log('Error en marcarNotificacionLeida: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

