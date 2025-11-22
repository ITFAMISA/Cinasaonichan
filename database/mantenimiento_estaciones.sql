-- Script para crear tabla de mantenimiento de estaciones/máquinas
-- Fecha: 2025-11-20

-- =========================================================
-- Tabla: estacion_mantenimiento
-- Descripción: Registra periodos de mantenimiento de estaciones
-- =========================================================

CREATE TABLE IF NOT EXISTS `estacion_mantenimiento` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `estacion_id` int NOT NULL COMMENT 'ID de la estación en mantenimiento',
  `motivo` varchar(100) NOT NULL COMMENT 'Motivo del mantenimiento (máquina rota, limpieza, cambio de orden, etc)',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción detallada del mantenimiento',
  `fecha_inicio` datetime NOT NULL COMMENT 'Fecha y hora de inicio del mantenimiento',
  `fecha_fin` datetime DEFAULT NULL COMMENT 'Fecha y hora de finalización del mantenimiento',
  `horas_mantenimiento` decimal(10,2) GENERATED ALWAYS AS (
    CASE
      WHEN fecha_fin IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, fecha_inicio, fecha_fin) / 60
      ELSE 0
    END
  ) STORED COMMENT 'Horas totales de mantenimiento calculadas',
  `usuario_id` int DEFAULT NULL COMMENT 'Usuario que registró el mantenimiento',
  `estatus` enum('activo', 'completado', 'cancelado') DEFAULT 'activo' COMMENT 'Estado del registro de mantenimiento',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estacion` (`estacion_id`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_estatus` (`estatus`),
  CONSTRAINT `fk_mantenimiento_estacion` FOREIGN KEY (`estacion_id`)
    REFERENCES `estaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de mantenimiento de estaciones/máquinas';

-- =========================================================
-- Tabla: estacion_proceso_mantenimiento
-- Descripción: Resumen de horas de mantenimiento por proceso en estación
-- =========================================================

CREATE TABLE IF NOT EXISTS `estacion_proceso_mantenimiento` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `estacion_id` int NOT NULL COMMENT 'ID de la estación',
  `proceso_id` int NOT NULL COMMENT 'ID del proceso',
  `total_horas_mantenimiento` decimal(10,2) DEFAULT 0 COMMENT 'Total de horas acumuladas en mantenimiento',
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_estacion_proceso` (`estacion_id`, `proceso_id`),
  KEY `idx_proceso` (`proceso_id`),
  CONSTRAINT `fk_mant_proceso_estacion` FOREIGN KEY (`estacion_id`)
    REFERENCES `estaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mant_proceso_proceso` FOREIGN KEY (`proceso_id`)
    REFERENCES `procesos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Resumen de horas de mantenimiento por estación y proceso';

-- =========================================================
-- Índices adicionales para rendimiento
-- =========================================================

ALTER TABLE `estacion_mantenimiento`
ADD INDEX `idx_estacion_fecha` (`estacion_id`, `fecha_inicio`, `fecha_fin`),
ADD INDEX `idx_estacion_estado` (`estacion_id`, `estatus`);
