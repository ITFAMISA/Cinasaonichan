<?php
/**
 * Controlador para editar un turno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $hora_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : null;
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $orden = isset($_POST['orden']) ? intval($_POST['orden']) : null;
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : null;

    if (!$id) {
        throw new Exception('ID de turno requerido');
    }

    // Construir actualización dinámicamente
    $updates = [];
    $params = [];

    if ($nombre !== null) {
        $updates[] = 'nombre = ?';
        $params[] = $nombre;
    }

    if ($hora_inicio !== null) {
        if (!preg_match('/^\d{2}:\d{2}$/', $hora_inicio)) {
            throw new Exception('Formato de hora inválido');
        }
        $updates[] = 'hora_inicio = ?';
        $params[] = $hora_inicio . ':00';
    }

    if ($hora_fin !== null) {
        if (!preg_match('/^\d{2}:\d{2}$/', $hora_fin)) {
            throw new Exception('Formato de hora inválido');
        }
        $updates[] = 'hora_fin = ?';
        $params[] = $hora_fin . ':00';
    }

    if ($orden !== null) {
        $updates[] = 'orden = ?';
        $params[] = $orden;
    }

    if ($activo !== null) {
        $updates[] = 'activo = ?';
        $params[] = $activo;
    }

    if (empty($updates)) {
        throw new Exception('No hay campos para actualizar');
    }

    $params[] = $id;

    $sql = "UPDATE turnos SET " . implode(', ', $updates) . " WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Turno actualizado correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
