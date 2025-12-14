<?php
// La sesión ya está iniciada en dashboard-bienestar.php, no es necesario iniciarla aquí

// Permitir acceso para admin y bienestar
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'bienestar'])) {
    header('Location: ../../../errors/403.html');
    exit;
}

// Obtener el rol del usuario para controlar las acciones
$rolUsuario = $_SESSION['rol'] ?? '';

require_once __DIR__ . '/../../../config/conexion.php';

// Manejar mensajes de status
$status = $_GET['status'] ?? null;
$mensajeExito = null;
if ($status === 'usuario_eliminado') {
    $mensajeExito = 'Usuario eliminado correctamente';
}

// Obtener usuarios del sistema
try {
    $db = Database::getInstance()->getConnection();
    
    // Primero verificar que la tabla existe y obtener todos los usuarios sin JOIN
    // Intentar obtener usuarios con o sin columna estado
    try {
        $usuariosRaw = $db->query("
            SELECT 
                u.id,
                u.usuario,
                u.tipo,
                u.estuempleado
            FROM usuarios u
            ORDER BY u.id DESC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Si falla, intentar sin estado
        $usuariosRaw = $db->query("
            SELECT 
                id,
                usuario,
                tipo,
                estuempleado
            FROM usuarios
            ORDER BY id DESC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Luego obtener datos adicionales si hay estuempleado
    $usuarios = [];
    foreach ($usuariosRaw as $u) {
        $nombreCompleto = $u['usuario'] . ' (Usuario)';
        $correo = null;
        
        // Si tiene estuempleado, intentar obtener más datos
        if (!empty($u['estuempleado'])) {
            try {
                if ($u['tipo'] == 1) {
                    // Empleado
                    $stmt = $db->prepare("SELECT apnom_emp, mailp_emp FROM empleado WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $u['estuempleado']]);
                    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($emp) {
                        $nombreCompleto = ($emp['apnom_emp'] ?? '') . ' (Empleado)';
                        $correo = $emp['mailp_emp'] ?? null;
                    }
                } elseif ($u['tipo'] == 2) {
                    // Estudiante
                    $stmt = $db->prepare("SELECT ap_est, am_est, nom_est, mailp_est FROM estudiante WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $u['estuempleado']]);
                    $est = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($est) {
                        $nombreCompleto = trim(($est['ap_est'] ?? '') . ' ' . ($est['am_est'] ?? '') . ' ' . ($est['nom_est'] ?? '')) . ' (Estudiante)';
                        $correo = $est['mailp_est'] ?? null;
                    }
                } elseif ($u['tipo'] == 3) {
                    // Empresa
                    $stmt = $db->prepare("SELECT razon_social, email FROM empresa WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $u['estuempleado']]);
                    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($empresa) {
                        $nombreCompleto = ($empresa['razon_social'] ?? '') . ' (Empresa)';
                        $correo = $empresa['email'] ?? null;
                    }
                }
            } catch (Exception $e) {
                error_log("Error al obtener datos adicionales para usuario {$u['id']}: " . $e->getMessage());
            }
        }
        
        $usuarios[] = [
            'id' => (int)$u['id'],
            'usuario' => $u['usuario'] ?? '',
            'tipo' => (int)($u['tipo'] ?? 0),
            'estado' => $u['estado'] ?? 1, // Si no tiene estado, asumir activo
            'estuempleado' => !empty($u['estuempleado']) ? (int)$u['estuempleado'] : null,
            'nombre_completo' => $nombreCompleto,
            'correo' => $correo,
            'rol_nombre' => ($u['tipo'] == 1 ? 'Empleado' : ($u['tipo'] == 2 ? 'Estudiante' : ($u['tipo'] == 3 ? 'Empresa' : 'Sin definir')))
        ];
    }
    
    // Estadísticas
    $totalUsuarios = count($usuarios);
    $usuariosActivos = count(array_filter($usuarios, fn($u) => ($u['estado'] ?? null) == 1 || ($u['estado'] ?? null) === '1'));
    $usuariosPorRol = [];
    foreach ($usuarios as $u) {
        $rol = $u['rol_nombre'] ?? 'Sin rol';
        $usuariosPorRol[$rol] = ($usuariosPorRol[$rol] ?? 0) + 1;
    }
    
    // Debug: Log para verificar que se están obteniendo usuarios
    error_log("=== DEBUG USUARIOS ===");
    error_log("Usuarios RAW obtenidos: " . count($usuariosRaw));
    error_log("Usuarios procesados: " . count($usuarios));
    if (count($usuariosRaw) > 0) {
        error_log("Primer usuario RAW: " . json_encode($usuariosRaw[0]));
    }
    if (count($usuarios) > 0) {
        error_log("Primer usuario procesado: " . json_encode($usuarios[0]));
    }
} catch (Exception $e) {
    error_log("Error al obtener usuarios: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $usuarios = [];
    $totalUsuarios = 0;
    $usuariosActivos = 0;
    $usuariosPorRol = [];
    $errorConsulta = $e->getMessage();
}
?>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.card-anim {
    animation: fadeInUp 0.5s ease-out forwards;
}
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.25);
}
.table-row {
    transition: all 0.2s ease;
}
.table-row:hover {
    background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
    transform: scale(1.005);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.action-btn-group {
    display: flex;
    gap: 0.375rem;
    flex-wrap: wrap;
}
.action-btn {
    padding: 0.375rem 0.625rem;
    font-size: 0.6875rem;
    font-weight: 600;
    border-radius: 0.375rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    border: none;
    cursor: pointer;
    white-space: nowrap;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.action-btn:active {
    transform: translateY(0);
}
.chart-container {
    transition: all 0.3s ease;
}
.chart-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
}
</style>

<!-- ESTADÍSTICAS RÁPIDAS -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
  <div class="stat-card rounded-xl p-3 text-white card-anim shadow-lg" style="animation-delay: 0.1s">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-white/90 text-xs font-medium mb-1">Total Usuarios</p>
        <p class="text-2xl font-bold"><?= $totalUsuarios ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-users text-base"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-3 text-white card-anim shadow-lg" style="animation-delay: 0.2s">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-white/90 text-xs font-medium mb-1">Usuarios Activos</p>
        <p class="text-2xl font-bold"><?= $usuariosActivos ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-check-circle text-base"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-3 text-white card-anim shadow-lg" style="animation-delay: 0.3s">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-white/90 text-xs font-medium mb-1">Roles Diferentes</p>
        <p class="text-2xl font-bold"><?= count($usuariosPorRol) ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-user-tag text-base"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl p-3 text-white card-anim shadow-lg" style="animation-delay: 0.4s">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-white/90 text-xs font-medium mb-1">Inactivos</p>
        <p class="text-2xl font-bold"><?= $totalUsuarios - $usuariosActivos ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-user-slash text-base"></i>
      </div>
    </div>
  </div>
</section>

<!-- GRAFICOS -->
<section class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
  <div class="chart-container bg-white rounded-xl shadow-lg border border-gray-100 p-4 card-anim" style="animation-delay: 0.2s">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-chart-bar text-blue-600 text-sm"></i>
        Usuarios Nuevos
      </h3>
    </div>
    <div class="relative" style="height: 200px;">
      <canvas id="barChart"></canvas>
    </div>
  </div>

  <div class="chart-container bg-white rounded-xl shadow-lg border border-gray-100 p-4 card-anim" style="animation-delay: 0.3s">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-chart-line text-indigo-600 text-sm"></i>
        Actividad Mensual
      </h3>
    </div>
    <div class="relative" style="height: 200px;">
      <canvas id="lineChart"></canvas>
    </div>
  </div>
</section>

<!-- TABLA DE USUARIOS -->
<section class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden card-anim" style="animation-delay: 0.4s">
  <?php if (isset($mensajeExito)): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 m-4">
      <div class="flex items-center gap-2">
        <i class="fas fa-check-circle text-green-600"></i>
        <p class="text-green-700 font-semibold"><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
  <?php endif; ?>
  <?php if (isset($errorConsulta)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 m-4">
      <div class="flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-red-600"></i>
        <p class="text-red-700 font-semibold">Error al cargar usuarios: <?= htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
  <?php endif; ?>
  <!-- Debug temporal -->
  <?php if (isset($usuariosRaw)): ?>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 m-4 text-xs">
      <strong>Debug:</strong> Se encontraron <?= count($usuariosRaw) ?> usuarios en la BD. 
      Procesados: <?= count($usuarios) ?>
      <?php if (count($usuariosRaw) > 0): ?>
        <br>Primero: <?= htmlspecialchars($usuariosRaw[0]['usuario'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-4 py-3">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-bold text-white flex items-center gap-2">
        <i class="fas fa-users text-sm"></i>
        <span>Usuarios del Sistema</span>
      </h2>
      <div class="flex items-center gap-3">
        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white rounded-full text-xs font-semibold">
          <?= $totalUsuarios ?> usuarios
        </span>
      </div>
    </div>
  </div>
  
  <div class="overflow-x-auto max-h-[450px]">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 sticky top-0">
        <tr>
          <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
            <i class="fas fa-user mr-1.5 text-blue-600 text-xs"></i>Usuario
          </th>
          <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
            <i class="fas fa-id-card mr-1.5 text-blue-600 text-xs"></i>Nombre Completo
          </th>
          <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
            <i class="fas fa-envelope mr-1.5 text-blue-600 text-xs"></i>Correo
          </th>
          <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
            <i class="fas fa-user-tag mr-1.5 text-blue-600 text-xs"></i>Rol
          </th>
          <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
            <i class="fas fa-toggle-on mr-1.5 text-blue-600 text-xs"></i>Estado
          </th>
          <th class="px-3 py-2.5 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
            <i class="fas fa-cog mr-1.5 text-blue-600 text-xs"></i>Acciones
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php if (empty($usuarios)): ?>
          <tr>
            <td colspan="6" class="px-3 py-8 text-center">
              <div class="flex flex-col items-center gap-4">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-2"></i>
                <div>
                  <p class="text-gray-700 font-semibold text-base mb-2">No hay usuarios registrados</p>
                  <p class="text-gray-500 text-sm mb-4">No hay usuarios registrados en el sistema</p>
                </div>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($usuarios as $index => $usuario): ?>
          <tr class="table-row">
            <td class="px-3 py-2.5 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                  <?= strtoupper(substr($usuario['usuario'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="ml-2">
                  <div class="text-xs font-semibold text-gray-900">
                    <?= htmlspecialchars($usuario['usuario'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-3 py-2.5 whitespace-nowrap">
              <div class="text-xs text-gray-900 font-medium">
                <?= htmlspecialchars($usuario['nombre_completo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
              </div>
            </td>
            <td class="px-3 py-2.5 whitespace-nowrap">
              <?php if (!empty($usuario['correo'])): ?>
                <a href="mailto:<?= htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8') ?>" 
                   class="text-xs text-blue-600 hover:text-blue-800 hover:underline font-medium">
                  <?= htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              <?php else: ?>
                <span class="text-xs text-gray-500 font-medium">N/A</span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2.5 whitespace-nowrap">
              <?php
                $rol = strtolower($usuario['rol_nombre'] ?? '');
                $rolColors = [
                  'admin' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'icon' => 'fa-crown'],
                  'bienestar' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'fa-heart'],
                  'direccion' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'icon' => 'fa-building'],
                  'default' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => 'fa-user']
                ];
                $rolStyle = $rolColors[$rol] ?? $rolColors['default'];
              ?>
              <span class="px-2 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1 <?= $rolStyle['bg'] ?> <?= $rolStyle['text'] ?>">
                <i class="fas <?= $rolStyle['icon'] ?> text-xs"></i>
                <?= htmlspecialchars(ucfirst($usuario['rol_nombre'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
              </span>
            </td>
            <td class="px-3 py-2.5 whitespace-nowrap">
              <?php if (($usuario['estado'] ?? null) == 1 || ($usuario['estado'] ?? null) === '1'): ?>
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                  Activo
                </span>
              <?php else: ?>
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                  <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                  Inactivo
                </span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2.5 whitespace-nowrap text-center">
              <?php if ($rolUsuario === 'admin'): ?>
                <div class="action-btn-group justify-center">
                  <button onclick="editarUsuario(<?= (int)($usuario['id'] ?? 0) ?>)" 
                          class="action-btn bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1"
                          title="Editar usuario">
                    <i class="fas fa-edit text-xs"></i>
                    <span>Editar</span>
                  </button>

                  <button onclick="modificarUsuario(<?= (int)($usuario['id'] ?? 0) ?>)" 
                          class="action-btn bg-amber-500 hover:bg-amber-600 text-white text-xs px-2 py-1"
                          title="Modificar usuario">
                    <i class="fas fa-pencil-alt text-xs"></i>
                    <span>Modificar</span>
                  </button>

                  <button onclick="eliminarUsuario(<?= (int)($usuario['id'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($usuario['usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')" 
                          class="action-btn bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1"
                          title="Eliminar usuario">
                    <i class="fas fa-trash-alt text-xs"></i>
                    <span>Eliminar</span>
                  </button>
                </div>
              <?php else: ?>
                <span class="text-xs text-gray-500 italic">Solo lectura</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Configuración común para gráficos
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0,0,0,0.85)',
        padding: 12,
        titleFont: { size: 14, weight: 'bold' },
        bodyFont: { size: 13 },
        borderColor: 'rgba(255,255,255,0.1)',
        borderWidth: 1,
        cornerRadius: 8
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: '#E5E7EB', drawBorder: false },
        ticks: { color: '#6B7280', font: { size: 11 } }
      },
      x: {
        grid: { display: false },
        ticks: { color: '#6B7280', font: { size: 11 } }
      }
    }
  };

  // Gráfico de barras
  const barCtx = document.getElementById("barChart");
  if (barCtx) {
    new Chart(barCtx, {
      type: "bar",
      data: {
        labels: ["Ene", "Feb", "Mar", "Abr", "May", "Jun"],
        datasets: [{
          label: "Usuarios nuevos",
          data: [120, 200, 150, 230, 180, 210],
          backgroundColor: (ctx) => {
            const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, '#3B82F6');
            gradient.addColorStop(1, '#60A5FA');
            return gradient;
          },
          borderRadius: 8,
          borderSkipped: false,
          maxBarThickness: 50
        }]
      },
      options: chartOptions
    });
  }

  // Gráfico de líneas
  const lineCtx = document.getElementById("lineChart");
  if (lineCtx) {
    new Chart(lineCtx, {
      type: "line",
      data: {
        labels: ["Ene", "Feb", "Mar", "Abr", "May", "Jun"],
        datasets: [{
          label: "Actividad",
          data: [120, 200, 170, 230, 180, 210],
          borderColor: "#667eea",
          backgroundColor: "rgba(102, 126, 234, 0.1)",
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: "#667eea",
          pointBorderColor: "#fff",
          pointBorderWidth: 2,
          pointHoverBackgroundColor: "#764ba2",
          pointHoverBorderColor: "#fff",
          pointHoverBorderWidth: 3
        }]
      },
      options: chartOptions
    });
  }
});

// Funciones de acciones
function editarUsuario(id) {
  // Redirigir al dashboard admin para editar usuario
  // Construir la URL relativa desde la ubicación actual
  const currentPath = window.location.pathname;
  
  // Si estamos en dashboard-bienestar.php, cambiar a dashboard-admin.php
  let targetPath = currentPath;
  if (currentPath.includes('dashboard-bienestar.php')) {
    targetPath = currentPath.replace('dashboard-bienestar.php', 'dashboard-admin.php');
  } else if (currentPath.includes('/views/')) {
    // Si estamos en includes, subir un nivel y cambiar a dashboard-admin.php
    targetPath = currentPath.substring(0, currentPath.lastIndexOf('/')) + '/dashboard-admin.php';
  } else {
    // Fallback: construir desde la raíz
    targetPath = '/views/dashboard-admin.php';
  }
  
  // Construir la URL completa con parámetros
  const url = window.location.origin + targetPath + '?pagina=admin-agregar-usuario&edit=' + id;
  window.location.href = url;
}

function modificarUsuario(id) {
  // Por ahora, modificar es igual que editar (se puede cambiar después)
  // Podría abrir un modal o redirigir a otra página de edición
  editarUsuario(id);
}

function eliminarUsuario(id, usuario) {
  if (confirm(`¿Estás seguro de eliminar al usuario "${usuario}"?\n\nEsta acción no se puede deshacer.`)) {
    // Crear un formulario oculto y enviarlo
    const form = document.createElement('form');
    form.method = 'POST';
    
    // Obtener la ruta base del proyecto desde la URL actual
    const currentPath = window.location.pathname;
    
    // Extraer la ruta base (hasta /Sistema-de-pagos-IESTP-main)
    let basePath = '';
    if (currentPath.includes('/Sistema-de-pagos-IESTP-main')) {
      const match = currentPath.match(/^(.+?\/Sistema-de-pagos-IESTP-main)/);
      if (match) {
        basePath = match[1];
      }
    }
    
    // Construir la URL completa al controlador
    form.action = basePath + '/controller/eliminar-usuario.php';
    
    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'id';
    inputId.value = id;
    
    const inputConfirmar = document.createElement('input');
    inputConfirmar.type = 'hidden';
    inputConfirmar.name = 'confirmar';
    inputConfirmar.value = '1';
    
    form.appendChild(inputId);
    form.appendChild(inputConfirmar);
    document.body.appendChild(form);
    form.submit();
  }
}
</script>
