<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $empleado_id = isset($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;
    $proceso_id = isset($_POST['proceso_id']) ? (int)$_POST['proceso_id'] : null;
    $accion = isset($_POST['accion']) ? $_POST['accion'] : null;

    if (!$empleado_id || !$proceso_id) {
        echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
        exit;
    }

    if ($accion === 'asignar') {
        $nivel = isset($_POST['nivel']) ? $_POST['nivel'] : 'intermedio';

        $sql = "INSERT INTO empleado_procesos (empleado_id, proceso_id, nivel, estatus, usuario_creacion)
                VALUES (:empleado_id, :proceso_id, :nivel, 'activo', :usuario_id)
                ON DUPLICATE KEY UPDATE
                nivel = VALUES(nivel),
                estatus = 'activo'";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);
        $stmt->bindValue(':nivel', $nivel);
        $stmt->bindValue(':usuario_id', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Habilidad asignada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al asignar']);
        }

    } elseif ($accion === 'desasignar') {
        $sql = "UPDATE empleado_procesos SET estatus = 'inactivo'
                WHERE empleado_id = :empleado_id AND proceso_id = :proceso_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Habilidad removida']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al remover']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
