<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/produccion_model.php';
require_once __DIR__ . '/../models/calidad_model.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $model = new ProductionModel($pdo);
    $calidadModel = new CalidadModel($pdo);

    $id = $_POST['id'] ?? null;
    $cantidadHoy = $_POST['cantidad_hoy'] ?? null;

    if (empty($id) || $cantidadHoy === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos'
        ]);
        exit;
    }

    $cantidadHoy = floatval($cantidadHoy);

    if ($cantidadHoy < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'La cantidad no puede ser negativa'
        ]);
        exit;
    }

    // Obtener el registro actual
    $registro = $model->obtenerProduccionPorId($id);
    if (!$registro) {
        echo json_encode([
            'success' => false,
            'message' => 'Registro de producción no encontrado'
        ]);
        exit;
    }

    // Validar que no exceda la cantidad solicitada
    $nuevoTotal = $registro['prod_total'] - $registro['prod_hoy'] + $cantidadHoy;

    if ($nuevoTotal > $registro['qty_solicitada'] * 1.05) {
        echo json_encode([
            'success' => false,
            'message' => 'La producción total (' . $nuevoTotal . ') excede la cantidad solicitada (' . $registro['qty_solicitada'] . ')'
        ]);
        exit;
    }

    // Si la producción de hoy es > 0, registrar en historial PRIMERO
    if ($cantidadHoy > 0) {
        $datosHistorial = [
            'produccion_id' => $id,
            'pedido_id' => $registro['pedido_id'],
            'producto_id' => $registro['producto_id'],
            'numero_pedido' => $registro['numero_pedido'],
            'item_code' => $registro['item_code'],
            'cantidad_producida' => $cantidadHoy,
            'fecha_produccion' => date('Y-m-d'),
            'supervisor' => $_POST['supervisor'] ?? null,
            'observaciones' => $_POST['observaciones'] ?? null
        ];

        $model->registrarProduccionHistorial($datosHistorial);
    }

    // Luego actualizar la producción (prod_total se recalcula a partir del histórico)
    $model->actualizarProduccionHoy($id, $cantidadHoy);

    // Generar piezas individuales si se ha producido cantidad > 0
    if ($cantidadHoy > 0) {
        try {
            // Redondear cantidad para crear piezas enteras
            $cantidadPiezas = max(1, round($cantidadHoy));
            $supervisor = $_POST['supervisor'] ?? null;
            $piezasCreadas = $calidadModel->crearPiezasProducidas($id, $cantidadPiezas, $supervisor);
            error_log("Se crearon " . count($piezasCreadas) . " piezas para producción ID: " . $id . " (cantidad producida: " . $cantidadHoy . ")");

            // Actualizar estado de calidad en la tabla de producción
            $model->actualizarEstadoCalidad($id);
        } catch (Exception $e) {
            $mensajeError = "Error creando piezas para calidad: " . $e->getMessage();
            error_log($mensajeError);
            error_log("Stack trace: " . $e->getTraceAsString());

            // Retornar error al usuario para que sepa que las piezas no se crearon
            echo json_encode([
                'success' => false,
                'message' => $mensajeError,
                'produccion_registrada' => true // Indicar que la producción sí se guardó
            ]);
            exit;
        }
    }

    // Obtener el registro actualizado
    $registroActualizado = $model->obtenerProduccionPorId($id);

    echo json_encode([
        'success' => true,
        'message' => 'Producción actualizada correctamente',
        'data' => $registroActualizado
    ]);

} catch (Exception $e) {
    error_log("Error al actualizar producción: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar producción: ' . $e->getMessage()
    ]);
}
?>
