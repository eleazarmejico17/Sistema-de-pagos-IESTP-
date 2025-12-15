<?php
require_once __DIR__ . '/../../../config/conexion.php';

$pdo = Conexion::getInstance()->getConnection();

function cajaMsg(string $type): void {
    $messages = [
        'creado' => ['Tipo de pago creado correctamente', 'bg-emerald-50 border-emerald-200 text-emerald-800', 'fa-check-circle'],
        'actualizado' => ['Tipo de pago actualizado correctamente', 'bg-blue-50 border-blue-200 text-blue-800', 'fa-check-circle'],
        'eliminado' => ['Tipo de pago eliminado correctamente', 'bg-red-50 border-red-200 text-red-800', 'fa-trash-alt'],
        'pago_creado' => ['Pago creado correctamente', 'bg-emerald-50 border-emerald-200 text-emerald-800', 'fa-check-circle'],
        'pago_actualizado' => ['Pago actualizado correctamente', 'bg-blue-50 border-blue-200 text-blue-800', 'fa-check-circle'],
        'pago_eliminado' => ['Pago eliminado correctamente', 'bg-red-50 border-red-200 text-red-800', 'fa-trash-alt'],
        'error' => ['Ha ocurrido un error', 'bg-red-50 border-red-200 text-red-800', 'fa-exclamation-circle'],
    ];

    if (!isset($messages[$type])) {
        return;
    }

    [$text, $color, $icon] = $messages[$type];

    if ($type === 'error' && isset($_GET['detalle'])) {
        $detalle = htmlspecialchars(urldecode((string)$_GET['detalle']), ENT_QUOTES, 'UTF-8');
        $text .= ': ' . $detalle;
    }

    echo "<div class='p-4 mb-6 border-l-4 rounded-lg shadow $color flex items-center gap-3'>
            <i class='fas $icon text-xl'></i>
            <p class='font-semibold'>$text</p>
          </div>";
}

// Detectar columnas en tipo_pago
try {
    $stmt = $pdo->query('SHOW COLUMNS FROM tipo_pago');
    $tipoPagoColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $tipoPagoColumns = [];
}
$hasPrecio = in_array('precio', $tipoPagoColumns, true);

// Datos para edición
$editTipoPago = null;
if (isset($_GET['editar_tipo_pago'])) {
    $id = (int)$_GET['editar_tipo_pago'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM tipo_pago WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $editTipoPago = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$editPago = null;
if (isset($_GET['editar_pago'])) {
    $id = (int)$_GET['editar_pago'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM pagos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $editPago = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

// Listar tipos de pago
$tiposPago = [];
try {
    $stmt = $pdo->query('SELECT * FROM tipo_pago ORDER BY id ASC');
    $tiposPago = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $tiposPago = [];
}

// Listar pagos
$pagos = [];
try {
    $stmtColumns = $pdo->query('SHOW COLUMNS FROM pagos');
    $pagoColumns = $stmtColumns->fetchAll(PDO::FETCH_COLUMN);
    $columnaEstudiante = in_array('estudiante', $pagoColumns, true) ? 'estudiante' : (in_array('estudiante_id', $pagoColumns, true) ? 'estudiante_id' : (in_array('id_estudiante', $pagoColumns, true) ? 'id_estudiante' : 'estudiante'));

    $sql = "
        SELECT
            p.id,
            p.{$columnaEstudiante} AS estudiante,
            p.tipo_pago,
            p.monto_original,
            p.monto_descuento,
            p.monto_final,
            p.fecha_pago,
            p.comprobante,
            p.registrado_en,
            tp.nombre AS tipo_pago_nombre
        FROM pagos p
        LEFT JOIN tipo_pago tp ON tp.id = p.tipo_pago
        ORDER BY COALESCE(p.fecha_pago, p.registrado_en) DESC
        LIMIT 200
    ";
    $stmt = $pdo->query($sql);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pagos = [];
}

$precioLabel = 'Precio';
$precioValue = $editTipoPago ? ($editTipoPago['precio'] ?? '') : '';
?>

<div class="space-y-6">
  <div>
    <h2 class="text-2xl font-bold">Administrar Caja</h2>
    <p class="text-gray-600">Métodos de pago (tipo_pago) y pagos registrados (pagos).</p>
  </div>

  <?php if (isset($_GET['msg'])) cajaMsg((string)$_GET['msg']); ?>

  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">Métodos de Pago</h3>
      <span class="text-sm text-gray-500"><?= count($tiposPago) ?></span>
    </div>

    <?php if (empty($tiposPago)): ?>
      <div class="text-gray-600">No hay métodos de pago registrados.</div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($tiposPago as $idx => $tp): ?>
          <?php
            $precio = $tp['precio'] ?? null;
            $precioTxt = $precio !== null ? number_format((float)$precio, 2, '.', '') : '—';
          ?>
          <div class="rounded-2xl border border-gray-200 p-5 bg-gradient-to-br from-slate-800 to-slate-900 text-white">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm opacity-80">ID #<?= (int)$tp['id'] ?></div>
                <div class="text-xl font-bold mt-1"><?= htmlspecialchars($tp['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-sm opacity-90 mt-2"><?= htmlspecialchars($tp['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="text-right">
                <div class="text-xs opacity-80"><?= htmlspecialchars($precioLabel, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-2xl font-bold mt-1"><?= htmlspecialchars($precioTxt, ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>

            <div class="flex items-center gap-3 mt-4">
              <a class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/15 border border-white/10" href="dashboard-admin.php?pagina=admin-caja&editar_tipo_pago=<?= (int)$tp['id'] ?>">
                <i class="fas fa-pen mr-1"></i>Editar
              </a>
              <a class="px-3 py-2 rounded-lg bg-red-500/20 hover:bg-red-500/30 border border-red-400/30" href="dashboard-admin.php?pagina=admin-caja&eliminar_tipo_pago=<?= (int)$tp['id'] ?>" onclick="return confirm('¿Eliminar este método de pago?')">
                <i class="fas fa-trash mr-1"></i>Eliminar
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="mt-8 border-t border-gray-200 pt-6">
      <h4 class="text-lg font-semibold mb-4"><?= $editTipoPago ? 'Editar método de pago' : 'Nuevo método de pago' ?></h4>

      <form method="POST" action="dashboard-admin.php?pagina=admin-caja" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="hidden" name="accion" value="<?= $editTipoPago ? 'editar' : 'crear' ?>">
        <?php if ($editTipoPago): ?>
          <input type="hidden" name="id" value="<?= (int)$editTipoPago['id'] ?>">
        <?php endif; ?>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre *</label>
          <input name="nombre" required class="w-full px-4 py-3 rounded-xl border border-gray-200" value="<?= htmlspecialchars($editTipoPago['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1"><?= htmlspecialchars($precioLabel, ENT_QUOTES, 'UTF-8') ?></label>
          <input type="number" step="0.01" min="0" name="precio" class="w-full px-4 py-3 rounded-xl border border-gray-200" value="<?= htmlspecialchars((string)$precioValue, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="md:col-span-3">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción</label>
          <textarea name="descripcion" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200"><?= htmlspecialchars($editTipoPago['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="md:col-span-3 flex items-center gap-3">
          <button class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
            <i class="fas fa-save mr-2"></i><?= $editTipoPago ? 'Actualizar' : 'Guardar' ?>
          </button>
          <?php if ($editTipoPago): ?>
            <a class="px-5 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold" href="dashboard-admin.php?pagina=admin-caja">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">Pagos Registrados</h3>
      <span class="text-sm text-gray-500"><?= count($pagos) ?></span>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-indigo-600 text-white">
            <th class="p-2 text-left">ID</th>
            <th class="p-2 text-left">Estudiante</th>
            <th class="p-2 text-left">Tipo Pago</th>
            <th class="p-2 text-right">Monto</th>
            <th class="p-2 text-right">Descuento</th>
            <th class="p-2 text-right">Final</th>
            <th class="p-2 text-left">Fecha</th>
            <th class="p-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagos as $p): ?>
            <tr class="border-b">
              <td class="p-2"><?= (int)$p['id'] ?></td>
              <td class="p-2"><?= htmlspecialchars((string)($p['estudiante'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2"><?= htmlspecialchars((string)($p['tipo_pago_nombre'] ?? $p['tipo_pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2 text-right"><?= number_format((float)($p['monto_original'] ?? 0), 2, '.', '') ?></td>
              <td class="p-2 text-right"><?= number_format((float)($p['monto_descuento'] ?? 0), 2, '.', '') ?></td>
              <td class="p-2 text-right"><?= number_format((float)($p['monto_final'] ?? 0), 2, '.', '') ?></td>
              <td class="p-2"><?= htmlspecialchars((string)($p['fecha_pago'] ?? $p['registrado_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2">
                <div class="flex items-center gap-3">
                  <a class="text-blue-700 hover:underline" href="dashboard-admin.php?pagina=admin-caja&editar_pago=<?= (int)$p['id'] ?>">Editar</a>
                  <a class="text-red-600 hover:underline" href="dashboard-admin.php?pagina=admin-caja&eliminar_pago=<?= (int)$p['id'] ?>" onclick="return confirm('¿Eliminar este pago?')">Eliminar</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($pagos)): ?>
            <tr><td class="p-3 text-gray-600" colspan="8">No hay pagos registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-8 border-t border-gray-200 pt-6">
      <h4 class="text-lg font-semibold mb-4"><?= $editPago ? 'Editar pago' : 'Nuevo pago' ?></h4>

      <form method="POST" action="dashboard-admin.php?pagina=admin-caja" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="accion_pago" value="<?= $editPago ? 'editar' : 'crear' ?>">
        <?php if ($editPago): ?>
          <input type="hidden" name="pago_id" value="<?= (int)$editPago['id'] ?>">
        <?php endif; ?>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Estudiante (ID) *</label>
          <input type="number" min="1" name="estudiante" required class="w-full px-4 py-3 rounded-xl border border-gray-200" value="<?= htmlspecialchars((string)($editPago['estudiante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de Pago *</label>
          <select name="tipo_pago" required class="w-full px-4 py-3 rounded-xl border border-gray-200">
            <option value="">Seleccione...</option>
            <?php foreach ($tiposPago as $tp): ?>
              <?php $sel = $editPago && (int)($editPago['tipo_pago'] ?? 0) === (int)$tp['id']; ?>
              <option value="<?= (int)$tp['id'] ?>" <?= $sel ? 'selected' : '' ?>><?= htmlspecialchars($tp['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Monto *</label>
          <input type="number" step="0.01" min="0" name="monto_original" required class="w-full px-4 py-3 rounded-xl border border-gray-200" value="<?= htmlspecialchars((string)($editPago['monto_original'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Descuento</label>
          <input type="number" step="0.01" min="0" name="monto_descuento" class="w-full px-4 py-3 rounded-xl border border-gray-200" value="<?= htmlspecialchars((string)($editPago['monto_descuento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="md:col-span-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Comprobante (opcional)</label>
          <input name="comprobante" class="w-full px-4 py-3 rounded-xl border border-gray-200" value="<?= htmlspecialchars((string)($editPago['comprobante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="md:col-span-4 flex items-center gap-3">
          <button class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
            <i class="fas fa-save mr-2"></i><?= $editPago ? 'Actualizar' : 'Guardar' ?>
          </button>
          <?php if ($editPago): ?>
            <a class="px-5 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold" href="dashboard-admin.php?pagina=admin-caja">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>
