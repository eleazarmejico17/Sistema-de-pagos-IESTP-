<?php
/**
 * Script para insertar conceptos de pago con valores de precio
 * Ejecutar una sola vez desde el navegador o línea de comandos
 */

require_once __DIR__ . '/../config/conexion.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar que exista la columna precio
    try {
        $stmt = $db->query("SHOW COLUMNS FROM tipo_pago LIKE 'precio'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("La columna 'precio' no existe en tipo_pago. Ejecuta la migración SQL primero.");
        }
    } catch (Exception $e) {
        throw $e;
    }
    
    // Conceptos de pago con valores de precio
    $conceptos = [
        ['nombre' => '1.1', 'descripcion' => 'Carnet de Medio Pasaje', 'precio' => 18.00],
        ['nombre' => '1.2', 'descripcion' => 'Duplicado de carnet', 'precio' => 18.00],
        ['nombre' => '3.1', 'descripcion' => 'Inscripción del postulante modalidad ordinario', 'precio' => 205.00],
        ['nombre' => '3.2', 'descripcion' => 'Inscripción del postulante modalidad exonerados', 'precio' => 205.00],
        ['nombre' => '3.3', 'descripcion' => 'Inscripción del postulante modalidad por convenio de Transitabilidad', 'precio' => 100.00],
        ['nombre' => '4.1', 'descripcion' => 'Trámite de Traslado Interno', 'precio' => 8.00],
        ['nombre' => '4.2', 'descripcion' => 'Trámite de Traslado de Turno', 'precio' => 8.00],
        ['nombre' => '4.3', 'descripcion' => 'Trámite de Traslado Externo', 'precio' => 8.00],
        ['nombre' => '5.1', 'descripcion' => 'Ratificación de matrícula', 'precio' => 172.00],
        ['nombre' => '5.2', 'descripcion' => 'Matrícula Ingresantes', 'precio' => 220.00],
        ['nombre' => '5.3', 'descripcion' => 'Matrícula de ingresantes por exoneración', 'precio' => 220.00],
        ['nombre' => '5.4', 'descripcion' => 'Matrícula Traslado de Turno', 'precio' => 288.00],
        ['nombre' => '5.5', 'descripcion' => 'Matrícula Traslado Interno', 'precio' => 288.00],
        ['nombre' => '5.6', 'descripcion' => 'Matrícula Traslado Externo', 'precio' => 515.00],
        ['nombre' => '6.1', 'descripcion' => 'Trámite de matrícula extemporánea', 'precio' => 8.00],
        ['nombre' => '6.2', 'descripcion' => 'Matrícula extemporánea', 'precio' => 233.00],
        ['nombre' => '6.3', 'descripcion' => 'Reserva de matrícula por procesos', 'precio' => 110.00],
        ['nombre' => '7.1', 'descripcion' => 'Convalidación interna por semestre', 'precio' => 61.00],
        ['nombre' => '7.2', 'descripcion' => 'Convalidación externa por semestre', 'precio' => 61.00],
        ['nombre' => '8.1', 'descripcion' => 'Trámite de repitencia de semestre', 'precio' => 8.00],
        ['nombre' => '8.2', 'descripcion' => 'Matrícula de repitencia de semestre', 'precio' => 343.00],
        ['nombre' => '9.1', 'descripcion' => 'Trámite de Reingreso', 'precio' => 8.00],
        ['nombre' => '9.2', 'descripcion' => 'Matrícula de Reingreso', 'precio' => 282.00],
    ];
    
    $insertados = 0;
    $actualizados = 0;
    
    $stmtInsert = $db->prepare("
        INSERT INTO tipo_pago (nombre, descripcion, precio) 
        VALUES (:nombre, :descripcion, :precio)
        ON DUPLICATE KEY UPDATE 
            descripcion = VALUES(descripcion),
            precio = VALUES(precio)
    ");
    
    foreach ($conceptos as $concepto) {
        $stmtInsert->execute([
            ':nombre' => $concepto['nombre'],
            ':descripcion' => $concepto['descripcion'],
            ':precio' => $concepto['precio']
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

