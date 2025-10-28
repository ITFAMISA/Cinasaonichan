<?php

class PdfOrdenesModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Guarda el registro de un PDF procesado
     */
    public function guardarPdfProcesado($datos) {
        $sql = "INSERT INTO pdf_ordenes_procesadas (
            nombre_archivo, ruta_archivo, texto_extraido,
            datos_estructurados, template_usado, estatus,
            usuario_proceso, fecha_proceso
        ) VALUES (
            :nombre_archivo, :ruta_archivo, :texto_extraido,
            :datos_estructurados, :template_usado, :estatus,
            :usuario_proceso, NOW()
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($datos);
        return $this->pdo->lastInsertId();
    }

    /**
     * Obtiene el listado de PDFs procesados
     */
    public function listarPdfsProcesados($filtros = [], $limite = 20, $offset = 0) {
        $where = [];
        $params = [];

        if (!empty($filtros['estatus'])) {
            $where[] = "estatus = ?";
            $params[] = $filtros['estatus'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "fecha_proceso >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "fecha_proceso <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM pdf_ordenes_procesadas {$whereClause}
                ORDER BY fecha_proceso DESC LIMIT ? OFFSET ?";

        $params[] = (int)$limite;
        $params[] = (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene un PDF procesado por ID
     */
    public function obtenerPdfPorId($id) {
        $sql = "SELECT * FROM pdf_ordenes_procesadas WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Actualiza los datos estructurados de un PDF
     */
    public function actualizarDatosEstructurados($id, $datos_estructurados, $estatus = 'procesado') {
        $sql = "UPDATE pdf_ordenes_procesadas SET
                datos_estructurados = :datos_estructurados,
                estatus = :estatus,
                fecha_actualizacion = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':datos_estructurados', $datos_estructurados);
        $stmt->bindValue(':estatus', $estatus);
        return $stmt->execute();
    }

    /**
     * Guarda un template de extracción
     */
    public function guardarTemplate($datos) {
        $sql = "INSERT INTO pdf_templates (
            nombre, descripcion, patron_regex, campos_mapeados,
            prioridad, activo, usuario_creacion, fecha_creacion
        ) VALUES (
            :nombre, :descripcion, :patron_regex, :campos_mapeados,
            :prioridad, :activo, :usuario_creacion, NOW()
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($datos);
        return $this->pdo->lastInsertId();
    }

    /**
     * Obtiene templates activos
     */
    public function obtenerTemplatesActivos() {
        $sql = "SELECT * FROM pdf_templates WHERE activo = 1 ORDER BY prioridad DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca un producto por código o descripción aproximada
     */
    public function buscarProducto($termino) {
        $sql = "SELECT * FROM productos
                WHERE (material_code LIKE ? OR descripcion LIKE ?)
                AND estatus = 'activo'
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $param = '%' . $termino . '%';
        $stmt->execute([$param, $param]);
        return $stmt->fetchAll();
    }
}
