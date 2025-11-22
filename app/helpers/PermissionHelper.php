<?php

class PermissionHelper {
    private $pdo;
    private $usuarioId;
    private $permisosCache = [];

    public function __construct($pdo, $usuarioId = null) {
        $this->pdo = $pdo;
        $this->usuarioId = $usuarioId ?? ($_SESSION['user_id'] ?? null);
    }

    /**
     * Verificar si un usuario tiene un permiso específico
     */
    public function tienePermiso($nombrePermiso) {
        if (!$this->usuarioId) {
            return false;
        }

        // Verificar en cache
        if (isset($this->permisosCache[$nombrePermiso])) {
            return $this->permisosCache[$nombrePermiso];
        }

        try {
            $sql = "SELECT COUNT(*) as total FROM permisos p
                    INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                    INNER JOIN usuario_rol ur ON rp.rol_id = ur.rol_id
                    WHERE ur.usuario_id = ? AND p.nombre = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->usuarioId, trim($nombrePermiso)]);

            $resultado = $stmt->fetch();
            $tienePermiso = $resultado['total'] > 0;

            // Guardar en cache
            $this->permisosCache[$nombrePermiso] = $tienePermiso;

            return $tienePermiso;
        } catch (Exception $e) {
            error_log("Error al verificar permiso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un usuario tiene alguno de los permisos especificados
     */
    public function tieneAlgunoPermiso($permisos) {
        if (!is_array($permisos)) {
            $permisos = [$permisos];
        }

        foreach ($permisos as $permiso) {
            if ($this->tienePermiso($permiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si un usuario tiene todos los permisos especificados
     */
    public function tieneTodosPermisos($permisos) {
        if (!is_array($permisos)) {
            $permisos = [$permisos];
        }

        foreach ($permisos as $permiso) {
            if (!$this->tienePermiso($permiso)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener todos los permisos de un usuario
     */
    public function obtenerPermisosUsuario() {
        if (!$this->usuarioId) {
            return [];
        }

        try {
            $sql = "SELECT DISTINCT p.* FROM permisos p
                    INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                    INNER JOIN usuario_rol ur ON rp.rol_id = ur.rol_id
                    WHERE ur.usuario_id = ?
                    ORDER BY p.modulo, p.accion";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->usuarioId]);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al obtener permisos del usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener los roles de un usuario
     */
    public function obtenerRolesUsuario() {
        if (!$this->usuarioId) {
            return [];
        }

        try {
            $sql = "SELECT r.* FROM roles r
                    INNER JOIN usuario_rol ur ON r.id = ur.rol_id
                    WHERE ur.usuario_id = ?
                    ORDER BY r.nombre";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->usuarioId]);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al obtener roles del usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un usuario tiene un rol específico
     */
    public function tieneRol($nombreRol) {
        if (!$this->usuarioId) {
            return false;
        }

        try {
            $sql = "SELECT COUNT(*) as total FROM roles r
                    INNER JOIN usuario_rol ur ON r.id = ur.rol_id
                    WHERE ur.usuario_id = ? AND r.nombre = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->usuarioId, trim($nombreRol)]);

            $resultado = $stmt->fetch();
            return $resultado['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un usuario es administrador
     */
    public function esAdministrador() {
        return $this->tieneRol('Administrador');
    }

    /**
     * Requiere que el usuario tenga un permiso específico (sino, detiene la ejecución)
     */
    public function requerirPermiso($nombrePermiso, $mensaje = 'No tienes permisos para acceder a este recurso') {
        if (!$this->tienePermiso($nombrePermiso)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $mensaje
            ]);
            exit;
        }
    }

    /**
     * Requiere que el usuario sea administrador
     */
    public function requerirAdmin($mensaje = 'Solo administradores pueden acceder') {
        if (!$this->esAdministrador()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $mensaje
            ]);
            exit;
        }
    }

    /**
     * Limpiar cache de permisos
     */
    public function limpiarCache() {
        $this->permisosCache = [];
    }

    /**
     * Establecer usuario diferente
     */
    public function establecerUsuario($usuarioId) {
        $this->usuarioId = $usuarioId;
        $this->limpiarCache();
    }
}
