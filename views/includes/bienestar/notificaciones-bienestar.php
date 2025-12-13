<?php
require_once __DIR__ . "/../../../config/conexion.php";

// La sesión ya está iniciada desde dashboard-usuario.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$notificaciones = [];
$notificacionesPagos = [];
$errorConsulta = null;

try {
    $db = Conexion::getInstance()->getConnection();
    
    // Obtener ID del empleado actual (bienestar)
    $usuarioSesion = $_SESSION['usuario'] ?? '';
    $empleadoId = null;
    
    if (!empty($usuarioSesion)) {
        // Primero obtener el ID desde tabla usuarios
        $stmtUser = $db->prepare("SELECT estuEmpleado FROM usuarios WHERE usuario = :usuario LIMIT 1");
        $stmtUser->execute([':usuario' => $usuarioSesion]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        // El campo estuEmpleado contiene el ID del empleado
        $empleadoId = $user['estuEmpleado'] ?? null;
    }
    
    // DEBUG: Verificar ID del empleado
    error_log("=== DEBUG USUARIO BIENESTAR ===");
    error_log("Usuario sesión: " . $usuarioSesion);
    error_log("ID empleado: " . $empleadoId);
    
    // Obtener notificaciones de la nueva tabla notificaciones_bienestar
    $sqlNotificaciones = "SELECT 
                nb.id,
                nb.tipo,
                nb.titulo,
                nb.mensaje,
                nb.id_resolucion,
                nb.id_empleado_creador,
                nb.id_empleado_revisor,
                nb.estado_notificacion,
                nb.creado_en AS fecha,
                nb.leido_en,
                r.numero_resolucion,
                r.titulo AS resolucion_titulo,
                r.monto_descuento,
                emp_creador.apnom_emp AS nombre_creador,
                emp_revisor.apnom_emp AS nombre_revisor,
                CASE 
                    WHEN nb.tipo = 'aprobacion' THEN 'aprobado'
                    WHEN nb.tipo = 'rechazo' THEN 'rechazado'
                    ELSE nb.tipo
                END AS estado,
                nb.mensaje AS motivo_respuesta,
                nb.creado_en AS fecha_registro,
                CASE 
                    WHEN nb.estado_notificacion = 'no_leida' THEN 0
                    ELSE 1
                END AS notificacion_enviada,
                'resolucion' AS tipo_notificacion
            FROM notificaciones_bienestar nb
            LEFT JOIN resoluciones r ON r.id = nb.id_resolucion
            LEFT JOIN empleado emp_creador ON emp_creador.id = nb.id_empleado_creador
            LEFT JOIN empleado emp_revisor ON emp_revisor.id = nb.id_empleado_revisor
            WHERE nb.id_empleado_creador = :empleado_id
            ORDER BY nb.creado_en DESC
            LIMIT 50";
    
    $stmt = $db->prepare($sqlNotificaciones);
    $stmt->execute([':empleado_id' => $empleadoId]);
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Verificar resultados
    error_log("=== DEBUG NOTIFICACIONES BIENESTAR ===");
    error_log("Total notificaciones encontradas: " . count($notificaciones));
    error_log("SQL ejecutado: " . $sqlNotificaciones);
    if (!empty($notificaciones)) {
        error_log("Primera notificación: " . json_encode($notificaciones[0]));
    }
    
    // Obtener notificaciones de pagos desde notificaciones_sistema
    try {
        // Verificar si existe la tabla notificaciones_sistema
        $db->exec("
            CREATE TABLE IF NOT EXISTS notificaciones_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT,
                usuario_nombre VARCHAR(255),
                tipo VARCHAR(50) NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                mensaje TEXT NOT NULL,
                modulo VARCHAR(100),
                accion VARCHAR(50),
                referencia_id INT,
                leida TINYINT(1) DEFAULT 0,
                creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_usuario (usuario_id),
                INDEX idx_leida (leida),
                INDEX idx_creado (creado_en)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Obtener notificaciones de pagos
        $sqlPagos = "SELECT 
                    id,
                    usuario_nombre AS nombre,
                    titulo,
                    mensaje,
                    tipo,
                    modulo,
                    accion,
                    referencia_id,
                    creado_en AS fecha_respuesta,
                    'pago' AS tipo_notificacion,
                    'Pago Realizado' AS tipo_solicitud,
                    'Aprobado' AS estado
                FROM notificaciones_sistema
                WHERE modulo = 'pagos'
                AND (usuario_id = :usuario_id OR usuario_id IS NULL)
                ORDER BY creado_en DESC
                LIMIT 50";
        
        $stmtPagos = $db->prepare($sqlPagos);
        $stmtPagos->execute([':usuario_id' => $empleadoId]);
        $notificacionesPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo notificaciones de pagos: " . $e->getMessage());
    }
    
    // Las notificaciones de resoluciones ya están ordenadas y limitadas en la consulta
    // No necesitamos combinar ni reordenar
    
} catch (PDOException $e) {
    $errorConsulta = "Error de base de datos: " . $e->getMessage();
    error_log('Error PDO en notificaciones: ' . $e->getMessage());
} catch (Exception $e) {
    $errorConsulta = "Error: " . $e->getMessage();
    error_log('Error en notificaciones: ' . $e->getMessage());
}
?>

<section class="w-full space-y-4">
  <h1 class="text-2xl font-bold mb-6 text-gray-800">Notificaciones</h1>

<?php if ($errorConsulta): ?>
  <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
    <p class="font-semibold">Error al cargar notificaciones:</p>
    <p><?= htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
<?php elseif (empty($notificaciones)): ?>

  <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 text-center">
    <div class="text-gray-400 text-6xl mb-4">🔕</div>
    <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay notificaciones</h3>
    <p class="text-gray-500">No tienes notificaciones pendientes.</p>
  </div>

<?php else: ?>

<?php foreach ($notificaciones as $notif): 
  $tipoNotif = $notif['tipo_notificacion'] ?? 'resolucion';
  $esPago = ($tipoNotif === 'pago');
  $esResolucion = ($tipoNotif === 'resolucion');
?>
<div class="mb-4">

<?php if ($notif['estado'] == 'Rechazado'): ?>

<!-- TARJETA RECHAZADA -->
<div onclick="toggleNotificacion(this)"
     class="cursor-pointer bg-white rounded-2xl shadow-lg border border-red-200 p-6 relative hover:shadow-xl transition">

  <div class="absolute right-5 top-5 text-red-500 text-xl font-bold">❌</div>

  <!-- RESUMEN -->
  <div class="flex justify-between items-center">
    <div>
      <p class="font-semibold text-gray-800"><?= htmlspecialchars($notif['nombre_creador'] ?? 'OF. Bienestar Estudiantil', ENT_QUOTES, 'UTF-8') ?></p>
      <span class="inline-block text-xs mt-2 bg-red-100 text-red-700 px-3 py-1 rounded-full">
        Resolución Rechazada
      </span>
    </div>
  </div>

  <!-- DETALLE -->
  <div class="detalle-notificacion hidden mt-4">

    <p class="text-gray-700 text-sm leading-relaxed mt-3">
      Hola <span class="font-medium"><?= htmlspecialchars($notif['nombre_creador'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></span>,
      su resolución ha sido <span class="font-bold text-red-600">rechazada</span>
      <?php if (!empty($notif['motivo_respuesta'])): ?>
        debido a que <?= htmlspecialchars($notif['motivo_respuesta'], ENT_QUOTES, 'UTF-8') ?>.
      <?php else: ?>
        por motivos administrativos.
      <?php endif; ?>
    </p>

    <div class="mt-4">
      <p class="font-semibold text-gray-700 text-sm">Resolución</p>
      <span class="inline-block bg-red-50 text-red-700 text-xs font-semibold px-3 py-1 rounded-full mt-1">
        N°<?= htmlspecialchars($notif['numero_resolucion'] ?? $notif['id'], ENT_QUOTES, 'UTF-8') ?>
      </span>
      <?php if (!empty($notif['resolucion_titulo'])): ?>
        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($notif['resolucion_titulo'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>

<?php if ($notif['archivos']): ?>
  <div class="mt-4">
    <p class="font-semibold text-gray-700 text-sm mb-2">Evidencias</p>
    <div class="flex flex-wrap gap-3">
      <?php 
      $archivos = explode(",", $notif['archivos']);
      foreach($archivos as $archivo): 
        $archivo = trim($archivo);
        if ($archivo === '') continue;
        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        $ruta = "../uploads/solicitudes/" . rawurlencode($archivo);
        $archivoSeguro = htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8');
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
          <div onclick="event.stopPropagation(); abrirImagen('<?= $ruta ?>')"
               class="w-24 h-20 bg-gray-100 rounded-xl overflow-hidden hover:shadow cursor-pointer">
            <img src="<?= $ruta ?>" class="w-full h-full object-cover">
          </div>
        <?php else: ?>
          <div class="w-24 h-20 flex items-center justify-center border rounded-xl text-xs text-gray-500">
            <?= $archivoSeguro ?>
          </div>
        <?php endif; endforeach; ?>
    </div>
  </div>
<?php endif; ?>

</div> <!-- DETALLE -->
</div> <!-- TARJETA -->

<?php elseif ($esPago): ?>

<!-- TARJETA PAGO REALIZADO -->
<div onclick="toggleNotificacion(this)"
     class="cursor-pointer bg-white rounded-2xl shadow-lg border border-green-200 p-6 hover:shadow-xl transition">

  <div class="flex justify-between items-center">
    <div>
      <p class="font-semibold text-gray-800"><?= htmlspecialchars($notif['nombre'] ?? $notif['usuario_nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></p>
      <span class="inline-block mt-2 bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
        Pago Realizado
      </span>
    </div>
    <span class="text-green-500 text-xl font-bold">💰</span>
  </div>

  <div class="detalle-notificacion hidden mt-4">
    <p class="text-gray-600 text-sm" style="white-space: pre-line;">
      <?= htmlspecialchars($notif['titulo'] ?? 'Pago procesado', ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php if (!empty($notif['mensaje'])): ?>
    <div class="mt-3 p-3 bg-green-50 rounded-lg">
      <div class="text-sm text-gray-700"><?= $notif['mensaje'] ?></div>
    </div>
    <?php endif; ?>
    <div class="mt-3">
      <span class="inline-block bg-green-50 text-green-700 text-xs px-3 py-1 rounded-full">
        <?= $notif['fecha_respuesta'] ? date('d/m/Y H:i', strtotime($notif['fecha_respuesta'])) : 'Hoy' ?>
      </span>
    </div>
  </div>

</div>

<?php else: ?>

<!-- TARJETA APROBADA -->
<div onclick="toggleNotificacion(this)"
     class="cursor-pointer bg-white rounded-2xl shadow-lg border border-blue-200 p-6 hover:shadow-xl transition">

  <div class="flex justify-between items-center">
    <div>
      <p class="font-semibold text-gray-800"><?= htmlspecialchars($notif['nombre_creador'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></p>
      <span class="inline-block mt-2 bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">
        Resolución Aprobada
      </span>
    </div>
    <span class="text-blue-500 text-xl font-bold">✔️</span>
  </div>

  <div class="detalle-notificacion hidden mt-4">
    <p class="text-gray-700 text-sm leading-relaxed">
      Hola <span class="font-medium"><?= htmlspecialchars($notif['nombre_creador'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></span>,
      su resolución ha sido <span class="font-bold text-blue-600">aprobada</span> correctamente.
    </p>

    <div class="mt-4">
      <p class="font-semibold text-gray-700 text-sm">Resolución</p>
      <span class="inline-block bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full mt-1">
        N°<?= htmlspecialchars($notif['numero_resolucion'] ?? $notif['id'], ENT_QUOTES, 'UTF-8') ?>
      </span>
      <?php if (!empty($notif['resolucion_titulo'])): ?>
        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($notif['resolucion_titulo'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php if (!empty($notif['monto_descuento']) && $notif['monto_descuento'] > 0): ?>
        <p class="text-sm text-green-600 mt-1 font-semibold">Descuento: S/ <?= number_format($notif['monto_descuento'], 2) ?></p>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php endif; ?>

</div>
<?php endforeach; ?>
<?php endif; ?>
</section>

<!-- MODAL DE IMAGEN -->
<div id="modalImagen" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl max-w-4xl overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b">
      <h3 class="font-semibold">Vista previa</h3>
      <button onclick="cerrarModalImagen()">✖</button>
    </div>
    <div class="p-4">
      <img id="imagenModal" class="max-w-full max-h-96 rounded">
    </div>
  </div>
</div>

<script>
function toggleNotificacion(card){
  const detalle = card.querySelector('.detalle-notificacion');

  if (!detalle) return;

  const abierta = !detalle.classList.contains('hidden');

  document.querySelectorAll('.detalle-notificacion').forEach(d => d.classList.add('hidden'));

  if (!abierta) detalle.classList.remove('hidden');
}

function abrirImagen(src){
  document.getElementById('imagenModal').src = src;
  document.getElementById('modalImagen').classList.remove('hidden');
}

function cerrarModalImagen(){
  document.getElementById('modalImagen').classList.add('hidden');
}
</script>
