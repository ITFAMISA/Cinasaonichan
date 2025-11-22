<?php
/**
 * Modelo para gestionar turnos de trabajo
 */

class TurnosModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtener todos los turnos activos
     */
    public function listarTurnosActivos() {
        try {
            $sql = "SELECT id, nombre, hora_inicio, hora_fin, orden
                    FROM turnos
                    WHERE activo = 1
                    ORDER BY orden ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al listar turnos: " . $e->getMessage());
        }
    }

    /**
     * Obtener un turno específico
     */
    public function obtenerTurno($turnoId) {
        try {
            $sql = "SELECT id, nombre, hora_inicio, hora_fin, orden, activo
                    FROM turnos
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$turnoId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener turno: " . $e->getMessage());
        }
    }

    /**
     * Crear un nuevo turno
     */
    public function crearTurno($nombre, $horaInicio, $horaFin, $orden) {
        try {
            // Validar que no exista un turno con el mismo nombre
            $sqlVerificar = "SELECT id FROM turnos WHERE nombre = ?";
            $stmtVerificar = $this->pdo->prepare($sqlVerificar);
            $stmtVerificar->execute([$nombre]);

            if ($stmtVerificar->rowCount() > 0) {
                throw new Exception("Ya existe un turno con ese nombre");
            }

            $sql = "INSERT INTO turnos (nombre, hora_inicio, hora_fin, orden, activo)
                    VALUES (?, ?, ?, ?, 1)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $horaInicio,
                $horaFin,
                $orden
            ]);

            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al crear turno: " . $e->getMessage());
        }
    }

    /**
     * Actualizar un turno
     */
    public function actualizarTurno($turnoId, $datos) {
        try {
            $updates = [];
            $params = [];

            if (isset($datos['nombre'])) {
                $updates[] = 'nombre = ?';
                $params[] = $datos['nombre'];
            }

            if (isset($datos['hora_inicio'])) {
                $updates[] = 'hora_inicio = ?';
                $params[] = $datos['hora_inicio'];
            }

            if (isset($datos['hora_fin'])) {
                $updates[] = 'hora_fin = ?';
                $params[] = $datos['hora_fin'];
            }

            if (isset($datos['orden'])) {
                $updates[] = 'orden = ?';
                $params[] = $datos['orden'];
            }

            if (isset($datos['activo'])) {
                $updates[] = 'activo = ?';
                $params[] = $datos['activo'];
            }

            if (empty($updates)) {
                throw new Exception("No hay campos para actualizar");
            }

            $params[] = $turnoId;

            $sql = "UPDATE turnos SET " . implode(', ', $updates) . " WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception("Error al actualizar turno: " . $e->getMessage());
        }
    }

    /**
     * Desactivar un turno
     */
    public function desactivarTurno($turnoId) {
        try {
            // Verificar que no hay asignaciones activas
            $sqlVerificar = "SELECT COUNT(*) as count FROM asignaciones_estacion_turno
                            WHERE turno_id = ? AND estatus IN ('pendiente', 'en_progreso')";
            $stmtVerificar = $this->pdo->prepare($sqlVerificar);
            $stmtVerificar->execute([$turnoId]);
            $result = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                throw new Exception("No se puede desactivar un turno que tiene asignaciones activas");
            }

            $sql = "UPDATE turnos SET activo = 0 WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$turnoId]);

            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception("Error al desactivar turno: " . $e->getMessage());
        }
    }
}
?>
