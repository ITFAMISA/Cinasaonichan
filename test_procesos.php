<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "No autenticado";
    exit;
}

echo "<h2>Comparación de Procesos</h2>";

echo "<h3>PROCESOS (tabla procesos) - para asignar a estaciones:</h3>";
$result = $pdo->query('SELECT id, nombre FROM procesos ORDER BY id');
$procesos = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th></tr>";
foreach ($procesos as $p) {
    echo "<tr><td>{$p['id']}</td><td>{$p['nombre']}</td></tr>";
}
echo "</table>";

echo "<h3>TRACKING_TIPOS_TRABAJO (tabla tracking_tipos_trabajo) - para filtros en tracking:</h3>";
$result = $pdo->query('SELECT id, nombre FROM tracking_tipos_trabajo ORDER BY id');
$tipos = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th></tr>";
foreach ($tipos as $t) {
    echo "<tr><td>{$t['id']}</td><td>{$t['nombre']}</td></tr>";
}
echo "</table>";

echo "<h3>ESTACION_PROCESOS (qué procesos se asignaron a máquinas):</h3>";
$result = $pdo->query('SELECT DISTINCT ep.proceso_id, p.nombre, COUNT(*) as total
                       FROM estacion_procesos ep
                       LEFT JOIN procesos p ON ep.proceso_id = p.id
                       GROUP BY ep.proceso_id');
$asignados = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Proceso ID</th><th>Nombre</th><th>Estaciones</th></tr>";
foreach ($asignados as $a) {
    echo "<tr><td>{$a['proceso_id']}</td><td>{$a['nombre']}</td><td>{$a['total']}</td></tr>";
}
echo "</table>";

echo "<h3>¿CUÁL ES EL PROBLEMA?</h3>";
echo "<p>El filtro en tracking_dashboard.php usa IDs 1-6 (tracking_tipos_trabajo).</p>";
echo "<p>Pero estación_procesos tiene asignados procesos de la tabla 'procesos'.</p>";
echo "<p><strong>Solución:</strong> Necesitamos mapear procesos → tracking_tipos_trabajo.</p>";
?>
