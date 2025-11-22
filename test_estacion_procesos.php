<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo "No autenticado";
    exit;
}

echo "<h2>Información de Tabla estacion_procesos</h2>";

// Obtener estructura
$sql = "DESCRIBE estacion_procesos";
$result = $pdo->query($sql);
$columns = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Columnas:</h3>";
echo "<pre>";
print_r($columns);
echo "</pre>";

echo "<h3>Datos de muestra:</h3>";
$sql = "SELECT * FROM estacion_procesos LIMIT 5";
$result = $pdo->query($sql);
$datos = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($datos);
echo "</pre>";

// Contar por estatus
echo "<h3>Conteo por estatus:</h3>";
$sql = "SELECT estatus, COUNT(*) as total FROM estacion_procesos GROUP BY estatus";
$result = $pdo->query($sql);
$conteos = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($conteos);
echo "</pre>";

// Test específico: Estaciones con proceso 4 (DETALLADO)
echo "<h3>Prueba: Estaciones con proceso 4 (DETALLADO):</h3>";
$sql = "SELECT DISTINCT e.id, e.nombre, e.nave, ep.proceso_id, ep.estatus
        FROM estaciones e
        INNER JOIN estacion_procesos ep ON e.id = ep.estacion_id
        WHERE e.estatus = 'activa'
        AND ep.proceso_id = 4";
$result = $pdo->query($sql);
$estaciones = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($estaciones);
echo "</pre>";

echo "<h3>Prueba: Todos los estacion_procesos:</h3>";
$sql = "SELECT * FROM estacion_procesos";
$result = $pdo->query($sql);
$todos = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
echo "Total registros: " . count($todos) . "\n";
print_r($todos);
echo "</pre>";
?>
