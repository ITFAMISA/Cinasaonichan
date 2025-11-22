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
    $agrupar = isset($_GET['agrupar']) && $_GET['agrupar'] === '1';

    if ($agrupar) {
        $permisos = $model->obtenerPermisosAgrupadosPorModulo();
    } else {
        $permisos = $model->obtenerTodosPermisos();
    }

    echo json_encode([
        'success' => true,
        'data' => $permisos
    ]);
} catch (Exception $e) {
    error_log("Error al listar permisos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener permisos'
    ]);
}
