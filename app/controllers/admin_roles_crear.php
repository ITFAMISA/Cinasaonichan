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

$model = new RolesModel($pdo);

try {
    $datos = json_decode(file_get_contents('php://input'), true);

    if (!$datos) {
        $datos = $_POST;
    }

    $rol_id = $model->crearRol($datos);

    // Asignar permisos si se proporcionan
    if (isset($datos['permisos']) && is_array($datos['permisos'])) {
        $model->asignarMultiplesPermisos($rol_id, $datos['permisos']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Rol creado exitosamente',
        'id' => $rol_id
    ]);
} catch (Exception $e) {
    error_log("Error al crear rol: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
