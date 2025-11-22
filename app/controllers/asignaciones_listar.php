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
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limite = (int)($_GET['limite'] ?? 50);
    $offset = ($page - 1) * $limite;

    $filtros = [
        'estatus' => $_GET['estatus'] ?? ''
    ];

    $model = new EstacionesModel($pdo);

    if (!empty($estacion_id)) {
        // Verificar que la estación existe
        $estacion = $model->obtenerEstacionPorId($estacion_id);
        if (!$estacion) {
            throw new Exception('Estación no encontrada');
        }

        $asignaciones = $model->obtenerAsignacionesEstacion($estacion_id, $filtros, $limite, $offset);
    } else {
        // Obtener todas las asignaciones activas
        $asignaciones = $model->obtenerAsignacionesActivasGlobal();
    }

    echo json_encode([
        'success' => true,
        'data' => $asignaciones
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
