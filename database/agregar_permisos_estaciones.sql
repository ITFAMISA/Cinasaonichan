-- ============================================================
-- Script: Agregar Permisos para Módulo de Estaciones
-- Fecha: 2025-11-04
-- Descripción: Agrega permisos para el módulo de estaciones/máquinas
-- ============================================================

-- Insertar permisos del módulo estaciones
INSERT INTO `permisos` (`id`, `nombre`, `descripcion`, `modulo`, `accion`, `fecha_creacion`) VALUES
(49, 'estaciones.listar', 'Ver lista de estaciones/máquinas', 'estaciones', 'listar', NOW()),
(50, 'estaciones.crear', 'Crear nueva estación/máquina', 'estaciones', 'crear', NOW()),
(51, 'estaciones.editar', 'Editar estación/máquina existente', 'estaciones', 'editar', NOW()),
(52, 'estaciones.eliminar', 'Eliminar/desactivar estación', 'estaciones', 'eliminar', NOW()),
(53, 'estaciones.asignar_procesos', 'Asignar procesos a estaciones', 'estaciones', 'asignar_procesos', NOW()),
(54, 'estaciones.ver_dashboard', 'Ver dashboard/layout del taller', 'estaciones', 'ver_dashboard', NOW());

-- Asignar todos los permisos de estaciones al rol Administrador (rol_id = 1)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES
(1, 49),  -- estaciones.listar
(1, 50),  -- estaciones.crear
(1, 51),  -- estaciones.editar
(1, 52),  -- estaciones.eliminar
(1, 53),  -- estaciones.asignar_procesos
(1, 54);  -- estaciones.ver_dashboard

-- Asignar permisos básicos de estaciones al rol Gerente (rol_id = 2)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES
(2, 49),  -- estaciones.listar
(2, 54);  -- estaciones.ver_dashboard

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
