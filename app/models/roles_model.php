<?php

class RolesModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Listar roles
     */
    public function listarRoles($estado = null) {
        $sql = "SELECT r.*, COUNT(DISTINCT ur.usuario_id) as total_usuarios,
                COUNT(DISTINCT rp.permiso_id) as total_permisos
                FROM roles r
                LEFT JOIN usuario_rol ur ON r.id = ur.rol_id
                LEFT JOIN rol_permiso rp ON r.id = rp.rol_id
                WHERE 1=1";

        $params = [];

        if ($estado) {
            $sql .= " AND r.estado = ?";
            $params[] = $estado;
        }

        $sql .= " GROUP BY r.id ORDER BY r.nombre";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Obtener rol por ID
     */
    public function obtenerRolById($id) {
        $sql = "SELECT r.*, COUNT(DISTINCT ur.usuario_id) as total_usuarios,
                COUNT(DISTINCT rp.permiso_id) as total_permisos
                FROM roles r
                LEFT JOIN usuario_rol ur ON r.id = ur.rol_id
                LEFT JOIN rol_permiso rp ON r.id = rp.rol_id
                WHERE r.id = ?
                GROUP BY r.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$id]);

        return $stmt->fetch();
    }

    /**
     * Crear nuevo rol
     */
    public function crearRol($datos) {
        if (empty($datos['nombre'])) {
            throw new Exception("El nombre del rol es requerido");
        }

        // Validar que no exista
        $sql = "SELECT id FROM roles WHERE nombre = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([trim($datos['nombre'])]);

        if ($stmt->fetch()) {
            throw new Exception("El rol ya existe");
        }

        $nombre = trim($datos['nombre']);
        $descripcion = !empty($datos['descripcion']) ? trim($datos['descripcion']) : null;
        $estado = $datos['estado'] ?? 'activo';

        $sql = "INSERT INTO roles (nombre, descripcion, estado) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$nombre, $descripcion, $estado]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Actualizar rol
     */
    public function actualizarRol($id, $datos) {
        $id = (int)$id;

        if (!$this->obtenerRolById($id)) {
            throw new Exception("El rol no existe");
        }

        // Validar que nombre sea único si se cambia
        if (isset($datos['nombre'])) {
            $sql = "SELECT id FROM roles WHERE nombre = ? AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([trim($datos['nombre']), $id]);

            if ($stmt->fetch()) {
                throw new Exception("El nombre de rol ya está en uso");
            }
        }

        $actualizaciones = [];
        $params = [];

        if (isset($datos['nombre'])) {
            $actualizaciones[] = "nombre = ?";
            $params[] = trim($datos['nombre']);
        }

        if (isset($datos['descripcion'])) {
            $actualizaciones[] = "descripcion = ?";
            $params[] = trim($datos['descripcion']);
        }

        if (isset($datos['estado'])) {
            $actualizaciones[] = "estado = ?";
            $params[] = $datos['estado'];
        }

        if (empty($actualizaciones)) {
            return true;
        }

        $params[] = $id;
        $sql = "UPDATE roles SET " . implode(", ", $actualizaciones) . " WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eliminar rol
     */
    public function eliminarRol($id) {
        $id = (int)$id;
        $rol = $this->obtenerRolById($id);

        if (!$rol) {
            throw new Exception("El rol no existe");
        }

        // Verificar si tiene usuarios asignados
        if ($rol['total_usuarios'] > 0) {
            throw new Exception("No se puede eliminar este rol porque tiene usuarios asignados");
        }

        $sql = "DELETE FROM roles WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$id]);
    }

    /**
     * Obtener permisos del rol
     */
    public function obtenerPermisosRol($rol_id) {
        $sql = "SELECT p.* FROM permisos p
                INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                WHERE rp.rol_id = ?
                ORDER BY p.modulo, p.accion";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$rol_id]);

        return $stmt->fetchAll();
    }

    /**
     * Asignar permiso a rol
     */
    public function asignarPermiso($rol_id, $permiso_id) {
        $rol_id = (int)$rol_id;
        $permiso_id = (int)$permiso_id;

        // Verificar que existen
        if (!$this->obtenerRolById($rol_id)) {
            throw new Exception("El rol no existe");
        }

        $sql = "SELECT id FROM permisos WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$permiso_id]);

        if (!$stmt->fetch()) {
            throw new Exception("El permiso no existe");
        }

        // Verificar si ya tiene el permiso
        $sql = "SELECT id FROM rol_permiso WHERE rol_id = ? AND permiso_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rol_id, $permiso_id]);

        if ($stmt->fetch()) {
            return true; // Ya tiene el permiso
        }

        $sql = "INSERT INTO rol_permiso (rol_id, permiso_id) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$rol_id, $permiso_id]);
    }

    /**
     * Remover permiso de rol
     */
    public function removerPermiso($rol_id, $permiso_id) {
        $rol_id = (int)$rol_id;
        $permiso_id = (int)$permiso_id;

        $sql = "DELETE FROM rol_permiso WHERE rol_id = ? AND permiso_id = ?";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$rol_id, $permiso_id]);
    }

    /**
     * Obtener todos los permisos disponibles
     */
    public function obtenerTodosPermisos() {
        $sql = "SELECT * FROM permisos ORDER BY modulo, accion";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtener permisos agrupados por módulo
     */
    public function obtenerPermisosAgrupadosPorModulo() {
        $sql = "SELECT * FROM permisos ORDER BY modulo, accion";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $permisos = $stmt->fetchAll();
        $agrupados = [];

        foreach ($permisos as $permiso) {
            $modulo = $permiso['modulo'];
            if (!isset($agrupados[$modulo])) {
                $agrupados[$modulo] = [];
            }
            $agrupados[$modulo][] = $permiso;
        }

        return $agrupados;
    }

    /**
     * Asignar múltiples permisos a rol
     */
    public function asignarMultiplesPermisos($rol_id, $permisos_ids) {
        $rol_id = (int)$rol_id;

        if (!$this->obtenerRolById($rol_id)) {
            throw new Exception("El rol no existe");
        }

        try {
            // Primero eliminar todos los permisos actuales
            $sql = "DELETE FROM rol_permiso WHERE rol_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rol_id]);

            // Luego asignar los nuevos
            if (is_array($permisos_ids) && count($permisos_ids) > 0) {
                foreach ($permisos_ids as $permiso_id) {
                    $this->asignarPermiso($rol_id, $permiso_id);
                }
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
