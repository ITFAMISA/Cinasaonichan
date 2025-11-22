-- Script para insertar áreas de trabajo iniciales en tracking
-- Ejecutar después de crear las tablas con tracking_sistema.sql

INSERT IGNORE INTO `tracking_areas_trabajo` (`nombre`, `descripcion`, `color`, `orden`, `estatus`) VALUES
('Nave 1', 'Nave principal de producción', '#3498db', 1, 'activa'),
('Nave 2', 'Nave de ensamblaje y conformado', '#2ecc71', 2, 'activa'),
('Nave 3', 'Nave de acabados y procesos finales', '#f39c12', 3, 'activa');