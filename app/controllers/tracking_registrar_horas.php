<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Se requiere POST.'
    ]);
    exit;
}

try {
    // Obtener datos del formulario
    $empleadoId = isset($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : 0;
    $pedidoId = isset($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : 0;
    $tipoTrabajoId = isset($_POST['tipo_trabajo_id']) ? (int)$_POST['tipo_trabajo_id'] : 0;
    $horaInicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : null;
    $horaFin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $cantidadProcesada = isset($_POST['cantidad_procesada']) ? floatval($_POST['cantidad_procesada']) : 0;
    $fechaRegistro = date('Y-m-d');

    // Validar datos obligatorios
    if ($empleadoId <= 0 || $pedidoId <= 0 || $tipoTrabajoId <= 0 || empty($horaInicio) || empty($horaFin)) {
        echo json_encode([
            'success' => false,
            'message' => 'Todos los campos obligatorios son requeridos'
        ]);
        exit;
    }

    // Verificar si ya existe una asignación para esta combinación
    $sqlBuscarAsignacion = "SELECT id FROM tracking_asignaciones
                         WHERE empleado_id = ? AND pedido_id = ? AND tipo_trabajo_id = ?";
    $stmtBuscar = $pdo->prepare($sqlBuscarAsignacion);
    $stmtBuscar->execute([$empleadoId, $pedidoId, $tipoTrabajoId]);
    $asignacion = $stmtBuscar->fetch(PDO::FETCH_ASSOC);

    $asignacionId = 0;

    // Si no existe la asignación, crearla
    if (!$asignacion) {
        $sqlCrearAsignacion = "INSERT INTO tracking_asignaciones
                              (empleado_id, pedido_id, tipo_trabajo_id, cantidad_total,
                               cantidad_procesada, estatus, fecha_asignacion)
                              VALUES (?, ?, ?, ?, ?, 'asignado', NOW())";
        $stmtCrear = $pdo->prepare($sqlCrearAsignacion);
        $cantidadTotal = $cantidadProcesada > 0 ? $cantidadProcesada : 1; // Si no se especifica, asumimos 1
        $stmtCrear->execute([$empleadoId, $pedidoId, $tipoTrabajoId, $cantidadTotal, $cantidadProcesada]);
        $asignacionId = $pdo->lastInsertId();
    } else {
        $asignacionId = $asignacion['id'];

        // Actualizar la cantidad procesada de la asignación
        if ($cantidadProcesada > 0) {
            $sqlActualizarCantidad = "UPDATE tracking_asignaciones
                                   SET cantidad_procesada = cantidad_procesada + ?
                                   WHERE id = ?";
            $stmtActualizar = $pdo->prepare($sqlActualizarCantidad);
            $stmtActualizar->execute([$cantidadProcesada, $asignacionId]);
        }
    }

    // Calcular minutos trabajados
    $tiempoInicio = strtotime($horaInicio);
    $tiempoFin = strtotime($horaFin);
    $minutosTotal = 0;

    if ($tiempoFin > $tiempoInicio) {
        $minutosTotal = round(($tiempoFin - $tiempoInicio) / 60);
    } else {
        // Si la hora de fin es menor (cruce de día), asumimos que son del mismo día
        echo json_encode([
            'success' => false,
            'message' => 'La hora de inicio debe ser anterior a la hora de fin'
        ]);
        exit;
    }

    // Registrar el tiempo detallado
    $sqlRegistrarTiempo = "INSERT INTO tracking_tiempo_detallado
                          (asignacion_id, empleado_id, fecha_registro, hora_inicio, hora_fin,
                           minutos_trabajados, cantidad_procesada, usuario_registro)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtRegistrar = $pdo->prepare($sqlRegistrarTiempo);
    $usuarioId = $_SESSION['usuario_id'] ?? 1; // ID de usuario actual o por defecto

    $stmtRegistrar->execute([
        $asignacionId,
        $empleadoId,
        $fechaRegistro,
        $horaInicio,
        $horaFin,
        $minutosTotal,
        $cantidadProcesada,
        $usuarioId
    ]);

    // Actualizar también el total de minutos trabajados en la asignación
    $sqlActualizarMinutos = "UPDATE tracking_asignaciones
                          SET minutos_trabajados = minutos_trabajados + ?
                          WHERE id = ?";
    $stmtMinutos = $pdo->prepare($sqlActualizarMinutos);
    $stmtMinutos->execute([$minutosTotal, $asignacionId]);

    // Si todo salió bien, retornar éxito
    echo json_encode([
        'success' => true,
        'message' => 'Horas registradas correctamente',
        'data' => [
            'asignacion_id' => $asignacionId,
            'minutos_trabajados' => $minutosTotal,
            'cantidad_procesada' => $cantidadProcesada
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar horas: ' . $e->getMessage()
    ]);
}