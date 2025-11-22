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
    $datos = json_decode(file_get_contents('php://input'), true);

    if (!$datos) {
        $datos = $_POST;
    }

    $model->actualizarRol($id, $datos);

    // Actualizar permisos si se proporcionan
    if (isset($datos['permisos']) && is_array($datos['permisos'])) {
        $model->asignarMultiplesPermisos($id, $datos['permisos']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Rol actualizado exitosamente'
    ]);
} catch (Exception $e) {
    error_log("Error al actualizar rol: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
