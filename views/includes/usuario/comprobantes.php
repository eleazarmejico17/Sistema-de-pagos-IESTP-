<?php
require_once __DIR__ . '/../../../config/conexion.php';

// La sesión ya está iniciada desde dashboard-usuario.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener ID del estudiante desde la sesión
$estudianteId = null;
$usuarioSesion = $_SESSION['usuario'] ?? '';

$pagoIdFiltro = null;
if (isset($_GET['pago_id'])) {
    $pagoIdFiltro = (int)$_GET['pago_id'];
    if ($pagoIdFiltro <= 0) {
        $pagoIdFiltro = null;
    }
}

if (!empty($usuarioSesion)) {
    $db = Conexion::getInstance()->getConnection();
    
    // Buscar estudiante asociado al usuario - múltiples métodos
    try {
        // Método 1: Buscar por campo 'usuario' exacto
        $stmt = $db->prepare("SELECT id, tipo, estuempleado, usuario FROM usuarios WHERE usuario = :usuario LIMIT 1");
        $stmt->execute([':usuario' => $usuarioSesion]);
        $usuarioRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuarioRow && $usuarioRow['tipo'] == 2 && !empty($usuarioRow['estuempleado'])) {
            $estudianteId = (int)$usuarioRow['estuempleado'];
            // Verificar que existe
            $stmtCheck = $db->prepare("SELECT id FROM estudiante WHERE id = :id LIMIT 1");
            $stmtCheck->execute([':id' => $estudianteId]);
            if (!$stmtCheck->fetch()) {
                $estudianteId = null;
            }
        }
        
        // Método 2: Buscar por DNI extraído del correo/usuario
        if (!$estudianteId) {
            $dni = null;
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
    } catch (Exception $e) {
        error_log("Error buscando estudiante: " . $e->getMessage());
    }
}

// Obtener pagos realizados
$pagos = [];
$estudianteInfo = null;
$db = Conexion::getInstance()->getConnection();

try {
    // Verificar estructura de la tabla pagos
    $stmtColumns = $db->query("SHOW COLUMNS FROM pagos");
    $columns = $stmtColumns->fetchAll(PDO::FETCH_COLUMN);
    $columnaEstudiante = in_array('estudiante', $columns) ? 'estudiante' : 
                        (in_array('estudiante_id', $columns) ? 'estudiante_id' : 'id_estudiante');
    
    if ($estudianteId) {
        // Obtener información del estudiante
        $stmt = $db->prepare("
            SELECT 
                e.id,
                e.dni_est,
                CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre_completo,
                m.id_matricula
            FROM estudiante e
            LEFT JOIN matricula m ON m.estudiante = e.id
            WHERE e.id = :id
            ORDER BY m.id DESC
            LIMIT 1
        ");
        $stmt->execute([':id' => $estudianteId]);
        $estudianteInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener pagos del estudiante específico
        $sql = "
            SELECT 
                p.id,
                p.monto_original,
                p.monto_descuento,
                p.monto_final,
                p.fecha_pago,
                p.registrado_en,
                tp.id AS tipo_pago_id,
                tp.nombre AS tipo_pago_nombre,
                tp.descripcion AS tipo_pago_descripcion
            FROM pagos p
            INNER JOIN tipo_pago tp ON tp.id = p.tipo_pago
            WHERE p.{$columnaEstudiante} = :estudiante_id
        ";

        $params = [':estudiante_id' => $estudianteId];
        if ($pagoIdFiltro !== null) {
            $sql .= " AND p.id = :pago_id";
            $params[':pago_id'] = $pagoIdFiltro;
        }

        $sql .= "
            ORDER BY p.fecha_pago DESC, p.registrado_en DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Si no se encuentra estudiante específico, mostrar todos los pagos recientes (últimos 20)
        $sql = "
            SELECT 
                p.id,
                p.monto_original,
                p.monto_descuento,
                p.monto_final,
                p.fecha_pago,
                p.registrado_en,
                tp.id AS tipo_pago_id,
                tp.nombre AS tipo_pago_nombre,
                tp.descripcion AS tipo_pago_descripcion,
                p.{$columnaEstudiante} AS estudiante_id
            FROM pagos p
            INNER JOIN tipo_pago tp ON tp.id = p.tipo_pago
        ";

        $params = [];
        if ($pagoIdFiltro !== null) {
            $sql .= " WHERE p.id = :pago_id";
            $params[':pago_id'] = $pagoIdFiltro;
        }

        $sql .= "
            ORDER BY p.fecha_pago DESC, p.registrado_en DESC
            LIMIT 20
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si hay pagos, obtener información del primer estudiante encontrado
        if (!empty($pagos)) {
            $primerEstudianteId = $pagos[0]['estudiante_id'] ?? null;
            if ($primerEstudianteId) {
                $stmt = $db->prepare("
                    SELECT 
                        e.id,
                        e.dni_est,
                        CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre_completo,
                        m.id_matricula
                    FROM estudiante e
                    LEFT JOIN matricula m ON m.estudiante = e.id
                    WHERE e.id = :id
                    ORDER BY m.id DESC
                    LIMIT 1
                ");
                $stmt->execute([':id' => $primerEstudianteId]);
                $estudianteInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
    
    // Para cada pago, obtener la información del estudiante si no está disponible
    if (!empty($pagos) && empty($estudianteInfo)) {
        foreach ($pagos as &$pago) {
            if (!isset($pago['estudiante_nombre'])) {
                $estId = $pago[$columnaEstudiante] ?? $pago['estudiante_id'] ?? null;
                if ($estId) {
                    $stmt = $db->prepare("
                        SELECT 
                            id,
                            dni_est,
                            CONCAT(ap_est, ' ', am_est, ' ', nom_est) AS nombre_completo,
                            (SELECT id_matricula FROM matricula WHERE estudiante = e.id ORDER BY id DESC LIMIT 1) AS id_matricula
                        FROM estudiante e
                        WHERE e.id = :id
                        LIMIT 1
                    ");
                    $stmt->execute([':id' => $estId]);
                    $estInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($estInfo) {
                        $pago['estudiante_info'] = $estInfo;
                        if (!$estudianteInfo) {
                            $estudianteInfo = $estInfo;
                        }
                    }
                }
            }
        }
        unset($pago);
    }
    
} catch (Exception $e) {
    error_log("Error obteniendo pagos: " . $e->getMessage());
}

// Colores para las tarjetas según el tipo de pago
function obtenerColorPago($index) {
    $colores = [
        'bg-blue-700',
        'bg-red-600', 
        'bg-lime-500',
        'bg-purple-600',
        'bg-orange-500',
        'bg-teal-600'
    ];
    return $colores[$index % count($colores)];
}

function obtenerColorBorde($index) {
    $colores = [
        'border-blue-700 text-blue-700',
        'border-red-600 text-red-600',
        'border-lime-500 text-lime-600',
        'border-purple-600 text-purple-600',
        'border-orange-500 text-orange-500',
        'border-teal-600 text-teal-600'
    ];
    return $colores[$index % count($colores)];
}

function obtenerColorFondo($index) {
    $colores = [
        'bg-blue-700 hover:bg-blue-800',
        'bg-red-600 hover:bg-red-700',
        'bg-lime-500 hover:bg-lime-600',
        'bg-purple-600 hover:bg-purple-700',
        'bg-orange-500 hover:bg-orange-600',
        'bg-teal-600 hover:bg-teal-700'
    ];
    return $colores[$index % count($colores)];
}

function obtenerColorHover($index) {
    $colores = [
        'hover:bg-blue-50',
        'hover:bg-red-50',
        'hover:bg-lime-50',
        'hover:bg-purple-50',
        'hover:bg-orange-50',
        'hover:bg-teal-50'
    ];
    return $colores[$index % count($colores)];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comprobantes - IESTP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gray-100 flex justify-center">

  <main class="flex-1 p-10 max-w-7xl">
    <h1 class="text-3xl font-bold mb-8 text-gray-800">Comprobantes</h1>

    <?php if (empty($pagos)): ?>
      <div class="bg-white rounded-xl shadow-lg p-8 text-center">
        <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay comprobantes disponibles</h3>
        <p class="text-gray-500">Aún no has realizado ningún pago. Los comprobantes aparecerán aquí después de completar un pago.</p>
      </div>
    <?php else: ?>
      <?php if ($pagoIdFiltro !== null && count($pagos) === 1): 
        $pago = $pagos[0];
        $estInfoPago = $pago['estudiante_info'] ?? $estudianteInfo;
        $codigoEstudiante = $estInfoPago['id_matricula'] ?? $estInfoPago['id'] ?? $pago['id'] ?? 'N/A';
        $nombreCompleto = $estInfoPago['nombre_completo'] ?? 'Estudiante';
        $dni = $estInfoPago['dni_est'] ?? 'N/A';
        $fechaPago = $pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : 
                    ($pago['registrado_en'] ? date('d/m/Y', strtotime($pago['registrado_en'])) : date('d/m/Y'));
        $montoOriginal = number_format((float)($pago['monto_original'] ?? 0), 2, '.', ',');
        $montoDescuento = number_format((float)($pago['monto_descuento'] ?? 0), 2, '.', ',');
        $montoFinal = number_format((float)($pago['monto_final'] ?? 0), 2, '.', ',');
        $concepto = !empty($pago['tipo_pago_descripcion']) ? $pago['tipo_pago_descripcion'] : 
                   (!empty($pago['tipo_pago_nombre']) ? $pago['tipo_pago_nombre'] : 'Pago');
        $serie = 'PAG-' . date('Y');
        $numero = str_pad($pago['id'], 6, '0', STR_PAD_LEFT);
      ?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h2 class="text-2xl font-bold text-gray-800">Detalle del comprobante</h2>
              <p class="text-sm text-gray-600">Pago #<?= htmlspecialchars((string)$pago['id'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="?pagina=comprobantes" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium text-center">
              Volver a la lista
            </a>
          </div>

          <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-gray-50 rounded-xl border p-4">
              <div class="font-semibold text-gray-700 mb-3">Información del estudiante</div>
              <div class="text-sm text-gray-700 space-y-1">
                <div><strong>Código:</strong> <?= htmlspecialchars((string)$codigoEstudiante, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Alumno:</strong> <?= htmlspecialchars((string)$nombreCompleto, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>DNI:</strong> <?= htmlspecialchars((string)$dni, ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>

            <div class="bg-gray-50 rounded-xl border p-4">
              <div class="font-semibold text-gray-700 mb-3">Información del comprobante</div>
              <div class="text-sm text-gray-700 space-y-1">
                <div><strong>Concepto:</strong> <?= htmlspecialchars((string)$concepto, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Serie:</strong> <?= htmlspecialchars((string)$serie, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Número:</strong> <?= htmlspecialchars((string)$numero, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Fecha:</strong> <?= htmlspecialchars((string)$fechaPago, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="mt-3">
                  <div><strong>Monto original:</strong> S/ <?= htmlspecialchars((string)$montoOriginal, ENT_QUOTES, 'UTF-8') ?></div>
                  <div><strong>Descuento:</strong> S/ <?= htmlspecialchars((string)$montoDescuento, ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="text-emerald-700 font-semibold"><strong>Total pagado:</strong> S/ <?= htmlspecialchars((string)$montoFinal, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <button
              onclick="descargarComprobante('<?= htmlspecialchars($concepto, ENT_QUOTES) ?>', '<?= htmlspecialchars($codigoEstudiante, ENT_QUOTES) ?>', '<?= htmlspecialchars($serie, ENT_QUOTES) ?>', '<?= htmlspecialchars($numero, ENT_QUOTES) ?>')"
              class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg font-medium transition flex items-center justify-center gap-2">
              <i class="fas fa-download"></i> Descargar comprobante
            </button>
          </div>
        </div>
      <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">
        <?php foreach ($pagos as $index => $pago): 
          // Obtener información del estudiante para este pago
          $estInfoPago = $pago['estudiante_info'] ?? $estudianteInfo;
          $codigoEstudiante = $estInfoPago['id_matricula'] ?? $estInfoPago['id'] ?? $pago['id'] ?? 'N/A';
          $nombreCompleto = $estInfoPago['nombre_completo'] ?? 'Estudiante';
          $dni = $estInfoPago['dni_est'] ?? 'N/A';
          $fechaPago = $pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : 
                      ($pago['registrado_en'] ? date('d/m/Y', strtotime($pago['registrado_en'])) : date('d/m/Y'));
          $monto = number_format((float)$pago['monto_final'], 2, '.', ',');
          $concepto = !empty($pago['tipo_pago_descripcion']) ? $pago['tipo_pago_descripcion'] : 
                     (!empty($pago['tipo_pago_nombre']) ? $pago['tipo_pago_nombre'] : 'Pago');
          $serie = 'PAG-' . date('Y');
          $numero = str_pad($pago['id'], 6, '0', STR_PAD_LEFT);
          $colorBarra = obtenerColorPago($index);
          $colorBorde = obtenerColorBorde($index);
          $colorFondo = obtenerColorFondo($index);
          $colorHover = obtenerColorHover($index);
        ?>
          <!-- CARD -->
          <div class="card bg-white shadow-lg rounded-xl overflow-hidden border hover:shadow-2xl transition hover:-translate-y-1">
            <div class="flex">
              <div class="w-2 <?= $colorBarra ?>"></div>

              <div class="p-5 flex-1">
                <h3 class="text-xl font-bold text-gray-800 mb-3"><?= htmlspecialchars($concepto) ?></h3>

                <div class="space-y-1 text-gray-700 text-sm">
                  <p><strong>Cód Estudiante:</strong> <?= htmlspecialchars($codigoEstudiante) ?></p>
                  <p><strong>Alumno:</strong> <?= htmlspecialchars($nombreCompleto) ?></p>
                  <p><strong>DNI:</strong> <?= htmlspecialchars($dni) ?></p>
                  <p><strong>Estado:</strong> <span class="text-green-600 font-semibold">PAGADO</span></p>
                </div>

                <div class="flex justify-between mt-5">
                  <a
                    href="?pagina=comprobantes&pago_id=<?= urlencode((string)$pago['id']) ?>"
                    class="px-4 py-2 border <?= $colorBorde ?> rounded-lg font-medium <?= $colorHover ?> transition text-center">
                    Ver
                  </a>

                  <button 
                    onclick="descargarComprobante('<?= htmlspecialchars($concepto, ENT_QUOTES) ?>', '<?= htmlspecialchars($codigoEstudiante, ENT_QUOTES) ?>', '<?= htmlspecialchars($serie, ENT_QUOTES) ?>', '<?= htmlspecialchars($numero, ENT_QUOTES) ?>')"
                    class="px-4 py-2 <?= $colorFondo ?> text-white rounded-lg font-medium transition flex items-center gap-2">
                    <i class="fas fa-download"></i> Descargar
                  </button>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <script>
    function descargarComprobante(tipo, codigo, serie, numero) {
      // Construir la URL del controlador
      const currentPath = window.location.pathname;
      let baseUrl;
      
      if (currentPath.includes('/views/')) {
        baseUrl = currentPath.substring(0, currentPath.indexOf('/views/'));
      } else {
        baseUrl = '';
      }
      
      const url = baseUrl + '/controller/descargarComprobante.php?tipo=' + encodeURIComponent(tipo) + 
                  '&codigo=' + encodeURIComponent(codigo) + 
                  '&serie=' + encodeURIComponent(serie) + 
                  '&numero=' + encodeURIComponent(numero) + 
                  '&download=1';
      
      // Abrir en nueva ventana para imprimir/guardar como PDF
      window.open(url, '_blank');
    }
  </script>

</body>
</html>

