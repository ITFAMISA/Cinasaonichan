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
    $estacion_id = (int)($_GET['estacion_id'] ?? 0);
    if (empty($estacion_id)) {
        throw new Exception('ID de estación requerido');
    }

    $model = new EstacionesModel($pdo);

    // Verificar que la estación existe
    $estacion = $model->obtenerEstacionPorId($estacion_id);
    if (!$estacion) {
        throw new Exception('Estación no encontrada');
    }

    $procesos = $model->obtenerProcessosEstacion($estacion_id);

    echo json_encode([
        'success' => true,
        'data' => $procesos
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
