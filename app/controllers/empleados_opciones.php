<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/empleados_model.php';

header('Content-Type: application/json');

$model = new EmpleadosModel($pdo);

try {
    // Si hay parámetro buscar, buscar empleados
    if (isset($_GET['buscar'])) {
        $buscar = trim($_GET['buscar']);
        $empleados = $model->buscarEmpleados($buscar);
        
        echo json_encode([
            'success' => true,
            'data' => $empleados
        ]);
        exit;
    }
    
    // Si no hay búsqueda, devolver opciones (departamentos, puestos, etc.)
    $opcion = isset($_GET['opcion']) ? trim($_GET['opcion']) : 'todas';

    $respuesta = [
        'success' => true,
        'data' => []
    ];

    if ($opcion === 'departamentos' || $opcion === 'todas') {
        $respuesta['data']['departamentos'] = $model->obtenerDepartamentos();
    }

    if ($opcion === 'puestos' || $opcion === 'todas') {
        $respuesta['data']['puestos'] = $model->obtenerPuestos();
    }

    if ($opcion === 'supervisores' || $opcion === 'todas') {
        $respuesta['data']['supervisores'] = $model->obtenerSupervisores();
    }

    echo json_encode($respuesta);
} catch (PDOException $e) {
    error_log("Error SQL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener opciones: ' . $e->getMessage()
    ]);
}
