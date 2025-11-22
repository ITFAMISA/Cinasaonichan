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
    $estacion_id = (int)($_POST['estacion_id'] ?? 0);
    $proceso_id = (int)($_POST['proceso_id'] ?? 0);

    if (empty($estacion_id) || empty($proceso_id)) {
        throw new Exception('ID de estación y proceso requeridos');
    }

    $model = new EstacionesModel($pdo);

    // Verificar que la estación existe
    $estacion = $model->obtenerEstacionPorId($estacion_id);
    if (!$estacion) {
        throw new Exception('Estación no encontrada');
    }

    $resultado = $model->eliminarEstacionProceso($estacion_id, $proceso_id);

    if (!$resultado) {
        throw new Exception('No se pudo eliminar la asignación');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Proceso desasignado de la estación exitosamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
