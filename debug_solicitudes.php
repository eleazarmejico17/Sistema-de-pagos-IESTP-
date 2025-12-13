<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Solicitudes Aprobadas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #333; }
        h3 { color: #666; }
        h4 { color: #888; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<?php
require_once __DIR__ . '/models/bienestar-beneficiariosModel.php';
$beneficiarioModel = new BeneficiarioModel();
$solicitudesAprobadas = $beneficiarioModel->listarSolicitudesAprobadas();

echo "<h2>Diagnóstico de Solicitudes Aprobadas</h2>";
echo "<p class='success'>Total encontradas por el modelo: <strong>" . count($solicitudesAprobadas) . "</strong></p>";

if (empty($solicitudesAprobadas)) {
    echo "<h3 class='error'>NO SE ENCONTRARON SOLICITUDES APROBADAS</h3>";
    
    // Verificar todos los estados en la BD
    try {
        $conn = new PDO("mysql:host=localhost;dbname=db_sistema", "root", "");
        $sql = "SELECT DISTINCT estado FROM solicitud ORDER BY estado";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $estados = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h4>Estados encontrados en BD:</h4>";
        echo "<pre>";
        print_r($estados);
        echo "</pre>";
        
        // Verificar si hay solicitudes con estado similar
        $sql = "SELECT id, nombre, estado FROM solicitud WHERE estado LIKE '%apro%' OR estado LIKE '%Apro%' LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $similares = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($similares)) {
            echo "<h4>Solicitudes con estado similar a 'aprobado':</h4>";
            echo "<pre>";
            print_r($similares);
            echo "</pre>";
        }
        
        // Mostrar todas las solicitudes
        $sql = "SELECT id, nombre, estado FROM solicitud LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Primeras 10 solicitudes en BD:</h4>";
        echo "<pre>";
        print_r($todas);
        echo "</pre>";
        
    } catch(PDOException $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h3 class='success'>SOLICITUDES APROBADAS ENCONTRADAS:</h3>";
    echo "<pre>";
    print_r($solicitudesAprobadas);
    echo "</pre>";
}
?>
</body>
</html>
