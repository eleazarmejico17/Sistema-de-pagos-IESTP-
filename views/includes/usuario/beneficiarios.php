<?php
require_once __DIR__ . '/../../../models/bienestar-beneficiariosModel.php';
$beneficiarioModel = new BeneficiarioModel();

$resolucionIdFiltro = null;
if (isset($_GET['resolucion_id'])) {
    $resolucionIdFiltro = (int)$_GET['resolucion_id'];
    if ($resolucionIdFiltro <= 0) {
        $resolucionIdFiltro = null;
    }
}

$beneficiariosResolucion = [];
$infoResolucion = null;

if ($resolucionIdFiltro !== null) {
    try {
        require_once __DIR__ . '/../../../config/conexion.php';
        $conn = Conexion::getInstance()->getConnection();

        $stmtColsBen = $conn->query('SHOW COLUMNS FROM beneficiarios');
        $colsBen = $stmtColsBen->fetchAll(PDO::FETCH_COLUMN);

        $colEstBen = in_array('estudiante', $colsBen, true) ? 'estudiante' : (in_array('estudiante_id', $colsBen, true) ? 'estudiante_id' : (in_array('id_estudiante', $colsBen, true) ? 'id_estudiante' : null));
        $colResBen = in_array('resoluciones', $colsBen, true) ? 'resoluciones' : (in_array('resolucion_id', $colsBen, true) ? 'resolucion_id' : (in_array('id_resolucion', $colsBen, true) ? 'id_resolucion' : (in_array('resolucion', $colsBen, true) ? 'resolucion' : null)));

        if ($colEstBen === null || $colResBen === null) {
            throw new Exception('No se encontró la estructura esperada en la tabla beneficiarios.');
        }

        $stmtRes = $conn->prepare('SELECT id, numero_resolucion, titulo, monto_descuento, fecha_inicio, fecha_fin FROM resoluciones WHERE id = :id LIMIT 1');
        $stmtRes->execute([':id' => $resolucionIdFiltro]);
        $infoResolucion = $stmtRes->fetch(PDO::FETCH_ASSOC) ?: null;

        $sql = "
            SELECT
                e.dni_est,
                CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre,
                pe.nom_progest AS programa_nombre,
                m.per_acad AS ciclo,
                COALESCE(b.activo, 1) AS activo
            FROM beneficiarios b
            INNER JOIN estudiante e ON e.id = b.{$colEstBen}
            LEFT JOIN matricula m ON m.estudiante = e.id
            LEFT JOIN prog_estudios pe ON pe.id = m.prog_estudios
            WHERE b.{$colResBen} = :resolucion_id
            ORDER BY nombre ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':resolucion_id' => $resolucionIdFiltro]);
        $beneficiariosResolucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $beneficiariosResolucion = [];
        $infoResolucion = null;
        echo "<!-- DEBUG Error: " . $e->getMessage() . " -->";
    }
} else {
    $solicitudesAprobadas = $beneficiarioModel->listarSolicitudesAprobadas();

    // Diagnóstico temporal
    echo "<!-- DEBUG: Total solicitudes aprobadas encontradas: " . count($solicitudesAprobadas) . " -->";
    if (!empty($solicitudesAprobadas)) {
        echo "<!-- DEBUG: Primera solicitud: " . print_r($solicitudesAprobadas[0], true) . " -->";
    }

    // Verificar directamente en BD para diagnóstico
    try {
        require_once __DIR__ . '/../../../config/conexion.php';
        $conn = Conexion::getInstance()->getConnection();
        $sql = "SELECT id, nombre, estado FROM solicitudes WHERE estado LIKE '%apro%' OR estado LIKE '%Apro%' LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $directas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<!-- DEBUG: Solicitudes directas de BD: " . print_r($directas, true) . " -->";
    } catch(Exception $e) {
        echo "<!-- DEBUG Error: " . $e->getMessage() . " -->";
    }
}
?>

<div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">

    <!-- ===== HEADER ===== -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-6 py-5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-500 rounded-lg">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold">BENEFICIARIOS</h2>
                <p class="text-blue-100 text-sm">
                  <?php if ($resolucionIdFiltro !== null): ?>
                    Beneficiarios por resolución
                  <?php else: ?>
                    Lista de beneficiarios registrados
                  <?php endif; ?>
                </p>
            </div>
        </div>

        <span class="px-3 py-1 bg-blue-500 bg-opacity-20 rounded-full text-sm font-medium">
            <?= $resolucionIdFiltro !== null ? count($beneficiariosResolucion) : count($solicitudesAprobadas) ?> registros
        </span>
    </div>

    <?php if ($resolucionIdFiltro !== null): ?>
      <div class="p-5 border-b border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <div class="font-semibold text-gray-800">Resolución</div>
            <div class="text-sm text-gray-600">
              <?php if ($infoResolucion): ?>
                <?= htmlspecialchars((string)$infoResolucion['numero_resolucion'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$infoResolucion['titulo'], ENT_QUOTES, 'UTF-8') ?>
              <?php else: ?>
                Resolución #<?= (int)$resolucionIdFiltro ?>
              <?php endif; ?>
            </div>
          </div>
          <a href="?pagina=comprobantes" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 text-center">
            Volver
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- ===== BUSCADOR ===== -->
    <div class="p-5 border-b border-gray-100">
        <div class="flex gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input 
                    type="text"
                    id="filtroBusqueda"
                    placeholder="Buscar por nombre, DNI o programa..."
                    class="pl-10 pr-4 py-2.5 w-full border border-gray-200 rounded-xl focus:ring-2 
                    focus:ring-blue-500"
                >
            </div>

            <button id="btnLimpiarFiltro" 
                class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 flex items-center gap-2">
                <i class="fas fa-sync-alt text-sm"></i> Limpiar
            </button>
        </div>
    </div>

    <!-- ===== TABLA ===== -->
    <div class="overflow-x-auto w-full">
        <table class="w-full table-auto">
            <?php if ($resolucionIdFiltro !== null): ?>
              <thead class="bg-gray-50 text-gray-600 text-sm uppercase font-semibold border-b">
                  <tr>
                      <th class="px-6 py-4 text-left">Nombre Completo</th>
                      <th class="px-6 py-4 text-left">DNI</th>
                      <th class="px-6 py-4 text-left">Programa</th>
                      <th class="px-6 py-4 text-left">Ciclo</th>
                      <th class="px-6 py-4 text-left">Estado</th>
                  </tr>
              </thead>

              <tbody id="tablaCuerpo" class="text-gray-700">
                  <?php foreach ($beneficiariosResolucion as $b): ?>
                  <tr class="hover:bg-gray-50 transition-all filtro-row">
                      <td class="px-6 py-4">
                        <span class="font-semibold"><?= htmlspecialchars((string)$b['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                      </td>
                      <td class="px-6 py-4">
                        <?= htmlspecialchars((string)$b['dni_est'], ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td class="px-6 py-4">
                        <?= htmlspecialchars((string)($b['programa_nombre'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td class="px-6 py-4">
                        <?= htmlspecialchars((string)($b['ciclo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td class="px-6 py-4">
                        <?php if ((int)($b['activo'] ?? 1) === 1): ?>
                          <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Activo</span>
                        <?php else: ?>
                          <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-semibold">Inactivo</span>
                        <?php endif; ?>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
            <?php else: ?>
              <thead class="bg-gray-50 text-gray-600 text-sm uppercase font-semibold border-b">
                  <tr>
                      <th class="px-6 py-4 text-left">Nombre Completo</th>
                      <th class="px-6 py-4 text-left">Solicitud</th>
                      <th class="px-6 py-4 text-left">Estado</th>
                      <th class="px-6 py-4 text-center">Acciones</th>
                  </tr>
              </thead>

              <tbody id="tablaCuerpo" class="text-gray-700">
                  <?php foreach ($solicitudesAprobadas as $sol): ?>
                  <tr class="hover:bg-gray-50 transition-all filtro-row">

                      <!-- ===== NOMBRE Y AVATAR ===== -->
                      <td class="px-6 py-4 flex items-center gap-3">
                          <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                              <i class="fas fa-user text-blue-600"></i>
                          </div>

                          <div class="flex flex-col leading-tight">
                              <span class="font-semibold"><?= $sol['nombre'] ?></span>
                              <span class="text-xs text-gray-500">DNI: <?= $sol['dni_est'] ?></span>
                          </div>
                      </td>

                      <!-- ===== SOLICITUD ===== -->
                      <td class="px-6 py-4">
                          <div class="font-medium"><?= $sol['tipo_solicitud'] ?></div>
                          <div class="text-xs text-gray-500">
                              <?= date('d/m/Y', strtotime($sol['fecha'])) ?>
                          </div>
                      </td>

                      <!-- ===== ESTADO ===== -->
                      <td class="px-6 py-4">
                          <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                              Aprobado
                          </span>
                      </td>

                      <!-- ===== ACCIONES ===== -->
                      <td class="px-6 py-4 text-center">
                          <div class="flex items-center justify-center gap-3">
                              <button onclick="verDetalles(<?= $sol['id'] ?>)" 
                                  class="text-blue-600 hover:text-blue-800">
                                  <i class="fas fa-eye"></i>
                              </button>

                              <button onclick="generarCertificado(<?= $sol['id'] ?>)" 
                                  class="text-green-600 hover:text-green-800">
                                  <i class="fas fa-file-pdf"></i>
                              </button>
                          </div>
                      </td>

                  </tr>
                  <?php endforeach; ?>
              </tbody>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
// === FILTRO ===
document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("filtroBusqueda");
    const rows = document.querySelectorAll(".filtro-row");
    const btn = document.getElementById("btnLimpiarFiltro");

    input.addEventListener("input", () => {
        const txt = input.value.toLowerCase();
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(txt) ? "" : "none";
        });
    });

    btn.addEventListener("click", () => {
        input.value = "";
        rows.forEach(r => r.style.display = "");
    });
});

// === FUNCIONES ===
function verDetalles(id) {
    alert("Ver detalles: " + id);
}

function generarCertificado(id) {
    alert("Generar certificado: " + id);
}
</script>
