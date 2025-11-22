<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

// Verificar acceso al módulo de administración
if (!hasModuleAccess('usuarios') && !hasModuleAccess('roles')) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$pageTitle = 'Panel de Administración';

// Variables para controlar qué secciones mostrar
$canManageUsers = hasPermission('usuarios.listar');
$canManageRoles = hasPermission('roles.listar');

include __DIR__ . '/app/views/header.php';
?>

<!-- Logo de Fondo Transparente -->
<div class="logo-background" style="position: fixed; top: 50%; left: 50%; width: 1200px; height: 1200px; margin-left: -600px; margin-top: -600px; background-image: url('<?php echo BASE_PATH; ?>/app/assets/img/logo.png'); background-size: 60%; background-repeat: no-repeat; background-position: center; background-attachment: fixed; opacity: 0.25; pointer-events: none; z-index: -1;"></div>

<?php if ($canManageUsers): ?>
<!-- SECCIÓN USUARIOS -->
<div class="mb-5">
    <!-- Enhanced Filter Section -->
    <div class="filter-section">
        <h5 class="mb-3 flex items-center text-xl">
            <i class="fas fa-filter text-blue-600"></i>
            <span class="ml-2">Filtros de Búsqueda</span>
        </h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="buscar_usuario" class="form-label">Buscar Usuario</label>
                <div class="relative">
                    <input type="text" class="form-control pl-10" id="buscar_usuario" placeholder="Nombre, usuario o correo...">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <div class="col-md-5">
                <label for="filtro_rol" class="form-label">Filtrar por Rol</label>
                <select class="form-select" id="filtro_rol">
                    <option value="">Todos los roles</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100 group" onclick="cargarUsuarios(1)" title="Buscar">
                    <i class="fas fa-search transition-transform group-hover:scale-125"></i>
                </button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <button class="btn btn-secondary btn-sm group" onclick="limpiarFiltrosUsuarios()">
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
                <i class="fas fa-users mr-2"></i> Gestión de Usuarios
            </span>
            <div class="flex gap-2">
                <?php if (hasPermission('usuarios.crear')): ?>
                <button id="btnCrearUsuario" class="btn btn-primary btn-sm group">
                    <i class="fas fa-plus transition-transform group-hover:rotate-90"></i>
                    <span class="ml-1">Nuevo Usuario</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Correo</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <?php if (hasPermission('usuarios.editar') || hasPermission('usuarios.eliminar')): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tablaUsuarios">
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
                <div id="contador-usuarios" class="text-muted"></div>
                <div id="paginacion-usuarios" class="pagination-container"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageUsers && $canManageRoles): ?>
<hr class="my-5">
<?php endif; ?>

<?php if ($canManageRoles): ?>
<!-- SECCIÓN ROLES -->
<div class="mb-5">
    <!-- Enhanced Main Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="flex items-center">
                <i class="fas fa-shield-alt mr-2"></i> Gestión de Roles
            </span>
            <div class="flex gap-2">
                <?php if (hasPermission('roles.crear')): ?>
                <button id="btnCrearRol" class="btn btn-primary btn-sm group">
                    <i class="fas fa-plus transition-transform group-hover:rotate-90"></i>
                    <span class="ml-1">Nuevo Rol</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Usuarios</th>
                            <th>Permisos</th>
                            <th>Estado</th>
                            <?php if (hasPermission('roles.editar') || hasPermission('roles.eliminar')): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tablaRoles">
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
            <div id="contador-roles" class="text-muted"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageRoles): ?>
<hr class="my-5">
<!-- SECCIÓN PERMISOS POR MÓDULO -->
<div class="mb-5">
    <!-- Enhanced Main Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="flex items-center">
                <i class="fas fa-lock mr-2"></i> Gestión de Permisos
            </span>
            <div class="flex gap-2">
                <select class="form-select form-select-sm" id="filtroModuloPermisos" style="max-width: 250px;">
                    <option value="">Todos los módulos</option>
                    <option value="usuarios">Usuarios</option>
                    <option value="roles">Roles</option>
                    <option value="estaciones">Estaciones/Máquinas</option>
                    <option value="procesos">Procesos</option>
                    <option value="productos">Productos</option>
                    <option value="pedidos">Pedidos</option>
                    <option value="produccion">Producción</option>
                    <option value="calidad">Calidad</option>
                    <option value="empleados">Empleados</option>
                    <option value="tracking">Tracking</option>
                    <option value="pdfs">PDFs</option>
                    <option value="reportes">Reportes</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Módulo</th>
                            <th>Permiso</th>
                            <th>Descripción</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPermisos">
                        <tr>
                            <td colspan="4" class="text-center">
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
            <div id="contador-permisos" class="text-muted"></div>
        </div>
    </div>
</div>
<?php endif; ?>

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
<script>
    // Permisos del usuario actual
    const USER_PERMISSIONS = {
        canCreateUser: <?php echo hasPermission('usuarios.crear') ? 'true' : 'false'; ?>,
        canEditUser: <?php echo hasPermission('usuarios.editar') ? 'true' : 'false'; ?>,
        canDeleteUser: <?php echo hasPermission('usuarios.eliminar') ? 'true' : 'false'; ?>,
        canCreateRole: <?php echo hasPermission('roles.crear') ? 'true' : 'false'; ?>,
        canEditRole: <?php echo hasPermission('roles.editar') ? 'true' : 'false'; ?>,
        canDeleteRole: <?php echo hasPermission('roles.eliminar') ? 'true' : 'false'; ?>,
        canManageUsers: <?php echo $canManageUsers ? 'true' : 'false'; ?>,
        canManageRoles: <?php echo $canManageRoles ? 'true' : 'false'; ?>
    };
</script>
<script src="<?php echo BASE_PATH; ?>/app/assets/admin.js?v=<?php echo time(); ?>"></script>
<script>
    // Event listeners para botones
    <?php if (hasPermission('usuarios.crear')): ?>
    const btnCrearUsuario = document.getElementById('btnCrearUsuario');
    if (btnCrearUsuario) {
        btnCrearUsuario.addEventListener('click', abrirModalCrearUsuario);
    }
    <?php endif; ?>
    
    <?php if (hasPermission('roles.crear')): ?>
    const btnCrearRol = document.getElementById('btnCrearRol');
    if (btnCrearRol) {
        btnCrearRol.addEventListener('click', abrirModalCrearRol);
    }
    <?php endif; ?>
</script>
</body>
</html>
