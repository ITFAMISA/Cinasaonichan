<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/roles_model.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de rol requerido'
    ]);
    exit;
}

$model = new RolesModel($pdo);

try {
    $id = (int)$_GET['id'];
    $model->eliminarRol($id);

    echo json_encode([
        'success' => true,
        'message' => 'Rol eliminado exitosamente'
    ]);
} catch (Exception $e) {
    error_log("Error al eliminar rol: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
