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

    $es_preferida = (int)($_POST['es_preferida'] ?? 0);
    $orden_preferencia = (int)($_POST['orden_preferencia'] ?? 999);
    $notas = $_POST['notas'] ?? '';

    $model = new EstacionesModel($pdo);

    // Verificar que la estación existe
    $estacion = $model->obtenerEstacionPorId($estacion_id);
    if (!$estacion) {
        throw new Exception('Estación no encontrada');
    }

    $resultado = $model->asignarProcesoAEstacion(
        $estacion_id,
        $proceso_id,
        $es_preferida,
        $orden_preferencia,
        $notas
    );

    if (!$resultado) {
        throw new Exception('No se pudo asignar el proceso');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Proceso asignado a la estación exitosamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
