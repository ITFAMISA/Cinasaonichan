-- Script para crear tablas de gestión de turnos
-- Fecha: 2025-11-20

-- =========================================================
-- Tabla: turnos
-- Descripción: Define los turnos de trabajo disponibles
-- =========================================================

CREATE TABLE IF NOT EXISTS `turnos` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `orden` int DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Tabla: asignaciones_estacion_turno
-- Descripción: Asignaciones de empleados y pedidos a estaciones en turnos específicos
-- =========================================================

CREATE TABLE IF NOT EXISTS `asignaciones_estacion_turno` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `estacion_id` int NOT NULL,
  `turno_id` int UNSIGNED NOT NULL,
  `empleado_id` bigint UNSIGNED NOT NULL,
  `pedido_id` bigint UNSIGNED NOT NULL,
  `tipo_trabajo_id` int DEFAULT NULL,
  `cantidad_total` decimal(10,2) NOT NULL,
  `cantidad_procesada` decimal(10,2) DEFAULT 0,
  `estatus` enum('pendiente', 'en_progreso', 'completado', 'cancelado') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `fecha_asignacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estacion_turno` (`estacion_id`, `turno_id`),
  KEY `idx_empleado` (`empleado_id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_turno` (`turno_id`),
  KEY `idx_estatus` (`estatus`),
  CONSTRAINT `fk_asignacion_estacion` FOREIGN KEY (`estacion_id`)
    REFERENCES `estaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_turno` FOREIGN KEY (`turno_id`)
    REFERENCES `turnos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_empleado` FOREIGN KEY (`empleado_id`)
    REFERENCES `empleados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_pedido` FOREIGN KEY (`pedido_id`)
    REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Insertar turnos por defecto
-- =========================================================

INSERT IGNORE INTO `turnos` (`nombre`, `hora_inicio`, `hora_fin`, `orden`, `activo`) VALUES
('Turno 1', '06:00:00', '14:00:00', 1, 1),
('Turno 2', '14:00:00', '22:00:00', 2, 1),
('Turno 3', '22:00:00', '06:00:00', 3, 1);
