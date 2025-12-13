<?php
session_start();

// Validar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../public/login.html');
    exit;
}

// Obtener el rol del GET o POST
$rol = null;
if (isset($_GET['rol'])) {
    $rol = strtolower(trim($_GET['rol']));
} elseif (isset($_POST['rol'])) {
    $rol = strtolower(trim($_POST['rol']));
} else {
    // Si viene por JSON (API)
    $input = json_decode(file_get_contents('php://input'), true);
    $rol = strtolower(trim($input['rol'] ?? ''));
}

// Validar que el rol sea válido
$rolesValidos = ['usuario', 'bienestar', 'direccion', 'admin'];
if (!$rol || !in_array($rol, $rolesValidos, true)) {
    if (isset($_GET['redirect']) || isset($_POST['redirect'])) {
        header('Location: ../views/dashboard-admin.php?error=rol_invalido');
        exit;
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Rol inválido']);
    exit;
}

// Actualizar el rol en la sesión
$_SESSION['rol'] = $rol;

// Si es petición con redirect (GET o POST directo), redirigir directamente
if (isset($_GET['redirect']) || isset($_POST['redirect'])) {
    header("Location: ../views/dashboard-{$rol}.php");
    exit;
}

// Si es petición AJAX, retornar JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'redirect' => "../views/dashboard-{$rol}.php"
]);
