<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuarios_model.php';

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
        'message' => 'ID de usuario requerido'
    ]);
    exit;
}

$model = new UsuariosModel($pdo);

try {
    $usuario = $model->obtenerUsuarioById((int)$_GET['id']);

    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
        exit;
    }

    // No devolver el hash de contraseña
    unset($usuario['contrasena_hash']);

    echo json_encode([
        'success' => true,
        'data' => $usuario
    ]);
} catch (Exception $e) {
    error_log("Error al obtener usuario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener usuario'
    ]);
}
