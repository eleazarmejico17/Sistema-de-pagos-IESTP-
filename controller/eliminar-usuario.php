<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/NotificacionHelper.php';

// Validar que el usuario esté autenticado como admin
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../public/login.html');
    exit;
}

// Solo procesar si es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/dashboard-bienestar.php?pagina=reportes-bienestar-estudiantil');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
$confirmar = filter_input(INPUT_POST, 'confirmar', FILTER_SANITIZE_NUMBER_INT);

if (!$id || !$confirmar) {
    $_SESSION['admin_errors'] = ['ID de usuario no válido'];
    header('Location: ../views/dashboard-bienestar.php?pagina=reportes-bienestar-estudiantil&status=error');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Obtener información del usuario antes de eliminarlo para la notificación
    $stmt = $pdo->prepare("SELECT usuario, tipo FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $usuarioEliminado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuarioEliminado) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Eliminar el usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
    
    if ($stmt->execute([':id' => $id])) {
        // Crear notificación
        $tiposUsuario = [
            1 => 'Empleado',
            2 => 'Estudiante',
            3 => 'Empresa'
        ];
        
        NotificacionHelper::crear('eliminar', 'usuario', [
            'nombre' => $usuarioEliminado['usuario'],
            'tipo' => $tiposUsuario[$usuarioEliminado['tipo']] ?? 'Usuario'
        ]);
        
        header('Location: ../views/dashboard-bienestar.php?pagina=reportes-bienestar-estudiantil&status=usuario_eliminado');
    } else {
        throw new Exception('No se pudo eliminar el usuario');
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar usuario: " . $e->getMessage());
    $_SESSION['admin_errors'] = [$e->getMessage()];
    header('Location: ../views/dashboard-bienestar.php?pagina=reportes-bienestar-estudiantil&status=error');
}
exit;
