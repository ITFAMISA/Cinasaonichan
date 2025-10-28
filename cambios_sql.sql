-- ========================================================
-- SCRIPT DE CAMBIOS PARA MÓDULO DE EMPLEADOS
-- ========================================================
-- Este script agrega la tabla de empleados y sus configuraciones
-- a la base de datos clientes_db

-- ========================================================
-- 1. CREAR TABLA DE EMPLEADOS
-- ========================================================

CREATE TABLE IF NOT EXISTS `empleados` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre del empleado - REQUERIDO',
  `apellido` varchar(100) NOT NULL COMMENT 'Apellido del empleado - REQUERIDO',
  `puesto` varchar(100) NOT NULL COMMENT 'Puesto del empleado - REQUERIDO',
  `numero_empleado` varchar(50) UNIQUE DEFAULT NULL COMMENT 'Número de empleado único',
  `correo` varchar(150) DEFAULT NULL COMMENT 'Correo electrónico del empleado',
  `telefono` varchar(30) DEFAULT NULL COMMENT 'Número de teléfono',
  `telefono_extension` varchar(10) DEFAULT NULL COMMENT 'Extensión telefónica',
  `departamento` varchar(100) DEFAULT NULL COMMENT 'Departamento asignado',
  `fecha_ingreso` date DEFAULT NULL COMMENT 'Fecha de ingreso a la empresa',
  `fecha_nacimiento` date DEFAULT NULL COMMENT 'Fecha de nacimiento',
  `genero` enum('M','F','Otro') DEFAULT NULL COMMENT 'Género del empleado',
  `numero_identificacion` varchar(50) DEFAULT NULL COMMENT 'RFC, Cédula o número de identificación',
  `tipo_identificacion` varchar(50) DEFAULT NULL COMMENT 'Tipo de identificación (RFC, INE, Pasaporte, etc)',
  `numero_seguro_social` varchar(50) DEFAULT NULL COMMENT 'Número de seguro social o equivalente',
  `banco` varchar(150) DEFAULT NULL COMMENT 'Banco para depósito de nómina',
  `cuenta_bancaria` varchar(50) DEFAULT NULL COMMENT 'Número de cuenta bancaria',
  `clabe` varchar(18) DEFAULT NULL COMMENT 'CLABE interbancaria para transferencias',
  `direccion` text DEFAULT NULL COMMENT 'Dirección de domicilio',
  `ciudad` varchar(100) DEFAULT NULL COMMENT 'Ciudad de residencia',
  `estado` varchar(100) DEFAULT NULL COMMENT 'Estado de residencia',
  `codigo_postal` varchar(10) DEFAULT NULL COMMENT 'Código postal',
  `pais` varchar(100) DEFAULT 'México' COMMENT 'País de residencia',
  `contacto_emergencia_nombre` varchar(150) DEFAULT NULL COMMENT 'Nombre del contacto de emergencia',
  `contacto_emergencia_relacion` varchar(50) DEFAULT NULL COMMENT 'Relación con el contacto de emergencia',
  `contacto_emergencia_telefono` varchar(30) DEFAULT NULL COMMENT 'Teléfono del contacto de emergencia',
  `estado_civil` enum('Soltero','Casado','Divorciado','Viudo','Unión Libre','Otro') DEFAULT NULL COMMENT 'Estado civil',
  `cantidad_dependientes` int(11) DEFAULT 0 COMMENT 'Número de dependientes económicos',
  `nivel_escolaridad` varchar(100) DEFAULT NULL COMMENT 'Nivel máximo de estudios',
  `especialidad` varchar(100) DEFAULT NULL COMMENT 'Especialidad o carrera profesional',
  `estatus_empleado` enum('activo','inactivo','licencia','suspendido','jubilado') DEFAULT 'activo' COMMENT 'Estado del empleado',
  `salario_base` decimal(15,2) DEFAULT 0.00 COMMENT 'Salario base mensual',
  `tipo_contrato` varchar(100) DEFAULT NULL COMMENT 'Tipo de contrato (Tiempo indeterminado, Temporal, Temporal Indefinido, etc)',
  `fecha_contrato` date DEFAULT NULL COMMENT 'Fecha de inicio del contrato actual',
  `supervisor_directo_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID del empleado que es supervisor directo',
  `observaciones` text DEFAULT NULL COMMENT 'Observaciones generales del empleado',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro en el sistema',
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de empleados con información laboral y personal';

-- ========================================================
-- 2. AGREGAR ÍNDICES A LA TABLA EMPLEADOS
-- ========================================================

ALTER TABLE `empleados`
  ADD UNIQUE KEY `numero_empleado` (`numero_empleado`),
  ADD KEY `idx_puesto` (`puesto`),
  ADD KEY `idx_departamento` (`departamento`),
  ADD KEY `idx_estatus_empleado` (`estatus_empleado`),
  ADD KEY `idx_apellido` (`apellido`),
  ADD KEY `idx_supervisor_directo_id` (`supervisor_directo_id`);

-- ========================================================
-- 3. AGREGAR CONSTRAINT DE CLAVE FORÁNEA (SUPERVISOR)
-- ========================================================

ALTER TABLE `empleados`
  ADD CONSTRAINT `fk_supervisor` FOREIGN KEY (`supervisor_directo_id`) REFERENCES `empleados` (`id`) ON DELETE SET NULL;

-- ========================================================
-- 4. INSERTAR DATOS DE EJEMPLO
-- ========================================================

INSERT INTO `empleados` (`nombre`, `apellido`, `puesto`, `numero_empleado`, `correo`, `telefono`, `departamento`, `fecha_ingreso`, `numero_identificacion`, `tipo_identificacion`, `estatus_empleado`, `salario_base`, `tipo_contrato`, `fecha_contrato`) VALUES
('Marcos', 'Palomo', 'Supervisor de Producción', 'EMP001', 'marcosp@gmail.com', '8662520822', 'Producción', '2025-10-24', 'PACM020626M33', 'RFC', 'activo', 15000.00, 'Tiempo Indeterminado', '2025-10-24'),
('Juan', 'Pérez', 'Inspector de Calidad', 'EMP002', 'jperez@gmail.com', '5551234567', 'Calidad', '2025-10-24', 'PEGJ950101ABC', 'RFC', 'activo', 12000.00, 'Tiempo Indeterminado', '2025-10-24');

-- ========================================================
-- RESUMEN DE CAMBIOS
-- ========================================================
-- ✓ Tabla empleados creada con campos requeridos:
--   - nombre (VARCHAR 100, NOT NULL)
--   - apellido (VARCHAR 100, NOT NULL)
--   - puesto (VARCHAR 100, NOT NULL)
--
-- ✓ Campos opcionales agregados para funcionalidades futuras:
--   - Datos de contacto (correo, telefono, extension)
--   - Información personal (fecha_nacimiento, genero, estado_civil)
--   - Documentos (número_identificacion, tipo_identificacion, seguro_social)
--   - Información bancaria (banco, cuenta, CLABE)
--   - Dirección (completa con ciudad, estado, código postal)
--   - Contacto de emergencia (nombre, relación, teléfono)
--   - Información laboral (departamento, fecha_ingreso, salario, tipo_contrato)
--   - Relaciones jerárquicas (supervisor_directo_id)
--
-- ✓ Índices creados para optimizar búsquedas
-- ✓ Constraint de clave foránea para jerarquía de supervisores
-- ========================================================
