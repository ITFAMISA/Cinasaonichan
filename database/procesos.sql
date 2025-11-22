-- Tabla de procesos de producción
CREATE TABLE IF NOT EXISTS procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    requiere_inspeccion_calidad BOOLEAN DEFAULT FALSE,
    estatus ENUM('activo', 'inactivo') DEFAULT 'activo',
    usuario_creacion INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id),
    INDEX idx_estatus (estatus),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de rutas de procesos por producto
CREATE TABLE IF NOT EXISTS producto_rutas_procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    proceso_id INT NOT NULL,
    orden_secuencia INT NOT NULL,
    notas TEXT,
    estatus ENUM('activo', 'inactivo') DEFAULT 'activo',
    usuario_creacion INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_producto_proceso (producto_id, proceso_id),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id),
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id),
    INDEX idx_producto_id (producto_id),
    INDEX idx_proceso_id (proceso_id),
    INDEX idx_orden (orden_secuencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar procesos iniciales de ejemplo
INSERT INTO procesos (nombre, descripcion, requiere_inspeccion_calidad) VALUES
('Corte', 'Proceso de corte de materiales', FALSE),
('Doblez', 'Proceso de doblado de piezas', FALSE),
('Maquinado', 'Proceso de maquinado y pulido', FALSE),
('Pintura', 'Proceso de pintura y acabado', FALSE),
('Soldadura', 'Proceso de soldadura de componentes', FALSE),
('Roscado', 'Proceso de roscado de orificios', FALSE),
('Empaque', 'Proceso de empaque y embalaje', FALSE),
('Inspección de Calidad', 'Inspección final de calidad', TRUE)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
