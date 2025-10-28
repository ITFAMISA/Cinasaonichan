<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/empleados_model.php';

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
        'message' => 'ID de empleado requerido'
    ]);
    exit;
}

$model = new EmpleadosModel($pdo);

try {
    $id = (int)$_GET['id'];

    // Obtener datos del POST
    $datos = json_decode(file_get_contents('php://input'), true);

    if (!$datos) {
        $datos = $_POST;
    }

    // Actualizar el empleado
    $model->actualizarEmpleado($id, $datos);

    echo json_encode([
        'success' => true,
        'message' => 'Empleado actualizado exitosamente'
    ]);
} catch (Exception $e) {
    error_log("Error al actualizar empleado: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Error SQL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos al actualizar empleado',
        'error' => $e->getMessage()
    ]);
}
