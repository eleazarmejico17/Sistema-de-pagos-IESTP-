<?php
$rolesPermitidos = ['direccion'];
require_once __DIR__ . '/../public/aut.php';
// Página actual
$pagina = $_GET['pagina'] ?? 'direccion-resolucion';

// Definir menú con iconos de Font Awesome
$menu = [
    'direccion-resolucion' => ['icon' => 'fa-bell', 'texto' => 'NOTIFICACIONES'],
    'reportes-direccion'   => ['icon' => 'fa-chart-bar', 'texto' => 'REPORTES'],
];

$titulo = $menu[$pagina]['texto'] ?? 'INICIO';
$icono  = $menu[$pagina]['icon'] ?? 'fa-home';

// Ruta del contenido
$ruta = "includes/direccion/{$pagina}.php";
if (!file_exists($ruta)) {
    $ruta = "includes/direccion/{$pagina}.html";
}

// Función para botón activo
function activo($id, $pagina){
    return $id === $pagina 
        ? 'bg-gradient-to-r from-blue-600 to-blue-400 text-white shadow-lg' 
        : 'text-white/80 hover:bg-gradient-to-r hover:from-blue-500 hover:to-blue-400';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Dirección</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
/* Animaciones */
@keyframes float {0%,100%{transform:translateY(0);}50%{transform:translateY(-6px);}}
.animate-float {animation: float 4s ease-in-out infinite;}

.hover-lift {transition: all 0.3s ease;}
.hover-lift:hover {transform: translateY(-4px) scale(1.02); box-shadow: 0 12px 24px rgba(0,0,0,0.1);}

.glass-effect {
    backdrop-filter: blur(12px);
    background: rgba(255,255,255,0.85);
}

.header-bg {
    background-image: url('assets/img/img-background.png'); 
    background-size: cover; 
    background-position: center; 
    background-repeat: no-repeat; 
    position: relative; 
    border-radius: 1rem;
}
.header-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background-color: rgba(0,0,0,0.4);
    border-radius: 1rem;
}
.header-content { position: relative; z-index: 10; }

.btn-action {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.btn-action::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}
.btn-action:hover::before {
    width: 300px;
    height: 300px;
}
</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 font-sans">

<div class="flex">
  <!-- BOTÓN TOGGLE -->
  <button id="toggleSidebar" class="fixed top-6 left-6 z-[60] bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-2xl shadow-lg transition-all duration-500">
    <i class="fas fa-bars text-xl"></i>
  </button>

  <!-- SIDEBAR -->
  <aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-black text-white shadow-2xl z-50 rounded-tr-[70px] rounded-br-[70px] overflow-hidden backdrop-blur-lg transition-transform duration-700 ease-out translate-x-0">
    
    <!-- LOGO -->
<div class="flex flex-col items-center justify-center pt-10 pb-4 relative">
  <img src="assets/img/logo1.png" alt="Logo" 
       class="w-28 h-28 rounded-full shadow-lg hover:scale-110 hover:rotate-6 transition-transform duration-700 ease-out animate-float">
  <div class="mt-3 flex items-center gap-2">
    <span class="relative flex h-3 w-3">
      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
      <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
    </span>
    <p class="text-sm text-gray-300">Direccion</p>
  </div>
</div>


    <div class="mx-8 mb-6 h-[2px] bg-gradient-to-r from-transparent via-blue-400/40 to-transparent"></div>

    <!-- MENÚ -->
    <nav class="flex flex-col gap-3 px-5">
      <?php foreach($menu as $key => $item): ?>
        <button onclick="window.location='?pagina=<?= $key ?>'" class="relative flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold transition-all duration-500 hover:translate-x-2 overflow-hidden group <?= activo($key, $pagina) ?>">
          <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-400 opacity-0 group-hover:opacity-100 blur-xl transition-all duration-700"></span>
          <span class="absolute inset-0 bg-white/5 group-hover:bg-white/10 rounded-2xl transition-all duration-700"></span>
          <i class="fas <?= $item['icon'] ?> text-xl relative z-10"></i>
          <span class="relative z-10"><?= $item['texto'] ?></span>
        </button>
      <?php endforeach; ?>
    </nav>

    <div class="flex-1"></div>

    <!-- BOTÓN SALIR -->
    <div class="p-6">
      <a href="../public/logout.php">
        <button 
          class="relative w-full flex items-center justify-center gap-3 px-4 py-3 font-semibold rounded-2xl bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-lg hover:shadow-[0_0_25px_rgba(239,68,68,0.5)] transition-all duration-500 overflow-hidden group">
            <i class="fas fa-sign-out-alt text-xl group-hover:-translate-x-1 transition-transform duration-300"></i>
            <span class="tracking-wide">SALIR</span>
            <span class="absolute top-0 left-0 w-full h-full bg-white/10 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 ease-out"></span>
        </button>
      </a>
    </div>
  </aside>
</div>

<script>
const sidebar = document.getElementById('sidebar');
document.getElementById('toggleSidebar').addEventListener('click', () => {
  sidebar.classList.toggle('-translate-x-full');
});
</script>

<main class="ml-72 p-8 flex-1">
  <header class="flex justify-between items-center mb-8 p-6 rounded-2xl text-white header-bg">
    <div class="header-content">
      <h1 class="text-4xl font-bold mb-2"><i class="fas <?= $icono ?> mr-3"></i><?= strtoupper($titulo) ?></h1>
      <p class="text-white/80">Gestión del módulo <?= strtoupper($titulo) ?></p>
    </div>
  </header>

  <div class="glass-effect rounded-3xl p-8 shadow-xl hover-lift section-content active">
    <?php
      if(file_exists($ruta)){
        include $ruta;
      } else {
        echo "<p class='text-red-600 font-semibold'>Página no encontrada</p>";
      }
    ?>
  </div>
</main>

</body>
</html>
