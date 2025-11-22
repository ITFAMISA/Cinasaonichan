<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $empleado_id = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;

    if ($empleado_id) {
        // Obtener procesos específicos de un empleado
        $sql = "SELECT ep.id, ep.empleado_id, ep.proceso_id, p.nombre as proceso_nombre,
                       ep.nivel, ep.estatus
                FROM empleado_procesos ep
                LEFT JOIN procesos p ON ep.proceso_id = p.id
                WHERE ep.empleado_id = :empleado_id
                AND ep.estatus = 'activo'
                ORDER BY p.nombre";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        $procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $procesos
        ]);
    } else {
        // Obtener todos los empleados con sus procesos
        $sql = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                       GROUP_CONCAT(p.id) as proceso_ids,
                       GROUP_CONCAT(p.nombre) as proceso_nombres
                FROM empleados e
                LEFT JOIN empleado_procesos ep ON e.id = ep.empleado_id AND ep.estatus = 'activo'
                LEFT JOIN procesos p ON ep.proceso_id = p.id
                WHERE e.estatus_empleado = 'activo'
                GROUP BY e.id
                ORDER BY e.apellido, e.nombre";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Procesar datos
        $resultado = [];
        foreach ($empleados as $emp) {
            $procesoIds = $emp['proceso_ids'] ? explode(',', $emp['proceso_ids']) : [];
            $procesoNombres = $emp['proceso_nombres'] ? explode(',', $emp['proceso_nombres']) : [];

            $resultado[] = [
                'id' => $emp['id'],
                'nombre_completo' => $emp['nombre_completo'],
                'proceso_ids' => array_map('intval', array_filter($procesoIds)),
                'proceso_nombres' => array_filter($procesoNombres)
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $resultado
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
