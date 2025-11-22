-- Script para insertar tipos de trabajo iniciales en tracking
-- Ejecutar después de crear las tablas con tracking_sistema.sql

INSERT IGNORE INTO `tracking_tipos_trabajo` (`nombre`, `descripcion`, `color`, `estatus`, `orden`) VALUES
('ARMADO', 'Proceso de armado y ensamblaje de piezas', '#3498db', 'activo', 1),
('CORTE', 'Proceso de corte de material', '#e74c3c', 'activo', 2),
('CORTE SIERRA CINTA', 'Proceso de corte utilizando sierra cinta', '#9b59b6', 'activo', 3),
('DETALLADO', 'Proceso de acabados y detalles finales', '#1abc9c', 'activo', 4),
('CONFORMADO', 'Proceso de conformado de material', '#f39c12', 'activo', 5),
('DOBLEZ', 'Proceso de doblez de material', '#27ae60', 'activo', 6);