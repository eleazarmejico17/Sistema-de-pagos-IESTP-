<?php
require_once __DIR__ . '/../../../models/bienestar-beneficiariosModel.php';
$beneficiarioModel = new BeneficiarioModel();
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
                <p class="text-blue-100 text-sm">Lista de beneficiarios registrados</p>
            </div>
        </div>

        <span class="px-3 py-1 bg-blue-500 bg-opacity-20 rounded-full text-sm font-medium">
            <?= count($solicitudesAprobadas) ?> registros
        </span>
    </div>

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
