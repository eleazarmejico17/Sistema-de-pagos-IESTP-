<?php
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../models/bienestar-resolucionesModel.php';

$pdo = Conexion::getInstance()->getConnection();
$model = new ResolucionModel();
$lista = $model->listarTodas();

$tiposPago = [];
try {
    $stmt = $pdo->query("SELECT id, nombre FROM tipo_pago ORDER BY id ASC");
    $tiposPago = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $tiposPago = [];
}
?>

<section class="w-full space-y-6">
  <div>
    <h2 class="text-2xl font-bold text-gray-800">Resoluciones</h2>
    <p class="text-gray-600">Gestiona resoluciones: crear, activar/desactivar y eliminar.</p>
  </div>

  <div class="bg-white rounded-2xl shadow border border-gray-200 p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Nueva resolución</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">N° Resolución</label>
        <input id="new_numero" class="w-full px-4 py-3 border rounded-xl" placeholder="Ej: RES-2025-001">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Título</label>
        <input id="new_titulo" class="w-full px-4 py-3 border rounded-xl" placeholder="Título de la resolución">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de pago</label>
        <select id="new_tipo_pago" class="w-full px-4 py-3 border rounded-xl">
          <option value="">Seleccione...</option>
          <?php foreach ($tiposPago as $tp): ?>
            <option value="<?= (int)$tp['id'] ?>"><?= htmlspecialchars((string)$tp['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Monto (S/)</label>
        <input id="new_monto" type="number" step="0.01" min="0" class="w-full px-4 py-3 border rounded-xl" placeholder="0.00">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción</label>
        <textarea id="new_desc" rows="3" class="w-full px-4 py-3 border rounded-xl" placeholder="Observaciones"></textarea>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha fin (opcional)</label>
        <input id="new_fecha_fin" type="date" class="w-full px-4 py-3 border rounded-xl">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Estado inicial</label>
        <select id="new_estado" class="w-full px-4 py-3 border rounded-xl">
          <option value="0">No activa (pendiente)</option>
          <option value="1">Activa</option>
        </select>
      </div>
    </div>

    <div class="mt-4">
      <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-5 rounded-xl" onclick="crearResolucion()">
        Crear resolución
      </button>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-gray-800">Listado</h3>
      <span class="text-sm text-gray-600">Total: <?= count($lista) ?></span>
    </div>

    <?php if (empty($lista)): ?>
      <div class="text-gray-600">No hay resoluciones.</div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($lista as $r): ?>
          <div class="border rounded-2xl p-4">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="font-bold text-gray-800">
                  <?= htmlspecialchars((string)($r['numero_resolucion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                  <span class="ml-2 text-xs px-2 py-1 rounded-full <?= !empty($r['estado']) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                    <?= !empty($r['estado']) ? 'Activa' : 'No activa' ?>
                  </span>
                </div>
                <div class="text-sm text-gray-700 mt-1"><?= htmlspecialchars((string)($r['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-gray-500 mt-1">Monto: S/ <?= htmlspecialchars((string)($r['monto_descuento'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-gray-500">Fecha fin: <?= htmlspecialchars((string)($r['fecha_fin'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
              </div>

              <div class="flex flex-col gap-2 w-full max-w-xs">
                <button class="bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 rounded-xl" onclick="toggleEstado(<?= (int)$r['id'] ?>, <?= !empty($r['estado']) ? 'false' : 'true' ?>)">
                  <?= !empty($r['estado']) ? 'Desactivar' : 'Activar' ?>
                </button>
                <button class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-xl" onclick="eliminarResolucion(<?= (int)$r['id'] ?>)">
                  Eliminar
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
async function crearResolucion() {
  const numero = document.getElementById('new_numero').value.trim();
  const titulo = document.getElementById('new_titulo').value.trim();
  const tipoPago = document.getElementById('new_tipo_pago').value;
  const monto = document.getElementById('new_monto').value;
  const desc = document.getElementById('new_desc').value;
  const fechaFin = document.getElementById('new_fecha_fin').value;
  const estado = document.getElementById('new_estado').value;

  if (!numero || !titulo || !tipoPago) {
    alert('Completa N° Resolución, Título y Tipo de pago');
    return;
  }

  const res = await fetch('../controller/resolucionesCrearController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      numero_resolucion: numero,
      titulo,
      texto_respaldo: desc,
      tipo_pago: tipoPago,
      monto_descuento: monto,
      fecha_fin: fechaFin,
      estado: estado === '1'
    })
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    alert(data.error || 'No se pudo crear');
    return;
  }

  location.reload();
}

async function toggleEstado(id, estado) {
  const res = await fetch('../controller/resolucionesController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ accion: 'cambiar_estado', id, estado })
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    alert(data.error || 'No se pudo cambiar el estado');
    return;
  }
  location.reload();
}

async function eliminarResolucion(id) {
  if (!confirm('¿Eliminar resolución?')) return;
  const res = await fetch('../controller/resolucionesController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ accion: 'eliminar', id })
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    alert(data.error || 'No se pudo eliminar');
    return;
  }
  location.reload();
}
</script>
