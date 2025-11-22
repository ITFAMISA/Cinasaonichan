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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        throw new Exception('Datos inválidos');
    }

    if (empty($data['producto_id']) || empty($data['proceso_id'])) {
        throw new Exception('Producto y Proceso son requeridos');
    }

    if (empty($data['orden_secuencia']) || $data['orden_secuencia'] < 1) {
        throw new Exception('Orden de secuencia debe ser mayor a 0');
    }

    $model = new ProcesosModel($pdo);

    // Log para debugging
    error_log("Asignando proceso - Producto: {$data['producto_id']}, Proceso: {$data['proceso_id']}, Orden: {$data['orden_secuencia']}");

    $resultado = $model->asignarProcesoAProducto(
        $data['producto_id'],
        $data['proceso_id'],
        $data['orden_secuencia'],
        $data['notas'] ?? ''
    );

    error_log("Resultado de asignación: " . ($resultado ? 'true' : 'false'));

    if (!$resultado) {
        throw new Exception('Error al asignar el proceso al producto. Verifique que el producto y proceso existan.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Proceso asignado correctamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
