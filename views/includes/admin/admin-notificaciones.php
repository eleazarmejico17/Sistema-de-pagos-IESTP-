<?php
require_once __DIR__ . '/../../../models/NotificacionModel.php';

$notificacionModel = new NotificacionModel();
$notificaciones = $notificacionModel->obtener(null, false, 50); // Todas las notificaciones para admins
$noLeidas = $notificacionModel->contarNoLeidas(null);

// ---------------------------------------------------------------------
// NOTA: Las acciones (marcar_leida, marcar_todas) se procesan en 
// dashboard-admin.php ANTES de cualquier output para evitar 
// errores de "headers already sent"
// ---------------------------------------------------------------------
?>

<div class="max-w-7xl mx-auto space-y-6">
    
    <!-- HEADER MEJORADO -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="p-4 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg">
                <i class="fas fa-bell text-white text-3xl"></i>
            </div>
            <div>
                <h2 class="text-4xl font-bold text-gray-800">Notificaciones del Sistema</h2>
                <p class="text-gray-500 mt-1">Historial completo de actividades y acciones realizadas</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <?php if ($noLeidas > 0): ?>
                <div class="px-4 py-2 bg-red-100 border border-red-200 rounded-lg">
                    <span class="text-red-700 font-semibold">
                        <i class="fas fa-circle text-red-500 text-xs animate-pulse mr-2"></i>
                        <?= $noLeidas ?> sin leer
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($noLeidas > 0): ?>
                <a href="dashboard-admin.php?pagina=admin-notificaciones&marcar_todas=1" 
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all flex items-center gap-2">
                    <i class="fas fa-check-double"></i>
                    <span>Marcar todas como leídas</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($notificaciones)): ?>
        <!-- ESTADO VACÍO -->
        <div class="bg-white rounded-2xl shadow-xl p-12 text-center border border-gray-200">
            <div class="inline-block p-6 bg-gray-100 rounded-full mb-6">
                <i class="fas fa-bell-slash text-6xl text-gray-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-700 mb-3">No hay notificaciones</h3>
            <p class="text-gray-500 text-lg">Aún no se han registrado acciones en el sistema.</p>
            <p class="text-gray-400 text-sm mt-2">Las notificaciones aparecerán aquí cuando se realicen cambios en el sistema.</p>
        </div>
    <?php else: ?>
        <!-- LISTA DE NOTIFICACIONES -->
        <div class="space-y-4">
            <?php foreach ($notificaciones as $notif): 
                $iconos = [
                    'success' => 'fa-check-circle',
                    'info' => 'fa-info-circle',
                    'warning' => 'fa-exclamation-triangle',
                    'error' => 'fa-times-circle',
                    'danger' => 'fa-times-circle'
                ];
                $colores = [
                    'success' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700', 'icon' => 'text-emerald-500'],
                    'info' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-700', 'icon' => 'text-blue-500'],
                    'warning' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-700', 'icon' => 'text-yellow-500'],
                    'error' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-700', 'icon' => 'text-red-500'],
                    'danger' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-700', 'icon' => 'text-red-500']
                ];
                
                $tipo = $notif['tipo'] ?? 'info';
                $color = $colores[$tipo] ?? $colores['info'];
                $icono = $iconos[$tipo] ?? $iconos['info'];
                $leida = (int)$notif['leida'] === 0;
                
                // Formatear fecha
                $fecha = new DateTime($notif['creado_en']);
                $ahora = new DateTime();
                $diferencia = $ahora->diff($fecha);
                
                if ($diferencia->days === 0) {
                    if ($diferencia->h === 0) {
                        $fechaTexto = $diferencia->i === 0 ? 'Hace un momento' : "Hace {$diferencia->i} minuto" . ($diferencia->i > 1 ? 's' : '');
                    } else {
                        $fechaTexto = "Hace {$diferencia->h} hora" . ($diferencia->h > 1 ? 's' : '');
                    }
                } else if ($diferencia->days === 1) {
                    $fechaTexto = 'Ayer';
                } else if ($diferencia->days < 7) {
                    $fechaTexto = "Hace {$diferencia->days} día" . ($diferencia->days > 1 ? 's' : '');
                } else {
                    $fechaTexto = $fecha->format('d/m/Y H:i');
                }
            ?>
                <div class="bg-white rounded-xl shadow-md border-l-4 <?= $color['border'] ?> hover:shadow-lg transition-all duration-300 <?= $leida ? 'opacity-75' : 'border-l-4' ?>">
                    <div class="p-5 flex items-start gap-4">
                        <!-- ICONO -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full <?= $color['bg'] ?> flex items-center justify-center">
                                <i class="fas <?= $icono ?> <?= $color['icon'] ?> text-xl"></i>
                            </div>
                        </div>
                        
                        <!-- CONTENIDO -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-bold text-gray-800 text-lg">
                                            <?= htmlspecialchars($notif['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                        </h3>
                                        <?php if ($leida): ?>
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Nuevo</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-gray-600 mb-2">
                                        <?= $notif['mensaje'] ?>
                                    </div>
                                    
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <?php if ($notif['modulo']): ?>
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-folder"></i>
                                                <?= htmlspecialchars(ucfirst($notif['modulo']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($notif['usuario_nombre']): ?>
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-user"></i>
                                                <?= htmlspecialchars($notif['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="flex items-center gap-1">
                                            <i class="far fa-clock"></i>
                                            <?= $fechaTexto ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- ACCIONES -->
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    <?php if ($leida): ?>
                                        <a href="dashboard-admin.php?pagina=admin-notificaciones&marcar_leida=<?= $notif['id'] ?>" 
                                           class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-all flex items-center gap-2"
                                           title="Marcar como leída">
                                            <i class="fas fa-check text-sm"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- PAGINACIÓN FUTURA (si se implementa) -->
        <div class="text-center text-gray-500 text-sm mt-8">
            Mostrando <?= count($notificaciones) ?> notificación<?= count($notificaciones) > 1 ? 'es' : '' ?>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.space-y-4 > div {
    animation: fadeIn 0.3s ease-out;
}
</style>
