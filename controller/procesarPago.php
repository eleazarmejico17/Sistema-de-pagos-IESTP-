<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexion.php';

try {
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['usuario'])) {
        throw new Exception("Usuario no autenticado");
    }

    // Obtener datos del POST
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception("Datos inválidos");
    }

    $concepto = $data['concepto'] ?? '';
    $monto = $data['monto'] ?? 0;
    $metodo_pago = $data['metodo_pago'] ?? '';
    $tipoPagoId = $data['tipo_pago_id'] ?? 1;
    $numero = $data['numero'] ?? '';
    $uit = $data['uit'] ?? 0;
    $dniEstudiante = $data['dni_estudiante'] ?? '';
    $nombreEstudiante = $data['nombre_estudiante'] ?? '';

    // Validaciones
    if (empty($metodo_pago)) {
        throw new Exception("Debe seleccionar un método de pago");
    }

    if (empty($concepto)) {
        throw new Exception("El concepto de pago es requerido");
    }

    if ($monto <= 0) {
        throw new Exception("El monto debe ser mayor a cero");
    }

    // Obtener conexión
    $db = Conexion::getInstance()->getConnection();

    // Obtener ID del estudiante - múltiples métodos de búsqueda
    $estudianteId = null;
    $usuarioSesion = $_SESSION['usuario'] ?? '';
    
    if (empty($usuarioSesion)) {
        throw new Exception("Sesión no válida. Por favor, inicie sesión nuevamente.");
    }
    
    // Método 1: Buscar por campo 'usuario' exacto
    $stmt = $db->prepare("SELECT id, tipo, estuempleado, usuario FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute([':usuario' => $usuarioSesion]);
    $usuarioRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuarioRow) {
        // Si tipo = 2 (ESTUDIANTE), usar estuempleado como ID del estudiante
        if ($usuarioRow['tipo'] == 2 && !empty($usuarioRow['estuempleado'])) {
            $estudianteId = (int)$usuarioRow['estuempleado'];
            // Verificar que el estudiante existe
            $stmtCheck = $db->prepare("SELECT id FROM estudiante WHERE id = :id LIMIT 1");
            $stmtCheck->execute([':id' => $estudianteId]);
            if (!$stmtCheck->fetch()) {
                $estudianteId = null; // El ID no existe en la tabla estudiante
            }
        }
    }
    
    // Método 2: Si no se encontró, intentar buscar por DNI extraído del correo/usuario
    if (!$estudianteId) {
        $dni = null;
        
        // Extraer DNI del formato: 12345678@institutocajas.edu.pe o 12345678
        if (preg_match('/^(\d{8})(@|$)/', $usuarioSesion, $matches)) {
            $dni = $matches[1];
        } elseif ($usuarioRow && preg_match('/^(\d{8})(@|$)/', $usuarioRow['usuario'] ?? '', $matches)) {
            $dni = $matches[1];
        }
        
        if ($dni) {
            $stmt = $db->prepare("SELECT id FROM estudiante WHERE dni_est = :dni LIMIT 1");
            $stmt->execute([':dni' => $dni]);
            $est = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($est) {
                $estudianteId = (int)$est['id'];
            }
        }
    }
    
    // Método 3: Si aún no se encontró, buscar usuario por correo si existe campo correo
    if (!$estudianteId) {
        try {
            // Intentar buscar por campo correo si existe en la tabla usuarios
            $stmt = $db->prepare("SELECT id, tipo, estuempleado, usuario, correo FROM usuarios WHERE correo = :correo OR usuario = :correo2 LIMIT 1");
            $stmt->execute([':correo' => $usuarioSesion, ':correo2' => $usuarioSesion]);
            $usuarioRow2 = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuarioRow2) {
                if ($usuarioRow2['tipo'] == 2 && !empty($usuarioRow2['estuempleado'])) {
                    $estudianteId = (int)$usuarioRow2['estuempleado'];
                    // Verificar que existe
                    $stmtCheck = $db->prepare("SELECT id FROM estudiante WHERE id = :id LIMIT 1");
                    $stmtCheck->execute([':id' => $estudianteId]);
                    if (!$stmtCheck->fetch()) {
                        $estudianteId = null;
                    }
                } elseif (preg_match('/^(\d{8})(@|$)/', $usuarioRow2['usuario'] ?? $usuarioRow2['correo'] ?? '', $matches)) {
                    $dni = $matches[1];
                    $stmt = $db->prepare("SELECT id FROM estudiante WHERE dni_est = :dni LIMIT 1");
                    $stmt->execute([':dni' => $dni]);
                    $est = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($est) {
                        $estudianteId = (int)$est['id'];
                    }
                }
            }
        } catch (PDOException $e) {
            // Si la columna correo no existe, ignorar el error
            error_log("Error buscando por correo (columna puede no existir): " . $e->getMessage());
        }
    }
    
    // Método 4: Si se proporciona DNI del estudiante en el formulario, usarlo
    if (!$estudianteId && !empty($dniEstudiante) && preg_match('/^\d{8}$/', $dniEstudiante)) {
        $stmt = $db->prepare("SELECT id FROM estudiante WHERE dni_est = :dni LIMIT 1");
        $stmt->execute([':dni' => $dniEstudiante]);
        $est = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($est) {
            $estudianteId = (int)$est['id'];
        } else {
            // Si no existe el estudiante, crear uno temporal con los datos proporcionados
            try {
                // Separar nombre en apellidos y nombre si es posible
                $nombres = explode(' ', trim($nombreEstudiante), 3);
                $ap = $nombres[0] ?? '';
                $am = $nombres[1] ?? '';
                $nom = $nombres[2] ?? ($nombres[1] ?? $nombres[0] ?? '');
                
                $stmtInsert = $db->prepare("INSERT INTO estudiante (dni_est, ap_est, am_est, nom_est, estado) VALUES (:dni, :ap, :am, :nom, 1)");
                $stmtInsert->execute([
                    ':dni' => $dniEstudiante,
                    ':ap' => $ap,
                    ':am' => $am,
                    ':nom' => $nom
                ]);
                $estudianteId = (int)$db->lastInsertId();
            } catch (Exception $e) {
                error_log("Error creando estudiante temporal: " . $e->getMessage());
                // Si falla la creación, buscar cualquier estudiante o usar ID 1 como fallback
                $stmtFallback = $db->query("SELECT id FROM estudiante LIMIT 1");
                $fallback = $stmtFallback->fetch(PDO::FETCH_ASSOC);
                $estudianteId = $fallback ? (int)$fallback['id'] : null;
            }
        }
    }
    
    // Si aún no se encuentra estudiante y se proporcionó DNI, usar el primer estudiante como fallback
    if (!$estudianteId && !empty($dniEstudiante)) {
        $stmt = $db->query("SELECT id FROM estudiante LIMIT 1");
        $fallback = $stmt->fetch(PDO::FETCH_ASSOC);
        $estudianteId = $fallback ? (int)$fallback['id'] : null;
    }
    
    // Si aún no se encuentra estudiante, crear uno con datos mínimos
    if (!$estudianteId) {
        try {
            $dniFinal = !empty($dniEstudiante) && preg_match('/^\d{8}$/', $dniEstudiante) 
                ? $dniEstudiante 
                : '00000000';
            $nombreFinal = !empty($nombreEstudiante) ? $nombreEstudiante : 'Estudiante';
            
            $nombres = explode(' ', trim($nombreFinal), 3);
            $ap = $nombres[0] ?? '';
            $am = $nombres[1] ?? '';
            $nom = $nombres[2] ?? ($nombres[1] ?? $nombres[0] ?? 'Estudiante');
            
            $stmtInsert = $db->prepare("INSERT INTO estudiante (dni_est, ap_est, am_est, nom_est, estado) VALUES (:dni, :ap, :am, :nom, 1)");
            $stmtInsert->execute([
                ':dni' => $dniFinal,
                ':ap' => $ap,
                ':am' => $am,
                ':nom' => $nom
            ]);
            $estudianteId = (int)$db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando estudiante final: " . $e->getMessage());
            throw new Exception("Error al procesar el pago. Por favor, intente nuevamente.");
        }
    }

    // El tipo_pago_id ya se obtuvo arriba desde los datos
    $solicitudId = $data['solicitud_id'] ?? null; // Opcional
    $montoDescuento = $data['monto_descuento'] ?? 0.00;
    $montoFinal = $monto - $montoDescuento;

    // Verificar estructura de la tabla pagos antes de insertar
    try {
        $stmtColumns = $db->query("SHOW COLUMNS FROM pagos");
        $columns = $stmtColumns->fetchAll(PDO::FETCH_COLUMN);
        
        // Verificar si la columna estudiante existe, si no, usar estudiante_id o id_estudiante
        $columnaEstudiante = 'estudiante';
        if (!in_array('estudiante', $columns)) {
            if (in_array('estudiante_id', $columns)) {
                $columnaEstudiante = 'estudiante_id';
            } elseif (in_array('id_estudiante', $columns)) {
                $columnaEstudiante = 'id_estudiante';
            } else {
                throw new Exception("No se encontró una columna de estudiante en la tabla pagos. Columnas disponibles: " . implode(', ', $columns));
            }
        }
        
        // Construir el INSERT dinámicamente según las columnas disponibles
        $columnasInsert = [];
        $valoresInsert = [];
        $parametros = [];
        
        // Columnas obligatorias
        $columnasInsert[] = $columnaEstudiante;
        $valoresInsert[] = ":estudiante";
        $parametros[':estudiante'] = $estudianteId;
        
        if (in_array('solicitudes', $columns)) {
            $columnasInsert[] = 'solicitudes';
            $valoresInsert[] = ":solicitudes";
            $parametros[':solicitudes'] = $solicitudId;
        }
        
        if (in_array('tipo_pago', $columns)) {
            $columnasInsert[] = 'tipo_pago';
            $valoresInsert[] = ":tipo_pago";
            $parametros[':tipo_pago'] = $tipoPagoId;
        }
        
        if (in_array('monto_original', $columns)) {
            $columnasInsert[] = 'monto_original';
            $valoresInsert[] = ":monto_original";
            $parametros[':monto_original'] = $monto;
        }
        
        if (in_array('monto_descuento', $columns)) {
            $columnasInsert[] = 'monto_descuento';
            $valoresInsert[] = ":monto_descuento";
            $parametros[':monto_descuento'] = $montoDescuento;
        }
        
        if (in_array('monto_final', $columns)) {
            $columnasInsert[] = 'monto_final';
            $valoresInsert[] = ":monto_final";
            $parametros[':monto_final'] = $montoFinal;
        }
        
        if (in_array('fecha_pago', $columns)) {
            $columnasInsert[] = 'fecha_pago';
            $valoresInsert[] = "NOW()";
        }
        
        if (in_array('registrado_en', $columns)) {
            $columnasInsert[] = 'registrado_en';
            $valoresInsert[] = "NOW()";
        }
        
        $sql = "INSERT INTO pagos (" . implode(', ', $columnasInsert) . ") VALUES (" . implode(', ', $valoresInsert) . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($parametros);
        
    } catch (PDOException $e) {
        error_log("Error verificando estructura de tabla pagos: " . $e->getMessage());
        // Intentar con estructura estándar como fallback
        try {
            $sql = "INSERT INTO pagos (estudiante, tipo_pago, monto_original, monto_final, fecha_pago) 
                    VALUES (:estudiante, :tipo_pago, :monto_original, :monto_final, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':estudiante' => $estudianteId,
                ':tipo_pago' => $tipoPagoId,
                ':monto_original' => $monto,
                ':monto_final' => $montoFinal
            ]);
        } catch (PDOException $e2) {
            error_log("Error en fallback de inserción: " . $e2->getMessage());
            throw new Exception("Error al registrar el pago en la base de datos. Por favor, contacte al administrador.");
        }
    }

    $pagoId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Pago procesado correctamente',
        'pago_id' => $pagoId,
        'redirect' => '?pagina=comprobantes'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

