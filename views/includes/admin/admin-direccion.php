<?php
require_once __DIR__ . '/../../../controller/admin-direccionController.php';
require_once 'C:/xampp/htdocs/Sistema-de-pagos-IESTP/config/conexion.php';

$ctrl = new DireccionController();
$usuarios = $ctrl->listar();

// Crear
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'])) {
    $ctrl->crear($_POST);
    header("Location: panel-admin.php?modulo=admin-direccion");
    exit;
}

// Eliminar
if (isset($_GET['delete'])) {
    $ctrl->eliminar($_GET['delete']);
    header("Location: panel-admin.php?modulo=admin-direccion");
    exit;
}
?>

<section class="bg-white rounded-xl shadow-md p-6 mt-8">
    <h2 class="text-lg font-semibold mb-4 text-gray-800">👥 Dirección</h2>

    <form method="POST" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <input name="usuario" placeholder="Usuario" required class="input border rounded px-3 py-2">
        <input name="password" type="password" placeholder="Contraseña" required class="input border rounded px-3 py-2">
        <input name="correo" type="email" placeholder="Correo" required class="input border rounded px-3 py-2">
        <select name="rol" class="input border rounded px-3 py-2">
            <option>Administrador</option>
            <option>Bienestar</option>
            <option>Dirección</option>
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Agregar</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                    <th class="border px-4 py-3">Usuario</th>
                    <th class="border px-4 py-3">Correo</th>
                    <th class="border px-4 py-3">Rol</th>
                    <th class="border px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border px-4 py-2"><?= htmlspecialchars($u['usuario']) ?></td>
                    <td class="border px-4 py-2"><?= htmlspecialchars($u['correo']) ?></td>
                    <td class="border px-4 py-2"><?= htmlspecialchars($u['rol']) ?></td>
                    <td class="border px-4 py-2 text-center">
                        <a href="?modulo=admin-direccion&delete=<?= $u['id'] ?>" class="text-red-600 hover:underline">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>