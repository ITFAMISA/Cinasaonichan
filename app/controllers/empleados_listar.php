<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/empleados_model.php';

header('Content-Type: application/json');

$model = new EmpleadosModel($pdo);

$filtros = [
    'buscar' => isset($_GET['buscar']) ? trim($_GET['buscar']) : '',
    'estatus_empleado' => isset($_GET['estatus_empleado']) ? trim($_GET['estatus_empleado']) : '',
    'departamento' => isset($_GET['departamento']) ? trim($_GET['departamento']) : '',
    'puesto' => isset($_GET['puesto']) ? trim($_GET['puesto']) : ''
];

$orden = isset($_GET['orden']) ? trim($_GET['orden']) : 'apellido';
$direccion = isset($_GET['direccion']) ? trim($_GET['direccion']) : 'ASC';
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

try {
    $empleados = $model->listarEmpleados($filtros, $orden, $direccion, $limite, $offset);
    $total = $model->contarEmpleados($filtros);
    $totalPaginas = ceil($total / $limite);

    echo json_encode([
        'success' => true,
        'data' => $empleados,
        'pagination' => [
            'total' => $total,
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
            'por_pagina' => $limite
        ]
    ]);
} catch (PDOException $e) {
    error_log("Error SQL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos al obtener empleados',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener empleados: ' . $e->getMessage()
    ]);
}
