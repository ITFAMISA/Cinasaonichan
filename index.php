<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';

$pageTitle = 'CINASA - Gestión de Clientes';
require_once __DIR__ . '/app/views/header.php';
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
        <div class="col-md-4">
            <label for="buscar" class="form-label">Buscar</label>
            <div class="relative">
                <input type="text" class="form-control pl-10" id="buscar" placeholder="Razón social, RFC o contacto...">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
        <div class="col-md-2">
            <label for="filtro_estatus" class="form-label">Estatus</label>
            <select class="form-select" id="filtro_estatus">
                <option value="">Todos</option>
                <option value="activo">Activo</option>
                <option value="suspendido">Suspendido</option>
                <option value="bloqueado">Bloqueado</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filtro_vendedor" class="form-label">Vendedor</label>
            <select class="form-select" id="filtro_vendedor">
                <option value="">Todos</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="filtro_pais" class="form-label">País</label>
            <select class="form-select" id="filtro_pais">
                <option value="">Todos</option>
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
            <i class="fas fa-list mr-2"></i> Listado de Clientes
        </span>
        <div class="flex gap-2">
            <button class="btn btn-success btn-sm group" id="btnExportarCSV">
                <i class="fas fa-file-csv transition-transform group-hover:scale-125"></i>
                <span class="ml-1">Exportar CSV</span>
            </button>
            <button class="btn btn-primary btn-sm group" id="btnNuevoCliente">
                <i class="fas fa-plus transition-transform group-hover:rotate-90"></i>
                <span class="ml-1">Nuevo Cliente</span>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="sortable" data-column="id">ID</th>
                        <th class="sortable" data-column="razon_social">Razón Social</th>
                        <th class="sortable" data-column="rfc">RFC</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th class="sortable" data-column="estatus">Estatus</th>
                        <th class="sortable" data-column="vendedor_asignado">Vendedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaClientes">
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-body border-top bg-gradient-to-r from-slate-50 to-blue-50">
        <div class="d-flex justify-content-center">
            <div id="paginacion"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/app/views/footer.php'; ?>
