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
    $producto_id = (int)($_GET['producto_id'] ?? 0);

    if (!$producto_id) {
        throw new Exception('ID de producto requerido');
    }

    $model = new ProcesosModel($pdo);
    $rutas = $model->obtenerRutaProcesosProducto($producto_id);

    // Log para debugging
    error_log("Listando rutas para producto $producto_id - Total encontrado: " . count($rutas));

    echo json_encode([
        'success' => true,
        'data' => $rutas
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
