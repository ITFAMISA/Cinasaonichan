<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/estaciones_model.php';

header('Content-Type: application/json');

if (!hasModuleAccess('estaciones')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limite = (int)($_GET['limite'] ?? 20);
    $offset = ($page - 1) * $limite;

    $filtros = [
        'buscar' => $_GET['buscar'] ?? '',
        'tipo' => $_GET['tipo'] ?? '',
        'estatus' => $_GET['estatus'] ?? ''
    ];

    $orden = $_GET['orden'] ?? 'nombre';
    $direccion = $_GET['direccion'] ?? 'ASC';

    $model = new EstacionesModel($pdo);

    $estaciones = $model->listarEstaciones($filtros, $orden, $direccion, $limite, $offset);
    $total = $model->contarEstaciones($filtros);

    $paginas = ceil($total / $limite);

    echo json_encode([
        'success' => true,
        'data' => $estaciones,
        'pagination' => [
            'page' => $page,
            'limite' => $limite,
            'total' => $total,
            'paginas' => $paginas
        ]
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
