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

if ($status === 'access_created') {
    $alerts[] = ['type' => 'success', 'text' => 'Credenciales asignadas correctamente.'];
} elseif ($status === 'access_updated') {
    $alerts[] = ['type' => 'success', 'text' => 'Credenciales actualizadas correctamente.'];
}

if (!empty($_SESSION['usuarios_estudiantes_errors'])) {
    foreach ((array) $_SESSION['usuarios_estudiantes_errors'] as $msg) {
        $errors[] = (string) $msg;
    }
    unset($_SESSION['usuarios_estudiantes_errors']);
}

// La lógica de eliminación se procesa en dashboard-admin.php antes del output

$estudiantes = $ctrl->listar();

$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->prepare('SELECT id, usuario, estuempleado FROM usuarios WHERE tipo = 2');
$stmt->execute();
$accesosEstudiantes = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $accesosEstudiantes[(int)$row['estuempleado']] = $row;
}
?>

<div class="max-w-7xl mx-auto space-y-6">
    <!-- HEADER MEJORADO -->
    <?php if (!$editData): ?>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestión de Estudiantes</h1>
            <p class="text-gray-600 mt-2">Administra los estudiantes registrados en el sistema</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600 bg-blue-50 px-4 py-2 rounded-lg">
            <i class="fas fa-users text-blue-600"></i>
            <span>Total: <span class="font-bold"><?= count($estudiantes) ?></span> estudiantes</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ALERTAS MEJORADAS -->
    <?php if (!empty($alerts) || !empty($errors)): ?>
    <div class="space-y-3">
        <?php foreach ($alerts as $alert): ?>
            <div class="rounded-xl border-l-4 <?= $alert['type'] === 'success' ? 'border-emerald-500 bg-emerald-50/80' : 'border-red-500 bg-red-50/80' ?> p-4 shadow-sm animate-fade-in">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle text-emerald-600' : 'fa-exclamation-circle text-red-600' ?> text-lg"></i>
                    </div>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($errors)): ?>
            <div class="rounded-xl border-l-4 border-red-500 bg-red-50/80 p-4 shadow-sm animate-fade-in">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800 mb-2">Se encontraron los siguientes errores:</p>
                        <ul class="list-disc pl-5 space-y-1 text-gray-700">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- FORMULARIO DE EDICIÓN MEJORADO -->
    <?php if ($editData): ?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <!-- Header del formulario -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                            <i class="fas fa-user-edit text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white">Editar Estudiante</h2>
                            <p class="text-blue-100 text-sm">Modifica la información del estudiante</p>
                        </div>
                    </div>
                    <a href="dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-all backdrop-blur-sm">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                </div>
            </div>

            <!-- Contenido del formulario -->
            <div class="p-8">
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($editData['id'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Grupo: Datos Personales -->
                        <div class="md:col-span-2 lg:col-span-3">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                <i class="fas fa-user-circle text-blue-600 mr-2"></i>
                                Datos Personales
                            </h3>
                        </div>

                        <!-- DNI -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-id-card text-blue-600 text-sm"></i>
                                    DNI <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    name="dni_est"
                                    value="<?= htmlspecialchars($editData['dni_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="8"
                                    required
                                    class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700 bg-white"
                                    placeholder="12345678"
                                >
                                <i class="fas fa-hashtag absolute left-3 top-3.5 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Apellido Paterno -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                    Apellido Paterno
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="ap_est"
                                value="<?= htmlspecialchars($editData['ap_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                                placeholder="Apellido paterno"
                            >
                        </div>

                        <!-- Apellido Materno -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                    Apellido Materno
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="am_est"
                                value="<?= htmlspecialchars($editData['am_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                                placeholder="Apellido materno"
                            >
                        </div>

                        <!-- Nombre -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-signature text-blue-600 text-sm"></i>
                                    Nombres <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="nom_est"
                                value="<?= htmlspecialchars($editData['nom_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                                placeholder="Nombres"
                            >
                        </div>

                        <!-- Sexo -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-venus-mars text-blue-600 text-sm"></i>
                                    Sexo
                                </span>
                            </label>
                            <select 
                                name="sex_est"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            >
                                <option value="">Seleccione...</option>
                                <option value="M" <?= ($editData['sex_est'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                <option value="F" <?= ($editData['sex_est'] ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                            </select>
                        </div>

                        <!-- Fecha de Nacimiento -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-birthday-cake text-blue-600 text-sm"></i>
                                    Fecha de Nacimiento
                                </span>
                            </label>
                            <input 
                                type="date" 
                                name="fecnac_est"
                                value="<?= !empty($editData['fecnac_est']) && $editData['fecnac_est'] !== '0000-00-00' ? date('Y-m-d', strtotime($editData['fecnac_est'])) : '' ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            >
                        </div>

                        <!-- Grupo: Información de Contacto -->
                        <div class="md:col-span-2 lg:col-span-3 mt-2">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                <i class="fas fa-address-book text-blue-600 mr-2"></i>
                                Información de Contacto
                            </h3>
                        </div>

                        <!-- Teléfono -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-phone text-blue-600 text-sm"></i>
                                    Teléfono
                                </span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    name="cel_est"
                                    value="<?= htmlspecialchars($editData['cel_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="9"
                                    class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                                    placeholder="987654321"
                                >
                                <i class="fas fa-mobile-alt absolute left-3 top-3.5 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-blue-600 text-sm"></i>
                                    Dirección
                                </span>
                            </label>
                            <input 
                                type="text" 
                                name="dir_est"
                                value="<?= htmlspecialchars($editData['dir_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                                placeholder="Dirección"
                            >
                        </div>

                        <!-- Correo Personal -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-envelope text-blue-600 text-sm"></i>
                                    Correo Personal
                                </span>
                            </label>
                            <input 
                                type="email" 
                                name="mailp_est"
                                value="<?= htmlspecialchars($editData['mailp_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                                placeholder="correo@ejemplo.com"
                            >
                        </div>

                        <!-- Correo Institucional -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-university text-blue-600 text-sm"></i>
                                    Correo Institucional
                                </span>
                            </label>
                            <input 
                                type="email" 
                                name="maili_est"
                                value="<?= htmlspecialchars($editData['maili_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                                placeholder="correo@institutocajas.edu.pe"
                            >
                        </div>

                        <!-- Grupo: Configuración -->
                        <div class="md:col-span-2 lg:col-span-3 mt-2">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                <i class="fas fa-cog text-blue-600 mr-2"></i>
                                Configuración
                            </h3>
                        </div>

                        <!-- Estado -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-toggle-on text-blue-600 text-sm"></i>
                                    Estado
                                </span>
                            </label>
                            <select 
                                name="estado"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            >
                                <option value="1" <?= ($editData['estado'] ?? 0) == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ($editData['estado'] ?? 0) == 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botón de Guardar -->
                    <div class="pt-6 border-t border-gray-200">
                        <button 
                            type="submit"
                            class="w-full md:w-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-3"
                        >
                            <i class="fas fa-save"></i>
                            <span>Guardar Cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABLA MEJORADA -->
    <?php if (!$editData): ?>
        <?php if (empty($estudiantes)): ?>
            <!-- Estado Vacío Mejorado -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-blue-50 rounded-full mb-6">
                    <i class="fas fa-users text-4xl text-blue-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No hay estudiantes registrados</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">Comienza agregando estudiantes al sistema para gestionar sus pagos y descuentos.</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all">
                        <i class="fas fa-plus mr-2"></i>
                        Agregar Primer Estudiante
                    </button>
                    <button class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-all">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Actualizar Lista
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Tabla Mejorada -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Header de la tabla -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Lista de Estudiantes</h2>
                            <p class="text-sm text-gray-600 mt-1">Gestiona la información de los estudiantes</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <input type="text" placeholder="Buscar estudiante..." 
                                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                            </div>
                            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-all">
                                <i class="fas fa-filter mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla Responsiva -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-500">#</span>
                                        ID
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-id-card text-gray-500 text-sm"></i>
                                        DNI
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user text-gray-500 text-sm"></i>
                                        Estudiante
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-info-circle text-gray-500 text-sm"></i>
                                        Información
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-check-circle text-gray-500 text-sm"></i>
                                        Estado
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center justify-end gap-2">
                                        <i class="fas fa-cog text-gray-500 text-sm"></i>
                                        Acciones
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($estudiantes as $e): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <!-- ID -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-semibold text-sm">
                                            <?= htmlspecialchars($e['id'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </td>
                                
                                <!-- DNI -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($e['dni_est'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                
                                <!-- Nombre Completo -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center">
                                                <span class="text-white font-semibold text-sm">
                                                    <?= substr(htmlspecialchars($e['ap_est'] ?? '', ENT_QUOTES, 'UTF-8'), 0, 1) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($e['nombre_completo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($e['maili_est'] ?? 'Sin correo', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Información -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-sm text-gray-900">
                                            <i class="fas fa-calendar-alt text-gray-400 text-xs mr-2"></i>
                                            <?= is_numeric($e['edad'] ?? null) ? htmlspecialchars($e['edad'], ENT_QUOTES, 'UTF-8') . ' años' : 'N/A' ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas <?= ($e['sexo'] ?? '') === 'M' ? 'fa-mars text-blue-500' : 'fa-venus text-pink-500' ?> text-xs mr-2"></i>
                                            <?= ($e['sexo'] ?? '') === 'M' ? 'Masculino' : 'Femenino' ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Estado -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($e['estado'] ?? 0) == 1): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Acciones -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Botón Editar -->
                                        <a href="dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios&edit=<?= $e['id'] ?>"
                                           class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-medium transition-colors"
                                           title="Editar estudiante">
                                            <i class="fas fa-edit text-sm"></i>
                                            <span>Editar</span>
                                        </a>
                                        
                                        <!-- Botón Credenciales -->
                                        <button type="button"
                                           class="inline-flex items-center gap-2 px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-sm font-medium transition-colors"
                                           onclick="document.getElementById('cred-est-<?= (int)$e['id'] ?>').classList.toggle('hidden')"
                                           title="<?= isset($accesosEstudiantes[(int)$e['id']]) ? 'Actualizar credenciales' : 'Asignar credenciales' ?>">
                                            <i class="fas fa-key text-sm"></i>
                                            <span>Credenciales</span>
                                        </button>
                                        
                                        <!-- Botón Eliminar -->
                                        <a href="dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios&delete=<?= $e['id'] ?>"
                                           onclick="return confirm('¿Estás seguro de eliminar este estudiante? Esta acción no se puede deshacer.')"
                                           class="inline-flex items-center gap-2 px-3 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg text-sm font-medium transition-colors"
                                           title="Eliminar estudiante">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                            <span>Eliminar</span>
                                        </a>
                                    </div>
                                    
                                    <!-- Formulario de Credenciales -->
                                    <div id="cred-est-<?= (int)$e['id'] ?>" class="hidden mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="mb-3">
                                            <div class="text-sm font-medium text-gray-700 mb-1">Usuario del sistema:</div>
                                            <div class="text-sm text-gray-900 bg-white px-3 py-2 rounded border">
                                                <?= htmlspecialchars(strtolower((string)($e['dni_est'] ?? '')) . '@institutocajas.edu.pe', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                        
                                        <form method="POST" action="dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios" class="space-y-3">
                                            <input type="hidden" name="accion" value="asignar_acceso_estudiante" />
                                            <input type="hidden" name="estudiante_id" value="<?= (int)$e['id'] ?>" />
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Contraseña <span class="text-red-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <input type="password" 
                                                           name="password" 
                                                           required 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                           placeholder="Ingrese la contraseña">
                                                    <i class="fas fa-lock absolute right-3 top-2.5 text-gray-400 text-sm"></i>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button"
                                                        onclick="document.getElementById('cred-est-<?= (int)$e['id'] ?>').classList.add('hidden')"
                                                        class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800">
                                                    Cancelar
                                                </button>
                                                <button type="submit" 
                                                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                    Guardar Contraseña
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Footer de la tabla -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-medium"><?= count($estudiantes) ?></span> estudiantes
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium">1</span>
                            <button class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* Scrollbar personalizada para la tabla */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>

<script>
// Auto-ocultar alertas después de 5 segundos
setTimeout(() => {
    const alerts = document.querySelectorAll('.animate-fade-in');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Scroll suave al formulario cuando se está editando
<?php if ($editData): ?>
document.addEventListener('DOMContentLoaded', function() {
    const formCard = document.querySelector('.bg-white.rounded-2xl');
    if (formCard) {
        setTimeout(() => {
            formCard.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start',
                inline: 'nearest'
            });
        }, 100);
    }
});
<?php endif; ?>

// Mejorar la funcionalidad de los formularios de credenciales
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar otros formularios de credenciales al abrir uno nuevo
    const credButtons = document.querySelectorAll('[onclick*="cred-est-"]');
    credButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('onclick').match(/cred-est-(\d+)/)[0];
            document.querySelectorAll('[id^="cred-est-"]').forEach(form => {
                if (form.id !== targetId) {
                    form.classList.add('hidden');
                }
            });
        });
    });
    
    // Generar contraseña automática
    const generatePassword = function(length = 12) {
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        let password = "";
        for (let i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        return password;
    };
    
    // Agregar botón para generar contraseña en cada formulario
    document.querySelectorAll('[id^="cred-est-"] form').forEach(form => {
        const passwordInput = form.querySelector('input[name="password"]');
        const buttonContainer = form.querySelector('.flex.items-center.justify-end');
        
        if (passwordInput && buttonContainer) {
            const generateBtn = document.createElement('button');
            generateBtn.type = 'button';
            generateBtn.className = 'px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition-colors';
            generateBtn.innerHTML = '<i class="fas fa-random text-xs mr-1"></i> Generar';
            
            generateBtn.addEventListener('click', function() {
                passwordInput.value = generatePassword();
                passwordInput.type = 'text';
                setTimeout(() => {
                    passwordInput.type = 'password';
                }, 2000);
            });
            
            buttonContainer.insertBefore(generateBtn, buttonContainer.firstChild);
        }
    });
});
</script>