<?php
session_start();

// Validar autenticación y rol
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
    header('Location: ../public/login.html');
    exit;
}

// Validar que el rol sea 'admin'
if ($_SESSION['rol'] !== 'admin') {
    header('Location: ../errors/403.html');
    exit;
}

// Procesar acciones ANTES de cualquier output
// 1. Procesar agregar/editar usuario de la tabla usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && !isset($_POST['accion'])) {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../controller/NotificacionHelper.php';
    
    try {
        $pdo = Conexion::getInstance()->getConnection();
        $idUsuario = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : null;
        $usuario = trim($_POST['usuario']);
        $password = trim($_POST['password'] ?? '');
        $tipo = (int)$_POST['tipo'];
        $estuempleado = !empty($_POST['estuempleado']) ? (int)$_POST['estuempleado'] : null;
        $token = !empty($_POST['token']) ? trim($_POST['token']) : null;

        // Validaciones
        if (empty($usuario) || empty($tipo)) {
            throw new Exception("Los campos obligatorios deben ser completados.");
        }

        if (!in_array($tipo, [1, 2, 3])) {
            throw new Exception("Debe seleccionar un tipo de usuario válido");
        }

        // Si es edición
        if ($idUsuario) {
            // Verificar que el usuario existe
            $stmt = $pdo->prepare("SELECT id, usuario FROM usuarios WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $idUsuario]);
            $usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuarioActual) {
                throw new Exception("Usuario no encontrado");
            }
            
            // Verificar si el nombre de usuario ya está en uso por otro usuario
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND id != :id");
            $stmt->execute([':usuario' => $usuario, ':id' => $idUsuario]);
            if ($stmt->fetch()) {
                throw new Exception("El nombre de usuario ya está en uso por otro usuario");
            }
            
            // Construir query de actualización
            $sql = "UPDATE usuarios SET usuario = :usuario, tipo = :tipo, estuempleado = :estuempleado, token = :token";
            $params = [
                ':id' => $idUsuario,
                ':usuario' => $usuario,
                ':tipo' => $tipo,
                ':estuempleado' => $estuempleado,
                ':token' => $token
            ];
            
            // Si se proporcionó una nueva contraseña, actualizarla
            if (!empty($password)) {
                $sql .= ", password = :password";
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                NotificacionHelper::crear('editar', 'usuario', [
                    'nombre' => $usuario,
                    'id' => $idUsuario
                ]);
                header("Location: dashboard-admin.php?pagina=admin-agregar-usuario&status=usuario_actualizado");
                exit;
            } else {
                throw new Exception('No se pudo actualizar el usuario.');
            }
        } else {
            // Es creación nueva
            if (empty($password)) {
                throw new Exception("La contraseña es requerida para nuevos usuarios.");
            }
            
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
            $stmt->execute([':usuario' => $usuario]);
            if ($stmt->fetch()) {
                throw new Exception("El nombre de usuario ya está en uso");
            }

            // Hashear la contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar usuario
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (usuario, password, tipo, estuempleado, token) 
                VALUES (:usuario, :password, :tipo, :estuempleado, :token)
            ");

            if ($stmt->execute([
                ':usuario' => $usuario,
                ':password' => $passwordHash,
                ':tipo' => $tipo,
                ':estuempleado' => $estuempleado,
                ':token' => $token
            ])) {
                // Crear notificación
                $tiposUsuario = [
                    1 => 'Empleado',
                    2 => 'Estudiante',
                    3 => 'Empresa'
                ];
                
                NotificacionHelper::crear('crear', 'usuario', [
                    'nombre' => $usuario,
                    'tipo' => $tiposUsuario[$tipo] ?? 'Usuario'
                ]);
                
                // Redirigir al formulario con mensaje de éxito
                header("Location: dashboard-admin.php?pagina=admin-agregar-usuario&status=usuario_created");
                exit;
            } else {
                throw new Exception('No se pudo registrar el usuario.');
            }
        }
    } catch (Exception $e) {
        $_SESSION['admin_errors'] = [$e->getMessage()];
        $_SESSION['admin_previous_data'] = $_POST;
        header("Location: dashboard-admin.php?pagina=admin-agregar-usuario&status=error");
        exit;
    }
}

// 2. Procesar actualizar estudiante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    require_once __DIR__ . '/../controller/admin-usuariosController.php';
    require_once __DIR__ . '/../controller/NotificacionHelper.php';
    
    $ctrl = new EstudiantesController();
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        $data = [
            'dni_est' => trim($_POST['dni_est'] ?? ''),
            'ap_est' => trim($_POST['ap_est'] ?? ''),
            'am_est' => trim($_POST['am_est'] ?? ''),
            'nom_est' => trim($_POST['nom_est'] ?? ''),
            'sex_est' => trim($_POST['sex_est'] ?? ''),
            'cel_est' => trim($_POST['cel_est'] ?? ''),
            'dir_est' => trim($_POST['dir_est'] ?? ''),
            'mailp_est' => trim($_POST['mailp_est'] ?? ''),
            'maili_est' => trim($_POST['maili_est'] ?? ''),
            'fecnac_est' => !empty($_POST['fecnac_est']) ? $_POST['fecnac_est'] : null,
            'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1
        ];

        if ($ctrl->actualizar($id, $data)) {
            NotificacionHelper::crear('editar', 'usuario', [
                'nombre' => $data['nom_est'] . ' ' . $data['ap_est'],
                'tipo' => 'Estudiante'
            ]);
            header("Location: dashboard-admin.php?pagina=usuarios&status=updated");
            exit;
        }
    } catch (Exception $e) {
        // Error será manejado en la vista
    }
}

// Procesar eliminación de estudiante
if (isset($_GET['delete']) && isset($_GET['pagina']) && $_GET['pagina'] === 'usuarios') {
    require_once __DIR__ . '/../controller/admin-usuariosController.php';
    require_once __DIR__ . '/../controller/NotificacionHelper.php';
    
    $ctrl = new EstudiantesController();
    $deleteId = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);
    
    if ($deleteId) {
        $estudianteEliminar = $ctrl->obtener($deleteId);
        
        if ($ctrl->eliminar($deleteId)) {
            if ($estudianteEliminar) {
                NotificacionHelper::crear('eliminar', 'usuario', [
                    'nombre' => ($estudianteEliminar['nom_est'] ?? '') . ' ' . ($estudianteEliminar['ap_est'] ?? ''),
                    'tipo' => 'Estudiante'
                ]);
            }
            header("Location: dashboard-admin.php?pagina=usuarios&status=deleted");
            exit;
        }
    }
}

// Procesar acciones de admin-tipo-pago ANTES de cualquier output
if (isset($_GET['pagina']) && $_GET['pagina'] === 'admin-tipo-pago') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../controller/NotificacionHelper.php';
    $pdo = Conexion::getInstance()->getConnection();

    // CREAR
    if (isset($_POST['accion']) && $_POST['accion'] === "crear") {
        try {
            $uit = isset($_POST['uit']) ? (float)$_POST['uit'] : 0.00;

            $stmt = $pdo->prepare("INSERT INTO tipo_pago (nombre, descripcion, uit) VALUES (:nombre, :descripcion, :uit)");
            $stmt->execute([
                ":nombre"      => $_POST['nombre'],
                ":descripcion" => $_POST['descripcion'],
                ":uit"         => $uit
            ]);

            // Crear notificación
            NotificacionHelper::crear('crear', 'tipo_pago', [
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? '',
                'uit' => $uit
            ]);

            header("Location: dashboard-admin.php?pagina=admin-tipo-pago&msg=creado");
            exit;
        } catch (Exception $e) {
            header("Location: dashboard-admin.php?pagina=admin-tipo-pago&msg=error");
            exit;
        }
    }

    // EDITAR
    if (isset($_POST['accion']) && $_POST['accion'] === "editar") {
        try {
            $uit = isset($_POST['uit']) ? (float)$_POST['uit'] : 0.00;

            $stmt = $pdo->prepare("UPDATE tipo_pago SET nombre=:nombre, descripcion=:descripcion, uit=:uit WHERE id=:id");
            $stmt->execute([
                ":id"          => $_POST['id'],
                ":nombre"      => $_POST['nombre'],
                ":descripcion" => $_POST['descripcion'],
                ":uit"         => $uit
            ]);

            // Crear notificación
            NotificacionHelper::crear('editar', 'tipo_pago', [
                'id' => $_POST['id'],
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? '',
                'uit' => $uit
            ]);

            header("Location: dashboard-admin.php?pagina=admin-tipo-pago&msg=actualizado");
            exit;
        } catch (Exception $e) {
            header("Location: dashboard-admin.php?pagina=admin-tipo-pago&msg=error");
            exit;
        }
    }

    // ELIMINAR
    if (isset($_GET['eliminar'])) {
        try {
            // Validar que el ID sea un número válido
            $id = filter_var($_GET['eliminar'], FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception("ID inválido");
            }

            // Verificar si el tipo de pago existe
            $stmtGet = $pdo->prepare("SELECT id, nombre, descripcion FROM tipo_pago WHERE id = :id LIMIT 1");
            $stmtGet->execute([":id" => $id]);
            $tipoPago = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$tipoPago) {
                throw new Exception("El tipo de pago no existe");
            }

            // Verificar si hay referencias en otras tablas
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM pagos WHERE tipo_pago = :id");
            $stmtCheck->execute([":id" => $id]);
            $referencias = $stmtCheck->fetch(PDO::FETCH_ASSOC)['count'];

            if ($referencias > 0) {
                throw new Exception("No se puede eliminar este tipo de pago porque está siendo usado en {$referencias} pago(s)");
            }

            // Eliminar
            $stmt = $pdo->prepare("DELETE FROM tipo_pago WHERE id = :id");
            $result = $stmt->execute([":id" => $id]);

            if (!$result) {
                throw new Exception("No se pudo eliminar el registro");
            }

            // Crear notificación
            NotificacionHelper::crear('eliminar', 'tipo_pago', [
                'id' => $id,
                'nombre' => $tipoPago['nombre'],
                'descripcion' => $tipoPago['descripcion'] ?? ''
            ]);

            header("Location: dashboard-admin.php?pagina=admin-tipo-pago&msg=eliminado");
            exit;
        } catch (Exception $e) {
            // Para debugging: mostrar el error real (comentar en producción)
            error_log("Error al eliminar tipo de pago: " . $e->getMessage());
            header("Location: dashboard-admin.php?pagina=admin-tipo-pago&msg=error&detalle=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Procesar acciones de admin-notificaciones ANTES de cualquier output
if (isset($_GET['pagina']) && $_GET['pagina'] === 'admin-notificaciones') {
    require_once __DIR__ . '/../models/NotificacionModel.php';
    $notificacionModel = new NotificacionModel();

    // Marcar como leída si se solicita
    if (isset($_GET['marcar_leida'])) {
        $notificacionModel->marcarLeida($_GET['marcar_leida']);
        header("Location: dashboard-admin.php?pagina=admin-notificaciones");
        exit;
    }

    // Marcar todas como leídas
    if (isset($_GET['marcar_todas'])) {
        $notificacionModel->marcarTodasLeidas();
        header("Location: dashboard-admin.php?pagina=admin-notificaciones");
        exit;
    }
}

// Página actual
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'panel-admin';

// Normalizar nombres de páginas
switch($pagina){
    case 'panel-admin':
        $titulo = 'INICIO';
        $icono = 'fa-plus';
        $archivo = 'panel-admin';
        break;
    case 'admin-notificaciones':
        $titulo = 'NOTIFICACIONES';
        $icono = 'fa-chart-bar';
        $archivo = 'admin-notificaciones';
        break;
    case 'admin-agregar-usuario':
        $titulo = 'USUARIOS';
        $icono = 'fa-chart-bar';
        $archivo = 'admin-agregar-usuario';
        break;
    case 'usuarios':
    case 'admin-usuarios':
        $titulo = 'USUARIOS';
        $icono = 'fa-users';
        $archivo = 'admin-usuarios'; // Normalizar a admin-usuarios
        break;
    case 'admin-tipo-pago':
        $titulo = 'TIPO DE PAGOS';
        $icono = 'fa-chart-bar';
        $archivo = 'admin-tipo-pago';
        break;
    default:
        $titulo = 'INICIO';
        $icono = 'fa-home';
        $archivo = 'panel-admin';
}

// Ruta del contenido
$ruta = "includes/admin/{$archivo}.php";
if (!file_exists(__DIR__ . "/" . $ruta)) {
    $ruta = null;
}

// Función para botón activo
function activo($id, $pagina){
    return $id === $pagina ? 'bg-gradient-to-r from-blue-600 to-blue-400 text-white' : 'text-white/80 hover:bg-gradient-to-r hover:from-blue-500 hover:to-blue-400';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel Administrativo</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/a2e0d6d123.js" crossorigin="anonymous"></script>
  <style>
    .header-bg {
        background-image: url('assets/img/img-background.png'); /* imagen de fondo para todos los módulos */
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }
    .header-bg::after {
        content: '';
        position: absolute;
        inset: 0;
        background-color: rgba(0,0,0,0.4); /* overlay para mejorar contraste del texto */
        border-radius: 1rem;
    }
    .header-content {
        position: relative;
        z-index: 10;
    }
  </style>
</head>


<body class="flex min-h-screen bg-gray-100 font-sans">

<div class="flex">
  <!-- BOTÓN DE TOGGLE -->
  <button id="toggleSidebar" class="fixed top-6 left-6 z-[60] bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-2xl shadow-lg transition-all duration-500">
    <i class="fas fa-bars text-xl"></i>
  </button>

  <!-- SIDEBAR -->
  <aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-black text-white shadow-2xl z-50 rounded-tr-[70px] rounded-br-[70px] overflow-hidden backdrop-blur-lg transition-transform duration-700 ease-out translate-x-0">

    <!-- LOGO Y ESTADO ADMIN -->
    <div class="flex flex-col items-center justify-center pt-10 pb-4 relative">
      <img src="assets/img/logo1.png" alt="Logo" class="w-28 h-28 rounded-full shadow-lg hover:scale-110 hover:rotate-6 transition-transform duration-700 ease-out animate-float">
      <div class="mt-3 flex items-center gap-2">
        <span class="relative flex h-3 w-3">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
        </span>
        <p class="text-sm text-gray-300">Administrador</p>
      </div>
    </div>

    <div class="mx-8 mb-6 h-[2px] bg-gradient-to-r from-transparent via-blue-400/40 to-transparent"></div>

    <!-- MENÚ HARD-CODED CON ICONOS DE FONT AWESOME -->
    <nav class="flex flex-col gap-3 px-5">
      <button onclick="window.location='?pagina=panel-admin'" class="relative flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold transition-all duration-500 hover:translate-x-2 overflow-hidden group <?= activo('registro-bienestar-estudiantil', $pagina) ?>">
        <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-400 opacity-0 group-hover:opacity-100 blur-xl transition-all duration-700"></span>
        <span class="absolute inset-0 bg-white/5 group-hover:bg-white/10 rounded-2xl transition-all duration-700"></span>
        <i class="fas fa-plus text-xl relative z-10"></i>
        <span class="relative z-10">INICIO</span>
      </button>

      <button onclick="window.location='?pagina=admin-notificaciones'" class="relative flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold transition-all duration-500 hover:translate-x-2 overflow-hidden group <?= activo('reportes-bienestar-estudiantil', $pagina) ?>">
        <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-400 opacity-0 group-hover:opacity-100 blur-xl transition-all duration-700"></span>
        <span class="absolute inset-0 bg-white/5 group-hover:bg-white/10 rounded-2xl transition-all duration-700"></span>
        <i class="fas fa-chart-bar text-xl relative z-10"></i>
        <span class="relative z-10">NOTIFICACIONES</span>
      </button>
      <button onclick="window.location='?pagina=admin-agregar-usuario'" class="relative flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold transition-all duration-500 hover:translate-x-2 overflow-hidden group <?= activo('reportes-bienestar-estudiantil', $pagina) ?>">
        <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-400 opacity-0 group-hover:opacity-100 blur-xl transition-all duration-700"></span>
        <span class="absolute inset-0 bg-white/5 group-hover:bg-white/10 rounded-2xl transition-all duration-700"></span>
        <i class="fas fa-chart-bar text-xl relative z-10"></i>
        <span class="relative z-10">AGREGAR USUARIO</span>
      </button>
      <button onclick="window.location='?pagina=admin-tipo-pago'" class="relative flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold transition-all duration-500 hover:translate-x-2 overflow-hidden group <?= activo('reportes-bienestar-estudiantil', $pagina) ?>">
        <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-400 opacity-0 group-hover:opacity-100 blur-xl transition-all duration-700"></span>
        <span class="absolute inset-0 bg-white/5 group-hover:bg-white/10 rounded-2xl transition-all duration-700"></span>
        <i class="fas fa-chart-bar text-xl relative z-10"></i>
        <span class="relative z-10">AGREGAR PAGO</span>
      </button>      
      
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

<!-- MAIN CONTENT -->
<main class="ml-72 p-8 flex-1">
  <!-- HEADER CON FOTO DE FONDO Y OVERLAY -->
  <header class="flex justify-between items-center mb-8 slide-in p-6 rounded-2xl text-white header-bg">
    <div class="header-content">
      <h1 class="text-4xl font-bold mb-2"><i class="fas <?= $icono ?> mr-3"></i><?= strtoupper($titulo) ?></h1>
      <p class="text-white/80">Gestión del módulo <?= strtoupper($titulo) ?></p>
    </div>
  </header>

    <!-- CONTENIDO -->
    <section class="p-8 bg-gray-50 flex-1 overflow-y-auto">
      <?php
      if ($ruta) {
        include $ruta;
      } else {
        echo "<div class='text-center text-gray-500 text-xl font-semibold mt-10'>
                <i class='fa-solid fa-triangle-exclamation text-3xl mb-3 text-red-500'></i><br>
                Página no encontrada
              </div>";
      }
      ?>
    </section>
  </main>

</body>
</html>
