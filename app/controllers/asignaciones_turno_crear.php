<?php
/**
 * Controlador para crear asignaciones de empleados a estaciones por turno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Validar datos requeridos
    $estacion_id = isset($_POST['estacion_id']) ? intval($_POST['estacion_id']) : null;
    $turno_id = isset($_POST['turno_id']) ? intval($_POST['turno_id']) : null;
    $empleado_id = isset($_POST['empleado_id']) ? intval($_POST['empleado_id']) : null;
    $pedido_id = isset($_POST['pedido_id']) ? intval($_POST['pedido_id']) : null;
    $tipo_trabajo_id = isset($_POST['tipo_trabajo_id']) ? intval($_POST['tipo_trabajo_id']) : null;
    $cantidad_total = isset($_POST['cantidad_total']) ? floatval($_POST['cantidad_total']) : null;

    if (!$estacion_id || !$turno_id || !$empleado_id || !$pedido_id || !$cantidad_total || !$tipo_trabajo_id) {
        throw new Exception('Campos requeridos faltantes');
    }

    // Validar que el empleado no tenga el mismo pedido asignado en el mismo turno
    $sqlVerificar = "SELECT id FROM asignaciones_estacion_turno
                     WHERE empleado_id = ? AND turno_id = ? AND pedido_id = ? AND estatus != 'cancelado'";
    $stmtVerificar = $pdo->prepare($sqlVerificar);
    $stmtVerificar->execute([$empleado_id, $turno_id, $pedido_id]);

    if ($stmtVerificar->rowCount() > 0) {
        throw new Exception('Este empleado ya tiene asignado este pedido en este turno');
    }

    // Insertar la asignación
    $sql = "INSERT INTO asignaciones_estacion_turno
            (estacion_id, turno_id, empleado_id, pedido_id, tipo_trabajo_id, cantidad_total, estatus)
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $estacion_id,
        $turno_id,
        $empleado_id,
        $pedido_id,
        $tipo_trabajo_id,
        $cantidad_total
    ]);

    $asignacion_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Asignación creada correctamente',
        'id' => $asignacion_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
