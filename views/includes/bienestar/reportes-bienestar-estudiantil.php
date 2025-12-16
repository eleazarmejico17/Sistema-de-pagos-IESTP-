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

// Reportes para Bienestar: resoluciones y solicitudes
$errorConsulta = null;

$stats = [
    'resoluciones_total' => 0,
    'resoluciones_pendientes' => 0,
    'resoluciones_activas' => 0,
    'solicitudes_total' => 0,
];

$resolucionesEstado = [
    'Activas' => 0,
    'Pendientes/No activas' => 0,
];

$solicitudesEstado = [
    'Aprobadas' => 0,
    'Rechazadas' => 0,
    'Pendientes' => 0,
];

$mensual = [
    'labels' => [],
    'res_activas' => [],
    'res_pendientes' => [],
    'sol_total' => [],
];

try {
    $db = Conexion::getInstance()->getConnection();

    // Obtener empleado_id del usuario Bienestar
    $empleadoId = null;
    $usuarioIdSesion = (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0);
    $usuarioSesion = (string)($_SESSION['usuario'] ?? '');
    if ($usuarioIdSesion > 0) {
        $stmtUser = $db->prepare('SELECT estuempleado FROM usuarios WHERE id = :id LIMIT 1');
        $stmtUser->execute([':id' => $usuarioIdSesion]);
        $empleadoId = (int)($stmtUser->fetchColumn() ?: 0);
    } elseif ($usuarioSesion !== '') {
        $stmtUser = $db->prepare('SELECT estuempleado FROM usuarios WHERE usuario = :usuario LIMIT 1');
        $stmtUser->execute([':usuario' => $usuarioSesion]);
        $empleadoId = (int)($stmtUser->fetchColumn() ?: 0);
    }
    if ($empleadoId <= 0) {
        $empleadoId = null;
    }

    // Conteos de resoluciones creadas por el empleado
    if ($empleadoId !== null) {
        $stmtTot = $db->prepare('SELECT COUNT(*) FROM resoluciones WHERE creado_por = :emp');
        $stmtTot->execute([':emp' => $empleadoId]);
        $stats['resoluciones_total'] = (int)($stmtTot->fetchColumn() ?: 0);

        $stmtAct = $db->prepare('SELECT COUNT(*) FROM resoluciones WHERE creado_por = :emp AND estado = true');
        $stmtAct->execute([':emp' => $empleadoId]);
        $stats['resoluciones_activas'] = (int)($stmtAct->fetchColumn() ?: 0);

        $stmtPen = $db->prepare('SELECT COUNT(*) FROM resoluciones WHERE creado_por = :emp AND estado = false');
        $stmtPen->execute([':emp' => $empleadoId]);
        $stats['resoluciones_pendientes'] = (int)($stmtPen->fetchColumn() ?: 0);

        $resolucionesEstado['Activas'] = $stats['resoluciones_activas'];
        $resolucionesEstado['Pendientes/No activas'] = $stats['resoluciones_pendientes'];
    }

    // Conteos de solicitudes (global, para el módulo bienestar)
    $stmtSolTot = $db->query('SELECT COUNT(*) FROM solicitudes');
    $stats['solicitudes_total'] = (int)($stmtSolTot->fetchColumn() ?: 0);

    $stmtSolEstado = $db->query("SELECT COALESCE(LOWER(TRIM(estado)), 'pendiente') as estado, COUNT(*) as total FROM solicitudes GROUP BY COALESCE(LOWER(TRIM(estado)), 'pendiente')");
    foreach ($stmtSolEstado->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $estado = (string)($row['estado'] ?? 'pendiente');
        $total = (int)($row['total'] ?? 0);
        if ($estado === 'aprobado') {
            $solicitudesEstado['Aprobadas'] += $total;
        } elseif ($estado === 'rechazado') {
            $solicitudesEstado['Rechazadas'] += $total;
        } else {
            $solicitudesEstado['Pendientes'] += $total;
        }
    }

    // Series mensuales últimos 7 meses
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $labels = [];
    $resAct = array_fill(0, 7, 0);
    $resPen = array_fill(0, 7, 0);
    $solTot = array_fill(0, 7, 0);

    // Resoluciones por mes (del empleado)
    if ($empleadoId !== null) {
        $stmtResMes = $db->prepare("
            SELECT DATE_FORMAT(creado_en, '%Y-%m') as mes, estado, COUNT(*) as total
            FROM resoluciones
            WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
              AND creado_por = :emp
            GROUP BY DATE_FORMAT(creado_en, '%Y-%m'), estado
            ORDER BY mes ASC
        ");
        $stmtResMes->execute([':emp' => $empleadoId]);
        $resMesRaw = $stmtResMes->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $resMesRaw = [];
    }

    // Solicitudes por mes (global)
    $stmtSolMes = $db->prepare("
        SELECT DATE_FORMAT(fecha_solicitud, '%Y-%m') as mes, COUNT(*) as total
        FROM solicitudes
        WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
        GROUP BY DATE_FORMAT(fecha_solicitud, '%Y-%m')
        ORDER BY mes ASC
    ");
    $stmtSolMes->execute();
    $solMesRaw = $stmtSolMes->fetchAll(PDO::FETCH_ASSOC);

    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m', strtotime("-$i months"));
        $mesNum = (int)date('n', strtotime("-$i months")) - 1;
        $labels[] = $meses[$mesNum];

        foreach ($resMesRaw as $row) {
            if (($row['mes'] ?? '') !== $fecha) continue;
            $estado = (bool)($row['estado'] ?? false);
            $total = (int)($row['total'] ?? 0);
            if ($estado) {
                $resAct[6 - $i] = $total;
            } else {
                $resPen[6 - $i] = $total;
            }
        }

        foreach ($solMesRaw as $row) {
            if (($row['mes'] ?? '') !== $fecha) continue;
            $solTot[6 - $i] = (int)($row['total'] ?? 0);
        }
    }

    $mensual = [
        'labels' => $labels,
        'res_activas' => $resAct,
        'res_pendientes' => $resPen,
        'sol_total' => $solTot,
    ];

} catch (Throwable $e) {
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
        <p class="text-white/90 text-xs font-medium mb-1">Resoluciones creadas</p>
        <p class="text-2xl font-bold"><?= (int)($stats['resoluciones_total'] ?? 0) ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-file-signature text-base"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-yellow-600 to-amber-700 rounded-xl p-3 text-white card-anim shadow-lg" style="animation-delay: 0.2s">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-white/90 text-xs font-medium mb-1">Resoluciones pendientes</p>
        <p class="text-2xl font-bold"><?= (int)($stats['resoluciones_pendientes'] ?? 0) ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-hourglass-half text-base"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl p-3 text-white card-anim shadow-lg" style="animation-delay: 0.3s">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-white/90 text-xs font-medium mb-1">Resoluciones activas</p>
        <p class="text-2xl font-bold"><?= (int)($stats['resoluciones_activas'] ?? 0) ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-check-circle text-base"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-blue-600 to-indigo-800 rounded-xl p-3 text-white card-anim shadow-lg" style="animation-delay: 0.4s">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-white/90 text-xs font-medium mb-1">Solicitudes registradas</p>
        <p class="text-2xl font-bold"><?= (int)($stats['solicitudes_total'] ?? 0) ?></p>
      </div>
      <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
        <i class="fas fa-file-alt text-base"></i>
      </div>
    </div>
  </div>
</section>

<!-- GRAFICOS -->
<section class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
  <div class="chart-container bg-white rounded-xl shadow-lg border border-gray-100 p-4 card-anim" style="animation-delay: 0.2s">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-chart-pie text-blue-600 text-sm"></i>
        Resoluciones por estado
      </h3>
    </div>
    <div class="relative" style="height: 240px;">
      <canvas id="resEstadoChart"></canvas>
    </div>
  </div>

  <div class="chart-container bg-white rounded-xl shadow-lg border border-gray-100 p-4 card-anim" style="animation-delay: 0.3s">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-chart-line text-indigo-600 text-sm"></i>
        Resoluciones por mes
      </h3>
    </div>
    <div class="relative" style="height: 240px;">
      <canvas id="resMensualChart"></canvas>
    </div>
  </div>

  <div class="chart-container bg-white rounded-xl shadow-lg border border-gray-100 p-4 card-anim" style="animation-delay: 0.4s">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-chart-pie text-emerald-600 text-sm"></i>
        Solicitudes por estado
      </h3>
    </div>
    <div class="relative" style="height: 240px;">
      <canvas id="solEstadoChart"></canvas>
    </div>
  </div>

  <div class="chart-container bg-white rounded-xl shadow-lg border border-gray-100 p-4 card-anim" style="animation-delay: 0.5s">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-chart-area text-cyan-600 text-sm"></i>
        Solicitudes por mes
      </h3>
    </div>
    <div class="relative" style="height: 240px;">
      <canvas id="solMensualChart"></canvas>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const resolucionesEstado = <?= json_encode($resolucionesEstado) ?>;
  const solicitudesEstado = <?= json_encode($solicitudesEstado) ?>;
  const mensual = <?= json_encode($mensual) ?>;

  // Configuración común para gráficos
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' },
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

  const pieBase = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: chartOptions.plugins.tooltip,
    }
  };

  const resEstadoCtx = document.getElementById('resEstadoChart');
  if (resEstadoCtx) {
    new Chart(resEstadoCtx, {
      type: 'doughnut',
      data: {
        labels: Object.keys(resolucionesEstado),
        datasets: [{
          data: Object.values(resolucionesEstado),
          backgroundColor: ['#16A34A', '#F59E0B'],
          borderWidth: 0,
        }]
      },
      options: pieBase
    });
  }

  const solEstadoCtx = document.getElementById('solEstadoChart');
  if (solEstadoCtx) {
    new Chart(solEstadoCtx, {
      type: 'doughnut',
      data: {
        labels: Object.keys(solicitudesEstado),
        datasets: [{
          data: Object.values(solicitudesEstado),
          backgroundColor: ['#2563EB', '#DC2626', '#F59E0B'],
          borderWidth: 0,
        }]
      },
      options: pieBase
    });
  }

  const resMensualCtx = document.getElementById('resMensualChart');
  if (resMensualCtx) {
    new Chart(resMensualCtx, {
      type: 'line',
      data: {
        labels: mensual.labels || [],
        datasets: [
          {
            label: 'Activas',
            data: mensual.res_activas || [],
            borderColor: '#16A34A',
            backgroundColor: 'rgba(22, 163, 74, 0.12)',
            tension: 0.35,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
          },
          {
            label: 'Pendientes',
            data: mensual.res_pendientes || [],
            borderColor: '#F59E0B',
            backgroundColor: 'rgba(245, 158, 11, 0.10)',
            tension: 0.35,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
          }
        ]
      },
      options: chartOptions
    });
  }

  const solMensualCtx = document.getElementById('solMensualChart');
  if (solMensualCtx) {
    new Chart(solMensualCtx, {
      type: 'bar',
      data: {
        labels: mensual.labels || [],
        datasets: [{
          label: 'Solicitudes',
          data: mensual.sol_total || [],
          backgroundColor: '#0EA5E9',
          borderRadius: 6,
          borderSkipped: false,
          maxBarThickness: 40,
        }]
      },
      options: chartOptions
    });
  }
});
</script>
