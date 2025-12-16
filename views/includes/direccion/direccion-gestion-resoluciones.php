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

<section class="w-full space-y-8">
  <!-- Header mejorado -->
  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Resoluciones</h1>
        <p class="text-gray-700 mt-2">Crea, activa/desactiva y gestiona las resoluciones del sistema.</p>
      </div>
      <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl shadow-sm border">
        <span class="text-gray-600">Total:</span>
        <span class="text-2xl font-bold text-blue-600"><?= count($lista) ?></span>
      </div>
    </div>
  </div>

  <!-- Formulario de creación mejorado -->
  <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
      <h3 class="text-xl font-bold text-white">Crear Nueva Resolución</h3>
      <p class="text-blue-100 text-sm">Completa todos los campos requeridos</p>
    </div>
    
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">
            <span class="text-red-500">*</span> N° Resolución
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <input id="new_numero" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Ej: RES-2025-001">
          </div>
        </div>
        
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">
            <span class="text-red-500">*</span> Título
          </label>
          <input id="new_titulo" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Título descriptivo de la resolución">
        </div>
        
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">
            <span class="text-red-500">*</span> Tipo de pago
          </label>
          <div class="relative">
            <select id="new_tipo_pago" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition appearance-none bg-white">
              <option value="">Seleccione un tipo...</option>
              <?php foreach ($tiposPago as $tp): ?>
                <option value="<?= (int)$tp['id'] ?>"><?= htmlspecialchars((string)$tp['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>
        </div>
        
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Monto (S/)</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">S/</span>
            </div>
            <input id="new_monto" type="number" step="0.01" min="0" class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="0.00">
          </div>
        </div>
        
        <div class="md:col-span-2 space-y-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción</label>
          <textarea id="new_desc" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Observaciones y detalles adicionales..."></textarea>
        </div>
        
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha fin (opcional)</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <input id="new_fecha_fin" type="date" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
          </div>
        </div>
        
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Estado inicial</label>
          <div class="relative">
            <select id="new_estado" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition appearance-none bg-white">
              <option value="0">📋 No activa (pendiente)</option>
              <option value="1">✅ Activa</option>
            </select>
          </div>
        </div>
      </div>

      <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
        <button class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-3 px-8 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2" onclick="crearResolucion()">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Crear Resolución
        </button>
      </div>
    </div>
  </div>

  <!-- Listado mejorado -->
  <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h3 class="text-xl font-bold text-gray-900">Resoluciones Existentes</h3>
        <div class="flex items-center gap-2">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
            <span class="text-sm text-gray-600">Activa</span>
          </div>
          <div class="flex items-center gap-2 ml-4">
            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
            <span class="text-sm text-gray-600">No activa</span>
          </div>
        </div>
      </div>
    </div>

    <div class="p-6">
      <?php if (empty($lista)): ?>
        <div class="text-center py-12">
          <div class="mx-auto w-24 h-24 text-gray-300 mb-4">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h4 class="text-lg font-medium text-gray-900 mb-2">No hay resoluciones</h4>
          <p class="text-gray-600 max-w-md mx-auto">Comienza creando tu primera resolución usando el formulario superior.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <?php foreach ($lista as $r): ?>
            <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition-shadow duration-200 <?= !empty($r['estado']) ? 'bg-gradient-to-r from-green-50 to-white' : 'bg-gradient-to-r from-yellow-50 to-white' ?>">
              <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <h4 class="font-bold text-gray-900 text-lg">
                        <?= htmlspecialchars((string)($r['numero_resolucion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      </h4>
                      <span class="text-xs font-semibold px-3 py-1 rounded-full <?= !empty($r['estado']) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= !empty($r['estado']) ? '✅ Activa' : '⏳ No activa' ?>
                      </span>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <p class="text-gray-800 font-medium"><?= htmlspecialchars((string)($r['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                  </div>
                  
                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-2 text-gray-600">
                      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>S/ <?= htmlspecialchars((string)($r['monto_descuento'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span><?= htmlspecialchars((string)($r['fecha_fin'] ?? 'Sin fecha'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  </div>
                </div>
                
                <div class="flex flex-col gap-2 min-w-[140px]">
                  <button class="w-full bg-gradient-to-r from-slate-700 to-slate-800 hover:from-slate-800 hover:to-slate-900 text-white font-semibold py-2.5 rounded-lg transition-all duration-200 flex items-center justify-center gap-2" 
                          onclick="toggleEstado(<?= (int)$r['id'] ?>, <?= !empty($r['estado']) ? 'false' : 'true' ?>)">
                    <?php if (!empty($r['estado'])): ?>
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Desactivar
                    <?php else: ?>
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Activar
                    <?php endif; ?>
                  </button>
                  
                  <button class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold py-2.5 rounded-lg transition-all duration-200 flex items-center justify-center gap-2" 
                          onclick="eliminarResolucion(<?= (int)$r['id'] ?>)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
// Las funciones JavaScript se mantienen exactamente igual
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