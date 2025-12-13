<?php
require_once __DIR__ . '/../../../config/conexion.php';

// La sesión ya está iniciada desde dashboard-usuario.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener ID del estudiante desde la sesión
$estudianteId = null;
$usuarioSesion = $_SESSION['usuario'] ?? '';

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
            ORDER BY p.fecha_pago DESC, p.registrado_en DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':estudiante_id' => $estudianteId]);
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
            ORDER BY p.fecha_pago DESC, p.registrado_en DESC
            LIMIT 20
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
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
                  <button 
                    onclick="openModal({
                      titulo: '<?= htmlspecialchars($concepto, ENT_QUOTES) ?>',
                      codigo: '<?= htmlspecialchars($codigoEstudiante, ENT_QUOTES) ?>',
                      alumno: '<?= htmlspecialchars($nombreCompleto, ENT_QUOTES) ?>',
                      dni: '<?= htmlspecialchars($dni, ENT_QUOTES) ?>',
                      estado: 'PAGADO',
                      fecha: '<?= htmlspecialchars($fechaPago, ENT_QUOTES) ?>',
                      monto: 'S/ <?= htmlspecialchars($monto, ENT_QUOTES) ?>',
                      tipo: 'Recibo de Ingreso',
                      serie: '<?= htmlspecialchars($serie, ENT_QUOTES) ?>',
                      numero: '<?= htmlspecialchars($numero, ENT_QUOTES) ?>',
                      descripcion: 'Pago realizado por concepto: <?= htmlspecialchars($concepto, ENT_QUOTES) ?>. Monto total: S/ <?= htmlspecialchars($monto, ENT_QUOTES) ?>.'
                    })"
                    class="px-4 py-2 border <?= $colorBorde ?> rounded-lg font-medium <?= $colorHover ?> transition">
                    Ver
                  </button>

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
  </main>

  <!-- === MODAL SUPER DETALLADO === -->
  <div id="modal" class="fixed inset-0 hidden bg-black bg-opacity-40 flex justify-center items-center backdrop-blur-sm p-4 z-50">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl p-6 animate-fadeIn border border-gray-200">

      <div class="flex justify-between items-center mb-4">
        <h2 id="modalTitulo" class="text-2xl font-bold flex items-center gap-2">
          <i class="fa-solid fa-file-invoice text-blue-600"></i>
        </h2>
        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
      </div>

      <!-- INFO GENERAL -->
      <div class="mb-4">
        <h3 class="font-semibold text-gray-700 mb-2">📌 Información del Estudiante</h3>
        <div class="bg-gray-50 p-3 rounded-lg border">
          <p><strong>Código:</strong> <span id="modalCodigo"></span></p>
          <p><strong>Alumno:</strong> <span id="modalAlumno"></span></p>
          <p><strong>DNI:</strong> <span id="modalDni"></span></p>
        </div>
      </div>

      <!-- COMPROBANTE -->
      <div class="mb-4">
        <h3 class="font-semibold text-gray-700 mb-2">🧾 Información del Comprobante</h3>
        <div class="bg-gray-50 p-3 rounded-lg border grid grid-cols-2 gap-2 text-sm">
          <p><strong>Tipo:</strong> <span id="modalTipo"></span></p>
          <p><strong>Serie:</strong> <span id="modalSerie"></span></p>
          <p><strong>Número:</strong> <span id="modalNumero"></span></p>
          <p><strong>Fecha:</strong> <span id="modalFecha"></span></p>
        </div>
      </div>

      <!-- PAGO -->
      <div class="mb-4">
        <h3 class="font-semibold text-gray-700 mb-2">💰 Información del Pago</h3>
        <div class="bg-gray-50 p-3 rounded-lg border">
          <p><strong>Monto:</strong> <span id="modalMonto" class="font-semibold text-green-600"></span></p>
          <p><strong>Estado:</strong> <span id="modalEstado" class="font-semibold text-green-600"></span></p>
        </div>
      </div>

      <!-- DESCRIPCION -->
      <div class="mb-4">
        <h3 class="font-semibold text-gray-700 mb-2">📝 Descripción del Servicio</h3>
        <div class="bg-gray-50 p-3 rounded-lg border text-sm">
          <p id="modalDescripcion" class="leading-relaxed"></p>
        </div>
      </div>

      <div class="flex justify-end mt-6">
        <button onclick="closeModal()" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <style>
    .animate-fadeIn { animation: fadeIn .3s ease-out forwards; }
    @keyframes fadeIn { from { opacity:0; transform:scale(.95); } to { opacity:1; transform:scale(1); } }
  </style>

  <script>
    function openModal(data) {
      document.getElementById("modalTitulo").innerHTML = '<i class="fa-solid fa-file-invoice text-blue-600"></i> ' + data.titulo;
      document.getElementById("modalCodigo").innerText = data.codigo;
      document.getElementById("modalAlumno").innerText = data.alumno;
      document.getElementById("modalDni").innerText = data.dni;
      document.getElementById("modalEstado").innerText = data.estado;
      document.getElementById("modalFecha").innerText = data.fecha;
      document.getElementById("modalMonto").innerText = data.monto;
      document.getElementById("modalTipo").innerText = data.tipo;
      document.getElementById("modalSerie").innerText = data.serie;
      document.getElementById("modalNumero").innerText = data.numero;
      document.getElementById("modalDescripcion").innerText = data.descripcion;

      document.getElementById("modal").classList.remove("hidden");
    }

    function closeModal() {
      document.getElementById("modal").classList.add("hidden");
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById("modal")?.addEventListener('click', function(e) {
      if (e.target.id === 'modal') {
        closeModal();
      }
    });

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

