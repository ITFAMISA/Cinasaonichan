<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Verificar si existen las tablas principales
    $tablas = [
        'tracking_asignaciones',
        'tracking_tipos_trabajo',
        'tracking_tiempo_detallado',
        'tracking_areas_trabajo'
    ];

    $tablasExistentes = 0;
    $totalTablas = count($tablas);

    foreach ($tablas as $tabla) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :tabla");
        $stmt->execute([':tabla' => $tabla]);

        if ($stmt->rowCount() > 0) {
            $tablasExistentes++;
        }
    }

    // Si todas las tablas existen
    if ($tablasExistentes === $totalTablas) {
        echo json_encode([
            'success' => true,
            'message' => 'Todas las tablas de tracking están configuradas correctamente'
        ]);
    } else {
        // Si faltan tablas
        echo json_encode([
            'success' => false,
            'message' => "Faltan tablas del sistema de tracking. Encontradas $tablasExistentes de $totalTablas",
            'tablas_encontradas' => $tablasExistentes,
            'tablas_requeridas' => $totalTablas
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar tablas: ' . $e->getMessage()
    ]);
}