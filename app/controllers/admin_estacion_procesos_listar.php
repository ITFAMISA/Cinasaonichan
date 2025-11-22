<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/estaciones_model.php';

header('Content-Type: application/json');

// Verificar acceso (comentar para debug si es necesario)
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

try {
    $estacionesModel = new EstacionesModel($pdo);

    // Obtener todas las estaciones (activas e inactivas para admin)
    $estaciones = $estacionesModel->obtenerTodasEstaciones();

    // Para cada estación, obtener sus procesos asignados
    $resultado = [];
    foreach ($estaciones as $estacion) {
        $procesos = $estacionesModel->obtenerProcessosEstacion($estacion['id']);

        $resultado[] = [
            'id' => $estacion['id'],
            'nombre' => $estacion['nombre'],
            'tipo' => $estacion['tipo'],
            'nave' => $estacion['nave'],
            'color' => $estacion['color'],
            'estatus' => $estacion['estatus'],
            'orden' => $estacion['orden'],
            'procesos_asignados' => array_column($procesos, 'proceso_id'),
            'total_procesos' => count($procesos)
        ];
    }

    // Agrupar por nave
    $estacionesPorNave = [];
    foreach ($resultado as $est) {
        $nave = $est['nave'] ?: 'Sin nave';
        if (!isset($estacionesPorNave[$nave])) {
            $estacionesPorNave[$nave] = [];
        }
        $estacionesPorNave[$nave][] = $est;
    }

    echo json_encode([
        'success' => true,
        'data' => $estacionesPorNave
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
