-- Script para crear las tablas del nuevo sistema de tracking
-- Fecha: 2025-11-13

-- Tabla principal de asignaciones de empleados a pedidos
CREATE TABLE IF NOT EXISTS `tracking_asignaciones` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint UNSIGNED NOT NULL COMMENT 'ID del empleado asignado',
  `pedido_id` bigint UNSIGNED NOT NULL COMMENT 'ID del pedido',
  `item_id` bigint UNSIGNED DEFAULT NULL COMMENT 'ID del item del pedido (opcional)',
  `tipo_trabajo_id` int NOT NULL COMMENT 'ID del tipo de trabajo',
  `area_id` int DEFAULT NULL COMMENT 'Área de trabajo donde se realiza',
  `fecha_asignacion` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de asignación',
  `hora_inicio` datetime DEFAULT NULL COMMENT 'Hora de inicio del trabajo',
  `hora_fin` datetime DEFAULT NULL COMMENT 'Hora de fin del trabajo',
  `minutos_trabajados` int DEFAULT 0 COMMENT 'Minutos trabajados totales',
  `cantidad_procesada` decimal(10,2) DEFAULT '0.00' COMMENT 'Cantidad procesada',
  `estatus` enum('asignado','en_proceso','pausado','completado','cancelado') DEFAULT 'asignado' COMMENT 'Estado de la asignación',
  `observaciones` text COMMENT 'Observaciones o detalles adicionales',
  `usuario_creacion` int DEFAULT NULL COMMENT 'Usuario que creó el registro',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tracking_empleado` (`empleado_id`),
  KEY `idx_tracking_pedido` (`pedido_id`),
  KEY `idx_tracking_item` (`item_id`),
  KEY `idx_tracking_tipo_trabajo` (`tipo_trabajo_id`),
  KEY `idx_tracking_area` (`area_id`),
  KEY `idx_tracking_estatus` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignaciones de empleados a pedidos para tracking de trabajo';

-- Tabla de tipos de trabajo
CREATE TABLE IF NOT EXISTS `tracking_tipos_trabajo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL COMMENT 'Nombre del tipo de trabajo',
  `descripcion` text COMMENT 'Descripción detallada',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Color para representación visual (#RRGGBB)',
  `icono` varchar(50) DEFAULT NULL COMMENT 'Clase de icono (FontAwesome)',
  `estatus` enum('activo','inactivo') DEFAULT 'activo' COMMENT 'Estado del tipo de trabajo',
  `orden` int DEFAULT 0 COMMENT 'Orden de visualización',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipo_trabajo_nombre` (`nombre`),
  KEY `idx_tipo_trabajo_estatus` (`estatus`),
  KEY `idx_tipo_trabajo_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de tipos de trabajo para tracking';

-- Tabla para registro detallado de tiempo
CREATE TABLE IF NOT EXISTS `tracking_tiempo_detallado` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `asignacion_id` bigint UNSIGNED NOT NULL COMMENT 'ID de la asignación',
  `empleado_id` bigint UNSIGNED NOT NULL COMMENT 'ID del empleado',
  `fecha_registro` date NOT NULL COMMENT 'Fecha del registro',
  `hora_inicio` time NOT NULL COMMENT 'Hora de inicio',
  `hora_fin` time NOT NULL COMMENT 'Hora de fin',
  `minutos_trabajados` int NOT NULL COMMENT 'Minutos trabajados en esta sesión',
  `cantidad_procesada` decimal(10,2) DEFAULT '0.00' COMMENT 'Cantidad procesada en esta sesión',
  `notas` text COMMENT 'Notas adicionales',
  `usuario_registro` int DEFAULT NULL COMMENT 'Usuario que registró',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tiempo_asignacion` (`asignacion_id`),
  KEY `idx_tiempo_empleado` (`empleado_id`),
  KEY `idx_tiempo_fecha` (`fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro detallado de tiempo trabajado por empleado';

-- Tabla para áreas de trabajo
CREATE TABLE IF NOT EXISTS `tracking_areas_trabajo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre del área (Taller 1, Línea A, etc)',
  `descripcion` text COMMENT 'Descripción detallada',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Color para representación visual (#RRGGBB)',
  `orden` int DEFAULT 0 COMMENT 'Orden de visualización',
  `estatus` enum('activa','inactiva') DEFAULT 'activa' COMMENT 'Estado del área',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_area_orden` (`orden`),
  KEY `idx_area_estatus` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Áreas o zonas para organizar el layout del tracking';

-- Tabla opcional para habilidades de empleados
CREATE TABLE IF NOT EXISTS `tracking_empleado_habilidades` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint UNSIGNED NOT NULL COMMENT 'ID del empleado',
  `tipo_trabajo_id` int NOT NULL COMMENT 'ID del tipo de trabajo',
  `nivel` enum('principiante','intermedio','avanzado','experto') DEFAULT 'intermedio' COMMENT 'Nivel de habilidad',
  `fecha_certificacion` date DEFAULT NULL COMMENT 'Fecha de certificación (si aplica)',
  `notas` text COMMENT 'Notas adicionales',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empleado_tipo_trabajo` (`empleado_id`,`tipo_trabajo_id`),
  KEY `idx_habilidad_empleado` (`empleado_id`),
  KEY `idx_habilidad_tipo` (`tipo_trabajo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Habilidades y tipos de trabajo que puede realizar cada empleado';

-- Nota: Los datos iniciales se encuentran en archivos separados:
-- - tracking_insert_tipos_trabajo.sql (para tipos de trabajo)
-- - tracking_insert_areas.sql (para áreas de trabajo)