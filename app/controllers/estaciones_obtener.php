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
    $id = (int)($_GET['id'] ?? 0);
    if (empty($id)) {
        throw new Exception('ID de estación requerido');
    }

    $model = new EstacionesModel($pdo);
    $estacion = $model->obtenerEstacionPorId($id);

    if (!$estacion) {
        throw new Exception('Estación no encontrada');
    }

    echo json_encode([
        'success' => true,
        'data' => $estacion
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
