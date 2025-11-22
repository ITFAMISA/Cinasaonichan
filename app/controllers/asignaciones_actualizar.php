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
    $id = (int)($_POST['id'] ?? 0);
    if (empty($id)) {
        throw new Exception('ID de asignación requerido');
    }

    $model = new EstacionesModel($pdo);

    // Verificar que la asignación existe
    $asignacion = $model->obtenerAsignacionPorId($id);
    if (!$asignacion) {
        throw new Exception('Asignación no encontrada');
    }

    // Si solo se actualiza el estado
    if (!empty($_POST['solo_estatus'])) {
        $estatus = $_POST['estatus'] ?? '';
        $cantidad_procesada = isset($_POST['cantidad_procesada']) ? (float)$_POST['cantidad_procesada'] : null;

        if (empty($estatus)) {
            throw new Exception('El estado es requerido');
        }

        $model->actualizarEstadoAsignacion($id, $estatus, $cantidad_procesada);

        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado exitosamente'
        ]);
        exit;
    }

    // Actualización completa
    $datos = [
        'cantidad_procesada' => (float)($_POST['cantidad_procesada'] ?? 0),
        'fecha_fin_estimada' => $_POST['fecha_fin_estimada'] ?? null,
        'estatus' => $_POST['estatus'] ?? 'pendiente',
        'observaciones' => $_POST['observaciones'] ?? '',
        'empleado_id' => (int)($_POST['empleado_id'] ?? 0)
    ];

    $model->actualizarAsignacion($id, $datos);

    echo json_encode([
        'success' => true,
        'message' => 'Asignación actualizada exitosamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
