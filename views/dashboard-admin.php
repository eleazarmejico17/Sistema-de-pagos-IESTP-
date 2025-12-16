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

$nombreAside = trim((string)($_SESSION['nombre'] ?? ''));
$dniAside = trim((string)($_SESSION['dni'] ?? ''));
if ($dniAside === '') {
    $userSesion = (string)($_SESSION['usuario'] ?? '');
    if (preg_match('/^(\d{8})@/i', $userSesion, $m)) {
        $dniAside = $m[1];
    }
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

// Asignar o actualizar credenciales de estudiante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? null) === 'asignar_acceso_estudiante') {
    require_once __DIR__ . '/../config/conexion.php';
    $pdo = Conexion::getInstance()->getConnection();

    $redirectEstudiantes = 'dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios';

    try {
        $estudianteId = (int)($_POST['estudiante_id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');

        if ($estudianteId <= 0 || $password === '') {
            throw new Exception('Debe completar los campos obligatorios.');
        }

        $stmt = $pdo->prepare('SELECT id, dni_est FROM estudiante WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $estudianteId]);
        $estudianteRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$estudianteRow) {
            throw new Exception('Estudiante no encontrado.');
        }

        $dni = trim((string)($estudianteRow['dni_est'] ?? ''));
        if ($dni === '') {
            throw new Exception('El estudiante no tiene DNI.');
        }

        $usuario = strtolower($dni . '@institutocajas.edu.pe');

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE estuempleado = :id AND tipo = 2 LIMIT 1');
        $stmt->execute([':id' => $estudianteId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = :usuario AND NOT (tipo = 2 AND estuempleado = :id) LIMIT 1');
        $stmt->execute([':usuario' => $usuario, ':id' => $estudianteId]);
        if ($stmt->fetch()) {
            throw new Exception('El usuario ya existe.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE usuarios SET usuario = :usuario, password = :password, token = NULL WHERE id = :id');
            $stmt->execute([
                ':usuario' => $usuario,
                ':password' => $passwordHash,
                ':id' => (int)$existing['id'],
            ]);

            header('Location: ' . $redirectEstudiantes . '&status=access_updated');
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, tipo, estuempleado, token) VALUES (:usuario, :password, 2, :estuempleado, NULL)');
        $stmt->execute([
            ':usuario' => $usuario,
            ':password' => $passwordHash,
            ':estuempleado' => $estudianteId,
        ]);

        header('Location: ' . $redirectEstudiantes . '&status=access_created');
        exit;
    } catch (Throwable $e) {
        $_SESSION['usuarios_estudiantes_errors'] = [$e->getMessage()];
        header('Location: ' . $redirectEstudiantes . '&status=error');
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
            header("Location: dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios&status=updated");
            exit;
        }
    } catch (Exception $e) {
        // Error será manejado en la vista
    }
}

// Procesar eliminación de estudiante
if (isset($_GET['delete']) && (($_GET['pagina'] ?? 'panel-admin') === 'panel-admin') && (($_GET['modulo'] ?? null) === 'admin-usuarios')) {
    require_once __DIR__ . '/../controller/admin-usuariosController.php';
    require_once __DIR__ . '/../controller/NotificacionHelper.php';

    $ctrl = new EstudiantesController();
    $redirectUrl = 'dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios';

    try {
        $estudianteId = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);
        if (!$estudianteId) {
            throw new Exception('Solicitud inválida.');
        }

        $estudianteEliminar = $ctrl->obtener($estudianteId);

        if (!$ctrl->eliminar($estudianteId)) {
            throw new Exception('No se pudo eliminar el estudiante.');
        }

        try {
            $pdo = Conexion::getInstance()->getConnection();
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE estuempleado = :id AND tipo = 2');
            $stmt->execute([':id' => $estudianteId]);
        } catch (Throwable $e) {
        }

        if ($estudianteEliminar) {
            NotificacionHelper::crear('eliminar', 'usuario', [
                'nombre' => ($estudianteEliminar['nom_est'] ?? '') . ' ' . ($estudianteEliminar['ap_est'] ?? ''),
                'tipo' => 'Estudiante'
            ]);
        }

        header('Location: ' . $redirectUrl . '&status=deleted');
        exit;
    } catch (Throwable $e) {
        $_SESSION['usuarios_estudiantes_errors'] = [$e->getMessage()];
        header('Location: ' . $redirectUrl . '&status=error');
        exit;
    }
}

// Procesar acciones de admin-usuarios-sistema ANTES de cualquier output
if (($_GET['pagina'] ?? 'panel-admin') === 'panel-admin' && ($_GET['modulo'] ?? null) === 'admin-usuarios-sistema') {
    require_once __DIR__ . '/../config/conexion.php';
    $pdo = Conexion::getInstance()->getConnection();

    $redirectUrl = 'dashboard-admin.php?pagina=panel-admin&modulo=admin-usuarios-sistema';

    // Remover acceso
    if (isset($_GET['remover'])) {
        try {
            $usuarioId = (int)($_GET['remover'] ?? 0);
            if ($usuarioId <= 0) {
                throw new Exception('Solicitud inválida.');
            }

            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id AND tipo IN (2,4,5)');
            $stmt->execute([':id' => $usuarioId]);

            header('Location: ' . $redirectUrl . '&status=access_removed');
            exit;
        } catch (Throwable $e) {
            $_SESSION['usuarios_sistema_errors'] = [$e->getMessage()];
            header('Location: ' . $redirectUrl . '&status=error');
            exit;
        }
    }

    // Asignar acceso (crear credenciales)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? null) === 'asignar_acceso') {
        try {
            $empleadoId = (int)($_POST['empleado_id'] ?? 0);
            $tipo = (int)($_POST['tipo'] ?? 0);
            $usuario = strtolower(trim((string)($_POST['usuario'] ?? '')));
            $password = (string)($_POST['password'] ?? '');

            if ($empleadoId <= 0 || !in_array($tipo, [4, 5], true) || $usuario === '' || $password === '') {
                throw new Exception('Debe completar los campos obligatorios.');
            }

            $stmt = $pdo->prepare('SELECT id FROM empleado WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $empleadoId]);
            if (!$stmt->fetch()) {
                throw new Exception('Empleado no encontrado.');
            }

            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE estuempleado = :id AND tipo IN (4,5) LIMIT 1');
            $stmt->execute([':id' => $empleadoId]);
            if ($stmt->fetch()) {
                throw new Exception('El empleado ya tiene acceso asignado.');
            }

            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            if ($stmt->fetch()) {
                throw new Exception('El usuario ya existe.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, tipo, estuempleado, token) VALUES (:usuario, :password, :tipo, :estuempleado, NULL)');
            $stmt->execute([
                ':usuario' => $usuario,
                ':password' => $passwordHash,
                ':tipo' => $tipo,
                ':estuempleado' => $empleadoId,
            ]);

            header('Location: ' . $redirectUrl . '&status=access_created');
            exit;
        } catch (Throwable $e) {
            $_SESSION['usuarios_sistema_errors'] = [$e->getMessage()];
            header('Location: ' . $redirectUrl . '&status=error');
            exit;
        }
    }

    // Editar acceso (cambiar contraseña opcional)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? null) === 'editar_acceso') {
        try {
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $tipo = (int)($_POST['tipo'] ?? 0);
            $password = (string)($_POST['password'] ?? '');

            if ($usuarioId <= 0 || !in_array($tipo, [2, 4, 5], true)) {
                throw new Exception('Solicitud inválida.');
            }

            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = :id AND tipo IN (2,4,5) LIMIT 1');
            $stmt->execute([':id' => $usuarioId]);
            if (!$stmt->fetch()) {
                throw new Exception('Usuario de sistema no encontrado.');
            }

            $sql = 'UPDATE usuarios SET tipo = :tipo';
            $params = [':id' => $usuarioId, ':tipo' => $tipo];

            if ($password !== '') {
                $sql .= ', password = :password';
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header('Location: ' . $redirectUrl . '&status=access_updated');
            exit;
        } catch (Throwable $e) {
            $_SESSION['usuarios_sistema_errors'] = [$e->getMessage()];
            header('Location: ' . $redirectUrl . '&status=error');
            exit;
        }
    }
}

// Procesar acciones de admin-caja ANTES de cualquier output
if (($_GET['pagina'] ?? null) === 'admin-caja') {
    require_once __DIR__ . '/../config/conexion.php';
    $pdo = Conexion::getInstance()->getConnection();

    $redirectCaja = 'dashboard-admin.php?pagina=admin-caja';

    $hasMetodoPagoTable = false;
    $hasMetodoPagoColumnInPagos = false;
    $hasPrecioColumnInTipoPago = false;

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'metodo_pago'");
        $hasMetodoPagoTable = (bool) $stmt->fetch(PDO::FETCH_NUM);
    } catch (Throwable $e) {
    }

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM pagos');
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $hasMetodoPagoColumnInPagos = in_array('metodo_pago', $cols, true);
    } catch (Throwable $e) {
    }

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM tipo_pago');
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $hasPrecioColumnInTipoPago = in_array('precio', $cols, true);
    } catch (Throwable $e) {
    }

    // Crear / Editar Tipo de Pago
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && in_array($_POST['accion'], ['crear', 'editar'], true)) {
        try {
            $accion = (string)$_POST['accion'];
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $descripcion = trim((string)($_POST['descripcion'] ?? ''));
            $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : null;

            if ($nombre === '') {
                throw new Exception('El nombre es obligatorio.');
            }
            if ($accion === 'editar' && $id <= 0) {
                throw new Exception('ID inválido.');
            }

            if ($accion === 'crear') {
                if ($hasPrecioColumnInTipoPago) {
                    if ($precio === null || $precio < 0) {
                        throw new Exception('El precio es obligatorio.');
                    }
                    $stmt = $pdo->prepare('INSERT INTO tipo_pago (nombre, descripcion, precio) VALUES (:nombre, :descripcion, :precio)');
                    $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':precio' => $precio]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO tipo_pago (nombre, descripcion) VALUES (:nombre, :descripcion)');
                    $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);
                }

                header('Location: ' . $redirectCaja . '&msg=creado');
                exit;
            }

            if ($hasPrecioColumnInTipoPago) {
                $stmt = $pdo->prepare('UPDATE tipo_pago SET nombre = :nombre, descripcion = :descripcion, precio = :precio WHERE id = :id');
                $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':precio' => (float)$precio, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE tipo_pago SET nombre = :nombre, descripcion = :descripcion WHERE id = :id');
                $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':id' => $id]);
            }

            header('Location: ' . $redirectCaja . '&msg=actualizado');
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $redirectCaja . '&msg=error&detalle=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // Eliminar Tipo de Pago
    if (isset($_GET['eliminar_tipo_pago'])) {
        try {
            $id = (int)($_GET['eliminar_tipo_pago'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID inválido.');
            }

            $stmt = $pdo->prepare('DELETE FROM tipo_pago WHERE id = :id');
            $stmt->execute([':id' => $id]);

            header('Location: ' . $redirectCaja . '&msg=eliminado');
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $redirectCaja . '&msg=error&detalle=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // Crear / Editar Método de Pago
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_metodo']) && in_array($_POST['accion_metodo'], ['crear', 'editar'], true)) {
        try {
            if (!$hasMetodoPagoTable) {
                throw new Exception('La tabla metodo_pago no existe en la base de datos.');
            }

            $accion = (string)$_POST['accion_metodo'];
            $id = (int)($_POST['metodo_id'] ?? 0);
            $nombre = trim((string)($_POST['metodo_nombre'] ?? ''));
            $descripcion = trim((string)($_POST['metodo_descripcion'] ?? ''));

            if ($nombre === '') {
                throw new Exception('El nombre del método es obligatorio.');
            }
            if ($accion === 'editar' && $id <= 0) {
                throw new Exception('ID inválido.');
            }

            if ($accion === 'crear') {
                $stmt = $pdo->prepare('INSERT INTO metodo_pago (nombre, descripcion) VALUES (:nombre, :descripcion)');
                $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);
                header('Location: ' . $redirectCaja . '&msg=creado');
                exit;
            }

            $stmt = $pdo->prepare('UPDATE metodo_pago SET nombre = :nombre, descripcion = :descripcion WHERE id = :id');
            $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':id' => $id]);
            header('Location: ' . $redirectCaja . '&msg=actualizado');
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $redirectCaja . '&msg=error&detalle=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // Eliminar Método de Pago
    if (isset($_GET['eliminar_metodo_pago'])) {
        try {
            if (!$hasMetodoPagoTable) {
                throw new Exception('La tabla metodo_pago no existe en la base de datos.');
            }

            $id = (int)($_GET['eliminar_metodo_pago'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID inválido.');
            }

            $stmt = $pdo->prepare('DELETE FROM metodo_pago WHERE id = :id');
            $stmt->execute([':id' => $id]);

            header('Location: ' . $redirectCaja . '&msg=eliminado');
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $redirectCaja . '&msg=error&detalle=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // Crear / Editar Pago
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_pago']) && in_array($_POST['accion_pago'], ['crear', 'editar'], true)) {
        try {
            $accion = (string)$_POST['accion_pago'];
            $pagoId = (int)($_POST['pago_id'] ?? 0);
            $estudianteId = (int)($_POST['estudiante'] ?? 0);
            $tipoPago = (int)($_POST['tipo_pago'] ?? 0);
            $metodoPago = (int)($_POST['metodo_pago'] ?? 0);
            $montoOriginal = (float)($_POST['monto_original'] ?? 0);
            $montoDescuento = (float)($_POST['monto_descuento'] ?? 0);
            $comprobante = trim((string)($_POST['comprobante'] ?? ''));

            if ($estudianteId <= 0 || $tipoPago <= 0) {
                throw new Exception('Debe seleccionar estudiante y tipo de pago.');
            }
            if ($montoOriginal < 0 || $montoDescuento < 0) {
                throw new Exception('Montos inválidos.');
            }
            if ($montoDescuento > $montoOriginal) {
                throw new Exception('El descuento no puede ser mayor que el monto.');
            }
            if ($accion === 'editar' && $pagoId <= 0) {
                throw new Exception('ID de pago inválido.');
            }
            if ($hasMetodoPagoColumnInPagos && $metodoPago <= 0) {
                throw new Exception('Debe seleccionar método de pago.');
            }

            $montoFinal = $montoOriginal - $montoDescuento;

            if ($accion === 'crear') {
                if ($hasMetodoPagoColumnInPagos) {
                    $stmt = $pdo->prepare('INSERT INTO pagos (estudiante, tipo_pago, metodo_pago, monto_original, monto_descuento, monto_final, fecha_pago, comprobante, registrado_por, registrado_en) VALUES (:estudiante, :tipo_pago, :metodo_pago, :monto_original, :monto_descuento, :monto_final, NOW(), :comprobante, NULL, NOW())');
                    $stmt->execute([
                        ':estudiante' => $estudianteId,
                        ':tipo_pago' => $tipoPago,
                        ':metodo_pago' => $metodoPago,
                        ':monto_original' => $montoOriginal,
                        ':monto_descuento' => $montoDescuento,
                        ':monto_final' => $montoFinal,
                        ':comprobante' => $comprobante,
                    ]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO pagos (estudiante, tipo_pago, monto_original, monto_descuento, monto_final, fecha_pago, comprobante, registrado_por, registrado_en) VALUES (:estudiante, :tipo_pago, :monto_original, :monto_descuento, :monto_final, NOW(), :comprobante, NULL, NOW())');
                    $stmt->execute([
                        ':estudiante' => $estudianteId,
                        ':tipo_pago' => $tipoPago,
                        ':monto_original' => $montoOriginal,
                        ':monto_descuento' => $montoDescuento,
                        ':monto_final' => $montoFinal,
                        ':comprobante' => $comprobante,
                    ]);
                }

                header('Location: ' . $redirectCaja . '&msg=pago_creado');
                exit;
            }

            if ($hasMetodoPagoColumnInPagos) {
                $stmt = $pdo->prepare('UPDATE pagos SET estudiante = :estudiante, tipo_pago = :tipo_pago, metodo_pago = :metodo_pago, monto_original = :monto_original, monto_descuento = :monto_descuento, monto_final = :monto_final, comprobante = :comprobante WHERE id = :id');
                $stmt->execute([
                    ':estudiante' => $estudianteId,
                    ':tipo_pago' => $tipoPago,
                    ':metodo_pago' => $metodoPago,
                    ':monto_original' => $montoOriginal,
                    ':monto_descuento' => $montoDescuento,
                    ':monto_final' => $montoFinal,
                    ':comprobante' => $comprobante,
                    ':id' => $pagoId,
                ]);
            } else {
                $stmt = $pdo->prepare('UPDATE pagos SET estudiante = :estudiante, tipo_pago = :tipo_pago, monto_original = :monto_original, monto_descuento = :monto_descuento, monto_final = :monto_final, comprobante = :comprobante WHERE id = :id');
                $stmt->execute([
                    ':estudiante' => $estudianteId,
                    ':tipo_pago' => $tipoPago,
                    ':monto_original' => $montoOriginal,
                    ':monto_descuento' => $montoDescuento,
                    ':monto_final' => $montoFinal,
                    ':comprobante' => $comprobante,
                    ':id' => $pagoId,
                ]);
            }

            header('Location: ' . $redirectCaja . '&msg=pago_actualizado');
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $redirectCaja . '&msg=error&detalle=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // Eliminar Pago
    if (isset($_GET['eliminar_pago'])) {
        try {
            $id = (int)($_GET['eliminar_pago'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID inválido.');
            }

            $stmt = $pdo->prepare('DELETE FROM pagos WHERE id = :id');
            $stmt->execute([':id' => $id]);

            header('Location: ' . $redirectCaja . '&msg=pago_eliminado');
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $redirectCaja . '&msg=error&detalle=' . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Página actual
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'panel-admin';

// Normalizar nombres de páginas
switch($pagina){
    case 'panel-admin':
        $titulo = 'ADMINISTRAR USUARIOS';
        $icono = 'fa-users-gear';
        $archivo = 'viewsaduser';
        break;
    case 'admin-resoluciones':
        $titulo = 'RESOLUCIONES';
        $icono = 'fa-file-signature';
        $archivo = 'admin-resoluciones';
        break;
    case 'admin-caja':
        $titulo = 'ADMINISTRAR CAJA';
        $icono = 'fa-cash-register';
        $archivo = 'viewadcja';
        break;
    case 'admin-sistema':
        $titulo = 'ADMINISTRAR SISTEMA';
        $icono = 'fa-gears';
        $archivo = 'viewadsis';
        break;
    default:
        $titulo = 'ADMINISTRAR USUARIOS';
        $icono = 'fa-home';
        $archivo = 'viewsaduser';
}

// Ruta del contenido
$ruta = "includes/admin/{$archivo}.php";
if (!file_exists(__DIR__ . "/" . $ruta)) {
    $ruta = null;
}

// Función para botón activo
function activo($id, $pagina){
    return $id === $pagina ? 'bg-blue-600 text-white' : 'text-white/80 hover:bg-white/10';
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

      <div class="mt-3 text-center">
        <?php if ($nombreAside !== ''): ?>
          <div class="text-sm font-semibold text-white/90"><?= htmlspecialchars($nombreAside, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($dniAside !== ''): ?>
          <div class="text-xs text-gray-300">DNI: <?= htmlspecialchars($dniAside, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="mx-8 mb-6 h-[2px] bg-gradient-to-r from-transparent via-blue-400/40 to-transparent"></div>

    <!-- MENÚ HARD-CODED CON ICONOS DE FONT AWESOME -->
    <nav class="flex flex-col gap-3 px-5">
      <button onclick="window.location='?pagina=panel-admin'" class="flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold transition-colors duration-200 <?= activo('panel-admin', $pagina) ?>">
        <i class="fas fa-users-gear text-xl"></i>
        <span>ADMINISTRAR USUARIOS</span>
      </button>

      <button onclick="window.location='?pagina=admin-caja'" class="flex items-center gap-4 px-5 py-3 rounded-2xl font-semibold transition-colors duration-200 <?= activo('admin-caja', $pagina) ?>">
        <i class="fas fa-cash-register text-xl"></i>
        <span>ADMINISTRAR CAJA</span>
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
