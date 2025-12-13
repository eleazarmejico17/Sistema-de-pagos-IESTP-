<?php
require_once __DIR__ . '/../../../controller/admin-usuariosController.php';
require_once __DIR__ . '/../../../controller/NotificacionHelper.php';
require_once __DIR__ . '/../../../config/conexion.php';

$ctrl = new EstudiantesController();
$status = $_GET['status'] ?? null;
$alerts = [];
$editData = null;

// Obtener datos del estudiante para editar
if (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_SANITIZE_NUMBER_INT);
    if ($editId) {
        $editData = $ctrl->obtener($editId);
        if (!$editData) {
            $alerts[] = ['type' => 'error', 'text' => 'Estudiante no encontrado'];
        }
    }
}

// La lógica de actualización se procesa en dashboard-admin.php antes del output

if ($status === 'created') {
    $alerts[] = ['type' => 'success', 'text' => 'Estudiante registrado correctamente.'];
} elseif ($status === 'updated') {
    $alerts[] = ['type' => 'success', 'text' => 'Estudiante actualizado correctamente.'];
} elseif ($status === 'deleted') {
    $alerts[] = ['type' => 'success', 'text' => 'Estudiante eliminado correctamente.'];
} elseif ($status === 'error') {
    $alerts[] = ['type' => 'error', 'text' => 'No se pudo completar la operación.'];
}

$errors = [];

// La lógica de eliminación se procesa en dashboard-admin.php antes del output

$estudiantes = $ctrl->listar();
?>

<div class="max-w-7xl mx-auto space-y-6">
    <!-- ALERTAS -->
    <?php if (!empty($alerts)): ?>
        <?php foreach ($alerts as $alert): ?>
            <div class="p-4 rounded-lg border-l-4 <?= $alert['type'] === 'success' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-red-50 border-red-500 text-red-700' ?>">
                <div class="flex items-center gap-3">
                    <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                    <p class="font-semibold"><?= htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-lg border-l-4 bg-red-50 border-red-500 text-red-700">
            <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO DE EDICIÓN -->
    <?php if ($editData): ?>
        <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-2xl shadow-xl border border-gray-200">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg">
                    <i class="fas fa-edit text-white text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-3xl font-bold text-gray-800">Editar Estudiante</h2>
                    <p class="text-gray-500 text-sm mt-1">Modifica la información del estudiante</p>
                </div>
                <a href="dashboard-admin.php?pagina=usuarios" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-all flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    <span>Cancelar</span>
                </a>
            </div>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" value="<?= htmlspecialchars($editData['id'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- DNI -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-id-card text-blue-600"></i>
                            <span>DNI <span class="text-red-500">*</span></span>
                        </label>
                        <input 
                            type="text" 
                            name="dni_est"
                            value="<?= htmlspecialchars($editData['dni_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="8"
                            required
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="12345678"
                        >
                    </div>

                    <!-- Apellido Paterno -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-user text-blue-600"></i>
                            <span>Apellido Paterno</span>
                        </label>
                        <input 
                            type="text" 
                            name="ap_est"
                            value="<?= htmlspecialchars($editData['ap_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="Apellido paterno"
                        >
                    </div>

                    <!-- Apellido Materno -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-user text-blue-600"></i>
                            <span>Apellido Materno</span>
                        </label>
                        <input 
                            type="text" 
                            name="am_est"
                            value="<?= htmlspecialchars($editData['am_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="Apellido materno"
                        >
                    </div>

                    <!-- Nombre -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-user text-blue-600"></i>
                            <span>Nombres <span class="text-red-500">*</span></span>
                        </label>
                        <input 
                            type="text" 
                            name="nom_est"
                            value="<?= htmlspecialchars($editData['nom_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="Nombres"
                        >
                    </div>

                    <!-- Sexo -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-venus-mars text-blue-600"></i>
                            <span>Sexo</span>
                        </label>
                        <select 
                            name="sex_est"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                        >
                            <option value="">Seleccione...</option>
                            <option value="M" <?= ($editData['sex_est'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($editData['sex_est'] ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                        </select>
                    </div>

                    <!-- Teléfono -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-phone text-blue-600"></i>
                            <span>Teléfono</span>
                        </label>
                        <input 
                            type="text" 
                            name="cel_est"
                            value="<?= htmlspecialchars($editData['cel_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="9"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="987654321"
                        >
                    </div>

                    <!-- Dirección -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-map-marker-alt text-blue-600"></i>
                            <span>Dirección</span>
                        </label>
                        <input 
                            type="text" 
                            name="dir_est"
                            value="<?= htmlspecialchars($editData['dir_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="Dirección"
                        >
                    </div>

                    <!-- Correo Personal -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-envelope text-blue-600"></i>
                            <span>Correo Personal</span>
                        </label>
                        <input 
                            type="email" 
                            name="mailp_est"
                            value="<?= htmlspecialchars($editData['mailp_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="correo@ejemplo.com"
                        >
                    </div>

                    <!-- Correo Institucional -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-university text-blue-600"></i>
                            <span>Correo Institucional</span>
                        </label>
                        <input 
                            type="email" 
                            name="maili_est"
                            value="<?= htmlspecialchars($editData['maili_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            placeholder="correo@institutocajas.edu.pe"
                        >
                    </div>

                    <!-- Fecha de Nacimiento -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-calendar text-blue-600"></i>
                            <span>Fecha de Nacimiento</span>
                        </label>
                        <input 
                            type="date" 
                            name="fecnac_est"
                            value="<?= !empty($editData['fecnac_est']) && $editData['fecnac_est'] !== '0000-00-00' ? date('Y-m-d', strtotime($editData['fecnac_est'])) : '' ?>"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                        >
                    </div>

                    <!-- Estado -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-semibold text-gray-700">
                            <i class="fas fa-toggle-on text-blue-600"></i>
                            <span>Estado</span>
                        </label>
                        <select 
                            name="estado"
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                        >
                            <option value="1" <?= ($editData['estado'] ?? 0) == 1 ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= ($editData['estado'] ?? 0) == 0 ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-3 transform hover:scale-[1.02]"
                >
                    <i class="fas fa-save text-xl"></i>
                    <span class="text-lg">Guardar Cambios</span>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- TABLA MEJORADA -->
    <?php if (!$editData): ?>
        <?php if (empty($estudiantes)): ?>
            <div class="bg-white rounded-2xl shadow-xl p-12 text-center border border-gray-200">
                <div class="inline-block p-6 bg-gray-100 rounded-full mb-6">
                    <i class="fas fa-users text-6xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-700 mb-3">No hay estudiantes registrados</h3>
                <p class="text-gray-500 text-lg">Aún no se han registrado estudiantes en el sistema.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-users text-indigo-600"></i>
                        Gestión de Estudiantes
                    </h2>
                    <p class="text-gray-500 mt-1">Lista completa de estudiantes registrados en el sistema</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-hashtag text-gray-500"></i>
                                        <span>ID</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-id-card text-gray-500"></i>
                                        <span>DNI</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user text-gray-500"></i>
                                        <span>Nombre Completo</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar text-gray-500"></i>
                                        <span>Edad</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-venus-mars text-gray-500"></i>
                                        <span>Sexo</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-check-circle text-gray-500"></i>
                                        <span>Estado</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center justify-center gap-2">
                                        <i class="fas fa-cog text-gray-500"></i>
                                        <span>Acciones</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($estudiantes as $e): ?>
                            <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 font-bold text-sm">
                                        <?= htmlspecialchars($e['id'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?= htmlspecialchars($e['dni_est'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?= htmlspecialchars($e['nombre_completo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600">
                                        <?= is_numeric($e['edad'] ?? null) ? htmlspecialchars($e['edad'], ENT_QUOTES, 'UTF-8') . ' años' : 'N/A' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($e['sexo'] ?? '') === "M"): ?>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-semibold inline-flex items-center gap-1">
                                            <i class="fas fa-mars"></i>
                                            Masculino
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-pink-100 text-pink-700 text-xs rounded-full font-semibold inline-flex items-center gap-1">
                                            <i class="fas fa-venus"></i>
                                            Femenino
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($e['estado'] ?? 0) == 1): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full font-semibold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full font-semibold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="dashboard-admin.php?pagina=usuarios&edit=<?= $e['id'] ?>"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                            <i class="fas fa-edit"></i>
                                            <span>Editar</span>
                                        </a>
                                        <a href="dashboard-admin.php?pagina=usuarios&delete=<?= $e['id'] ?>"
                                           onclick="return confirm('¿Estás seguro de eliminar este estudiante? Esta acción no se puede deshacer.')"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>Eliminar</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Auto-ocultar alertas después de 4 segundos
setTimeout(() => {
    const alerts = document.querySelectorAll('.bg-emerald-50, .bg-red-50');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease-out';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 4000);

// Scroll suave al formulario cuando se está editando
<?php if ($editData): ?>
document.addEventListener('DOMContentLoaded', function() {
    const formCard = document.querySelector('.bg-gradient-to-br.from-white');
    if (formCard) {
        setTimeout(() => {
            formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
});
<?php endif; ?>
</script>
