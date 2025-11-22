<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener empleados activos organizados por nave (si es posible)
    // Si no hay relación de empleados con naves, simplemente se devuelven todos
    $sql = "SELECT
                e.id,
                CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                e.puesto,
                e.departamento,
                COALESCE(e.departamento, 'Otros') as nave
            FROM empleados e
            WHERE e.estatus_empleado = 'activo'
            ORDER BY e.departamento, e.apellido, e.nombre";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar empleados por nave (departamento)
    $empleadosPorNave = [];
    foreach ($empleados as $emp) {
        $nave = $emp['nave'];
        if (!isset($empleadosPorNave[$nave])) {
            $empleadosPorNave[$nave] = [];
        }
        $empleadosPorNave[$nave][] = $emp;
    }

    echo json_encode([
        'success' => true,
        'data' => $empleadosPorNave
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
