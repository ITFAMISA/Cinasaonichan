<?php
/**
 * Controlador para eliminar un turno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;

    if (!$id) {
        throw new Exception('ID de turno requerido');
    }

    // Verificar que no hay asignaciones activas
    $sqlVerificar = "SELECT COUNT(*) as count FROM asignaciones_estacion_turno
                     WHERE turno_id = ? AND estatus != 'cancelado'";
    $stmtVerificar = $pdo->prepare($sqlVerificar);
    $stmtVerificar->execute([$id]);
    $result = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        throw new Exception('No se puede eliminar un turno que tiene asignaciones activas');
    }

    // Marcar turno como inactivo en lugar de eliminarlo
    $sql = "UPDATE turnos SET activo = 0 WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Turno eliminado correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
