<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    // Parámetros de búsqueda y filtrado
    $solicitud_id = $_GET['solicitud_id'] ?? null;
    $buscar = $_GET['buscar'] ?? '';
    $estatus = $_GET['estatus'] ?? '';
    $orden = $_GET['orden'] ?? 'fecha_solicitud';
    $direccion = $_GET['direccion'] ?? 'DESC';
    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $por_pagina = 10;

    // Validar orden y dirección
    $ordenes_validas = ['numero_pedido', 'fecha_solicitud', 'usuario_solicitante', 'motivo_modificacion', 'estatus'];
    if (!in_array($orden, $ordenes_validas)) {
        $orden = 'fecha_solicitud';
    }
    $direccion = strtoupper($direccion) === 'ASC' ? 'ASC' : 'DESC';

    // Construir la consulta base
    $sql = "SELECT s.*, p.numero_pedido
            FROM solicitudes_modificacion_pedido s
            INNER JOIN pedidos p ON s.pedido_id = p.id
            WHERE 1=1";

    $params = [];

    // Filtro por ID específico (para modal de detalles)
    if (!empty($solicitud_id)) {
        $sql .= " AND s.id = ?";
        $params[] = $solicitud_id;
    }

    // Filtro de búsqueda
    if (!empty($buscar)) {
        $sql .= " AND (p.numero_pedido LIKE ? OR s.usuario_solicitante LIKE ?)";
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }

    // Filtro de estatus
    if (!empty($estatus)) {
        $sql .= " AND s.estatus = ?";
        $params[] = $estatus;
    }

    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total FROM solicitudes_modificacion_pedido s
                 INNER JOIN pedidos p ON s.pedido_id = p.id
                 WHERE 1=1";

    if (!empty($solicitud_id)) {
        $countSql .= " AND s.id = ?";
    }
    if (!empty($buscar)) {
        $countSql .= " AND (p.numero_pedido LIKE ? OR s.usuario_solicitante LIKE ?)";
    }
    if (!empty($estatus)) {
        $countSql .= " AND s.estatus = ?";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRegistros = $countStmt->fetch()['total'] ?? 0;
    $totalPaginas = ceil($totalRegistros / $por_pagina);
    $pagina = min($pagina, max(1, $totalPaginas));

    // Ordenamiento
    $sql .= " ORDER BY s.$orden $direccion";

    // Paginación
    $offset = ($pagina - 1) * $por_pagina;
    $sql .= " LIMIT $por_pagina OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enriquecer datos_modificacion con descripciones de items
    foreach ($solicitudes as &$solicitud) {
        if ($solicitud['datos_modificacion']) {
            $datos_mod = json_decode($solicitud['datos_modificacion'], true);

            // Si contiene items modificados, traer descripciones
            if (is_array($datos_mod)) {
                // Buscar arrays que contengan item_id
                foreach ($datos_mod as $key => $value) {
                    if (is_array($value) && !empty($value) && isset($value[0]['item_id'])) {
                        // Es un array de items, enriquecer con descripción
                        foreach ($value as &$item) {
                            if (isset($item['item_id'])) {
                                $itemStmt = $pdo->prepare("
                                    SELECT descripcion FROM pedidos_items
                                    WHERE id = ? LIMIT 1
                                ");
                                $itemStmt->execute([$item['item_id']]);
                                $itemData = $itemStmt->fetch(PDO::FETCH_ASSOC);
                                if ($itemData) {
                                    $item['descripcion'] = $itemData['descripcion'];
                                }
                            }
                        }
                        unset($item);
                        // Volver a convertir a JSON
                        $datos_mod[$key] = $value;
                    }
                }
                $solicitud['datos_modificacion'] = json_encode($datos_mod, JSON_UNESCAPED_UNICODE);
            }
        }
    }
    unset($solicitud);

    echo json_encode([
        'success' => true,
        'data' => $solicitudes,
        'pagination' => [
            'current_page' => $pagina,
            'total_pages' => $totalPaginas,
            'total' => $totalRegistros,
            'per_page' => $por_pagina
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
