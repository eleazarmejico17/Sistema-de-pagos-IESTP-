<?php
// Incluir el modelo de beneficiarios
require_once __DIR__ . '/../../../models/bienestar-beneficiariosModel.php';
$beneficiarioModel = new BeneficiarioModel();

require_once __DIR__ . '/../../../config/conexion.php';

$db = Conexion::getInstance()->getConnection();

$dniBeneficiario = isset($_GET['dni_beneficiario']) ? trim((string)$_GET['dni_beneficiario']) : '';
$beneficioEncontrado = null;
$beneficioError = null;

$sessionUser = (string)($_SESSION['usuario'] ?? '');
$dniSesion = null;
if (preg_match('/^(\d{8})@institutocajas\.edu\.pe$/', $sessionUser, $m)) {
  $dniSesion = $m[1];
}

$estudiante = null;
if ($dniSesion) {
  $stmtEst = $db->prepare("SELECT id, dni_est, ap_est, am_est, nom_est, cel_est, maili_est, mailp_est FROM estudiante WHERE dni_est = :dni LIMIT 1");
  $stmtEst->execute([':dni' => $dniSesion]);
  $estudiante = $stmtEst->fetch(PDO::FETCH_ASSOC) ?: null;
}

$resoluciones = [];
try {
  $stmtRes = $db->query("SELECT id, numero_resolucion, titulo, texto_respaldo, monto_descuento, fecha_inicio, fecha_fin, estado, creado_en
    FROM resoluciones
    ORDER BY creado_en DESC");
  $resoluciones = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $resoluciones = [];
}

try {
  if ($dniBeneficiario !== '') {
    if (!preg_match('/^\d{8}$/', $dniBeneficiario)) {
      $beneficioError = 'Ingresa un DNI válido (8 dígitos).';
    } else {
      $stmtColsBen = $db->query('SHOW COLUMNS FROM beneficiarios');
      $colsBen = $stmtColsBen->fetchAll(PDO::FETCH_COLUMN);

      $colEstBen = in_array('estudiante', $colsBen, true) ? 'estudiante' : (in_array('estudiante_id', $colsBen, true) ? 'estudiante_id' : (in_array('id_estudiante', $colsBen, true) ? 'id_estudiante' : null));
      $colResBen = in_array('resoluciones', $colsBen, true) ? 'resoluciones' : (in_array('resolucion_id', $colsBen, true) ? 'resolucion_id' : (in_array('id_resolucion', $colsBen, true) ? 'id_resolucion' : (in_array('resolucion', $colsBen, true) ? 'resolucion' : null)));

      if ($colEstBen === null || $colResBen === null) {
        throw new Exception('No se encontró la estructura esperada en la tabla beneficiarios.');
      }

      $sqlBenef = "
        SELECT
          b.id AS beneficiario_id,
          b.activo,
          r.id AS resolucion_id,
          r.numero_resolucion,
          r.titulo,
          r.monto_descuento,
          r.fecha_inicio,
          r.fecha_fin,
          e.id AS estudiante_id,
          e.dni_est,
          CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS estudiante_nombre
        FROM beneficiarios b
        INNER JOIN estudiante e ON e.id = b.{$colEstBen}
        INNER JOIN resoluciones r ON r.id = b.{$colResBen}
        WHERE e.dni_est = :dni
          AND COALESCE(b.activo, 1) = 1
        ORDER BY COALESCE(r.fecha_inicio, r.id) DESC
        LIMIT 1
      ";

      $stmtBenef = $db->prepare($sqlBenef);
      $stmtBenef->execute([':dni' => $dniBeneficiario]);
      $beneficioEncontrado = $stmtBenef->fetch(PDO::FETCH_ASSOC) ?: null;
      if (!$beneficioEncontrado) {
        $beneficioError = 'No se encontró un beneficio activo para ese DNI.';
      }
    }
  }
} catch (Throwable $e) {
  $beneficioError = 'Error consultando beneficiario: ' . $e->getMessage();
}

$hoy = (new DateTime('now'))->format('Y-m-d');
$hoyObj = new DateTime($hoy);

$beneficiarioResolucionIds = [];
if ($estudiante && isset($estudiante['id'])) {
  try {
    $stmtBen = $db->prepare('SELECT resoluciones FROM beneficiarios WHERE estudiante = :estudiante AND activo = 1');
    $stmtBen->execute([':estudiante' => (int)$estudiante['id']]);
    $beneficiarioResolucionIds = array_map('intval', $stmtBen->fetchAll(PDO::FETCH_COLUMN));
  } catch (Throwable $e) {
    $beneficiarioResolucionIds = [];
  }
}

$resolucionesFiltradas = [];
$resolucionesDisponibles = [];

foreach ($resoluciones as $r) {
  $rid = (int)($r['id'] ?? 0);
  if ($rid <= 0) continue;
  if (in_array($rid, $beneficiarioResolucionIds, true)) {
    continue;
  }

  $inicioStr = (string)($r['fecha_inicio'] ?? '');
  $finStr = (string)($r['fecha_fin'] ?? '');

  $inicio = null;
  $fin = null;

  try {
    if ($inicioStr !== '' && $inicioStr !== '0000-00-00') {
      $inicio = new DateTime($inicioStr);
    }
  } catch (Throwable $e) {
    $inicio = null;
  }

  try {
    if ($finStr !== '' && $finStr !== '0000-00-00') {
      $fin = new DateTime($finStr);
    }
  } catch (Throwable $e) {
    $fin = null;
  }

  $enRango = true;
  if ($inicio && $hoyObj < $inicio) $enRango = false;
  if ($fin && $hoyObj > $fin) $enRango = false;

  $estado = (int)($r['estado'] ?? 0) === 1;
  $disponible = $estado && $enRango;

  $r['_disponible'] = $disponible;
  $r['_en_rango'] = $enRango;
  $r['_estado'] = $estado;

  $resolucionesFiltradas[] = $r;
  if ($disponible) {
    $resolucionesDisponibles[] = $r;
  }
}
?>

<style>
    .resolucion-card {
        transition: all 0.2s ease-in-out;
        border: 2px solid transparent;
    }
    .resolucion-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: #3b82f6;
    }
    .resolucion-card.disabled {
        opacity: 0.7;
        filter: grayscale(20%);
    }
    .resolucion-card.disabled:hover {
        transform: none;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-color: transparent;
    }
    .badge {
        transition: all 0.2s ease;
    }
    .fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .form-section {
        animation: slideUp 0.4s ease-out;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="space-y-8">
    <!-- Header con información del estudiante -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 text-white">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-2">Solicitudes de Beneficios</h2>
                    <p class="text-blue-100 opacity-90">Solicita tu admisión en las resoluciones vigentes para obtener descuentos</p>
                    
                    <?php if ($estudiante): ?>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fas fa-user-graduate text-lg"></i>
                        </div>
                        <div>
                            <div class="font-semibold"><?= htmlspecialchars(trim((string)(($estudiante['ap_est'] ?? '') . ' ' . ($estudiante['am_est'] ?? '') . ' ' . ($estudiante['nom_est'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-sm text-blue-200">DNI: <?= htmlspecialchars((string)($estudiante['dni_est'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 min-w-[200px]">
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-1"><?= count($resolucionesDisponibles) ?></div>
                        <div class="text-sm text-blue-200">Resoluciones disponibles</div>
                        <div class="text-xs text-blue-300 mt-2">
                            <?= count($resolucionesFiltradas) ?> en total
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Consultar beneficio por DNI</h2>
      <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="pagina" value="inicio">
        <input
          type="text"
          name="dni_beneficiario"
          value="<?= htmlspecialchars($dniBeneficiario, ENT_QUOTES, 'UTF-8') ?>"
          placeholder="Ingresa DNI (8 dígitos)"
          class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
        >
        <button type="submit" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white rounded-lg font-medium">
          Buscar
        </button>
        <?php if ($dniBeneficiario !== ''): ?>
          <a href="?pagina=inicio" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium text-center">
            Limpiar
          </a>
        <?php endif; ?>
      </form>

      <?php if ($beneficioError): ?>
        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">
          <?= htmlspecialchars($beneficioError, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php elseif ($beneficioEncontrado): ?>
        <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-lg p-4">
          <div class="font-semibold">Beneficiario encontrado</div>
          <div class="text-sm mt-1">
            <div><strong>Estudiante:</strong> <?= htmlspecialchars((string)$beneficioEncontrado['estudiante_nombre'], ENT_QUOTES, 'UTF-8') ?> (DNI: <?= htmlspecialchars((string)$beneficioEncontrado['dni_est'], ENT_QUOTES, 'UTF-8') ?>)</div>
            <div class="mt-2"><strong>Resolución:</strong> <?= htmlspecialchars((string)$beneficioEncontrado['numero_resolucion'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$beneficioEncontrado['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Monto descuento:</strong> S/ <?= number_format((float)($beneficioEncontrado['monto_descuento'] ?? 0), 2, '.', ',') ?></div>
          </div>
          <div class="mt-4">
            <a href="?pagina=beneficiarios&resolucion_id=<?= urlencode((string)$beneficioEncontrado['resolucion_id']) ?>"
               class="inline-block px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
              Ver beneficiarios de esta resolución
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$estudiante): ?>
    <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 rounded-xl p-5 fade-in">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-500 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-white text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-red-800 text-lg mb-1">Información del estudiante no encontrada</h3>
                <p class="text-red-700">No pudimos obtener tu información personal. Por favor:</p>
                <ul class="list-disc list-inside text-red-600 text-sm mt-2 space-y-1">
                    <li>Cierra sesión y vuelve a iniciar</li>
                    <li>Verifica que tus datos estén registrados en el sistema</li>
                    <li>Contacta al área de soporte si el problema persiste</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($resolucionesFiltradas)): ?>
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl shadow-sm border border-gray-200 p-10 text-center fade-in">
        <div class="max-w-md mx-auto">
            <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-file-contract text-gray-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No hay resoluciones disponibles</h3>
            <p class="text-gray-600 mb-4">
                <?php if ($beneficiarioResolucionIds): ?>
                    Ya eres beneficiario activo en algunas resoluciones. Las resoluciones activas no aparecen en esta lista.
                <?php else: ?>
                    No hay resoluciones activas en este momento. Intenta más tarde.
                <?php endif; ?>
            </p>
            <div class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Las resoluciones aparecen solo cuando están activas y dentro de su periodo de vigencia.
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Filtros de estado -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Filtrar por estado:</span>
            <button class="px-3 py-1.5 text-sm rounded-lg bg-blue-100 text-blue-700 font-medium border border-blue-200" onclick="filtrarResoluciones('todas')">
                Todas (<?= count($resolucionesFiltradas) ?>)
            </button>
            <button class="px-3 py-1.5 text-sm rounded-lg bg-emerald-100 text-emerald-700 font-medium border border-emerald-200" onclick="filtrarResoluciones('disponibles')">
                Disponibles (<?= count($resolucionesDisponibles) ?>)
            </button>
            <button class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-700 font-medium border border-gray-200" onclick="filtrarResoluciones('no-disponibles')">
                No disponibles
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="resoluciones-container">
        <?php foreach ($resolucionesFiltradas as $r): ?>
            <?php
                $tituloRes = (string)($r['titulo'] ?? '');
                $numeroRes = (string)($r['numero_resolucion'] ?? '');
                $desc = trim((string)($r['texto_respaldo'] ?? ''));
                $descBreve = mb_strlen($desc) > 120 ? (mb_substr($desc, 0, 120) . '...') : $desc;
                $montoRes = (float)($r['monto_descuento'] ?? 0);
                $fi = (string)($r['fecha_inicio'] ?? '');
                $ff = (string)($r['fecha_fin'] ?? '');
                $disponible = (bool)($r['_disponible'] ?? false);
                $estado = (bool)($r['_estado'] ?? false);
                $enRango = (bool)($r['_en_rango'] ?? false);
                
                // Determinar estado y colores
                if ($disponible) {
                    $badgeText = 'Disponible';
                    $badgeClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                    $statusClass = 'disponible';
                } elseif (!$estado) {
                    $badgeText = 'Inactiva';
                    $badgeClass = 'bg-gray-100 text-gray-700 border-gray-200';
                    $statusClass = 'inactiva';
                } elseif (!$enRango) {
                    $badgeText = 'Fuera de vigencia';
                    $badgeClass = 'bg-amber-100 text-amber-700 border-amber-200';
                    $statusClass = 'fuera-vigencia';
                } else {
                    $badgeText = 'No disponible';
                    $badgeClass = 'bg-gray-100 text-gray-700 border-gray-200';
                    $statusClass = 'no-disponible';
                }
                
                // Color de borde según estado
                $borderColor = $disponible ? 'border-blue-300' : 'border-gray-200';
            ?>
            
            <div class="resolucion-card bg-white rounded-xl shadow-md border <?= $borderColor ?> <?= !$disponible ? 'disabled' : '' ?> fade-in"
                 data-status="<?= $statusClass ?>"
                 data-disponible="<?= $disponible ? 'si' : 'no' ?>">
                <!-- Encabezado con número de resolución -->
                <div class="px-5 pt-5 pb-3 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-file-alt text-blue-600 text-sm"></i>
                            </div>
                            <span class="text-sm font-semibold text-blue-600"><?= htmlspecialchars($numeroRes, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border <?= $badgeClass ?> badge">
                            <?= htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>
                
                <!-- Contenido principal -->
                <div class="p-5">
                    <h3 class="text-lg font-bold text-gray-800 mb-3 leading-tight">
                        <?= htmlspecialchars($tituloRes, ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    
                    <p class="text-sm text-gray-600 mb-4 min-h-[60px]">
                        <?= htmlspecialchars($descBreve ?: 'Sin descripción.', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    
                    <!-- Información de descuento -->
                    <div class="bg-gradient-to-r from-emerald-50 to-green-50 rounded-lg p-4 mb-4 border border-emerald-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-emerald-600 font-medium">Descuento aplicable</div>
                                <div class="text-2xl font-bold text-emerald-700 mt-1">S/ <?= number_format($montoRes, 2, '.', '') ?></div>
                            </div>
                            <i class="fas fa-percentage text-emerald-400 text-2xl"></i>
                        </div>
                    </div>
                    
                    <!-- Información de fechas -->
                    <div class="space-y-2 text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar-day text-gray-400 w-4"></i>
                            <span>Inicio: <span class="font-medium text-gray-700"><?= htmlspecialchars($fi ?: '—', ENT_QUOTES, 'UTF-8') ?></span></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar-times text-gray-400 w-4"></i>
                            <span>Fin: <span class="font-medium text-gray-700"><?= htmlspecialchars($ff ?: '—', ENT_QUOTES, 'UTF-8') ?></span></span>
                        </div>
                    </div>
                </div>
                
                <!-- Botón de acción -->
                <div class="px-5 pb-5 pt-3 border-t border-gray-100">
                    <?php if ($disponible): ?>
                        <button type="button"
                                onclick="seleccionarResolucion(<?= (int)$r['id'] ?>)"
                                class="w-full px-4 py-3 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <i class="fas fa-paper-plane"></i>
                            Solicitar admisión
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-2">Haz clic para solicitar ser admitido</p>
                    <?php else: ?>
                        <button class="w-full px-4 py-3 rounded-lg bg-gray-100 text-gray-500 font-medium cursor-not-allowed flex items-center justify-center gap-2" disabled>
                            <i class="fas fa-lock"></i>
                            No disponible
                        </button>
                        <p class="text-xs text-gray-400 text-center mt-2">
                            <?= $badgeText === 'Inactiva' ? 'Resolución inactiva' : ($badgeText === 'Fuera de vigencia' ? 'Fuera del periodo de vigencia' : 'No disponible para solicitud') ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Sección del formulario (oculta inicialmente) -->
    <div id="solicitudFormSection" class="hidden form-section">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-blue-100">
            <!-- Header del formulario -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 border-b border-blue-100">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                            <i class="fas fa-file-signature text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Formulario de Solicitud</h2>
                            <p class="text-sm text-gray-600">Completa los campos requeridos para tu solicitud</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-sm text-gray-600 bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                            <i class="far fa-calendar-alt mr-1"></i>
                            <span class="font-semibold"><?= htmlspecialchars($hoy, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <button type="button" onclick="cerrarFormulario()" 
                                class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contenido del formulario -->
            <div class="p-6">
                <div id="alertSolicitud" class="hidden mb-5 p-4 rounded-xl border"></div>

                <form id="formSolicitudInicio" enctype="multipart/form-data" class="space-y-6">
                    <!-- Información del estudiante (solo lectura) -->
                    <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-user-circle text-blue-600"></i>
                            Información del Estudiante
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre completo</label>
                                <input type="text" readonly
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 font-medium"
                                       value="<?= htmlspecialchars(trim((string)(($estudiante ? ($estudiante['ap_est'] ?? '') : '') . ' ' . ($estudiante ? ($estudiante['am_est'] ?? '') : '') . ' ' . ($estudiante ? ($estudiante['nom_est'] ?? '') : ''))), ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">DNI</label>
                                <input type="text" readonly
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 font-medium"
                                       value="<?= htmlspecialchars((string)($estudiante['dni_est'] ?? $dniSesion ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Campos editables -->
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <i class="fas fa-phone text-blue-500"></i>
                                    Teléfono <span class="text-red-500 ml-1">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-3 text-gray-500">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <input type="tel" name="telefono" id="solTelefono" required
                                           class="w-full pl-10 px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                                           placeholder="Ej: 999 999 999"
                                           value="<?= htmlspecialchars((string)($estudiante ? ($estudiante['cel_est'] ?? '') : ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Número móvil de 9 dígitos que empiece con 9</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <i class="fas fa-file-contract text-blue-500"></i>
                                    Resolución <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="tipo" id="solResolucion" required
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all appearance-none bg-white">
                                    <option value="">Seleccione una resolución...</option>
                                    <?php foreach ($resolucionesDisponibles as $rr): ?>
                                        <option value="<?= (int)$rr['id'] ?>"
                                                data-monto="<?= number_format((float)($rr['monto_descuento'] ?? 0), 2, '.', '') ?>">
                                            <?= htmlspecialchars((string)($rr['numero_resolucion'] ?? ''), ENT_QUOTES, 'UTF-8') ?> - 
                                            <?= htmlspecialchars((string)($rr['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="solMonto" value="">
                                <div class="mt-2 p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                                    <div class="text-xs text-emerald-800">
                                        <i class="fas fa-tag mr-1"></i>
                                        Descuento aplicable: 
                                        <span id="solMontoInfo" class="font-bold text-emerald-700">S/ 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-align-left text-blue-500"></i>
                                Descripción de la solicitud <span class="text-red-500 ml-1">*</span>
                            </label>
                            <textarea name="descripcion" id="solDescripcion" required rows="4"
                                      class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all resize-none"
                                      placeholder="Describe detalladamente tu solicitud, incluyendo el motivo por el cual solicitas ser admitido en esta resolución..."></textarea>
                            <div class="flex justify-between text-xs text-gray-500 mt-2">
                                <span>Mínimo 10 caracteres</span>
                                <span id="contadorCaracteres">0 caracteres</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-paperclip text-blue-500"></i>
                                Evidencias de respaldo (opcional)
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors cursor-pointer"
                                 onclick="document.getElementById('solArchivo').click()">
                                <input type="file" name="archivo[]" id="solArchivo" multiple
                                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                       class="hidden">
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-cloud-upload-alt text-blue-500 text-xl"></i>
                                </div>
                                <div class="font-medium text-gray-700 mb-1">Haz clic para adjuntar archivos</div>
                                <div class="text-sm text-gray-500">
                                    Formatos: JPG, PNG, PDF, DOC, DOCX<br>
                                    Máximo 5 archivos, 5MB por archivo
                                </div>
                                <div id="archivosSeleccionados" class="mt-4 space-y-2 hidden"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                        <button type="submit" id="btnEnviarSolicitud"
                                class="flex-1 px-6 py-3 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
                                <?= !$estudiante ? 'disabled' : '' ?>>
                            <i class="fas fa-paper-plane"></i>
                            Enviar solicitud
                        </button>
                        <button type="button" id="btnEnviarSolicitudLoading"
                                class="hidden flex-1 px-6 py-3 rounded-lg bg-blue-300 text-white font-semibold cursor-not-allowed flex items-center justify-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            Procesando solicitud...
                        </button>
                        <button type="button" onclick="cerrarFormulario()"
                                class="px-6 py-3 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const resolucionesData = <?php echo json_encode(array_map(function ($r) {
  return [
    'id' => (int)$r['id'],
    'numero_resolucion' => (string)($r['numero_resolucion'] ?? ''),
    'titulo' => (string)($r['titulo'] ?? ''),
    'monto_descuento' => (float)($r['monto_descuento'] ?? 0),
  ];
}, $resolucionesDisponibles), JSON_UNESCAPED_UNICODE); ?>;

// Filtrar resoluciones
function filtrarResoluciones(tipo) {
    const cards = document.querySelectorAll('.resolucion-card');
    cards.forEach(card => {
        card.style.display = 'block';
        if (tipo === 'disponibles') {
            if (card.getAttribute('data-disponible') !== 'si') {
                card.style.display = 'none';
            }
        } else if (tipo === 'no-disponibles') {
            if (card.getAttribute('data-disponible') === 'si') {
                card.style.display = 'none';
            }
        }
    });
}

// Contador de caracteres
document.getElementById('solDescripcion').addEventListener('input', function() {
    const contador = document.getElementById('contadorCaracteres');
    const caracteres = this.value.length;
    contador.textContent = caracteres + ' caracteres';
    
    if (caracteres < 10) {
        contador.classList.add('text-red-500');
        contador.classList.remove('text-green-500');
    } else {
        contador.classList.remove('text-red-500');
        contador.classList.add('text-green-500');
    }
});

// Mostrar archivos seleccionados
document.getElementById('solArchivo').addEventListener('change', function() {
    const contenedor = document.getElementById('archivosSeleccionados');
    const archivos = this.files;
    
    if (archivos.length > 0) {
        contenedor.innerHTML = '';
        contenedor.classList.remove('hidden');
        
        for (let i = 0; i < Math.min(archivos.length, 5); i++) {
            const file = archivos[i];
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between bg-gray-50 rounded-lg p-3';
            div.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas fa-file text-gray-500"></i>
                    <span class="text-sm text-gray-700 truncate">${file.name}</span>
                </div>
                <span class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
            `;
            contenedor.appendChild(div);
        }
        
        if (archivos.length > 5) {
            const alerta = document.createElement('div');
            alerta.className = 'text-sm text-red-600 bg-red-50 p-2 rounded-lg';
            alerta.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Máximo 5 archivos permitidos';
            contenedor.appendChild(alerta);
        }
    } else {
        contenedor.classList.add('hidden');
    }
});

function seleccionarResolucion(id) {
    const section = document.getElementById('solicitudFormSection');
    const select = document.getElementById('solResolucion');
    const found = resolucionesData.find(r => parseInt(r.id) === parseInt(id));

    select.value = String(id);
    document.getElementById('solMonto').value = found ? String(found.monto_descuento || 0) : '';
    document.getElementById('solMontoInfo').textContent = found ? ('S/ ' + Number(found.monto_descuento || 0).toFixed(2)) : 'S/ 0.00';

    // Actualizar contador de caracteres
    document.getElementById('solDescripcion').dispatchEvent(new Event('input'));

    section.classList.remove('hidden');
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cerrarFormulario() {
    document.getElementById('solicitudFormSection').classList.add('hidden');
    document.getElementById('formSolicitudInicio').reset();
    document.getElementById('archivosSeleccionados').classList.add('hidden');
    document.getElementById('archivosSeleccionados').innerHTML = '';
    document.getElementById('contadorCaracteres').textContent = '0 caracteres';
    document.getElementById('contadorCaracteres').classList.remove('text-green-500', 'text-red-500');
    document.getElementById('alertSolicitud').classList.add('hidden');
}

document.getElementById('solResolucion').addEventListener('change', function() {
    const section = document.getElementById('solicitudFormSection');
    if (this.value) {
        section.classList.remove('hidden');
        const selectedOption = this.options[this.selectedIndex];
        const monto = selectedOption.getAttribute('data-monto') || '0.00';
        document.getElementById('solMontoInfo').textContent = 'S/ ' + monto;
    }
});

<?php if (!$estudiante): ?>
document.getElementById('btnEnviarSolicitud').setAttribute('disabled', 'disabled');
<?php endif; ?>

function setAlertSolicitud(type, text) {
    const el = document.getElementById('alertSolicitud');
    el.classList.remove('hidden', 'bg-green-50', 'border-green-200', 'text-green-800', 'bg-red-50', 'border-red-200', 'text-red-800', 'bg-yellow-50', 'border-yellow-200', 'text-yellow-800');
    
    if (type === 'success') {
        el.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
        el.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-lg"></i>
                <div>
                    <div class="font-semibold">¡Solicitud enviada!</div>
                    <div class="text-sm mt-1">${text}</div>
                </div>
            </div>
        `;
    } else {
        el.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
        el.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                <div>
                    <div class="font-semibold">Error</div>
                    <div class="text-sm mt-1">${text}</div>
                </div>
            </div>
        `;
    }
}

function validarArchivos(input) {
    const archivos = input.files;
    if (!archivos) return true;
    if (archivos.length > 5) {
        setAlertSolicitud('error', 'Máximo 5 archivos permitidos.');
        return false;
    }
    const tiposPermitidos = ['image/jpeg','image/png','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    for (const archivo of archivos) {
        if (archivo.size > 5 * 1024 * 1024) {
            setAlertSolicitud('error', `El archivo "${archivo.name}" excede el límite de 5MB.`);
            return false;
        }
        if (!tiposPermitidos.includes(archivo.type)) {
            setAlertSolicitud('error', `El archivo "${archivo.name}" tiene un formato no permitido.`);
            return false;
        }
    }
    return true;
}

document.getElementById('formSolicitudInicio').addEventListener('submit', function(e) {
    e.preventDefault();

    const select = document.getElementById('solResolucion');
    const telefono = document.getElementById('solTelefono').value.trim();
    const descripcion = document.getElementById('solDescripcion').value.trim();
    const archivoInput = document.getElementById('solArchivo');

    if (!select.value) {
        setAlertSolicitud('error', 'Por favor, selecciona una resolución.');
        select.focus();
        return;
    }
    
    if (!telefono) {
        setAlertSolicitud('error', 'El teléfono es requerido.');
        document.getElementById('solTelefono').focus();
        return;
    }
    
    const telefonoLimpio = telefono.replace(/\D/g, '');
    if (!/^9\d{8}$/.test(telefonoLimpio)) {
        setAlertSolicitud('error', 'Teléfono inválido. Debe tener 9 dígitos y empezar con 9.');
        document.getElementById('solTelefono').focus();
        return;
    }
    
    if (descripcion.length < 10) {
        setAlertSolicitud('error', 'La descripción debe tener al menos 10 caracteres.');
        document.getElementById('solDescripcion').focus();
        return;
    }
    
    if (archivoInput.files.length > 0 && !validarArchivos(archivoInput)) {
        return;
    }

    const btn = document.getElementById('btnEnviarSolicitud');
    const btnLoading = document.getElementById('btnEnviarSolicitudLoading');
    btn.classList.add('hidden');
    btnLoading.classList.remove('hidden');

    const formData = new FormData(this);

    fetch('../controller/guardarSolicitudController.php', {
        method: 'POST',
        body: formData,
    })
    .then(r => {
        if (!r.ok) throw new Error('Error en la respuesta del servidor');
        return r.json();
    })
    .then(data => {
        if (data.success) {
            setAlertSolicitud('success', data.message || 'Tu solicitud ha sido registrada exitosamente.');
            cerrarFormulario();
            
            // Recargar la página después de 2 segundos
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            setAlertSolicitud('error', data.error || 'No se pudo registrar la solicitud. Por favor, intenta nuevamente.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setAlertSolicitud('error', 'Error de conexión con el servidor. Por favor, verifica tu conexión a internet.');
    })
    .finally(() => {
        btn.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    });
});

// Inicializar contador de caracteres al cargar
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('solDescripcion').dispatchEvent(new Event('input'));
});
</script>