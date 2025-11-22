<?php
/**
 * Modelo para gestionar asignaciones de empleados a estaciones por turno
 */

class AsignacionesTurnoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Crear una nueva asignación
     */
    public function crear($datos) {
        try {
            // Validar campos requeridos
            $camposRequeridos = ['estacion_id', 'turno_id', 'empleado_id', 'pedido_id', 'tipo_trabajo_id', 'cantidad_total'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($datos[$campo]) || empty($datos[$campo])) {
                    throw new Exception("Campo requerido faltante: $campo");
                }
            }

            // Validar que el empleado no tenga otra asignación en el mismo turno
            $sqlVerificar = "SELECT id FROM asignaciones_estacion_turno
                            WHERE empleado_id = ? AND turno_id = ? AND estatus IN ('pendiente', 'en_progreso')";
            $stmtVerificar = $this->pdo->prepare($sqlVerificar);
            $stmtVerificar->execute([$datos['empleado_id'], $datos['turno_id']]);

            if ($stmtVerificar->rowCount() > 0) {
                throw new Exception("Este empleado ya tiene una asignación activa en este turno");
            }

            $sql = "INSERT INTO asignaciones_estacion_turno
                    (estacion_id, turno_id, empleado_id, pedido_id, tipo_trabajo_id, cantidad_total, estatus)
                    VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $datos['estacion_id'],
                $datos['turno_id'],
                $datos['empleado_id'],
                $datos['pedido_id'],
                $datos['tipo_trabajo_id'],
                $datos['cantidad_total']
            ]);

            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Obtener asignaciones activas por estación y turno
     */
    public function listarPorEstacionTurno($estacionId, $turnoId) {
        try {
            $sql = "SELECT aet.*,
                           e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                           p.numero_pedido, p.razon_social,
                           t.nombre as turno_nombre,
                           est.nombre as estacion_nombre,
                           tt.nombre as tipo_trabajo_nombre, tt.color as tipo_trabajo_color
                    FROM asignaciones_estacion_turno aet
                    JOIN empleados e ON aet.empleado_id = e.id
                    JOIN pedidos p ON aet.pedido_id = p.id
                    JOIN turnos t ON aet.turno_id = t.id
                    JOIN estaciones est ON aet.estacion_id = est.id
                    LEFT JOIN tipos_trabajo tt ON aet.tipo_trabajo_id = tt.id
                    WHERE aet.estacion_id = ? AND aet.turno_id = ? AND aet.estatus IN ('pendiente', 'en_progreso')
                    ORDER BY aet.fecha_asignacion";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$estacionId, $turnoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al listar asignaciones: " . $e->getMessage());
        }
    }

    /**
     * Obtener todas las asignaciones activas
     */
    public function listarActivas() {
        try {
            $sql = "SELECT aet.*,
                           e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                           p.numero_pedido, p.razon_social,
                           t.nombre as turno_nombre, t.hora_inicio, t.hora_fin,
                           est.nombre as estacion_nombre,
                           tt.nombre as tipo_trabajo_nombre, tt.color as tipo_trabajo_color
                    FROM asignaciones_estacion_turno aet
                    JOIN empleados e ON aet.empleado_id = e.id
                    JOIN pedidos p ON aet.pedido_id = p.id
                    JOIN turnos t ON aet.turno_id = t.id
                    JOIN estaciones est ON aet.estacion_id = est.id
                    LEFT JOIN tipos_trabajo tt ON aet.tipo_trabajo_id = tt.id
                    WHERE aet.estatus IN ('pendiente', 'en_progreso')
                    ORDER BY est.id, t.orden, aet.fecha_asignacion";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al listar asignaciones: " . $e->getMessage());
        }
    }

    /**
     * Obtener una asignación específica
     */
    public function obtener($asignacionId) {
        try {
            $sql = "SELECT aet.*,
                           e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                           p.numero_pedido,
                           t.nombre as turno_nombre
                    FROM asignaciones_estacion_turno aet
                    JOIN empleados e ON aet.empleado_id = e.id
                    JOIN pedidos p ON aet.pedido_id = p.id
                    JOIN turnos t ON aet.turno_id = t.id
                    WHERE aet.id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$asignacionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener asignación: " . $e->getMessage());
        }
    }

    /**
     * Actualizar asignación
     */
    public function actualizar($asignacionId, $datos) {
        try {
            $updates = [];
            $params = [];

            if (isset($datos['cantidad_procesada'])) {
                $updates[] = 'cantidad_procesada = ?';
                $params[] = $datos['cantidad_procesada'];
            }

            if (isset($datos['estatus'])) {
                $updates[] = 'estatus = ?';
                $params[] = $datos['estatus'];

                // Actualizar fechas según el estatus
                if ($datos['estatus'] === 'en_progreso') {
                    $updates[] = 'fecha_inicio = NOW()';
                } elseif ($datos['estatus'] === 'completado') {
                    $updates[] = 'fecha_fin = NOW()';
                }
            }

            if (isset($datos['notas'])) {
                $updates[] = 'notas = ?';
                $params[] = $datos['notas'];
            }

            if (empty($updates)) {
                throw new Exception("No hay campos para actualizar");
            }

            $params[] = $asignacionId;

            $sql = "UPDATE asignaciones_estacion_turno
                    SET " . implode(', ', $updates) . "
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception("Error al actualizar asignación: " . $e->getMessage());
        }
    }

    /**
     * Cancelar asignación
     */
    public function cancelar($asignacionId) {
        try {
            $sql = "UPDATE asignaciones_estacion_turno
                    SET estatus = 'cancelado'
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$asignacionId]);

            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception("Error al cancelar asignación: " . $e->getMessage());
        }
    }
}
?>
