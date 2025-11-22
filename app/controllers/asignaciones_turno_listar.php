<?php
/**
 * Controlador para listar asignaciones de empleados a estaciones por turno
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener asignaciones con información completa
    $sql = "SELECT
                aet.id,
                aet.estacion_id,
                aet.turno_id,
                aet.empleado_id,
                aet.pedido_id,
                aet.tipo_trabajo_id,
                aet.cantidad_total,
                aet.cantidad_procesada,
                aet.estatus,
                aet.notas,
                aet.fecha_asignacion,
                e.nombre as empleado_nombre,
                e.apellido as empleado_apellido,
                p.numero_pedido,
                c.razon_social,
                t.nombre as turno_nombre,
                t.hora_inicio,
                t.hora_fin,
                est.nombre as estacion_nombre,
                tt.nombre as tipo_trabajo_nombre,
                tt.color as tipo_trabajo_color
            FROM asignaciones_estacion_turno aet
            JOIN empleados e ON aet.empleado_id = e.id
            JOIN pedidos p ON aet.pedido_id = p.id
            JOIN clientes c ON p.cliente_id = c.id
            JOIN turnos t ON aet.turno_id = t.id
            JOIN estaciones est ON aet.estacion_id = est.id
            LEFT JOIN tracking_tipos_trabajo tt ON aet.tipo_trabajo_id = tt.id
            WHERE aet.estatus IN ('pendiente', 'en_progreso')
            ORDER BY aet.estacion_id, aet.turno_id, aet.fecha_asignacion";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $asignaciones
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener asignaciones: ' . $e->getMessage()
    ]);
}
?>
