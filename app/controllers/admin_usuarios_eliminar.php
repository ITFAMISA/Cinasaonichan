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

    // No permitir eliminar al propio usuario
    if ($id === $_SESSION['user_id'] ?? null) {
        throw new Exception("No puedes eliminar tu propio usuario");
    }

    $model->eliminarUsuario($id);

    echo json_encode([
        'success' => true,
        'message' => 'Usuario eliminado exitosamente'
    ]);
} catch (Exception $e) {
    error_log("Error al eliminar usuario: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
