<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/procesos_model.php';

header('Content-Type: application/json');

// Verificar permiso de acceso
if (!hasModuleAccess('procesos')) {
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
        'estatus' => $_GET['estatus'] ?? ''
    ];

    $orden = $_GET['orden'] ?? 'nombre';
    $direccion = $_GET['direccion'] ?? 'ASC';

    $model = new ProcesosModel($pdo);

    $procesos = $model->listarProcesos($filtros, $orden, $direccion, $limite, $offset);
    $total = $model->contarProcesos($filtros);

    $paginas = ceil($total / $limite);

    echo json_encode([
        'success' => true,
        'data' => $procesos,
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
