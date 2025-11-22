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
    $rol = $model->obtenerRolById((int)$_GET['id']);

    if (!$rol) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Rol no encontrado'
        ]);
        exit;
    }

    // Obtener permisos del rol
    $permisos = $model->obtenerPermisosRol($rol['id']);
    $rol['permisos'] = $permisos;

    echo json_encode([
        'success' => true,
        'data' => $rol
    ]);
} catch (Exception $e) {
    error_log("Error al obtener rol: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener rol'
    ]);
}
