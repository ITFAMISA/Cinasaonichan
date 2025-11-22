<?php
/**
 * Controlador para listar todos los turnos (incluyendo inactivos)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, nombre, hora_inicio, hora_fin, orden, activo
            FROM turnos
            ORDER BY orden ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $turnos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener turnos: ' . $e->getMessage()
    ]);
}
?>
