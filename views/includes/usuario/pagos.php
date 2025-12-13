
<?php
require_once __DIR__ . '/../../../config/conexion.php';
$pdo = Conexion::getInstance()->getConnection();

// Función para obtener descuentos activos del estudiante
function obtenerDescuentosActivos($pdo, $estudianteId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.id as beneficiario_id,
                b.porcentaje_descuento,
                r.numero_resolucion,
                r.titulo,
                r.monto_descuento as monto_resolucion
            FROM beneficiarios b
            INNER JOIN resoluciones r ON b.resoluciones = r.id
            WHERE b.estudiante = :estudiante_id 
            AND b.activo = 1 
            AND (b.fecha_fin IS NULL OR b.fecha_fin >= CURDATE())
            AND (b.fecha_inicio IS NULL OR b.fecha_inicio <= CURDATE())
            ORDER BY b.porcentaje_descuento DESC
        ");
        $stmt->execute([':estudiante_id' => $estudianteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo descuentos: " . $e->getMessage());
        return [];
    }
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

// DEBUG: Agregar información de depuración
echo "<!-- DEBUG: ID Estudiante: " . ($estudianteIdActual ?? 'NULL') . " -->";
echo "<!-- DEBUG: Descuentos Activos: " . print_r($descuentosActivos, true) . " -->";

// Verificar y agregar campo UIT si no existe
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM tipo_pago LIKE 'uit'");
    $hasUIT = $stmt->rowCount() > 0;
    
    if (!$hasUIT) {
        // Agregar campo UIT a la tabla
        $pdo->exec("ALTER TABLE tipo_pago ADD COLUMN uit DECIMAL(10,2) DEFAULT 0.00 AFTER descripcion");
    }
} catch (Exception $e) {
    error_log("Error verificando campo UIT: " . $e->getMessage());
}

// Obtener TODOS los datos con UIT - mostrar todos los conceptos de pago ordenados por ID
try {
    $stmt = $pdo->query("
        SELECT id, nombre, descripcion, COALESCE(uit, 0.00) as uit 
        FROM tipo_pago 
        ORDER BY id ASC
    ");
    $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback si hay error
    try {
        $stmt = $pdo->query("
            SELECT id, nombre, descripcion, COALESCE(uit, 0.00) as uit 
            FROM tipo_pago 
            ORDER BY id ASC
        ");
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        // Último fallback
        $stmt = $pdo->query("SELECT id, nombre, descripcion FROM tipo_pago ORDER BY id ASC");
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lista as &$item) {
            $item['uit'] = 0.00;
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
</head>

<body class="flex bg-gray-100 min-h-screen">
  <main class="flex-1 p-8">
    <div class="bg-white shadow rounded-xl p-6 overflow-x-auto">
      <table class="min-w-full bg-white border-collapse">
        <thead>
          <tr class="bg-gray-200">
            <th class="py-3 px-4 border border-gray-300 text-left font-semibold text-gray-700">ID</th>
            <th class="py-3 px-4 border border-gray-300 text-left font-semibold text-gray-700">DESCRIPCIÓN</th>
            <th class="py-3 px-4 border border-gray-300 text-left font-semibold text-gray-700">UIT</th>
            <th class="py-3 px-4 border border-gray-300 text-center font-semibold text-gray-700">ACCIONES</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista as $index => $item): 
            $id = (int)$item['id'];
            $descripcion = htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8');
            $uit = isset($item['uit']) && $item['uit'] > 0 ? number_format((float)$item['uit'], 2, '.', '') : number_format(0.00, 2, '.', '');
          ?>
            <tr class="hover:bg-gray-50 border-b border-gray-200">
              <td class="py-3 px-4 border border-gray-300 text-gray-700"><?= $id ?></td>
              <td class="py-3 px-4 border border-gray-300 text-gray-700"><?= $descripcion ?></td>
              <td class="py-3 px-4 border border-gray-300 text-gray-700">S/ <?= $uit ?></td>
              <td class="py-3 px-4 border border-gray-300 text-center">
                <button 
                  onclick="abrirModalPago('<?= $id ?>', '<?= $descripcion ?>', <?= $uit ?>, <?= $id ?>)"
                  class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">
                  <i class="fas fa-credit-card mr-2"></i> Pagar
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Modal de Pago -->
  <div id="modalPago" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 max-w-5xl w-full max-h-[90vh] overflow-y-auto">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
        
        <!-- Resumen de pago -->
        <div class="space-y-6">
          <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">Resumen de Pago</h2>
            <button onclick="cerrarModalPago()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
          </div>

          <div class="bg-gray-50 border rounded-2xl p-6 shadow-sm space-y-3">
            <p class="flex justify-between text-gray-700"><span>Concepto</span><span id="modal-concept-name" class="font-medium">—</span></p>
            <p class="flex justify-between text-gray-700"><span>UIT</span><span id="modal-uit-value" class="font-medium">S/ 0.00</span></p>
            
            <!-- Sección de Descuentos -->
            <div id="modal-descuentos-section" class="hidden">
              <div class="border-t pt-3 mt-3">
                <p class="text-sm font-semibold text-gray-600 mb-2">Descuentos Aplicables:</p>
                <div id="modal-descuentos-list" class="space-y-1"></div>
              </div>
            </div>
            
            <p class="flex justify-between text-gray-700"><span>Descuento Total</span><span id="modal-discount-value" class="font-medium text-green-600">S/ 0.00</span></p>
            <p class="flex justify-between font-semibold text-gray-800 text-lg border-t pt-3"><span>Total a Pagar</span><span id="modal-total-amount" class="text-blue-600">S/ 0.00</span></p>
          </div>
        </div>

        <!-- Selección de Método de Pago -->
        <div class="bg-gray-50 border rounded-3xl p-8 shadow-sm">
          <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Selecciona Método de Pago</h2>

          <!-- Mensaje de error/success -->
          <div id="modal-mensaje-pago" class="hidden mb-4 p-4 rounded-lg"></div>

          <form id="modal-form-pago" class="space-y-6">
            <!-- Métodos de pago seleccionables -->
            <div class="space-y-4">
              <label class="block text-sm font-medium mb-3 text-gray-700 text-center">Elige tu método de pago preferido</label>
              
              <div class="grid grid-cols-1 gap-4">
                <!-- Opción Yape -->
                <label class="modal-metodo-pago-option relative flex items-center p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all" data-metodo="yape">
                  <input type="radio" name="modal_metodo_pago" value="yape" class="sr-only" required>
                  <div class="flex items-center gap-4 w-full">
                    <div class="flex-shrink-0">
                      <img src="../../../assets/img/yape.png" alt="Yape" class="w-20 h-12 object-contain" onerror="this.style.display='none'">
                    </div>
                    <div class="flex-1">
                      <span class="font-semibold text-gray-800">Yape</span>
                      <p class="text-sm text-gray-600">Pago móvil rápido y seguro</p>
                    </div>
                    <div class="flex-shrink-0">
                      <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center modal-metodo-pago-check">
                        <div class="w-3 h-3 bg-blue-600 rounded-full hidden"></div>
                      </div>
                    </div>
                  </div>
                </label>

                <!-- Opción Plin -->
                <label class="modal-metodo-pago-option relative flex items-center p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all" data-metodo="plin">
                  <input type="radio" name="modal_metodo_pago" value="plin" class="sr-only" required>
                  <div class="flex items-center gap-4 w-full">
                    <div class="flex-shrink-0">
                      <img src="../../../assets/img/plin.png" alt="Plin" class="w-20 h-12 object-contain" onerror="this.style.display='none'">
                    </div>
                    <div class="flex-1">
                      <span class="font-semibold text-gray-800">Plin</span>
                      <p class="text-sm text-gray-600">Pago móvil bancario</p>
                    </div>
                    <div class="flex-shrink-0">
                      <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center modal-metodo-pago-check">
                        <div class="w-3 h-3 bg-blue-600 rounded-full hidden"></div>
                      </div>
                    </div>
                  </div>
                </label>

                <!-- Opción Transferencia -->
                <label class="modal-metodo-pago-option relative flex items-center p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all" data-metodo="transferencia">
                  <input type="radio" name="modal_metodo_pago" value="transferencia" class="sr-only" required>
                  <div class="flex items-center gap-4 w-full">
                    <div class="flex-shrink-0">
                      <div class="w-20 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-2xl"></i>
                      </div>
                    </div>
                    <div class="flex-1">
                      <span class="font-semibold text-gray-800">Transferencia Bancaria</span>
                      <p class="text-sm text-gray-600">Transferencia electrónica</p>
                    </div>
                    <div class="flex-shrink-0">
                      <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center modal-metodo-pago-check">
                        <div class="w-3 h-3 bg-blue-600 rounded-full hidden"></div>
                      </div>
                    </div>
                  </div>
                </label>

                <!-- Opción Depósito -->
                <label class="modal-metodo-pago-option relative flex items-center p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all" data-metodo="deposito">
                  <input type="radio" name="modal_metodo_pago" value="deposito" class="sr-only" required>
                  <div class="flex items-center gap-4 w-full">
                    <div class="flex-shrink-0">
                      <div class="w-20 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-building text-white text-2xl"></i>
                      </div>
                    </div>
                    <div class="flex-1">
                      <span class="font-semibold text-gray-800">Depósito en Banco</span>
                      <p class="text-sm text-gray-600">Pago en cuenta bancaria</p>
                    </div>
                    <div class="flex-shrink-0">
                      <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center modal-metodo-pago-check">
                        <div class="w-3 h-3 bg-blue-600 rounded-full hidden"></div>
                      </div>
                    </div>
                  </div>
                </label>

                <!-- Opción Efectivo -->
                <label class="modal-metodo-pago-option relative flex items-center p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all" data-metodo="efectivo">
                  <input type="radio" name="modal_metodo_pago" value="efectivo" class="sr-only" required>
                  <div class="flex items-center gap-4 w-full">
                    <div class="flex-shrink-0">
                      <div class="w-20 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-white text-2xl"></i>
                      </div>
                    </div>
                    <div class="flex-1">
                      <span class="font-semibold text-gray-800">Efectivo</span>
                      <p class="text-sm text-gray-600">Pago físico directo</p>
                    </div>
                    <div class="flex-shrink-0">
                      <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center modal-metodo-pago-check">
                        <div class="w-3 h-3 bg-blue-600 rounded-full hidden"></div>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Formulario de datos del estudiante (se muestra después de seleccionar método de pago) -->
            <div id="formulario-estudiante" class="hidden space-y-4 mt-4 pt-4 border-t">
              <h3 class="font-semibold text-gray-800 mb-3">Datos del Estudiante</h3>
              
              <div>
                <label class="block text-sm font-medium mb-2 text-gray-700">DNI del Estudiante</label>
                <div class="relative">
                  <input 
                    type="text" 
                    id="modal-dni-estudiante"
                    name="dni_estudiante"
                    maxlength="8"
                    placeholder="Ingrese DNI (8 dígitos)"
                    class="w-full border rounded-lg p-3 pr-10 focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm transition border-gray-300"
                    required
                    oninput="this.value = this.value.replace(/\D/g, '')">
                  <span id="modal-dni-loader" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-spinner fa-spin text-blue-500"></i>
                  </span>
                  <span id="modal-dni-check" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-check-circle text-green-500"></i>
                  </span>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium mb-2 text-gray-700">Nombre Completo</label>
                <input 
                  type="text" 
                  id="modal-nombre-estudiante"
                  name="nombre_estudiante"
                  placeholder="Nombre completo del estudiante"
                  class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm transition border-gray-300"
                  required>
              </div>
            </div>

            <button type="submit" id="modal-pay-button" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-6 py-3 rounded-xl w-full mt-4 transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
              <span id="modal-pay-button-text">
                <i class="fas fa-credit-card"></i> Pagar: <span id="modal-pay-amount">S/ 0.00</span>
              </span>
              <span id="modal-pay-button-loading" class="hidden">
                <i class="fas fa-spinner fa-spin"></i> Procesando...
              </span>
            </button>
          </form>

          <p class="text-gray-500 text-sm text-center mt-6">
            Al continuar, aceptas nuestros <a href="#" class="text-blue-600 underline hover:text-blue-800">Términos y Condiciones</a> y <a href="#" class="text-blue-600 underline hover:text-blue-800">Política de Privacidad</a>.
          </p>
        </div>

      </div>
    </div>
  </div>

  <style>
    .modal-metodo-pago-option.selected {
      border-color: #2563eb !important;
      background-color: #eff6ff !important;
    }
    .modal-metodo-pago-option.selected .modal-metodo-pago-check {
      border-color: #2563eb !important;
    }
    .modal-metodo-pago-option.selected .modal-metodo-pago-check > div {
      display: block !important;
    }
  </style>

  <script>
    let pagoActual = { numero: '', concepto: '', uit: 0, id: 0 };

    // Filtro de búsqueda
    document.addEventListener('DOMContentLoaded', function() {
      const filtroBusqueda = document.getElementById('filtroBusqueda');
      const btnLimpiar = document.getElementById('btnLimpiarFiltro');
      const tarjetas = document.querySelectorAll('.concepto-card');

      function filtrarTarjetas() {
        const texto = filtroBusqueda.value.toLowerCase();
        tarjetas.forEach(tarjeta => {
          const textoTarjeta = tarjeta.textContent.toLowerCase();
          tarjeta.style.display = textoTarjeta.includes(texto) ? '' : 'none';
        });
      }

      if (filtroBusqueda) {
        filtroBusqueda.addEventListener('input', filtrarTarjetas);
      }

      if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
          filtroBusqueda.value = '';
          tarjetas.forEach(tarjeta => tarjeta.style.display = '');
        });
      }
    });

    function abrirModalPago(numero, concepto, uit, id) {
      pagoActual = { numero, concepto, uit: parseFloat(uit), id: parseInt(id) };
      
      // El monto total es directamente el valor UIT (sin multiplicar)
      const montoTotal = pagoActual.uit;
      
      // Calcular descuentos
      const descuentos = <?php echo json_encode($descuentosActivos); ?>;
      let montoDescuento = 0;
      let descuentoDetalles = [];
      
      if (descuentos && descuentos.length > 0) {
        console.log('Descuentos encontrados:', descuentos);
        
        // Aplicar el descuento más alto (o sumar si se permite múltiples)
        const porcentajeDescuento = Math.max(...descuentos.map(d => parseFloat(d.porcentaje_descuento)));
        montoDescuento = montoTotal * (porcentajeDescuento / 100);
        
        console.log('Porcentaje descuento:', porcentajeDescuento);
        console.log('Monto total:', montoTotal);
        console.log('Monto descuento:', montoDescuento);
        
        // Preparar detalles para mostrar
        descuentoDetalles = descuentos.map(d => ({
          resolucion: d.numero_resolucion,
          titulo: d.titulo,
          porcentaje: parseFloat(d.porcentaje_descuento),
          monto: montoTotal * (parseFloat(d.porcentaje_descuento) / 100)
        }));
        
        // Mostrar sección de descuentos
        const descuentosSection = document.getElementById('modal-descuentos-section');
        const descuentosList = document.getElementById('modal-descuentos-list');
        
        descuentosSection.classList.remove('hidden');
        descuentosList.innerHTML = descuentoDetalles.map(d => 
          `<div class="flex justify-between text-sm">
            <span class="text-gray-600">${d.resolucion} - ${d.titulo} (${d.porcentaje}%)</span>
            <span class="text-green-600">-S/ ${d.monto.toFixed(2)}</span>
          </div>`
        ).join('');
      } else {
        // Ocultar sección de descuentos
        document.getElementById('modal-descuentos-section').classList.add('hidden');
      }
      
      const montoFinal = montoTotal - montoDescuento;
      
      // Actualizar datos del modal
      document.getElementById('modal-concept-name').textContent = concepto;
      document.getElementById('modal-uit-value').textContent = 'S/ ' + montoTotal.toFixed(2);
      document.getElementById('modal-discount-value').textContent = 'S/ ' + montoDescuento.toFixed(2);
      document.getElementById('modal-total-amount').textContent = 'S/ ' + montoFinal.toFixed(2);
      document.getElementById('modal-pay-amount').textContent = 'S/ ' + montoFinal.toFixed(2);
      
      // Limpiar formulario
      document.getElementById('modal-form-pago').reset();
      document.getElementById('modal-mensaje-pago').classList.add('hidden');
      document.getElementById('formulario-estudiante').classList.add('hidden');
      const metodoOptions = document.querySelectorAll('.modal-metodo-pago-option');
      metodoOptions.forEach(opt => {
        opt.classList.remove('selected');
        const check = opt.querySelector('.modal-metodo-pago-check > div');
        if (check) check.classList.add('hidden');
      });
      
      // Mostrar modal
      document.getElementById('modalPago').classList.remove('hidden');
    }

    function cerrarModalPago() {
      document.getElementById('modalPago').classList.add('hidden');
      document.getElementById('modal-form-pago').reset();
      document.getElementById('modal-mensaje-pago').classList.add('hidden');
      document.getElementById('formulario-estudiante').classList.add('hidden');
    }

    // Manejar selección de método de pago en el modal
    document.addEventListener('DOMContentLoaded', function() {
      const metodoOptions = document.querySelectorAll('.modal-metodo-pago-option');
      metodoOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        
        radio.addEventListener('change', function() {
          metodoOptions.forEach(opt => {
            opt.classList.remove('selected');
            const check = opt.querySelector('.modal-metodo-pago-check > div');
            if (check) check.classList.add('hidden');
          });
          
          if (radio.checked) {
            option.classList.add('selected');
            const check = option.querySelector('.modal-metodo-pago-check > div');
            if (check) check.classList.remove('hidden');
            
            // Mostrar formulario de estudiante cuando se selecciona un método de pago
            document.getElementById('formulario-estudiante').classList.remove('hidden');
          }
        });
        
        option.addEventListener('click', function(e) {
          if (e.target.tagName !== 'INPUT') {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
          }
        });
      });

      // Manejar envío del formulario
      const formPago = document.getElementById('modal-form-pago');
      if (formPago) {
        formPago.addEventListener('submit', async function(e) {
          e.preventDefault();
          
          const metodoPago = document.querySelector('input[name="modal_metodo_pago"]:checked');
          const mensajePago = document.getElementById('modal-mensaje-pago');
          const payButton = document.getElementById('modal-pay-button');
          const payButtonText = document.getElementById('modal-pay-button-text');
          const payButtonLoading = document.getElementById('modal-pay-button-loading');
          
          function mostrarMensaje(texto, tipo = 'error') {
            mensajePago.className = `mb-4 p-4 rounded-lg ${tipo === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'}`;
            mensajePago.innerHTML = `<i class="fas ${tipo === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'} mr-2"></i>${texto}`;
            mensajePago.classList.remove('hidden');
          }

          if (!metodoPago) {
            mostrarMensaje('Por favor, selecciona un método de pago', 'error');
            return;
          }

          // Validar datos del estudiante
          const dniEstudiante = document.getElementById('modal-dni-estudiante').value.trim();
          const nombreEstudiante = document.getElementById('modal-nombre-estudiante').value.trim();

          if (!dniEstudiante || dniEstudiante.length !== 8) {
            mostrarMensaje('Por favor, ingrese un DNI válido (8 dígitos)', 'error');
            return;
          }

          if (!nombreEstudiante || nombreEstudiante.length < 3) {
            mostrarMensaje('Por favor, ingrese el nombre completo del estudiante', 'error');
            return;
          }

          // El monto es directamente el valor UIT
          const monto = pagoActual.uit;
          
          // Calcular descuento
          const descuentos = <?php echo json_encode($descuentosActivos); ?>;
          let montoDescuento = 0;
          
          if (descuentos && descuentos.length > 0) {
            const porcentajeDescuento = Math.max(...descuentos.map(d => parseFloat(d.porcentaje_descuento)));
            montoDescuento = monto * (porcentajeDescuento / 100);
          }
          
          if (monto <= 0) {
            mostrarMensaje('El monto debe ser mayor a cero', 'error');
            return;
          }

          // Deshabilitar botón y mostrar loading
          payButton.disabled = true;
          payButtonText.classList.add('hidden');
          payButtonLoading.classList.remove('hidden');

          // Determinar ruta del controlador
          const currentPath = window.location.pathname;
          let baseUrl = '';
          if (currentPath.includes('/views/')) {
            baseUrl = currentPath.substring(0, currentPath.indexOf('/views/'));
          }

          try {
            const response = await fetch(baseUrl + '/controller/procesarPago.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                concepto: pagoActual.concepto,
                monto: monto,
                monto_descuento: montoDescuento,
                metodo_pago: metodoPago.value,
                tipo_pago_id: pagoActual.id,
                numero: pagoActual.numero,
                uit: pagoActual.uit,
                dni_estudiante: dniEstudiante,
                nombre_estudiante: nombreEstudiante
              })
            });

            const data = await response.json();

            if (data.success) {
              mostrarMensaje(data.message || 'Pago procesado correctamente. Redirigiendo...', 'success');
              setTimeout(() => {
                if (data.redirect) {
                  window.location.href = data.redirect;
                } else {
                  window.location.href = window.location.href.split('?')[0] + '?pagina=comprobantes';
                }
              }, 2000);
            } else {
              mostrarMensaje(data.error || 'Error al procesar el pago', 'error');
              payButton.disabled = false;
              payButtonText.classList.remove('hidden');
              payButtonLoading.classList.add('hidden');
            }
          } catch (error) {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión. Por favor, intente nuevamente.', 'error');
            payButton.disabled = false;
            payButtonText.classList.remove('hidden');
            payButtonLoading.classList.add('hidden');
          }
        });
      }

      // Cerrar modal al hacer clic fuera de él
      document.getElementById('modalPago').addEventListener('click', function(e) {
        if (e.target.id === 'modalPago') {
          cerrarModalPago();
        }
      });

      // Autocompletar nombre cuando se ingresa DNI válido
      const modalDniInput = document.getElementById('modal-dni-estudiante');
      const modalNombreInput = document.getElementById('modal-nombre-estudiante');
      const modalDniLoader = document.getElementById('modal-dni-loader');
      const modalDniCheck = document.getElementById('modal-dni-check');
      
      if (modalDniInput) {
        modalDniInput.addEventListener('input', function(e) {
          const dni = e.target.value.trim();
          
          // Si se ingresaron 8 dígitos, buscar estudiante
          if (dni.length === 8) {
            buscarEstudiantePorDNI(dni);
          } else if (dni.length < 8) {
            // Limpiar nombre si se borra el DNI
            modalNombreInput.value = '';
            modalDniLoader.classList.add('hidden');
            modalDniCheck.classList.add('hidden');
            modalDniInput.style.borderColor = '';
            modalNombreInput.style.borderColor = '';
          }
        });
      }

      function buscarEstudiantePorDNI(dni) {
        // Mostrar indicador de carga
        modalDniInput.disabled = true;
        modalDniInput.style.backgroundColor = '#f3f4f6';
        modalDniLoader.classList.remove('hidden');
        modalDniCheck.classList.add('hidden');
        
        // Calcular ruta correcta según la ubicación actual
        const currentPath = window.location.pathname;
        let apiPath;
        
        if (currentPath.includes('/views/')) {
          apiPath = '../controller/buscarEstudianteSolicitud.php';
        } else {
          apiPath = '../../controller/buscarEstudianteSolicitud.php';
        }
        
        fetch(`${apiPath}?dni=${dni}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.estudiante) {
              // Autocompletar nombre completo
              modalNombreInput.value = data.estudiante.nombre_completo || '';
              
              // Mostrar check de éxito
              modalDniLoader.classList.add('hidden');
              modalDniCheck.classList.remove('hidden');
              
              // Estilo visual de éxito
              modalNombreInput.style.borderColor = '#10b981';
              modalDniInput.style.borderColor = '#10b981';
              
              // Remover estilo después de 2 segundos
              setTimeout(() => {
                modalNombreInput.style.borderColor = '';
                modalDniInput.style.borderColor = '';
                modalDniCheck.classList.add('hidden');
              }, 2000);
            } else {
              // Limpiar nombre si no se encontró
              modalNombreInput.value = '';
              modalDniLoader.classList.add('hidden');
              modalDniCheck.classList.add('hidden');
              
              // Opcional: mostrar mensaje de advertencia
              // console.log('Estudiante no encontrado');
            }
          })
          .catch(error => {
            console.error('Error al buscar estudiante:', error);
            modalNombreInput.value = '';
            modalDniLoader.classList.add('hidden');
            modalDniCheck.classList.add('hidden');
          })
          .finally(() => {
            // Rehabilitar campo DNI
            modalDniInput.disabled = false;
            modalDniInput.style.backgroundColor = '';
          });
      }
    });
  </script>
</body>
</html>
