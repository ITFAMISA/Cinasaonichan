<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuarios_model.php';

// Verificar permiso
try {
    requirePermission('usuarios.listar');
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$model = new UsuariosModel($pdo);

try {
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    $rol_id = isset($_GET['rol_id']) && !empty($_GET['rol_id']) ? (int)$_GET['rol_id'] : null;

    $usuarios = $model->listarUsuarios($pagina, $buscar, $rol_id);
    $total = $model->contarUsuarios($buscar, $rol_id);

    $total_paginas = ceil($total / 20);

    echo json_encode([
        'success' => true,
        'data' => $usuarios,
        'pagination' => [
            'pagina_actual' => $pagina,
            'total_paginas' => $total_paginas,
            'total_registros' => $total,
            'registros_por_pagina' => 20
        ]
    ]);
} catch (Exception $e) {
    error_log("Error al listar usuarios: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener usuarios: ' . $e->getMessage()
    ]);
}
