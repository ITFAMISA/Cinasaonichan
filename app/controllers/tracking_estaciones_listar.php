<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, nombre, tipo, nave, estatus, color, orden
            FROM estaciones
            WHERE estatus = 'activa'
            ORDER BY nave ASC, orden ASC, nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $estaciones
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estaciones: ' . $e->getMessage()
    ]);
}