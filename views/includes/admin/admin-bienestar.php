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

<h2 class="text-2xl font-bold mb-6">Gestión de Personal Bienestar</h2>

<?php if (!empty($alerts)): ?>
    <?php foreach ($alerts as $alert): ?>
        <div class="mt-4 p-4 rounded-xl <?= $alert['type'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
            <?= htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="mt-4 p-4 rounded-xl bg-red-50 text-red-700 border border-red-200">
        <ul class="list-disc pl-5 space-y-1 text-sm text-left">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>



<!-- TABLA -->
<table class="mt-6 w-full bg-white shadow rounded-xl">
    <tr class="bg-indigo-600 text-white">
        <th class="p-2">DNI</th>
        <th class="p-2">Nombre Completo</th>
        <th class="p-2">Celular</th>
        <th class="p-2">Correo</th>
        <th class="p-2">Cargo</th>
        <th class="p-2">Foto</th>
        <th class="p-2">Estado</th>
        <th class="p-2">Acciones</th>
    </tr>

    <?php foreach ($empleados as $e): ?>
    <tr class="border-b">
        <td class="p-2"><?= htmlspecialchars($e['dni_emp'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="p-2"><?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="p-2"><?= htmlspecialchars($e['cel_emp'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="p-2"><?= htmlspecialchars($e['mailp_emp'], ENT_QUOTES, 'UTF-8') ?></td>

        <td class="p-2">
            <?php
                switch ($e['cargo_emp']) {
                    case 'A': echo "Administrativo"; break;
                    case 'D': echo "Docente"; break;
                    case 'B': echo "Bienestar"; break;
                    default: echo "Sin definir";
                }
            ?>
        </td>

        <td class="p-2">
            <?php if (!empty($e['foto_emp'])): ?>
                <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($e['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" 
                     alt="Foto de <?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>" 
                     class="w-12 h-12 rounded-full object-cover border shadow-sm"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCI+PHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiBmaWxsPSIjZTBlN2ViIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5Y2EzYWIiIGZvbnQtc2l6ZT0iMjAiPj88L3RleHQ+PC9zdmc+';">
            <?php else: ?>
                <div class="w-12 h-12 rounded-full bg-gray-200 border flex items-center justify-center">
                    <i class="fas fa-user text-gray-400 text-lg"></i>
                </div>
            <?php endif; ?>
        </td>

        <td class="p-2">
            <?= (int) $e['estado'] === 1 ? 'Activo' : 'Inactivo' ?>
        </td>

        <td class="p-2">
            <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&delete=<?= (int) $e['id'] ?>" class="text-red-600 hover:underline">Eliminar</a>
        </td>
    </tr>
    <?php endforeach ?>
</table>
