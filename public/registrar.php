<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexion.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $inputRaw = file_get_contents('php://input');
    $data = json_decode($inputRaw, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        exit;
    }

    $correo = strtolower(trim($data['correo_institucional'] ?? $data['usuario'] ?? $data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');

    // Datos requeridos por tabla estudiante (mínimos esperados por tu formulario)
    $dni_est = trim((string)($data['dni_est'] ?? ''));
    $ap_est = trim((string)($data['ap_est'] ?? ''));
    $am_est = trim((string)($data['am_est'] ?? ''));
    $nom_est = trim((string)($data['nom_est'] ?? ''));
    $sex_est = strtoupper(trim((string)($data['sex_est'] ?? '')));
    $cel_est = trim((string)($data['cel_est'] ?? ''));
    $ubigeodir_est = trim((string)($data['ubigeodir_est'] ?? ''));
    $ubigeonac_est = trim((string)($data['ubigeonac_est'] ?? ''));
    $dir_est = trim((string)($data['dir_est'] ?? ''));
    $mailp_est = strtolower(trim((string)($data['mailp_est'] ?? '')));
    $maili_est = strtolower(trim((string)($data['maili_est'] ?? $correo)));
    $fecnac_est = trim((string)($data['fecnac_est'] ?? ''));
    $ubdistrito = $data['ubdistrito'] ?? null;

    // Validar correo institucional: 8 dígitos + @institutocajas.edu.pe
    if (!preg_match('/^(\d{8})@institutocajas\.edu\.pe$/', $correo, $m)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Solo se permiten correos institucionales con formato 8 números + @institutocajas.edu.pe'
        ]);
        exit;
    }

    $dniFromCorreo = $m[1];

    // Validaciones mínimas
    if (!preg_match('/^\d{8}$/', $dni_est)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'DNI inválido']);
        exit;
    }

    if ($dni_est !== $dniFromCorreo) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El DNI no coincide con el correo institucional']);
        exit;
    }

    if ($ap_est === '' || $am_est === '' || $nom_est === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Apellidos y nombres son obligatorios']);
        exit;
    }

    if (!in_array($sex_est, ['M', 'F'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Sexo inválido (use M o F)']);
        exit;
    }

    if ($cel_est !== '' && !preg_match('/^\d{9}$/', $cel_est)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Celular inválido (9 dígitos)']);
        exit;
    }

    if ($fecnac_est !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecnac_est)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Fecha de nacimiento inválida (YYYY-MM-DD)']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }

    $db = Conexion::getInstance()->getConnection();
    $db->beginTransaction();

    // Validar no duplicados (estudiante)
    $stmt = $db->prepare('SELECT id FROM estudiante WHERE dni_est = :dni LIMIT 1');
    $stmt->execute([':dni' => $dni_est]);
    if ($stmt->fetch()) {
        $db->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Ya existe un estudiante registrado con ese DNI']);
        exit;
    }

    // Validar no duplicados (usuario)
    $stmt = $db->prepare('SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1');
    $stmt->execute([':usuario' => $correo]);
    if ($stmt->fetch()) {
        $db->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Ya existe una cuenta registrada con ese correo']);
        exit;
    }

    // Insertar estudiante
    $sqlEst = 'INSERT INTO estudiante (
        ubdistrito,
        dni_est,
        ap_est,
        am_est,
        nom_est,
        sex_est,
        cel_est,
        ubigeodir_est,
        ubigeonac_est,
        dir_est,
        mailp_est,
        maili_est,
        fecnac_est,
        foto_est,
        estado
    ) VALUES (
        :ubdistrito,
        :dni_est,
        :ap_est,
        :am_est,
        :nom_est,
        :sex_est,
        :cel_est,
        :ubigeodir_est,
        :ubigeonac_est,
        :dir_est,
        :mailp_est,
        :maili_est,
        :fecnac_est,
        :foto_est,
        :estado
    )';

    $stmt = $db->prepare($sqlEst);
    $stmt->execute([
        ':ubdistrito' => ($ubdistrito === '' ? null : $ubdistrito),
        ':dni_est' => $dni_est,
        ':ap_est' => $ap_est,
        ':am_est' => $am_est,
        ':nom_est' => $nom_est,
        ':sex_est' => $sex_est,
        ':cel_est' => ($cel_est === '' ? null : $cel_est),
        ':ubigeodir_est' => ($ubigeodir_est === '' ? null : $ubigeodir_est),
        ':ubigeonac_est' => ($ubigeonac_est === '' ? null : $ubigeonac_est),
        ':dir_est' => ($dir_est === '' ? null : $dir_est),
        ':mailp_est' => ($mailp_est === '' ? null : $mailp_est),
        ':maili_est' => $maili_est,
        ':fecnac_est' => ($fecnac_est === '' ? null : $fecnac_est),
        ':foto_est' => null,
        ':estado' => 1
    ]);

    $estudianteId = (int)$db->lastInsertId();

    // Insertar usuario (tipo=2 estudiante)
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(16));

    $stmt = $db->prepare('INSERT INTO usuarios (usuario, password, tipo, estuempleado, token) VALUES (:usuario, :password, :tipo, :estuempleado, :token)');
    $stmt->execute([
        ':usuario' => $correo,
        ':password' => $passwordHash,
        ':tipo' => 2,
        ':estuempleado' => $estudianteId,
        ':token' => $token
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Registro exitoso. Ya puedes iniciar sesión.',
        'usuario' => $correo
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor',
        'detail' => $e->getMessage()
    ]);
}
