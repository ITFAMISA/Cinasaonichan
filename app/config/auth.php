<?php
/**
 * Sistema de Autenticación y Autorización
 */

require_once __DIR__ . '/database.php';

class Auth {
    private $pdo;
    private static $instance = null;

    private function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtener ID del usuario actual
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtener información del usuario actual
     */
    public function getUser() {
        $userId = $this->getUserId();
        if (!$userId) {
            return null;
        }

        $sql = "SELECT id, nombre_usuario, nombre_completo, correo, estado 
                FROM usuarios WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Obtener roles del usuario actual
     */
    public function getUserRoles() {
        $userId = $this->getUserId();
        if (!$userId) {
            return [];
        }

        $sql = "SELECT r.* FROM roles r
                INNER JOIN usuario_rol ur ON r.id = ur.rol_id
                WHERE ur.usuario_id = ? AND r.estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener permisos del usuario actual
     */
    public function getUserPermissions() {
        $userId = $this->getUserId();
        if (!$userId) {
            return [];
        }

        $sql = "SELECT DISTINCT p.* FROM permisos p
                INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                INNER JOIN usuario_rol ur ON rp.rol_id = ur.rol_id
                WHERE ur.usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function hasPermission($permiso) {
        $userId = $this->getUserId();
        if (!$userId) {
            return false;
        }

        // Cache permisos en sesión
        if (!isset($_SESSION['user_permissions'])) {
            $permissions = $this->getUserPermissions();
            $_SESSION['user_permissions'] = array_column($permissions, 'nombre');
        }

        return in_array($permiso, $_SESSION['user_permissions']);
    }

    /**
     * Verificar si el usuario tiene acceso a un módulo
     */
    public function hasModuleAccess($modulo) {
        $userId = $this->getUserId();
        if (!$userId) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM permisos p
                INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                INNER JOIN usuario_rol ur ON rp.rol_id = ur.rol_id
                WHERE ur.usuario_id = ? AND p.modulo = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $modulo]);
        $result = $stmt->fetch();

        return $result['count'] > 0;
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($rolNombre) {
        $roles = $this->getUserRoles();
        foreach ($roles as $rol) {
            if ($rol['nombre'] === $rolNombre) {
                return true;
            }
        }
        return false;
    }

    /**
     * Requiere permiso - Lanza excepción si no tiene permiso
     */
    public function requirePermission($permiso) {
        if (!$this->hasPermission($permiso)) {
            http_response_code(403);
            throw new Exception("No tienes permiso para realizar esta acción");
        }
    }

    /**
     * Requiere acceso a módulo - Lanza excepción si no tiene acceso
     */
    public function requireModuleAccess($modulo) {
        if (!$this->hasModuleAccess($modulo)) {
            http_response_code(403);
            throw new Exception("No tienes acceso a este módulo");
        }
    }

    /**
     * Limpiar caché de permisos
     */
    public function clearPermissionsCache() {
        unset($_SESSION['user_permissions']);
    }

    /**
     * Obtener módulos disponibles para el usuario
     */
    public function getAvailableModules() {
        $userId = $this->getUserId();
        if (!$userId) {
            return [];
        }

        $sql = "SELECT DISTINCT p.modulo FROM permisos p
                INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                INNER JOIN usuario_rol ur ON rp.rol_id = ur.rol_id
                WHERE ur.usuario_id = ?
                ORDER BY p.modulo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'modulo');
    }
}

// Funciones helper globales
function auth() {
    return Auth::getInstance();
}

function hasPermission($permiso) {
    return auth()->hasPermission($permiso);
}

function hasModuleAccess($modulo) {
    return auth()->hasModuleAccess($modulo);
}

function requirePermission($permiso) {
    auth()->requirePermission($permiso);
}

function requireModuleAccess($modulo) {
    auth()->requireModuleAccess($modulo);
}

function hasRole($rolNombre) {
    return auth()->hasRole($rolNombre);
}
