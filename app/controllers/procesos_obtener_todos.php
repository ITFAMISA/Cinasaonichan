<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/procesos_model.php';

header('Content-Type: application/json');

try {
    $procesosModel = new ProcesosModel($pdo);

    // Obtener todos los procesos activos
    $procesos = $procesosModel->obtenerTodosProcesosActivos();

    echo json_encode([
        'success' => true,
        'data' => $procesos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
