<?php
/**
 * Controlador para crear un nuevo turno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $hora_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : null;
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $orden = isset($_POST['orden']) ? intval($_POST['orden']) : null;

    if (!$nombre || !$hora_inicio || !$hora_fin || !$orden) {
        throw new Exception('Campos requeridos faltantes');
    }

    // Validar formato de hora
    if (!preg_match('/^\d{2}:\d{2}$/', $hora_inicio) || !preg_match('/^\d{2}:\d{2}$/', $hora_fin)) {
        throw new Exception('Formato de hora inválido');
    }

    // Validar que no exista un turno con el mismo nombre
    $sqlVerificar = "SELECT id FROM turnos WHERE nombre = ?";
    $stmtVerificar = $pdo->prepare($sqlVerificar);
    $stmtVerificar->execute([$nombre]);

    if ($stmtVerificar->rowCount() > 0) {
        throw new Exception('Ya existe un turno con ese nombre');
    }

    // Insertar turno
    $sql = "INSERT INTO turnos (nombre, hora_inicio, hora_fin, orden, activo)
            VALUES (?, ?, ?, ?, 1)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nombre,
        $hora_inicio . ':00',
        $hora_fin . ':00',
        $orden
    ]);

    $turno_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Turno creado correctamente',
        'id' => $turno_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
