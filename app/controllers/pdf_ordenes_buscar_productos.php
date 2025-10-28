<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/pdf_ordenes_model.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['codigos']) || !is_array($input['codigos'])) {
        throw new Exception('Se requiere un array de códigos para buscar');
    }

    $model = new PdfOrdenesModel($pdo);
    $resultados = [];

    foreach ($input['codigos'] as $codigo) {
        $productos = $model->buscarProducto($codigo);
        if (!empty($productos)) {
            $resultados[$codigo] = $productos;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $resultados
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}
