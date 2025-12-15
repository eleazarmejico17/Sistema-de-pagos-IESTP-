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
        e.estado
    FROM usuarios u
    LEFT JOIN empleado e ON e.id = u.estuempleado
    WHERE u.tipo IN (4,5)
    ORDER BY u.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$usuariosSistema = $stmt->fetchAll(PDO::FETCH_ASSOC);

$direccion = [];
$bienestar = [];
foreach ($usuariosSistema as $u) {
    if ((int)$u['tipo'] === 5) {
        $direccion[] = $u;
    } else {
        $bienestar[] = $u;
    }
}

$uploadsPath = '../../../uploads/empleados/';

function cargoEmpleadoLabel($c) {
    switch ($c) {
        case 'A': return 'Administrativo';
        case 'D': return 'Docente';
        case 'B': return 'Bienestar';
        default: return 'Sin definir';
    }
}
?>

<div class="space-y-6">
  <div>
    <h2 class="text-2xl font-bold">Usuarios del Sistema</h2>
    <p class="text-gray-600">Gestión de credenciales y acceso al sistema (Dirección y Bienestar).</p>
  </div>

  <?php if (!empty($alerts)): ?>
    <?php foreach ($alerts as $alert): ?>
      <div class="p-4 rounded-xl <?= $alert['type'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
        <?= htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="p-4 rounded-xl bg-red-50 text-red-700 border border-red-200">
      <ul class="list-disc pl-5 space-y-1 text-sm">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($empleadoAsignar): ?>
    <div class="bg-white rounded-2xl border border-gray-200 shadow p-6">
      <h3 class="text-lg font-semibold mb-4">Asignar credenciales a empleado</h3>
      <div class="text-sm text-gray-600 mb-4">
        <div><span class="font-semibold">Empleado:</span> <?= htmlspecialchars($empleadoAsignar['apnom_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div><span class="font-semibold">DNI:</span> <?= htmlspecialchars($empleadoAsignar['dni_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
      </div>

      <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="accion" value="asignar_acceso" />
        <input type="hidden" name="empleado_id" value="<?= (int)$empleadoAsignar['id'] ?>" />

        <div class="md:col-span-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Usuario (correo) *</label>
          <input name="usuario" required class="w-full px-4 py-3 rounded-xl border border-gray-200" value="<?= htmlspecialchars(strtolower((string)($empleadoAsignar['maili_emp'] ?: $empleadoAsignar['mailp_emp'])), ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de acceso *</label>
          <select name="tipo" required class="w-full px-4 py-3 rounded-xl border border-gray-200">
            <option value="">Seleccione...</option>
            <option value="4">Bienestar</option>
            <option value="5">Dirección</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña *</label>
          <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-gray-200" />
        </div>

        <div class="md:col-span-2 flex items-center justify-between gap-3">
          <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700">Cancelar</a>
          <button class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
            <i class="fas fa-key mr-2"></i>Asignar
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-gray-200 shadow p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Usuarios con Acceso - Dirección</h3>
        <span class="text-sm text-gray-500"><?= count($direccion) ?></span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-slate-800 text-white">
              <th class="p-2 text-left">Empleado</th>
              <th class="p-2 text-left">Usuario</th>
              <th class="p-2 text-left">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($direccion as $u): ?>
              <tr class="border-b">
                <td class="p-2">
                  <div class="flex items-center gap-3">
                    <?php if (!empty($u['foto_emp'])): ?>
                      <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($u['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" class="w-10 h-10 rounded-full object-cover border" />
                    <?php else: ?>
                      <div class="w-10 h-10 rounded-full bg-gray-100 border flex items-center justify-center"><i class="fas fa-user text-gray-400"></i></div>
                    <?php endif; ?>
                    <div>
                      <div class="font-semibold text-gray-800"><?= htmlspecialchars($u['apnom_emp'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="text-xs text-gray-500"><?= htmlspecialchars($u['dni_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars(cargoEmpleadoLabel($u['cargo_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                </td>
                <td class="p-2">
                  <div class="font-semibold"><?= htmlspecialchars($u['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars(($u['maili_emp'] ?? '') ?: ($u['mailp_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </td>
                <td class="p-2">
                  <div class="flex items-center gap-2">
                    <button type="button" class="px-3 py-2 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100" onclick="document.getElementById('edit-dir-<?= (int)$u['id'] ?>').classList.toggle('hidden')">
                      <i class="fas fa-pen mr-1"></i>Editar
                    </button>
                    <a class="px-3 py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100" href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&remover=<?= (int)$u['id'] ?>" onclick="return confirm('¿Remover acceso de este usuario?')">
                      <i class="fas fa-trash mr-1"></i>Remover
                    </a>
                  </div>

                  <div id="edit-dir-<?= (int)$u['id'] ?>" class="hidden mt-3 p-3 rounded-xl border border-gray-200 bg-gray-50">
                    <form method="POST" class="grid grid-cols-1 gap-2">
                      <input type="hidden" name="accion" value="editar_acceso" />
                      <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>" />
                      <input type="hidden" name="tipo" value="5" />
                      <label class="text-xs font-semibold text-gray-700">Nueva contraseña (opcional)</label>
                      <input type="password" name="password" class="w-full px-3 py-2 rounded-lg border border-gray-200" placeholder="Dejar en blanco para no cambiar" />
                      <div class="flex items-center justify-end">
                        <button class="mt-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold">
                          Guardar
                        </button>
                      </div>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($direccion)): ?>
              <tr><td class="p-3 text-gray-600" colspan="3">No hay usuarios con acceso a Dirección.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Usuarios con Acceso - Bienestar</h3>
        <span class="text-sm text-gray-500"><?= count($bienestar) ?></span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-indigo-600 text-white">
              <th class="p-2 text-left">Empleado</th>
              <th class="p-2 text-left">Usuario</th>
              <th class="p-2 text-left">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bienestar as $u): ?>
              <tr class="border-b">
                <td class="p-2">
                  <div class="flex items-center gap-3">
                    <?php if (!empty($u['foto_emp'])): ?>
                      <img src="<?= htmlspecialchars($uploadsPath . rawurlencode($u['foto_emp']), ENT_QUOTES, 'UTF-8') ?>" class="w-10 h-10 rounded-full object-cover border" />
                    <?php else: ?>
                      <div class="w-10 h-10 rounded-full bg-gray-100 border flex items-center justify-center"><i class="fas fa-user text-gray-400"></i></div>
                    <?php endif; ?>
                    <div>
                      <div class="font-semibold text-gray-800"><?= htmlspecialchars($u['apnom_emp'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="text-xs text-gray-500"><?= htmlspecialchars($u['dni_emp'] ?? '', ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars(cargoEmpleadoLabel($u['cargo_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                </td>
                <td class="p-2">
                  <div class="font-semibold"><?= htmlspecialchars($u['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars(($u['maili_emp'] ?? '') ?: ($u['mailp_emp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </td>
                <td class="p-2">
                  <div class="flex items-center gap-2">
                    <button type="button" class="px-3 py-2 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100" onclick="document.getElementById('edit-bie-<?= (int)$u['id'] ?>').classList.toggle('hidden')">
                      <i class="fas fa-pen mr-1"></i>Editar
                    </button>
                    <a class="px-3 py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100" href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>&remover=<?= (int)$u['id'] ?>" onclick="return confirm('¿Remover acceso de este usuario?')">
                      <i class="fas fa-trash mr-1"></i>Remover
                    </a>
                  </div>

                  <div id="edit-bie-<?= (int)$u['id'] ?>" class="hidden mt-3 p-3 rounded-xl border border-gray-200 bg-gray-50">
                    <form method="POST" class="grid grid-cols-1 gap-2">
                      <input type="hidden" name="accion" value="editar_acceso" />
                      <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>" />
                      <input type="hidden" name="tipo" value="4" />
                      <label class="text-xs font-semibold text-gray-700">Nueva contraseña (opcional)</label>
                      <input type="password" name="password" class="w-full px-3 py-2 rounded-lg border border-gray-200" placeholder="Dejar en blanco para no cambiar" />
                      <div class="flex items-center justify-end">
                        <button class="mt-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold">
                          Guardar
                        </button>
                      </div>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($bienestar)): ?>
              <tr><td class="p-3 text-gray-600" colspan="3">No hay usuarios con acceso a Bienestar.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
