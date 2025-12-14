<?php
session_start();

// Validar autenticación y rol
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
    header('Location: ../public/login.html');
    exit;
}

// Validar que el rol sea 'bienestar'
if ($_SESSION['rol'] !== 'bienestar') {
    header('Location: ../errors/403.html');
    exit;
}

// Procesar formularios si se envían (ANTES de cualquier output HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../controller/bienestar-registroController.php';
    $ctrl = new BienestarRegistroController();
    
    $accion = $_POST['accion'] ?? '';
    
    // Procesar formulario de resolución
    if ($accion === 'agregar_resolucion') {
        try {
            $data = [
                'numero_resolucion' => trim($_POST['numero_resolucion'] ?? ''),
                'titulo' => trim($_POST['titulo'] ?? ''),
                'texto_respaldo' => trim($_POST['texto_respaldo'] ?? ''),
                'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
                'fecha_fin' => $_POST['fecha_fin'] ?? null,
            ];
            
            // Validaciones básicas
            if (empty($data['numero_resolucion']) || empty($data['titulo']) || empty($data['fecha_inicio'])) {
                throw new Exception('Los campos obligatorios deben ser completados.');
            }
            
            $result = $ctrl->crearResolucion($data, $_FILES);
            
            if ($result) {
                header('Location: dashboard-bienestar.php?pagina=registro-bienestar-estudiantil&status=resolucion_created');
                exit;
            } else {
                throw new Exception('No se pudo crear la resolución.');
            }
        } catch (Exception $e) {
            $_SESSION['bienestar_errors'] = [$e->getMessage()];
            $_SESSION['bienestar_previous_data'] = $_POST;
            header('Location: dashboard-bienestar.php?pagina=registro-bienestar-estudiantil&status=error');
            exit;
        }
    }
    
    // Procesar formulario de beneficiario
    if ($accion === 'agregar_beneficiario') {
        try {
            $dni = trim($_POST['dni'] ?? '');
            
            // Buscar estudiante por DNI
            $estudiante = $ctrl->buscarEstudiante($dni);
            
            if (!$estudiante || !isset($estudiante['id'])) {
                throw new Exception('No se encontró un estudiante con ese DNI. Por favor, busca primero al estudiante.');
            }
            
            $data = [
                'estudiante_id' => $estudiante['id'],
                'resolucion_id' => $_POST['resolucion_id'] ?? null,
                'porcentaje_descuento' => $_POST['porcentaje_descuento'] ?? null,
                'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
                'fecha_fin' => $_POST['fecha_fin'] ?? null,
                'activo' => 1
            ];
            
            // Validaciones básicas
            if (empty($data['resolucion_id']) || empty($data['porcentaje_descuento'])) {
                throw new Exception('Los campos obligatorios deben ser completados.');
            }
            
            $result = $ctrl->crearBeneficiario($data);
            
            if ($result) {
                header('Location: dashboard-bienestar.php?pagina=registro-bienestar-estudiantil&status=beneficiario_created');
                exit;
            } else {
                throw new Exception('No se pudo crear el beneficiario.');
            }
        } catch (Exception $e) {
            $_SESSION['bienestar_errors'] = [$e->getMessage()];
            $_SESSION['bienestar_previous_data'] = $_POST;
            header('Location: dashboard-bienestar.php?pagina=registro-bienestar-estudiantil&status=error');
            exit;
        }
    }
}

// Página actual
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'registro-bienestar-estudiantil';

// Permitir acceso a reportes-bienestar-estudiantil para admin y bienestar
// (bienestar solo puede ver, no puede hacer acciones)

// Definir título e icono Font Awesome según página
switch($pagina){
    case 'registro-bienestar-estudiantil':
        $titulo = 'NUEVO';
        $icono  = 'fa-plus';
        break;

    case 'reportes-bienestar-estudiantil':
        $titulo = 'REPORTES';
        $icono  = 'fa-chart-bar';
        break;

    case 'solicitud-bienestar-estudiantil':   // ← CORREGIDO (sin .html)
        $titulo = 'SOLICITUDES';
        $icono  = 'fa-file-alt';
        break;

    case 'notificaciones-bienestar':
        $titulo = 'NOTIFICACIONES';
        $icono  = 'fa-bell';
        break;

    default:
        $titulo = 'INICIO';
        $icono  = 'fa-home';
}

// CORREGIDO → priorizar PHP, luego HTML
if (file_exists("includes/bienestar/{$pagina}.php")) {
    $ruta = "includes/bienestar/{$pagina}.php";
} else {
    $ruta = "includes/bienestar/{$pagina}.html";
}

// Función para botón activo
function activo($id, $pagina){
    return $id === $pagina 
        ? 'bg-gradient-to-r from-blue-600 to-blue-400 text-white' 
        : 'text-white/80 hover:bg-gradient-to-r hover:from-blue-500 hover:to-blue-400';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Bienestar</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<style>
@keyframes float {0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.animate-float { animation: float 4s ease-in-out infinite; }
.hover-lift {transition: all 0.3s ease;}
.hover-lift:hover {transform: translateY(-6px) scale(1.02); box-shadow: 0 15px 30px rgba(0,0,0,0.1);}
.section-content {display:none;}
.section-content.active {display:block;}
.header-bg {
    background-image: url('assets/img/img-background.png');
    background-size: cover;
    background-position: center;
    position: relative;
}
.header-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background-color: rgba(0,0,0,0.4);
    border-radius: 1rem;
}
.header-content { position: relative; z-index: 10; }
</style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 font-sans">

<div class="flex">

    <!-- BOTÓN DE TOGGLE -->
    <button id="toggleSidebar" class="fixed top-6 left-6 z-[60] bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-2xl shadow-lg transition-all">
        <i class="fas fa-bars text-xl"></i>
    </button>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-black text-white
        shadow-2xl z-50 rounded-tr-[70px] rounded-br-[70px] transition-transform">

        <!-- LOGO -->
        <div class="flex flex-col items-center pt-10 pb-4">
            <img src="assets/img/logo1.png" alt="Logo" class="w-28 h-28 rounded-full shadow-lg animate-float">
            <div class="mt-3 flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <p class="text-sm text-gray-300">Bienestar</p>
            </div>
        </div>

        <div class="mx-8 mb-6 h-[2px] bg-gradient-to-r from-transparent via-blue-400/40 to-transparent"></div>

        <!-- MENÚ -->
        <nav class="flex flex-col gap-3 px-5">

            <button onclick="window.location='?pagina=registro-bienestar-estudiantil'"
                class="flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold <?= activo('registro-bienestar-estudiantil', $pagina) ?>">
                <i class="fas fa-plus text-xl"></i> NUEVO
            </button>

            <button onclick="window.location='?pagina=reportes-bienestar-estudiantil'"
                class="flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold <?= activo('reportes-bienestar-estudiantil', $pagina) ?>">
                <i class="fas fa-chart-bar text-xl"></i> REPORTES
            </button>

            <button onclick="window.location='?pagina=solicitud-bienestar-estudiantil'"
                class="flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold <?= activo('solicitud-bienestar-estudiantil', $pagina) ?>">
                <i class="fas fa-file-alt text-xl"></i> SOLICITUDES
            </button>

            <button onclick="window.location='?pagina=notificaciones-bienestar'"
                class="flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold <?= activo('notificaciones-bienestar', $pagina) ?>">
                <i class="fas fa-bell text-xl"></i> NOTIFICACIONES
            </button>

        </nav>

        <div class="flex-1"></div>

        <!-- SALIR -->
        <div class="p-6">
            <a href="../public/logout.php">
                <button class="w-full bg-gradient-to-r from-red-500 to-red-600 py-3 rounded-2xl font-semibold text-white">
                    <i class="fas fa-sign-out-alt"></i> SALIR
                </button>
            </a>
        </div>

    </aside>
</div>

<script>
document.getElementById('toggleSidebar').onclick = () => {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
};
</script>

<!-- MAIN CONTENT -->
<main class="ml-72 p-8">

    <header class="p-6 rounded-2xl text-white header-bg mb-8">
        <div class="header-content">
            <h1 class="text-4xl font-bold">
                <i class="fas <?= $icono ?> mr-3"></i><?= strtoupper($titulo) ?>
            </h1>
            <p class="text-white/80">Gestión del módulo <?= strtoupper($titulo) ?></p>
        </div>
    </header>

    <div class="rounded-3xl p-8 shadow-xl section-content active">
        <?php 
        if (file_exists($ruta)) {
            include $ruta;
        } else {
            echo "<p class='text-red-600 font-bold'>Página no encontrada: $ruta</p>";
        }
        ?>
    </div>

</main>

</body>
</html>
