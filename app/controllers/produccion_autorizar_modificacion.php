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

// Verificar que el usuario tiene rol de gerente
$stmt = $pdo->prepare("SELECT GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles FROM usuarios u
                       LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                       LEFT JOIN roles r ON ur.rol_id = r.id
                       WHERE u.id = ?
                       GROUP BY u.id");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

$rolesUsuario = $usuario ? strtolower($usuario['roles'] ?? '') : '';
$tieneRolGerencia = !empty($rolesUsuario) && (strpos($rolesUsuario, 'gerente') !== false || strpos($rolesUsuario, 'administrador') !== false || strpos($rolesUsuario, 'admin') !== false);

if (!$tieneRolGerencia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos de gerencia para autorizar']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $solicitud_id = $input['solicitud_id'] ?? null;
    $accion = $input['accion'] ?? ''; // 'aprobar' o 'rechazar'
    $comentarios = $input['comentarios'] ?? '';

    if (!$solicitud_id || !in_array($accion, ['aprobar', 'rechazar'])) {
        throw new Exception('Datos inválidos');
    }

    $pdo->beginTransaction();

    // Obtener la solicitud
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_modificacion_pedido WHERE id = ? AND estatus = 'pendiente'");
    $stmt->execute([$solicitud_id]);
    $solicitud = $stmt->fetch();

    if (!$solicitud) {
        throw new Exception('Solicitud no encontrada o ya procesada');
    }

    $nuevo_estatus = $accion === 'aprobar' ? 'aprobada' : 'rechazada';

    // Actualizar la solicitud
    $stmt = $pdo->prepare("
        UPDATE solicitudes_modificacion_pedido
        SET estatus = ?,
            usuario_autorizador = ?,
            fecha_respuesta = NOW(),
            comentarios_autorizacion = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $nuevo_estatus,
        $_SESSION['username'] ?? $_SESSION['user_id'],
        $comentarios,
        $solicitud_id
    ]);

    // Si se aprueba, aplicar los cambios inmediatamente
    if ($accion === 'aprobar') {
        // Parsear datos de modificación
        $datos_modificacion = json_decode($solicitud['datos_modificacion'], true) ?? [];

        // Procesar cantidades de items
        if (!empty($datos_modificacion['cantidades_items']) && !empty($datos_modificacion['items_modificados'])) {
            $items_modificados = $datos_modificacion['items_modificados'];

            foreach ($items_modificados as $item_mod) {
                $item_id = $item_mod['item_id'] ?? null;
                $nueva_qty = $item_mod['nueva_qty'] ?? null;

                if (!$item_id || $nueva_qty === null) {
                    continue;
                }

                // Actualizar en pedidos_items
                $stmt = $pdo->prepare("UPDATE pedidos_items SET cantidad = ? WHERE id = ?");
                $stmt->execute([$nueva_qty, $item_id]);

                // Actualizar subtotal
                $stmt = $pdo->prepare("
                    UPDATE pedidos_items
                    SET subtotal = cantidad * precio_unitario
                    WHERE id = ?
                ");
                $stmt->execute([$item_id]);

                // Actualizar en produccion
                $stmt = $pdo->prepare("UPDATE produccion SET qty_solicitada = ? WHERE id = ?");
                $stmt->execute([$nueva_qty, $item_id]);
            }
        }

        // Procesar fecha de entrega
        if (!empty($datos_modificacion['fecha_entrega']) && !empty($datos_modificacion['nueva_fecha_entrega'])) {
            $stmt = $pdo->prepare("UPDATE pedidos SET fecha_entrega = ? WHERE id = ?");
            $stmt->execute([$datos_modificacion['nueva_fecha_entrega'], $solicitud['pedido_id']]);
        }

        // Procesar datos de contacto
        if (!empty($datos_modificacion['datos_contacto'])) {
            $campos = [];
            $valores = [];

            if (!empty($datos_modificacion['nuevo_contacto_principal'])) {
                $campos[] = "contacto = ?";
                $valores[] = $datos_modificacion['nuevo_contacto_principal'];
            }
            if (!empty($datos_modificacion['nuevo_telefono'])) {
                $campos[] = "telefono = ?";
                $valores[] = $datos_modificacion['nuevo_telefono'];
            }
            if (!empty($datos_modificacion['nuevo_correo'])) {
                $campos[] = "correo = ?";
                $valores[] = $datos_modificacion['nuevo_correo'];
            }

            if (!empty($campos)) {
                $valores[] = $solicitud['pedido_id'];
                $sql = "UPDATE pedidos SET " . implode(', ', $campos) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($valores);
            }
        }

        // Procesar observaciones
        if (!empty($datos_modificacion['observaciones']) && !empty($datos_modificacion['nuevas_observaciones'])) {
            $stmt = $pdo->prepare("UPDATE pedidos SET observaciones = ? WHERE id = ?");
            $stmt->execute([$datos_modificacion['nuevas_observaciones'], $solicitud['pedido_id']]);
        }

        // Actualizar el pedido
        $stmt = $pdo->prepare("
            UPDATE pedidos
            SET modificado_en_produccion = 1,
                fecha_modificacion_produccion = NOW(),
                usuario_modificacion_produccion = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_SESSION['username'] ?? $_SESSION['user_id'],
            $solicitud['pedido_id']
        ]);

        // Cambiar estatus a 'aplicada' en lugar de 'aprobada'
        $stmt = $pdo->prepare("
            UPDATE solicitudes_modificacion_pedido
            SET estatus = 'aplicada'
            WHERE id = ?
        ");
        $stmt->execute([$solicitud_id]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $accion === 'aprobar'
            ? 'Modificación aprobada y aplicada correctamente al pedido.'
            : 'Modificación rechazada',
        'estatus' => $accion === 'aprobar' ? 'aplicada' : $nuevo_estatus
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
