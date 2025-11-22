<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

$pageTitle = 'Solicitudes de Modificación';

// Verificar que el usuario es gerente o administrador para mostrar Solicitudes
$stmt = $pdo->prepare("SELECT GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles FROM usuarios u
                       LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                       LEFT JOIN roles r ON ur.rol_id = r.id
                       WHERE u.id = ?
                       GROUP BY u.id");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

$rolesUsuario = $usuario ? strtolower($usuario['roles'] ?? '') : '';
$esGerente = !empty($rolesUsuario) && (strpos($rolesUsuario, 'gerente') !== false || strpos($rolesUsuario, 'administrador') !== false || strpos($rolesUsuario, 'admin') !== false);

include __DIR__ . '/app/views/header.php';
?>

<!-- Logo de Fondo Transparente -->
<div class="logo-background" style="position: fixed; top: 50%; left: 50%; width: 1200px; height: 1200px; margin-left: -600px; margin-top: -600px; background-image: url('<?php echo BASE_PATH; ?>/app/assets/img/logo.png'); background-size: 60%; background-repeat: no-repeat; background-position: center; background-attachment: fixed; opacity: 0.25; pointer-events: none; z-index: -1;"></div>

<?php if (!$esGerente): ?>
    <div class="alert alert-danger">
        <i class="fas fa-ban me-2"></i>
        No tiene permisos de gerencia para acceder a esta sección.
    </div>
<?php else: ?>

<!-- Enhanced Filter Section -->
<div class="filter-section">
    <h5 class="mb-3 flex items-center text-xl">
        <i class="fas fa-filter text-blue-600"></i>
        <span class="ml-2">Filtros de Búsqueda</span>
    </h5>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="buscar" class="form-label">Buscar</label>
            <div class="relative">
                <input type="text" class="form-control pl-10" id="buscar" placeholder="Número de pedido o solicitante...">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
        <div class="col-md-3">
            <label for="estatus" class="form-label">Estado</label>
            <select class="form-select" id="estatus">
                <option value="" selected>Todos</option>
                <option value="pendiente">Pendientes</option>
                <option value="aprobada">Aprobadas</option>
                <option value="rechazada">Rechazadas</option>
                <option value="aplicada">Aplicadas</option>
            </select>
        </div>
        <div class="col-md-1 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100 group" id="btnBuscar" title="Buscar">
                <i class="fas fa-search transition-transform group-hover:scale-125"></i>
            </button>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <button class="btn btn-secondary btn-sm group" id="btnLimpiarFiltros">
                <i class="fas fa-eraser transition-transform group-hover:rotate-12"></i>
                <span class="ml-1">Limpiar Filtros</span>
            </button>
        </div>
    </div>
</div>

<!-- Enhanced Main Card -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="flex items-center">
            <i class="fas fa-clipboard-list mr-2"></i> Solicitudes de Modificación
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="sortable" data-column="numero_pedido">Pedido</th>
                        <th class="sortable" data-column="fecha_solicitud">Solicitado</th>
                        <th class="sortable" data-column="usuario_solicitante">Solicitante</th>
                        <th class="sortable" data-column="motivo_modificacion">Motivo</th>
                        <th class="sortable" data-column="estatus">Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaSolicitudes">
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div id="contador" class="text-muted"></div>
            <div id="paginacion" class="pagination-container"></div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Los modales se generarán dinámicamente desde el JavaScript -->

    </main>
    <footer class="bg-gradient-to-r from-slate-100 via-blue-50 to-slate-100 text-center py-6 mt-8 shadow-inner">
        <div class="container">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <img src="<?php echo BASE_PATH; ?>/app/assets/img/logo.png" alt="CINASA Logo" class="h-8 w-8">
                <p class="text-slate-600 mb-0 font-medium">
                    <i class="fas fa-copyright text-blue-600"></i>
                    <?php echo date('Y'); ?> Solicitudes de Modificación - CINASA
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_PATH; ?>/app/assets/solicitudes_tabla.js?v=<?php echo time(); ?>"></script>
</body>
</html>
