-- Limpiar áreas de trabajo duplicadas
-- Mantener solo las 3 áreas principales

-- Primero, eliminar todas las áreas
DELETE FROM `tracking_areas_trabajo`;

-- Luego, insertar solo las 3 correctas con IDs específicos
INSERT INTO `tracking_areas_trabajo` (`id`, `nombre`, `descripcion`, `color`, `orden`, `estatus`) VALUES
(1, 'Nave 1', 'Nave principal de producción', '#3498db', 1, 'activa'),
(2, 'Nave 2', 'Nave de ensamblaje y conformado', '#2ecc71', 2, 'activa'),
(3, 'Nave 3', 'Nave de acabados y procesos finales', '#f39c12', 3, 'activa');