-- ============================================================
-- SETUP DE EMPLEADO_PROCESOS - SISTEMA DE HABILIDADES
-- ============================================================
-- Este archivo contiene todas las instrucciones necesarias para
-- configurar la tabla empleado_procesos y el sistema de
-- gestión de habilidades de empleados.
-- ============================================================

-- 1. CREAR TABLA EMPLEADO_PROCESOS (si no existe)
-- Vincula empleados con procesos que pueden realizar
-- ============================================================
CREATE TABLE IF NOT EXISTS empleado_procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empleado_id BIGINT(20) UNSIGNED NOT NULL,
    proceso_id INT NOT NULL,
    nivel VARCHAR(50) COMMENT 'principiante, intermedio, avanzado',
    fecha_capacitacion DATE,
    estatus ENUM('activo', 'inactivo') DEFAULT 'activo',
    usuario_creacion INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_empleado_proceso (empleado_id, proceso_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_proceso (proceso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Vinculación de empleados con procesos que pueden realizar';

-- ============================================================
-- 2. DATOS INICIALES (EJEMPLOS)
-- ============================================================
-- Los empleados se pueden asignar a procesos mediante:
-- - Panel de Administración en /admin_empleado_procesos.php
-- - API Controller: /app/controllers/empleado_procesos_actualizar.php
--
-- Ejemplo de asignación manual (si es necesario):
-- INSERT INTO empleado_procesos (empleado_id, proceso_id, nivel, estatus, usuario_creacion)
-- VALUES (1, 1, 'intermedio', 'activo', 1);

-- ============================================================
-- 3. VERIFICACIÓN
-- ============================================================
-- Listar empleados con sus habilidades:
-- SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as empleado,
--        p.nombre as proceso, ep.nivel
-- FROM empleados e
-- LEFT JOIN empleado_procesos ep ON e.id = ep.empleado_id AND ep.estatus = 'activo'
-- LEFT JOIN procesos p ON ep.proceso_id = p.id
-- WHERE e.estatus_empleado = 'activo'
-- ORDER BY e.apellido, e.nombre, p.nombre;

-- ============================================================
-- TABLA RELACIONADA: ESTACION_PROCESOS
-- ============================================================
-- La tabla estacion_procesos vincula máquinas/estaciones con procesos
-- para que el sistema de tracking pueda filtrar máquinas por
-- proceso cuando se selecciona un empleado.
--
-- SELECT e.id, e.nombre as maquina, p.nombre as proceso
-- FROM estaciones e
-- INNER JOIN estacion_procesos ep ON e.id = ep.estacion_id
-- INNER JOIN procesos p ON ep.proceso_id = p.id
-- ORDER BY e.nave, p.nombre;
