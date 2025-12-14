<?php

require_once __DIR__ . '/../../../config/conexion.php';
$pagina = $_GET['pagina'] ?? 'panel-admin';

switch ($pagina) {
    case 'panel-admin':
        $titulo = 'INICIO';
        $icono = 'fa-home';
        break;
    case 'admin-notificaciones':
        $titulo = 'NOTIFICACIONES';
        $icono = 'fa-chart-bar';
        break;
    default:
        $titulo = 'INICIO';
        $icono = 'fa-home';
}


function activopanel($id, $pagina)
{
    return $id === $pagina ? 'bg-gradient-to-r from-blue-600 to-blue-400 text-white' : 'text-white/80 hover:bg-gradient-to-r hover:from-blue-500 hover:to-blue-400';
}

$modulo = $_GET['modulo'] ?? null;
$archivos_validos = ['admin-usuarios', 'admin-bienestar', 'admin-direccion'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel Administrativo</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .card-animate { animation: fadeInUp 0.6s ease forwards; }
    .hover-lift:hover { transform: translateY(-8px) scale(1.02); }
    .shimmer {
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
      background-size: 1000px 100%;
      animation: shimmer 2s infinite;
    }
  </style>
</head>
<body class="min-h-screen bg-gray-100 font-sans">

<main class="p-8">
  <header class="mb-8">
  </header>


  <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <form method="GET">
      <input type="hidden" name="modulo" value="admin-bienestar">
      <button type="submit" class="card-animate hover-lift bg-gradient-to-br from-lime-400 to-emerald-500 text-white p-6 rounded-2xl shadow-lg w-full text-left">
        <i class="fas fa-heart text-3xl mb-2"></i>
        <h3 class="text-xl font-bold">EMPLEADOS</h3>
        <p class="text-sm opacity-90">Gestión de Empleados</p>
      </button>
    </form>


    <form method="GET">
      <input type="hidden" name="modulo" value="admin-usuarios">
      <button type="submit" class="card-animate hover-lift bg-gradient-to-br from-blue-700 to-indigo-900 text-white p-6 rounded-2xl shadow-lg w-full text-left">
        <i class="fas fa-users-cog text-3xl mb-2"></i>
        <h3 class="text-xl font-bold">ESTUDIANTES</h3>
        <p class="text-sm opacity-90">Gestión general de ESTUDIANTES</p>
      </button>
    </form>
  </section>


  <div class="bg-white rounded-2xl shadow p-6">
    <?php
    if ($modulo && in_array($modulo, $archivos_validos)) {
        include __DIR__ . "/$modulo.php";
    } else {
        echo '<p class="text-gray-600 text-center">Selecciona un módulo para comenzar.</p>';
    }
    ?>
  </div>
</main>

</body>

