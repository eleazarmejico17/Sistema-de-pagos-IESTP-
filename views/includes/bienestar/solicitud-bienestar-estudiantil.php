<?php
require_once __DIR__ . "/../../../config/conexion.php";

$solicitudes = [];
$errorConsulta = null;
$totalSolicitudes = 0;

try {
    // Intentar con Database primero, luego con Conexion para compatibilidad
    if (class_exists('Database')) {
        $db = Database::getInstance()->getConnection();
    } elseif (class_exists('Conexion')) {
        $db = Conexion::getInstance()->getConnection();
    } else {
        throw new Exception("No se encontró la clase de conexión");
    }

    // Primero verificar que la tabla 'solicitudes' existe
    $checkTable = $db->query("SHOW TABLES LIKE 'solicitudes'");
    if ($checkTable->rowCount() === 0) {
        throw new Exception("La tabla 'solicitudes' no existe en la base de datos");
    }

    // Contar total de solicitudes
    $countStmt = $db->query("SELECT COUNT(*) as total FROM solicitudes");
    $totalSolicitudes = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtener todas las solicitudes con información completa
    // Usamos la estructura real: solicitudes + estudiante
    $sql = "SELECT 
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
                    WHEN s.estado = 'aprobada' THEN 'Aprobado'
                    WHEN s.estado = 'Aprobado' THEN 'Aprobado'
                    WHEN s.estado = 'APROBADO' THEN 'Aprobado'
                    WHEN s.estado = 'rechazado' THEN 'Rechazado'
                    WHEN s.estado = 'rechazada' THEN 'Rechazado'
                    WHEN s.estado = 'Rechazado' THEN 'Rechazado'
                    WHEN s.estado = 'RECHAZADO' THEN 'Rechazado'
                    WHEN s.estado = 'en_evaluacion' THEN 'En evaluación'
                    WHEN s.estado = 'evaluacion' THEN 'En evaluación'
                    WHEN s.estado = 'En evaluación' THEN 'En evaluación'
                    WHEN s.estado = 'EN_EVALUACION' THEN 'En evaluación'
                    ELSE 'Pendiente'
                END AS estado,
                s.observaciones AS motivo_respuesta,
                s.fecha_revision AS fecha_respuesta,
                s.fecha_solicitud AS fecha_registro,
                s.estado AS estado_original,
                '' AS empleado_nombre
            FROM solicitudes s
            LEFT JOIN estudiante e ON e.id = s.estudiante
            ORDER BY s.id DESC, COALESCE(s.fecha_revision, s.fecha_solicitud, NOW()) DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $solicitudes = [];
    $errorConsulta = "Error de base de datos: " . $e->getMessage();
    error_log('Error PDO al obtener solicitudes: ' . $e->getMessage());
} catch (Exception $e) {
    $solicitudes = [];
    $errorConsulta = $e->getMessage();
    error_log('Error al obtener solicitudes: ' . $e->getMessage());
}

// Función para obtener el color según el estado
function getEstadoColor($estado) {
    $colores = [
        'Pendiente' => ['bg' => 'yellow', 'text' => 'yellow-800', 'border' => 'yellow-400', 'icon' => 'fa-clock', 'gradient' => 'from-yellow-400 to-amber-500'],
        'En evaluación' => ['bg' => 'blue', 'text' => 'blue-800', 'border' => 'blue-400', 'icon' => 'fa-hourglass-half', 'gradient' => 'from-blue-400 to-indigo-500'],
        'Aprobado' => ['bg' => 'green', 'text' => 'green-800', 'border' => 'green-400', 'icon' => 'fa-check-circle', 'gradient' => 'from-green-400 to-emerald-500'],
        'Rechazado' => ['bg' => 'red', 'text' => 'red-800', 'border' => 'red-400', 'icon' => 'fa-times-circle', 'gradient' => 'from-red-400 to-rose-500']
    ];
    return $colores[$estado] ?? ['bg' => 'gray', 'text' => 'gray-800', 'border' => 'gray-400', 'icon' => 'fa-question', 'gradient' => 'from-gray-400 to-gray-500'];
}

function getEstadoTexto($estado) {
    return $estado ?? 'Pendiente';
}

// Contar por estado
$pendientes = count(array_filter($solicitudes, fn($s) => ($s['estado'] ?? 'Pendiente') === 'Pendiente'));
$enEvaluacion = count(array_filter($solicitudes, fn($s) => ($s['estado'] ?? '') === 'En evaluación'));
$aprobadas = count(array_filter($solicitudes, fn($s) => ($s['estado'] ?? '') === 'Aprobado'));
$rechazadas = count(array_filter($solicitudes, fn($s) => ($s['estado'] ?? '') === 'Rechazado'));
?>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}
.card-anim {
    animation: fadeInUp 0.5s ease-out forwards;
}
.solicitud-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.solicitud-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}
.expand-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.expand-content.expanded {
    max-height: 2000px;
}
.stat-badge {
    background: linear-gradient(135deg, var(--gradient));
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}
.stat-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.filtro-tipo-btn.active {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-color: #6366f1;
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.filtro-tipo-btn:hover:not(.active) {
    background: #f3f4f6;
    border-color: #d1d5db;
    transform: translateY(-1px);
}
.filtro-tipo-btn:active {
    transform: translateY(0);
}
</style>

<!-- Estadísticas rápidas -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="stat-badge rounded-xl p-3 text-white card-anim" style="--gradient: linear-gradient(135deg, #1f2937 0%, #111827 100%); animation-delay: 0.1s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-900 text-xs font-bold mb-1">Pendientes</p>
                <p class="text-2xl font-black text-gray-900"><?= $pendientes ?></p>
            </div>
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                <i class="fas fa-clock text-lg text-yellow-400"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-badge rounded-xl p-3 text-white card-anim" style="--gradient: linear-gradient(135deg, #1f2937 0%, #111827 100%); animation-delay: 0.2s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-900 text-xs font-bold mb-1">En Evaluación</p>
                <p class="text-2xl font-black text-gray-900"><?= $enEvaluacion ?></p>
            </div>
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                <i class="fas fa-hourglass-half text-lg text-blue-400"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-badge rounded-xl p-3 text-white card-anim" style="--gradient: linear-gradient(135deg, #1f2937 0%, #111827 100%); animation-delay: 0.3s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-900 text-xs font-bold mb-1">Aprobadas</p>
                <p class="text-2xl font-black text-gray-900"><?= $aprobadas ?></p>
            </div>
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                <i class="fas fa-check-circle text-lg text-green-400"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-badge rounded-xl p-3 text-white card-anim" style="--gradient: linear-gradient(135deg, #1f2937 0%, #111827 100%); animation-delay: 0.4s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-900 text-xs font-bold mb-1">Rechazadas</p>
                <p class="text-2xl font-black text-gray-900"><?= $rechazadas ?></p>
            </div>
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                <i class="fas fa-times-circle text-lg text-red-400"></i>
            </div>
        </div>
    </div>
</section>

<!-- Header -->
<div class="mb-4 pb-3 border-b border-gray-200/50">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-0.5 flex items-center gap-2">
                <i class="fas fa-file-alt text-indigo-600"></i>
                <span>Solicitudes de Descuento</span>
            </h2>
            <p class="text-gray-600 text-xs">Gestiona las solicitudes de los estudiantes</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg border border-indigo-200 shadow-sm">
            <i class="fas fa-file-alt text-indigo-600 text-sm"></i>
            <span class="font-bold text-indigo-800 text-sm"><span id="totalSolicitudes"><?= count($solicitudes) ?></span> solicitudes</span>
        </div>
    </div>
</div>

<!-- Barra de búsqueda y filtros -->
<div class="mb-4 p-4 bg-white rounded-xl shadow-md border border-gray-100 card-anim">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <!-- Búsqueda general -->
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
                <i class="fas fa-search text-indigo-600 text-xs"></i>
                <span>Buscar por nombre, DNI, teléfono o correo</span>
            </label>
            <div class="relative">
                <input 
                    type="text" 
                    id="buscarSolicitud"
                    placeholder="Ej: Juan Pérez, 12345678, 987654321..."
                    class="w-full px-4 py-2.5 pl-10 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200/50 transition-all text-sm font-medium placeholder-gray-400">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <button 
                    type="button"
                    id="btnLimpiarBusqueda"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"
                    title="Limpiar búsqueda">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>
        
        <!-- Filtro por estado -->
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
                <i class="fas fa-filter text-indigo-600 text-xs"></i>
                <span>Filtrar por estado</span>
            </label>
            <select 
                id="filtroEstado"
                class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200/50 transition-all appearance-none bg-white text-sm font-medium text-gray-800 cursor-pointer">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En evaluación">En evaluación</option>
                <option value="Aprobado">Aprobado</option>
                <option value="Rechazado">Rechazado</option>
            </select>
        </div>
    </div>
    
    <!-- Filtro por tipo de solicitud -->
    <div class="mt-3">
        <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
            <i class="fas fa-tag text-indigo-600 text-xs"></i>
            <span>Filtrar por tipo de solicitud</span>
        </label>
        <div class="flex flex-wrap gap-2" id="filtrosTipo">
            <button 
                type="button"
                class="filtro-tipo-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border-2 border-gray-200 bg-white text-gray-700 hover:bg-gray-50 active"
                data-tipo="">
                <i class="fas fa-list mr-1"></i>Todos
            </button>
            <?php
            $tiposUnicos = array_unique(array_column($solicitudes, 'tipo_solicitud'));
            foreach ($tiposUnicos as $tipo):
                if (empty($tipo)) continue;
            ?>
            <button 
                type="button"
                class="filtro-tipo-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border-2 border-gray-200 bg-white text-gray-700 hover:bg-gray-50"
                data-tipo="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Error -->
<?php if ($errorConsulta): ?>
    <div class="mb-4 p-4 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 rounded-xl shadow-lg card-anim">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-red-600 text-lg mt-0.5"></i>
            <div>
                <p class="text-red-800 font-semibold mb-1 text-sm">Error al cargar las solicitudes</p>
                <p class="text-red-700 text-xs"><?= htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Estado vacío -->
<?php if (empty($solicitudes)): ?>
    <div class="flex flex-col items-center justify-center py-16 px-4 card-anim">
        <div class="relative mb-4">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-100 via-orange-100 to-yellow-100 shadow-lg">
                <i class="fas fa-inbox text-4xl text-amber-600"></i>
            </div>
            <div class="absolute -top-1 -right-1 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center shadow-lg">
                <i class="fas fa-file-alt text-white text-xs"></i>
            </div>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">No hay solicitudes registradas</h3>
        <p class="text-gray-600 text-center mb-6 max-w-md mx-auto text-sm leading-relaxed">
            Las solicitudes que los estudiantes envíen desde su panel aparecerán aquí para su revisión y procesamiento.
        </p>
        <div class="flex items-center gap-2 px-3 py-2 bg-indigo-50 rounded-lg border border-indigo-200">
            <i class="fas fa-info-circle text-indigo-600 text-xs"></i>
            <span class="text-xs text-indigo-800 font-medium">Las solicitudes se mostrarán automáticamente</span>
        </div>
    </div>
<?php else: ?>
    <!-- Lista de solicitudes -->
    <div class="space-y-3" id="listaSolicitudes">
        <?php foreach ($solicitudes as $index => $sol): 
            $estado = getEstadoTexto($sol['estado']);
            $color = getEstadoColor($estado);
            $fechaRegistro = !empty($sol['fecha_registro']) ? date('d/m/Y H:i', strtotime($sol['fecha_registro'])) : 'N/A';
            $fechaRespuesta = !empty($sol['fecha_respuesta']) ? date('d/m/Y H:i', strtotime($sol['fecha_respuesta'])) : null;
        ?>
        <div class="solicitud-card bg-white rounded-xl shadow-md hover:shadow-xl border border-gray-100 overflow-hidden card-anim solicitud-item" 
             data-nombre="<?= htmlspecialchars(strtolower($sol['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
             data-telefono="<?= htmlspecialchars($sol['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
             data-correo="<?= htmlspecialchars(strtolower($sol['correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
             data-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>"
             data-tipo="<?= htmlspecialchars(strtolower($sol['tipo_solicitud'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
             style="animation-delay: <?= $index * 0.05 ?>s">
            <!-- Header clickeable -->
            <div class="flex justify-between items-start p-4 cursor-pointer hover:bg-gradient-to-r hover:from-gray-50 hover:to-indigo-50/30 transition-all"
                 onclick="toggleSolicitud(<?= $sol['id'] ?>)">
                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-<?= $color['bg'] ?>-100 to-<?= $color['bg'] ?>-200 flex items-center justify-center flex-shrink-0 shadow-sm">
                            <i class="fas <?= $color['icon'] ?> text-<?= $color['text'] ?> text-base"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <h3 class="text-base font-bold text-gray-800 truncate"><?= htmlspecialchars($sol['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-gradient-to-r from-<?= $color['bg'] ?>-100 to-<?= $color['bg'] ?>-200 text-<?= $color['text'] ?> border border-<?= $color['border'] ?>/50 flex items-center gap-1">
                                    <i class="fas <?= $color['icon'] ?> text-[10px]"></i><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-600">
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-phone text-gray-400 text-[10px]"></i>
                                    <span class="truncate"><?= htmlspecialchars($sol['telefono'], ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <?php if (!empty($sol['correo'])): ?>
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-envelope text-gray-400 text-[10px]"></i>
                                        <span class="truncate max-w-[150px]"><?= htmlspecialchars($sol['correo'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                <?php endif; ?>
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-calendar text-gray-400 text-[10px]"></i>
                                    <?= htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-tag text-gray-400 text-[10px]"></i>
                                    <strong class="truncate"><?= htmlspecialchars($sol['tipo_solicitud'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="ml-3 p-1.5 rounded-lg hover:bg-gray-100 transition-colors flex-shrink-0">
                    <i class="fas fa-chevron-down text-gray-400 text-sm transition-transform duration-300" id="arrow-<?= $sol['id'] ?>"></i>
                </button>
            </div>

            <!-- Contenido expandible -->
            <div class="expand-content bg-gradient-to-b from-gray-50/50 to-white" id="content-<?= $sol['id'] ?>">
                <div class="px-4 pb-4 pt-2 space-y-3">
                    <!-- Descripción -->
                    <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-align-left text-indigo-600 text-sm"></i>
                            <p class="font-semibold text-gray-700 text-xs">Descripción</p>
                        </div>
                        <p class="text-gray-700 text-xs leading-relaxed">
                            <?= nl2br(htmlspecialchars($sol['descripcion'], ENT_QUOTES, 'UTF-8')) ?>
                        </p>
                    </div>

                    <!-- Archivos -->
                    <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-paperclip text-indigo-600 text-sm"></i>
                            <p class="font-semibold text-gray-700 text-xs">Evidencias Adjuntas</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $archivos = !empty($sol['archivos']) ? explode(",", $sol['archivos']) : [];
                            $hayArchivos = false;

                            foreach($archivos as $archivo){
                                $archivo = trim($archivo);
                                if ($archivo === '') continue;

                                $hayArchivos = true;
                                $ruta = "../uploads/solicitudes/" . rawurlencode($archivo);
                                $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                                $archivoSeguro = htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8');

                                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                                    echo "
                                    <div onclick='abrirImagen(\"$ruta\")'
                                         class='group/thumb relative w-20 h-20 rounded-lg overflow-hidden border-2 border-gray-200 hover:border-indigo-400 cursor-pointer transition-all hover:shadow-lg'>
                                        <img src='$ruta' alt='$archivoSeguro' class='w-full h-full object-cover group-hover/thumb:scale-110 transition-transform duration-300'>
                                        <div class='absolute inset-0 bg-black/0 group-hover/thumb:bg-black/30 transition-colors flex items-center justify-center'>
                                            <i class='fas fa-search-plus text-white opacity-0 group-hover/thumb:opacity-100 transition-opacity text-sm'></i>
                                        </div>
                                    </div>";
                                } else {
                                    echo "
                                    <a href='$ruta' target='_blank'
                                       class='flex flex-col items-center justify-center w-20 h-20 rounded-lg border-2 border-gray-200 hover:border-indigo-400 bg-gray-50 hover:bg-indigo-50 transition-all hover:shadow-lg'>
                                        <i class='fas fa-file text-xl text-gray-400 mb-1'></i>
                                        <span class='text-[10px] text-gray-600 text-center px-1 truncate w-full'>$archivoSeguro</span>
                                    </a>";
                                }
                            }

                            if (!$hayArchivos) {
                                echo "<p class='text-gray-500 text-xs'>No se adjuntaron evidencias en esta solicitud.</p>";
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php if (!empty($sol['motivo_respuesta'])): ?>
                            <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <i class="fas fa-comment-alt text-purple-600 text-sm"></i>
                                    <p class="font-semibold text-gray-700 text-xs">Motivo de Respuesta</p>
                                </div>
                                <p class="text-gray-700 text-xs">
                                    <?= nl2br(htmlspecialchars($sol['motivo_respuesta'], ENT_QUOTES, 'UTF-8')) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($fechaRespuesta): ?>
                            <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <i class="fas fa-clock text-indigo-600 text-sm"></i>
                                    <p class="font-semibold text-gray-700 text-xs">Fecha de Respuesta</p>
                                </div>
                                <p class="text-gray-700 text-xs"><?= htmlspecialchars($fechaRespuesta, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sol['empleado_nombre'])): ?>
                            <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <i class="fas fa-user-tie text-green-600 text-sm"></i>
                                    <p class="font-semibold text-gray-700 text-xs">Procesado por</p>
                                </div>
                                <p class="text-gray-700 text-xs"><?= htmlspecialchars($sol['empleado_nombre'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botones de acción -->
                    <?php if ($estado === 'Pendiente' || $estado === 'En evaluación'): ?>
                        <div class="flex gap-2 pt-1">
                            <button onclick="accionSolicitud(<?= $sol['id'] ?>, 'Aprobado')"
                                class="flex-1 py-2.5 px-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2 text-sm">
                                <i class="fas fa-check-circle text-sm"></i>
                                <span>Aprobar</span>
                            </button>

                            <button onclick="accionSolicitud(<?= $sol['id'] ?>, 'Rechazado')"
                                class="flex-1 py-2.5 px-4 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2 text-sm">
                                <i class="fas fa-times-circle text-sm"></i>
                                <span>Rechazar</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-gradient-to-r from-<?= $color['bg'] ?>-50 to-<?= $color['bg'] ?>-100 border border-<?= $color['border'] ?>/50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i class="fas <?= $color['icon'] ?> text-<?= $color['text'] ?> text-sm"></i>
                                <p class="text-xs font-semibold text-<?= $color['text'] ?>">
                                    Esta solicitud ya ha sido procesada.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal de imagen -->
<div id="modalImagen" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-4 max-w-5xl max-h-[90vh] relative shadow-2xl">
        <button onclick="cerrarModalImagen()" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700 hover:text-gray-900 transition-colors">
            <i class="fas fa-times text-sm"></i>
        </button>
        <div class="mb-3">
            <h3 class="text-lg font-bold text-gray-800">Previsualización</h3>
        </div>
        <img id="imagenModal" src="" class="max-w-full max-h-[75vh] object-contain rounded-lg shadow-lg">
    </div>
</div>

<script>
let abierto = null;

function toggleSolicitud(id) {
    const content = document.getElementById('content-' + id);
    const arrow = document.getElementById('arrow-' + id);
    
    if (abierto && abierto !== content) {
        abierto.classList.remove('expanded');
        const prevArrow = document.getElementById('arrow-' + abierto.id.split('-')[1]);
        if (prevArrow) {
            prevArrow.style.transform = "rotate(0deg)";
        }
    }
    
    if (content.classList.contains('expanded')) {
        content.classList.remove('expanded');
        arrow.style.transform = "rotate(0deg)";
        abierto = null;
    } else {
        content.classList.add('expanded');
        arrow.style.transform = "rotate(180deg)";
        abierto = content;
    }
}

function abrirImagen(src) {
    document.getElementById('imagenModal').src = src;
    document.getElementById('modalImagen').classList.remove('hidden');
}

function cerrarModalImagen() {
    document.getElementById('modalImagen').classList.add('hidden');
}

function accionSolicitud(id, estado) {
    const accionTexto = estado === 'Aprobado' ? 'aprobar' : 'rechazar';
    
    if (!confirm(`¿Estás seguro de ${accionTexto} esta solicitud?`)) {
        return;
    }

    const motivo = estado === 'Rechazado' 
        ? prompt('Por favor, ingresa el motivo del rechazo:') 
        : '';

    if (estado === 'Rechazado' && !motivo) {
        alert('Debes ingresar un motivo para rechazar la solicitud.');
        return;
    }

    // Obtener el botón que disparó el evento
    const buttonContainer = event.target.closest('.flex');
    const buttons = buttonContainer ? buttonContainer.querySelectorAll('button') : [];
    
    // Deshabilitar botones
    buttons.forEach(btn => {
        btn.disabled = true;
        const originalHTML = btn.innerHTML;
        btn.dataset.originalHtml = originalHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    });

    // Determinar la ruta correcta del controlador
    // Desde views/dashboard-bienestar.php necesitamos subir un nivel a la raíz del proyecto
    const currentPath = window.location.pathname;
    let controllerPath;
    
    if (currentPath.includes('/views/')) {
        // Si estamos en views/, subir un nivel
        const projectRoot = currentPath.substring(0, currentPath.indexOf('/views/'));
        controllerPath = projectRoot + '/controller/actualizarEstadoSolicitud.php';
    } else {
        // Ruta alternativa: relativa desde la ubicación actual
        controllerPath = '../controller/actualizarEstadoSolicitud.php';
    }

    fetch(controllerPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            estado: estado,
            motivo: motivo || ''
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-2xl z-50 flex items-center gap-2 animate-slide-in';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle text-lg"></i>
                <div>
                    <p class="font-bold text-sm">${data.message}</p>
                </div>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateX(100%)';
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
            
            setTimeout(() => location.reload(), 1000);
        } else {
            const errorMsg = data.error || 'No se pudo actualizar la solicitud';
            alert('❌ Error: ' + errorMsg);
            console.error('Error del servidor:', data);
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.originalHtml || '';
            });
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        alert('❌ Error al procesar la solicitud. Verifica la conexión y vuelve a intentar.');
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml || '';
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('button[onclick*="accionSolicitud"]').forEach(btn => {
        btn.dataset.originalHtml = btn.innerHTML;
    });
    
    // Sistema de búsqueda y filtros
    const buscarInput = document.getElementById('buscarSolicitud');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtrosTipo = document.querySelectorAll('.filtro-tipo-btn');
    const btnLimpiar = document.getElementById('btnLimpiarBusqueda');
    const solicitudes = document.querySelectorAll('.solicitud-item');
    const totalSolicitudesSpan = document.getElementById('totalSolicitudes');
    
    function filtrarSolicitudes() {
        const textoBusqueda = buscarInput.value.toLowerCase().trim();
        const estadoSeleccionado = filtroEstado.value.toLowerCase();
        const tipoSeleccionado = document.querySelector('.filtro-tipo-btn.active')?.dataset.tipo?.toLowerCase() || '';
        
        let visible = 0;
        
        solicitudes.forEach(sol => {
            const nombre = sol.dataset.nombre || '';
            const telefono = sol.dataset.telefono || '';
            const correo = sol.dataset.correo || '';
            const estado = sol.dataset.estado?.toLowerCase() || '';
            const tipo = sol.dataset.tipo || '';
            
            // Verificar búsqueda de texto
            const coincideTexto = !textoBusqueda || 
                nombre.includes(textoBusqueda) || 
                telefono.includes(textoBusqueda) || 
                correo.includes(textoBusqueda) ||
                telefono.replace(/\s/g, '').includes(textoBusqueda.replace(/\s/g, ''));
            
            // Verificar filtro de estado
            const coincideEstado = !estadoSeleccionado || estado === estadoSeleccionado;
            
            // Verificar filtro de tipo
            const coincideTipo = !tipoSeleccionado || tipo === tipoSeleccionado;
            
            if (coincideTexto && coincideEstado && coincideTipo) {
                sol.style.display = '';
                visible++;
            } else {
                sol.style.display = 'none';
            }
        });
        
        // Actualizar contador
        totalSolicitudesSpan.textContent = visible;
        
        // Mostrar/ocultar botón limpiar
        if (textoBusqueda || estadoSeleccionado || tipoSeleccionado) {
            btnLimpiar.classList.remove('hidden');
        } else {
            btnLimpiar.classList.add('hidden');
        }
        
        // Mostrar mensaje si no hay resultados
        let mensajeNoResultados = document.getElementById('mensajeNoResultados');
        if (visible === 0 && (textoBusqueda || estadoSeleccionado || tipoSeleccionado)) {
            if (!mensajeNoResultados) {
                mensajeNoResultados = document.createElement('div');
                mensajeNoResultados.id = 'mensajeNoResultados';
                mensajeNoResultados.className = 'text-center py-12 px-4 card-anim';
                mensajeNoResultados.innerHTML = `
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-search text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">No se encontraron resultados</h3>
                    <p class="text-gray-600 text-sm">Intenta ajustar los filtros de búsqueda</p>
                `;
                document.getElementById('listaSolicitudes').appendChild(mensajeNoResultados);
            }
            mensajeNoResultados.style.display = '';
        } else if (mensajeNoResultados) {
            mensajeNoResultados.style.display = 'none';
        }
    }
    
    // Event listeners
    buscarInput.addEventListener('input', filtrarSolicitudes);
    filtroEstado.addEventListener('change', filtrarSolicitudes);
    
    filtrosTipo.forEach(btn => {
        btn.addEventListener('click', function() {
            filtrosTipo.forEach(b => b.classList.remove('active', 'bg-indigo-100', 'border-indigo-400', 'text-indigo-700'));
            this.classList.add('active', 'bg-indigo-100', 'border-indigo-400', 'text-indigo-700');
            filtrarSolicitudes();
        });
    });
    
    btnLimpiar.addEventListener('click', function() {
        buscarInput.value = '';
        filtroEstado.value = '';
        filtrosTipo.forEach(b => b.classList.remove('active', 'bg-indigo-100', 'border-indigo-400', 'text-indigo-700'));
        filtrosTipo[0].classList.add('active', 'bg-indigo-100', 'border-indigo-400', 'text-indigo-700');
        filtrarSolicitudes();
        buscarInput.focus();
    });
    
    // Autocompletado básico (sugerencias mientras escribe)
    buscarInput.addEventListener('focus', function() {
        this.classList.add('ring-2', 'ring-indigo-200');
    });
    
    buscarInput.addEventListener('blur', function() {
        this.classList.remove('ring-2', 'ring-indigo-200');
    });
});
</script>

<style>
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
.animate-slide-in {
    animation: slideIn 0.3s ease-out;
}
</style>
