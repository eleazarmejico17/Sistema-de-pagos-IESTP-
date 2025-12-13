<?php
/**
 * Script para insertar conceptos de pago con valores UIT
 * Ejecutar una sola vez desde el navegador o línea de comandos
 */

require_once __DIR__ . '/../config/conexion.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar y agregar campo UIT si no existe
    try {
        $stmt = $db->query("SHOW COLUMNS FROM tipo_pago LIKE 'uit'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE tipo_pago ADD COLUMN uit DECIMAL(10,2) DEFAULT 0.00 AFTER descripcion");
            echo "✅ Campo UIT agregado a la tabla tipo_pago\n";
        }
    } catch (Exception $e) {
        error_log("Error al verificar campo UIT: " . $e->getMessage());
    }
    
    // Conceptos de pago con valores UIT
    $conceptos = [
        ['nombre' => '1.1', 'descripcion' => 'Carnet de Medio Pasaje', 'uit' => 18.00],
        ['nombre' => '1.2', 'descripcion' => 'Duplicado de carnet', 'uit' => 18.00],
        ['nombre' => '3.1', 'descripcion' => 'Inscripción del postulante modalidad ordinario', 'uit' => 205.00],
        ['nombre' => '3.2', 'descripcion' => 'Inscripción del postulante modalidad exonerados', 'uit' => 205.00],
        ['nombre' => '3.3', 'descripcion' => 'Inscripción del postulante modalidad por convenio de Transitabilidad', 'uit' => 100.00],
        ['nombre' => '4.1', 'descripcion' => 'Trámite de Traslado Interno', 'uit' => 8.00],
        ['nombre' => '4.2', 'descripcion' => 'Trámite de Traslado de Turno', 'uit' => 8.00],
        ['nombre' => '4.3', 'descripcion' => 'Trámite de Traslado Externo', 'uit' => 8.00],
        ['nombre' => '5.1', 'descripcion' => 'Ratificación de matrícula', 'uit' => 172.00],
        ['nombre' => '5.2', 'descripcion' => 'Matrícula Ingresantes', 'uit' => 220.00],
        ['nombre' => '5.3', 'descripcion' => 'Matrícula de ingresantes por exoneración', 'uit' => 220.00],
        ['nombre' => '5.4', 'descripcion' => 'Matrícula Traslado de Turno', 'uit' => 288.00],
        ['nombre' => '5.5', 'descripcion' => 'Matrícula Traslado Interno', 'uit' => 288.00],
        ['nombre' => '5.6', 'descripcion' => 'Matrícula Traslado Externo', 'uit' => 515.00],
        ['nombre' => '6.1', 'descripcion' => 'Trámite de matrícula extemporánea', 'uit' => 8.00],
        ['nombre' => '6.2', 'descripcion' => 'Matrícula extemporánea', 'uit' => 233.00],
        ['nombre' => '6.3', 'descripcion' => 'Reserva de matrícula por procesos', 'uit' => 110.00],
        ['nombre' => '7.1', 'descripcion' => 'Convalidación interna por semestre', 'uit' => 61.00],
        ['nombre' => '7.2', 'descripcion' => 'Convalidación externa por semestre', 'uit' => 61.00],
        ['nombre' => '8.1', 'descripcion' => 'Trámite de repitencia de semestre', 'uit' => 8.00],
        ['nombre' => '8.2', 'descripcion' => 'Matrícula de repitencia de semestre', 'uit' => 343.00],
        ['nombre' => '9.1', 'descripcion' => 'Trámite de Reingreso', 'uit' => 8.00],
        ['nombre' => '9.2', 'descripcion' => 'Matrícula de Reingreso', 'uit' => 282.00],
    ];
    
    $insertados = 0;
    $actualizados = 0;
    
    $stmtInsert = $db->prepare("
        INSERT INTO tipo_pago (nombre, descripcion, uit) 
        VALUES (:nombre, :descripcion, :uit)
        ON DUPLICATE KEY UPDATE 
            descripcion = VALUES(descripcion),
            uit = VALUES(uit)
    ");
    
    foreach ($conceptos as $concepto) {
        $stmtInsert->execute([
            ':nombre' => $concepto['nombre'],
            ':descripcion' => $concepto['descripcion'],
            ':uit' => $concepto['uit']
        ]);
        
        if ($stmtInsert->rowCount() > 0) {
            $insertados++;
        } else {
            $actualizados++;
        }
    }
    
    echo "✅ Proceso completado exitosamente!\n";
    echo "📊 Conceptos insertados/actualizados: " . count($conceptos) . "\n";
    echo "✅ Nuevos registros: $insertados\n";
    echo "🔄 Registros actualizados: $actualizados\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    error_log("Error al insertar conceptos de pago: " . $e->getMessage());
}

