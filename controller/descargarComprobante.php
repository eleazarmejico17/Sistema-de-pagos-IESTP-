<?php
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    die('No autorizado');
}

// Obtener parámetros
$tipo = $_GET['tipo'] ?? '';
$codigo = $_GET['codigo'] ?? '';
$serie = $_GET['serie'] ?? '';
$numero = $_GET['numero'] ?? '';

if (empty($tipo) || empty($codigo)) {
    http_response_code(400);
    die('Parámetros faltantes');
}

// Datos del comprobante (en producción, estos vendrían de la base de datos)
$datos = [
    'Matrícula' => [
        'monto' => 'S/ 120.00',
        'fecha' => '12/03/2025',
        'descripcion' => 'Pago por concepto de matrícula anual para el periodo académico 2025.'
    ],
    'Carnet Medio Pasaje' => [
        'monto' => 'S/ 25.00',
        'fecha' => '18/04/2025',
        'descripcion' => 'Pago por emisión de carnet de medio pasaje para transporte público.'
    ],
    'Folder de Prácticas' => [
        'monto' => 'S/ 15.00',
        'fecha' => '05/06/2025',
        'descripcion' => 'Pago por adquisición del folder institucional para prácticas preprofesionales.'
    ]
];

$info = $datos[$tipo] ?? [
    'monto' => 'S/ 0.00',
    'fecha' => date('d/m/Y'),
    'descripcion' => 'Comprobante de pago.'
];

// Generar HTML del comprobante
$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago - ' . htmlspecialchars($tipo) . '</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
        }
        .header {
            background: linear-gradient(135deg, #002B77 0%, #0040A0 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            border: 2px solid #002B77;
            border-top: none;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h2 {
            color: #002B77;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            background: #f9fafb;
            padding: 12px;
            border-radius: 5px;
        }
        .info-item strong {
            color: #374151;
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .info-item span {
            color: #111827;
            font-size: 14px;
        }
        .total {
            background: #10B981;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        .btn-print {
            background: #002B77;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px auto;
            display: block;
        }
        .btn-print:hover {
            background: #0040A0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INSTITUTO DE EDUCACIÓN SUPERIOR<br>TECNOLÓGICO PÚBLICO</h1>
        <p>ANDRÉS AVELINO CÁCERES DORREGARAY</p>
        <p style="margin-top: 10px; font-size: 16px; font-weight: bold;">COMPROBANTE DE PAGO</p>
    </div>

    <div class="content">
        <div class="section">
            <h2>📌 Información del Estudiante</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Código Estudiante</strong>
                    <span>' . htmlspecialchars($codigo) . '</span>
                </div>
                <div class="info-item">
                    <strong>DNI</strong>
                    <span>73145710</span>
                </div>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <strong>Alumno</strong>
                    <span>Mejico de la cruz Eleazar Natanel</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>🧾 Información del Comprobante</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Tipo de Comprobante</strong>
                    <span>' . htmlspecialchars($tipo) . '</span>
                </div>
                <div class="info-item">
                    <strong>Serie</strong>
                    <span>' . htmlspecialchars($serie) . '</span>
                </div>
                <div class="info-item">
                    <strong>Número</strong>
                    <span>' . htmlspecialchars($numero) . '</span>
                </div>
                <div class="info-item">
                    <strong>Fecha</strong>
                    <span>' . htmlspecialchars($info['fecha']) . '</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>💰 Información del Pago</h2>
            <div class="info-item" style="background: #ecfdf5;">
                <strong>Monto Pagado</strong>
                <span style="font-size: 20px; color: #10B981; font-weight: bold;">' . htmlspecialchars($info['monto']) . '</span>
            </div>
            <div class="info-item" style="margin-top: 10px;">
                <strong>Estado</strong>
                <span style="color: #10B981; font-weight: bold; font-size: 16px;">✓ PAGADO</span>
            </div>
        </div>

        <div class="section">
            <h2>📝 Descripción del Servicio</h2>
            <p style="color: #374151; line-height: 1.6;">' . htmlspecialchars($info['descripcion']) . '</p>
        </div>

        <div class="total">
            Total: ' . htmlspecialchars($info['monto']) . '
        </div>
    </div>

    <div class="footer">
        <p>Este es un comprobante generado electrónicamente por el Sistema de Pagos IESTP</p>
        <p>Fecha de emisión: ' . date('d/m/Y H:i:s') . '</p>
    </div>

    <button class="btn-print no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimir / Guardar como PDF
    </button>

    <script>
        // Auto-imprimir si se solicita descarga directa
        if (window.location.search.includes("download=1")) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>';

// Si se solicita descarga directa, redirigir a la versión imprimible
if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

// Para descarga como PDF, mostrar el HTML (el navegador puede guardarlo como PDF)
header('Content-Type: text/html; charset=utf-8');
echo $html;
exit;
