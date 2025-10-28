<?php

class CalidadModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ==================== GESTIÓN DE DEFECTOS ====================

    // Obtener todos los defectos activos
    public function obtenerDefectos() {
        try {
            // Intentar con columna estado
            $sql = "SELECT * FROM defectos WHERE estado = 'activo' ORDER BY nombre";
            $stmt = $this->pdo->query($sql);
            $defectos = $stmt->fetchAll();

            // Si retorna resultados, devolver
            if (!empty($defectos)) {
                return $defectos;
            }

            // Si no hay resultados con filtro estado, obtener todos
            $sql = "SELECT * FROM defectos ORDER BY nombre";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            // Si la tabla no existe o hay error, retornar vacío
            error_log('Error obtener defectos: ' . $e->getMessage());
            return [];
        }
    }

    // Obtener defecto por ID
    public function obtenerDefectoPorId($id) {
        $sql = "SELECT * FROM defectos WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Crear defecto
    public function crearDefecto($datos) {
        $sql = "INSERT INTO defectos (codigo, nombre, descripcion)
                VALUES (:codigo, :nombre, :descripcion)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($datos);
    }

    // ==================== GESTIÓN DE PIEZAS PRODUCIDAS ====================

    // Generar folio único para una pieza
    public function generarFolioPieza($fecha = null) {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }

        // Formato: PROD-YYYY-MM-DD-XXXXX
        $fecha_formateada = date('Y-m-d', strtotime($fecha));

        // Obtener el siguiente número secuencial para esta fecha usando FOR UPDATE para evitar race conditions
        $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(folio_pieza, -5) AS UNSIGNED)), 0) as ultimo_numero
                FROM piezas_producidas
                WHERE folio_pieza LIKE :patron
                FOR UPDATE";
        $stmt = $this->pdo->prepare($sql);
        $patron = 'PROD-' . $fecha_formateada . '-%';
        $stmt->bindValue(':patron', $patron);
        $stmt->execute();
        $resultado = $stmt->fetch();
        $numero = ($resultado['ultimo_numero'] ?? 0) + 1;

        $folio = 'PROD-' . $fecha_formateada . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);

        return $folio;
    }

    // Crear pieza producida
    public function crearPiezaProducida($datos) {
        $sql = "INSERT INTO piezas_producidas (
            folio_pieza, produccion_id, pedido_id, producto_id, numero_pedido,
            item_code, descripcion, supervisor_produccion, fecha_produccion, estatus
        ) VALUES (
            :folio_pieza, :produccion_id, :pedido_id, :producto_id, :numero_pedido,
            :item_code, :descripcion, :supervisor_produccion, :fecha_produccion, :estatus
        )";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($datos);
    }

    // Crear múltiples piezas producidas (lote)
    public function crearPiezasProducidas($produccion_id, $cantidad, $supervisor = null) {
        try {
            $this->pdo->beginTransaction();

            // Obtener datos de producción
            $produccion = $this->obtenerDetallesProduccion($produccion_id);

            if (!$produccion) {
                throw new Exception('Producción ID ' . $produccion_id . ' no encontrada');
            }

            // Si no se proporciona supervisor, obtener del historial más reciente
            if (empty($supervisor)) {
                $sql_super = "SELECT supervisor FROM produccion_historial
                             WHERE produccion_id = :produccion_id
                             ORDER BY fecha_registro DESC LIMIT 1";
                $stmt_super = $this->pdo->prepare($sql_super);
                $stmt_super->bindValue(':produccion_id', $produccion_id, PDO::PARAM_INT);
                $stmt_super->execute();
                $historial = $stmt_super->fetch();
                $supervisor = $historial['supervisor'] ?? 'Sin asignar';
            }

            $piezas_creadas = [];

            for ($i = 0; $i < $cantidad; $i++) {
                $folio = $this->generarFolioPieza();

                $datos = [
                    ':folio_pieza' => $folio,
                    ':produccion_id' => $produccion_id,
                    ':pedido_id' => $produccion['pedido_id'],
                    ':producto_id' => $produccion['producto_id'],
                    ':numero_pedido' => $produccion['numero_pedido'],
                    ':item_code' => $produccion['item_code'],
                    ':descripcion' => $produccion['descripcion'],
                    ':supervisor_produccion' => $supervisor,
                    ':fecha_produccion' => date('Y-m-d'),
                    ':estatus' => 'por_inspeccionar'
                ];

                $sql = "INSERT INTO piezas_producidas (
                    folio_pieza, produccion_id, pedido_id, producto_id, numero_pedido,
                    item_code, descripcion, supervisor_produccion, fecha_produccion, estatus
                ) VALUES (
                    :folio_pieza, :produccion_id, :pedido_id, :producto_id, :numero_pedido,
                    :item_code, :descripcion, :supervisor_produccion, :fecha_produccion, :estatus
                )";

                $stmt = $this->pdo->prepare($sql);
                if (!$stmt->execute($datos)) {
                    $errorInfo = $stmt->errorInfo();
                    throw new Exception('Error al crear pieza ' . ($i + 1) . ' de ' . $cantidad .
                                      ' (folio: ' . $folio . '): ' . $errorInfo[2]);
                }

                $piezas_creadas[] = $folio;
            }

            $this->pdo->commit();
            return $piezas_creadas;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception('Error creando piezas para producción ID ' . $produccion_id . ': ' . $e->getMessage());
        }
    }

    // Obtener pieza por folio
    public function obtenerPiezaPorFolio($folio) {
        $sql = "SELECT p.*, cl.razon_social as cliente, prod.especificaciones
                FROM piezas_producidas p
                LEFT JOIN produccion prod_t ON p.produccion_id = prod_t.id
                LEFT JOIN pedidos ped ON p.pedido_id = ped.id
                LEFT JOIN clientes cl ON ped.cliente_id = cl.id
                LEFT JOIN productos prod ON p.producto_id = prod.id
                WHERE p.folio_pieza = :folio";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':folio', $folio);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Obtener detalles de producción
    private function obtenerDetallesProduccion($produccion_id) {
        $sql = "SELECT p.* FROM produccion p WHERE p.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $produccion_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // ==================== INSPECCIONES ====================

    // Listar piezas por inspeccionar con filtros
    public function listarPiezasPorInspeccionar($filtros = [], $orden = 'fecha_produccion', $direccion = 'DESC', $limite = 20, $offset = 0) {
        try {
            $where = ["pp.estatus IN ('por_inspeccionar', 'pendiente_reinspeccion')"];
            $params = [];

            // Filtro por fecha
            if (!empty($filtros['fecha'])) {
                $where[] = "DATE(pp.fecha_produccion) = :fecha";
                $params[':fecha'] = $filtros['fecha'];
            }

            // Filtro por fecha desde
            if (!empty($filtros['fecha_desde'])) {
                $where[] = "DATE(pp.fecha_produccion) >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }

            // Filtro por fecha hasta
            if (!empty($filtros['fecha_hasta'])) {
                $where[] = "DATE(pp.fecha_produccion) <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }

            // Filtro por item_code
            if (!empty($filtros['item_code'])) {
                $where[] = "pp.item_code LIKE :item_code";
                $params[':item_code'] = '%' . $filtros['item_code'] . '%';
            }

            // Filtro por supervisor
            if (!empty($filtros['supervisor'])) {
                $where[] = "pp.supervisor_produccion = :supervisor";
                $params[':supervisor'] = $filtros['supervisor'];
            }

            // Filtro por cliente
            if (!empty($filtros['cliente_id'])) {
                $where[] = "ped.cliente_id = :cliente_id";
                $params[':cliente_id'] = $filtros['cliente_id'];
            }

            // Filtro por búsqueda general
            if (!empty($filtros['buscar'])) {
                $buscar = '%' . trim($filtros['buscar']) . '%';
                $where[] = "(pp.folio_pieza LIKE :buscar OR pp.numero_pedido LIKE :buscar2 OR pp.item_code LIKE :buscar3)";
                $params[':buscar'] = $buscar;
                $params[':buscar2'] = $buscar;
                $params[':buscar3'] = $buscar;
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $columnasPermitidas = ['folio_pieza', 'fecha_produccion', 'item_code', 'numero_pedido', 'supervisor_produccion'];
            if (!in_array($orden, $columnasPermitidas)) {
                $orden = 'fecha_produccion';
            }

            $direccion = strtoupper($direccion) === 'ASC' ? 'ASC' : 'DESC';

            $sql = "SELECT pp.*, cl.razon_social as cliente
                    FROM piezas_producidas pp
                    LEFT JOIN produccion prod ON pp.produccion_id = prod.id
                    LEFT JOIN pedidos ped ON pp.pedido_id = ped.id
                    LEFT JOIN clientes cl ON ped.cliente_id = cl.id
                    {$whereClause}
                    ORDER BY pp.{$orden} {$direccion}
                    LIMIT :limite OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $valor) {
                if (strpos($key, 'limite') !== false || strpos($key, 'offset') !== false) {
                    $stmt->bindValue($key, $valor, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $valor);
                }
            }

            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log('Error listando piezas: ' . $e->getMessage());
            return [];
        }
    }

    // Contar piezas por inspeccionar
    public function contarPiezasPorInspeccionar($filtros = []) {
        try {
            $where = ["pp.estatus IN ('por_inspeccionar', 'pendiente_reinspeccion')"];
            $params = [];

            if (!empty($filtros['fecha'])) {
                $where[] = "DATE(pp.fecha_produccion) = :fecha";
                $params[':fecha'] = $filtros['fecha'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $where[] = "DATE(pp.fecha_produccion) >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $where[] = "DATE(pp.fecha_produccion) <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }

            if (!empty($filtros['item_code'])) {
                $where[] = "pp.item_code LIKE :item_code";
                $params[':item_code'] = '%' . $filtros['item_code'] . '%';
            }

            if (!empty($filtros['supervisor'])) {
                $where[] = "pp.supervisor_produccion = :supervisor";
                $params[':supervisor'] = $filtros['supervisor'];
            }

            if (!empty($filtros['cliente_id'])) {
                $where[] = "ped.cliente_id = :cliente_id";
                $params[':cliente_id'] = $filtros['cliente_id'];
            }

            if (!empty($filtros['buscar'])) {
                $buscar = '%' . trim($filtros['buscar']) . '%';
                $where[] = "(pp.folio_pieza LIKE :buscar OR pp.numero_pedido LIKE :buscar2 OR pp.item_code LIKE :buscar3)";
                $params[':buscar'] = $buscar;
                $params[':buscar2'] = $buscar;
                $params[':buscar3'] = $buscar;
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT COUNT(*) as total
                    FROM piezas_producidas pp
                    LEFT JOIN produccion prod ON pp.produccion_id = prod.id
                    LEFT JOIN pedidos ped ON pp.pedido_id = ped.id
                    LEFT JOIN clientes cl ON ped.cliente_id = cl.id
                    {$whereClause}";

            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $valor) {
                $stmt->bindValue($key, $valor);
            }

            $stmt->execute();
            $resultado = $stmt->fetch();
            return $resultado['total'] ?? 0;

        } catch (Exception $e) {
            error_log('Error contando piezas: ' . $e->getMessage());
            return 0;
        }
    }

    // Registrar inspección
    public function registrarInspeccion($datos) {
        try {
            $this->pdo->beginTransaction();

            // Validar que aceptada + rechazada = inspeccionada
            $aceptada = (float)$datos['cantidad_aceptada'];
            $rechazada = (float)$datos['cantidad_rechazada'];
            $inspeccionada = (float)$datos['cantidad_inspeccionada'];

            if (abs(($aceptada + $rechazada) - $inspeccionada) > 0.01) {
                throw new Exception('La suma de aceptadas y rechazadas debe ser igual a inspeccionadas');
            }

            // Obtener pieza para validar
            $folio = $datos['folio_pieza'];
            $pieza = $this->obtenerPiezaPorFolio($folio);

            if (!$pieza) {
                throw new Exception('Pieza no encontrada');
            }

            // Insertar inspección
            $sql = "INSERT INTO calidad_inspecciones (
                folio_pieza, produccion_id, pedido_id, producto_id, numero_pedido, item_code,
                supervisor_produccion, inspector_calidad, cantidad_inspeccionada, cantidad_aceptada,
                cantidad_rechazada, defectos, observaciones, estatus
            ) VALUES (
                :folio_pieza, :produccion_id, :pedido_id, :producto_id, :numero_pedido, :item_code,
                :supervisor_produccion, :inspector_calidad, :cantidad_inspeccionada, :cantidad_aceptada,
                :cantidad_rechazada, :defectos, :observaciones, 'completa'
            )";

            $stmt = $this->pdo->prepare($sql);
            $defectos_json = !empty($datos['defectos']) ? json_encode($datos['defectos']) : null;

            $stmt->bindValue(':folio_pieza', $folio);
            $stmt->bindValue(':produccion_id', $pieza['produccion_id'], PDO::PARAM_INT);
            $stmt->bindValue(':pedido_id', $pieza['pedido_id'], PDO::PARAM_INT);
            $stmt->bindValue(':producto_id', $pieza['producto_id'], PDO::PARAM_INT);
            $stmt->bindValue(':numero_pedido', $pieza['numero_pedido']);
            $stmt->bindValue(':item_code', $pieza['item_code']);
            $stmt->bindValue(':supervisor_produccion', $pieza['supervisor_produccion']);
            $stmt->bindValue(':inspector_calidad', $datos['inspector_calidad']);
            $stmt->bindValue(':cantidad_inspeccionada', $inspeccionada, PDO::PARAM_STR);
            $stmt->bindValue(':cantidad_aceptada', $aceptada, PDO::PARAM_STR);
            $stmt->bindValue(':cantidad_rechazada', $rechazada, PDO::PARAM_STR);
            $stmt->bindValue(':defectos', $defectos_json);
            $stmt->bindValue(':observaciones', $datos['observaciones'] ?? null);

            if (!$stmt->execute()) {
                throw new Exception('Error al registrar inspección');
            }

            $inspeccion_id = $this->pdo->lastInsertId();

            // Registrar defectos si existen
            if (!empty($datos['defectos']) && is_array($datos['defectos'])) {
                foreach ($datos['defectos'] as $defecto_id => $cantidad) {
                    $sql_def = "INSERT INTO inspeccion_defectos (inspeccion_id, defecto_id, cantidad)
                                VALUES (:inspeccion_id, :defecto_id, :cantidad)";
                    $stmt_def = $this->pdo->prepare($sql_def);
                    $stmt_def->bindValue(':inspeccion_id', $inspeccion_id, PDO::PARAM_INT);
                    $stmt_def->bindValue(':defecto_id', $defecto_id, PDO::PARAM_INT);
                    $stmt_def->bindValue(':cantidad', (int)$cantidad, PDO::PARAM_INT);

                    if (!$stmt_def->execute()) {
                        throw new Exception('Error al registrar defectos');
                    }
                }
            }

            // Actualizar estado de la pieza
            $nuevo_estatus = ($rechazada > 0) ? 'rechazada' : 'liberada';
            $this->actualizarEstatusPieza($folio, $nuevo_estatus);

            // Actualizar resumen de calidad
            $this->actualizarResumenCalidad($pieza['produccion_id']);

            $this->pdo->commit();
            return $inspeccion_id;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Actualizar estado de pieza
    public function actualizarEstatusPieza($folio_pieza, $estatus) {
        $sql = "UPDATE piezas_producidas
                SET estatus = :estatus, fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE folio_pieza = :folio_pieza";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':estatus', $estatus);
        $stmt->bindValue(':folio_pieza', $folio_pieza);

        return $stmt->execute();
    }

    // Obtener inspecciones de una pieza
    public function obtenerInspeccionesPieza($folio_pieza) {
        $sql = "SELECT ci.*,
                GROUP_CONCAT(CONCAT(d.nombre, ' (', id_def.cantidad, ')') SEPARATOR ', ') as defectos_descripcion
                FROM calidad_inspecciones ci
                LEFT JOIN inspeccion_defectos id_def ON ci.id = id_def.inspeccion_id
                LEFT JOIN defectos d ON id_def.defecto_id = d.id
                WHERE ci.folio_pieza = :folio_pieza
                GROUP BY ci.id
                ORDER BY ci.fecha_inspeccion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':folio_pieza', $folio_pieza);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ==================== RESUMEN DE CALIDAD ====================

    // Actualizar resumen de calidad por producción
    public function actualizarResumenCalidad($produccion_id) {
        try {
            // Obtener totales
            $sql = "SELECT
                        COUNT(DISTINCT CASE WHEN pp.estatus IN ('por_inspeccionar', 'pendiente_reinspeccion') THEN pp.id END) as pendientes,
                        COUNT(DISTINCT CASE WHEN pp.estatus = 'liberada' THEN pp.id END) as aceptadas,
                        COUNT(DISTINCT CASE WHEN pp.estatus = 'rechazada' THEN pp.id END) as rechazadas,
                        SUM(CASE WHEN pp.estatus = 'liberada' THEN 1 ELSE 0 END) as total_aceptadas,
                        SUM(CASE WHEN pp.estatus = 'rechazada' THEN 1 ELSE 0 END) as total_rechazadas,
                        COUNT(*) as total_producidas
                    FROM piezas_producidas pp
                    WHERE pp.produccion_id = :produccion_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':produccion_id', $produccion_id, PDO::PARAM_INT);
            $stmt->execute();
            $resumen = $stmt->fetch();

            $total_producidas = $resumen['total_producidas'] ?? 0;
            $total_aceptadas = $resumen['total_aceptadas'] ?? 0;
            $total_rechazadas = $resumen['total_rechazadas'] ?? 0;
            $piezas_pendientes = $resumen['pendientes'] ?? 0;

            $porcentaje_aceptacion = ($total_producidas > 0)
                ? round(($total_aceptadas / $total_producidas) * 100, 2)
                : 0;

            // Insertar o actualizar resumen
            $sql_upsert = "INSERT INTO calidad_resumen (
                produccion_id, total_piezas_producidas, total_piezas_inspeccionadas,
                total_piezas_aceptadas, total_piezas_rechazadas, porcentaje_aceptacion,
                piezas_pendientes_inspeccion
            ) VALUES (
                :produccion_id, :total_producidas, :total_inspeccionadas,
                :aceptadas, :rechazadas, :porcentaje, :pendientes
            )
            ON DUPLICATE KEY UPDATE
                total_piezas_producidas = :total_producidas,
                total_piezas_inspeccionadas = :total_inspeccionadas,
                total_piezas_aceptadas = :aceptadas,
                total_piezas_rechazadas = :rechazadas,
                porcentaje_aceptacion = :porcentaje,
                piezas_pendientes_inspeccion = :pendientes,
                fecha_actualizacion = CURRENT_TIMESTAMP";

            $stmt = $this->pdo->prepare($sql_upsert);
            $stmt->bindValue(':produccion_id', $produccion_id, PDO::PARAM_INT);
            $stmt->bindValue(':total_producidas', $total_producidas);
            $stmt->bindValue(':total_inspeccionadas', $total_aceptadas + $total_rechazadas);
            $stmt->bindValue(':aceptadas', $total_aceptadas);
            $stmt->bindValue(':rechazadas', $total_rechazadas);
            $stmt->bindValue(':porcentaje', $porcentaje_aceptacion);
            $stmt->bindValue(':pendientes', $piezas_pendientes);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log('Error actualizando resumen de calidad: ' . $e->getMessage());
            return false;
        }
    }

    // Obtener resumen de calidad por producción
    public function obtenerResumenCalidad($produccion_id) {
        $sql = "SELECT * FROM calidad_resumen WHERE produccion_id = :produccion_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':produccion_id', $produccion_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Obtener supervisores únicos
    public function obtenerSupervisores() {
        $sql = "SELECT DISTINCT supervisor_produccion as supervisor
                FROM piezas_producidas
                WHERE supervisor_produccion IS NOT NULL
                ORDER BY supervisor_produccion";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Obtener items únicos
    public function obtenerItems() {
        $sql = "SELECT DISTINCT item_code
                FROM piezas_producidas
                WHERE item_code IS NOT NULL
                ORDER BY item_code";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Obtener piezas producidas con filtros
    public function obtenerPiezasProducidas($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['produccion_id'])) {
            $where[] = "pp.produccion_id = :produccion_id";
            $params[':produccion_id'] = $filtros['produccion_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT pp.* FROM piezas_producidas pp
                {$whereClause}
                ORDER BY pp.folio_pieza";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $valor) {
            $stmt->bindValue($key, $valor);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
