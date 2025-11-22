<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/procesos_model.php';

header('Content-Type: application/json');

// Verificar permiso de acceso
if (!hasModuleAccess('procesos')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || empty($data['ruta_id'])) {
        throw new Exception('ID de ruta requerido');
    }

    $model = new ProcesosModel($pdo);
    $model->eliminarRutaProceso($data['ruta_id']);

    echo json_encode([
        'success' => true,
        'message' => 'Proceso removido de la ruta correctamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
