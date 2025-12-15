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

    $estadoDb = null;
    $estadoIn = trim((string)$estado);
    if ($estadoIn === 'Aprobado' || $estadoIn === 'aprobado' || $estadoIn === 'APROBADO') {
        $estadoDb = 'aprobado';
    } elseif ($estadoIn === 'Rechazado' || $estadoIn === 'rechazado' || $estadoIn === 'RECHAZADO') {
        $estadoDb = 'rechazado';
    } elseif ($estadoIn === 'En evaluación' || $estadoIn === 'en_evaluacion' || $estadoIn === 'EN_EVALUACION' || $estadoIn === 'evaluacion') {
        $estadoDb = 'en_evaluacion';
    } elseif ($estadoIn === 'Pendiente' || $estadoIn === 'pendiente') {
        $estadoDb = 'pendiente';
    } else {
        throw new Exception('Estado inválido');
    }

    $checkSol = $db->prepare("SELECT id, estudiante, resoluciones FROM solicitudes WHERE id = :id LIMIT 1");
    $checkSol->execute([':id' => $id]);
    $solicitud = $checkSol->fetch(PDO::FETCH_ASSOC);
    if (!$solicitud) {
        throw new Exception('Solicitud no encontrada');
    }

    // BUSCAR EMPLEADO SEGÚN SESIÓN
    $empleadoId = null;
    try {
        $usuarioIdSesion = (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0);
        $usuarioSesion = (string)($_SESSION['usuario'] ?? '');
        if ($usuarioIdSesion > 0) {
            $stmtUser = $db->prepare('SELECT estuempleado FROM usuarios WHERE id = :id LIMIT 1');
            $stmtUser->execute([':id' => $usuarioIdSesion]);
            $u = $stmtUser->fetch(PDO::FETCH_ASSOC);
            if ($u && !empty($u['estuempleado'])) {
                $empleadoId = (int)$u['estuempleado'];
            }
        } elseif ($usuarioSesion !== '') {
            $stmtUser = $db->prepare('SELECT estuempleado FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmtUser->execute([':usuario' => $usuarioSesion]);
            $u = $stmtUser->fetch(PDO::FETCH_ASSOC);
            if ($u && !empty($u['estuempleado'])) {
                $empleadoId = (int)$u['estuempleado'];
            }
        }
    } catch (Throwable $e) {
        $empleadoId = null;
    }

    // CONSTRUIR CAMPOS para tabla 'solicitudes'
    $set = [
        'estado = :estado',
        'observaciones = :motivo',
        'fecha_revision = NOW()'
    ];

    $params = [
        ':estado' => $estadoDb,
        ':motivo' => $motivo,
        ':id'     => $id
    ];

    if ($empleadoId !== null) {
        $set[] = 'empleado = :empleado_id';
        $params[':empleado_id'] = $empleadoId;
    }

    $sql = "UPDATE solicitudes SET " . implode(', ', $set) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    if ($estadoDb === 'aprobado') {
        $estudianteId = (int)($solicitud['estudiante'] ?? 0);
        $resolucionId = (int)($solicitud['resoluciones'] ?? 0);
        if ($estudianteId <= 0 || $resolucionId <= 0) {
            throw new Exception('No se pudo determinar estudiante o resolución de la solicitud');
        }

        $stmtRes = $db->prepare('SELECT fecha_inicio, fecha_fin FROM resoluciones WHERE id = :id LIMIT 1');
        $stmtRes->execute([':id' => $resolucionId]);
        $res = $stmtRes->fetch(PDO::FETCH_ASSOC) ?: [];

        $fechaInicio = !empty($res['fecha_inicio']) ? (string)$res['fecha_inicio'] : date('Y-m-d');
        $fechaFin = !empty($res['fecha_fin']) ? (string)$res['fecha_fin'] : null;

        $stmtBen = $db->prepare('SELECT id FROM beneficiarios WHERE estudiante = :est AND resoluciones = :res LIMIT 1');
        $stmtBen->execute([':est' => $estudianteId, ':res' => $resolucionId]);
        $beneficiarioId = (int)($stmtBen->fetchColumn() ?: 0);

        if ($beneficiarioId > 0) {
            $sqlBen = 'UPDATE beneficiarios SET activo = 1, fecha_inicio = :fi, fecha_fin = :ff WHERE id = :id';
            $stmtUpd = $db->prepare($sqlBen);
            $stmtUpd->execute([
                ':fi' => $fechaInicio,
                ':ff' => $fechaFin,
                ':id' => $beneficiarioId
            ]);
        } else {
            $sqlBen = 'INSERT INTO beneficiarios (estudiante, resoluciones, porcentaje_descuento, fecha_inicio, fecha_fin, activo, registrado_por, registrado_en)
                       VALUES (:est, :res, 0.00, :fi, :ff, 1, :emp, NOW())';
            $stmtIns = $db->prepare($sqlBen);
            $stmtIns->execute([
                ':est' => $estudianteId,
                ':res' => $resolucionId,
                ':fi' => $fechaInicio,
                ':ff' => $fechaFin,
                ':emp' => $empleadoId
            ]);
        }
    }

    
    // INSERTAR HISTORIAL SI EXISTE EMPLEADO
    if ($empleadoId !== null) {
        try {
            $historial = $db->prepare("
                INSERT INTO historial_solicitudes 
                (solicitud_id, estado, fecha, empleado, comentarios)
                VALUES (:sid, :estado, NOW(), :emp, :coment)
            ");
            $historial->execute([
                ':sid'   => $id,
                ':estado'=> $estadoDb,
                ':emp'   => $empleadoId,
                ':coment'=> $motivo
            ]);
        } catch (Throwable $e) {
            $historial = $db->prepare("
                INSERT INTO historial_solicitudes 
                (solicitud_id, estado, empleado_id, comentarios)
                VALUES (:sid, :estado, :emp, :coment)
            ");
            $historial->execute([
                ':sid'   => $id,
                ':estado'=> $estadoDb,
                ':emp'   => $empleadoId,
                ':coment'=> $motivo
            ]);
        }
    }

    // CREAR NOTIFICACIÓN PARA BIENESTAR CON DATOS DE RESOLUCIÓN
    try {
        // Obtener información completa de la resolución para la notificación
        $resolucionIdNotif = (int)($solicitud['resoluciones'] ?? 0);
        if ($resolucionIdNotif > 0) {
            $stmtNotif = $db->prepare("
                SELECT r.id, r.numero_resolucion, r.titulo, r.texto_respaldo, 
                       r.monto_descuento, r.fecha_inicio, r.fecha_fin, r.creado_en,
                       r.ruta_documento, r.creado_por,
                       emp.apnom_emp AS creador_nombre, emp.mailp_emp AS creador_correo
                FROM resoluciones r
                LEFT JOIN empleado emp ON emp.id = r.creado_por
                WHERE r.id = :id
            ");
            $stmtNotif->execute([':id' => $resolucionIdNotif]);
            $notifData = $stmtNotif->fetch(PDO::FETCH_ASSOC);
            if ($notifData) {
                $mensajeNotificacion = "Resolución N° " . ($notifData['numero_resolucion'] ?: 'N/A') . "\n";
                $mensajeNotificacion .= "Título: " . ($notifData['titulo'] ?: 'Sin título') . "\n";
                if ($notifData['monto_descuento'] && $notifData['monto_descuento'] > 0) {
                    $mensajeNotificacion .= "Descuento: S/ " . number_format($notifData['monto_descuento'], 2) . "\n";
                }
                if ($estadoDb === 'rechazado' && $motivo) {
                    $mensajeNotificacion .= "Motivo de rechazo: " . $motivo;
                } else {
                    $mensajeNotificacion .= "Estado: " . $estadoDb;
                }

                try {
                    $historialNotif = $db->prepare("
                        INSERT INTO historial_solicitudes 
                        (solicitud_id, estado, fecha, empleado, comentarios)
                        VALUES (:sid, :estado, NOW(), :emp, :coment)
                    ");
                    $historialNotif->execute([
                        ':sid'   => $id,
                        ':estado'=> $estadoDb,
                        ':emp'   => $empleadoId,
                        ':coment'=> $mensajeNotificacion
                    ]);
                } catch (Throwable $e) {
                    $historialNotif = $db->prepare("
                        INSERT INTO historial_solicitudes 
                        (solicitud_id, estado, empleado_id, comentarios)
                        VALUES (:sid, :estado, :emp, :coment)
                    ");
                    $historialNotif->execute([
                        ':sid'   => $id,
                        ':estado'=> $estadoDb,
                        ':emp'   => $empleadoId,
                        ':coment'=> $mensajeNotificacion
                    ]);
                }
            }
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
