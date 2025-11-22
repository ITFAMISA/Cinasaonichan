<?php
/**
 * API Endpoint: Listar procesos asignados a una estación con información de mantenimiento
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../config/database.php';

    // Validar que se proporcione estacion_id
    if (empty($_GET['estacion_id'])) {
        throw new Exception('estacion_id es requerido');
    }

    $estacion_id = intval($_GET['estacion_id']);

    // Obtener información de la estación
    $sqlEstacion = "SELECT e.id, e.nombre, e.estatus FROM estaciones e WHERE e.id = :estacion_id";
    $stmtEstacion = $pdo->prepare($sqlEstacion);
    $stmtEstacion->execute([':estacion_id' => $estacion_id]);
    $estacion = $stmtEstacion->fetch(PDO::FETCH_ASSOC);

    if (!$estacion) {
        throw new Exception('Estación no encontrada');
    }

    // Obtener procesos asignados a esta estación (VERSIÓN SIMPLE - sin GROUP BY)
    $sql = "
        SELECT DISTINCT
            p.id,
            p.nombre,
            8 as horas_posibles
        FROM procesos p
        INNER JOIN estacion_procesos ep ON p.id = ep.proceso_id AND ep.estatus = 'activo'
        WHERE ep.estacion_id = :estacion_id
        ORDER BY p.nombre ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':estacion_id' => $estacion_id]);
    $procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar máquinas por proceso y obtener horas de mantenimiento
    foreach ($procesos as &$proceso) {
        $sqlCount = "
            SELECT COUNT(DISTINCT ep.estacion_id) as cantidad_maquinas
            FROM estacion_procesos ep
            WHERE ep.proceso_id = :proceso_id AND ep.estatus = 'activo'
        ";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute([':proceso_id' => $proceso['id']]);
        $count = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $proceso['cantidad_maquinas'] = $count['cantidad_maquinas'] ?? 0;
        $proceso['horas_posibles'] = ($count['cantidad_maquinas'] ?? 0) * 8;

        // Obtener horas de mantenimiento acumuladas para este proceso en esta estación
        try {
            $sqlMantHoras = "
                SELECT COALESCE(total_horas_mantenimiento, 0) as horas_mantenimiento
                FROM estacion_proceso_mantenimiento
                WHERE estacion_id = :estacion_id AND proceso_id = :proceso_id
            ";
            $stmtMantHoras = $pdo->prepare($sqlMantHoras);
            $stmtMantHoras->execute([
                ':estacion_id' => $estacion_id,
                ':proceso_id' => $proceso['id']
            ]);
            $mantResult = $stmtMantHoras->fetch(PDO::FETCH_ASSOC);
            $proceso['horas_mantenimiento'] = floatval($mantResult['horas_mantenimiento'] ?? 0);
        } catch (Exception $e) {
            // Si la tabla no existe, poner 0
            $proceso['horas_mantenimiento'] = 0;
        }
    }

    // Obtener estado actual de mantenimiento
    $mantenimiento_activo = null;
    try {
        $sqlMantenimiento = "
            SELECT
                id,
                motivo,
                descripcion,
                fecha_inicio,
                fecha_fin,
                horas_mantenimiento,
                estatus
            FROM estacion_mantenimiento
            WHERE estacion_id = :estacion_id AND estatus = 'activo'
            ORDER BY fecha_inicio DESC
            LIMIT 1
        ";

        $stmtMantenimiento = $pdo->prepare($sqlMantenimiento);
        $stmtMantenimiento->execute([':estacion_id' => $estacion_id]);
        $mantenimiento_activo = $stmtMantenimiento->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Tabla no existe aún, ignorar
    }

    echo json_encode([
        'success' => true,
        'estacion' => $estacion,
        'procesos' => $procesos,
        'mantenimiento_activo' => $mantenimiento_activo ? $mantenimiento_activo : null,
        'total_procesos' => count($procesos)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
