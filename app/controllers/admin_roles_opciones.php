<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/roles_model.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$model = new RolesModel($pdo);

try {
    $roles = $model->listarRoles('activo');

    echo json_encode([
        'success' => true,
        'data' => $roles
    ]);
} catch (Exception $e) {
    error_log("Error al listar opciones de roles: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener roles'
    ]);
}
