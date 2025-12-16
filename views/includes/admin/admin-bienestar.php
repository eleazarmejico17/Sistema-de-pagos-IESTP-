<?php
require_once __DIR__ . '/../../../controller/admin-bienestarController.php';

$ctrl = new BienestarController();
$moduleParam = isset($_GET['modulo']) ? 'modulo' : 'pagina';
$validTargets = ['admin-bienestar', 'bienestar'];
$moduleValue = $_GET[$moduleParam] ?? 'admin-bienestar';

if (!in_array($moduleValue, $validTargets, true)) {
    $moduleValue = 'admin-bienestar';
}

$redirectUrl = sprintf('%s?%s=%s', basename($_SERVER['PHP_SELF']), $moduleParam, $moduleValue);
$status = $_GET['status'] ?? null;
$alerts = [];

if ($status === 'created') {
    $alerts[] = ['type' => 'success', 'text' => 'Empleado registrado correctamente.'];
} elseif ($status === 'deleted') {
    $alerts[] = ['type' => 'success', 'text' => 'Empleado eliminado correctamente.'];
} elseif ($status === 'error') {
    $alerts[] = ['type' => 'error', 'text' => 'Ocurrió un problema al procesar la solicitud.'];
}

$errors = [];
$previousData = [];

// Eliminar empleado
if (isset($_GET['delete'])) {
    $deleteId = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);

    if ($deleteId) {
        $ctrl->eliminar($deleteId);
        header("Location: {$redirectUrl}&status=deleted");
        exit;
    }

    $errors[] = 'El identificador del empleado no es válido.';
}

$empleados = $ctrl->listar();
// Ruta correcta desde views/includes/admin/ hacia uploads/empleados/ en la raíz
$uploadsPath = '../../../uploads/empleados/';

$oldValue = function (string $key) use ($previousData): string {
    return htmlspecialchars($previousData[$key] ?? '', ENT_QUOTES, 'UTF-8');
};
?>

<!-- Encabezado mejorado -->
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 mb-8 shadow-lg">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Gestión de Empleados</h1>
            <p class="text-indigo-100 max-w-3xl">Empleados sin credenciales de acceso al sistema. Usa <span class="font-semibold text-white">Asignar credenciales</span> para crear su usuario de Bienestar o Dirección.</p>
        </div>
        <div class="bg-white/10 p-4 rounded-xl backdrop-blur-sm">
            <div class="text-center">
                <span class="text-white text-2xl font-bold block"><?= count($empleados) ?></span>
                <span class="text-indigo-200 text-sm">Empleados registrados</span>
            </div>
        </div>
    </div>
</div>

<!-- Alertas mejoradas -->
<?php if (!empty($alerts)): ?>
    <div class="space-y-4 mb-6">
        <?php foreach ($alerts as $alert): ?>
            <div class="p-4 rounded-xl border-l-4 <?= $alert['type'] === 'success' ? 'bg-green-50 text-green-800 border-green-500' : 'bg-red-50 text-red-800 border-red-500' ?> shadow-sm animate-fade-in">
                <div class="flex items-center">
                    <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-3 text-lg"></i>
                    <span class="font-medium"><?= htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Errores mejorados -->
<?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 shadow-sm">
        <div class="flex items-center mb-2">
            <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
            <h3 class="font-semibold text-red-700">Se encontraron los siguientes errores:</h3>
        </div>
        <ul class="list-disc pl-5 space-y-1 text-sm">
            <?php foreach ($errors as $error): ?>
                <li class="text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Tarjetas de empleados (Vista alternativa para móviles) -->
<div class="block md:hidden space-y-4 mb-8">
    <?php foreach ($empleados as $e): ?>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
        <div class="p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <?php if (!empty($e['foto_emp'])): ?>
                        <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($e['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" 
                             alt="Foto de <?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>" 
                             class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-lg"
                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCI+PHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiBmaWxsPSIjZTBlN2ViIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5Y2EzYWIiIGZvbnQtc2l6ZT0iMjAiPj88L3RleHQ+PC9zdmc+';">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-100 to-gray-300 border-2 border-white shadow-lg flex items-center justify-center">
                            <i class="fas fa-user text-gray-500 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full <?= (int) $e['estado'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= (int) $e['estado'] === 1 ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <p class="text-xs text-gray-500 font-medium">DNI</p>
                    <p class="font-semibold"><?= htmlspecialchars($e['dni_emp'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Cargo</p>
                    <p class="font-semibold">
                        <?php
                            switch ($e['cargo_emp']) {
                                case 'A': echo "Administrativo"; break;
                                case 'D': echo "Docente"; break;
                                case 'B': echo "Bienestar"; break;
                                default: echo "Sin definir";
                            }
                        ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Celular</p>
                    <p class="font-semibold"><?= htmlspecialchars($e['cel_emp'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Correo</p>
                    <p class="font-semibold truncate"><?= htmlspecialchars($e['mailp_emp'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            
            <div class="flex justify-between pt-4 border-t border-gray-100">
                <a href="dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios-sistema&asignar=<?= (int) $e['id'] ?>" 
                   class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 text-sm font-medium flex items-center">
                    <i class="fas fa-key mr-2"></i> Asignar credenciales
                </a>
                <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&delete=<?= (int) $e['id'] ?>" 
                   class="px-4 py-2 bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition-colors duration-200 text-sm font-medium flex items-center"
                   onclick="return confirm('¿Eliminar empleado? Esta acción no asigna ni revoca accesos, elimina el registro del empleado.')">
                    <i class="fas fa-trash-alt mr-2"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>

<!-- TABLA (Vista de escritorio) -->
<div class="hidden md:block bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                <tr>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">DNI</th>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">Nombre Completo</th>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">Celular</th>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">Correo</th>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">Cargo</th>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">Foto</th>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">Estado</th>
                    <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($empleados as $index => $e): ?>
                <tr class="hover:bg-gray-50 transition-colors duration-150 <?= $index % 2 === 0 ? 'bg-gray-50/50' : '' ?>">
                    <td class="p-4">
                        <span class="font-mono font-semibold text-gray-800"><?= htmlspecialchars($e['dni_emp'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center">
                            <?php if (!empty($e['foto_emp'])): ?>
                                <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($e['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" 
                                     alt="Foto de <?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>" 
                                     class="w-10 h-10 rounded-full object-cover border-2 border-white shadow mr-3"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCI+PHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiBmaWxsPSIjZTBlN2ViIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5Y2EzYWIiIGZvbnQtc2l6ZT0iMjAiPj88L3RleHQ+PC9zdmc+';">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-300 border-2 border-white shadow mr-3 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                            <?php endif; ?>
                            <span class="font-medium text-gray-800"><?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </td>
                    <td class="p-4">
                        <span class="text-gray-700"><?= htmlspecialchars($e['cel_emp'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="p-4">
                        <a href="mailto:<?= htmlspecialchars($e['mailp_emp'], ENT_QUOTES, 'UTF-8') ?>" class="text-indigo-600 hover:text-indigo-800 hover:underline truncate block max-w-xs">
                            <?= htmlspecialchars($e['mailp_emp'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td class="p-4">
                        <?php
                            $cargoClass = '';
                            switch ($e['cargo_emp']) {
                                case 'A': 
                                    $cargoText = "Administrativo";
                                    $cargoClass = 'bg-blue-100 text-blue-800';
                                    break;
                                case 'D': 
                                    $cargoText = "Docente";
                                    $cargoClass = 'bg-purple-100 text-purple-800';
                                    break;
                                case 'B': 
                                    $cargoText = "Bienestar";
                                    $cargoClass = 'bg-indigo-100 text-indigo-800';
                                    break;
                                default: 
                                    $cargoText = "Sin definir";
                                    $cargoClass = 'bg-gray-100 text-gray-800';
                            }
                        ?>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?= $cargoClass ?>">
                            <?= $cargoText ?>
                        </span>
                    </td>
                    <td class="p-4">
                        <?php if (!empty($e['foto_emp'])): ?>
                            <div class="relative group">
                                <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($e['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" 
                                     alt="Foto de <?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>" 
                                     class="w-12 h-12 rounded-full object-cover border-2 border-white shadow cursor-pointer transition-transform duration-200 group-hover:scale-110"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCI+PHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiBmaWxsPSIjZTBlN2ViIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5Y2EzYWIiIGZvbnQtc2l6ZT0iMjAiPj88L3RleHQ+PC9zdmc+';">
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
                                    Ver foto
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-gray-100 to-gray-300 border-2 border-white shadow flex items-center justify-center">
                                <i class="fas fa-user text-gray-500"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <span class="inline-flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2 <?= (int) $e['estado'] === 1 ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                            <span class="font-medium <?= (int) $e['estado'] === 1 ? 'text-green-700' : 'text-red-700' ?>">
                                <?= (int) $e['estado'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </span>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center space-x-3">
                            <a href="dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios-sistema&asignar=<?= (int) $e['id'] ?>" 
                               class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors duration-200 text-sm font-medium flex items-center"
                               title="Asignar credenciales de acceso">
                                <i class="fas fa-key mr-2"></i>
                                <span>Asignar</span>
                            </a>
                            <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&delete=<?= (int) $e['id'] ?>" 
                               class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-200 text-sm font-medium flex items-center"
                               onclick="return confirm('¿Eliminar empleado? Esta acción no asigna ni revoca accesos, elimina el registro del empleado.')"
                               title="Eliminar empleado">
                                <i class="fas fa-trash-alt mr-2"></i>
                                <span>Eliminar</span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($empleados)): ?>
    <div class="p-12 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
            <i class="fas fa-users text-gray-400 text-3xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay empleados registrados</h3>
        <p class="text-gray-500 max-w-md mx-auto">No se encontraron empleados sin credenciales en el sistema.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Añade este estilo para las animaciones si no las tienes -->
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* Mejoras para scroll en la tabla */
.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: #c7d2fe #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #c7d2fe;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #a5b4fc;
}
</style>