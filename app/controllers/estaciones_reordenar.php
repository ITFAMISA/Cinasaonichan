<?php
/**
 * Controlador: Reordenar estaciones en el dashboard
 * Recibe la información del drag & drop y actualiza el orden en la BD
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/estaciones_model.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar acceso al módulo
    if (!hasModuleAccess('estaciones')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
        exit;
    }

    // Obtener los datos POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }

    // Validar parámetros
    if (!isset($data['nave']) || !isset($data['estaciones'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos']);
        exit;
    }

    $nave = trim($data['nave']);
    $estaciones = $data['estaciones']; // Array con [id => orden, ...]

    if (empty($nave) || !is_array($estaciones)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
        exit;
    }

    // Validar que todos los valores sean números enteros
    foreach ($estaciones as $id => $orden) {
        if (!is_numeric($id) || !is_numeric($orden)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'IDs u órdenes inválidos']);
            exit;
        }
    }

    // Crear instancia del modelo
    $model = new EstacionesModel($pdo);

    // Actualizar el orden
    $resultado = $model->reordenarEstacionesEnNave($nave, $estaciones);

    if ($resultado) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Orden actualizado correctamente',
            'nave' => $nave,
            'estaciones_actualizadas' => count($estaciones)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el orden']);
    }

} catch (Exception $e) {
    error_log('Error en estaciones_reordenar.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
