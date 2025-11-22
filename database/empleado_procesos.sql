-- Tabla para vincular empleados con procesos que pueden realizar
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
