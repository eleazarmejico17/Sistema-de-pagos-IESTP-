<?php
require_once __DIR__ . '/../../../config/conexion.php';

$pdo = Conexion::getInstance()->getConnection();

function cajaMsg(string $type): void {
    $messages = [
        'creado' => ['Registro creado correctamente', 'bg-emerald-50 border-emerald-200 text-emerald-800', 'fa-check-circle'],
        'actualizado' => ['Registro actualizado correctamente', 'bg-blue-50 border-blue-200 text-blue-800', 'fa-check-circle'],
        'eliminado' => ['Registro eliminado correctamente', 'bg-red-50 border-red-200 text-red-800', 'fa-trash-alt'],
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

    echo "<div class='p-4 mb-6 border-l-4 rounded-lg shadow $color flex items-center gap-3 animate-fade-in'>
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

$hasMetodoPagoTable = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'metodo_pago'");
    $hasMetodoPagoTable = (bool) $stmt->fetch(PDO::FETCH_NUM);
} catch (Throwable $e) {
    $hasMetodoPagoTable = false;
}

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

$editMetodoPago = null;
if ($hasMetodoPagoTable && isset($_GET['editar_metodo_pago'])) {
    $id = (int)$_GET['editar_metodo_pago'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM metodo_pago WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $editMetodoPago = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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

$metodosPago = [];
if ($hasMetodoPagoTable) {
    try {
        $stmt = $pdo->query('SELECT * FROM metodo_pago ORDER BY id ASC');
        $metodosPago = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $metodosPago = [];
    }
}

// Listar pagos
$pagos = [];
$hasMetodoPagoColumn = false;
try {
    $stmtColumns = $pdo->query('SHOW COLUMNS FROM pagos');
    $pagoColumns = $stmtColumns->fetchAll(PDO::FETCH_COLUMN);
    $columnaEstudiante = in_array('estudiante', $pagoColumns, true) ? 'estudiante' : (in_array('estudiante_id', $pagoColumns, true) ? 'estudiante_id' : (in_array('id_estudiante', $pagoColumns, true) ? 'id_estudiante' : 'estudiante'));
    $hasMetodoPagoColumn = in_array('metodo_pago', $pagoColumns, true);

    if ($hasMetodoPagoTable && $hasMetodoPagoColumn) {
        $sql = "
            SELECT
                p.id,
                p.{$columnaEstudiante} AS estudiante,
                p.tipo_pago,
                p.metodo_pago,
                p.monto_original,
                p.monto_descuento,
                p.monto_final,
                p.fecha_pago,
                p.comprobante,
                p.registrado_en,
                tp.nombre AS tipo_pago_nombre,
                mp.nombre AS metodo_pago_nombre
            FROM pagos p
            LEFT JOIN tipo_pago tp ON tp.id = p.tipo_pago
            LEFT JOIN metodo_pago mp ON mp.id = p.metodo_pago
            ORDER BY COALESCE(p.fecha_pago, p.registrado_en) DESC
            LIMIT 200
        ";
    } else {
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
    }
    $stmt = $pdo->query($sql);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pagos = [];
}

$precioLabel = 'Precio';
$precioValue = $editTipoPago ? ($editTipoPago['precio'] ?? '') : '';
?>

<style>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px;
    overflow: hidden;
}
.stat-card-content {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.1);
}
</style>

<div class="space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl shadow-xl p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">Administración de Caja</h2>
                <p class="text-blue-100">Gestión completa de tipos de pago, métodos y registros de pagos.</p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <div class="text-center">
                    <div class="text-2xl font-bold"><?= count($tiposPago) ?></div>
                    <div class="text-sm text-blue-200">Tipos de pago</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold"><?= count($pagos) ?></div>
                    <div class="text-sm text-blue-200">Pagos registrados</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['msg'])) cajaMsg((string)$_GET['msg']); ?>

    <!-- Tipos de Pago Section -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="border-b border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-tags text-blue-600"></i>
                        Tipos de Pago
                    </h3>
                    <p class="text-gray-500 text-sm mt-1">Configure los diferentes tipos de pagos disponibles</p>
                </div>
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                    <?= count($tiposPago) ?> registros
                </span>
            </div>
        </div>

        <div class="p-6">
            <?php if (empty($tiposPago)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-tags text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600">No hay tipos de pago registrados.</p>
                    <p class="text-gray-500 text-sm mt-2">Comience agregando un nuevo tipo de pago</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($tiposPago as $idx => $tp): ?>
                        <?php
                            $precio = $tp['precio'] ?? null;
                            $precioTxt = $precio !== null ? '$' . number_format((float)$precio, 2, '.', ',') : '—';
                            $colors = [
                                'from-blue-500 to-blue-600',
                                'from-emerald-500 to-emerald-600',
                                'from-purple-500 to-purple-600',
                                'from-amber-500 to-amber-600',
                                'from-rose-500 to-rose-600',
                                'from-cyan-500 to-cyan-600'
                            ];
                            $colorClass = $colors[$idx % count($colors)];
                        ?>
                        <div class="rounded-xl border border-gray-200 overflow-hidden hover-lift">
                            <div class="p-5 bg-gradient-to-br <?= $colorClass ?> text-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-xs opacity-80 font-medium">ID #<?= (int)$tp['id'] ?></div>
                                        <div class="text-lg font-bold mt-1 truncate"><?= htmlspecialchars($tp['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs opacity-80"><?= htmlspecialchars($precioLabel, ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-xl font-bold mt-1"><?= htmlspecialchars($precioTxt, ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                                <?php if (!empty($tp['descripcion'])): ?>
                                    <div class="mt-3 text-sm opacity-90 line-clamp-2"><?= htmlspecialchars($tp['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="bg-white p-4 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <a class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-100 text-sm font-medium transition-colors flex items-center gap-1" 
                                           href="dashboard-admin.php?pagina=admin-caja&editar_tipo_pago=<?= (int)$tp['id'] ?>">
                                            <i class="fas fa-pen text-xs"></i> Editar
                                        </a>
                                        <a class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 border border-red-100 text-sm font-medium transition-colors flex items-center gap-1" 
                                           href="dashboard-admin.php?pagina=admin-caja&eliminar_tipo_pago=<?= (int)$tp['id'] ?>" 
                                           onclick="return confirm('¿Está seguro de eliminar este tipo de pago?')">
                                            <i class="fas fa-trash text-xs"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Formulario Tipos de Pago -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <div class="flex items-center justify-between mb-6">
                    <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas <?= $editTipoPago ? 'fa-edit' : 'fa-plus' ?>"></i>
                        <?= $editTipoPago ? 'Editar Tipo de Pago' : 'Nuevo Tipo de Pago' ?>
                    </h4>
                    <?php if ($editTipoPago): ?>
                        <a class="text-sm text-gray-500 hover:text-gray-700" href="dashboard-admin.php?pagina=admin-caja">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="dashboard-admin.php?pagina=admin-caja" class="space-y-4">
                    <input type="hidden" name="accion" value="<?= $editTipoPago ? 'editar' : 'crear' ?>">
                    <?php if ($editTipoPago): ?>
                        <input type="hidden" name="id" value="<?= (int)$editTipoPago['id'] ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-tag text-gray-400"></i>
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input name="nombre" required 
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-colors"
                                   placeholder="Ej: Matrícula"
                                   value="<?= htmlspecialchars($editTipoPago['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-dollar-sign text-gray-400"></i>
                                <?= htmlspecialchars($precioLabel, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input type="number" step="0.01" min="0" name="precio" 
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-colors"
                                   placeholder="0.00"
                                   value="<?= htmlspecialchars((string)$precioValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-align-left text-gray-400"></i>
                                Descripción
                            </label>
                            <textarea name="descripcion" rows="3" 
                                      class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-colors resize-none"
                                      placeholder="Descripción del tipo de pago"><?= htmlspecialchars($editTipoPago['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <button class="px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            <?= $editTipoPago ? 'Actualizar' : 'Guardar Tipo de Pago' ?>
                        </button>
                        <?php if ($editTipoPago): ?>
                            <a class="px-6 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition-colors" 
                               href="dashboard-admin.php?pagina=admin-caja">
                                Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Métodos de Pago Section -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="border-b border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-credit-card text-purple-600"></i>
                        Métodos de Pago
                    </h3>
                    <p class="text-gray-500 text-sm mt-1">Configure las formas de pago aceptadas</p>
                </div>
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">
                    <?= $hasMetodoPagoTable ? count($metodosPago) : 0 ?> registros
                </span>
            </div>
        </div>

        <div class="p-6">
            <?php if (!$hasMetodoPagoTable): ?>
                <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-xl">
                    <i class="fas fa-database text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 font-medium">Tabla no encontrada</p>
                    <p class="text-gray-500 text-sm mt-2">La tabla <span class="font-mono bg-gray-100 px-2 py-1 rounded">metodo_pago</span> no existe en la base de datos.</p>
                </div>
            <?php elseif (empty($metodosPago)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600">No hay métodos de pago registrados.</p>
                    <p class="text-gray-500 text-sm mt-2">Agregue métodos de pago como efectivo, tarjeta, transferencia, etc.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($metodosPago as $mp): ?>
                        <div class="rounded-xl border border-gray-200 overflow-hidden hover-lift">
                            <div class="p-5 bg-gradient-to-br from-purple-500 to-indigo-600 text-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-xs opacity-80 font-medium">ID #<?= (int)$mp['id'] ?></div>
                                        <div class="text-lg font-bold mt-1"><?= htmlspecialchars($mp['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <i class="fas fa-credit-card text-xl opacity-70"></i>
                                </div>
                                <?php if (!empty($mp['descripcion'])): ?>
                                    <div class="mt-3 text-sm opacity-90"><?= htmlspecialchars($mp['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="bg-white p-4 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    <a class="px-3 py-1.5 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 border border-purple-100 text-sm font-medium transition-colors flex items-center gap-1" 
                                       href="dashboard-admin.php?pagina=admin-caja&editar_metodo_pago=<?= (int)$mp['id'] ?>">
                                        <i class="fas fa-pen text-xs"></i> Editar
                                    </a>
                                    <a class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 border border-red-100 text-sm font-medium transition-colors flex items-center gap-1" 
                                       href="dashboard-admin.php?pagina=admin-caja&eliminar_metodo_pago=<?= (int)$mp['id'] ?>" 
                                       onclick="return confirm('¿Está seguro de eliminar este método de pago?')">
                                        <i class="fas fa-trash text-xs"></i> Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($hasMetodoPagoTable): ?>
                <!-- Formulario Métodos de Pago -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas <?= $editMetodoPago ? 'fa-edit' : 'fa-plus' ?>"></i>
                            <?= $editMetodoPago ? 'Editar Método de Pago' : 'Nuevo Método de Pago' ?>
                        </h4>
                        <?php if ($editMetodoPago): ?>
                            <a class="text-sm text-gray-500 hover:text-gray-700" href="dashboard-admin.php?pagina=admin-caja">
                                <i class="fas fa-times mr-1"></i> Cancelar
                            </a>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="dashboard-admin.php?pagina=admin-caja" class="space-y-4">
                        <input type="hidden" name="accion_metodo" value="<?= $editMetodoPago ? 'editar' : 'crear' ?>">
                        <?php if ($editMetodoPago): ?>
                            <input type="hidden" name="metodo_id" value="<?= (int)$editMetodoPago['id'] ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                    <i class="fas fa-tag text-gray-400"></i>
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input name="metodo_nombre" required 
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition-colors"
                                       placeholder="Ej: Efectivo, Tarjeta, Transferencia"
                                       value="<?= htmlspecialchars($editMetodoPago['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                    <i class="fas fa-align-left text-gray-400"></i>
                                    Descripción
                                </label>
                                <textarea name="metodo_descripcion" rows="2" 
                                          class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition-colors resize-none"
                                          placeholder="Descripción del método de pago"><?= htmlspecialchars($editMetodoPago['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-4">
                            <button class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-700 hover:from-purple-700 hover:to-indigo-800 text-white font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                <?= $editMetodoPago ? 'Actualizar' : 'Guardar Método' ?>
                            </button>
                            <?php if ($editMetodoPago): ?>
                                <a class="px-6 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition-colors" 
                                   href="dashboard-admin.php?pagina=admin-caja">
                                    Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagos Registrados Section -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="border-b border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-receipt text-emerald-600"></i>
                        Pagos Registrados
                    </h3>
                    <p class="text-gray-500 text-sm mt-1">Historial de todos los pagos realizados</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm font-semibold">
                        <?= count($pagos) ?> registros
                    </span>
                    <button onclick="document.querySelector('#form-pago').scrollIntoView({ behavior: 'smooth' })" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i> Nuevo Pago
                    </button>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estudiante</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tipo</th>
                                <?php if ($hasMetodoPagoTable && $hasMetodoPagoColumn): ?>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Método</th>
                                <?php endif; ?>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Monto</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Descuento</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Final</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($pagos as $p): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            #<?= (int)$p['id'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                        <?= htmlspecialchars((string)($p['estudiante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            <?= htmlspecialchars((string)($p['tipo_pago_nombre'] ?? $p['tipo_pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <?php if ($hasMetodoPagoTable && $hasMetodoPagoColumn): ?>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?= htmlspecialchars((string)($p['metodo_pago_nombre'] ?? $p['metodo_pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                        $<?= number_format((float)($p['monto_original'] ?? 0), 2, '.', ',') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                        $<?= number_format((float)($p['monto_descuento'] ?? 0), 2, '.', ',') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-bold text-emerald-700">
                                        $<?= number_format((float)($p['monto_final'] ?? 0), 2, '.', ',') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-sm">
                                        <?= htmlspecialchars((string)($p['fecha_pago'] ?? $p['registrado_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <a class="px-3 py-1 text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors font-medium" 
                                               href="dashboard-admin.php?pagina=admin-caja&editar_pago=<?= (int)$p['id'] ?>">
                                                <i class="fas fa-pen mr-1"></i>Editar
                                            </a>
                                            <a class="px-3 py-1 text-xs bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors font-medium" 
                                               href="dashboard-admin.php?pagina=admin-caja&eliminar_pago=<?= (int)$p['id'] ?>" 
                                               onclick="return confirm('¿Está seguro de eliminar este pago?')">
                                                <i class="fas fa-trash mr-1"></i>Eliminar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pagos)): ?>
                                <tr>
                                    <td colspan="<?= ($hasMetodoPagoTable && $hasMetodoPagoColumn) ? 9 : 8 ?>" class="px-4 py-12 text-center">
                                        <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-600">No hay pagos registrados</p>
                                        <p class="text-gray-500 text-sm mt-2">Registre el primer pago usando el formulario a continuación</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Formulario Pagos -->
            <div id="form-pago" class="mt-8 pt-8 border-t border-gray-200">
                <div class="flex items-center justify-between mb-6">
                    <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas <?= $editPago ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $editPago ? 'Editar Pago' : 'Registrar Nuevo Pago' ?>
                    </h4>
                    <?php if ($editPago): ?>
                        <a class="text-sm text-gray-500 hover:text-gray-700" href="dashboard-admin.php?pagina=admin-caja">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="dashboard-admin.php?pagina=admin-caja" class="space-y-6">
                    <input type="hidden" name="accion_pago" value="<?= $editPago ? 'editar' : 'crear' ?>">
                    <?php if ($editPago): ?>
                        <input type="hidden" name="pago_id" value="<?= (int)$editPago['id'] ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-user-graduate text-gray-400"></i>
                                ID Estudiante <span class="text-red-500">*</span>
                            </label>
                            <input type="number" min="1" name="estudiante" required 
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-colors"
                                   placeholder="Ej: 123"
                                   value="<?= htmlspecialchars((string)($editPago['estudiante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-tags text-gray-400"></i>
                                Tipo de Pago <span class="text-red-500">*</span>
                            </label>
                            <select name="tipo_pago" required 
                                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-colors appearance-none">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($tiposPago as $tp): ?>
                                    <?php $sel = $editPago && (int)($editPago['tipo_pago'] ?? 0) === (int)$tp['id']; ?>
                                    <option value="<?= (int)$tp['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tp['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($hasMetodoPagoTable && $hasMetodoPagoColumn): ?>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                    <i class="fas fa-credit-card text-gray-400"></i>
                                    Método <span class="text-red-500">*</span>
                                </label>
                                <select name="metodo_pago" required 
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-colors appearance-none">
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($metodosPago as $mp): ?>
                                        <?php $sel = $editPago && (int)($editPago['metodo_pago'] ?? 0) === (int)$mp['id']; ?>
                                        <option value="<?= (int)$mp['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mp['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-money-bill-wave text-gray-400"></i>
                                Monto Original <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-gray-500">$</span>
                                <input type="number" step="0.01" min="0" name="monto_original" required 
                                       class="w-full pl-8 px-4 py-3 rounded-xl border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-colors"
                                       placeholder="0.00"
                                       value="<?= htmlspecialchars((string)($editPago['monto_original'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-percentage text-gray-400"></i>
                                Descuento
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-gray-500">$</span>
                                <input type="number" step="0.01" min="0" name="monto_descuento" 
                                       class="w-full pl-8 px-4 py-3 rounded-xl border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-colors"
                                       placeholder="0.00"
                                       value="<?= htmlspecialchars((string)($editPago['monto_descuento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <div class="md:col-span-2 lg:col-span-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-file-invoice text-gray-400"></i>
                                Comprobante (opcional)
                            </label>
                            <input name="comprobante" 
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-colors"
                                   placeholder="Número o referencia del comprobante"
                                   value="<?= htmlspecialchars((string)($editPago['comprobante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <button class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            <?= $editPago ? 'Actualizar Pago' : 'Registrar Pago' ?>
                        </button>
                        <?php if ($editPago): ?>
                            <a class="px-6 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition-colors" 
                               href="dashboard-admin.php?pagina=admin-caja">
                                Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>