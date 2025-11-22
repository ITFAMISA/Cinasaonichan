<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, nombre, descripcion, color, orden, estatus
            FROM tracking_areas_trabajo
            WHERE estatus = 'activa'
            ORDER BY orden ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $areas
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener áreas de trabajo: ' . $e->getMessage()
    ]);
}