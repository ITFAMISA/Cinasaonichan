<?php

class EmpleadosModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lista empleados con filtros y paginación
     */
    public function listarEmpleados($filtros = [], $orden = 'apellido', $direccion = 'ASC', $limite = 20, $offset = 0) {
        $where = [];
        $params = [];

        // Filtro de búsqueda por nombre o apellido
        if (!empty($filtros['buscar'])) {
            $buscar = trim($filtros['buscar']);
            $where[] = "(nombre LIKE ? OR apellido LIKE ? OR numero_empleado LIKE ? OR correo LIKE ?)";
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
        }

        // Filtro por estado
        if (!empty($filtros['estatus_empleado'])) {
            $where[] = "estatus_empleado = ?";
            $params[] = $filtros['estatus_empleado'];
        }

        // Filtro por departamento
        if (!empty($filtros['departamento'])) {
            $where[] = "departamento = ?";
            $params[] = $filtros['departamento'];
        }

        // Filtro por puesto
        if (!empty($filtros['puesto'])) {
            $where[] = "puesto = ?";
            $params[] = $filtros['puesto'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $columnasPermitidas = ['id', 'nombre', 'apellido', 'puesto', 'departamento', 'estatus_empleado', 'fecha_ingreso', 'salario_base'];
        if (!in_array($orden, $columnasPermitidas)) {
            $orden = 'apellido';
        }

        $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT id, nombre, apellido, puesto, numero_empleado, correo, telefono, departamento,
                       fecha_ingreso, estatus_empleado, salario_base, supervisor_directo_id, fecha_creacion
                FROM empleados {$whereClause} ORDER BY {$orden} {$direccion} LIMIT ? OFFSET ?";

        $params[] = (int)$limite;
        $params[] = (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Contar empleados con filtros
     */
    public function contarEmpleados($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = trim($filtros['buscar']);
            $where[] = "(nombre LIKE ? OR apellido LIKE ? OR numero_empleado LIKE ? OR correo LIKE ?)";
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
            $params[] = '%' . $buscar . '%';
        }

        if (!empty($filtros['estatus_empleado'])) {
            $where[] = "estatus_empleado = ?";
            $params[] = $filtros['estatus_empleado'];
        }

        if (!empty($filtros['departamento'])) {
            $where[] = "departamento = ?";
            $params[] = $filtros['departamento'];
        }

        if (!empty($filtros['puesto'])) {
            $where[] = "puesto = ?";
            $params[] = $filtros['puesto'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total FROM empleados {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch();
        return $resultado['total'];
    }

    /**
     * Obtener empleado por ID
     */
    public function obtenerEmpleadoPorId($id) {
        $sql = "SELECT * FROM empleados WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /**
     * Crear nuevo empleado
     */
    public function crearEmpleado($datos) {
        // Validar campos requeridos
        if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['puesto'])) {
            throw new Exception("Los campos: nombre, apellido y puesto son requeridos");
        }

        // Sanitizar datos
        $nombre = trim($datos['nombre']);
        $apellido = trim($datos['apellido']);
        $puesto = trim($datos['puesto']);

        // Campos opcionales
        $numero_empleado = !empty($datos['numero_empleado']) ? trim($datos['numero_empleado']) : null;
        $correo = !empty($datos['correo']) ? trim($datos['correo']) : null;
        $telefono = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        $telefono_extension = !empty($datos['telefono_extension']) ? trim($datos['telefono_extension']) : null;
        $departamento = !empty($datos['departamento']) ? trim($datos['departamento']) : null;
        $fecha_ingreso = !empty($datos['fecha_ingreso']) ? $datos['fecha_ingreso'] : null;
        $fecha_nacimiento = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;
        $genero = !empty($datos['genero']) ? $datos['genero'] : null;
        $numero_identificacion = !empty($datos['numero_identificacion']) ? trim($datos['numero_identificacion']) : null;
        $tipo_identificacion = !empty($datos['tipo_identificacion']) ? trim($datos['tipo_identificacion']) : null;
        $numero_seguro_social = !empty($datos['numero_seguro_social']) ? trim($datos['numero_seguro_social']) : null;
        $banco = !empty($datos['banco']) ? trim($datos['banco']) : null;
        $cuenta_bancaria = !empty($datos['cuenta_bancaria']) ? trim($datos['cuenta_bancaria']) : null;
        $clabe = !empty($datos['clabe']) ? trim($datos['clabe']) : null;
        $direccion = !empty($datos['direccion']) ? trim($datos['direccion']) : null;
        $ciudad = !empty($datos['ciudad']) ? trim($datos['ciudad']) : null;
        $estado = !empty($datos['estado']) ? trim($datos['estado']) : null;
        $codigo_postal = !empty($datos['codigo_postal']) ? trim($datos['codigo_postal']) : null;
        $pais = !empty($datos['pais']) ? trim($datos['pais']) : 'México';
        $contacto_emergencia_nombre = !empty($datos['contacto_emergencia_nombre']) ? trim($datos['contacto_emergencia_nombre']) : null;
        $contacto_emergencia_relacion = !empty($datos['contacto_emergencia_relacion']) ? trim($datos['contacto_emergencia_relacion']) : null;
        $contacto_emergencia_telefono = !empty($datos['contacto_emergencia_telefono']) ? trim($datos['contacto_emergencia_telefono']) : null;
        $estado_civil = !empty($datos['estado_civil']) ? $datos['estado_civil'] : null;
        $cantidad_dependientes = !empty($datos['cantidad_dependientes']) ? (int)$datos['cantidad_dependientes'] : 0;
        $nivel_escolaridad = !empty($datos['nivel_escolaridad']) ? trim($datos['nivel_escolaridad']) : null;
        $especialidad = !empty($datos['especialidad']) ? trim($datos['especialidad']) : null;
        $estatus_empleado = !empty($datos['estatus_empleado']) ? $datos['estatus_empleado'] : 'activo';
        $salario_base = !empty($datos['salario_base']) ? (float)$datos['salario_base'] : 0.00;
        $tipo_contrato = !empty($datos['tipo_contrato']) ? trim($datos['tipo_contrato']) : null;
        $fecha_contrato = !empty($datos['fecha_contrato']) ? $datos['fecha_contrato'] : null;
        $supervisor_directo_id = !empty($datos['supervisor_directo_id']) ? (int)$datos['supervisor_directo_id'] : null;
        $observaciones = !empty($datos['observaciones']) ? trim($datos['observaciones']) : null;

        $sql = "INSERT INTO empleados (
                    nombre, apellido, puesto, numero_empleado, correo, telefono, telefono_extension,
                    departamento, fecha_ingreso, fecha_nacimiento, genero, numero_identificacion,
                    tipo_identificacion, numero_seguro_social, banco, cuenta_bancaria, clabe,
                    direccion, ciudad, estado, codigo_postal, pais,
                    contacto_emergencia_nombre, contacto_emergencia_relacion, contacto_emergencia_telefono,
                    estado_civil, cantidad_dependientes, nivel_escolaridad, especialidad,
                    estatus_empleado, salario_base, tipo_contrato, fecha_contrato, supervisor_directo_id, observaciones
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $nombre, $apellido, $puesto, $numero_empleado, $correo, $telefono, $telefono_extension,
            $departamento, $fecha_ingreso, $fecha_nacimiento, $genero, $numero_identificacion,
            $tipo_identificacion, $numero_seguro_social, $banco, $cuenta_bancaria, $clabe,
            $direccion, $ciudad, $estado, $codigo_postal, $pais,
            $contacto_emergencia_nombre, $contacto_emergencia_relacion, $contacto_emergencia_telefono,
            $estado_civil, $cantidad_dependientes, $nivel_escolaridad, $especialidad,
            $estatus_empleado, $salario_base, $tipo_contrato, $fecha_contrato, $supervisor_directo_id, $observaciones
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Actualizar empleado
     */
    public function actualizarEmpleado($id, $datos) {
        $id = (int)$id;

        // Verificar que el empleado existe
        if (!$this->obtenerEmpleadoPorId($id)) {
            throw new Exception("El empleado no existe");
        }

        // Validar campos requeridos
        if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['puesto'])) {
            throw new Exception("Los campos: nombre, apellido y puesto son requeridos");
        }

        $actualizaciones = [];
        $params = [];

        // Campos requeridos
        $actualizaciones[] = "nombre = ?";
        $params[] = trim($datos['nombre']);

        $actualizaciones[] = "apellido = ?";
        $params[] = trim($datos['apellido']);

        $actualizaciones[] = "puesto = ?";
        $params[] = trim($datos['puesto']);

        // Campos opcionales
        if (isset($datos['numero_empleado'])) {
            $actualizaciones[] = "numero_empleado = ?";
            $params[] = !empty($datos['numero_empleado']) ? trim($datos['numero_empleado']) : null;
        }

        if (isset($datos['correo'])) {
            $actualizaciones[] = "correo = ?";
            $params[] = !empty($datos['correo']) ? trim($datos['correo']) : null;
        }

        if (isset($datos['telefono'])) {
            $actualizaciones[] = "telefono = ?";
            $params[] = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        }

        if (isset($datos['telefono_extension'])) {
            $actualizaciones[] = "telefono_extension = ?";
            $params[] = !empty($datos['telefono_extension']) ? trim($datos['telefono_extension']) : null;
        }

        if (isset($datos['departamento'])) {
            $actualizaciones[] = "departamento = ?";
            $params[] = !empty($datos['departamento']) ? trim($datos['departamento']) : null;
        }

        if (isset($datos['fecha_ingreso'])) {
            $actualizaciones[] = "fecha_ingreso = ?";
            $params[] = !empty($datos['fecha_ingreso']) ? $datos['fecha_ingreso'] : null;
        }

        if (isset($datos['salario_base'])) {
            $actualizaciones[] = "salario_base = ?";
            $params[] = !empty($datos['salario_base']) ? (float)$datos['salario_base'] : 0.00;
        }

        if (isset($datos['estatus_empleado'])) {
            $actualizaciones[] = "estatus_empleado = ?";
            $params[] = !empty($datos['estatus_empleado']) ? $datos['estatus_empleado'] : 'activo';
        }

        if (isset($datos['supervisor_directo_id'])) {
            $actualizaciones[] = "supervisor_directo_id = ?";
            $params[] = !empty($datos['supervisor_directo_id']) ? (int)$datos['supervisor_directo_id'] : null;
        }

        if (isset($datos['observaciones'])) {
            $actualizaciones[] = "observaciones = ?";
            $params[] = !empty($datos['observaciones']) ? trim($datos['observaciones']) : null;
        }

        // Agregar más campos opcionales según sea necesario
        if (isset($datos['fecha_nacimiento'])) {
            $actualizaciones[] = "fecha_nacimiento = ?";
            $params[] = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;
        }

        if (isset($datos['genero'])) {
            $actualizaciones[] = "genero = ?";
            $params[] = !empty($datos['genero']) ? $datos['genero'] : null;
        }

        if (isset($datos['banco'])) {
            $actualizaciones[] = "banco = ?";
            $params[] = !empty($datos['banco']) ? trim($datos['banco']) : null;
        }

        if (isset($datos['cuenta_bancaria'])) {
            $actualizaciones[] = "cuenta_bancaria = ?";
            $params[] = !empty($datos['cuenta_bancaria']) ? trim($datos['cuenta_bancaria']) : null;
        }

        if (isset($datos['clabe'])) {
            $actualizaciones[] = "clabe = ?";
            $params[] = !empty($datos['clabe']) ? trim($datos['clabe']) : null;
        }

        if (isset($datos['direccion'])) {
            $actualizaciones[] = "direccion = ?";
            $params[] = !empty($datos['direccion']) ? trim($datos['direccion']) : null;
        }

        if (isset($datos['ciudad'])) {
            $actualizaciones[] = "ciudad = ?";
            $params[] = !empty($datos['ciudad']) ? trim($datos['ciudad']) : null;
        }

        if (isset($datos['estado'])) {
            $actualizaciones[] = "estado = ?";
            $params[] = !empty($datos['estado']) ? trim($datos['estado']) : null;
        }

        if (isset($datos['codigo_postal'])) {
            $actualizaciones[] = "codigo_postal = ?";
            $params[] = !empty($datos['codigo_postal']) ? trim($datos['codigo_postal']) : null;
        }

        if (isset($datos['pais'])) {
            $actualizaciones[] = "pais = ?";
            $params[] = !empty($datos['pais']) ? trim($datos['pais']) : 'México';
        }

        if (isset($datos['estado_civil'])) {
            $actualizaciones[] = "estado_civil = ?";
            $params[] = !empty($datos['estado_civil']) ? $datos['estado_civil'] : null;
        }

        if (isset($datos['cantidad_dependientes'])) {
            $actualizaciones[] = "cantidad_dependientes = ?";
            $params[] = !empty($datos['cantidad_dependientes']) ? (int)$datos['cantidad_dependientes'] : 0;
        }

        if (isset($datos['tipo_identificacion'])) {
            $actualizaciones[] = "tipo_identificacion = ?";
            $params[] = !empty($datos['tipo_identificacion']) ? trim($datos['tipo_identificacion']) : null;
        }

        if (isset($datos['numero_identificacion'])) {
            $actualizaciones[] = "numero_identificacion = ?";
            $params[] = !empty($datos['numero_identificacion']) ? trim($datos['numero_identificacion']) : null;
        }

        if (isset($datos['numero_seguro_social'])) {
            $actualizaciones[] = "numero_seguro_social = ?";
            $params[] = !empty($datos['numero_seguro_social']) ? trim($datos['numero_seguro_social']) : null;
        }

        if (isset($datos['tipo_contrato'])) {
            $actualizaciones[] = "tipo_contrato = ?";
            $params[] = !empty($datos['tipo_contrato']) ? trim($datos['tipo_contrato']) : null;
        }

        if (isset($datos['fecha_contrato'])) {
            $actualizaciones[] = "fecha_contrato = ?";
            $params[] = !empty($datos['fecha_contrato']) ? $datos['fecha_contrato'] : null;
        }

        if (isset($datos['contacto_emergencia_nombre'])) {
            $actualizaciones[] = "contacto_emergencia_nombre = ?";
            $params[] = !empty($datos['contacto_emergencia_nombre']) ? trim($datos['contacto_emergencia_nombre']) : null;
        }

        if (isset($datos['contacto_emergencia_relacion'])) {
            $actualizaciones[] = "contacto_emergencia_relacion = ?";
            $params[] = !empty($datos['contacto_emergencia_relacion']) ? trim($datos['contacto_emergencia_relacion']) : null;
        }

        if (isset($datos['contacto_emergencia_telefono'])) {
            $actualizaciones[] = "contacto_emergencia_telefono = ?";
            $params[] = !empty($datos['contacto_emergencia_telefono']) ? trim($datos['contacto_emergencia_telefono']) : null;
        }

        if (isset($datos['nivel_escolaridad'])) {
            $actualizaciones[] = "nivel_escolaridad = ?";
            $params[] = !empty($datos['nivel_escolaridad']) ? trim($datos['nivel_escolaridad']) : null;
        }

        if (isset($datos['especialidad'])) {
            $actualizaciones[] = "especialidad = ?";
            $params[] = !empty($datos['especialidad']) ? trim($datos['especialidad']) : null;
        }

        $params[] = $id;

        $sql = "UPDATE empleados SET " . implode(", ", $actualizaciones) . " WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eliminar empleado
     */
    public function eliminarEmpleado($id) {
        $id = (int)$id;

        // No permitir eliminar si es supervisor de otros
        $sql = "SELECT COUNT(*) as total FROM empleados WHERE supervisor_directo_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $resultado = $stmt->fetch();

        if ($resultado['total'] > 0) {
            throw new Exception("No se puede eliminar este empleado porque es supervisor de otros empleados");
        }

        $sql = "DELETE FROM empleados WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Obtener opciones de departamentos
     */
    public function obtenerDepartamentos() {
        $sql = "SELECT DISTINCT departamento FROM empleados WHERE departamento IS NOT NULL ORDER BY departamento";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $resultados = $stmt->fetchAll();
        $departamentos = [];
        foreach ($resultados as $row) {
            if (!empty($row['departamento'])) {
                $departamentos[] = $row['departamento'];
            }
        }
        return $departamentos;
    }

    /**
     * Obtener opciones de puestos
     */
    public function obtenerPuestos() {
        $sql = "SELECT DISTINCT puesto FROM empleados WHERE puesto IS NOT NULL ORDER BY puesto";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $resultados = $stmt->fetchAll();
        $puestos = [];
        foreach ($resultados as $row) {
            if (!empty($row['puesto'])) {
                $puestos[] = $row['puesto'];
            }
        }
        return $puestos;
    }

    /**
     * Obtener empleados supervisores (solo activos)
     */
    public function obtenerSupervisores() {
        $sql = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM empleados
                WHERE estatus_empleado = 'activo' ORDER BY apellido, nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Buscar empleados por nombre, apellido o correo
     */
    public function buscarEmpleados($buscar = '') {
        $sql = "SELECT id, nombre, apellido, correo, puesto, departamento, estatus_empleado 
                FROM empleados 
                WHERE estatus_empleado = 'activo'";
        
        $params = [];
        
        if (!empty($buscar)) {
            $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR numero_empleado LIKE ?)";
            $buscar_param = "%{$buscar}%";
            $params = [$buscar_param, $buscar_param, $buscar_param, $buscar_param];
        }
        
        $sql .= " ORDER BY apellido, nombre LIMIT 50";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
