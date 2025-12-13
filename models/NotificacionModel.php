<?php
require_once __DIR__ . '/../config/conexion.php';

class NotificacionModel {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
        $this->crearTablaSiNoExiste();
    }

    /**
     * Crea la tabla de notificaciones si no existe
     */
    private function crearTablaSiNoExiste() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS notificaciones_sistema (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT,
                    usuario_nombre VARCHAR(255),
                    tipo VARCHAR(50) NOT NULL,
                    titulo VARCHAR(255) NOT NULL,
                    mensaje TEXT NOT NULL,
                    modulo VARCHAR(100),
                    accion VARCHAR(50),
                    referencia_id INT,
                    leida TINYINT(1) DEFAULT 0,
                    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_usuario (usuario_id),
                    INDEX idx_leida (leida),
                    INDEX idx_creado (creado_en)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Error creando tabla notificaciones: " . $e->getMessage());
        }
    }

    /**
     * Crea una nueva notificación
     */
    public function crear($datos) {
        try {
            $sql = "INSERT INTO notificaciones_sistema 
                    (usuario_id, usuario_nombre, tipo, titulo, mensaje, modulo, accion, referencia_id) 
                    VALUES (:usuario_id, :usuario_nombre, :tipo, :titulo, :mensaje, :modulo, :accion, :referencia_id)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':usuario_id' => $datos['usuario_id'] ?? null,
                ':usuario_nombre' => $datos['usuario_nombre'] ?? 'Sistema',
                ':tipo' => $datos['tipo'] ?? 'info',
                ':titulo' => $datos['titulo'],
                ':mensaje' => $datos['mensaje'],
                ':modulo' => $datos['modulo'] ?? null,
                ':accion' => $datos['accion'] ?? null,
                ':referencia_id' => $datos['referencia_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error creando notificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene notificaciones para un usuario o todas las del sistema
     */
    public function obtener($usuarioId = null, $noLeidas = false, $limite = 50) {
        try {
            $sql = "SELECT * FROM notificaciones_sistema WHERE 1=1";
            $params = [];

            if ($usuarioId !== null) {
                $sql .= " AND usuario_id = :usuario_id";
                $params[':usuario_id'] = $usuarioId;
            }

            if ($noLeidas) {
                $sql .= " AND leida = 0";
            }

            $sql .= " ORDER BY creado_en DESC LIMIT :limite";
            $params[':limite'] = (int)$limite;

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo notificaciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca una notificación como leída
     */
    public function marcarLeida($id, $usuarioId = null) {
        try {
            $sql = "UPDATE notificaciones_sistema SET leida = 1 WHERE id = :id";
            $params = [':id' => $id];

            if ($usuarioId !== null) {
                $sql .= " AND usuario_id = :usuario_id";
                $params[':usuario_id'] = $usuarioId;
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error marcando notificación como leída: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca todas las notificaciones como leídas
     */
    public function marcarTodasLeidas($usuarioId = null) {
        try {
            $sql = "UPDATE notificaciones_sistema SET leida = 1 WHERE leida = 0";
            $params = [];

            if ($usuarioId !== null) {
                $sql .= " AND usuario_id = :usuario_id";
                $params[':usuario_id'] = $usuarioId;
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error marcando todas las notificaciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el conteo de notificaciones no leídas
     */
    public function contarNoLeidas($usuarioId = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM notificaciones_sistema WHERE leida = 0";
            $params = [];

            if ($usuarioId !== null) {
                $sql .= " AND usuario_id = :usuario_id";
                $params[':usuario_id'] = $usuarioId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error contando notificaciones: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Elimina notificaciones antiguas (más de 30 días)
     */
    public function limpiarAntiguas($dias = 30) {
        try {
            $sql = "DELETE FROM notificaciones_sistema WHERE creado_en < DATE_SUB(NOW(), INTERVAL :dias DAY) AND leida = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':dias' => $dias]);
            return true;
        } catch (Exception $e) {
            error_log("Error limpiando notificaciones antiguas: " . $e->getMessage());
            return false;
        }
    }
}

