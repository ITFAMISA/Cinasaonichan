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

    if (empty($data['id'])) {
        throw new Exception('ID de proceso requerido');
    }

    $id = (int)$data['id'];
    if ($id <= 0) {
        throw new Exception('ID de proceso inválido');
    }

    // Validaciones
    if (empty($data['nombre'])) {
        throw new Exception('El nombre del proceso es requerido');
    }

    if (strlen($data['nombre']) > 100) {
        throw new Exception('El nombre no puede exceder 100 caracteres');
    }

    $estatus = strtolower(trim($data['estatus'] ?? 'activo'));

    // Validar estatus
    if (!in_array($estatus, ['activo', 'inactivo'], true)) {
        throw new Exception('Estatus inválido. Debe ser "activo" o "inactivo"');
    }

    $datos = [
        'nombre' => trim($data['nombre']),
        'descripcion' => trim($data['descripcion'] ?? ''),
        'requiere_inspeccion_calidad' => isset($data['requiere_inspeccion_calidad']) ? (int)$data['requiere_inspeccion_calidad'] : 0,
        'estatus' => $estatus
    ];

    $model = new ProcesosModel($pdo);
    $resultado = $model->actualizarProceso($id, $datos);

    echo json_encode([
        'success' => true,
        'message' => 'Proceso actualizado correctamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
