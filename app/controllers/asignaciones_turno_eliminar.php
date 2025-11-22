<?php
/**
 * Controlador para eliminar asignaciones de turno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $asignacion_id = isset($_POST['id']) ? intval($_POST['id']) : null;

    if (!$asignacion_id) {
        throw new Exception('ID de asignación requerido');
    }

    // Cambiar estatus a cancelado en lugar de eliminar
    $sql = "UPDATE asignaciones_estacion_turno
            SET estatus = 'cancelado'
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$asignacion_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Asignación eliminada correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
