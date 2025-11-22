<?php

class UsuariosModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Listar usuarios con paginación
     */
    public function listarUsuarios($pagina = 1, $buscar = '', $rol_id = null) {
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        $sql = "SELECT u.*, GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles
                FROM usuarios u
                LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                LEFT JOIN roles r ON ur.rol_id = r.id
                WHERE 1=1";

        $params = [];

        if (!empty($buscar)) {
            $sql .= " AND (u.nombre_usuario LIKE ? OR u.nombre_completo LIKE ? OR u.correo LIKE ?)";
            $buscar_param = "%{$buscar}%";
            $params = [$buscar_param, $buscar_param, $buscar_param];
        }

        if ($rol_id !== null) {
            $sql .= " AND ur.rol_id = ?";
            $params[] = (int)$rol_id;
        }

        $sql .= " GROUP BY u.id ORDER BY u.fecha_creacion DESC LIMIT ? OFFSET ?";
        $params[] = $limite;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Contar total de usuarios
     */
    public function contarUsuarios($buscar = '', $rol_id = null) {
        $sql = "SELECT COUNT(DISTINCT u.id) as total
                FROM usuarios u
                LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                WHERE 1=1";

        $params = [];

        if (!empty($buscar)) {
            $sql .= " AND (u.nombre_usuario LIKE ? OR u.nombre_completo LIKE ? OR u.correo LIKE ?)";
            $buscar_param = "%{$buscar}%";
            $params = [$buscar_param, $buscar_param, $buscar_param];
        }

        if ($rol_id !== null) {
            $sql .= " AND ur.rol_id = ?";
            $params[] = (int)$rol_id;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch();
        return $resultado['total'] ?? 0;
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerUsuarioById($id) {
        $sql = "SELECT u.*, GROUP_CONCAT(ur.rol_id) as rol_ids, GROUP_CONCAT(r.nombre) as roles
                FROM usuarios u
                LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                LEFT JOIN roles r ON ur.rol_id = r.id
                WHERE u.id = ?
                GROUP BY u.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$id]);

        return $stmt->fetch();
    }

    /**
     * Crear nuevo usuario
     */
    public function crearUsuario($datos) {
        if (empty($datos['nombre_usuario']) || empty($datos['nombre_completo']) ||
            empty($datos['correo']) || empty($datos['contrasena'])) {
            throw new Exception("Los campos: nombre_usuario, nombre_completo, correo y contraseña son requeridos");
        }

        // Validar que el usuario no exista
        $sql = "SELECT id FROM usuarios WHERE nombre_usuario = ? OR correo = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([trim($datos['nombre_usuario']), trim($datos['correo'])]);

        if ($stmt->fetch()) {
            throw new Exception("El nombre de usuario o correo ya existe");
        }

        $nombre_usuario = trim($datos['nombre_usuario']);
        $nombre_completo = trim($datos['nombre_completo']);
        $correo = trim($datos['correo']);
        $contrasena_hash = password_hash($datos['contrasena'], PASSWORD_BCRYPT);
        $estado = $datos['estado'] ?? 'activo';

        $sql = "INSERT INTO usuarios (nombre_usuario, nombre_completo, correo, contrasena_hash, estado)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$nombre_usuario, $nombre_completo, $correo, $contrasena_hash, $estado]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario($id, $datos) {
        $id = (int)$id;

        // Verificar que existe
        if (!$this->obtenerUsuarioById($id)) {
            throw new Exception("El usuario no existe");
        }

        // Validar que correo sea único si se cambia
        if (isset($datos['correo'])) {
            $sql = "SELECT id FROM usuarios WHERE correo = ? AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([trim($datos['correo']), $id]);

            if ($stmt->fetch()) {
                throw new Exception("El correo ya está en uso por otro usuario");
            }
        }

        $actualizaciones = [];
        $params = [];

        if (isset($datos['nombre_completo'])) {
            $actualizaciones[] = "nombre_completo = ?";
            $params[] = trim($datos['nombre_completo']);
        }

        if (isset($datos['correo'])) {
            $actualizaciones[] = "correo = ?";
            $params[] = trim($datos['correo']);
        }

        if (isset($datos['estado'])) {
            $actualizaciones[] = "estado = ?";
            $params[] = $datos['estado'];
        }

        if (isset($datos['contrasena']) && !empty($datos['contrasena'])) {
            $actualizaciones[] = "contrasena_hash = ?";
            $params[] = password_hash($datos['contrasena'], PASSWORD_BCRYPT);
        }

        if (empty($actualizaciones)) {
            return true;
        }

        $params[] = $id;
        $sql = "UPDATE usuarios SET " . implode(", ", $actualizaciones) . " WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eliminar usuario
     */
    public function eliminarUsuario($id) {
        $id = (int)$id;

        if (!$this->obtenerUsuarioById($id)) {
            throw new Exception("El usuario no existe");
        }

        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$id]);
    }

    /**
     * Asignar rol a usuario
     */
    public function asignarRol($usuario_id, $rol_id) {
        $usuario_id = (int)$usuario_id;
        $rol_id = (int)$rol_id;

        // Verificar que existen
        if (!$this->obtenerUsuarioById($usuario_id)) {
            throw new Exception("El usuario no existe");
        }

        $sql = "SELECT id FROM roles WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rol_id]);

        if (!$stmt->fetch()) {
            throw new Exception("El rol no existe");
        }

        // Verificar si ya tiene el rol
        $sql = "SELECT id FROM usuario_rol WHERE usuario_id = ? AND rol_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario_id, $rol_id]);

        if ($stmt->fetch()) {
            return true; // Ya tiene el rol
        }

        $sql = "INSERT INTO usuario_rol (usuario_id, rol_id) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$usuario_id, $rol_id]);
    }

    /**
     * Remover rol de usuario
     */
    public function removerRol($usuario_id, $rol_id) {
        $usuario_id = (int)$usuario_id;
        $rol_id = (int)$rol_id;

        $sql = "DELETE FROM usuario_rol WHERE usuario_id = ? AND rol_id = ?";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$usuario_id, $rol_id]);
    }

    /**
     * Obtener roles de usuario
     */
    public function obtenerRolesUsuario($usuario_id) {
        $sql = "SELECT r.* FROM roles r
                INNER JOIN usuario_rol ur ON r.id = ur.rol_id
                WHERE ur.usuario_id = ?
                ORDER BY r.nombre";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$usuario_id]);

        return $stmt->fetchAll();
    }

    /**
     * Obtener todos los roles disponibles
     */
    public function obtenerRoles($estado = 'activo') {
        $sql = "SELECT * FROM roles";
        $params = [];

        if ($estado) {
            $sql .= " WHERE estado = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY nombre";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarContrasena($usuario_id, $contrasena_actual, $contrasena_nueva) {
        $usuario = $this->obtenerUsuarioById($usuario_id);

        if (!$usuario) {
            throw new Exception("Usuario no encontrado");
        }

        if (!password_verify($contrasena_actual, $usuario['contrasena_hash'])) {
            throw new Exception("La contraseña actual es incorrecta");
        }

        $sql = "UPDATE usuarios SET contrasena_hash = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([password_hash($contrasena_nueva, PASSWORD_BCRYPT), $usuario_id]);
    }

    /**
     * Verificar credenciales de login
     */
    public function verificarCredenciales($nombre_usuario, $contrasena) {
        $sql = "SELECT * FROM usuarios WHERE nombre_usuario = ? AND estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([trim($nombre_usuario)]);

        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($contrasena, $usuario['contrasena_hash'])) {
            // Incrementar intentos fallidos
            if ($usuario) {
                $this->incrementarIntentosFallidos($usuario['id']);
            }
            return null;
        }

        // Reset intentos fallidos
        $this->resetearIntentosFallidos($usuario['id']);

        // Actualizar último login
        $sql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario['id']]);

        return $usuario;
    }

    /**
     * Incrementar intentos fallidos
     */
    private function incrementarIntentosFallidos($usuario_id) {
        $sql = "UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$usuario_id]);

        // Si hay más de 5 intentos fallidos, bloquear por 15 minutos
        $usuario = $this->obtenerUsuarioById($usuario_id);

        if ($usuario['intentos_fallidos'] >= 5) {
            $sql = "UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(int)$usuario_id]);
        }
    }

    /**
     * Resetear intentos fallidos
     */
    private function resetearIntentosFallidos($usuario_id) {
        $sql = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$usuario_id]);
    }

    /**
     * Obtener permisos del usuario
     */
    public function obtenerPermisosUsuario($usuario_id) {
        $sql = "SELECT DISTINCT p.* FROM permisos p
                INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                INNER JOIN usuario_rol ur ON rp.rol_id = ur.rol_id
                WHERE ur.usuario_id = ?
                ORDER BY p.modulo, p.accion";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$usuario_id]);

        return $stmt->fetchAll();
    }

    /**
     * Verificar si usuario tiene permiso
     */
    public function tienePermiso($usuario_id, $nombre_permiso) {
        $sql = "SELECT COUNT(*) as total FROM permisos p
                INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                INNER JOIN usuario_rol ur ON rp.rol_id = ur.rol_id
                WHERE ur.usuario_id = ? AND p.nombre = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$usuario_id, trim($nombre_permiso)]);

        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    }
}
