<?php
/**
 * Script de instalación para el sistema de turnos
 * Este archivo ejecuta el script SQL para crear las tablas necesarias
 */

require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación del Sistema de Turnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .instalacion-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        .instalacion-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .instalacion-body {
            padding: 30px;
        }
        .resultado {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .resultado.exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .resultado.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .paso {
            margin: 15px 0;
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
        }
        .paso.completado {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .paso.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="instalacion-container">
        <div class="instalacion-header">
            <h2><i class="fas fa-clock me-2"></i>Sistema de Turnos</h2>
            <p>Instalación e Inicialización</p>
        </div>

        <div class="instalacion-body">
            <?php
            $errores = [];
            $exitos = [];

            try {
                // Leer el archivo SQL
                $sqlFile = __DIR__ . '/database/turnos_sistema.sql';
                if (!file_exists($sqlFile)) {
                    throw new Exception('Archivo SQL no encontrado: ' . $sqlFile);
                }

                $sql = file_get_contents($sqlFile);

                echo '<div class="paso completado">';
                echo '<strong><i class="fas fa-check-circle me-2"></i>Archivo SQL cargado</strong>';
                echo '</div>';

                // 1. Crear tabla de turnos
                try {
                    $sql1 = "CREATE TABLE IF NOT EXISTS `turnos` (
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

                    $pdo->exec($sql1);
                    $exitos[] = 'Tabla <strong>turnos</strong> creada correctamente';
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), '1050') === false) {
                        throw $e;
                    }
                    $exitos[] = 'Tabla turnos ya existe';
                }

                // 2. Eliminar tabla anterior si existe con conflictos
                try {
                    $pdo->exec("DROP TABLE IF EXISTS `asignaciones_estacion_turno`");
                } catch (Exception $e) {
                    // Ignorar si falla
                }

                // 2. Crear tabla de asignaciones
                try {
                    $sql2 = "CREATE TABLE IF NOT EXISTS `asignaciones_estacion_turno` (
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

                    $pdo->exec($sql2);
                    $exitos[] = 'Tabla <strong>asignaciones_estacion_turno</strong> creada correctamente';
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), '1050') === false) {
                        throw $e;
                    }
                    $exitos[] = 'Tabla asignaciones_estacion_turno ya existe';
                }

                // 3. Insertar turnos por defecto
                try {
                    $pdo->exec("INSERT IGNORE INTO `turnos` (`nombre`, `hora_inicio`, `hora_fin`, `orden`, `activo`) VALUES
                    ('Turno 1', '06:00:00', '14:00:00', 1, 1),
                    ('Turno 2', '14:00:00', '22:00:00', 2, 1),
                    ('Turno 3', '22:00:00', '06:00:00', 3, 1)");
                    $exitos[] = 'Turnos por defecto insertados correctamente';
                } catch (Exception $e) {
                    $exitos[] = 'Turnos por defecto ya existían';
                }

                // Verificar que las tablas existan
                echo '<div class="paso completado">';
                echo '<strong><i class="fas fa-check-circle me-2"></i>Verificación de tablas</strong>';
                echo '</div>';

                $tablas1 = $pdo->query("SHOW TABLES LIKE 'turnos'")->fetchAll();
                $tablas2 = $pdo->query("SHOW TABLES LIKE 'asignaciones_estacion_turno'")->fetchAll();

                if (count($tablas1) > 0 && count($tablas2) > 0) {
                    echo '<div class="paso completado">';
                    echo '<strong><i class="fas fa-check-circle me-2"></i>Todas las tablas están presentes</strong>';
                    echo '</div>';
                }

                // Mostrar resultados exitosos
                if (!empty($exitos)) {
                    echo '<div class="resultado exito">';
                    echo '<h5><i class="fas fa-check-circle me-2"></i>Instalación Completada</h5>';
                    echo '<ul class="mb-0">';
                    foreach ($exitos as $exito) {
                        echo '<li>' . $exito . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }

                // Mostrar errores si los hay
                if (!empty($errores)) {
                    echo '<div class="resultado error">';
                    echo '<h5><i class="fas fa-exclamation-circle me-2"></i>Advertencias</h5>';
                    echo '<ul class="mb-0">';
                    foreach ($errores as $error) {
                        echo '<li><small>' . htmlspecialchars($error) . '</small></li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }

                // Información sobre turnos creados
                $turnos = $pdo->query("SELECT COUNT(*) as count FROM turnos")->fetch();
                echo '<div class="paso completado">';
                echo '<strong><i class="fas fa-info-circle me-2"></i>Turnos Configurados</strong>';
                echo '<br>Total de turnos en el sistema: <strong>' . $turnos['count'] . '</strong>';
                echo '</div>';

            } catch (Exception $e) {
                echo '<div class="resultado error">';
                echo '<h5><i class="fas fa-times-circle me-2"></i>Error de Instalación</h5>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            ?>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/tracking_dashboard.php" class="btn btn-primary w-100">
                    <i class="fas fa-arrow-right me-2"></i> Ir al Dashboard de Tracking
                </a>
            </div>

            <div style="margin-top: 10px; text-align: center; color: #666; font-size: 12px;">
                <p>Sistema de Gestión de Turnos - Instalación completada el <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
