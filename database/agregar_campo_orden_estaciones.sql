-- ============================================================
-- Migración: Agregar campo 'orden' a tabla estaciones
-- Fecha: 2025-11-07
-- Descripción: Permite controlar el orden de visualización de estaciones en el dashboard
-- ============================================================

-- Agregar columna 'orden' a la tabla estaciones
ALTER TABLE `estaciones`
ADD COLUMN `orden` int(11) DEFAULT 0 COMMENT 'Orden de visualización en el dashboard (dentro de cada nave)' AFTER `estatus`;

-- Crear índice para mejorar rendimiento en ordenamiento
ALTER TABLE `estaciones`
ADD INDEX `idx_orden` (`orden`);

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
