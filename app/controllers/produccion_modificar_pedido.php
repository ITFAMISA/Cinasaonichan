<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $solicitud_id = $input['solicitud_id'] ?? null;

    if (!$solicitud_id) {
        throw new Exception('ID de solicitud requerido');
    }

    $pdo->beginTransaction();

    // Obtener la solicitud aprobada
    $stmt = $pdo->prepare("
        SELECT sp.*, p.id as pedido_id FROM solicitudes_modificacion_pedido sp
        JOIN pedidos p ON sp.pedido_id = p.id
        WHERE sp.id = ? AND sp.estatus = 'aprobada'
    ");
    $stmt->execute([$solicitud_id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        throw new Exception('Solicitud no encontrada o no está aprobada');
    }

    $pedido_id = $solicitud['pedido_id'];

    // Obtener datos actuales del pedido
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedido_actual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido_actual) {
        throw new Exception('Pedido no encontrado');
    }

    // Parsear los datos de modificación
    $datos_modificacion = json_decode($solicitud['datos_modificacion'], true) ?? [];

    if (empty($datos_modificacion)) {
        throw new Exception('No hay datos de modificación para aplicar');
    }

    // Preparar datos para el historial
    $datos_anteriores = [];
    $datos_nuevos = [];
    $campos_actualizar = [];
    $valores = [];

    // Procesar fecha de entrega
    if (!empty($datos_modificacion['fecha_entrega']) && !empty($datos_modificacion['nueva_fecha_entrega'])) {
        $datos_anteriores['fecha_entrega'] = $pedido_actual['fecha_entrega'];
        $datos_nuevos['fecha_entrega'] = $datos_modificacion['nueva_fecha_entrega'];
        $campos_actualizar[] = "fecha_entrega = ?";
        $valores[] = $datos_modificacion['nueva_fecha_entrega'];
    }

    // Procesar datos de contacto
    if (!empty($datos_modificacion['datos_contacto'])) {
        if (!empty($datos_modificacion['nuevo_contacto_principal'])) {
            $datos_anteriores['contacto_principal'] = $pedido_actual['contacto_principal'];
            $datos_nuevos['contacto_principal'] = $datos_modificacion['nuevo_contacto_principal'];
            $campos_actualizar[] = "contacto_principal = ?";
            $valores[] = $datos_modificacion['nuevo_contacto_principal'];
        }

        if (!empty($datos_modificacion['nuevo_telefono'])) {
            $datos_anteriores['telefono'] = $pedido_actual['telefono'];
            $datos_nuevos['telefono'] = $datos_modificacion['nuevo_telefono'];
            $campos_actualizar[] = "telefono = ?";
            $valores[] = $datos_modificacion['nuevo_telefono'];
        }

        if (!empty($datos_modificacion['nuevo_correo'])) {
            $datos_anteriores['correo'] = $pedido_actual['correo'];
            $datos_nuevos['correo'] = $datos_modificacion['nuevo_correo'];
            $campos_actualizar[] = "correo = ?";
            $valores[] = $datos_modificacion['nuevo_correo'];
        }
    }

    // Procesar observaciones
    if (!empty($datos_modificacion['observaciones']) && !empty($datos_modificacion['nuevas_observaciones'])) {
        $datos_anteriores['observaciones'] = $pedido_actual['observaciones'];
        $datos_nuevos['observaciones'] = $datos_modificacion['nuevas_observaciones'];
        $campos_actualizar[] = "observaciones = ?";
        $valores[] = $datos_modificacion['nuevas_observaciones'];
    }

    // Procesar cantidades de items
    if (!empty($datos_modificacion['cantidades_items']) && !empty($datos_modificacion['items_modificados'])) {
        $items_modificados = $datos_modificacion['items_modificados'];
        $datos_anteriores['items'] = [];
        $datos_nuevos['items'] = [];

        foreach ($items_modificados as $item_mod) {
            $item_id = $item_mod['item_id'] ?? null;
            $nueva_qty = $item_mod['nueva_qty'] ?? null;

            if (!$item_id || $nueva_qty === null) {
                continue;
            }

            // Obtener datos actuales del item desde pedidos_items
            $stmt = $pdo->prepare("SELECT cantidad FROM pedidos_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item_actual = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item_actual) {
                $datos_anteriores['items'][] = [
                    'item_id' => $item_id,
                    'qty_original' => $item_actual['cantidad']
                ];
                $datos_nuevos['items'][] = [
                    'item_id' => $item_id,
                    'qty_nueva' => $nueva_qty
                ];

                // Actualizar la cantidad en pedidos_items
                $stmt = $pdo->prepare("UPDATE pedidos_items SET cantidad = ? WHERE id = ?");
                $stmt->execute([$nueva_qty, $item_id]);

                // También actualizar subtotal si existe precio unitario
                $stmt = $pdo->prepare("
                    UPDATE pedidos_items
                    SET subtotal = cantidad * precio_unitario
                    WHERE id = ?
                ");
                $stmt->execute([$item_id]);

                // Actualizar también en la tabla produccion (qty_pendiente se calcula automáticamente)
                $stmt = $pdo->prepare("UPDATE produccion SET qty_solicitada = ? WHERE id = ?");
                $stmt->execute([$nueva_qty, $item_id]);
            }
        }
    }

    if (empty($campos_actualizar) && (empty($datos_anteriores['items']) || count($datos_anteriores['items']) === 0)) {
        throw new Exception('No hay cambios válidos para aplicar');
    }

    // Actualizar el pedido (si hay campos para actualizar)
    if (!empty($campos_actualizar)) {
        $valores[] = $pedido_id;
        $sql = "UPDATE pedidos SET " . implode(', ', $campos_actualizar) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($valores);
    }

    // Registrar en el historial (si la tabla existe)
    $stmt = $pdo->prepare("
        SELECT 1 FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'historial_modificaciones_pedido'
    ");
    $stmt->execute();

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO historial_modificaciones_pedido
            (pedido_id, solicitud_id, usuario_modificador, tipo_modificacion, datos_anteriores, datos_nuevos, descripcion)
            VALUES (?, ?, ?, 'datos_generales', ?, ?, ?)
        ");

        $descripcion = "Modificación aprobada por gerencia aplicada al pedido";

        $stmt->execute([
            $pedido_id,
            $solicitud_id,
            $_SESSION['username'] ?? $_SESSION['user_id'],
            json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE),
            json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE),
            $descripcion
        ]);
    }

    // Actualizar el estatus de la solicitud a 'aplicada'
    $stmt = $pdo->prepare("
        UPDATE solicitudes_modificacion_pedido
        SET estatus = 'aplicada'
        WHERE id = ?
    ");
    $stmt->execute([$solicitud_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Modificación aplicada exitosamente al pedido'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
