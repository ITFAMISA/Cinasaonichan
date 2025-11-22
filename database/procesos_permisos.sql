-- Agregar permisos para el módulo de Procesos
INSERT INTO permisos (nombre, descripcion, modulo, accion) VALUES
('procesos.listar', 'Listar procesos de producción', 'procesos', 'listar'),
('procesos.crear', 'Crear nuevo proceso', 'procesos', 'crear'),
('procesos.editar', 'Editar procesos existentes', 'procesos', 'editar'),
('procesos.eliminar', 'Eliminar procesos', 'procesos', 'eliminar')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Asignar permisos de procesos al rol Administrador
-- Primero obtener el ID del rol Administrador (típicamente es 1)
-- Luego asignar todos los permisos de procesos
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos WHERE modulo = 'procesos'
ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id);

-- Opcionalmente, también asignar al rol Gerente (típicamente es 2)
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 2, id FROM permisos WHERE modulo = 'procesos'
ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id);
