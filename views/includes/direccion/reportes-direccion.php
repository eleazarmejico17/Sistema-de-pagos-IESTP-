<?php
require_once __DIR__ . "/../../../config/conexion.php";

$usuarios = [];
$usuariosPorRol = [];
$actividadMensual = [];
$resolucionesPorEstado = [
    'Emitidas/Activas' => 0,
    'No activas/Pendientes' => 0,
];
$resolucionesMensual = [
    'labels' => [],
    'activas' => [],
    'no_activas' => [],
];
$errorConsulta = null;

try {
    // Intentar con Database primero, luego con Conexion para compatibilidad
    if (class_exists('Database')) {
        $db = Database::getInstance()->getConnection();
    } elseif (class_exists('Conexion')) {
        $db = Conexion::getInstance()->getConnection();
    } else {
        throw new Exception("No se encontró la clase de conexión");
    }

    // Obtener usuarios con información de roles
    $sqlUsuarios = "SELECT 
                        u.id,
                        u.usuario,
                        u.tipo,
                        u.estuempleado,
                        e.apnom_emp,
                        e.mailp_emp,
                        e.maili_emp,
                        est.nom_est,
                        est.ap_est,
                        est.am_est,
                        est.mailp_est,
                        est.maili_est
                    FROM usuarios u
                    LEFT JOIN empleado e ON e.id = u.estuempleado AND u.tipo = 1
                    LEFT JOIN estudiante est ON est.id = u.estuempleado AND u.tipo = 2
                    ORDER BY u.id DESC
                    LIMIT 100";
    
    $stmt = $db->prepare($sqlUsuarios);
    $stmt->execute();
    $usuariosRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar usuarios
    foreach ($usuariosRaw as $u) {
        $rolNombre = 'Sin definir';
        $correo = '';
        
        // Determinar rol según tipo
        switch ($u['tipo']) {
            case 1:
                $rolNombre = 'Empleado';
                $correo = $u['mailp_emp'] ?? $u['maili_emp'] ?? '';
                $nombreCompleto = $u['apnom_emp'] ?? '';
                break;
            case 2:
                $rolNombre = 'Estudiante';
                $correo = $u['mailp_est'] ?? $u['maili_est'] ?? '';
                $nombreCompleto = trim(($u['nom_est'] ?? '') . ' ' . ($u['ap_est'] ?? '') . ' ' . ($u['am_est'] ?? ''));
                break;
            case 3:
                $rolNombre = 'Empresa';
                break;
            default:
                $rolNombre = 'Administrador';
        }

        $usuarios[] = [
            'usuario' => $u['usuario'] ?? '',
            'rol' => $rolNombre,
            'correo' => $correo ?: 'No especificado',
            'nombre_completo' => $nombreCompleto ?? ''
        ];

        // Contar por rol
        if (!isset($usuariosPorRol[$rolNombre])) {
            $usuariosPorRol[$rolNombre] = 0;
        }
        $usuariosPorRol[$rolNombre]++;
    }

    // Obtener actividad mensual de solicitudes
    $sqlActividad = "SELECT 
                        DATE_FORMAT(fecha_solicitud, '%Y-%m') as mes,
                        COUNT(*) as total
                    FROM solicitudes
                    WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
                    GROUP BY DATE_FORMAT(fecha_solicitud, '%Y-%m')
                    ORDER BY mes ASC";
    
    $stmt = $db->prepare($sqlActividad);
    $stmt->execute();
    $actividadRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparar datos para el gráfico (últimos 7 meses)
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $actividadMensual = array_fill(0, 7, 0);
    $labelsMensual = [];
    
    // Obtener los últimos 7 meses
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m', strtotime("-$i months"));
        $mesNum = (int)date('n', strtotime("-$i months")) - 1;
        $labelsMensual[] = $meses[$mesNum];
        
        // Buscar actividad para este mes
        foreach ($actividadRaw as $act) {
            if ($act['mes'] === $fecha) {
                $actividadMensual[6 - $i] = (int)$act['total'];
                break;
            }
        }
    }

    // Resoluciones por estado
    try {
        $stmtRes = $db->prepare("SELECT estado, COUNT(*) as total FROM resoluciones GROUP BY estado");
        $stmtRes->execute();
        $resEstados = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resEstados as $row) {
            $estado = (bool)($row['estado'] ?? false);
            $total = (int)($row['total'] ?? 0);
            if ($estado) {
                $resolucionesPorEstado['Emitidas/Activas'] += $total;
            } else {
                $resolucionesPorEstado['No activas/Pendientes'] += $total;
            }
        }
    } catch (Throwable $e) {
        // mantener valores por defecto
    }

    // Tendencia mensual (últimos 7 meses) de resoluciones activas/no activas
    try {
        $stmtResMes = $db->prepare("
            SELECT 
                DATE_FORMAT(creado_en, '%Y-%m') as mes,
                estado,
                COUNT(*) as total
            FROM resoluciones
            WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
            GROUP BY DATE_FORMAT(creado_en, '%Y-%m'), estado
            ORDER BY mes ASC
        ");
        $stmtResMes->execute();
        $resMesRaw = $stmtResMes->fetchAll(PDO::FETCH_ASSOC);

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $labels = [];
        $act = array_fill(0, 7, 0);
        $noAct = array_fill(0, 7, 0);

        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m', strtotime("-$i months"));
            $mesNum = (int)date('n', strtotime("-$i months")) - 1;
            $labels[] = $meses[$mesNum];

            foreach ($resMesRaw as $row) {
                if (($row['mes'] ?? '') !== $fecha) {
                    continue;
                }
                $estado = (bool)($row['estado'] ?? false);
                $total = (int)($row['total'] ?? 0);
                if ($estado) {
                    $act[6 - $i] = $total;
                } else {
                    $noAct[6 - $i] = $total;
                }
            }
        }

        $resolucionesMensual = [
            'labels' => $labels,
            'activas' => $act,
            'no_activas' => $noAct,
        ];
    } catch (Throwable $e) {
        // mantener valores por defecto
    }

} catch (PDOException $e) {
    $errorConsulta = "Error de base de datos: " . $e->getMessage();
    error_log('Error PDO en reportes: ' . $e->getMessage());
} catch (Exception $e) {
    $errorConsulta = "Error: " . $e->getMessage();
    error_log('Error en reportes: ' . $e->getMessage());
}

// Preparar datos para el gráfico de dona
$labelsRoles = array_keys($usuariosPorRol);
$datosRoles = array_values($usuariosPorRol);
$coloresRoles = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];

$labelsResEstado = array_keys($resolucionesPorEstado);
$datosResEstado = array_values($resolucionesPorEstado);
$coloresResEstado = ['#10B981', '#F59E0B'];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .hover-lift {
    transition: all 0.3s ease;
  }
  .hover-lift:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
  }
</style>

<section class="w-full space-y-6">

  <?php if ($errorConsulta): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
      <p class="font-semibold">Error al cargar reportes:</p>
      <p><?= htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
  <?php endif; ?>

  <!-- GRAFICOS -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-2xl shadow-lg hover-lift">
      <h2 class="text-lg font-semibold mb-4 text-gray-800">Usuarios por Rol</h2>
      <div class="relative" style="height: 300px; max-height: 400px;">
        <canvas id="doughnutChart"></canvas>
      </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-lg hover-lift">
      <h2 class="text-lg font-semibold mb-4 text-gray-800">Resoluciones (Emitidas vs No activas)</h2>
      <div class="relative" style="height: 300px; max-height: 400px;">
        <canvas id="resolucionesEstadoChart"></canvas>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-2xl shadow-lg hover-lift">
      <h2 class="text-lg font-semibold mb-4 text-gray-800">Actividad Mensual (Solicitudes)</h2>
      <div class="relative" style="height: 300px; max-height: 400px;">
        <canvas id="gradientLineChart"></canvas>
      </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-lg hover-lift">
      <h2 class="text-lg font-semibold mb-4 text-gray-800">Resoluciones por Mes</h2>
      <div class="relative" style="height: 300px; max-height: 400px;">
        <canvas id="resolucionesMensualChart"></canvas>
      </div>
    </div>
  </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Datos para los gráficos desde PHP
  const datosRoles = <?= json_encode($datosRoles) ?>;
  const labelsRoles = <?= json_encode($labelsRoles) ?>;
  const coloresRoles = <?= json_encode(array_slice($coloresRoles, 0, count($labelsRoles))) ?>;
  const actividadMensual = <?= json_encode($actividadMensual) ?>;
  const labelsMensual = <?= json_encode($labelsMensual) ?>;
  const labelsResEstado = <?= json_encode($labelsResEstado) ?>;
  const datosResEstado = <?= json_encode($datosResEstado) ?>;
  const coloresResEstado = <?= json_encode($coloresResEstado) ?>;
  const resMensual = <?= json_encode($resolucionesMensual) ?>;

  // Doughnut chart - Usuarios por Rol
  const doughnutCtx = document.getElementById("doughnutChart");
  if (doughnutCtx) {
    new Chart(doughnutCtx, {
      type: "doughnut",
      data: {
        labels: labelsRoles.length > 0 ? labelsRoles : ["Sin datos"],
        datasets: [{
          data: datosRoles.length > 0 ? datosRoles : [1],
          backgroundColor: coloresRoles.length > 0 ? coloresRoles : ["#9CA3AF"],
          borderWidth: 2,
          borderColor: "#fff"
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 1.5,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { 
              padding: 15, 
              boxWidth: 15,
              font: {
                size: 12
              }
            }
          }
        }
      }
    });
  }

  // Gradient line chart - Actividad Mensual
  const lineCtx = document.getElementById("gradientLineChart");
  if (lineCtx) {
    const gradient = lineCtx.getContext("2d").createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

    new Chart(lineCtx, {
      type: "line",
      data: {
        labels: labelsMensual.length > 0 ? labelsMensual : ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul"],
        datasets: [{
          label: "Solicitudes",
          data: actividadMensual.length > 0 ? actividadMensual : [0, 0, 0, 0, 0, 0, 0],
          borderColor: "#3B82F6",
          backgroundColor: gradient,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: "#3B82F6",
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 1.5,
        plugins: { 
          legend: { 
            display: false 
          } 
        },
        scales: {
          y: { 
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        }
      }
    });
  }

  // Line chart - Resoluciones por mes (activas vs no activas)
  const resMesCtx = document.getElementById('resolucionesMensualChart');
  if (resMesCtx) {
    new Chart(resMesCtx, {
      type: 'line',
      data: {
        labels: (resMensual.labels && resMensual.labels.length > 0) ? resMensual.labels : ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul"],
        datasets: [
          {
            label: 'Emitidas/Activas',
            data: (resMensual.activas && resMensual.activas.length > 0) ? resMensual.activas : [0,0,0,0,0,0,0],
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.15)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
          },
          {
            label: 'No activas/Pendientes',
            data: (resMensual.no_activas && resMensual.no_activas.length > 0) ? resMensual.no_activas : [0,0,0,0,0,0,0],
            borderColor: '#F59E0B',
            backgroundColor: 'rgba(245, 158, 11, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        }
      }
    });
  }
});
</script>

