<?php
/**
 * Script para diagnosticar por qué no se muestran las solicitudes aprobadas
 * Acceder: http://localhost/Sistema-de-pagos-IESTP/controller/verificarEstados.php
 */
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Solicitudes</title>
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
    <h1>Diagnóstico de Solicitudes - Estados</h1>
    
<?php
try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Verificar que la tabla existe
    echo "<h2>1. Verificación de Tabla</h2>";
    $checkTable = $db->query("SHOW TABLES LIKE 'solicitudes'");
    if ($checkTable->rowCount() === 0) {
        echo "<p class='error'>❌ La tabla 'solicitudes' NO existe</p>";
        exit;
    } else {
        echo "<p class='success'>✅ La tabla 'solicitudes' existe</p>";
    }
    
    // 2. Verificar todos los estados que existen
    echo "<h2>2. Estados encontrados en la tabla</h2>";
    $estadosStmt = $db->query("SELECT DISTINCT estado FROM solicitudes ORDER BY estado");
    $estados = $estadosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($estados)) {
        echo "<p class='error'>⚠️ No hay registros en la tabla solicitudes</p>";
    } else {
        echo "<table>";
        echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
        foreach ($estados as $estado) {
            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM solicitudes WHERE estado = ?");
            $countStmt->execute([$estado['estado']]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "<tr><td>{$estado['estado']}</td><td>{$count}</td></tr>";
        }
        echo "</table>";
    }
    
    // 3. Verificar solicitudes aprobadas específicamente
    echo "<h2>3. Búsqueda específica de solicitudes aprobadas</h2>";
    
    // Intentar con diferentes posibles valores
    $posiblesEstados = ['aprobado', 'aprobada', 'Aprobado', 'APROBADO'];
    
    foreach ($posiblesEstados as $estado) {
        $aprobStmt = $db->prepare("SELECT COUNT(*) as total FROM solicitudes WHERE estado = ?");
        $aprobStmt->execute([$estado]);
        $totalAprob = $aprobStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($totalAprob > 0) {
            echo "<p class='success'>✅ Encontradas {$totalAprob} solicitudes con estado '{$estado}'</p>";
            
            // Mostrar detalles de estas solicitudes
            $detallesStmt = $db->prepare("SELECT id, estudiante, tipo_solicitud, estado, fecha_solicitud FROM solicitudes WHERE estado = ? LIMIT 5");
            $detallesStmt->execute([$estado]);
            $detalles = $detallesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Estudiante</th><th>Tipo</th><th>Estado</th><th>Fecha</th></tr>";
            foreach ($detalles as $detalle) {
                echo "<tr>";
                echo "<td>{$detalle['id']}</td>";
                echo "<td>{$detalle['estudiante']}</td>";
                echo "<td>{$detalle['tipo_solicitud']}</td>";
                echo "<td>{$detalle['estado']}</td>";
                echo "<td>{$detalle['fecha_solicitud']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // 4. Mostrar las últimas 10 solicitudes para diagnóstico
    echo "<h2>4. Últimas 10 solicitudes (todos los estados)</h2>";
    $ultimasStmt = $db->query("SELECT id, estudiante, tipo_solicitud, estado, fecha_solicitud FROM solicitudes ORDER BY id DESC LIMIT 10");
    $ultimas = $ultimasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ultimas)) {
        echo "<p class='error'>⚠️ No hay solicitudes en la tabla</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Estudiante</th><th>Tipo</th><th>Estado</th><th>Fecha</th></tr>";
        foreach ($ultimas as $solicitud) {
            $colorEstado = '';
            switch($solicitud['estado']) {
                case 'aprobado':
                case 'aprobada':
                case 'Aprobado':
                case 'APROBADO':
                    $colorEstado = 'style="background-color: #d4edda;"';
                    break;
                case 'rechazado':
                case 'rechazada':
                case 'Rechazado':
                case 'RECHAZADO':
                    $colorEstado = 'style="background-color: #f8d7da;"';
                    break;
                default:
                    $colorEstado = '';
            }
            
            echo "<tr>";
            echo "<td>{$solicitud['id']}</td>";
            echo "<td>{$solicitud['estudiante']}</td>";
            echo "<td>{$solicitud['tipo_solicitud']}</td>";
            echo "<td {$colorEstado}>{$solicitud['estado']}</td>";
            echo "<td>{$solicitud['fecha_solicitud']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. Probar la consulta exacta que usa la vista
    echo "<h2>5. Prueba de consulta (igual a la vista)</h2>";
    $testSql = "SELECT 
                s.id,
                CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre,
                e.cel_est AS telefono,
                e.mailp_est AS correo,
                s.tipo_solicitud,
                s.descripcion,
                s.foto AS archivos,
                s.fecha_solicitud AS fecha,
                CASE 
                    WHEN s.estado = 'aprobado' THEN 'Aprobado'
                    WHEN s.estado = 'rechazado' THEN 'Rechazado'
                    WHEN s.estado = 'en_evaluacion' THEN 'En evaluación'
                    ELSE 'Pendiente'
                END AS estado,
                s.observaciones AS motivo_respuesta,
                s.fecha_revision AS fecha_respuesta,
                s.fecha_solicitud AS fecha_registro,
                '' AS empleado_nombre
            FROM solicitudes s
            LEFT JOIN estudiante e ON e.id = s.estudiante
            ORDER BY s.id DESC, COALESCE(s.fecha_revision, s.fecha_solicitud, NOW()) DESC";
    
    $testStmt = $db->prepare($testSql);
    $testStmt->execute();
    $testSolicitudes = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Resultados de la consulta: <strong>" . count($testSolicitudes) . " solicitudes</strong></p>";
    
    // Contar por estado en los resultados
    $contarResultados = [];
    foreach ($testSolicitudes as $solicitud) {
        $estado = $solicitud['estado'];
        if (!isset($contarResultados[$estado])) {
            $contarResultados[$estado] = 0;
        }
        $contarResultados[$estado]++;
    }
    
    echo "<table>";
    echo "<tr><th>Estado (según CASE)</th><th>Cantidad</th></tr>";
    foreach ($contarResultados as $estado => $cantidad) {
        echo "<tr><td>{$estado}</td><td>{$cantidad}</td></tr>";
    }
    echo "</table>";
    
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
