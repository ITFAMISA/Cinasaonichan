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

    $pedido_id = $input['pedido_id'] ?? null;
    $motivo = $input['motivo'] ?? '';
    $datos_modificacion = $input['datos_modificacion'] ?? [];

    if (!$pedido_id || empty($motivo)) {
        throw new Exception('Faltan datos requeridos');
    }

    // Verificar que el pedido existe y está en producción
    $stmt = $pdo->prepare("SELECT id, estatus, numero_pedido FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }

    if ($pedido['estatus'] !== 'en_produccion') {
        throw new Exception('El pedido no está en producción');
    }

    // Crear la solicitud de modificación
    $stmt = $pdo->prepare("
        INSERT INTO solicitudes_modificacion_pedido
        (pedido_id, usuario_solicitante, motivo_modificacion, datos_modificacion, estatus)
        VALUES (?, ?, ?, ?, 'pendiente')
    ");

    $resultado = $stmt->execute([
        $pedido_id,
        $_SESSION['username'] ?? $_SESSION['user_id'],
        $motivo,
        json_encode($datos_modificacion, JSON_UNESCAPED_UNICODE)
    ]);

    if ($resultado) {
        $solicitud_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Solicitud de modificación enviada a gerencia',
            'solicitud_id' => $solicitud_id
        ]);
    } else {
        throw new Exception('Error al crear la solicitud');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
