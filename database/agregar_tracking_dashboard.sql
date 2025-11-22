-- Script para agregar módulo de Tracking Dashboard
-- Ejecutar este script para añadir el módulo y permisos al sistema

-- 1. Verificar e insertar el módulo si no existe
INSERT IGNORE INTO permisos (nombre, modulo, accion, descripcion, estado)
VALUES
    ('ver_tracking_dashboard', 'tracking_dashboard', 'view', 'Ver dashboard de tracking', 'activo');

-- 2. Obtener el ID del permiso recién insertado o existente
SET @permiso_id = (SELECT id FROM permisos WHERE nombre = 'ver_tracking_dashboard' LIMIT 1);

-- 3. Asignar el permiso a los roles (Administrador y Supervisor)
-- Obtener ID de Administrador
SET @admin_rol_id = (SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1);

-- Obtener ID de Supervisor (si existe)
SET @supervisor_rol_id = (SELECT id FROM roles WHERE nombre = 'Supervisor' LIMIT 1);

-- Asignar al rol Administrador si existe
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT @admin_rol_id, @permiso_id
WHERE @admin_rol_id IS NOT NULL;

-- Asignar al rol Supervisor si existe
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT @supervisor_rol_id, @permiso_id
WHERE @supervisor_rol_id IS NOT NULL;

-- Mostrar resultado
SELECT 'Módulo tracking_dashboard agregado exitosamente' as resultado;
SELECT * FROM permisos WHERE nombre = 'ver_tracking_dashboard';
