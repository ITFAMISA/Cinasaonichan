<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener parámetros
    $buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    $estatus = isset($_GET['estatus']) ? trim($_GET['estatus']) : 'en_produccion'; // Por defecto solo pedidos en producción

    // Consulta base
    $sql = "SELECT p.id, p.numero_pedido, p.fecha_creacion, p.estatus,
                  c.razon_social, c.contacto_principal as contacto
            FROM pedidos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE 1=1";

    $params = [];

    // Agregar filtro de búsqueda
    if (!empty($buscar)) {
        $sql .= " AND (p.numero_pedido LIKE ? OR c.razon_social LIKE ?)";
        $params[] = "%{$buscar}%";
        $params[] = "%{$buscar}%";
    }

    // Filtrar por estatus si no es 'todos'
    if ($estatus !== 'todos' && !empty($estatus)) {
        $sql .= " AND p.estatus = ?";
        $params[] = $estatus;
    }

    // Ordenar por fecha de creación (más recientes primero)
    $sql .= " ORDER BY p.fecha_creacion DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $pedidos
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener pedidos: ' . $e->getMessage()
    ]);
}