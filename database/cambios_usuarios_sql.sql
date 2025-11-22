-- ============================================
-- Sistema de Usuarios, Roles y Permisos
-- ============================================

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo', 'bloqueado') DEFAULT 'activo',
    ultimo_login DATETIME,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_correo (correo),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de permisos
CREATE TABLE IF NOT EXISTS permisos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT,
    modulo VARCHAR(50) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_modulo (modulo),
    INDEX idx_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla relación usuario-rol (muchos a muchos)
CREATE TABLE IF NOT EXISTS usuario_rol (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usuario_rol (usuario_id, rol_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_rol (rol_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla relación rol-permiso (muchos a muchos)
CREATE TABLE IF NOT EXISTS rol_permiso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rol_permiso (rol_id, permiso_id),
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE,
    INDEX idx_rol (rol_id),
    INDEX idx_permiso (permiso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Datos Iniciales
-- ============================================

-- Insertar roles por defecto
INSERT INTO roles (nombre, descripcion, estado) VALUES
('Administrador', 'Control total del sistema', 'activo'),
('Gerente', 'Acceso a reportes y gestión general', 'activo'),
('Usuario', 'Acceso básico a módulos', 'activo'),
('Inspector', 'Acceso a módulo de calidad', 'activo'),
('Operario', 'Acceso a módulo de producción', 'activo');

-- Insertar permisos
INSERT INTO permisos (nombre, descripcion, modulo, accion) VALUES
-- Permisos de Usuarios
('usuarios.listar', 'Ver lista de usuarios', 'usuarios', 'listar'),
('usuarios.crear', 'Crear nuevo usuario', 'usuarios', 'crear'),
('usuarios.editar', 'Editar usuario existente', 'usuarios', 'editar'),
('usuarios.eliminar', 'Eliminar usuario', 'usuarios', 'eliminar'),
('usuarios.cambiar_rol', 'Cambiar rol de usuario', 'usuarios', 'cambiar_rol'),
('usuarios.resetear_contrasena', 'Resetear contraseña de usuario', 'usuarios', 'resetear_contrasena'),

-- Permisos de Roles
('roles.listar', 'Ver lista de roles', 'roles', 'listar'),
('roles.crear', 'Crear nuevo rol', 'roles', 'crear'),
('roles.editar', 'Editar rol existente', 'roles', 'editar'),
('roles.eliminar', 'Eliminar rol', 'roles', 'eliminar'),
('roles.asignar_permisos', 'Asignar permisos a rol', 'roles', 'asignar_permisos'),

-- Permisos de Empleados
('empleados.listar', 'Ver lista de empleados', 'empleados', 'listar'),
('empleados.crear', 'Crear nuevo empleado', 'empleados', 'crear'),
('empleados.editar', 'Editar empleado existente', 'empleados', 'editar'),
('empleados.eliminar', 'Eliminar empleado', 'empleados', 'eliminar'),
('empleados.ver_detalle', 'Ver detalle completo del empleado', 'empleados', 'ver_detalle'),

-- Permisos de Clientes
('clientes.listar', 'Ver lista de clientes', 'clientes', 'listar'),
('clientes.crear', 'Crear nuevo cliente', 'clientes', 'crear'),
('clientes.editar', 'Editar cliente existente', 'clientes', 'editar'),
('clientes.eliminar', 'Eliminar cliente', 'clientes', 'eliminar'),

-- Permisos de Producción
('produccion.listar', 'Ver lista de órdenes de producción', 'produccion', 'listar'),
('produccion.crear', 'Crear nueva orden de producción', 'produccion', 'crear'),
('produccion.editar', 'Editar orden de producción', 'produccion', 'editar'),
('produccion.eliminar', 'Eliminar orden de producción', 'produccion', 'eliminar'),

-- Permisos de Calidad
('calidad.listar', 'Ver inspecciones de calidad', 'calidad', 'listar'),
('calidad.crear', 'Crear nueva inspección', 'calidad', 'crear'),
('calidad.editar', 'Editar inspección', 'calidad', 'editar'),
('calidad.eliminar', 'Eliminar inspección', 'calidad', 'eliminar'),

-- Permisos de Reportes
('reportes.ver', 'Ver reportes', 'reportes', 'ver'),
('reportes.exportar', 'Exportar reportes', 'reportes', 'exportar');

-- Asignar permisos al rol Administrador (todos los permisos)
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos;

-- Asignar permisos al rol Gerente
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 2, id FROM permisos
WHERE modulo IN ('empleados', 'clientes', 'produccion', 'calidad', 'reportes')
AND accion IN ('listar', 'ver_detalle', 'ver', 'exportar');

-- Asignar permisos al rol Usuario
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 3, id FROM permisos
WHERE modulo IN ('empleados', 'clientes')
AND accion = 'listar';

-- Asignar permisos al rol Inspector
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 4, id FROM permisos
WHERE modulo = 'calidad';

-- Asignar permisos al rol Operario
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 5, id FROM permisos
WHERE modulo = 'produccion';

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (hasheada con password_hash)
INSERT INTO usuarios (nombre_usuario, nombre_completo, correo, contrasena_hash, estado) VALUES
('admin', 'Administrador Sistema', 'admin@cinasa.com', '$2y$10$jDOF9JNk5JZmqFbE8lJ8duVLKv.VsKZ7Ym6dJFfJzJzLz5gFxJ.jK', 'activo');

-- Asignar rol Administrador al usuario admin
INSERT INTO usuario_rol (usuario_id, rol_id)
SELECT id, 1 FROM usuarios WHERE nombre_usuario = 'admin';
