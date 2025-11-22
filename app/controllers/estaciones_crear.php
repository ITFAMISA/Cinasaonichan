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
    $nave = $_POST['nave'] ?? 'Nave 1';

    $datos = [
        'nombre' => $_POST['nombre'] ?? '',
        'tipo' => $_POST['tipo'] ?? '',
        'nave' => $nave,
        'descripcion' => $_POST['descripcion'] ?? '',
        'ubicacion_x' => (int)($_POST['ubicacion_x'] ?? 0),
        'ubicacion_y' => (int)($_POST['ubicacion_y'] ?? 0),
        'ancho' => (int)($_POST['ancho'] ?? 50),
        'alto' => (int)($_POST['alto'] ?? 50),
        'color' => $_POST['color'] ?? '#4CAF50',
        'estatus' => $_POST['estatus'] ?? 'activa',
        'observaciones' => $_POST['observaciones'] ?? '',
        'usuario_creacion' => $_SESSION['user_id'] ?? 1
    ];

    // Validación básica
    if (empty($datos['nombre'])) {
        throw new Exception('El nombre es requerido');
    }
    if (empty($datos['tipo'])) {
        throw new Exception('El tipo es requerido');
    }
    if (empty($datos['nave'])) {
        throw new Exception('La nave es requerida');
    }

    $model = new EstacionesModel($pdo);

    // Obtener el próximo número de orden para la nave
    $proximo_orden = $model->obtenerProximoOrdenNave($nave);
    $datos['orden'] = $proximo_orden;

    $id = $model->crearEstacion($datos);

    echo json_encode([
        'success' => true,
        'message' => 'Estación creada exitosamente',
        'id' => $id
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
