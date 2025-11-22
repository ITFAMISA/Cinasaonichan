<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener procesos seleccionados desde query parameters
    $procesosSeleccionados = isset($_GET['procesos']) ? explode(',', $_GET['procesos']) : [];
    $procesosSeleccionados = array_filter(array_map('intval', $procesosSeleccionados));

    error_log('Procesos seleccionados: ' . implode(',', $procesosSeleccionados));

    if (empty($procesosSeleccionados)) {
        // Si no hay filtro, retornar todas las estaciones
        $sql = "SELECT id, nombre, tipo, nave, estatus, color, orden
                FROM estaciones
                WHERE estatus = 'activa'
                ORDER BY nave ASC, orden ASC, nombre ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Obtener estaciones que tengan al menos uno de los procesos seleccionados
        $placeholders = implode(',', array_fill(0, count($procesosSeleccionados), '?'));

        $sql = "SELECT DISTINCT e.id, e.nombre, e.tipo, e.nave, e.estatus, e.color, e.orden
                FROM estaciones e
                INNER JOIN estacion_procesos ep ON e.id = ep.estacion_id
                WHERE e.estatus = 'activa'
                AND ep.proceso_id IN ($placeholders)
                ORDER BY e.nave ASC, e.orden ASC, e.nombre ASC";

        error_log('SQL Query: ' . $sql);
        error_log('Proceso IDs: ' . implode(',', $procesosSeleccionados));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($procesosSeleccionados);
    }

    $estaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log('Estaciones encontradas: ' . count($estaciones));

    echo json_encode([
        'success' => true,
        'data' => $estaciones,
        'debug' => [
            'procesos_count' => count($procesosSeleccionados),
            'estaciones_count' => count($estaciones)
        ]
    ]);

} catch (Exception $e) {
    error_log('Error en tracking_estaciones_filtrar: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
