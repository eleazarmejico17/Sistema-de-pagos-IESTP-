<?php
require_once __DIR__ . "/../../../config/conexion.php";

$solicitudes = [];
$errorConsulta = null;
$estadisticas = [
    'aprobadas' => 0,
    'pendientes' => 0,
    'beneficiarios' => 0
];

try {
    // Intentar con Database primero, luego con Conexion para compatibilidad
    if (class_exists('Database')) {
        $db = Database::getInstance()->getConnection();
    } elseif (class_exists('Conexion')) {
        $db = Conexion::getInstance()->getConnection();
    } else {
        throw new Exception("No se encontró la clase de conexión");
    }

    // Obtener estadísticas de resoluciones
    try {
        // Resoluciones aprobadas
        $stmtAprobadas = $db->prepare("SELECT COUNT(*) as total FROM resoluciones WHERE estado = true");
        $stmtAprobadas->execute();
        $aprobadasResult = $stmtAprobadas->fetch(PDO::FETCH_ASSOC);
        $estadisticas['aprobadas'] = $aprobadasResult['total'];
        
        // Resoluciones pendientes
        $stmtPendientes = $db->prepare("SELECT COUNT(*) as total FROM resoluciones WHERE estado = false");
        $stmtPendientes->execute();
        $pendientesResult = $stmtPendientes->fetch(PDO::FETCH_ASSOC);
        $estadisticas['pendientes'] = $pendientesResult['total'];
        
        // Total de beneficiarios
        $stmtBeneficiarios = $db->prepare("SELECT COUNT(*) as total FROM beneficiarios WHERE activo = 1");
        $stmtBeneficiarios->execute();
        $beneficiariosResult = $stmtBeneficiarios->fetch(PDO::FETCH_ASSOC);
        $estadisticas['beneficiarios'] = $beneficiariosResult['total'];
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
    }

    // Obtener resoluciones pendientes
    $sql = "SELECT 
                r.id,
                r.numero_resolucion,
                r.titulo,
                r.texto_respaldo,
                r.monto_descuento,
                r.fecha_inicio,
                r.fecha_fin,
                r.ruta_documento,
                r.creado_en,
                emp.apnom_emp AS nombre_creador,
                emp.mailp_emp AS correo_creador,
                emp.cel_emp AS telefono_creador,
                'Pendiente' AS estado
            FROM resoluciones r
            LEFT JOIN empleado emp ON emp.id = r.creado_por
            WHERE r.estado = false
            ORDER BY r.creado_en DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $solicitudes = [];
    $errorConsulta = "Error de base de datos: " . $e->getMessage();
    error_log('Error PDO al obtener solicitudes: ' . $e->getMessage());
} catch (Exception $e) {
    $solicitudes = [];
    $errorConsulta = "Error: " . $e->getMessage();
    error_log('Error al obtener solicitudes: ' . $e->getMessage());
}
?>

<style>
/* Estilos para acordeones */
.accordion-header {
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 1rem;
    border-radius: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.accordion-header:hover {
    background-color: #f9fafb;
}

.accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}

.accordion-content.open {
    max-height: 2000px;
    transition: max-height 0.5s ease-in;
}

.chevron-icon {
    transition: transform 0.3s ease;
}

.chevron-icon.open {
    transform: rotate(180deg);
}

/* Estilos para imágenes de evidencia */
.evidencia-img {
    cursor: pointer;
    transition: transform 0.2s;
}

.evidencia-img:hover {
    transform: scale(1.05);
}

/* Animación para botones deshabilitados */
.btn-action:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Badge de estado */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<section class="w-full space-y-6">

    <!-- TÍTULO Y ESTADÍSTICAS -->
    <div class="space-y-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Panel de Resoluciones</h1>
        <p class="text-gray-600 mb-6">Gestiona las solicitudes de resoluciones pendientes de aprobación</p>
        
        <!-- TARJETAS DE ESTADÍSTICAS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <!-- Resoluciones Pendientes -->
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-100 rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-800 text-sm font-medium">Pendientes de Revisión</p>
                        <p class="text-2xl font-bold text-yellow-900 mt-1"><?= number_format($estadisticas['pendientes']) ?></p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Resoluciones Aprobadas -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-100 rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-800 text-sm font-medium">Resoluciones Aprobadas</p>
                        <p class="text-2xl font-bold text-green-900 mt-1"><?= number_format($estadisticas['aprobadas']) ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Total de Beneficiarios -->
            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-100 rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-800 text-sm font-medium">Beneficiarios Activos</p>
                        <p class="text-2xl font-bold text-blue-900 mt-1"><?= number_format($estadisticas['beneficiarios']) ?></p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LISTA DE RESOLUCIONES PENDIENTES -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Resoluciones Pendientes</h2>
            <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-3 py-1 rounded-full">
                <?= count($solicitudes) ?> solicitudes
            </span>
        </div>

        <?php if ($errorConsulta): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                <p class="font-semibold">Error al cargar solicitudes:</p>
                <p><?= htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php elseif (empty($solicitudes)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                <div class="text-gray-300 text-5xl mb-4">📋</div>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No hay solicitudes pendientes</h3>
                <p class="text-gray-500">Todas las resoluciones han sido procesadas.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3" id="listaResoluciones">
                <?php foreach ($solicitudes as $index => $sol): 
                    $resolucionNum = htmlspecialchars($sol['numero_resolucion'] ?? 'Sin número', ENT_QUOTES, 'UTF-8');
                    $titulo = htmlspecialchars($sol['titulo'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                    $creador = htmlspecialchars($sol['nombre_creador'] ?? 'Desconocido', ENT_QUOTES, 'UTF-8');
                    $fechaCreacion = date('d/m/Y', strtotime($sol['creado_en'] ?? 'now'));
                    $archivos = !empty($sol['ruta_documento']) ? [$sol['ruta_documento']] : [];
                ?>
                    <!-- Acordeón para cada resolución -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <!-- Encabezado del acordeón -->
                        <div 
                            class="accordion-header bg-gray-50 hover:bg-gray-100"
                            onclick="toggleAccordion(<?= $index ?>)"
                            id="accordion-header-<?= $index ?>"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-semibold text-blue-700">Resolución #<?= $resolucionNum ?></span>
                                    <span class="status-badge bg-yellow-100 text-yellow-800">Pendiente</span>
                                </div>
                                <p class="text-gray-600 text-sm mt-1"><?= $titulo ?></p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-user text-gray-400"></i>
                                        <?= $creador ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-calendar text-gray-400"></i>
                                        <?= $fechaCreacion ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="chevron-icon text-gray-400" id="chevron-<?= $index ?>">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Contenido del acordeón -->
                        <div 
                            class="accordion-content border-t border-gray-100"
                            id="accordion-content-<?= $index ?>"
                        >
                            <div class="p-5 space-y-5">
                                <!-- Información de la resolución -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Título completo</label>
                                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm">
                                                <?= $titulo ?>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm min-h-[100px]">
                                                <?= htmlspecialchars($sol['texto_respaldo'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto de descuento</label>
                                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                <span class="text-lg font-semibold text-green-700">
                                                    S/ <?= number_format($sol['monto_descuento'] ?? 0, 2) ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm">
                                                    <?= date('d/m/Y', strtotime($sol['fecha_inicio'] ?? 'now')) ?>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm">
                                                    <?= date('d/m/Y', strtotime($sol['fecha_fin'] ?? 'now')) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Creador</label>
                                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-user-circle text-gray-400"></i>
                                                    <div>
                                                        <p class="font-medium"><?= $creador ?></p>
                                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($sol['correo_creador'] ?? 'Sin correo', ENT_QUOTES, 'UTF-8') ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Evidencias -->
                                <?php 
                                $evidenciasValidas = array_filter(array_map('trim', $archivos));
                                if (!empty($evidenciasValidas)): 
                                ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        <i class="fas fa-paperclip mr-2"></i>Documentos adjuntos
                                    </label>
                                    <div class="flex flex-wrap gap-3">
                                        <?php foreach($evidenciasValidas as $archivo): 
                                            $ruta = "../uploads/solicitudes/" . rawurlencode($archivo);
                                            $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                                            $archivoSeguro = htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8');
                                            
                                            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): 
                                        ?>
                                            <div 
                                                onclick="abrirImagen('<?= $ruta ?>')"
                                                class="group relative w-32 h-32 rounded-lg overflow-hidden border border-gray-200 hover:border-blue-400 cursor-pointer bg-white shadow-sm">
                                                <img 
                                                    src="<?= $ruta ?>" 
                                                    alt="<?= $archivoSeguro ?>"
                                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                                    onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                                                <div class="hidden absolute inset-0 bg-gray-100 flex flex-col items-center justify-center p-2">
                                                    <i class="fas fa-image text-gray-400 text-xl mb-2"></i>
                                                    <p class="text-xs text-gray-500 text-center">Imagen no disponible</p>
                                                </div>
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>
                                            </div>
                                        <?php else: ?>
                                            <a 
                                                href="<?= $ruta ?>" 
                                                target="_blank"
                                                class="group w-32 h-32 rounded-lg border border-gray-200 hover:border-blue-400 bg-white hover:bg-blue-50 transition-all shadow-sm flex flex-col items-center justify-center p-4">
                                                <i class="fas fa-file text-3xl text-gray-400 group-hover:text-blue-500 mb-3 transition-colors"></i>
                                                <span class="text-xs text-gray-600 group-hover:text-blue-600 text-center break-words w-full">
                                                    <?= $archivoSeguro ?>
                                                </span>
                                            </a>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Botones de acción -->
                                <div class="flex gap-3 pt-4 border-t border-gray-100">
                                    <button 
                                        onclick="procesarSolicitud(<?= $sol['id'] ?>, 'Aprobado', this)"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg transition font-semibold flex items-center justify-center gap-2 btn-action">
                                        <i class="fas fa-check-circle"></i>
                                        Aprobar Resolución
                                    </button>
                                    <button 
                                        onclick="procesarSolicitud(<?= $sol['id'] ?>, 'Rechazado', this)"
                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-lg transition font-semibold flex items-center justify-center gap-2 btn-action">
                                        <i class="fas fa-times-circle"></i>
                                        Rechazar Resolución
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</section>

<!-- Modal para ver imagen -->
<div id="modalImagen" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" onclick="cerrarModal()">
    <div class="bg-white rounded-xl max-w-4xl max-h-[90vh] relative shadow-2xl" onclick="event.stopPropagation()">
        <button onclick="cerrarModal()" class="absolute -top-10 right-0 w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors">
            <i class="fas fa-times text-lg"></i>
        </button>
        <div class="p-1">
            <img id="imagenModal" src="" alt="Evidencia" class="w-full h-auto max-h-[85vh] object-contain rounded-lg">
        </div>
    </div>
</div>

<script>
// Control de acordeones
function toggleAccordion(index) {
    const content = document.getElementById(`accordion-content-${index}`);
    const chevron = document.getElementById(`chevron-${index}`);
    
    // Toggle contenido
    content.classList.toggle('open');
    
    // Toggle chevron
    chevron.classList.toggle('open');
}

// Función para abrir imagen en modal
function abrirImagen(ruta) {
    const modal = document.getElementById('modalImagen');
    const img = document.getElementById('imagenModal');
    img.src = ruta;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('modalImagen').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});

// Función para procesar solicitud
function procesarSolicitud(id, estado, button) {
    const accionTexto = estado === 'Aprobado' ? 'aprobar' : 'rechazar';
    const accionDisplay = estado === 'Aprobado' ? 'aprobar' : 'rechazar';
    
    if (!confirm(`¿Estás seguro de que deseas ${accionDisplay} esta resolución?`)) {
        return;
    }

    let motivo = '';
    if (estado === 'Rechazado') {
        motivo = prompt('Por favor, ingresa el motivo del rechazo:', '');
        if (motivo === null) return; // Usuario canceló
        if (!motivo.trim()) {
            alert('Debes ingresar un motivo para rechazar la resolución.');
            return;
        }
    }

    // Deshabilitar todos los botones del acordeón
    const accordion = button.closest('.accordion-content');
    const buttons = accordion.querySelectorAll('.btn-action');
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Procesando...`;
    });

    // Ruta del controlador
    const controllerPath = '../controller/actualizarEstadoSolicitud.php';

    fetch(controllerPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            estado: estado,
            motivo: motivo || ''
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            alert(`Resolución ${accionTexto}da correctamente.`);
            
            // Recargar la página después de 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.error || 'Error al procesar la solicitud');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud. Por favor, intenta nuevamente.');
        
        // Rehabilitar botones en caso de error
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = btn.classList.contains('bg-green-600') 
                ? '<i class="fas fa-check-circle"></i> Aprobar Resolución'
                : '<i class="fas fa-times-circle"></i> Rechazar Resolución';
        });
    });
}

// Inicialización: colapsar todos los acordeones excepto el primero
document.addEventListener('DOMContentLoaded', function() {
    const totalAccordions = <?= count($solicitudes) ?>;
    
    // Abrir solo el primer acordeón por defecto
    if (totalAccordions > 0) {
        toggleAccordion(0);
    }
});
</script>