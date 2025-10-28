<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/empleados_model.php';

header('Content-Type: application/json');

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
    $empleado = $model->obtenerEmpleadoPorId($id);

    if (!$empleado) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Empleado no encontrado'
        ]);
        exit;
    }

    // Obtener nombre del supervisor si existe
    if ($empleado['supervisor_directo_id']) {
        $supervisor = $model->obtenerEmpleadoPorId($empleado['supervisor_directo_id']);
        $empleado['nombre_supervisor'] = $supervisor ? $supervisor['nombre'] . ' ' . $supervisor['apellido'] : 'N/A';
    }

    echo json_encode([
        'success' => true,
        'data' => $empleado
    ]);
} catch (PDOException $e) {
    error_log("Error SQL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos al obtener empleado',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener empleado: ' . $e->getMessage()
    ]);
}
