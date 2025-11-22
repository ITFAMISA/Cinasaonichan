<?php
/**
 * API Endpoint: Finalizar registro de mantenimiento de una estación
 * PUT /app/controllers/estacion_mantenimiento_finalizar.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../config/database.php';

    // Validar método PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar campos requeridos
    if (empty($input['id']) || empty($input['fecha_fin']) || empty($input['proceso_id'])) {
        throw new Exception('Campos requeridos: id, fecha_fin, proceso_id');
    }

    $mant_id = intval($input['id']);
    $fecha_fin = $input['fecha_fin'];
    $proceso_id = intval($input['proceso_id']);

    // Obtener el registro de mantenimiento
    $sqlGet = "
        SELECT id, estacion_id, fecha_inicio, horas_mantenimiento
        FROM estacion_mantenimiento
        WHERE id = :id AND estatus = 'activo'
    ";

    $stmtGet = $pdo->prepare($sqlGet);
    $stmtGet->execute([':id' => $mant_id]);
    $mantenimiento = $stmtGet->fetch(PDO::FETCH_ASSOC);

    if (!$mantenimiento) {
        throw new Exception('Registro de mantenimiento no encontrado o ya completado');
    }

    // Actualizar registro de mantenimiento
    $sqlUpdate = "
        UPDATE estacion_mantenimiento
        SET fecha_fin = :fecha_fin, estatus = 'completado'
        WHERE id = :id
    ";

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':fecha_fin' => $fecha_fin,
        ':id' => $mant_id
    ]);

    // Calcular horas de mantenimiento
    $sqlCalc = "
        SELECT horas_mantenimiento FROM estacion_mantenimiento WHERE id = :id
    ";
    $stmtCalc = $pdo->prepare($sqlCalc);
    $stmtCalc->execute([':id' => $mant_id]);
    $result = $stmtCalc->fetch(PDO::FETCH_ASSOC);
    $horas_mantenimiento = floatval($result['horas_mantenimiento']);

    // Actualizar o crear registro en estacion_proceso_mantenimiento
    $sqlUpsert = "
        INSERT INTO estacion_proceso_mantenimiento (estacion_id, proceso_id, total_horas_mantenimiento)
        VALUES (:estacion_id, :proceso_id, :horas)
        ON DUPLICATE KEY UPDATE
            total_horas_mantenimiento = total_horas_mantenimiento + VALUES(total_horas_mantenimiento)
    ";

    $stmtUpsert = $pdo->prepare($sqlUpsert);
    $stmtUpsert->execute([
        ':estacion_id' => $mantenimiento['estacion_id'],
        ':proceso_id' => $proceso_id,
        ':horas' => $horas_mantenimiento
    ]);

    // Obtener el registro actualizado
    $sqlFinal = "
        SELECT id, estacion_id, motivo, descripcion, fecha_inicio, fecha_fin, horas_mantenimiento, estatus
        FROM estacion_mantenimiento
        WHERE id = :id
    ";

    $stmtFinal = $pdo->prepare($sqlFinal);
    $stmtFinal->execute([':id' => $mant_id]);
    $mantenimiento_final = $stmtFinal->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Registro de mantenimiento finalizado exitosamente',
        'mantenimiento' => $mantenimiento_final
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
