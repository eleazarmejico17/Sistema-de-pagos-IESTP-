<?php
require_once __DIR__ . '/../config/conexion.php'; // O como tengas tu conexión

class ModelLogin {
	private $db;
	private const DOMINIO_INSTITUCIONAL = 'institutocajas.edu.pe'; // Constante para tu dominio

	public function __construct() {
		$this->db = Database::getInstance()->getConnection();
	}

	/**
	 * Intenta autenticar un usuario por correo institucional.
	 * Retorna un array con keys: success (bool), message (string), data (array|null), token (string|null)
	 */
	public function login(string $usuario, string $contrasena): array {
		
		// -----------------------------------------------------------------
		// PASO 1: (NUEVO) Validar el formato del correo institucional
		// -----------------------------------------------------------------
		// Regex: busca 8 dígitos, seguido de @ y el dominio escapado.
		$pattern = '/^[0-9]{8}@' . preg_quote(self::DOMINIO_INSTITUCIONAL, '/') . '$/';

		if (!preg_match($pattern, $usuario)) {
			return [
				'success' => false,
				'message' => 'El usuario debe ser un correo institucional válido (ej: 12345678@' . self::DOMINIO_INSTITUCIONAL . ')',
				'data'    => null,
				'token'   => null // API: Devolvemos 'token' en lugar de 'redirect'
			];
		}

		// Sanitizar entradas (aunque PDO ayuda, es buena práctica)
		$correo_institucional = Database::sanitizeInput($usuario); // Asumimos que $usuario es el correo
		$contrasena = trim($contrasena);

		// -----------------------------------------------------------------
		// PASO 2: (MODIFICADO) Buscar solo por correo en la BD
		// -----------------------------------------------------------------
		$sql = "SELECT u.id, u.usuario, u.nombre_completo, u.correo, u.contrasena_hash, r.nombre AS rol_nombre, r.id AS rol_id
				FROM usuarios u
				LEFT JOIN roles r ON u.rol_id = r.id
				WHERE u.correo = :correo_institucional
				LIMIT 1";

		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute([':correo_institucional' => $correo_institucional]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$row) {
				return [
					'success' => false,
					'message' => 'Correo institucional no encontrado',
					'data'    => null,
					'token'   => null
				];
			}

			// -----------------------------------------------------------------
			// PASO 3: (SIN CAMBIOS) Tu lógica de verificación de contraseña
			// (Esta parte ya era excelente, con fallback de SHA2 y migración)
			// -----------------------------------------------------------------
			$storedHash = $row['contrasena_hash'] ?? '';
			$passwordOk = false;

			if (!empty($storedHash) && password_verify($contrasena, $storedHash)) {
				$passwordOk = true;
			}

			if (!$passwordOk) {
				$sha256 = hash('sha256', $contrasena);
				if (!empty($storedHash) && strcasecmp($sha256, $storedHash) === 0) {
					try {
						$newHash = password_hash($contrasena, PASSWORD_DEFAULT);
						$updateSql = "UPDATE usuarios SET contrasena_hash = :newhash WHERE id = :id";
						$updateStmt = $this->db->prepare($updateSql);
						$updateStmt->execute([':newhash' => $newHash, ':id' => $row['id']]);
						$passwordOk = true;
					} catch (PDOException $e) {
						error_log('Error migrating SHA2 hash for user ' . $row['id'] . ': ' . $e->getMessage());
					}
				}
			}

			if (!$passwordOk) {
				return [
					'success' => false,
					'message' => 'Contraseña incorrecta',
					'data'    => null,
					'token'   => null
				];
			}

			// -----------------------------------------------------------------
			// PASO 4: (GRAN CAMBIO) Generar TOKEN en lugar de iniciar sesión
			// -----------------------------------------------------------------
			// Ya no llamamos a $this->iniciarSesion($userData);
			
			$token = $this->generarYGuardarToken($row['id']);

			if ($token === null) {
				return [
					'success' => false,
					'message' => 'Error en el servidor al generar el token de acceso',
					'data'    => null,
					'token'   => null
				];
			}

			// Preparar datos de usuario para devolver (sin contraseñas ni hashes)
			$userData = [
				'id'             => $row['id'],
				'usuario'        => $row['usuario'],
				'nombre_completo'=> $row['nombre_completo'],
				'correo'         => $row['correo'],
				'rol_id'         => $row['rol_id'],
				'rol_nombre'     => $row['rol_nombre'] ?? 'usuario'
			];

			// ¡Éxito! Devolvemos los datos y el token.
			return [
				'success' => true,
				'message' => 'Autenticación correcta',
				'data'    => $userData,
				'token'   => $token // El cliente debe guardar esto
			];

		} catch (PDOException $e) {
			error_log('Login query error: ' . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error en el servidor al autenticar',
				'data'    => null,
				'token'   => null
			];
		}
	}

	/**
	 * (NUEVO) Genera un token seguro y lo guarda en la BD.
	 * Asume que tienes una columna 'token' en tu tabla 'usuarios'.
	 */
	private function generarYGuardarToken(int $userId): ?string {
		try {
			$token = bin2hex(random_bytes(32)); // Token seguro de 64 caracteres

			$sql = "UPDATE usuarios SET token = :token WHERE id = :id";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':token' => $token,
				':id'    => $userId
			]);

			return $token;

		} catch (Exception $e) {
			error_log('Error generando o guardando token: ' . $e->getMessage());
			return null;
		}
	}

	/**
	 * (ELIMINADO) La función 'iniciarSesion' ya no es necesaria en un API.
	 * private function iniciarSesion(array $userData): void { ... }
	 */
}
?>