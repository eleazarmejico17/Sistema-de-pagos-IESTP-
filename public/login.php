<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexion.php';

/* ---------- 1. Recibimos credenciales ---------- */
$input = json_decode(file_get_contents('php://input'), true);
$usuario  = strtolower(trim($input['usuario'] ?? ''));
$password = (string)($input['password'] ?? '');
$tipoAcceso = strtolower(trim($input['tipo_acceso'] ?? 'estudiante'));

if ($usuario === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Debe ingresar correo y contraseña']);
    exit;
}

/* ---------- 2. Administrador principal (no se guarda en BD) ---------- */
// Puedes cambiar estas credenciales en este archivo.
// Si defines variables de entorno, tendrán prioridad.
$masterUser = getenv('MASTER_ADMIN_USER') ?: 'admin@institutocajas.edu.pe';
$masterPass = getenv('MASTER_ADMIN_PASS') ?: '123456';

// Si se eligió Administrador, no se valida formato de correo ni se consulta BD.
if ($tipoAcceso === 'admin') {
    if (hash_equals(strtolower($masterUser), $usuario) && hash_equals($masterPass, $password)) {
        $_SESSION['usuario'] = $usuario;
        $_SESSION['rol'] = 'admin';
        $_SESSION['user_id'] = 0;
        $_SESSION['nombre'] = 'Administrador';
        $_SESSION['dni'] = '';
        echo json_encode(['redirect' => "../views/dashboard-admin.php"]);
        exit;
    }

    http_response_code(401);
    echo json_encode(['error' => 'Credenciales de administrador incorrectas']);
    exit;
}

// Estudiantes deben usar formato 8 dígitos + @institutocajas.edu.pe
if ($tipoAcceso === 'estudiante' && !preg_match('/^(\d{8})@institutocajas\.edu\.pe$/', $usuario)) {
    http_response_code(400);
    echo json_encode(['error' => 'Para estudiantes el correo debe ser: DNI@institutocajas.edu.pe']);
    exit;
}

/* ---------- 3. Autenticación contra BD (tabla usuarios) ---------- */
try {
    $db = Conexion::getInstance()->getConnection();

    $stmt = $db->prepare('SELECT id, usuario, password, tipo, estuempleado FROM usuarios WHERE usuario = :usuario LIMIT 1');
    $stmt->execute([':usuario' => $usuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'El correo electrónico no existe']);
        exit;
    }

    if (!password_verify($password, (string)$row['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Contraseña incorrecta']);
        exit;
    }

    $tipo = (int)($row['tipo'] ?? 0);

    // Validar que el usuario exista y corresponda al tipo de acceso seleccionado
    $expectedTipo = null;
    if ($tipoAcceso === 'estudiante') {
        $expectedTipo = 2;
    } elseif ($tipoAcceso === 'bienestar') {
        $expectedTipo = 4;
    } elseif ($tipoAcceso === 'direccion') {
        $expectedTipo = 5;
    }

    if ($expectedTipo !== null && $tipo !== $expectedTipo) {
        http_response_code(403);
        echo json_encode(['error' => 'No tiene el nivel de acceso requerido para la opción seleccionada']);
        exit;
    }

    // Rol para dashboards (strings que usa tu sistema)
    $rol = 'usuario';
    if ($tipo === 5) {
        $rol = 'direccion';
    } elseif ($tipo === 4) {
        $rol = 'bienestar';
    } elseif ($tipo === 1) {
        $rol = 'admin';
    } else {
        $rol = 'usuario';
    }

    $nombreSesion = '';
    $dniSesion = '';
    try {
        if ($tipo === 2) {
            if (!empty($row['estuempleado'])) {
                $stmtEst = $db->prepare("SELECT dni_est, CONCAT(ap_est, ' ', am_est, ' ', nom_est) AS nombre FROM estudiante WHERE id = :id LIMIT 1");
                $stmtEst->execute([':id' => (int)$row['estuempleado']]);
                $est = $stmtEst->fetch(PDO::FETCH_ASSOC);
                if ($est) {
                    $nombreSesion = trim((string)($est['nombre'] ?? ''));
                    $dniSesion = (string)($est['dni_est'] ?? '');
                }
            }

            if ($dniSesion === '' && preg_match('/^(\d{8})@/i', $usuario, $m)) {
                $dniSesion = $m[1];
            }
        } else {
            if (!empty($row['estuempleado'])) {
                $stmtEmp = $db->prepare("SELECT dni_emp, apnom_emp FROM empleado WHERE id = :id LIMIT 1");
                $stmtEmp->execute([':id' => (int)$row['estuempleado']]);
                $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
                if ($emp) {
                    $nombreSesion = trim((string)($emp['apnom_emp'] ?? ''));
                    $dniSesion = (string)($emp['dni_emp'] ?? '');
                }
            }
        }
    } catch (Throwable $e) {
        $nombreSesion = '';
        $dniSesion = '';
    }

    $_SESSION['usuario'] = $row['usuario'];
    $_SESSION['rol'] = $rol;
    $_SESSION['user_id'] = (int)$row['id'];
    $_SESSION['nombre'] = $nombreSesion;
    $_SESSION['dni'] = $dniSesion;

    echo json_encode(['redirect' => "../views/dashboard-{$rol}.php"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}