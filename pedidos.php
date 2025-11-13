<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/pedidos_model.php';

$pageTitle = 'Gestión de Pedidos';
$model = new PedidosModel($pdo);

include __DIR__ . '/app/views/header.php';
?>

<!-- Logo de Fondo Transparente -->
<div class="logo-background" style="position: fixed; top: 50%; left: 50%; width: 1200px; height: 1200px; margin-left: -600px; margin-top: -600px; background-image: url('<?php echo BASE_PATH; ?>/app/assets/img/logo.png'); background-size: 60%; background-repeat: no-repeat; background-position: center; background-attachment: fixed; opacity: 0.25; pointer-events: none; z-index: -1;"></div>

<!-- Enhanced Filter Section -->
<div class="filter-section">
    <h5 class="mb-3 flex items-center text-xl">
        <i class="fas fa-filter text-blue-600"></i>
        <span class="ml-2">Filtros de Búsqueda</span>
    </h5>
    <div class="row g-3">
        <div class="col-md-3">
            <label for="buscar" class="form-label">Buscar</label>
            <div class="relative">
                <input type="text" class="form-control pl-10" id="buscar" placeholder="Número de pedido o cliente...">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
        <div class="col-md-3">
            <label for="cliente_filtro" class="form-label">Cliente</label>
            <input type="text" class="form-control" id="cliente_filtro" placeholder="Filtro de cliente..." readonly style="background-color: #f3f4f6;">
        </div>
        <div class="col-md-2">
            <label for="estatus" class="form-label">Estatus</label>
            <select class="form-select" id="estatus">
                <option value="">Todos</option>
                <option value="creada">Creada</option>
                <option value="en_produccion">En Producción</option>
                <option value="completada">Completada</option>
                <option value="cancelada">Cancelada</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100 group" id="btnBuscar" title="Buscar">
                <i class="fas fa-search transition-transform group-hover:scale-125"></i>
            </button>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2" id="btnLimpiarClienteContainer" style="display:none;">
            <button class="btn btn-secondary w-100 group" id="btnLimpiarCliente" title="Limpiar filtro de cliente">
                <i class="fas fa-times transition-transform group-hover:rotate-90"></i>
                <span class="ml-1">Limpiar Cliente</span>
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
            <i class="fas fa-file-invoice mr-2"></i> Gestión de Pedidos
        </span>
        <div class="flex gap-2">
            <button class="btn btn-primary btn-sm group" id="btnNuevoPedido">
                <i class="fas fa-plus transition-transform group-hover:rotate-90"></i>
                <span class="ml-1">Nuevo Pedido</span>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="sortable" data-column="numero_pedido">Número Pedido</th>
                        <th class="sortable" data-column="razon_social">Cliente</th>
                        <th class="sortable" data-column="fecha_creacion">Fecha Creación</th>
                        <th class="sortable" data-column="estatus">Estatus</th>
                        <th>Contacto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaPedidos">
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

    </main>
    <footer class="bg-gradient-to-r from-slate-100 via-blue-50 to-slate-100 text-center py-6 mt-8 shadow-inner">
        <div class="container">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <img src="<?php echo BASE_PATH; ?>/app/assets/img/logo.png" alt="CINASA Logo" class="h-8 w-8">
                <p class="text-slate-600 mb-0 font-medium">
                    <i class="fas fa-copyright text-blue-600"></i>
                    <?php echo date('Y'); ?> Sistema de Gestión de Pedidos - CINASA
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_PATH; ?>/app/assets/pedidos.js?v=<?php echo time(); ?>"></script>
</body>
</html>
