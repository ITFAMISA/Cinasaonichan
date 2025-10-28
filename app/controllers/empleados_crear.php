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

$model = new EmpleadosModel($pdo);

try {
    // Obtener datos del POST
    $datos = json_decode(file_get_contents('php://input'), true);

    if (!$datos) {
        $datos = $_POST;
    }

    // Crear el empleado
    $id = $model->crearEmpleado($datos);

    echo json_encode([
        'success' => true,
        'message' => 'Empleado creado exitosamente',
        'id' => $id
    ]);
} catch (Exception $e) {
    error_log("Error al crear empleado: " . $e->getMessage());
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
        'message' => 'Error de base de datos al crear empleado',
        'error' => $e->getMessage()
    ]);
}
