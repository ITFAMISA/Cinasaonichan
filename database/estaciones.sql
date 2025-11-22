-- ============================================================
-- Migración: Sistema de Gestión de Estaciones/Máquinas
-- Fecha: 2025-11-04
-- Descripción: Tablas para manejo de máquinas, asignación de procesos y tracking de producción
-- ============================================================

-- --------------------------------------------------------
-- Tabla: estaciones (máquinas del taller)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `estaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre de la estación/máquina (ej: Sierra Cinta 1, Plasma, etc)',
  `tipo` varchar(100) NOT NULL COMMENT 'Tipo de estación (corte, soldadura, doblez, etc)',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción de la máquina',
  `ubicacion_x` int(11) DEFAULT 0 COMMENT 'Coordenada X en el plano del taller (para layout visual)',
  `ubicacion_y` int(11) DEFAULT 0 COMMENT 'Coordenada Y en el plano del taller (para layout visual)',
  `ancho` int(11) DEFAULT 50 COMMENT 'Ancho en píxeles para visualización',
  `alto` int(11) DEFAULT 50 COMMENT 'Alto en píxeles para visualización',
  `color` varchar(7) DEFAULT '#4CAF50' COMMENT 'Color de identificación en layout (#RRGGBB)',
  `estatus` enum('activa','mantenimiento','inactiva') DEFAULT 'activa' COMMENT 'Estado operacional',
  `observaciones` text DEFAULT NULL COMMENT 'Notas y observaciones',
  `usuario_creacion` int(11) DEFAULT NULL COMMENT 'Usuario que creó el registro',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_estatus` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de estaciones/máquinas del taller';

-- --------------------------------------------------------
-- Tabla: estacion_procesos (relación M:M)
-- Descripción: Asigna qué procesos puede realizar cada estación
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `estacion_procesos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estacion_id` int(11) NOT NULL,
  `proceso_id` int(11) NOT NULL,
  `es_preferida` tinyint(1) DEFAULT 0 COMMENT 'Si es 1, es la máquina preferida para este proceso',
  `orden_preferencia` int(11) DEFAULT 999 COMMENT 'Orden de preferencia (menor = más preferida)',
  `notas` text DEFAULT NULL COMMENT 'Notas sobre configuración o limitaciones',
  `estatus` enum('activo','inactivo') DEFAULT 'activo',
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estacion_proceso` (`estacion_id`, `proceso_id`),
  KEY `idx_proceso` (`proceso_id`),
  KEY `idx_preferida` (`es_preferida`),
  CONSTRAINT `fk_estacion_procesos_estacion` FOREIGN KEY (`estacion_id`)
    REFERENCES `estaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_estacion_procesos_proceso` FOREIGN KEY (`proceso_id`)
    REFERENCES `procesos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación: qué máquinas pueden hacer cada proceso';

-- --------------------------------------------------------
-- Tabla: asignaciones_estaciones (tracking de producción en máquinas)
-- Descripción: Asigna pedidos/tareas a estaciones y trackea su progreso
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `asignaciones_estaciones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `estacion_id` int(11) NOT NULL COMMENT 'Estación/máquina asignada',
  `pedido_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Pedido en el que se trabaja',
  `producto_id` int(11) NOT NULL COMMENT 'Producto siendo procesado',
  `proceso_id` int(11) NOT NULL COMMENT 'Proceso ejecutándose',
  `numero_pedido` varchar(50) NOT NULL COMMENT 'Referencia del pedido',
  `cantidad_total` decimal(10,2) NOT NULL COMMENT 'Cantidad total a procesar',
  `cantidad_procesada` decimal(10,2) DEFAULT 0.00 COMMENT 'Cantidad ya procesada',
  `cantidad_pendiente` decimal(10,2) GENERATED ALWAYS AS (`cantidad_total` - `cantidad_procesada`) STORED,
  `fecha_asignacion` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo se asignó',
  `fecha_fin_estimada` datetime DEFAULT NULL COMMENT 'Finalización estimada',
  `fecha_inicio_real` datetime DEFAULT NULL COMMENT 'Cuándo comenzó realmente',
  `fecha_fin_real` datetime DEFAULT NULL COMMENT 'Cuándo terminó realmente',
  `estatus` enum('pendiente','en_progreso','pausada','completada','cancelada') DEFAULT 'pendiente' COMMENT 'Estado de la asignación',
  `observaciones` text DEFAULT NULL COMMENT 'Notas sobre el trabajo',
  `empleado_id` int(11) DEFAULT NULL COMMENT 'Operario asignado (si aplica)',
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estacion` (`estacion_id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_proceso` (`proceso_id`),
  KEY `idx_estatus` (`estatus`),
  KEY `idx_fecha_asignacion` (`fecha_asignacion`),
  KEY `idx_empleado` (`empleado_id`),
  CONSTRAINT `fk_asignaciones_estacion` FOREIGN KEY (`estacion_id`)
    REFERENCES `estaciones` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asignaciones_pedido` FOREIGN KEY (`pedido_id`)
    REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asignaciones_producto` FOREIGN KEY (`producto_id`)
    REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asignaciones_proceso` FOREIGN KEY (`proceso_id`)
    REFERENCES `procesos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignaciones de tareas a estaciones (qué se está haciendo en cada máquina)';

-- ============================================================
-- DATOS INICIALES (Ejemplo)
-- ============================================================

INSERT INTO `estaciones` (`nombre`, `tipo`, `descripcion`, `ubicacion_x`, `ubicacion_y`, `color`, `estatus`, `observaciones`) VALUES
('Sierra Cinta 1', 'Corte', 'Sierra cinta para cortes rápidos', 50, 50, '#FF5722', 'activa', NULL),
('Plasma', 'Corte', 'Cortador plasma de precisión', 250, 50, '#FF5722', 'activa', NULL),
('Oxicorte Pantógrafo', 'Corte', 'Cortador oxiacetilén con pantógrafo', 450, 50, '#FF5722', 'activa', NULL),
('Dobladora Hidráulica 1', 'Doblez', 'Prensa dobladora hidráulica', 50, 200, '#2196F3', 'activa', NULL),
('Dobladora Neumática', 'Doblez', 'Prensa dobladora neumática', 250, 200, '#2196F3', 'activa', NULL),
('Soldadora MIG 1', 'Soldadura', 'Soldadora MIG semiautomática', 50, 350, '#4CAF50', 'activa', NULL),
('Soldadora TIG', 'Soldadura', 'Soldadora TIG de precisión', 250, 350, '#4CAF50', 'activa', NULL),
('Torno CNC', 'Maquinado', 'Torno CNC', 50, 500, '#9C27B0', 'activa', NULL),
('Fresadora CNC', 'Maquinado', 'Fresadora CNC', 250, 500, '#9C27B0', 'activa', NULL),
('Cabina Pintura', 'Pintura', 'Cabina de pintura por aspersión', 450, 350, '#FF9800', 'activa', NULL),
('Mesa Inspección', 'Inspección', 'Mesa de control de calidad', 50, 650, '#673AB7', 'activa', NULL);

-- ============================================================
-- Asignaciones iniciales: qué máquinas hacen cada proceso
-- ============================================================

-- Proceso: Corte
INSERT INTO `estacion_procesos` (`estacion_id`, `proceso_id`, `es_preferida`, `orden_preferencia`) VALUES
(1, 1, 1, 1),   -- Sierra Cinta es preferida para corte
(2, 1, 0, 2),   -- Plasma también puede cortar
(3, 1, 0, 3);   -- Oxicorte es alternativa

-- Proceso: Doblez
INSERT INTO `estacion_procesos` (`estacion_id`, `proceso_id`, `es_preferida`, `orden_preferencia`) VALUES
(4, 2, 1, 1),   -- Dobladora Hidráulica es preferida
(5, 2, 0, 2);   -- Neumática más rápida pero limitaciones

-- Proceso: Maquinado
INSERT INTO `estacion_procesos` (`estacion_id`, `proceso_id`, `es_preferida`, `orden_preferencia`) VALUES
(8, 3, 1, 1),   -- Torno CNC
(9, 3, 0, 2);   -- Fresadora CNC

-- Proceso: Pintura
INSERT INTO `estacion_procesos` (`estacion_id`, `proceso_id`, `es_preferida`, `orden_preferencia`) VALUES
(10, 4, 1, 1);  -- Cabina pintura

-- Proceso: Soldadura
INSERT INTO `estacion_procesos` (`estacion_id`, `proceso_id`, `es_preferida`, `orden_preferencia`) VALUES
(6, 5, 1, 1),   -- MIG es preferida
(7, 5, 0, 2);   -- TIG para trabajos de precisión

-- Proceso: Roscado
INSERT INTO `estacion_procesos` (`estacion_id`, `proceso_id`, `es_preferida`, `orden_preferencia`) VALUES
(8, 6, 1, 1),   -- Torno CNC
(9, 6, 0, 2);   -- Fresadora CNC

-- Proceso: Inspección de Calidad
INSERT INTO `estacion_procesos` (`estacion_id`, `proceso_id`, `es_preferida`, `orden_preferencia`) VALUES
(11, 8, 1, 1);  -- Mesa de inspección

-- ============================================================
-- ÍNDICES ADICIONALES (rendimiento)
-- ============================================================

ALTER TABLE `asignaciones_estaciones`
ADD INDEX `idx_estacion_estatus` (`estacion_id`, `estatus`),
ADD INDEX `idx_pedido_proceso` (`pedido_id`, `proceso_id`);

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
