<?php
require_once __DIR__ . '/../models/NotificacionModel.php';

class NotificacionHelper {
    
    /**
     * Crea una notificación cuando se realiza una acción en el sistema
     */
    public static function crear($accion, $modulo, $datos = []) {
        try {
            $notificacion = new NotificacionModel();
            
            // Obtener información del usuario actual
            $usuarioId = $_SESSION['usuario']['id'] ?? $_SESSION['usuario_id'] ?? null;
            $usuarioNombre = $_SESSION['usuario']['nombre_completo'] ?? 
                            $_SESSION['usuario']['usuario'] ?? 
                            $_SESSION['usuario'] ?? 'Sistema';

            // Determinar tipo y mensaje según la acción
            $config = self::getConfiguracion($accion, $modulo, $datos);

            return $notificacion->crear([
                'usuario_id' => null, // null = notificación global para todos los admins
                'usuario_nombre' => $usuarioNombre,
                'tipo' => $config['tipo'],
                'titulo' => $config['titulo'],
                'mensaje' => $config['mensaje'],
                'modulo' => $modulo,
                'accion' => $accion,
                'referencia_id' => $datos['id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error en NotificacionHelper::crear: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configuración de mensajes según acción y módulo
     */
    private static function getConfiguracion($accion, $modulo, $datos) {
        $config = [
            'tipo' => 'info',
            'titulo' => '',
            'mensaje' => ''
        ];

        switch ($modulo) {
            case 'tipo_pago':
                switch ($accion) {
                    case 'crear':
                        $config = [
                            'tipo' => 'success',
                            'titulo' => 'Nuevo Tipo de Pago Creado',
                            'mensaje' => "Se ha creado un nuevo tipo de pago: <strong>" . ($datos['nombre'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'editar':
                        $config = [
                            'tipo' => 'info',
                            'titulo' => 'Tipo de Pago Actualizado',
                            'mensaje' => "Se ha actualizado el tipo de pago: <strong>" . ($datos['nombre'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'eliminar':
                        $config = [
                            'tipo' => 'warning',
                            'titulo' => 'Tipo de Pago Eliminado',
                            'mensaje' => "Se ha eliminado el tipo de pago: <strong>" . ($datos['nombre'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                }
                break;

            case 'usuario':
                switch ($accion) {
                    case 'crear':
                        $config = [
                            'tipo' => 'success',
                            'titulo' => 'Nuevo Usuario Registrado',
                            'mensaje' => "Se ha registrado un nuevo usuario: <strong>" . ($datos['nombre'] ?? $datos['usuario'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'editar':
                        $config = [
                            'tipo' => 'info',
                            'titulo' => 'Usuario Actualizado',
                            'mensaje' => "Se ha actualizado la información del usuario: <strong>" . ($datos['nombre'] ?? $datos['usuario'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'eliminar':
                        $config = [
                            'tipo' => 'warning',
                            'titulo' => 'Usuario Eliminado',
                            'mensaje' => "Se ha eliminado el usuario: <strong>" . ($datos['nombre'] ?? $datos['usuario'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                }
                break;

            case 'empleado':
            case 'bienestar':
                switch ($accion) {
                    case 'crear':
                        $config = [
                            'tipo' => 'success',
                            'titulo' => 'Nuevo Empleado Registrado',
                            'mensaje' => "Se ha registrado un nuevo empleado: <strong>" . ($datos['nombre_completo'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'editar':
                        $config = [
                            'tipo' => 'info',
                            'titulo' => 'Empleado Actualizado',
                            'mensaje' => "Se ha actualizado la información del empleado: <strong>" . ($datos['nombre_completo'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'eliminar':
                        $config = [
                            'tipo' => 'warning',
                            'titulo' => 'Empleado Eliminado',
                            'mensaje' => "Se ha eliminado el empleado: <strong>" . ($datos['nombre_completo'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                }
                break;

            case 'solicitud':
                switch ($accion) {
                    case 'crear':
                        $config = [
                            'tipo' => 'info',
                            'titulo' => 'Nueva Solicitud Recibida',
                            'mensaje' => "Se ha recibido una nueva solicitud de <strong>" . ($datos['estudiante'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'aprobar':
                        $config = [
                            'tipo' => 'success',
                            'titulo' => 'Solicitud Aprobada',
                            'mensaje' => "Se ha aprobado la solicitud #<strong>" . ($datos['id'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                    case 'rechazar':
                        $config = [
                            'tipo' => 'warning',
                            'titulo' => 'Solicitud Rechazada',
                            'mensaje' => "Se ha rechazado la solicitud #<strong>" . ($datos['id'] ?? 'N/A') . "</strong>"
                        ];
                        break;
                }
                break;
        }

        return $config;
    }
}

