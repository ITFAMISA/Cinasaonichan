<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $numero_pedido = $_GET['pedido'] ?? '';

    if (empty($numero_pedido)) {
        throw new Exception('Número de pedido no especificado');
    }

    // Obtener información del pedido
    $sqlPedido = "
        SELECT 
            p.id as pedido_id,
            p.numero_pedido,
            c.razon_social as cliente_nombre,
            c.contacto_principal,
            c.telefono,
            c.correo,
            p.fecha_creacion,
            p.fecha_entrega,
            p.estatus as pedido_estatus,
            p.observaciones,
            COUNT(DISTINCT pp.id) as total_piezas,
            SUM(CASE WHEN pp.estatus = 'por_inspeccionar' THEN 1 ELSE 0 END) as piezas_por_inspeccionar,
            SUM(CASE WHEN pp.estatus = 'liberada' THEN 1 ELSE 0 END) as piezas_liberadas,
            SUM(CASE WHEN pp.estatus = 'rechazada' THEN 1 ELSE 0 END) as piezas_rechazadas,
            SUM(CASE WHEN pp.estatus = 'pendiente_reinspeccion' THEN 1 ELSE 0 END) as piezas_reinspeccion,
            ROUND((SUM(CASE WHEN pp.estatus = 'liberada' THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT pp.id), 0)) * 100, 1) as porcentaje_aprobacion
        FROM pedidos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN piezas_producidas pp ON p.id = pp.pedido_id
        WHERE p.numero_pedido = :numero_pedido
        GROUP BY p.id, p.numero_pedido, c.razon_social, c.contacto_principal, c.telefono, c.correo, 
                 p.fecha_creacion, p.fecha_entrega, p.estatus, p.observaciones
    ";

    $stmtPedido = $pdo->prepare($sqlPedido);
    $stmtPedido->bindValue(':numero_pedido', $numero_pedido);
    $stmtPedido->execute();
    $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }

    // Obtener información de producción por item
    $sqlItems = "
        SELECT
            prod.id as produccion_id,
            prod.item_code,
            prod.descripcion,
            prod.qty_solicitada,
            prod.prod_total,
            prod.qty_pendiente,
            prod.unidad_medida,
            prod.estatus as estatus_produccion
        FROM produccion prod
        WHERE prod.pedido_id = :pedido_id
        ORDER BY prod.item_code
    ";

    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->bindValue(':pedido_id', $pedido['pedido_id']);
    $stmtItems->execute();
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todas las piezas del pedido con sus inspecciones
    $sqlPiezas = "
        SELECT
            pp.id,
            pp.folio_pieza,
            pp.item_code,
            pp.descripcion,
            pp.supervisor_produccion,
            pp.fecha_produccion,
            pp.estatus,
            pp.fecha_actualizacion,
            ci.id as inspeccion_id,
            ci.inspector_calidad,
            ci.fecha_inspeccion,
            ci.cantidad_aceptada,
            ci.cantidad_rechazada,
            ci.observaciones as observaciones_inspeccion
        FROM piezas_producidas pp
        LEFT JOIN calidad_inspecciones ci ON pp.folio_pieza = ci.folio_pieza
        WHERE pp.pedido_id = :pedido_id
        ORDER BY pp.item_code, pp.fecha_produccion DESC, pp.folio_pieza DESC
    ";

    $stmtPiezas = $pdo->prepare($sqlPiezas);
    $stmtPiezas->bindValue(':pedido_id', $pedido['pedido_id']);
    $stmtPiezas->execute();
    $piezas = $stmtPiezas->fetchAll(PDO::FETCH_ASSOC);

    // Obtener defectos para cada pieza que tenga inspección
    foreach ($piezas as &$pieza) {
        $pieza['defectos'] = [];

        if ($pieza['inspeccion_id']) {
            $sqlDefectos = "
                SELECT d.codigo, d.nombre, id.cantidad
                FROM inspeccion_defectos id
                JOIN defectos d ON id.defecto_id = d.id
                WHERE id.inspeccion_id = :inspeccion_id
            ";
            $stmtDefectos = $pdo->prepare($sqlDefectos);
            $stmtDefectos->bindValue(':inspeccion_id', $pieza['inspeccion_id']);
            $stmtDefectos->execute();
            $pieza['defectos'] = $stmtDefectos->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Agrupar piezas por item_code
    $piezasPorItem = [];
    foreach ($piezas as $pieza) {
        $itemCode = $pieza['item_code'];
        if (!isset($piezasPorItem[$itemCode])) {
            $piezasPorItem[$itemCode] = [];
        }
        $piezasPorItem[$itemCode][] = $pieza;
    }

    // Agregar contadores de piezas a cada item
    foreach ($items as &$item) {
        $itemCode = $item['item_code'];
        $item['piezas'] = $piezasPorItem[$itemCode] ?? [];
        $item['total_piezas'] = count($item['piezas']);

        // Calcular estadísticas de calidad por item
        $item['piezas_por_inspeccionar'] = 0;
        $item['piezas_liberadas'] = 0;
        $item['piezas_rechazadas'] = 0;
        $item['piezas_reinspeccion'] = 0;

        foreach ($item['piezas'] as $pieza) {
            switch ($pieza['estatus']) {
                case 'por_inspeccionar':
                    $item['piezas_por_inspeccionar']++;
                    break;
                case 'liberada':
                    $item['piezas_liberadas']++;
                    break;
                case 'rechazada':
                    $item['piezas_rechazadas']++;
                    break;
                case 'pendiente_reinspeccion':
                    $item['piezas_reinspeccion']++;
                    break;
            }
        }

        // Calcular porcentaje de aprobación por item
        if ($item['total_piezas'] > 0) {
            $item['porcentaje_aprobacion'] = round(($item['piezas_liberadas'] / $item['total_piezas']) * 100, 1);
        } else {
            $item['porcentaje_aprobacion'] = 0;
        }
    }

    echo json_encode([
        'exito' => true,
        'pedido' => $pedido,
        'items' => $items
    ]);

} catch (Exception $e) {
    echo json_encode([
        'exito' => false,
        'error' => $e->getMessage()
    ]);
}
