<?php
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../models/bienestar-resolucionesModel.php';

$pdo = Conexion::getInstance()->getConnection();
$model = new ResolucionModel();
$pendientes = $model->listarPendientes();
?>

<section class="w-full space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-gray-800">Solicitudes de Resolución (Pendientes)</h2>
      <p class="text-gray-600">Aquí puedes ajustar el monto y la fecha fin antes de aprobar o rechazar.</p>
    </div>
    <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full"><?= count($pendientes) ?> pendientes</span>
  </div>

  <?php if (empty($pendientes)): ?>
    <div class="bg-white rounded-2xl shadow border border-gray-200 p-8 text-center text-gray-600">
      No hay resoluciones pendientes.
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($pendientes as $r): ?>
        <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm text-gray-500">Resolución</div>
              <div class="text-lg font-bold text-gray-800">
                <?= htmlspecialchars((string)($r['numero_resolucion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div class="text-sm text-gray-700 mt-1">
                <?= htmlspecialchars((string)($r['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div class="text-xs text-gray-500 mt-2">
                Creado por: <?= htmlspecialchars((string)($r['creado_por_nombre'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
              </div>
            </div>

            <div class="w-full max-w-md space-y-3">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Monto (S/)</label>
                  <input type="number" step="0.01" min="0" class="w-full px-3 py-2 border rounded-xl" id="monto-<?= (int)$r['id'] ?>" value="<?= htmlspecialchars((string)($r['monto_descuento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha fin (opcional)</label>
                  <input type="date" class="w-full px-3 py-2 border rounded-xl" id="fechafin-<?= (int)$r['id'] ?>" value="<?= htmlspecialchars((string)($r['fecha_fin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>

              <div class="flex gap-2">
                <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl" onclick="guardarCambios(<?= (int)$r['id'] ?>)">
                  Guardar cambios
                </button>
                <button class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-xl" onclick="aprobar(<?= (int)$r['id'] ?>)">
                  Aprobar
                </button>
                <button class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-xl" onclick="rechazar(<?= (int)$r['id'] ?>)">
                  Rechazar
                </button>
              </div>

              <?php if (!empty($r['ruta_documento'])): ?>
                <a class="text-sm text-blue-700 hover:underline" target="_blank" href="../<?= htmlspecialchars((string)$r['ruta_documento'], ENT_QUOTES, 'UTF-8') ?>">
                  Ver documento adjunto
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script>
async function guardarCambios(id) {
  const monto = document.getElementById('monto-' + id).value;
  const fechaFin = document.getElementById('fechafin-' + id).value;

  const res = await fetch('../controller/resolucionesController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ accion: 'actualizar_campos', id, monto_descuento: monto, fecha_fin: fechaFin })
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    alert(data.error || 'No se pudo guardar');
    return;
  }
  alert('Cambios guardados');
}

async function aprobar(id) {
  if (!confirm('¿Aprobar esta resolución?')) return;
  const res = await fetch('../controller/resolucionesController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ accion: 'cambiar_estado', id, estado: true })
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    alert(data.error || 'No se pudo aprobar');
    return;
  }
  location.reload();
}

async function rechazar(id) {
  if (!confirm('¿Rechazar esta resolución? Esto la eliminará.')) return;
  const res = await fetch('../controller/resolucionesController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ accion: 'eliminar', id })
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    alert(data.error || 'No se pudo rechazar');
    return;
  }
  location.reload();
}
</script>
