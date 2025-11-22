<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Se requiere POST.'
    ]);
    exit;
}

try {
    // Obtener datos del formulario
    $empleadoId = isset($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : 0;
    $pedidoId = isset($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : 0;
    $tipoTrabajoId = isset($_POST['tipo_trabajo_id']) ? (int)$_POST['tipo_trabajo_id'] : 0;
    $cantidadTotal = isset($_POST['cantidad_total']) ? floatval($_POST['cantidad_total']) : 0;
    $areaId = isset($_POST['area_id']) ? (int)$_POST['area_id'] : null;
    $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : null;

    // Validar datos obligatorios
    if ($empleadoId <= 0 || $pedidoId <= 0 || $tipoTrabajoId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Empleado, pedido y tipo de trabajo son obligatorios'
        ]);
        exit;
    }

    // Verificar si el empleado existe
    $sqlEmpleado = "SELECT id FROM empleados WHERE id = ?";
    $stmtEmpleado = $pdo->prepare($sqlEmpleado);
    $stmtEmpleado->execute([$empleadoId]);
    if ($stmtEmpleado->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'El empleado seleccionado no existe'
        ]);
        exit;
    }

    // Verificar si el pedido existe
    $sqlPedido = "SELECT id, numero_pedido FROM pedidos WHERE id = ?";
    $stmtPedido = $pdo->prepare($sqlPedido);
    $stmtPedido->execute([$pedidoId]);
    $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'message' => 'El pedido seleccionado no existe'
        ]);
        exit;
    }

    // Verificar si el tipo de trabajo existe
    $sqlTipo = "SELECT id FROM tracking_tipos_trabajo WHERE id = ?";
    $stmtTipo = $pdo->prepare($sqlTipo);
    $stmtTipo->execute([$tipoTrabajoId]);
    if ($stmtTipo->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'El tipo de trabajo seleccionado no existe'
        ]);
        exit;
    }

    // Verificar si el área existe (si se proporciona)
    if ($areaId) {
        $sqlArea = "SELECT id FROM tracking_areas_trabajo WHERE id = ?";
        $stmtArea = $pdo->prepare($sqlArea);
        $stmtArea->execute([$areaId]);
        if ($stmtArea->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'El área seleccionada no existe'
            ]);
            exit;
        }
    }

    // Verificar si el item existe (si se proporciona)
    if ($itemId) {
        $sqlItem = "SELECT id FROM pedidos_items WHERE id = ? AND pedido_id = ?";
        $stmtItem = $pdo->prepare($sqlItem);
        $stmtItem->execute([$itemId, $pedidoId]);
        if ($stmtItem->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'El item seleccionado no pertenece al pedido'
            ]);
            exit;
        }
    }

    // Verificar si ya existe una asignación para esta combinación
    $sqlBuscar = "SELECT id FROM tracking_asignaciones
                  WHERE empleado_id = ? AND pedido_id = ? AND tipo_trabajo_id = ?";

    $params = [$empleadoId, $pedidoId, $tipoTrabajoId];

    if ($itemId) {
        $sqlBuscar .= " AND item_id = ?";
        $params[] = $itemId;
    } else {
        $sqlBuscar .= " AND item_id IS NULL";
    }

    $stmtBuscar = $pdo->prepare($sqlBuscar);
    $stmtBuscar->execute($params);

    if ($stmtBuscar->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe una asignación con estos datos'
        ]);
        exit;
    }

    // Insertar la asignación
    $sql = "INSERT INTO tracking_asignaciones
            (empleado_id, pedido_id, item_id, tipo_trabajo_id, area_id,
             fecha_asignacion, cantidad_total, cantidad_procesada,
             estatus, usuario_creacion)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, 0, 'asignado', ?)";

    $usuarioId = $_SESSION['usuario_id'] ?? 1; // ID de usuario actual o por defecto

    $params = [
        $empleadoId,
        $pedidoId,
        $itemId ?: null,
        $tipoTrabajoId,
        $areaId ?: null,
        $cantidadTotal > 0 ? $cantidadTotal : 1, // Si no se especifica, asumimos 1
        $usuarioId
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $asignacionId = $pdo->lastInsertId();

    // Si todo salió bien, retornar éxito
    echo json_encode([
        'success' => true,
        'message' => 'Asignación creada correctamente',
        'data' => [
            'id' => $asignacionId,
            'empleado_id' => $empleadoId,
            'pedido_id' => $pedidoId,
            'pedido_numero' => $pedido['numero_pedido'],
            'tipo_trabajo_id' => $tipoTrabajoId,
            'area_id' => $areaId,
            'cantidad_total' => $cantidadTotal > 0 ? $cantidadTotal : 1
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear asignación: ' . $e->getMessage()
    ]);
}