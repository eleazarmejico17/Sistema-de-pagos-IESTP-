<?php
// Incluir el modelo de beneficiarios
require_once __DIR__ . '/../../../models/bienestar-beneficiariosModel.php';
$beneficiarioModel = new BeneficiarioModel();

// Obtener la lista de beneficiarios
$beneficiarios = $beneficiarioModel->listarBeneficiarios();
$totalBeneficiarios = count($beneficiarios);
?>

<div class="space-y-8">
  <!-- Tarjeta de Resumen de Beneficiarios -->
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover-lift transition-all duration-300">
    <div class="p-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 mb-1">Resumen de Beneficiarios</h2>
          <p class="text-gray-600">Total de beneficiarios registrados en el sistema</p>
        </div>
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-xl">
          <i class="fas fa-users text-3xl"></i>
        </div>
      </div>
      
      <div class="mt-6">
        <div class="text-4xl font-bold text-gray-800"><?= $totalBeneficiarios ?></div>
        <div class="mt-2 flex items-center text-sm text-green-600">
          <i class="fas fa-arrow-up mr-1"></i>
          <span>Activos</span>
        </div>
      </div>
      
      <div class="mt-6 pt-4 border-t border-gray-100">
        <a href="?pagina=beneficiarios" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
          Ver lista completa
          <i class="fas fa-arrow-right ml-2"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Sección de Acciones Rápidas -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Tarjeta 1 -->
    <div class="bg-white rounded-2xl p-6 shadow-lg hover-lift transition-all duration-300">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Nueva Solicitud</h3>
        <div class="bg-green-100 p-3 rounded-lg">
          <i class="fas fa-plus-circle text-green-600 text-xl"></i>
        </div>
      </div>
      <p class="text-gray-600 text-sm mb-4">Crea una nueva solicitud de pago o beneficio.</p>
      <a href="?pagina=usuario-solicitud" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
        Crear solicitud <i class="fas fa-arrow-right ml-1"></i>
      </a>
    </div>
    
    <!-- Tarjeta 2 -->
    <div class="bg-white rounded-2xl p-6 shadow-lg hover-lift transition-all duration-300">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Ver Pagos</h3>
        <div class="bg-blue-100 p-3 rounded-lg">
          <i class="fas fa-file-invoice-dollar text-blue-600 text-xl"></i>
        </div>
      </div>
      <p class="text-gray-600 text-sm mb-4">Consulta el estado de tus pagos realizados.</p>
      <a href="?pagina=pagos" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
        Ver pagos <i class="fas fa-arrow-right ml-1"></i>
      </a>
    </div>
    
    <!-- Tarjeta 3 -->
    <div class="bg-white rounded-2xl p-6 shadow-lg hover-lift transition-all duration-300">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Comprobantes</h3>
        <div class="bg-purple-100 p-3 rounded-lg">
          <i class="fas fa-receipt text-purple-600 text-xl"></i>
        </div>
      </div>
      <p class="text-gray-600 text-sm mb-4">Revisa y descarga tus comprobantes de pago.</p>
      <a href="?pagina=comprobantes" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
        Ver comprobantes <i class="fas fa-arrow-right ml-1"></i>
      </a>
    </div>
  </div>
  
  <!-- Últimos Beneficiarios -->
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Últimos Beneficiarios</h2>
        <a href="?pagina=beneficiarios" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
          Ver todos <i class="fas fa-arrow-right ml-1"></i>
        </a>
      </div>
      
      <?php if (!empty($beneficiarios)): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DNI</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programa</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php 
              // Mostrar solo los primeros 5 beneficiarios
              $ultimosBeneficiarios = array_slice($beneficiarios, 0, 5);
              foreach ($ultimosBeneficiarios as $beneficiario): 
                $nombreCompleto = trim(($beneficiario['ap_est'] ?? '') . ' ' . ($beneficiario['am_est'] ?? '') . ' ' . ($beneficiario['nom_est'] ?? ''));
              ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($nombreCompleto) ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($beneficiario['dni_est'] ?? '') ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($beneficiario['programa_nombre'] ?? 'No especificado') ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($beneficiario['ciclo'] ?? 'No especificado') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-8">
          <i class="fas fa-users-slash text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">No hay beneficiarios registrados</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
