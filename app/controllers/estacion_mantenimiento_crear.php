<?php
/**
 * API Endpoint: Crear registro de mantenimiento para una estación
 * POST /app/controllers/estacion_mantenimiento_crear.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../config/database.php';

    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar campos requeridos
    if (empty($input['estacion_id']) || empty($input['motivo']) || empty($input['fecha_inicio'])) {
        throw new Exception('Campos requeridos: estacion_id, motivo, fecha_inicio');
    }

    $estacion_id = intval($input['estacion_id']);
    $motivo = trim($input['motivo']);
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : null;
    $fecha_inicio = $input['fecha_inicio'];
    $usuario_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : null;

    // Validar que la estación existe
    $sqlVerify = "SELECT id FROM estaciones WHERE id = :estacion_id";
    $stmtVerify = $pdo->prepare($sqlVerify);
    $stmtVerify->execute([':estacion_id' => $estacion_id]);

    if (!$stmtVerify->fetch()) {
        throw new Exception('Estación no encontrada');
    }

    // Crear registro de mantenimiento
    $sql = "
        INSERT INTO estacion_mantenimiento (estacion_id, motivo, descripcion, fecha_inicio, usuario_id, estatus)
        VALUES (:estacion_id, :motivo, :descripcion, :fecha_inicio, :usuario_id, 'activo')
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':estacion_id' => $estacion_id,
        ':motivo' => $motivo,
        ':descripcion' => $descripcion,
        ':fecha_inicio' => $fecha_inicio,
        ':usuario_id' => $usuario_id
    ]);

    $mantenimiento_id = $pdo->lastInsertId();

    // Obtener el registro creado
    $sqlGet = "
        SELECT id, estacion_id, motivo, descripcion, fecha_inicio, fecha_fin, horas_mantenimiento, estatus
        FROM estacion_mantenimiento
        WHERE id = :id
    ";

    $stmtGet = $pdo->prepare($sqlGet);
    $stmtGet->execute([':id' => $mantenimiento_id]);
    $mantenimiento = $stmtGet->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Registro de mantenimiento creado exitosamente',
        'mantenimiento' => $mantenimiento
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
