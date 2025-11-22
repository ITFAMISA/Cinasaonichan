<?php

class ProcesosModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ==================== PROCESOS ====================

    public function listarProcesos($filtros = [], $orden = 'nombre', $direccion = 'ASC', $limite = 20, $offset = 0) {
        $where = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = trim($filtros['buscar']);
            $where[] = "(nombre LIKE ? OR descripcion LIKE ?)";
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
        }

        if (!empty($filtros['estatus'])) {
            $where[] = "estatus = ?";
            $params[] = $filtros['estatus'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $columnasPermitidas = ['id', 'nombre', 'descripcion', 'requiere_inspeccion_calidad', 'estatus', 'fecha_creacion'];
        if (!in_array($orden, $columnasPermitidas)) {
            $orden = 'nombre';
        }

        $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT id, nombre, descripcion, requiere_inspeccion_calidad, estatus, fecha_creacion, fecha_actualizacion
                FROM procesos {$whereClause}
                ORDER BY {$orden} {$direccion}
                LIMIT ? OFFSET ?";

        $params[] = (int)$limite;
        $params[] = (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contarProcesos($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = trim($filtros['buscar']);
            $where[] = "(nombre LIKE ? OR descripcion LIKE ?)";
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
        }

        if (!empty($filtros['estatus'])) {
            $where[] = "estatus = ?";
            $params[] = $filtros['estatus'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total FROM procesos {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch();
        return $resultado['total'];
    }

    public function obtenerProcesoPorId($id) {
        $sql = "SELECT id, nombre, descripcion, requiere_inspeccion_calidad, estatus, fecha_creacion, fecha_actualizacion
                FROM procesos WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function crearProceso($datos) {
        // Asegurar que requiere_inspeccion_calidad es booleano
        $datos['requiere_inspeccion_calidad'] = isset($datos['requiere_inspeccion_calidad']) && $datos['requiere_inspeccion_calidad'] ? 1 : 0;

        $sql = "INSERT INTO procesos (
            nombre, descripcion, requiere_inspeccion_calidad, estatus, usuario_creacion
        ) VALUES (
            :nombre, :descripcion, :requiere_inspeccion_calidad, :estatus, :usuario_creacion
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($datos);
        return $this->pdo->lastInsertId();
    }

    public function actualizarProceso($id, $datos) {
        // Asegurar que requiere_inspeccion_calidad es booleano
        $datos['requiere_inspeccion_calidad'] = isset($datos['requiere_inspeccion_calidad']) && $datos['requiere_inspeccion_calidad'] ? 1 : 0;

        $datos['id'] = $id;

        $sql = "UPDATE procesos SET
            nombre = :nombre,
            descripcion = :descripcion,
            requiere_inspeccion_calidad = :requiere_inspeccion_calidad,
            estatus = :estatus
        WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($datos);
    }

    public function eliminarProceso($id) {
        // Soft delete - cambiar estatus a inactivo
        $sql = "UPDATE procesos SET estatus = 'inactivo' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerTodosProcesosActivos() {
        $sql = "SELECT id, nombre, descripcion, requiere_inspeccion_calidad
                FROM procesos
                WHERE estatus = 'activo'
                ORDER BY nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ==================== RUTAS DE PROCESOS POR PRODUCTO ====================

    public function obtenerRutaProcesosProducto($producto_id) {
        $sql = "SELECT prp.id, prp.producto_id, prp.proceso_id, prp.orden_secuencia,
                       prp.notas, prp.estatus, p.nombre as proceso_nombre,
                       p.descripcion as proceso_descripcion, p.requiere_inspeccion_calidad
                FROM producto_rutas_procesos prp
                INNER JOIN procesos p ON prp.proceso_id = p.id
                WHERE prp.producto_id = :producto_id AND prp.estatus = 'activo'
                ORDER BY prp.orden_secuencia ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function asignarProcesoAProducto($producto_id, $proceso_id, $orden_secuencia, $notas = '') {
        // Verificar que el producto y proceso existan
        $sqlVerificar = "SELECT id FROM productos WHERE id = :producto_id AND estatus != 'descontinuado'";
        $stmtVerificar = $this->pdo->prepare($sqlVerificar);
        $stmtVerificar->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
        $stmtVerificar->execute();

        if (!$stmtVerificar->fetch()) {
            error_log("Producto $producto_id no encontrado o descontinuado");
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

        $sql = "INSERT INTO producto_rutas_procesos (
            producto_id, proceso_id, orden_secuencia, notas, estatus, usuario_creacion
        ) VALUES (
            :producto_id, :proceso_id, :orden_secuencia, :notas, 'activo', :usuario_id
        ) ON DUPLICATE KEY UPDATE
            orden_secuencia = VALUES(orden_secuencia),
            notas = VALUES(notas),
            estatus = 'activo'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
        $stmt->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);
        $stmt->bindValue(':orden_secuencia', $orden_secuencia, PDO::PARAM_INT);
        $stmt->bindValue(':notas', $notas);
        $stmt->bindValue(':usuario_id', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

        $resultado = $stmt->execute();
        error_log("Query ejecutado: $sql");
        error_log("Rows afectados: " . $stmt->rowCount());

        return $resultado;
    }

    public function actualizarRutaProceso($ruta_id, $orden_secuencia, $notas = '') {
        $sql = "UPDATE producto_rutas_procesos SET
            orden_secuencia = :orden_secuencia,
            notas = :notas
        WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $ruta_id, PDO::PARAM_INT);
        $stmt->bindValue(':orden_secuencia', $orden_secuencia, PDO::PARAM_INT);
        $stmt->bindValue(':notas', $notas);

        return $stmt->execute();
    }

    public function eliminarRutaProceso($ruta_id) {
        $sql = "UPDATE producto_rutas_procesos SET estatus = 'inactivo' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $ruta_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerRutaProcesosPorProducto($producto_id) {
        $sql = "SELECT prp.id, prp.proceso_id, prp.orden_secuencia, prp.notas, prp.estatus,
                       p.nombre, p.descripcion, p.requiere_inspeccion_calidad
                FROM producto_rutas_procesos prp
                INNER JOIN procesos p ON prp.proceso_id = p.id
                WHERE prp.producto_id = :producto_id AND prp.estatus = 'activo'
                ORDER BY prp.orden_secuencia ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contarProcessosAsignadosAProducto($producto_id) {
        $sql = "SELECT COUNT(*) as total FROM producto_rutas_procesos
                WHERE producto_id = :producto_id AND estatus = 'activo'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch();
        return $resultado['total'];
    }
}
