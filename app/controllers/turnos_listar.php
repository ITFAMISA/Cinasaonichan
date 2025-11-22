<?php
/**
 * Controlador para listar turnos disponibles
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Obtener todos los turnos activos ordenados por orden
    $sql = "SELECT id, nombre, hora_inicio, hora_fin, orden
            FROM turnos
            WHERE activo = 1
            ORDER BY orden ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir a formato 12 horas para visualización
    $turnosFormateados = array_map(function($turno) {
        $inicio = DateTime::createFromFormat('H:i:s', $turno['hora_inicio']);
        $fin = DateTime::createFromFormat('H:i:s', $turno['hora_fin']);

        return [
            'id' => $turno['id'],
            'nombre' => $turno['nombre'],
            'hora_inicio' => $turno['hora_inicio'],
            'hora_fin' => $turno['hora_fin'],
            'hora_inicio_formato' => $inicio ? $inicio->format('H:i') : $turno['hora_inicio'],
            'hora_fin_formato' => $fin ? $fin->format('H:i') : $turno['hora_fin'],
            'orden' => $turno['orden']
        ];
    }, $turnos);

    echo json_encode([
        'success' => true,
        'data' => $turnosFormateados
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener turnos: ' . $e->getMessage()
    ]);
}
?>
