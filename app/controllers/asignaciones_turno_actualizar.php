<?php
/**
 * Controlador para actualizar asignaciones de turno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $asignacion_id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $cantidad_procesada = isset($_POST['cantidad_procesada']) ? floatval($_POST['cantidad_procesada']) : null;
    $estatus = isset($_POST['estatus']) ? $_POST['estatus'] : null;
    $notas = isset($_POST['notas']) ? $_POST['notas'] : null;

    if (!$asignacion_id) {
        throw new Exception('ID de asignación requerido');
    }

    // Construir actualización dinámicamente
    $updates = [];
    $params = [];

    if ($cantidad_procesada !== null) {
        $updates[] = 'cantidad_procesada = ?';
        $params[] = $cantidad_procesada;
    }

    if ($estatus !== null) {
        $updates[] = 'estatus = ?';
        $params[] = $estatus;

        // Actualizar fecha de inicio si cambia a en_progreso
        if ($estatus === 'en_progreso') {
            $updates[] = 'fecha_inicio = NOW()';
        }
        // Actualizar fecha de fin si cambia a completado
        if ($estatus === 'completado') {
            $updates[] = 'fecha_fin = NOW()';
        }
    }

    if ($notas !== null) {
        $updates[] = 'notas = ?';
        $params[] = $notas;
    }

    if (empty($updates)) {
        throw new Exception('No hay campos para actualizar');
    }

    $params[] = $asignacion_id;

    $sql = "UPDATE asignaciones_estacion_turno
            SET " . implode(', ', $updates) . "
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Asignación actualizada correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
