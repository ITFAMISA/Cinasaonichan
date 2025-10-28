<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/pdf_ordenes_model.php';

header('Content-Type: application/json');

try {
    $model = new PdfOrdenesModel($pdo);

    $filtros = [
        'estatus' => $_GET['estatus'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
    ];

    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $porPagina = isset($_GET['porPagina']) ? (int)$_GET['porPagina'] : 20;
    $offset = ($pagina - 1) * $porPagina;

    $pdfs = $model->listarPdfsProcesados($filtros, $porPagina, $offset);

    // Decodificar JSON en datos_estructurados
    foreach ($pdfs as &$pdf) {
        if (!empty($pdf['datos_estructurados'])) {
            $pdf['datos_estructurados'] = json_decode($pdf['datos_estructurados'], true);
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $pdfs,
        'pagina' => $pagina
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener PDFs: ' . $e->getMessage()
    ]);
}
