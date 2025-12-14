<?php
session_start();
header('Content-Type: application/json');

/* ---------- 1. Datos de prueba (más adelante vendrán de BD) ---------- */
$usuarios = [
    ['usuario' => 'admin',     'password' => '123456',  'rol' => 'admin'],
    ['usuario' => 'direccion', 'password' => 'dir123',  'rol' => 'direccion'],
    ['usuario' => 'bienestar', 'password' => 'bien123', 'rol' => 'bienestar'],
    ['usuario' => 'usuario',   'password' => 'user123', 'rol' => 'usuario'],
];

/* ---------- 2. Recibimos credenciales ---------- */
$input = json_decode(file_get_contents('php://input'), true);
$usuario  = strtolower(trim($input['usuario']   ?? ''));
$password = strtolower(trim($input['password'] ?? ''));

/* ---------- 3. Buscamos el usuario ---------- */
$user = null;
foreach ($usuarios as $u) {
    if ($u['usuario'] === $usuario && $u['password'] === $password) {
        $user = $u;
        break;
    }
}

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
    exit;
}

/* ---------- 4. Creamos la sesión ---------- */
$_SESSION['usuario'] = $user['usuario'];
$_SESSION['rol']     = $user['rol'];

/* ---------- 5. Respondemos con la ruta a la que debe ir el front ---------- */
echo json_encode(['redirect' => "../views/dashboard-{$user['rol']}.php"]);