<?php
// --------------------------------------------------
// NOTIFICACIONES MEJORADAS
// --------------------------------------------------
function showMessage($type) {
    $messages = [
        "creado"     => ["Registro creado correctamente", "bg-emerald-50 border-emerald-200 text-emerald-800", "fa-check-circle"],
        "actualizado"=> ["Registro actualizado correctamente", "bg-blue-50 border-blue-200 text-blue-800", "fa-check-circle"],
        "eliminado"  => ["Registro eliminado correctamente", "bg-red-50 border-red-200 text-red-800", "fa-trash-alt"],
        "error"      => ["Ha ocurrido un error", "bg-red-50 border-red-200 text-red-800", "fa-exclamation-circle"]
    ];

    if (!isset($messages[$type])) return;

    [$text, $color, $icon] = $messages[$type];
    
    // Si hay un detalle en el error, mostrarlo
    $detalle = '';
    if ($type === 'error' && isset($_GET['detalle'])) {
        $detalle = htmlspecialchars(urldecode($_GET['detalle']), ENT_QUOTES, 'UTF-8');
        $text .= ": " . $detalle;
    }

    echo "
        <div class='alert-auto p-4 mb-6 border-l-4 rounded-lg shadow-lg animate-slideDown $color flex items-center gap-3'>
            <i class='fas $icon text-xl'></i>
            <p class='font-semibold'>$text</p>
        </div>
        <style>
            @keyframes slideDown { 
                from { opacity:0; transform: translateY(-20px); } 
                to { opacity:1; transform: translateY(0); } 
            }
            .animate-slideDown { animation: slideDown .4s ease-out; }
        </style>
    ";
}

// ---------------------------------------------------------------------
// CONEXIÓN Y CONSULTAS (solo después de procesar acciones)
// ---------------------------------------------------------------------
require_once __DIR__ . '/../../../config/conexion.php';
$pdo = Conexion::getInstance()->getConnection();

// ---------------------------------------------------------------------
// FORMULARIO DE EDICIÓN
// ---------------------------------------------------------------------
$editData = null;

if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM tipo_pago WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $_GET['editar']]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ---------------------------------------------------------------------
// CONSULTAS
// ---------------------------------------------------------------------
$lista = [];
try {
    $stmt = $pdo->query("SELECT * FROM tipo_pago ORDER BY id ASC");
    $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $lista = [];
}

// Consulta para conceptos visibles para usuarios (formato numerado 1.1, 1.2, etc.)
$conceptosUsuario = [];
try {
    $stmtConceptos = $pdo->query("\n        SELECT id, nombre, descripcion, COALESCE(uit, 0.00) AS uit\n        FROM tipo_pago\n        WHERE nombre REGEXP '^[0-9]+\\.[0-9]+$'\n        ORDER BY CAST(SUBSTRING_INDEX(nombre, '.', 1) AS UNSIGNED) ASC,\n                 CAST(SUBSTRING_INDEX(nombre, '.', -1) AS UNSIGNED) ASC\n    ");
    $conceptosUsuario = $stmtConceptos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback simple si REGEXP no está disponible
    $stmtConceptos = $pdo->query("\n        SELECT id, nombre, descripcion, COALESCE(uit, 0.00) AS uit\n        FROM tipo_pago\n        WHERE nombre LIKE '%.%'\n        ORDER BY nombre ASC\n    ");
    $conceptosUsuario = $stmtConceptos->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="max-w-full mx-auto p-6">

    <!-- ALERTAS -->
    <?php if (isset($_GET['msg'])) showMessage($_GET['msg']); ?>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-cash-register text-blue-600 text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Gestión de Tipos de Pago</h1>
                <p class="text-gray-600 text-sm">Administre los conceptos y métodos de pago del sistema</p>
            </div>
        </div>
    </div>

    <!-- LAYOUT HORIZONTAL -->
    <div class="flex flex-col xl:flex-row gap-6">

      <!-- FORMULARIO (LADO IZQUIERDO) -->
      <div class="xl:w-1/3">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas <?= $editData ? 'fa-edit' : 'fa-plus' ?> text-blue-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        <?= $editData ? "Editar Tipo de Pago" : "Nuevo Tipo de Pago" ?>
                    </h2>
                    <p class="text-gray-500 text-sm">
                        <?= $editData ? "Actualice los datos del tipo de pago" : "Registre un nuevo concepto o método" ?>
                    </p>
                </div>
            </div>
            <?php if ($editData): ?>
                <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm text-amber-800">Modo de edición activo</p>
                </div>
            <?php endif; ?>

        <form method="POST" class="space-y-4">
                <input type="hidden" name="accion" value="<?= $editData ? "editar" : "crear" ?>">
                <?php if ($editData): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($editData['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" name="nombre" id="nombre" required
                           value="<?= htmlspecialchars($editData['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto (UIT)</label>
                    <input type="number" name="uit" id="uit" step="0.01" min="0" required
                           value="<?= htmlspecialchars($editData['uit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($editData['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Visible para usuarios</label>
                        <select name="visible_usuario" id="visible_usuario" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="1" <?= (isset($editData['visible_usuario']) && $editData['visible_usuario'] == 1) ? 'selected' : '' ?>>Sí</option>
                            <option value="0" <?= (isset($editData['visible_usuario']) && $editData['visible_usuario'] == 0) ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="estado" id="estado" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="1" <?= (isset($editData['estado']) && $editData['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= (isset($editData['estado']) && $editData['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        <?= $editData ? "Actualizar" : "Registrar" ?>
                    </button>
                    <?php if ($editData): ?>
                        <a href="dashboard-admin.php?pagina=admin-tipo-pago"
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
      </div>

      <!-- TABLAS (LADO DERECHO - OCUPA MÁS ESPACIO) -->
      <div class="xl:flex-1 space-y-4">

        <!-- Botones tipo pestañas -->
        <div class="bg-gray-50 rounded-lg p-1 border border-gray-200 inline-flex">
          <button id="tab-tipos" type="button"
                  class="tab-btn px-4 py-2 text-sm font-medium rounded-md bg-white text-blue-600 border border-gray-300">
            Tipos de Pago
          </button>
          <button id="tab-conceptos" type="button"
                  class="tab-btn px-4 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900">
            Conceptos (Usuario)
          </button>
        </div>

        <!-- TABLA -->
        <div id="panel-tipos" class="bg-white border border-gray-200 rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Tipos de Pago Registrados</h2>
                <span class="text-sm text-gray-500"><?= count($lista) ?> registros</span>
            </div>
        </div>

        <?php if (empty($lista)): ?>
            <div class="text-center py-8 px-6">
                <div class="text-gray-400 mb-2">
                    <i class="fas fa-inbox text-4xl"></i>
                </div>
                <p class="text-gray-500">No hay tipos de pago registrados</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">UIT</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($lista as $index => $item): ?>
                            <tr class="hover:bg-gray-50 <?= $editData && $editData['id'] == $item['id'] ? 'bg-blue-50' : '' ?>">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                    <?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?= htmlspecialchars($item['descripcion'] ?: 'Sin descripción', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                    <?= isset($item['uit']) ? number_format((float)$item['uit'], 2, '.', '') : '0.00' ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                    <a href="dashboard-admin.php?pagina=admin-tipo-pago&editar=<?= $item['id'] ?>"
                                       class="text-blue-600 hover:text-blue-900 mr-3 inline-flex items-center">
                                        <i class="fas fa-edit mr-1"></i>Editar
                                    </a>
                                    <a href="dashboard-admin.php?pagina=admin-tipo-pago&eliminar=<?= $item['id'] ?>"
                                       onclick="return confirmarEliminacion('<?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>')"
                                       class="text-red-600 hover:text-red-900 inline-flex items-center">
                                        <i class="fas fa-trash mr-1"></i>Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        </div>

        <!-- VISTA DE CONCEPTOS DE PAGO (COMO LO VE EL USUARIO) -->
        <div id="panel-conceptos" class="bg-white border border-gray-200 rounded-lg hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Conceptos de Pago (Vista Usuario)</h2>
                <span class="text-sm text-gray-500"><?= count($lista) ?> conceptos disponibles</span>
            </div>
        </div>

        <?php if (empty($lista)): ?>
            <div class="text-center py-8 px-6">
                <div class="text-gray-400 mb-2">
                    <i class="fas fa-inbox text-4xl"></i>
                </div>
                <p class="text-gray-500">No hay conceptos de pago disponibles</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">UIT</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($lista as $index => $item): 
                            $id = (int)$item['id'];
                            $descripcion = htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8');
                            $uit = isset($item['uit']) && $item['uit'] > 0 ? number_format((float)$item['uit'], 2, '.', '') : number_format(0.00, 2, '.', '');
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    <?= $id ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?= $descripcion ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    S/ <?= $uit ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                    <a href="dashboard-admin.php?pagina=admin-tipo-pago&editar=<?= $id ?>"
                                       class="text-blue-600 hover:text-blue-900 mr-3">Editar</a>
                                    <a href="dashboard-admin.php?pagina=admin-tipo-pago&eliminar=<?= $id ?>"
                                       onclick="return confirmarEliminacion('<?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?>')"
                                       class="text-red-600 hover:text-red-900">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Información adicional -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-2"></i>
                        Esta vista muestra los conceptos de pago disponibles para los estudiantes, tal como los ven ellos en el sistema.
                    </div>
                    <div class="text-sm text-gray-500">
                        Total conceptos: <?= count($lista) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>

      </div> <!-- fin columna derecha -->

    </div> <!-- fin grid principal -->
</div>

<script>
// Auto-ocultar alertas después de 4 segundos
setTimeout(() => {
    const alert = document.querySelector('.alert-auto');
    if (alert) {
        alert.style.transition = 'opacity 0.5s ease-out';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }
}, 4000);

// Función mejorada para confirmar eliminación
function confirmarEliminacion(nombre) {
    return confirm(`¿Estás seguro de que deseas eliminar el tipo de pago "${nombre}"?\n\nEsta acción no se puede deshacer.`);
}

// Scroll suave al formulario cuando se está editando
<?php if ($editData): ?>
document.addEventListener('DOMContentLoaded', function() {
    const formCard = document.querySelector('.bg-white');
    if (formCard) {
        setTimeout(() => {
            formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
});
<?php endif; ?>

// Tabs para alternar tablas en columna derecha
document.addEventListener('DOMContentLoaded', function() {
    const tabTipos = document.getElementById('tab-tipos');
    const tabConceptos = document.getElementById('tab-conceptos');
    const panelTipos = document.getElementById('panel-tipos');
    const panelConceptos = document.getElementById('panel-conceptos');

    if (!tabTipos || !tabConceptos || !panelTipos || !panelConceptos) return;

    function activarTab(t) {
        const esTipos = t === 'tipos';

        panelTipos.classList.toggle('hidden', !esTipos);
        panelConceptos.classList.toggle('hidden', esTipos);

        if (esTipos) {
            tabTipos.classList.add('bg-white','text-blue-600','border','border-gray-300');
            tabConceptos.classList.remove('bg-white','text-blue-600','border','border-gray-300');
            tabConceptos.classList.add('text-gray-600');
        } else {
            tabConceptos.classList.add('bg-white','text-blue-600','border','border-gray-300');
            tabTipos.classList.remove('bg-white','text-blue-600','border','border-gray-300');
            tabTipos.classList.add('text-gray-600');
        }
    }

    tabTipos.addEventListener('click', () => activarTab('tipos'));
    tabConceptos.addEventListener('click', () => activarTab('conceptos'));

    // Estado inicial: mostrar tipos de pago
    activarTab('tipos');
});
</script>

<style>
/* Animación suave para las filas de la tabla */
@keyframes fadeInRow {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

tbody tr {
    animation: fadeInRow 0.3s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(1) { animation-delay: 0.05s; }
tbody tr:nth-child(2) { animation-delay: 0.1s; }
tbody tr:nth-child(3) { animation-delay: 0.15s; }
tbody tr:nth-child(4) { animation-delay: 0.2s; }
tbody tr:nth-child(5) { animation-delay: 0.25s; }
tbody tr:nth-child(n+6) { animation-delay: 0.3s; }
</style>
