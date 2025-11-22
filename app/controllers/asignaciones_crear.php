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
    $datos = [
        'estacion_id' => (int)($_POST['estacion_id'] ?? 0),
        'pedido_id' => (int)($_POST['pedido_id'] ?? 0),
        'producto_id' => (int)($_POST['producto_id'] ?? 0),
        'proceso_id' => (int)($_POST['proceso_id'] ?? 0),
        'numero_pedido' => $_POST['numero_pedido'] ?? '',
        'cantidad_total' => (float)($_POST['cantidad_total'] ?? 0),
        'cantidad_procesada' => (float)($_POST['cantidad_procesada'] ?? 0),
        'fecha_fin_estimada' => $_POST['fecha_fin_estimada'] ?? null,
        'estatus' => $_POST['estatus'] ?? 'pendiente',
        'observaciones' => $_POST['observaciones'] ?? '',
        'empleado_id' => (int)($_POST['empleado_id'] ?? 0),
        'usuario_creacion' => $_SESSION['user_id'] ?? 1
    ];

    // Validación básica
    if (empty($datos['estacion_id'])) {
        throw new Exception('ID de estación requerido');
    }
    if (empty($datos['pedido_id'])) {
        throw new Exception('ID de pedido requerido');
    }
    if (empty($datos['cantidad_total']) || $datos['cantidad_total'] <= 0) {
        throw new Exception('Cantidad debe ser mayor a 0');
    }

    $model = new EstacionesModel($pdo);

    // Verificar que la estación existe
    $estacion = $model->obtenerEstacionPorId($datos['estacion_id']);
    if (!$estacion) {
        throw new Exception('Estación no encontrada');
    }

    $id = $model->crearAsignacion($datos);

    echo json_encode([
        'success' => true,
        'message' => 'Asignación creada exitosamente',
        'id' => $id
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
