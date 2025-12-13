<?php
require_once __DIR__ . '/../../../controller/bienestar-registroController.php';

// Ruta base para las peticiones AJAX (relativa desde views/)
$basePath = '..';

// Nota: El procesamiento del POST se hace en dashboard-bienestar.php antes de cualquier output HTML
$status = $_GET['status'] ?? null;
$alerts = [];

// Mostrar mensaje de éxito al registrar resolución y mensaje de error cuando falle
if ($status === 'resolucion_created') {
    $alerts[] = ['type' => 'success', 'text' => 'Resolución registrada exitosamente.'];
} elseif ($status === 'error') {
    $alerts[] = ['type' => 'error', 'text' => 'Ocurrió un problema al procesar la solicitud.'];
}

// Recuperar errores y datos previos de la sesión
$errors = $_SESSION['bienestar_errors'] ?? [];
$previousData = $_SESSION['bienestar_previous_data'] ?? [];

// Limpiar datos de sesión después de usarlos
unset($_SESSION['bienestar_errors'], $_SESSION['bienestar_previous_data']);

// Obtener resoluciones para el select
$ctrl = new BienestarRegistroController();
$resoluciones = $ctrl->listarResoluciones();

$oldValue = function (string $key) use ($previousData): string {
    return htmlspecialchars($previousData[$key] ?? '', ENT_QUOTES, 'UTF-8');
};
?>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}
@keyframes slideIn {
    from { opacity: 0; transform: translateX(100%); }
    to { opacity: 1; transform: translateX(0); }
}
.card-anim {
    animation: fadeInUp 0.6s ease-out forwards;
}
.form-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}
.form-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}
.animate-shimmer {
    animation: shimmer 3s infinite;
}
.animate-slide-in {
    animation: slideIn 0.3s ease-out;
}
input:focus, textarea:focus {
    outline: none;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.6;
    filter: invert(0.5);
}
input[type="date"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}
</style>

<!-- Alertas mejoradas -->
<?php if (!empty($alerts)): ?>
    <div class="mb-4 space-y-2 card-anim">
        <?php foreach ($alerts as $alert): ?>
            <div class="relative flex items-center gap-3 p-3 rounded-lg shadow-md backdrop-blur-sm <?= $alert['type'] === 'success' ? 'bg-gradient-to-r from-green-500/10 to-emerald-500/10 border border-green-500/30' : 'bg-gradient-to-r from-red-500/10 to-rose-500/10 border border-red-500/30' ?>">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg <?= $alert['type'] === 'success' ? 'bg-green-500/20' : 'bg-red-500/20' ?> flex items-center justify-center">
                    <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600' ?> text-sm"></i>
                </div>
                <p class="font-bold text-sm <?= $alert['type'] === 'success' ? 'text-green-800' : 'text-red-800' ?>">
                    <?= htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Errores mejorados -->
<?php if (!empty($errors)): ?>
    <div class="mb-4 p-3 rounded-lg bg-gradient-to-r from-red-500/10 to-rose-500/10 border border-red-500/30 shadow-md backdrop-blur-sm card-anim">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
            </div>
            <div class="flex-1">
                <p class="font-bold text-red-800 text-sm mb-2">Errores encontrados:</p>
                <ul class="space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li class="flex items-start gap-2 text-red-700 text-xs">
                            <span class="text-red-500 mt-0.5">•</span>
                            <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Grid de Formularios -->
<section class="grid grid-cols-1 xl:grid-cols-1 gap-6 max-w-4xl mx-auto">

    <!-- Formulario Resolución -->
    <div class="form-card group relative bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200/50 card-anim">
        <!-- Header con gradiente mejorado -->
        <div class="relative bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 p-6 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent animate-shimmer" style="background-size: 200% 100%;"></div>
            <div class="relative flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shadow-xl border-2 border-white/30">
                    <i class="fas fa-file-signature text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-2xl mb-1">Nueva Resolución</h3>
                    <p class="text-white/90 text-sm font-medium">Registrar resolución institucional</p>
                </div>
            </div>
        </div>

        <!-- Formulario -->
        <form method="POST" action="dashboard-bienestar.php?pagina=registro-bienestar-estudiantil" enctype="multipart/form-data" id="formResolucion" class="p-6 md:p-8 space-y-6 bg-gradient-to-b from-white via-gray-50/50 to-white">
            <input type="hidden" name="accion" value="agregar_resolucion">
            
            <!-- Número de resolución mejorado -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                    <i class="fas fa-hashtag text-blue-600"></i>
                    <span>N° Resolución</span>
                    <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="numero_resolucion" 
                    required
                    value="<?= $oldValue('numero_resolucion') ?>" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm font-medium placeholder-gray-400 shadow-sm hover:border-gray-400" 
                    placeholder="Ej: RES-2025-001">
            </div>

            <!-- Título mejorado -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                    <i class="fas fa-heading text-blue-600"></i>
                    <span>Título</span>
                    <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="titulo" 
                    required
                    value="<?= $oldValue('titulo') ?>" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm font-medium placeholder-gray-400 shadow-sm hover:border-gray-400" 
                    placeholder="Título de la resolución">
            </div>

            <!-- Fechas y monto de descuento -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-600"></i>
                        <span>Fecha Resolución</span>
                        <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="fecha_inicio" 
                        required
                        value="<?= $oldValue('fecha_inicio') ?>" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm font-medium shadow-sm hover:border-gray-400">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-calendar-times text-blue-600"></i>
                        <span>Fecha Fin</span>
                    </label>
                    <input 
                        type="date" 
                        name="fecha_fin" 
                        value="<?= $oldValue('fecha_fin') ?>" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm font-medium shadow-sm hover:border-gray-400">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-money-bill-wave text-blue-600"></i>
                        <span>Monto de Descuento</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            name="monto_descuento" 
                            step="0.01" 
                            min="0" 
                            value="<?= $oldValue('monto_descuento') ?>" 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm font-medium placeholder-gray-400 shadow-sm hover:border-gray-400" 
                            placeholder="Ej: 150.00">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <span class="text-gray-400 font-semibold text-sm">S/</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descripción mejorada -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                    <i class="fas fa-align-left text-blue-600"></i>
                    <span>Descripción / Observaciones</span>
                </label>
                <textarea 
                    rows="4" 
                    name="texto_respaldo" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all resize-none text-sm font-medium placeholder-gray-400 shadow-sm hover:border-gray-400" 
                    placeholder="Detalles adicionales o comentarios sobre la resolución"><?= $oldValue('texto_respaldo') ?></textarea>
            </div>

            <!-- Documento mejorado -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                    <i class="fas fa-file-upload text-blue-600"></i>
                    <span>Documento</span>
                    <span class="text-sm text-gray-500 font-normal">(PDF, DOC, DOCX)</span>
                </label>
                <div class="relative">
                    <div class="flex items-center justify-center w-full px-6 py-8 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-400 hover:bg-blue-50/30 transition-all cursor-pointer bg-gray-50 group">
                        <input 
                            type="file" 
                            name="documento" 
                            accept=".pdf,.doc,.docx"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 group-hover:text-blue-500 mb-3 transition-all transform group-hover:scale-110"></i>
                            <p class="text-sm font-semibold text-gray-700 group-hover:text-blue-600 mb-1">
                                <span class="text-blue-600 underline">Click para subir</span> o arrastra el archivo
                            </p>
                            <p class="text-xs text-gray-500">PDF, DOC, DOCX (máx. 10MB)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón submit mejorado -->
            <div class="pt-4">
                <button 
                    type="submit"
                    class="w-full py-4 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 text-white font-bold text-base rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02] flex items-center justify-center gap-3 group">
                    <i class="fas fa-file-plus text-lg group-hover:rotate-12 transition-transform"></i>
                    <span>Agregar Resolución</span>
                    <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition-transform"></i>
                </button>
            </div>
        </form>
    </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para mostrar notificación toast
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-2xl shadow-2xl animate-slide-in ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} text-xl"></i>
                <span class="font-semibold">${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Mejorar experiencia de carga de archivos
    const fileInput = document.querySelector('input[type="file"][name="documento"]');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    showToast('El archivo es demasiado grande. Máximo 10MB', 'error');
                    this.value = '';
                    return;
                }
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                showToast(`Archivo seleccionado: ${fileName} (${fileSize} MB)`, 'success');
            }
        });
    }

    // Búsqueda de estudiante por DNI
    const btnBuscarDNI = document.getElementById('btnBuscarDNI');
    const dniInput = document.getElementById('dni');
    const infoEstudiante = document.getElementById('infoEstudiante');
    const nombreEstudiante = document.getElementById('nombreEstudiante');
    const programaEstudios = document.getElementById('programa_estudios');
    const ciclo = document.getElementById('ciclo');
    const turno = document.getElementById('turno');
    const estudianteIdInput = document.getElementById('estudiante_id');

    if (btnBuscarDNI && dniInput) {
        // Validación en tiempo real del DNI
        dniInput.addEventListener('input', function() {
            const dni = this.value.trim();
            if (dni.length === 8 && /^\d+$/.test(dni)) {
                this.classList.remove('border-red-300');
                this.classList.add('border-green-300');
            } else if (dni.length > 0) {
                this.classList.remove('border-green-300');
                this.classList.add('border-red-300');
            } else {
                this.classList.remove('border-red-300', 'border-green-300');
            }
        });

        // Buscar estudiante al presionar Enter
        dniInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnBuscarDNI.click();
            }
        });

        btnBuscarDNI.addEventListener('click', function() {
            const dni = dniInput.value.trim();
            
            if (dni.length !== 8) {
                showToast('El DNI debe tener exactamente 8 dígitos', 'error');
                dniInput.focus();
                return;
            }

            if (!/^\d+$/.test(dni)) {
                showToast('El DNI solo debe contener números', 'error');
                dniInput.focus();
                return;
            }

            // Mostrar loading
            const originalHTML = btnBuscarDNI.innerHTML;
            btnBuscarDNI.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span class="hidden sm:inline">Buscando...</span>';
            btnBuscarDNI.disabled = true;
            btnBuscarDNI.classList.add('opacity-75', 'cursor-not-allowed');

            fetch('<?= $basePath ?>/controller/buscarEstudiante.php?dni=' + dni)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.estudiante) {
                        const est = data.estudiante;
                        estudianteIdInput.value = est.id || '';
                        nombreEstudiante.textContent = est.nombre_completo || 'N/A';
                        programaEstudios.value = est.programa_nombre || 'N/A';
                        ciclo.value = est.ciclo || 'N/A';
                        const turnoValue = est.turno || '';
                        turno.value = turnoValue === 'D' || turnoValue === 'Diurno' ? 'Diurno' : 
                                      (turnoValue === 'V' || turnoValue === 'Vespertino' ? 'Vespertino' : turnoValue || 'N/A');
                        
                        // Mostrar información con animación
                        infoEstudiante.classList.remove('hidden');
                        showToast('Estudiante encontrado correctamente', 'success');
                    } else {
                        showToast(data.message || 'No se encontró el estudiante con ese DNI', 'error');
                        infoEstudiante.classList.add('hidden');
                        programaEstudios.value = '';
                        ciclo.value = '';
                        turno.value = '';
                        estudianteIdInput.value = '';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error al buscar el estudiante. Intenta nuevamente.', 'error');
                })
                .finally(() => {
                    btnBuscarDNI.innerHTML = originalHTML;
                    btnBuscarDNI.disabled = false;
                    btnBuscarDNI.classList.remove('opacity-75', 'cursor-not-allowed');
                });
        });
    }

    // Validación en tiempo real del porcentaje
    const porcentajeInput = document.querySelector('input[name="porcentaje_descuento"]');
    if (porcentajeInput) {
        porcentajeInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value < 0 || value > 100) {
                this.classList.add('border-red-300');
                showToast('El porcentaje debe estar entre 0 y 100', 'error');
            } else {
                this.classList.remove('border-red-300');
            }
        });
    }

    // Animación de entrada para los formularios
    const forms = document.querySelectorAll('form');
    forms.forEach((form, index) => {
        form.style.opacity = '0';
        form.style.transform = 'translateY(20px)';
        setTimeout(() => {
            form.style.transition = 'all 0.6s ease-out';
            form.style.opacity = '1';
            form.style.transform = 'translateY(0)';
        }, index * 200);
    });
});
</script>
