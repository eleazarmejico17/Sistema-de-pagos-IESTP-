<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/conexion.php';

try {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception("JSON inválido");
    }

    $id     = $data['id'] ?? null;
    $estado = $data['estado'] ?? null;
    $motivo = $data['motivo'] ?? '';

    if (!$id || !$estado) {
        throw new Exception("ID y estado son obligatorios");
    }

    // ✅ CONEXIÓN CORRECTA
    $db = Conexion::getInstance()->getConnection();

    // VALIDAMOS ID en tabla 'resoluciones' (nuevo sistema)
    $check = $db->prepare("SELECT id, creado_por FROM resoluciones WHERE id = :id");
    $check->execute([':id' => $id]);
    if ($check->rowCount() === 0) {
        throw new Exception("Resolución no encontrada");
    }
    $resolucionData = $check->fetch(PDO::FETCH_ASSOC);

    // BUSCAR EMPLEADO SEGÚN SESIÓN
    $empleadoId = null;
    if (isset($_SESSION['usuario']['correo'])) {
        $stmtEmp = $db->prepare("
            SELECT id FROM empleado 
            WHERE (maili_emp = :correo OR mailp_emp = :correo) 
              AND estado = 1
            LIMIT 1
        ");
        $stmtEmp->execute([':correo' => $_SESSION['usuario']['correo']]);
        $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
        if ($emp) {
            $empleadoId = (int)$emp['id'];
        }
    }

    // CONSTRUIR CAMPOS para tabla 'solicitudes'
    $set = [
        'estado = :estado',
        'observaciones = :motivo',
        'fecha_revision = NOW()'
    ];

    $params = [
        ':estado' => $estado,
        ':motivo' => $motivo,
        ':id'     => $id
    ];

    if ($empleadoId !== null) {
        $set[] = 'empleado = :empleado_id';
        $params[':empleado_id'] = $empleadoId;
    }

    // ACTUALIZAR en tabla 'resoluciones' (nuevo sistema)
    if ($estado === 'Aprobado' || $estado === 'aprobado' || $estado === 'APROBADO') {
        $sql = "UPDATE resoluciones SET estado = true WHERE id = :id";
    } else {
        $sql = "UPDATE resoluciones SET estado = false WHERE id = :id";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);

    
    // INSERTAR HISTORIAL SI EXISTE EMPLEADO
    if ($empleadoId !== null) {
        $historial = $db->prepare("
            INSERT INTO historial_solicitudes 
            (solicitud_id, estado, empleado_id, comentarios)
            VALUES (:sid, :estado, :emp, :coment)
        ");
        $historial->execute([
            ':sid'   => $id,
            ':estado'=> $estado,
            ':emp'   => $empleadoId,
            ':coment'=> $motivo
        ]);
    }

    // CREAR NOTIFICACIÓN PARA BIENESTAR CON DATOS DE RESOLUCIÓN
    try {
        // Obtener información completa de la resolución para la notificación
        $stmtNotif = $db->prepare("
            SELECT r.id, r.numero_resolucion, r.titulo, r.texto_respaldo, 
                   r.monto_descuento, r.fecha_inicio, r.fecha_fin, r.creado_en,
                   r.ruta_documento, r.creado_por,
                   emp.apnom_emp AS creador_nombre, emp.mailp_emp AS creador_correo
            FROM resoluciones r
            LEFT JOIN empleado emp ON emp.id = r.creado_por
            WHERE r.id = :id
        ");
        $stmtNotif->execute([':id' => $id]);
        $notifData = $stmtNotif->fetch(PDO::FETCH_ASSOC);
        
        if ($notifData) {
            // Construir mensaje con datos específicos de la resolución
            $mensajeNotificacion = "Resolución N° " . ($notifData['numero_resolucion'] ?: 'N/A') . "\n";
            $mensajeNotificacion .= "Título: " . ($notifData['titulo'] ?: 'Sin título') . "\n";
            $mensajeNotificacion .= "Creador: " . ($notifData['creador_nombre'] ?: 'No especificado') . "\n";
            
            if ($notifData['monto_descuento'] && $notifData['monto_descuento'] > 0) {
                $mensajeNotificacion .= "Descuento: S/ " . number_format($notifData['monto_descuento'], 2) . "\n";
            }
            
            if ($notifData['fecha_inicio'] && $notifData['fecha_fin']) {
                $mensajeNotificacion .= "Vigencia: " . date('d/m/Y', strtotime($notifData['fecha_inicio'])) . 
                                       " al " . date('d/m/Y', strtotime($notifData['fecha_fin'])) . "\n";
            }
            
            if ($estado === 'Rechazado' && $motivo) {
                $mensajeNotificacion .= "Motivo de rechazo: " . $motivo;
            } else {
                $mensajeNotificacion .= "Estado: " . $estado . " por Dirección";
            }
            
            // Insertar en historial_solicitudes con información detallada
            $historialNotif = $db->prepare("
                INSERT INTO historial_solicitudes 
                (solicitud_id, estado, empleado_id, comentarios, fecha_registro)
                VALUES (:sid, :estado, :emp, :coment, NOW())
            ");
            $historialNotif->execute([
                ':sid'   => $id,
                ':estado'=> $estado,
                ':emp'   => $empleadoId,
                ':coment'=> $mensajeNotificacion
            ]);
            
            // Crear notificación específica para bienestar
            $notifBienestar = $db->prepare("
                INSERT INTO notificaciones_bienestar 
                (tipo, titulo, mensaje, id_resolucion, id_empleado_creador, estado_notificacion, creado_en)
                VALUES (:tipo, :titulo, :mensaje, :id_res, :id_emp, 'no_leida', NOW())
            ");
            
            $tipoNotif = ($estado === 'Aprobado' || $estado === 'aprobado' || $estado === 'APROBADO') ? 'aprobacion' : 'rechazo';
            $tituloNotif = "Resolución " . (($estado === 'Aprobado' || $estado === 'aprobado' || $estado === 'APROBADO') ? 'Aprobada' : 'Rechazada');
            $mensajeCorto = "La resolución N° " . ($notifData['numero_resolucion'] ?: 'N/A') . 
                           " ha sido " . (($estado === 'Aprobado' || $estado === 'aprobado' || $estado === 'APROBADO') ? 'aprobada' : 'rechazada') . 
                           " por Dirección.";
            
            // DEBUG: Verificar datos antes de insertar
            error_log("=== DEBUG CREANDO NOTIFICACIÓN ===");
            error_log("Tipo: " . $tipoNotif);
            error_log("Título: " . $tituloNotif);
            error_log("Mensaje: " . $mensajeCorto);
            error_log("ID Resolución: " . $id);
            error_log("ID Empleado Creador: " . $notifData['creado_por']);
            
            $result = $notifBienestar->execute([
                ':tipo' => $tipoNotif,
                ':titulo' => $tituloNotif,
                ':mensaje' => $mensajeCorto,
                ':id_res' => $id,
                ':id_emp' => $notifData['creado_por']
            ]);
            
            error_log("Resultado de inserción: " . ($result ? 'EXITOSO' : 'FALLIDO'));
            error_log("ID de última inserción: " . $db->lastInsertId());
        }
    } catch (Exception $e) {
        error_log("Error creando notificación: " . $e->getMessage());
        // No fallar el proceso principal si hay error en la notificación
    }

    echo json_encode([
        "success" => true,
        "message" => "Solicitud actualizada correctamente"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
