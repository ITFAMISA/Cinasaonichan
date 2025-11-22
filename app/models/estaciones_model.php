<?php

class EstacionesModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ==================== ESTACIONES ====================

    public function listarEstaciones($filtros = [], $orden = 'nombre', $direccion = 'ASC', $limite = 20, $offset = 0) {
        $where = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = trim($filtros['buscar']);
            $where[] = "(nombre LIKE ? OR descripcion LIKE ? OR tipo LIKE ?)";
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
        }

        if (!empty($filtros['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['estatus'])) {
            $where[] = "estatus = ?";
            $params[] = $filtros['estatus'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $columnasPermitidas = ['id', 'nombre', 'tipo', 'estatus', 'fecha_creacion'];
        if (!in_array($orden, $columnasPermitidas)) {
            $orden = 'nombre';
        }

        $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT id, nombre, tipo, nave, descripcion, ubicacion_x, ubicacion_y, ancho, alto,
                       color, estatus, observaciones, fecha_creacion, fecha_actualizacion
                FROM estaciones {$whereClause}
                ORDER BY {$orden} {$direccion}
                LIMIT ? OFFSET ?";

        $params[] = (int)$limite;
        $params[] = (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contarEstaciones($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = trim($filtros['buscar']);
            $where[] = "(nombre LIKE ? OR descripcion LIKE ? OR tipo LIKE ?)";
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
        }

        if (!empty($filtros['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['estatus'])) {
            $where[] = "estatus = ?";
            $params[] = $filtros['estatus'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total FROM estaciones {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch();
        return $resultado['total'];
    }

    public function obtenerEstacionPorId($id) {
        $sql = "SELECT id, nombre, tipo, nave, descripcion, ubicacion_x, ubicacion_y, ancho, alto,
                       color, estatus, observaciones, fecha_creacion, fecha_actualizacion
                FROM estaciones WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function crearEstacion($datos) {
        $sql = "INSERT INTO estaciones (
            nombre, tipo, nave, descripcion, ubicacion_x, ubicacion_y, ancho, alto,
            color, estatus, orden, observaciones, usuario_creacion
        ) VALUES (
            :nombre, :tipo, :nave, :descripcion, :ubicacion_x, :ubicacion_y, :ancho, :alto,
            :color, :estatus, :orden, :observaciones, :usuario_creacion
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($datos);
        return $this->pdo->lastInsertId();
    }

    public function actualizarEstacion($id, $datos) {
        $datos['id'] = $id;

        $sql = "UPDATE estaciones SET
            nombre = :nombre,
            tipo = :tipo,
            nave = :nave,
            descripcion = :descripcion,
            ubicacion_x = :ubicacion_x,
            ubicacion_y = :ubicacion_y,
            ancho = :ancho,
            alto = :alto,
            color = :color,
            estatus = :estatus,
            orden = :orden,
            observaciones = :observaciones
        WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($datos);
    }

    public function actualizarUbicacion($id, $x, $y) {
        $sql = "UPDATE estaciones SET ubicacion_x = :x, ubicacion_y = :y WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':x', $x, PDO::PARAM_INT);
        $stmt->bindValue(':y', $y, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminarEstacion($id) {
        // Hard delete - eliminar de la base de datos
        $sql = "DELETE FROM estaciones WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerTodasEstacionesActivas() {
        $sql = "SELECT id, nombre, tipo, nave, descripcion, ubicacion_x, ubicacion_y, ancho, alto, color, estatus, orden
                FROM estaciones
                WHERE estatus = 'activa'
                ORDER BY nave ASC, orden ASC, nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerTodasEstaciones() {
        $sql = "SELECT id, nombre, tipo, nave, descripcion, ubicacion_x, ubicacion_y, ancho, alto, color, estatus, orden
                FROM estaciones
                ORDER BY nave ASC, orden ASC, nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function actualizarOrdenEstacion($id, $orden) {
        $sql = "UPDATE estaciones SET orden = :orden WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':orden', $orden, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function reordenarEstacionesEnNave($nave, $estaciones_ordenadas) {
        // $estaciones_ordenadas es un array con [id => nuevo_orden, ...]
        try {
            foreach ($estaciones_ordenadas as $estacion_id => $nuevo_orden) {
                $sql = "UPDATE estaciones SET orden = :orden WHERE id = :id AND nave = :nave";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':orden', $nuevo_orden, PDO::PARAM_INT);
                $stmt->bindValue(':id', $estacion_id, PDO::PARAM_INT);
                $stmt->bindValue(':nave', $nave);
                if (!$stmt->execute()) {
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en reordenarEstacionesEnNave: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerProximoOrdenNave($nave) {
        // Obtiene el próximo número de orden para una nave
        $sql = "SELECT MAX(orden) as max_orden FROM estaciones WHERE nave = :nave";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':nave', $nave);
        $stmt->execute();

        $resultado = $stmt->fetch();
        $max_orden = $resultado['max_orden'] ?? 0;

        // Si max_orden es NULL o 0, devuelve 0; si no, devuelve max_orden + 1
        return (int)$max_orden + 1;
    }

    // ==================== ESTACION PROCESOS (M:M) ====================

    public function obtenerProcessosEstacion($estacion_id) {
        $sql = "SELECT ep.id, ep.estacion_id, ep.proceso_id, ep.es_preferida, ep.orden_preferencia,
                       ep.notas, ep.estatus, p.nombre as proceso_nombre, p.descripcion as proceso_descripcion
                FROM estacion_procesos ep
                INNER JOIN procesos p ON ep.proceso_id = p.id
                WHERE ep.estacion_id = :estacion_id AND ep.estatus = 'activo'
                ORDER BY ep.orden_preferencia ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':estacion_id', $estacion_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerEstacionesPorProceso($proceso_id) {
        $sql = "SELECT ep.id, ep.estacion_id, ep.proceso_id, ep.es_preferida, ep.orden_preferencia,
                       ep.notas, ep.estatus, e.nombre, e.tipo, e.ubicacion_x, e.ubicacion_y, e.color
                FROM estacion_procesos ep
                INNER JOIN estaciones e ON ep.estacion_id = e.id
                WHERE ep.proceso_id = :proceso_id AND ep.estatus = 'activo' AND e.estatus = 'activa'
                ORDER BY ep.es_preferida DESC, ep.orden_preferencia ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function asignarProcesoAEstacion($estacion_id, $proceso_id, $es_preferida = false, $orden_preferencia = 999, $notas = '') {
        // Verificar que la estación y proceso existan
        $sqlVerificar = "SELECT id FROM estaciones WHERE id = :estacion_id";
        $stmtVerificar = $this->pdo->prepare($sqlVerificar);
        $stmtVerificar->bindValue(':estacion_id', $estacion_id, PDO::PARAM_INT);
        $stmtVerificar->execute();

        if (!$stmtVerificar->fetch()) {
            error_log("Estación $estacion_id no encontrada");
            return false;
        }

        $sqlVerificar2 = "SELECT id FROM procesos WHERE id = :proceso_id AND estatus = 'activo'";
        $stmtVerificar2 = $this->pdo->prepare($sqlVerificar2);
        $stmtVerificar2->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);
        $stmtVerificar2->execute();

        if (!$stmtVerificar2->fetch()) {
            error_log("Proceso $proceso_id no encontrado o inactivo");
            return false;
        }

        $sql = "INSERT INTO estacion_procesos (
            estacion_id, proceso_id, es_preferida, orden_preferencia, notas, estatus, usuario_creacion
        ) VALUES (
            :estacion_id, :proceso_id, :es_preferida, :orden_preferencia, :notas, 'activo', :usuario_id
        ) ON DUPLICATE KEY UPDATE
            es_preferida = VALUES(es_preferida),
            orden_preferencia = VALUES(orden_preferencia),
            notas = VALUES(notas),
            estatus = 'activo'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':estacion_id', $estacion_id, PDO::PARAM_INT);
        $stmt->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);
        $stmt->bindValue(':es_preferida', $es_preferida ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':orden_preferencia', $orden_preferencia, PDO::PARAM_INT);
        $stmt->bindValue(':notas', $notas);
        $stmt->bindValue(':usuario_id', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function actualizarEstacionProceso($estacion_proceso_id, $es_preferida, $orden_preferencia, $notas = '') {
        $sql = "UPDATE estacion_procesos SET
            es_preferida = :es_preferida,
            orden_preferencia = :orden_preferencia,
            notas = :notas
        WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $estacion_proceso_id, PDO::PARAM_INT);
        $stmt->bindValue(':es_preferida', $es_preferida ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':orden_preferencia', $orden_preferencia, PDO::PARAM_INT);
        $stmt->bindValue(':notas', $notas);

        return $stmt->execute();
    }

    public function eliminarEstacionProceso($estacion_proceso_id) {
        // Hard delete - eliminar de la base de datos
        $sql = "DELETE FROM estacion_procesos WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $estacion_proceso_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerEstacionPreferidaProceso($proceso_id) {
        $sql = "SELECT ep.id, ep.estacion_id, e.nombre, e.ubicacion_x, e.ubicacion_y
                FROM estacion_procesos ep
                INNER JOIN estaciones e ON ep.estacion_id = e.id
                WHERE ep.proceso_id = :proceso_id AND ep.es_preferida = 1 AND ep.estatus = 'activo' AND e.estatus = 'activa'
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    // ==================== ASIGNACIONES ESTACIONES ====================

    public function crearAsignacion($datos) {
        $sql = "INSERT INTO asignaciones_estaciones (
            estacion_id, pedido_id, producto_id, proceso_id, numero_pedido,
            cantidad_total, cantidad_procesada, fecha_fin_estimada,
            estatus, observaciones, empleado_id, usuario_creacion
        ) VALUES (
            :estacion_id, :pedido_id, :producto_id, :proceso_id, :numero_pedido,
            :cantidad_total, :cantidad_procesada, :fecha_fin_estimada,
            :estatus, :observaciones, :empleado_id, :usuario_creacion
        )";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($datos) ? $this->pdo->lastInsertId() : false;
    }

    public function obtenerAsignacionPorId($id) {
        $sql = "SELECT ae.*, e.nombre as estacion_nombre, e.tipo as estacion_tipo,
                       p.nombre as proceso_nombre, pr.material_code as producto_codigo, pr.descripcion as producto_descripcion
                FROM asignaciones_estaciones ae
                LEFT JOIN estaciones e ON ae.estacion_id = e.id
                LEFT JOIN procesos p ON ae.proceso_id = p.id
                LEFT JOIN productos pr ON ae.producto_id = pr.id
                WHERE ae.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function obtenerAsignacionesEstacion($estacion_id, $filtros = [], $limite = 50, $offset = 0) {
        $where = "WHERE ae.estacion_id = :estacion_id";
        $params = [':estacion_id' => $estacion_id];

        if (!empty($filtros['estatus'])) {
            $where .= " AND ae.estatus = :estatus";
            $params[':estatus'] = $filtros['estatus'];
        }

        $sql = "SELECT ae.id, ae.pedido_id, ae.numero_pedido, ae.producto_id, ae.proceso_id,
                       ae.cantidad_total, ae.cantidad_procesada, ae.cantidad_pendiente,
                       ae.fecha_asignacion, ae.fecha_fin_estimada, ae.estatus,
                       e.nombre as estacion_nombre, p.nombre as proceso_nombre,
                       pr.material_code, pr.descripcion
                FROM asignaciones_estaciones ae
                LEFT JOIN estaciones e ON ae.estacion_id = e.id
                LEFT JOIN procesos p ON ae.proceso_id = p.id
                LEFT JOIN productos pr ON ae.producto_id = pr.id
                {$where}
                ORDER BY
                    CASE ae.estatus
                        WHEN 'en_progreso' THEN 1
                        WHEN 'pendiente' THEN 2
                        WHEN 'pausada' THEN 3
                        ELSE 4
                    END,
                    ae.fecha_asignacion ASC
                LIMIT ? OFFSET ?";

        $params[] = (int)$limite;
        $params[] = (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function obtenerAsignacionActualEstacion($estacion_id) {
        $sql = "SELECT ae.id, ae.pedido_id, ae.numero_pedido, ae.producto_id, ae.proceso_id,
                       ae.cantidad_total, ae.cantidad_procesada, ae.cantidad_pendiente,
                       ae.fecha_asignacion, ae.fecha_fin_estimada, ae.estatus,
                       p.nombre as proceso_nombre, pr.material_code, pr.descripcion
                FROM asignaciones_estaciones ae
                LEFT JOIN procesos p ON ae.proceso_id = p.id
                LEFT JOIN productos pr ON ae.producto_id = pr.id
                WHERE ae.estacion_id = :estacion_id AND ae.estatus IN ('en_progreso', 'pendiente')
                ORDER BY ae.estatus = 'en_progreso' DESC, ae.fecha_asignacion ASC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':estacion_id', $estacion_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function actualizarAsignacion($id, $datos) {
        $datos['id'] = $id;

        $sql = "UPDATE asignaciones_estaciones SET
            cantidad_procesada = :cantidad_procesada,
            fecha_fin_estimada = :fecha_fin_estimada,
            estatus = :estatus,
            observaciones = :observaciones,
            empleado_id = :empleado_id
        WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($datos);
    }

    public function actualizarEstadoAsignacion($id, $estatus, $cantidad_procesada = null) {
        $datos = ['id' => $id, 'estatus' => $estatus];

        if ($estatus === 'en_progreso' && is_null($cantidad_procesada)) {
            $sql = "UPDATE asignaciones_estaciones SET
                estatus = :estatus,
                fecha_inicio_real = CASE WHEN fecha_inicio_real IS NULL THEN NOW() ELSE fecha_inicio_real END
            WHERE id = :id";
        } elseif ($estatus === 'completada') {
            $sql = "UPDATE asignaciones_estaciones SET
                estatus = :estatus,
                fecha_inicio_real = CASE WHEN fecha_inicio_real IS NULL THEN NOW() ELSE fecha_inicio_real END,
                fecha_fin_real = NOW()
            WHERE id = :id";
        } elseif (!is_null($cantidad_procesada)) {
            $datos['cantidad_procesada'] = $cantidad_procesada;
            $sql = "UPDATE asignaciones_estaciones SET
                cantidad_procesada = :cantidad_procesada,
                estatus = :estatus
            WHERE id = :id";
        } else {
            $sql = "UPDATE asignaciones_estaciones SET estatus = :estatus WHERE id = :id";
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($datos);
    }

    public function cancelarAsignacion($id) {
        $sql = "UPDATE asignaciones_estaciones SET estatus = 'cancelada' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerAsignacionesActivasGlobal() {
        $sql = "SELECT ae.id, ae.estacion_id, ae.pedido_id, ae.numero_pedido, ae.producto_id, ae.proceso_id,
                       ae.cantidad_total, ae.cantidad_procesada, ae.cantidad_pendiente,
                       ae.estatus, ae.fecha_asignacion, ae.fecha_fin_estimada,
                       e.nombre as estacion_nombre, e.ubicacion_x, e.ubicacion_y, e.color,
                       p.nombre as proceso_nombre, pr.material_code, pr.descripcion
                FROM asignaciones_estaciones ae
                LEFT JOIN estaciones e ON ae.estacion_id = e.id
                LEFT JOIN procesos p ON ae.proceso_id = p.id
                LEFT JOIN productos pr ON ae.producto_id = pr.id
                WHERE ae.estatus IN ('en_progreso', 'pendiente', 'pausada')
                ORDER BY ae.estatus = 'en_progreso' DESC, ae.fecha_asignacion ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerEstacionesConAsignacionesActuales() {
        $sql = "SELECT DISTINCT e.id, e.nombre, e.tipo, e.ubicacion_x, e.ubicacion_y, e.color, e.ancho, e.alto, e.estatus,
                       (SELECT COUNT(*) FROM asignaciones_estaciones WHERE estacion_id = e.id AND estatus IN ('en_progreso', 'pendiente')) as cantidad_pendientes
                FROM estaciones e
                WHERE EXISTS (
                    SELECT 1 FROM asignaciones_estaciones ae
                    WHERE ae.estacion_id = e.id AND ae.estatus IN ('en_progreso', 'pendiente')
                )
                ORDER BY e.ubicacion_x ASC, e.ubicacion_y ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerEstadisticasEstacion($estacion_id) {
        $sql = "SELECT
                COUNT(CASE WHEN estatus = 'en_progreso' THEN 1 END) as en_progreso,
                COUNT(CASE WHEN estatus = 'pendiente' THEN 1 END) as pendiente,
                COUNT(CASE WHEN estatus = 'pausada' THEN 1 END) as pausada,
                COUNT(CASE WHEN estatus = 'completada' THEN 1 END) as completada,
                COUNT(CASE WHEN estatus = 'cancelada' THEN 1 END) as cancelada,
                COUNT(*) as total
                FROM asignaciones_estaciones
                WHERE estacion_id = :estacion_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':estacion_id', $estacion_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function obtenerTiposEstaciones() {
        $sql = "SELECT DISTINCT tipo FROM estaciones ORDER BY tipo ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
