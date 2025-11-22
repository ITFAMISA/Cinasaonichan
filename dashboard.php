<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

$pageTitle = 'Dashboard - CINASA';

// Obtener estadísticas
try {
    // Total de clientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $totalClientes = $stmt->fetch()['total'];

    // Total de productos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE estatus = 'activo'");
    $totalProductos = $stmt->fetch()['total'];

    // Total de pedidos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
    $totalPedidos = $stmt->fetch()['total'];

    // Pedidos por estado
    $stmt = $pdo->query("SELECT estatus, COUNT(*) as total FROM pedidos GROUP BY estatus");
    $pedidosPorEstado = $stmt->fetchAll();

    // Total de empleados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE estatus_empleado = 'activo'");
    $totalEmpleados = $stmt->fetch()['total'];

    // Pedidos recientes
    $stmt = $pdo->query("SELECT p.*, c.razon_social 
                         FROM pedidos p 
                         LEFT JOIN clientes c ON p.cliente_id = c.id 
                         ORDER BY p.fecha_creacion DESC 
                         LIMIT 5");
    $pedidosRecientes = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error al cargar dashboard: " . $e->getMessage());
}

include __DIR__ . '/app/views/header.php';
?>

<!-- Logo de Fondo -->
<div style="position: fixed; top: 50%; left: 50%; width: 1200px; height: 1200px; margin-left: -600px; margin-top: -600px; background-image: url('<?php echo BASE_PATH; ?>/app/assets/img/logo.png'); background-size: 60%; background-repeat: no-repeat; background-position: center; background-attachment: fixed; opacity: 0.25; pointer-events: none; z-index: -1;"></div>

<!-- Welcome Section -->
<div class="mb-4">
    <h2 class="text-3xl font-bold text-slate-700">
        <i class="fas fa-home text-blue-600"></i>
        Bienvenido, <?php echo auth()->getUser()['nombre_completo'] ?? 'Usuario'; ?>
    </h2>
    <p class="text-slate-600">Panel de control del sistema de gestión empresarial</p>
</div>

<!-- Estadísticas Principales -->
<div class="row g-4 mb-4">
    <?php if (hasModuleAccess('clientes')): ?>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-90">Total Clientes</p>
                    <h3 class="mb-0"><?php echo number_format($totalClientes ?? 0); ?></h3>
                </div>
                <div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (hasModuleAccess('productos')): ?>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-90">Total Productos</p>
                    <h3 class="mb-0"><?php echo number_format($totalProductos ?? 0); ?></h3>
                </div>
                <div>
                    <i class="fas fa-boxes fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (hasModuleAccess('pedidos')): ?>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-90">Total Pedidos</p>
                    <h3 class="mb-0"><?php echo number_format($totalPedidos ?? 0); ?></h3>
                </div>
                <div>
                    <i class="fas fa-file-invoice fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (hasModuleAccess('empleados')): ?>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-90">Empleados Activos</p>
                    <h3 class="mb-0"><?php echo number_format($totalEmpleados ?? 0); ?></h3>
                </div>
                <div>
                    <i class="fas fa-user-tie fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Estado de Pedidos -->
    <?php if (hasModuleAccess('pedidos') && !empty($pedidosPorEstado)): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Estado de Pedidos
                </h5>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th class="text-end">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidosPorEstado as $estado): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo $estado['estatus']; ?>">
                                        <?php 
                                        echo match($estado['estatus']) {
                                            'creada' => 'Creada',
                                            'en_produccion' => 'En Producción',
                                            'completada' => 'Completada',
                                            'cancelada' => 'Cancelada',
                                            default => $estado['estatus']
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td class="text-end"><strong><?php echo $estado['total']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pedidos Recientes -->
    <?php if (hasModuleAccess('pedidos') && !empty($pedidosRecientes)): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Pedidos Recientes
                </h5>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidosRecientes as $pedido): ?>
                            <tr style="cursor: pointer;" onclick="window.location.href='<?php echo BASE_PATH; ?>/pedidos.php'">
                                <td><strong><?php echo htmlspecialchars($pedido['numero_pedido']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pedido['razon_social'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $pedido['estatus']; ?>">
                                        <?php 
                                        echo match($pedido['estatus']) {
                                            'creada' => 'Creada',
                                            'en_produccion' => 'En Producción',
                                            'completada' => 'Completada',
                                            'cancelada' => 'Cancelada',
                                            default => $pedido['estatus']
                                        };
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="<?php echo BASE_PATH; ?>/pedidos.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-arrow-right me-2"></i>Ver Todos los Pedidos
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Accesos Rápidos -->
<div class="row g-4 mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Accesos Rápidos
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if (hasModuleAccess('clientes')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/index.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                            Gestión de Clientes
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('productos')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/productos.php" class="btn btn-outline-success w-100 py-3">
                            <i class="fas fa-boxes fa-2x mb-2 d-block"></i>
                            Catálogo de Productos
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('pedidos')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/pedidos.php" class="btn btn-outline-warning w-100 py-3">
                            <i class="fas fa-file-invoice fa-2x mb-2 d-block"></i>
                            Gestión de Pedidos
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('produccion')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/produccion.php" class="btn btn-outline-info w-100 py-3">
                            <i class="fas fa-industry fa-2x mb-2 d-block"></i>
                            Producción
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('calidad')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/calidad.php" class="btn btn-outline-secondary w-100 py-3">
                            <i class="fas fa-clipboard-check fa-2x mb-2 d-block"></i>
                            Control de Calidad
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('empleados')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/empleados.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-user-tie fa-2x mb-2 d-block"></i>
                            Empleados
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('tracking')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/tracking_piezas.php" class="btn btn-outline-success w-100 py-3">
                            <i class="fas fa-route fa-2x mb-2 d-block"></i>
                            Tracking de Piezas
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('pdfs')): ?>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_PATH; ?>/pdf_ordenes.php" class="btn btn-outline-danger w-100 py-3">
                            <i class="fas fa-file-pdf fa-2x mb-2 d-block"></i>
                            Gestión de PDFs
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Mostrar mensaje si no tiene acceso a ningún módulo
                $tieneAlgunAcceso = hasModuleAccess('clientes') || hasModuleAccess('productos') || 
                                    hasModuleAccess('pedidos') || hasModuleAccess('produccion') || 
                                    hasModuleAccess('calidad') || hasModuleAccess('empleados') ||
                                    hasModuleAccess('tracking') || hasModuleAccess('pdfs');
                
                if (!$tieneAlgunAcceso): 
                ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No tienes acceso a ningún módulo. Contacta al administrador para asignar permisos.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</main>
<footer class="bg-gradient-to-r from-slate-100 via-blue-50 to-slate-100 text-center py-6 mt-8 shadow-inner">
    <div class="container">
        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
            <img src="<?php echo BASE_PATH; ?>/app/assets/img/logo.png" alt="CINASA Logo" class="h-8 w-8">
            <p class="text-slate-600 mb-0 font-medium">
                <i class="fas fa-copyright text-blue-600"></i>
                <?php echo date('Y'); ?> Catálogo Maestro de Clientes - Sistema Empresarial
            </p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
