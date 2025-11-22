<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/estaciones_model.php';

header('Content-Type: application/json');

// Verificar acceso
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado'
    ]);
    exit;
}

// Verificar acceso al módulo (opcional para admin)
/*
if (!hasModuleAccess('estaciones')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acceso denegado al módulo'
    ]);
    exit;
}
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    $estacionesModel = new EstacionesModel($pdo);

    $estacion_id = isset($_POST['estacion_id']) ? (int)$_POST['estacion_id'] : null;
    $proceso_id = isset($_POST['proceso_id']) ? (int)$_POST['proceso_id'] : null;
    $accion = isset($_POST['accion']) ? $_POST['accion'] : null; // 'asignar' o 'desasignar'

    if (!$estacion_id || !$proceso_id || !$accion) {
        echo json_encode([
            'success' => false,
            'message' => 'Parámetros incompletos'
        ]);
        exit;
    }

    if ($accion === 'asignar') {
        // Asignar proceso a estación
        $resultado = $estacionesModel->asignarProcesoAEstacion(
            $estacion_id,
            $proceso_id,
            false, // no es preferida por defecto
            999,   // orden de preferencia
            'Asignado desde admin'
        );

        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Proceso asignado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al asignar el proceso'
            ]);
        }

    } elseif ($accion === 'desasignar') {
        // Desasignar proceso de estación
        // Obtener el ID de estacion_procesos
        $sql = "SELECT id FROM estacion_procesos
                WHERE estacion_id = :estacion_id AND proceso_id = :proceso_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':estacion_id', $estacion_id, PDO::PARAM_INT);
        $stmt->bindValue(':proceso_id', $proceso_id, PDO::PARAM_INT);
        $stmt->execute();

        $estacion_proceso = $stmt->fetch();

        if ($estacion_proceso) {
            $resultado = $estacionesModel->eliminarEstacionProceso($estacion_proceso['id']);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Proceso desasignado correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al desasignar el proceso'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Relación no encontrada'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
