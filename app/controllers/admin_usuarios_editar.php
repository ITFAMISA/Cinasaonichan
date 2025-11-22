<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuarios_model.php';

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
        'message' => 'ID de usuario requerido'
    ]);
    exit;
}

$model = new UsuariosModel($pdo);

try {
    $id = (int)$_GET['id'];
    $datos = json_decode(file_get_contents('php://input'), true);

    if (!$datos) {
        $datos = $_POST;
    }

    $model->actualizarUsuario($id, $datos);

    // Actualizar roles si se proporcionan
    if (isset($datos['roles']) && is_array($datos['roles'])) {
        // Primero eliminar todos los roles actuales
        $roles_actuales = $model->obtenerRolesUsuario($id);
        foreach ($roles_actuales as $rol) {
            $model->removerRol($id, $rol['id']);
        }

        // Luego asignar los nuevos
        foreach ($datos['roles'] as $rol_id) {
            $model->asignarRol($id, $rol_id);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Usuario actualizado exitosamente'
    ]);
} catch (Exception $e) {
    error_log("Error al actualizar usuario: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
