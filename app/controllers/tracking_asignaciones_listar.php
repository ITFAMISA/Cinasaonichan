<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener parámetros
    $estatus = isset($_GET['estatus']) ? trim($_GET['estatus']) : '';
    $empleadoId = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : 0;
    $pedidoId = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : 0;
    $tipoTrabajoId = isset($_GET['tipo_trabajo_id']) ? (int)$_GET['tipo_trabajo_id'] : 0;
    $areaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : 0;

    // Consulta base
    $sql = "SELECT a.*, p.numero_pedido
            FROM tracking_asignaciones a
            LEFT JOIN pedidos p ON a.pedido_id = p.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtros
    if (!empty($estatus)) {
        $sql .= " AND a.estatus = ?";
        $params[] = $estatus;
    }

    if ($empleadoId > 0) {
        $sql .= " AND a.empleado_id = ?";
        $params[] = $empleadoId;
    }

    if ($pedidoId > 0) {
        $sql .= " AND a.pedido_id = ?";
        $params[] = $pedidoId;
    }

    if ($tipoTrabajoId > 0) {
        $sql .= " AND a.tipo_trabajo_id = ?";
        $params[] = $tipoTrabajoId;
    }

    if ($areaId > 0) {
        $sql .= " AND a.area_id = ?";
        $params[] = $areaId;
    }

    // Ordenar por fecha de asignación (más recientes primero)
    $sql .= " ORDER BY a.fecha_asignacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $asignaciones
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener asignaciones: ' . $e->getMessage()
    ]);
}