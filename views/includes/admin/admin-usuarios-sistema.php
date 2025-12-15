<?php
require_once __DIR__ . '/../../../config/conexion.php';

$pdo = Database::getInstance()->getConnection();

$moduleParam = isset($_GET['modulo']) ? 'modulo' : 'pagina';
$moduleValue = $_GET[$moduleParam] ?? 'admin-usuarios-sistema';
$redirectUrl = sprintf('%s?%s=%s', basename($_SERVER['PHP_SELF']), $moduleParam, 'admin-usuarios-sistema');

$status = $_GET['status'] ?? null;
$alerts = [];
$errors = [];

if ($status === 'access_created') {
    $alerts[] = ['type' => 'success', 'text' => 'Credenciales asignadas correctamente.'];
} elseif ($status === 'access_updated') {
    $alerts[] = ['type' => 'success', 'text' => 'Credenciales actualizadas correctamente.'];
} elseif ($status === 'access_removed') {
    $alerts[] = ['type' => 'success', 'text' => 'Acceso removido correctamente.'];
} elseif ($status === 'error') {
    $errors[] = 'Ocurrió un problema al procesar la solicitud.';
}

if (!empty($_SESSION['usuarios_sistema_errors'])) {
    foreach ((array) $_SESSION['usuarios_sistema_errors'] as $msg) {
        $errors[] = (string) $msg;
    }
    unset($_SESSION['usuarios_sistema_errors']);
}

$assignEmpleadoId = isset($_GET['asignar']) ? (int)$_GET['asignar'] : 0;
$empleadoAsignar = null;
if ($assignEmpleadoId > 0) {
    $stmt = $pdo->prepare('SELECT id, dni_emp, apnom_emp, mailp_emp, maili_emp, cargo_emp FROM empleado WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $assignEmpleadoId]);
    $empleadoAsignar = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$sql = "
    SELECT
        u.id,
        u.usuario,
        u.tipo,
        u.estuempleado,
        e.dni_emp,
        e.apnom_emp,
        e.cel_emp,
        e.mailp_emp,
        e.maili_emp,
        e.cargo_emp,
        e.foto_emp,
        e.estado,
        s.dni_est,
        CONCAT(TRIM(COALESCE(s.ap_est, '')), ' ', TRIM(COALESCE(s.am_est, '')), ' ', TRIM(COALESCE(s.nom_est, ''))) AS apnom_est,
        s.cel_est,
        s.mailp_est,
        s.maili_est,
        s.foto_est,
        s.estado AS estado_est
    FROM usuarios u
    LEFT JOIN empleado e ON e.id = u.estuempleado
    LEFT JOIN estudiante s ON s.id = u.estuempleado
    WHERE u.tipo IN (2,4,5)
    ORDER BY u.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$usuariosSistema = $stmt->fetchAll(PDO::FETCH_ASSOC);

$direccion = [];
$bienestar = [];
$estudiantes = [];
foreach ($usuariosSistema as $u) {
    if ((int)$u['tipo'] === 5) {
        $direccion[] = $u;
    } elseif ((int)$u['tipo'] === 4) {
        $bienestar[] = $u;
    } else {
        $estudiantes[] = $u;
    }
}

$uploadsPath = '../../../uploads/empleados/';
$uploadsEstudiantesPath = '../../../uploads/';

function cargoEmpleadoLabel($c) {
    switch ($c) {
        case 'A': return 'Administrativo';
        case 'D': return 'Docente';
        case 'B': return 'Bienestar';
        default: return 'Sin definir';
    }
}
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- HEADER MEJORADO -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestión de Usuarios del Sistema</h1>
            <p class="text-gray-600 mt-2">Administra las credenciales de acceso de Dirección, Bienestar y Estudiantes</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <div class="bg-blue-50 px-4 py-2 rounded-lg">
                <div class="text-sm text-blue-700 font-medium">Total usuarios</div>
                <div class="text-2xl font-bold text-blue-900"><?= count($usuariosSistema) ?></div>
            </div>
        </div>
    </div>

    <!-- ALERTAS MEJORADAS -->
    <?php if (!empty($alerts) || !empty($errors)): ?>
    <div class="space-y-4">
        <?php foreach ($alerts as $alert): ?>
            <div class="rounded-xl border-l-4 <?= $alert['type'] === 'success' ? 'border-emerald-500 bg-emerald-50/90' : 'border-red-500 bg-red-50/90' ?> p-4 shadow-sm animate-fade-in">
                <div class="flex items-center gap-3">
                    <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle text-emerald-600' : 'fa-exclamation-circle text-red-600' ?> text-lg"></i>
                    <span class="font-medium text-gray-800"><?= htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($errors)): ?>
            <div class="rounded-xl border-l-4 border-red-500 bg-red-50/90 p-4 shadow-sm animate-fade-in">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                    <span class="font-medium text-gray-800">Se encontraron errores en la operación</span>
                </div>
                <ul class="mt-2 pl-7 space-y-1 text-gray-700 text-sm">
                    <?php foreach ($errors as $e): ?>
                        <li class="list-disc"><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- FORMULARIO DE ASIGNACIÓN MEJORADO -->
    <?php if ($empleadoAsignar): ?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <!-- Header del formulario -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                            <i class="fas fa-user-plus text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white">Asignar Credenciales</h2>
                            <p class="text-blue-100 text-sm">Configura el acceso al sistema para el empleado</p>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-all backdrop-blur-sm">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                </div>
            </div>

            <!-- Información del empleado -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-user-tie text-blue-600 text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($empleadoAsignar['apnom_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">DNI:</span>
                                <span class="font-medium"><?= htmlspecialchars($empleadoAsignar['dni_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">Correo institucional:</span>
                                <span class="font-medium"><?= htmlspecialchars($empleadoAsignar['maili_emp'] ?? 'No disponible', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="accion" value="asignar_acceso" />
                    <input type="hidden" name="empleado_id" value="<?= (int)$empleadoAsignar['id'] ?>" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Usuario -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-envelope text-blue-600 text-sm"></i>
                                    Usuario (correo) <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <div class="relative">
                                <input 
                                    name="usuario" 
                                    required 
                                    class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                    value="<?= htmlspecialchars(strtolower((string)($empleadoAsignar['maili_emp'] ?: $empleadoAsignar['mailp_emp'])), ENT_QUOTES, 'UTF-8') ?>"
                                    placeholder="usuario@instituto.edu.pe"
                                />
                                <i class="fas fa-at absolute left-3 top-3.5 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Tipo de acceso -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-user-shield text-blue-600 text-sm"></i>
                                    Tipo de acceso <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <select 
                                name="tipo" 
                                required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700"
                            >
                                <option value="">Seleccione un rol...</option>
                                <option value="4">👨‍⚕️ Bienestar</option>
                                <option value="5">👨‍💼 Dirección</option>
                            </select>
                        </div>

                        <!-- Contraseña -->
                        <div class="md:col-span-2 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-key text-blue-600 text-sm"></i>
                                    Contraseña <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    name="password" 
                                    required 
                                    class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                    placeholder="Ingrese una contraseña segura"
                                />
                                <i class="fas fa-lock absolute left-3 top-3.5 text-gray-400"></i>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres, incluyendo mayúsculas, minúsculas y números</p>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>" 
                           class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-all">
                            <i class="fas fa-arrow-left"></i>
                            <span>Volver</span>
                        </a>
                        <button class="inline-flex items-center gap-3 px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all">
                            <i class="fas fa-key"></i>
                            <span>Asignar Credenciales</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ESTADÍSTICAS RÁPIDAS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Dirección -->
        <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl border border-blue-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-user-tie text-blue-600 text-2xl"></i>
                </div>
                <span class="text-3xl font-bold text-blue-900"><?= count($direccion) ?></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Dirección</h3>
            <p class="text-sm text-gray-600">Usuarios con acceso administrativo total</p>
        </div>

        <!-- Bienestar -->
        <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl border border-indigo-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <i class="fas fa-heartbeat text-indigo-600 text-2xl"></i>
                </div>
                <span class="text-3xl font-bold text-indigo-900"><?= count($bienestar) ?></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Bienestar</h3>
            <p class="text-sm text-gray-600">Personal de apoyo estudiantil</p>
        </div>

        <!-- Estudiantes -->
        <div class="bg-gradient-to-br from-emerald-50 to-white rounded-xl border border-emerald-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-emerald-100 p-3 rounded-lg">
                    <i class="fas fa-user-graduate text-emerald-600 text-2xl"></i>
                </div>
                <span class="text-3xl font-bold text-emerald-900"><?= count($estudiantes) ?></span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Estudiantes</h3>
            <p class="text-sm text-gray-600">Alumnos con acceso al sistema</p>
        </div>
    </div>

    <!-- SECCIÓN DE USUARIOS -->
    <div class="space-y-8">
        <!-- DIRECCIÓN -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user-tie text-white text-xl"></i>
                        <h2 class="text-xl font-bold text-white">Usuarios de Dirección</h2>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-white text-sm font-medium">
                        <?= count($direccion) ?> usuario<?= count($direccion) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <?php if (empty($direccion)): ?>
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-50 rounded-full mb-4">
                        <i class="fas fa-user-tie text-3xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay usuarios de dirección</h3>
                    <p class="text-gray-500 max-w-md mx-auto">No se han asignado credenciales de acceso para el equipo directivo.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Empleado</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Credenciales</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($direccion as $u): ?>
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <!-- Empleado -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($u['foto_emp'])): ?>
                                                <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($u['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" 
                                                     class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-lg">
                                                    <?= substr($u['apnom_emp'] ?? '?', 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($u['apnom_emp'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($u['dni_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?> • 
                                                <?= htmlspecialchars(cargoEmpleadoLabel($u['cargo_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Credenciales -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="font-medium text-gray-900">
                                            <i class="fas fa-user-circle text-blue-500 text-sm mr-2"></i>
                                            <?= htmlspecialchars($u['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-envelope text-gray-400 text-sm mr-2"></i>
                                            <?= htmlspecialchars(($u['maili_emp'] ?? '') ?: ($u['mailp_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Acciones -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <!-- Editar -->
                                        <button type="button" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-medium transition-colors"
                                                onclick="toggleEditForm('dir-<?= (int)$u['id'] ?>')">
                                            <i class="fas fa-pencil-alt text-sm"></i>
                                            <span>Editar</span>
                                        </button>
                                        
                                        <!-- Remover -->
                                        <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&remover=<?= (int)$u['id'] ?>"
                                           onclick="return confirm('¿Está seguro de remover el acceso de este usuario?')"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-medium transition-colors">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                            <span>Remover</span>
                                        </a>
                                    </div>
                                    
                                    <!-- Formulario de edición -->
                                    <div id="edit-form-dir-<?= (int)$u['id'] ?>" class="hidden mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <form method="POST" class="space-y-3">
                                            <input type="hidden" name="accion" value="editar_acceso" />
                                            <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>" />
                                            <input type="hidden" name="tipo" value="5" />
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Nueva contraseña <span class="text-gray-500 text-xs">(opcional)</span>
                                                </label>
                                                <div class="relative">
                                                    <input type="password" 
                                                           name="password" 
                                                           class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                           placeholder="Dejar en blanco para no cambiar">
                                                    <i class="fas fa-lock absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">La contraseña actual se mantendrá si este campo está vacío</p>
                                            </div>
                                            
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button"
                                                        onclick="toggleEditForm('dir-<?= (int)$u['id'] ?>')"
                                                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                                    Cancelar
                                                </button>
                                                <button type="submit" 
                                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                    Actualizar Contraseña
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
            <?php endif; ?>
        </div>

        <!-- BIENESTAR -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-indigo-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-heartbeat text-white text-xl"></i>
                        <h2 class="text-xl font-bold text-white">Usuarios de Bienestar</h2>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-white text-sm font-medium">
                        <?= count($bienestar) ?> usuario<?= count($bienestar) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <?php if (empty($bienestar)): ?>
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-50 rounded-full mb-4">
                        <i class="fas fa-heartbeat text-3xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay usuarios de bienestar</h3>
                    <p class="text-gray-500 max-w-md mx-auto">No se han asignado credenciales para el personal de bienestar estudiantil.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Empleado</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Credenciales</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($bienestar as $u): ?>
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                <!-- Empleado -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($u['foto_emp'])): ?>
                                                <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($u['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" 
                                                     class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-lg">
                                                    <?= substr($u['apnom_emp'] ?? '?', 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($u['apnom_emp'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($u['dni_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?> • 
                                                <?= htmlspecialchars(cargoEmpleadoLabel($u['cargo_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Credenciales -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="font-medium text-gray-900">
                                            <i class="fas fa-user-circle text-indigo-500 text-sm mr-2"></i>
                                            <?= htmlspecialchars($u['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-envelope text-gray-400 text-sm mr-2"></i>
                                            <?= htmlspecialchars(($u['maili_emp'] ?? '') ?: ($u['mailp_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Acciones -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <!-- Editar -->
                                        <button type="button" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-medium transition-colors"
                                                onclick="toggleEditForm('bie-<?= (int)$u['id'] ?>')">
                                            <i class="fas fa-pencil-alt text-sm"></i>
                                            <span>Editar</span>
                                        </button>
                                        
                                        <!-- Remover -->
                                        <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&remover=<?= (int)$u['id'] ?>"
                                           onclick="return confirm('¿Está seguro de remover el acceso de este usuario?')"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-medium transition-colors">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                            <span>Remover</span>
                                        </a>
                                    </div>
                                    
                                    <!-- Formulario de edición -->
                                    <div id="edit-form-bie-<?= (int)$u['id'] ?>" class="hidden mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <form method="POST" class="space-y-3">
                                            <input type="hidden" name="accion" value="editar_acceso" />
                                            <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>" />
                                            <input type="hidden" name="tipo" value="4" />
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Nueva contraseña <span class="text-gray-500 text-xs">(opcional)</span>
                                                </label>
                                                <div class="relative">
                                                    <input type="password" 
                                                           name="password" 
                                                           class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                           placeholder="Dejar en blanco para no cambiar">
                                                    <i class="fas fa-lock absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button"
                                                        onclick="toggleEditForm('bie-<?= (int)$u['id'] ?>')"
                                                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                                    Cancelar
                                                </button>
                                                <button type="submit" 
                                                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                    Actualizar Contraseña
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
            <?php endif; ?>
        </div>

        <!-- ESTUDIANTES -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-emerald-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user-graduate text-white text-xl"></i>
                        <h2 class="text-xl font-bold text-white">Estudiantes con Acceso</h2>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-white text-sm font-medium">
                        <?= count($estudiantes) ?> estudiante<?= count($estudiantes) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <?php if (empty($estudiantes)): ?>
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-emerald-50 rounded-full mb-4">
                        <i class="fas fa-user-graduate text-3xl text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay estudiantes con acceso</h3>
                    <p class="text-gray-500 max-w-md mx-auto">No se han asignado credenciales de acceso a estudiantes.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Estudiante</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Credenciales</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($estudiantes as $u): ?>
                            <tr class="hover:bg-emerald-50/30 transition-colors">
                                <!-- Estudiante -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($u['foto_est'])): ?>
                                                <img src="<?= htmlspecialchars($uploadsEstudiantesPath . rawurlencode($u['foto_est']), ENT_QUOTES, 'UTF-8') ?>" 
                                                     class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-semibold text-lg">
                                                    <?= substr($u['apnom_est'] ?? '?', 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($u['apnom_est'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($u['dni_est'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Credenciales -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="font-medium text-gray-900">
                                            <i class="fas fa-user-circle text-emerald-500 text-sm mr-2"></i>
                                            <?= htmlspecialchars($u['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-envelope text-gray-400 text-sm mr-2"></i>
                                            <?= htmlspecialchars(($u['maili_est'] ?? '') ?: ($u['mailp_est'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Acciones -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <!-- Editar -->
                                        <button type="button" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg font-medium transition-colors"
                                                onclick="toggleEditForm('est-<?= (int)$u['id'] ?>')">
                                            <i class="fas fa-pencil-alt text-sm"></i>
                                            <span>Editar</span>
                                        </button>
                                        
                                        <!-- Remover -->
                                        <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&remover=<?= (int)$u['id'] ?>"
                                           onclick="return confirm('¿Está seguro de remover el acceso de este estudiante?')"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-medium transition-colors">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                            <span>Remover</span>
                                        </a>
                                    </div>
                                    
                                    <!-- Formulario de edición -->
                                    <div id="edit-form-est-<?= (int)$u['id'] ?>" class="hidden mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <form method="POST" class="space-y-3">
                                            <input type="hidden" name="accion" value="editar_acceso" />
                                            <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>" />
                                            <input type="hidden" name="tipo" value="2" />
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Nueva contraseña <span class="text-gray-500 text-xs">(opcional)</span>
                                                </label>
                                                <div class="relative">
                                                    <input type="password" 
                                                           name="password" 
                                                           class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                                           placeholder="Dejar en blanco para no cambiar">
                                                    <i class="fas fa-lock absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button"
                                                        onclick="toggleEditForm('est-<?= (int)$u['id'] ?>')"
                                                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                                    Cancelar
                                                </button>
                                                <button type="submit" 
                                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                    Actualizar Contraseña
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
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* Scrollbar personalizada */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
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

/* Hover effects */
.hover-lift {
    transition: transform 0.2s ease-out;
}

.hover-lift:hover {
    transform: translateY(-2px);
}
</style>

<script>
// Auto-ocultar alertas
setTimeout(() => {
    const alerts = document.querySelectorAll('.animate-fade-in');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Función para mostrar/ocultar formularios de edición
function toggleEditForm(id) {
    const form = document.getElementById(`edit-form-${id}`);
    if (form) {
        form.classList.toggle('hidden');
        
        // Cerrar otros formularios abiertos
        document.querySelectorAll('[id^="edit-form-"]').forEach(otherForm => {
            if (otherForm !== form && !otherForm.classList.contains('hidden')) {
                otherForm.classList.add('hidden');
            }
        });
        
        // Scroll suave al formulario
        if (!form.classList.contains('hidden')) {
            setTimeout(() => {
                form.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest',
                    inline: 'nearest'
                });
            }, 100);
        }
    }
}

// Generar contraseña segura
function generateSecurePassword(length = 12) {
    const charset = {
        lowercase: 'abcdefghijklmnopqrstuvwxyz',
        uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        numbers: '0123456789',
        symbols: '!@#$%^&*'
    };
    
    let password = '';
    
    // Asegurar al menos un carácter de cada tipo
    password += charset.lowercase[Math.floor(Math.random() * charset.lowercase.length)];
    password += charset.uppercase[Math.floor(Math.random() * charset.uppercase.length)];
    password += charset.numbers[Math.floor(Math.random() * charset.numbers.length)];
    password += charset.symbols[Math.floor(Math.random() * charset.symbols.length)];
    
    // Completar con caracteres aleatorios
    const allChars = charset.lowercase + charset.uppercase + charset.numbers + charset.symbols;
    for (let i = password.length; i < length; i++) {
        password += allChars[Math.floor(Math.random() * allChars.length)];
    }
    
    // Mezclar la contraseña
    return password.split('').sort(() => Math.random() - 0.5).join('');
}

// Agregar botón para generar contraseña en el formulario de asignación
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector('input[name="password"]');
    if (passwordInput) {
        const container = passwordInput.parentElement;
        const generateBtn = document.createElement('button');
        generateBtn.type = 'button';
        generateBtn.className = 'absolute right-3 top-3.5 text-gray-400 hover:text-blue-600 transition-colors';
        generateBtn.innerHTML = '<i class="fas fa-random text-sm"></i>';
        generateBtn.title = 'Generar contraseña segura';
        
        generateBtn.addEventListener('click', function() {
            passwordInput.value = generateSecurePassword();
            passwordInput.type = 'text';
            
            // Mostrar temporalmente la contraseña
            setTimeout(() => {
                passwordInput.type = 'password';
            }, 3000);
            
            // Copiar al portapapeles
            passwordInput.select();
            document.execCommand('copy');
            
            // Mostrar notificación
            const notification = document.createElement('div');
            notification.className = 'fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg animate-fade-in z-50';
            notification.textContent = 'Contraseña generada y copiada al portapapeles';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        });
        
        container.classList.add('relative');
        container.appendChild(generateBtn);
    }
});

// Validación de contraseña en tiempo real
document.addEventListener('input', function(e) {
    if (e.target.name === 'password') {
        const password = e.target.value;
        const feedback = e.target.parentElement.querySelector('.password-feedback') || 
                         document.createElement('div');
        
        if (!feedback.classList.contains('password-feedback')) {
            feedback.className = 'password-feedback text-xs mt-1';
            e.target.parentElement.appendChild(feedback);
        }
        
        if (password.length === 0) {
            feedback.textContent = '';
            return;
        }
        
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasMinLength = password.length >= 8;
        
        const checks = [
            { condition: hasMinLength, text: '✓ Mínimo 8 caracteres' },
            { condition: hasLower, text: '✓ Minúsculas' },
            { condition: hasUpper, text: '✓ Mayúsculas' },
            { condition: hasNumber, text: '✓ Números' }
        ];
        
        let html = '';
        checks.forEach(check => {
            html += `<div class="${check.condition ? 'text-green-600' : 'text-gray-400'}">${check.text}</div>`;
        });
        
        feedback.innerHTML = html;
    }
});
</script>