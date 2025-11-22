<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener procesos seleccionados desde query parameters
    $procesosSeleccionados = isset($_GET['procesos']) ? explode(',', $_GET['procesos']) : [];
    $procesosSeleccionados = array_filter(array_map('intval', $procesosSeleccionados));

    if (empty($procesosSeleccionados)) {
        // Si no hay filtro, retornar todos los empleados activos
        $sql = "SELECT id, nombre, apellido, puesto, estatus_empleado
                FROM empleados
                WHERE estatus_empleado = 'activo'
                ORDER BY apellido, nombre";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Obtener empleados que tengan al menos uno de los procesos
        $placeholders = implode(',', array_fill(0, count($procesosSeleccionados), '?'));

        $sql = "SELECT DISTINCT e.id, e.nombre, e.apellido, e.puesto, e.estatus_empleado
                FROM empleados e
                INNER JOIN empleado_procesos ep ON e.id = ep.empleado_id
                WHERE e.estatus_empleado = 'activo'
                AND ep.estatus = 'activo'
                AND ep.proceso_id IN ($placeholders)
                ORDER BY e.apellido, e.nombre";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($procesosSeleccionados);
    }

    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada empleado, obtener sus procesos
    $resultado = [];
    foreach ($empleados as $emp) {
        $sqlProcesos = "SELECT ep.proceso_id, p.nombre as proceso_nombre
                        FROM empleado_procesos ep
                        LEFT JOIN procesos p ON ep.proceso_id = p.id
                        WHERE ep.empleado_id = :empleado_id
                        AND ep.estatus = 'activo'";

        $stmtProcesos = $pdo->prepare($sqlProcesos);
        $stmtProcesos->bindValue(':empleado_id', $emp['id'], PDO::PARAM_INT);
        $stmtProcesos->execute();
        $procesos = $stmtProcesos->fetchAll(PDO::FETCH_ASSOC);

        $emp['proceso_ids'] = array_column($procesos, 'proceso_id');
        $emp['proceso_nombres'] = array_column($procesos, 'proceso_nombre');

        $resultado[] = $emp;
    }

    echo json_encode([
        'success' => true,
        'data' => $resultado
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
