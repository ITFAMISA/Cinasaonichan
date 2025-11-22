<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/estaciones_model.php';

header('Content-Type: application/json');

if (!hasModuleAccess('estaciones')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    $model = new EstacionesModel($pdo);

    // Obtener todas las estaciones (activas, mantenimiento e inactivas) con su información completa
    $estaciones = $model->obtenerTodasEstaciones();

    // Enriquecer con información de asignaciones actuales
    foreach ($estaciones as &$estacion) {
        $asignacion_actual = $model->obtenerAsignacionActualEstacion($estacion['id']);
        $estadisticas = $model->obtenerEstadisticasEstacion($estacion['id']);

        $estacion['asignacion_actual'] = $asignacion_actual;
        $estacion['estadisticas'] = $estadisticas;
    }

    echo json_encode([
        'success' => true,
        'data' => $estaciones
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
