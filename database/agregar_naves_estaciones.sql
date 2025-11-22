-- ============================================================
-- Migración: Agregar campo "nave" a tabla estaciones
-- Fecha: 2025-11-04
-- ============================================================

ALTER TABLE `estaciones` ADD COLUMN `nave` varchar(50) DEFAULT 'Nave 1' COMMENT 'Nave o sección del taller donde se ubica (Nave 1, Nave 2, Nave 3, etc)' AFTER `tipo`;

-- Crear índice para búsquedas por nave
ALTER TABLE `estaciones` ADD INDEX `idx_nave` (`nave`);

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================
