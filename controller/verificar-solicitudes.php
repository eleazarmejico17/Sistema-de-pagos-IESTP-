<?php
/**
 * Script para verificar y crear solicitudes de prueba
 * Acceder: http://localhost/Sistema-de-pagos-IESTP-mains/controller/verificar-solicitudes.php
 */
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificar Solicitudes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Verificación de Solicitudes</h1>
    
<?php
try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Verificar que la tabla existe
    echo "<h2>1. Verificación de Tabla</h2>";
    $checkTable = $db->query("SHOW TABLES LIKE 'solicitud'");
    if ($checkTable->rowCount() === 0) {
        echo "<p class='error'>❌ La tabla 'solicitud' NO existe</p>";
        exit;
    } else {
        echo "<p class='success'>✅ La tabla 'solicitud' existe</p>";
    }
    
    // 2. Contar solicitudes
    echo "<h2>2. Conteo de Solicitudes</h2>";
    $countStmt = $db->query("SELECT COUNT(*) as total FROM solicitud");
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p class='info'>Total de solicitudes en la base de datos: <strong>$total</strong></p>";
    
    // 3. Mostrar todas las solicitudes
    echo "<h2>3. Solicitudes Existentes</h2>";
    $sql = "SELECT 
                s.id,
                s.nombre,
                s.telefono,
                s.correo,
                s.tipo_solicitud,
                s.estado,
                s.fecha,
                s.fecha_registro
            FROM solicitud s
            ORDER BY s.id DESC
            LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($solicitudes)) {
        echo "<p class='error'>⚠️ No hay solicitudes en la base de datos</p>";
        
        // 4. Crear solicitud de prueba
        echo "<h2>4. Crear Solicitud de Prueba</h2>";
        
        // Verificar que existe un estudiante
        $estStmt = $db->query("SELECT id FROM estudiante LIMIT 1");
        $estudiante = $estStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($estudiante) {
            $insertSql = "INSERT INTO solicitud 
                (estudiante_id, nombre, telefono, correo, tipo_solicitud, descripcion, archivos, fecha, estado)
                VALUES 
                (:estudiante_id, :nombre, :telefono, :correo, :tipo_solicitud, :descripcion, :archivos, :fecha, 'Pendiente')";
            
            $insertStmt = $db->prepare($insertSql);
            $result = $insertStmt->execute([
                ':estudiante_id' => $estudiante['id'],
                ':nombre' => 'Solicitud de Prueba',
                ':telefono' => '987654321',
                ':correo' => 'prueba@institutocajas.edu.pe',
                ':tipo_solicitud' => 'Descuento Académico',
                ':descripcion' => 'Esta es una solicitud de prueba creada automáticamente para verificar el sistema.',
                ':archivos' => '',
                ':fecha' => date('Y-m-d')
            ]);
            
            if ($result) {
                echo "<p class='success'>✅ Solicitud de prueba creada correctamente</p>";
                echo "<p><a href='../views/dashboard-bienestar.php?pagina=solicitud-bienestar-estudiantil'>Ver en el Dashboard</a></p>";
            } else {
                echo "<p class='error'>❌ Error al crear solicitud de prueba</p>";
            }
        } else {
            echo "<p class='error'>⚠️ No hay estudiantes en la base de datos. Crea un estudiante primero.</p>";
        }
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Tipo</th><th>Estado</th><th>Fecha</th></tr>";
        foreach ($solicitudes as $sol) {
            echo "<tr>";
            echo "<td>{$sol['id']}</td>";
            echo "<td>{$sol['nombre']}</td>";
            echo "<td>{$sol['telefono']}</td>";
            echo "<td>{$sol['tipo_solicitud']}</td>";
            echo "<td>{$sol['estado']}</td>";
            echo "<td>{$sol['fecha']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='success'>✅ Se encontraron " . count($solicitudes) . " solicitudes</p>";
        echo "<p><a href='../views/dashboard-bienestar.php?pagina=solicitud-bienestar-estudiantil'>Ver en el Dashboard</a></p>";
    }
    
    // 5. Probar la consulta exacta que usa la vista
    echo "<h2>5. Prueba de Consulta (igual a la vista)</h2>";
    $testSql = "SELECT 
                s.id,
                s.nombre,
                s.telefono,
                s.correo,
                s.tipo_solicitud,
                s.descripcion,
                s.archivos,
                s.fecha,
                COALESCE(s.estado, 'Pendiente') AS estado,
                s.motivo_respuesta,
                s.fecha_respuesta,
                s.fecha_registro,
                COALESCE(e.apnom_emp, '') AS empleado_nombre
            FROM solicitud s
            LEFT JOIN empleado e ON e.id = s.empleado_id
            ORDER BY COALESCE(s.fecha_registro, s.fecha, NOW()) DESC";
    
    $testStmt = $db->prepare($testSql);
    $testStmt->execute();
    $testSolicitudes = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Resultados de la consulta: <strong>" . count($testSolicitudes) . " solicitudes</strong></p>";
    
    if (count($testSolicitudes) > 0) {
        echo "<p class='success'>✅ La consulta funciona correctamente</p>";
    } else {
        echo "<p class='error'>⚠️ La consulta no devuelve resultados aunque hay datos</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
</body>
</html>

