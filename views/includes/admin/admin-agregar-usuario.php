<?php
require_once __DIR__ . '/../../../config/conexion.php';

// Nota: El procesamiento del POST se hace en dashboard-admin.php antes de cualquier output HTML
$status = $_GET['status'] ?? null;
$mensaje = null;
$tipoMensaje = null;
$editData = null;

// Si hay un parámetro edit, cargar datos del usuario
if (isset($_GET['edit'])) {
    try {
        $pdo = Database::getInstance()->getConnection();
        $editId = filter_input(INPUT_GET, 'edit', FILTER_SANITIZE_NUMBER_INT);
        if ($editId) {
            $stmt = $pdo->prepare("SELECT id, usuario, tipo, estuempleado, token FROM usuarios WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $editId]);
            $editData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$editData) {
                $mensaje = "Usuario no encontrado";
                $tipoMensaje = "error";
            }
        }
    } catch (Exception $e) {
        error_log("Error al obtener usuario para editar: " . $e->getMessage());
        $mensaje = "Error al cargar datos del usuario";
        $tipoMensaje = "error";
    }
}

// Recuperar mensajes de la sesión
if ($status === 'usuario_created') {
    $mensaje = "Usuario registrado correctamente";
    $tipoMensaje = "success";
} elseif ($status === 'usuario_actualizado') {
    $mensaje = "Usuario actualizado correctamente";
    $tipoMensaje = "success";
} elseif ($status === 'error') {
    $errors = $_SESSION['admin_errors'] ?? [];
    $mensaje = !empty($errors) ? implode(', ', $errors) : 'Ocurrió un problema al procesar la solicitud.';
    $tipoMensaje = "error";
    unset($_SESSION['admin_errors']);
}

// Recuperar datos previos
$previousData = $_SESSION['admin_previous_data'] ?? [];
unset($_SESSION['admin_previous_data']);

// Función helper para valores
$oldValue = function (string $key) use ($previousData, $editData) {
    if ($editData && isset($editData[$key])) {
        return htmlspecialchars($editData[$key], ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars($previousData[$key] ?? '', ENT_QUOTES, 'UTF-8');
};

$isEdit = !empty($editData);
?>

<div class="max-w-2xl mx-auto">
    <!-- ALERTA -->
    <?php if ($mensaje): ?>
        <div class="mb-6 p-4 rounded-lg border-l-4 <?= $tipoMensaje === 'success' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-red-50 border-red-500 text-red-700' ?>">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <i class="fas <?= $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-xl"></i>
                    <p class="font-semibold"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php if ($tipoMensaje === 'success'): ?>
                    <a href="../../views/dashboard-bienestar.php?pagina=reportes-bienestar-estudiantil" 
                       class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold transition-all duration-300 flex items-center gap-2 text-sm">
                        <i class="fas fa-eye"></i>
                        <span>Ver Usuarios</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-2xl shadow-xl border border-gray-200">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-3 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg">
                <i class="fas fa-user-plus text-white text-2xl"></i>
            </div>
                <div>
                    <h2 class="text-3xl font-bold text-gray-800"><?= $isEdit ? 'Editar Usuario' : 'Registrar Usuario' ?></h2>
                    <p class="text-gray-500 text-sm mt-1"><?= $isEdit ? 'Modifica la información del usuario' : 'Complete la información para agregar un nuevo usuario al sistema' ?></p>
                </div>
                <?php if ($isEdit): ?>
                    <a href="dashboard-admin.php?pagina=admin-agregar-usuario" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-all flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                <?php endif; ?>
            </div>

        <form method="POST" class="space-y-6">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($editData['id'], ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <!-- Usuario -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 font-semibold text-gray-700">
                    <i class="fas fa-user text-indigo-600"></i>
                    <span>Nombre de Usuario <span class="text-red-500">*</span></span>
                </label>
                <input 
                    type="text" 
                    name="usuario"
                    value="<?= $oldValue('usuario') ?>"
                    required
                    class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-gray-700 placeholder-gray-400"
                    placeholder="Ingrese el nombre de usuario"
                >
            </div>

            <!-- Contraseña -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 font-semibold text-gray-700">
                    <i class="fas fa-lock text-indigo-600"></i>
                    <span>Contraseña <?php if (!$isEdit): ?><span class="text-red-500">*</span><?php else: ?><span class="text-gray-500 text-xs">(dejar vacío para mantener la actual)</span><?php endif; ?></span>
                </label>
                <input 
                    type="password" 
                    name="password"
                    <?php if (!$isEdit): ?>required<?php endif; ?>
                    class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-gray-700 placeholder-gray-400"
                    placeholder="<?= $isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña segura' ?>"
                >
            </div>

            <!-- Tipo de Usuario -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 font-semibold text-gray-700">
                    <i class="fas fa-user-tag text-indigo-600"></i>
                    <span>Tipo de Usuario <span class="text-red-500">*</span></span>
                </label>
                <select 
                    name="tipo"
                    required
                    class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-gray-700"
                >
                    <option value="">Seleccione...</option>
                    <option value="1" <?= ($oldValue('tipo') == '1') ? 'selected' : '' ?>>Empleado</option>
                    <option value="2" <?= ($oldValue('tipo') == '2') ? 'selected' : '' ?>>Estudiante</option>
                    <option value="3" <?= ($oldValue('tipo') == '3') ? 'selected' : '' ?>>Empresa</option>
                </select>
            </div>

            <!-- ID Estudiante/Empleado -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 font-semibold text-gray-700">
                    <i class="fas fa-id-card text-indigo-600"></i>
                    <span>ID Estudiante/Empleado</span>
                </label>
                <input 
                    type="number" 
                    name="estuempleado"
                    value="<?= $oldValue('estuempleado') ?>"
                    min="1"
                    class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-gray-700 placeholder-gray-400"
                    placeholder="ID asociado (opcional)"
                >
            </div>

            <!-- Token -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 font-semibold text-gray-700">
                    <i class="fas fa-key text-indigo-600"></i>
                    <span>Token (Opcional)</span>
                </label>
                <textarea 
                    name="token" 
                    rows="3"
                    class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-gray-700 placeholder-gray-400 resize-none"
                    placeholder="Token asignado al usuario"
                ><?= $oldValue('token') ?></textarea>
            </div>

            <!-- Botón -->
            <button 
                type="submit"
                class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold py-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-3 transform hover:scale-[1.02]"
            >
                <i class="fas <?= $isEdit ? 'fa-save' : 'fa-user-plus' ?> text-xl"></i>
                <span class="text-lg"><?= $isEdit ? 'Actualizar Usuario' : 'Guardar Usuario' ?></span>
            </button>
        </form>
    </div>
</div>

<script>
// Auto-ocultar mensaje de éxito después de 5 segundos
<?php if ($tipoMensaje === 'success'): ?>
setTimeout(() => {
    const alert = document.querySelector('.bg-emerald-50');
    if (alert) {
        alert.style.transition = 'opacity 0.5s ease-out';
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.remove();
            <?php if (!$isEdit): ?>
            // Limpiar formulario solo si no es edición
            document.querySelector('form').reset();
            <?php endif; ?>
        }, 500);
    }
}, 5000);
<?php endif; ?>
</script>
