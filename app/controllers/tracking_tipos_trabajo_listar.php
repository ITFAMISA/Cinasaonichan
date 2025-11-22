<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, nombre, descripcion, color, icono, estatus, orden
            FROM tracking_tipos_trabajo
            WHERE estatus = 'activo'
            ORDER BY orden ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $tipos
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener tipos de trabajo: ' . $e->getMessage()
    ]);
}