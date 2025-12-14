<?php
require_once __DIR__ . '/../config/conexion.php';

class BeneficiarioModel {
    private $db;

    public function __construct() {
        // ✅ CORREGIDO
        $this->db = Conexion::getInstance()->getConnection();
        
    }

    public function buscarEstudiantePorDNI($dni) {
        $sql = $this->db->prepare("
            SELECT 
                e.id,
                e.dni_est,
                CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre_completo,
                e.cel_est,
                e.mailp_est,
                e.maili_est,
                m.prog_estudios,
                pe.nom_progest AS programa_nombre,
                m.per_acad AS ciclo,
                m.turno
            FROM estudiante e
            LEFT JOIN matricula m ON m.estudiante = e.id
            LEFT JOIN prog_estudios pe ON pe.id = m.prog_estudios
            WHERE e.dni_est = :dni
            ORDER BY m.id DESC
            LIMIT 1
        ");
        $sql->execute([':dni' => $dni]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las columnas reales de la tabla beneficiarios
     */
    private function obtenerColumnasTabla() {
        try {
            $stmt = $this->db->query("DESCRIBE beneficiarios");
            $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $columnas;
        } catch (PDOException $e) {
            error_log("Error al obtener columnas: " . $e->getMessage());
            return [];
        }
    }

    public function crear($data) {
        try {
            $registradoPor = $data['registrado_por'] ?? null;

            // Validar que tenemos los datos necesarios
            if (empty($data['estudiante_id'])) {
                throw new Exception('El ID del estudiante es requerido');
            }
            if (empty($data['resolucion_id'])) {
                throw new Exception('El ID de la resolución es requerido');
            }
            if (empty($data['porcentaje_descuento'])) {
                throw new Exception('El porcentaje de descuento es requerido');
            }

            // Obtener columnas reales de la tabla
            $columnasReales = $this->obtenerColumnasTabla();
            
            // Mapeo de columnas esperadas a posibles variantes en la BD
            $mapColumnas = [
                'estudiante_id' => ['estudiante', 'estudiante_id', 'id_estudiante'],
                'resolucion_id' => ['resoluciones', 'resolucion_id', 'id_resolucion', 'resolucion'],
                'porcentaje_descuento' => ['porcentaje_descuento', 'descuento', 'porcentaje'],
                'fecha_inicio' => ['fecha_inicio', 'fecha_ini', 'inicio'],
                'fecha_fin' => ['fecha_fin', 'fecha_final', 'fin'],
                'activo' => ['activo', 'estado', 'habilitado'],
                'registrado_por' => ['registrado_por', 'registradoPor', 'usuario_id'],
                'registrado_en' => ['registrado_en', 'fecha_registro', 'created_at', 'fecha_creacion']
            ];

            // Encontrar los nombres reales de las columnas
            $columnasFinales = [];
            $valoresFinales = [];
            $params = [];

            foreach ($mapColumnas as $key => $posiblesNombres) {
                if ($key === 'registrado_por' && ($registradoPor === null || $registradoPor <= 0)) {
                    continue; // Saltar si no hay registrado_por
                }
                if ($key === 'registrado_en') {
                    // Siempre incluir fecha de registro
                    foreach ($posiblesNombres as $nombre) {
                        if (in_array($nombre, $columnasReales)) {
                            $columnasFinales[] = $nombre;
                            $valoresFinales[] = 'NOW()';
                            break;
                        }
                    }
                    continue;
                }

                $valor = null;
                switch ($key) {
                    case 'estudiante_id':
                        $valor = (int)$data['estudiante_id'];
                        break;
                    case 'resolucion_id':
                        $valor = (int)$data['resolucion_id'];
                        break;
                    case 'porcentaje_descuento':
                        $valor = (float)$data['porcentaje_descuento'];
                        break;
                    case 'fecha_inicio':
                        $valor = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
                        break;
                    case 'fecha_fin':
                        $valor = !empty($data['fecha_fin']) ? $data['fecha_fin'] : null;
                        break;
                    case 'activo':
                        $valor = isset($data['activo']) ? (int)$data['activo'] : 1;
                        break;
                    case 'registrado_por':
                        $valor = (int)$registradoPor;
                        break;
                }

                // Buscar el nombre real de la columna
                $columnaEncontrada = null;
                foreach ($posiblesNombres as $nombre) {
                    if (in_array($nombre, $columnasReales)) {
                        $columnaEncontrada = $nombre;
                        break;
                    }
                }

                if ($columnaEncontrada) {
                    if ($key === 'registrado_en') {
                        continue; // Ya manejado arriba
                    }
                    $columnasFinales[] = $columnaEncontrada;
                    $paramName = ':' . $columnaEncontrada;
                    $valoresFinales[] = $paramName;
                    $params[$paramName] = $valor;
                } elseif (in_array($key, ['estudiante_id', 'resolucion_id', 'porcentaje_descuento'])) {
                    // Columnas obligatorias
                    throw new Exception("No se encontró la columna requerida para '$key'. Columnas disponibles: " . implode(', ', $columnasReales));
                }
            }

            if (empty($columnasFinales)) {
                throw new Exception('No se pudieron mapear las columnas. Columnas disponibles en la tabla: ' . implode(', ', $columnasReales));
            }

            $sql = "INSERT INTO beneficiarios (" . implode(', ', $columnasFinales) . ") VALUES (" . implode(', ', $valoresFinales) . ")";
            
            // Log del SQL para debugging
            error_log("SQL generado: " . $sql);
            error_log("Parámetros: " . print_r($params, true));
            error_log("Columnas encontradas en BD: " . implode(', ', $columnasReales));
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt->execute($params)) {
                $errorInfo = $stmt->errorInfo();
                $errorMsg = $errorInfo[2] ?? 'Error desconocido';
                error_log("Error al ejecutar SQL: " . $errorMsg);
                error_log("SQL que falló: " . $sql);
                throw new Exception('Error al insertar beneficiario: ' . $errorMsg);
            }
            
            return true;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log("Error PDO al crear beneficiario: " . $errorMsg);
            error_log("Código de error: " . $e->getCode());
            
            throw new Exception('Error de base de datos: ' . $errorMsg);
        } catch (Exception $e) {
            error_log("Error al crear beneficiario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene la lista de conceptos de pago con valores UIT
     * @return array Lista de conceptos de pago
     */
    public function listarConceptosPago() {
        try {
            // Intentar verificar si existe la tabla tipo_pago
            $stmt = $this->db->query("SHOW TABLES LIKE 'tipo_pago'");
            $existeTabla = $stmt->rowCount() > 0;
            
            if ($existeTabla) {
                // Verificar si existe la columna uit
                $stmt = $this->db->query("SHOW COLUMNS FROM tipo_pago LIKE 'uit'");
                $tieneUIT = $stmt->rowCount() > 0;
                
                if ($tieneUIT) {
                    $sql = $this->db->prepare("
                        SELECT 
                            id,
                            nombre,
                            descripcion,
                            COALESCE(uit, 0.00) AS uit,
                            CASE 
                                WHEN uit > 0 THEN 'Activo'
                                ELSE 'Sin valor'
                            END AS estado_uit
                        FROM tipo_pago 
                        ORDER BY nombre ASC
                    ");
                } else {
                    $sql = $this->db->prepare("
                        SELECT 
                            id,
                            nombre,
                            descripcion,
                            0.00 AS uit,
                            'Sin configurar' AS estado_uit
                        FROM tipo_pago 
                        ORDER BY nombre ASC
                    ");
                }
                
                $sql->execute();
                $resultados = $sql->fetchAll(PDO::FETCH_ASSOC);
                
                // Si hay resultados, devolverlos
                if (!empty($resultados)) {
                    return $resultados;
                }
            }
            
            // Si no hay tabla o no hay resultados, devolver datos de ejemplo
            return [
                ['id' => 1, 'nombre' => '1.1', 'descripcion' => 'Carnet de Medio Pasaje', 'uit' => 18.00, 'estado_uit' => 'Activo'],
                ['id' => 2, 'nombre' => '1.2', 'descripcion' => 'Duplicado de carnet', 'uit' => 18.00, 'estado_uit' => 'Activo'],
                ['id' => 3, 'nombre' => '3.1', 'descripcion' => 'Inscripción del postulante modalidad ordinario', 'uit' => 205.00, 'estado_uit' => 'Activo'],
                ['id' => 4, 'nombre' => '3.2', 'descripcion' => 'Inscripción del postulante modalidad exonerados', 'uit' => 205.00, 'estado_uit' => 'Activo'],
                ['id' => 5, 'nombre' => '5.1', 'descripcion' => 'Ratificación de matrícula', 'uit' => 172.00, 'estado_uit' => 'Activo'],
                ['id' => 6, 'nombre' => '5.2', 'descripcion' => 'Matrícula Ingresantes', 'uit' => 220.00, 'estado_uit' => 'Activo'],
                ['id' => 7, 'nombre' => '6.1', 'descripcion' => 'Trámite de matrícula extemporánea', 'uit' => 8.00, 'estado_uit' => 'Activo'],
                ['id' => 8, 'nombre' => '7.1', 'descripcion' => 'Convalidación interna por semestre', 'uit' => 61.00, 'estado_uit' => 'Activo']
            ];
            
        } catch (PDOException $e) {
            error_log("Error en listarConceptosPago: " . $e->getMessage());
            // En caso de error, devolver datos de ejemplo
            return [
                ['id' => 1, 'nombre' => '1.1', 'descripcion' => 'Carnet de Medio Pasaje', 'uit' => 18.00, 'estado_uit' => 'Activo'],
                ['id' => 2, 'nombre' => '1.2', 'descripcion' => 'Duplicado de carnet', 'uit' => 18.00, 'estado_uit' => 'Activo'],
                ['id' => 3, 'nombre' => '3.1', 'descripcion' => 'Inscripción del postulante modalidad ordinario', 'uit' => 205.00, 'estado_uit' => 'Activo'],
                ['id' => 4, 'nombre' => '3.2', 'descripcion' => 'Inscripción del postulante modalidad exonerados', 'uit' => 205.00, 'estado_uit' => 'Activo'],
                ['id' => 5, 'nombre' => '5.1', 'descripcion' => 'Ratificación de matrícula', 'uit' => 172.00, 'estado_uit' => 'Activo'],
                ['id' => 6, 'nombre' => '5.2', 'descripcion' => 'Matrícula Ingresantes', 'uit' => 220.00, 'estado_uit' => 'Activo'],
                ['id' => 7, 'nombre' => '6.1', 'descripcion' => 'Trámite de matrícula extemporánea', 'uit' => 8.00, 'estado_uit' => 'Activo'],
                ['id' => 8, 'nombre' => '7.1', 'descripcion' => 'Convalidación interna por semestre', 'uit' => 61.00, 'estado_uit' => 'Activo']
            ];
        }
    }

    /**
     * Obtiene la lista de solicitudes aprobadas con información detallada
     * @return array Lista de solicitudes aprobadas
     */
    public function listarSolicitudesAprobadas() {
        try {
            $sql = $this->db->prepare("
                SELECT 
                    s.id,
                    CONCAT(e.ap_est, ' ', e.am_est, ' ', e.nom_est) AS nombre,
                    e.cel_est AS telefono,
                    e.mailp_est AS correo,
                    s.tipo_solicitud,
                    s.descripcion,
                    s.foto AS archivos,
                    s.fecha_solicitud AS fecha,
                    COALESCE(s.estado, 'Pendiente') AS estado,
                    s.observaciones AS motivo_respuesta,
                    s.fecha_revision AS fecha_respuesta,
                    s.fecha_solicitud AS fecha_registro,
                    COALESCE(emp.apnom_emp, '') AS empleado_nombre,
                    e.dni_est,
                    m.per_acad AS ciclo,
                    pe.nom_progest AS programa_nombre
                FROM solicitudes s
                LEFT JOIN empleado emp ON emp.id = s.empleado
                LEFT JOIN estudiante e ON e.id = s.estudiante
                LEFT JOIN matricula m ON m.estudiante = e.id
                LEFT JOIN prog_estudios pe ON pe.id = m.prog_estudios
                WHERE LOWER(TRIM(COALESCE(s.estado, 'Pendiente'))) IN 
                    ('aprobado', 'aprobada', 'approved', 'aprobados', 'aprobadas', 'accept', 'aceptado', 'aceptada', 'aceptados', 'aceptadas')
                ORDER BY COALESCE(s.fecha_revision, s.fecha_solicitud, NOW()) DESC
            ");
            $sql->execute();
            $resultados = $sql->fetchAll(PDO::FETCH_ASSOC);
            
            // Log para diagnóstico
            error_log("listarSolicitudesAprobadas: encontrados " . count($resultados) . " resultados");
            
            return $resultados;
        } catch (PDOException $e) {
            error_log("Error al listar solicitudes aprobadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la lista de beneficiarios con información detallada
     * @return array Lista de beneficiarios con sus datos
     */
    public function listarBeneficiarios() {
        try {
            $sql = $this->db->prepare("\n                SELECT \n                    s.id,\n                    s.nombre,\n                    s.telefono,\n                    s.correo,\n                    s.tipo_solicitud,\n                    s.descripcion,\n                    s.archivos,\n                    s.fecha,\n                    COALESCE(s.estado, 'Pendiente') AS estado,\n                    s.motivo_respuesta,\n                    s.fecha_respuesta,\n                    s.fecha_registro,\n                    COALESCE(emp.apnom_emp, '') AS empleado_nombre,\n                    est.dni_est\n                FROM solicitud s\n                LEFT JOIN empleado emp ON emp.id = s.empleado_id\n                LEFT JOIN estudiante est ON est.id = s.estudiante_id\n                ORDER BY COALESCE(s.fecha_registro, s.fecha, NOW()) DESC\n            ");
            
            $sql->execute();
            return $sql->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en listarBeneficiarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Método original de listar (mantenido para compatibilidad)
     */
    public function listar() {
        return $this->listarBeneficiarios();
    }
}
