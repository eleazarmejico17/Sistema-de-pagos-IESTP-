<?php
require_once __DIR__ . '/../../../config/conexion.php';
$pdo = Conexion::getInstance()->getConnection();

// Función para obtener descuentos activos del estudiante
function obtenerDescuentosActivos($pdo, $estudianteId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.id as beneficiario_id,
                r.numero_resolucion,
                r.titulo,
                r.monto_descuento as monto_resolucion,
                r.tipo_pago as tipo_pago_id
            FROM beneficiarios b
            INNER JOIN resoluciones r ON b.resoluciones = r.id
            WHERE b.estudiante = :estudiante_id 
            AND b.activo = 1 
            AND (b.fecha_fin IS NULL OR b.fecha_fin >= CURDATE())
            AND (b.fecha_inicio IS NULL OR b.fecha_inicio <= CURDATE())
            ORDER BY COALESCE(r.monto_descuento, 0) DESC
        ");
        $stmt->execute([':estudiante_id' => $estudianteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo descuentos: " . $e->getMessage());
        return [];
    }

}

function obtenerEstudianteBasico($pdo, $estudianteId) {
    if (!$estudianteId) return null;
    try {
        $stmt = $pdo->prepare("SELECT id, dni_est, ap_est, am_est, nom_est FROM estudiante WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$estudianteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $nombre = trim(($row['ap_est'] ?? '') . ' ' . ($row['am_est'] ?? '') . ' ' . ($row['nom_est'] ?? ''));
        return [
            'id' => (int)$row['id'],
            'dni' => (string)($row['dni_est'] ?? ''),
            'nombre' => $nombre,
        ];
    } catch (Throwable $e) {
        return null;
    }
}

function descuentoYaUsado($pdo, $estudianteId) {
    if (!$estudianteId) return false;
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM pagos WHERE estudiante = :estudiante AND monto_descuento > 0 LIMIT 1");
        $stmt->execute([':estudiante' => (int)$estudianteId]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return true;
    }
}

function obtenerMetodosPago($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nombre, descripcion FROM metodo_pago ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            return $rows;
        }
    } catch (Throwable $e) {
        // ignore
    }

    return [
        ['id' => 1, 'nombre' => 'Yape', 'descripcion' => 'Pago móvil rápido y seguro'],
        ['id' => 2, 'nombre' => 'Plin', 'descripcion' => 'Pago móvil bancario'],
        ['id' => 3, 'nombre' => 'Transferencia', 'descripcion' => 'Transferencia electrónica'],
        ['id' => 4, 'nombre' => 'Depósito', 'descripcion' => 'Pago en cuenta bancaria'],
        ['id' => 5, 'nombre' => 'Efectivo', 'descripcion' => 'Pago físico directo'],
    ];
}

// Función para obtener ID del estudiante desde sesión
function obtenerEstudianteIdDesdeSesion($pdo) {
    if (!isset($_SESSION['usuario'])) {
        return null;
    }
    
    $usuarioSesion = $_SESSION['usuario'];
    
    // Método 1: Buscar por campo 'usuario' exacto
    $stmt = $pdo->prepare("SELECT id, tipo, estuempleado FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute([':usuario' => $usuarioSesion]);
    $usuarioRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuarioRow && $usuarioRow['tipo'] == 2 && !empty($usuarioRow['estuempleado'])) {
        return (int)$usuarioRow['estuempleado'];
    }
    
    // Método 2: Extraer DNI del usuario
    if (preg_match('/^(\d{8})(@|$)/', $usuarioSesion, $matches)) {
        $dni = $matches[1];
        $stmt = $pdo->prepare("SELECT id FROM estudiante WHERE dni_est = :dni LIMIT 1");
        $stmt->execute([':dni' => $dni]);
        $est = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($est) {
            return (int)$est['id'];
        }
    }
    
    return null;
}

// Obtener ID del estudiante actual
$estudianteIdActual = obtenerEstudianteIdDesdeSesion($pdo);
$descuentosActivos = [];
if ($estudianteIdActual) {
    $descuentosActivos = obtenerDescuentosActivos($pdo, $estudianteIdActual);
}

$estudianteBasico = obtenerEstudianteBasico($pdo, $estudianteIdActual);
$dniEstudianteSesion = $estudianteBasico ? (string)($estudianteBasico['dni'] ?? '') : '';
$nombreEstudianteSesion = $estudianteBasico ? (string)($estudianteBasico['nombre'] ?? '') : '';

$descuentoBloqueado = false;
if ($estudianteIdActual) {
    $descuentoBloqueado = descuentoYaUsado($pdo, $estudianteIdActual);
    if ($descuentoBloqueado) {
        $descuentosActivos = [];
    }
}

$metodosPago = obtenerMetodosPago($pdo);

// Obtener TODOS los conceptos de pago con precio
try {
    $stmt = $pdo->query("
        SELECT id, nombre, descripcion, COALESCE(precio, 0.00) as precio
        FROM tipo_pago 
        ORDER BY id ASC
    ");
    $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback si hay error
    try {
        $stmt = $pdo->query("
            SELECT id, nombre, descripcion, COALESCE(precio, 0.00) as precio
            FROM tipo_pago 
            ORDER BY id ASC
        ");
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        // Último fallback
        $stmt = $pdo->query("SELECT id, nombre, descripcion FROM tipo_pago ORDER BY id ASC");
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lista as &$item) {
            $item['precio'] = 0.00;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagos - IESTP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .animate-slide-up {
        animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .payment-card {
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }
    .payment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        border-color: #3b82f6;
    }
    .method-card {
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }
    .method-card:hover {
        transform: translateY(-2px);
        border-color: #6366f1;
    }
    .method-card.selected {
        border-color: #6366f1;
        background-color: #f0f9ff;
    }
    .glow-effect {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
  </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
  <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8">
    <!-- Header principal -->
    <div class="mb-8 animate-fade-in">
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 md:p-8 text-white">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="flex-1">
              <h1 class="text-2xl md:text-3xl font-bold mb-2">Portal de Pagos</h1>
              <p class="text-blue-100 opacity-90 mb-4">Realiza tus pagos de trámites de forma rápida y segura</p>
              
              <?php if ($estudianteBasico): ?>
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl p-4 max-w-md">
                  <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                  <div>
                    <div class="font-semibold"><?= htmlspecialchars($nombreEstudianteSesion, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-sm text-blue-200">DNI: <?= htmlspecialchars($dniEstudianteSesion, ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 min-w-[220px]">
              <div class="text-center">
                <div class="flex items-center justify-center gap-2 mb-2">
                  <i class="fas fa-wallet text-2xl opacity-80"></i>
                  <div>
                    <div class="text-2xl font-bold"><?= count($lista) ?></div>
                    <div class="text-sm text-blue-200">Trámites disponibles</div>
                  </div>
                </div>
                <?php if (!$descuentoBloqueado && count($descuentosActivos) > 0): ?>
                  <div class="text-xs text-emerald-300 bg-emerald-500/20 rounded-lg p-2 mt-2">
                    <i class="fas fa-tag mr-1"></i>
                    <?= count($descuentosActivos) ?> descuento(s) disponible(s)
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de trámites/pagos -->
    <div id="lista-pagos" class="animate-slide-up">
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-100">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h2 class="text-xl font-bold text-gray-800">Trámites Disponibles</h2>
              <p class="text-gray-600 text-sm">Selecciona el trámite que deseas realizar</p>
            </div>
            
            <?php if ($descuentoBloqueado): ?>
              <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                <div class="flex items-center gap-2">
                  <i class="fas fa-info-circle text-amber-600"></i>
                  <span class="text-sm text-amber-700">Ya has utilizado tu descuento disponible</span>
                </div>
              </div>
            <?php elseif (!empty($descuentosActivos)): ?>
              <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                <div class="flex items-center gap-2">
                  <i class="fas fa-tag text-emerald-600"></i>
                  <span class="text-sm text-emerald-700">Tienes descuentos disponibles</span>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">TRÁMITE</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">PRECIO</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">DESCUENTO</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ACCIONES</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($lista as $index => $item): 
                $id = (int)$item['id'];
                $nombre = htmlspecialchars((string)($item['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
                $descripcion = htmlspecialchars((string)($item['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
                $precio = isset($item['precio']) && $item['precio'] > 0 ? number_format((float)$item['precio'], 2, '.', '') : number_format(0.00, 2, '.', '');

                $tieneDescuento = false;
                if (!$descuentoBloqueado && !empty($descuentosActivos)) {
                  foreach ($descuentosActivos as $d) {
                    $tipoPagoId = isset($d['tipo_pago_id']) ? (int)$d['tipo_pago_id'] : 0;
                    $montoRes = isset($d['monto_resolucion']) ? (float)$d['monto_resolucion'] : 0;
                    if ($montoRes > 0 && ($tipoPagoId === 0 || $tipoPagoId === $id)) {
                      $tieneDescuento = true;
                      break;
                    }
                  }
                }
              ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      #<?= $id ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="font-semibold text-gray-900"><?= $nombre ?></div>
                    <?php if ($descripcion !== ''): ?>
                      <div class="text-sm text-gray-500 mt-1 max-w-md"><?= $descripcion ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-lg font-bold text-gray-900">S/ <?= $precio ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php if ($tieneDescuento): ?>
                      <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-emerald-100 to-green-100 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-tag text-xs"></i>
                        Descuento aplicable
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                        Sin descuento
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <button 
                      onclick="mostrarResumenPago('<?= $id ?>', '<?= $nombre ?>', <?= $precio ?>, <?= $id ?>)"
                      class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2">
                      <i class="fas fa-credit-card"></i>
                      Pagar ahora
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (empty($lista)): ?>
          <div class="text-center py-12">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-credit-card text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No hay trámites disponibles</h3>
            <p class="text-gray-500 max-w-md mx-auto">En este momento no hay trámites disponibles para pago. Intenta más tarde.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Resumen de pago (oculto por defecto) -->
    <div id="resumen-pago" class="hidden animate-slide-up">
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-blue-100">
        <!-- Header del resumen -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 border-b border-blue-100">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                <i class="fas fa-file-invoice-dollar text-white"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-gray-800">Resumen de Pago</h2>
                <p class="text-sm text-gray-600">Revisa y confirma los detalles de tu pago</p>
              </div>
            </div>
            <button onclick="volverListaPagos()" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors flex items-center gap-2">
              <i class="fas fa-arrow-left"></i>
              Volver a trámites
            </button>
          </div>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Panel izquierdo: Resumen del trámite -->
            <div class="lg:col-span-2">
              <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                  <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-blue-600"></i>
                    Detalles del Trámite
                  </h3>
                  
                  <div class="space-y-4">
                    <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-gray-100">
                      <div>
                        <div class="text-sm text-gray-600">Trámite seleccionado</div>
                        <div id="resumen-concept" class="font-bold text-gray-800 text-lg">—</div>
                      </div>
                      <div class="text-right">
                        <div class="text-sm text-gray-600">Precio base</div>
                        <div id="resumen-precio" class="font-bold text-gray-800 text-lg">S/ 0.00</div>
                      </div>
                    </div>

                    <!-- Sección de Descuentos -->
                    <div id="resumen-descuentos-section" class="hidden">
                      <div class="bg-gradient-to-r from-emerald-50 to-green-50 rounded-lg border border-emerald-200 p-4">
                        <h4 class="font-semibold text-emerald-800 mb-3 flex items-center gap-2">
                          <i class="fas fa-tag"></i>
                          Descuentos Aplicables
                        </h4>
                        <div id="resumen-descuentos-list" class="space-y-2"></div>
                      </div>
                    </div>

                    <!-- Total a pagar -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200 p-4">
                      <div class="flex justify-between items-center">
                        <div>
                          <div class="font-semibold text-gray-800">Total a pagar</div>
                          <div class="text-sm text-gray-600">Incluye descuentos aplicados</div>
                        </div>
                        <div id="resumen-total" class="text-2xl font-bold text-emerald-600">S/ 0.00</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Información del estudiante -->
                <div class="p-6">
                  <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-user-graduate text-blue-600"></i>
                    Datos del Estudiante
                  </h3>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">DNI</label>
                      <div class="flex items-center gap-2 px-4 py-3 bg-white border border-gray-300 rounded-lg">
                        <i class="fas fa-id-card text-gray-400"></i>
                        <input type="text" id="resumen-dni" value="<?= $dniEstudianteSesion ?>" readonly class="flex-1 bg-transparent outline-none text-gray-800" />
                      </div>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo</label>
                      <div class="flex items-center gap-2 px-4 py-3 bg-white border border-gray-300 rounded-lg">
                        <i class="fas fa-user text-gray-400"></i>
                        <input type="text" id="resumen-nombre" value="<?= $nombreEstudianteSesion ?>" readonly class="flex-1 bg-transparent outline-none text-gray-800" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Panel derecho: Métodos de pago y confirmación -->
            <div class="space-y-6">
              <!-- Métodos de pago -->
              <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <i class="fas fa-credit-card text-blue-600"></i>
                  Método de Pago
                </h3>
                
                <div class="space-y-3" id="metodos-pago-container">
                  <?php foreach ($metodosPago as $metodo): ?>
                    <label class="method-card flex items-center gap-3 p-4 border border-gray-300 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md">
                      <input type="radio" name="metodo_pago" value="<?= (int)$metodo['id'] ?>" class="w-5 h-5 text-blue-600" required>
                      <div class="flex-1">
                        <div class="font-medium text-gray-800"><?= htmlspecialchars($metodo['nombre']) ?></div>
                        <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($metodo['descripcion'] ?? '') ?></div>
                      </div>
                      <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-wallet text-blue-600"></i>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>

                <!-- Botón de confirmación -->
                <div class="mt-6">
                  <button type="button" onclick="procesarPago()" 
                          id="btn-procesar-pago"
                          class="w-full px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 glow-effect flex items-center justify-center gap-3">
                    <i class="fas fa-lock"></i>
                    Confirmar y Pagar
                    <span id="resumen-total-btn" class="font-bold">S/ 0.00</span>
                  </button>
                  
                  <p class="text-xs text-gray-500 text-center mt-3">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Tu pago está protegido con encriptación SSL
                  </p>
                </div>
              </div>

              <!-- Información adicional -->
              <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600"></i>
                  </div>
                  <div class="text-sm text-blue-800">
                    <div class="font-semibold mb-1">Información importante</div>
                    <ul class="list-disc list-inside space-y-1">
                      <li>Recibirás un comprobante por email</li>
                      <li>El procesamiento toma 1-2 minutos</li>
                      <li>Guarda tu número de operación</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Mensaje de resultado -->
          <div id="resumen-mensaje" class="hidden mt-6"></div>
        </div>
      </div>
    </div>
  </main>

  <script>
    let pagoActual = { numero: '', concepto: '', precio: 0, id: 0 };
    let montoDescuentoGlobal = 0;
    const descuentoBloqueado = <?php echo json_encode($descuentoBloqueado); ?>;

    // Configurar selección de métodos de pago
    document.addEventListener('DOMContentLoaded', function() {
      const methodCards = document.querySelectorAll('.method-card');
      methodCards.forEach(card => {
        const radio = card.querySelector('input[type="radio"]');
        radio.addEventListener('change', function() {
          methodCards.forEach(c => c.classList.remove('selected'));
          if (this.checked) {
            card.classList.add('selected');
          }
        });
      });
    });

    function mostrarResumenPago(numero, concepto, precio, id) {
      pagoActual = { numero, concepto, precio: parseFloat(precio), id: parseInt(id) };
      
      const montoTotal = pagoActual.precio;
      const descuentos = <?php echo json_encode($descuentosActivos); ?>;
      let montoDescuento = 0;
      let descuentoDetalles = [];
      
      // Calcular descuentos
      if (!descuentoBloqueado && descuentos && descuentos.length > 0) {
        const aplicables = descuentos.filter(d => !d.tipo_pago_id || parseInt(d.tipo_pago_id) === pagoActual.id);
        const montoFijo = aplicables.length > 0
          ? Math.max(...aplicables.map(d => parseFloat(d.monto_resolucion || 0)))
          : 0;
        montoDescuento = Math.min(montoTotal, Math.max(0, montoFijo));
        
        descuentoDetalles = aplicables.map(d => ({
          resolucion: d.numero_resolucion,
          titulo: d.titulo,
          monto: Math.min(montoTotal, Math.max(0, parseFloat(d.monto_resolucion || 0)))
        }));
        
        // Mostrar sección de descuentos
        const descuentosSection = document.getElementById('resumen-descuentos-section');
        const descuentosList = document.getElementById('resumen-descuentos-list');
        
        descuentosSection.classList.remove('hidden');
        descuentosList.innerHTML = descuentoDetalles.map(d => 
          `<div class="flex items-center justify-between p-2 bg-white rounded border border-emerald-100">
            <div>
              <div class="text-xs text-emerald-700 font-medium">${d.resolucion}</div>
              <div class="text-xs text-gray-600 truncate max-w-[200px]">${d.titulo}</div>
            </div>
            <div class="text-sm font-bold text-emerald-600">-S/ ${d.monto.toFixed(2)}</div>
          </div>`
        ).join('');
      } else {
        document.getElementById('resumen-descuentos-section').classList.add('hidden');
      }
      
      montoDescuentoGlobal = montoDescuento;
      const montoFinal = montoTotal - montoDescuento;
      
      // Actualizar datos del resumen
      document.getElementById('resumen-concept').textContent = concepto;
      document.getElementById('resumen-precio').textContent = 'S/ ' + montoTotal.toFixed(2);
      document.getElementById('resumen-total').textContent = 'S/ ' + montoFinal.toFixed(2);
      document.getElementById('resumen-total-btn').textContent = 'S/ ' + montoFinal.toFixed(2);
      
      // Scroll suave y mostrar resumen
      document.getElementById('lista-pagos').classList.add('hidden');
      const resumenSection = document.getElementById('resumen-pago');
      resumenSection.classList.remove('hidden');
      resumenSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      
      // Resetear formulario
      document.querySelectorAll('.method-card').forEach(card => card.classList.remove('selected'));
      document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => radio.checked = false);
      document.getElementById('resumen-mensaje').classList.add('hidden');
    }

    function volverListaPagos() {
      document.getElementById('resumen-pago').classList.add('hidden');
      document.getElementById('lista-pagos').classList.remove('hidden');
      document.getElementById('lista-pagos').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function mostrarMensaje(mensaje, tipo) {
      const mensajeDiv = document.getElementById('resumen-mensaje');
      mensajeDiv.innerHTML = `
        <div class="p-4 rounded-xl border ${tipo === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'}">
          <div class="flex items-center gap-3">
            <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-lg"></i>
            <div>
              <div class="font-semibold">${tipo === 'success' ? '¡Éxito!' : 'Error'}</div>
              <div class="text-sm mt-1">${mensaje}</div>
            </div>
          </div>
        </div>
      `;
      mensajeDiv.classList.remove('hidden');
    }

    function procesarPago() {
      const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
      if (!metodoPago) {
        mostrarMensaje('Por favor, selecciona un método de pago.', 'error');
        return;
      }
      
      const montoFinal = parseFloat(pagoActual.precio) - montoDescuentoGlobal;
      const submitButton = document.getElementById('btn-procesar-pago');
      const originalContent = submitButton.innerHTML;
      
      submitButton.disabled = true;
      submitButton.innerHTML = `
        <div class="flex items-center gap-3">
          <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <span>Procesando pago...</span>
        </div>
      `;
      submitButton.classList.remove('glow-effect');
      
      // Calcular ruta correcta
      const currentPath = window.location.pathname;
      let apiPath = currentPath.includes('/views/') ? '../controller/procesarPago.php' : '../../controller/procesarPago.php';
      
      const formData = new FormData();
      formData.append('numero_pago', pagoActual.numero);
      formData.append('concepto', pagoActual.concepto);
      formData.append('precio', pagoActual.precio);
      formData.append('metodo_pago', metodoPago.value);
      formData.append('dni_estudiante', document.getElementById('resumen-dni').value);
      formData.append('nombre_estudiante', document.getElementById('resumen-nombre').value);
      formData.append('monto_descuento', montoDescuentoGlobal);
      formData.append('monto_total', montoFinal);
      
      fetch(apiPath, {
        method: 'POST',
        body: formData
      })
      .then(async (response) => {
        let data = null;
        try {
          data = await response.json();
        } catch (e) {
          data = null;
        }

        if (!response.ok) {
          const msg = (data && (data.error || data.message)) ? (data.error || data.message) : 'Error en la respuesta del servidor';
          throw new Error(msg);
        }

        return data;
      })
      .then(data => {
        if (data.success) {
          mostrarMensaje('¡Pago registrado correctamente! Redirigiendo...', 'success');
          // Mostrar detalles del pago exitoso
          setTimeout(() => {
            const confirmMessage = `
              <div class="bg-white rounded-xl border border-emerald-200 p-6 mt-4">
                <div class="text-center">
                  <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-emerald-600 text-2xl"></i>
                  </div>
                  <h3 class="text-lg font-bold text-gray-800 mb-2">¡Pago Completado!</h3>
                  <p class="text-gray-600 mb-4">Tu pago ha sido procesado exitosamente</p>
                  <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                      <div class="text-gray-600">Trámite:</div>
                      <div class="font-medium text-gray-800">${pagoActual.concepto}</div>
                      <div class="text-gray-600">Monto:</div>
                      <div class="font-bold text-emerald-600">S/ ${montoFinal.toFixed(2)}</div>
                      <div class="text-gray-600">Método:</div>
                      <div class="font-medium text-gray-800">${metodoPago.parentElement.querySelector('.font-medium').textContent}</div>
                    </div>
                  </div>
                  <p class="text-xs text-gray-500">Redirigiendo a la página principal...</p>
                </div>
              </div>
            `;
            document.getElementById('resumen-mensaje').innerHTML = confirmMessage;
            
            // Redirigir después de mostrar confirmación
            setTimeout(() => {
              window.location.reload();
            }, 3000);
          }, 500);
        } else {
          mostrarMensaje(data.message || 'Error al procesar el pago.', 'error');
          submitButton.classList.add('glow-effect');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        mostrarMensaje(error?.message || 'Error de conexión. Por favor, verifica tu conexión a internet.', 'error');
        submitButton.classList.add('glow-effect');
      })
      .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = originalContent;
      });
    }
  </script>
</body>
</html>