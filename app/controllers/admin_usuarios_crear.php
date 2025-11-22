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

$model = new UsuariosModel($pdo);

try {
    $datos = json_decode(file_get_contents('php://input'), true);

    if (!$datos) {
        $datos = $_POST;
    }

    $usuario_id = $model->crearUsuario($datos);

    // Asignar roles si se proporcionan
    if (isset($datos['roles']) && is_array($datos['roles'])) {
        foreach ($datos['roles'] as $rol_id) {
            $model->asignarRol($usuario_id, $rol_id);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado exitosamente',
        'id' => $usuario_id
    ]);
} catch (Exception $e) {
    error_log("Error al crear usuario: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
