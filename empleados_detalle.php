<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/empleados_model.php';

if (!isset($_GET['id'])) {
    header('Location: empleados.php');
    exit;
}

$pageTitle = 'Detalle de Empleado';
$model = new EmpleadosModel($pdo);
$empleado = $model->obtenerEmpleadoPorId((int)$_GET['id']);

if (!$empleado) {
    header('Location: empleados.php');
    exit;
}

// Obtener supervisor si existe
if ($empleado['supervisor_directo_id']) {
    $supervisor = $model->obtenerEmpleadoPorId($empleado['supervisor_directo_id']);
    $empleado['nombre_supervisor'] = $supervisor ? $supervisor['nombre'] . ' ' . $supervisor['apellido'] : 'N/A';
}

include __DIR__ . '/app/views/header.php';
?>

<div class="container-lg mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-user-tie mr-2"></i>
                    <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>
                </h2>
                <div class="gap-2">
                    <a href="empleados.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button class="btn btn-warning" onclick="editarEmpleado(<?php echo $empleado['id']; ?>)">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información General -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-info-circle"></i> Información General
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Número Empleado</label>
                            <p><?php echo htmlspecialchars($empleado['numero_empleado'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Estatus</label>
                            <p>
                                <span class="badge badge-<?php echo htmlspecialchars($empleado['estatus_empleado'] ?: 'primary'); ?>">
                                    <?php echo ucfirst($empleado['estatus_empleado'] ?: 'Activo'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Puesto</label>
                            <p><?php echo htmlspecialchars($empleado['puesto'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Departamento</label>
                            <p><?php echo htmlspecialchars($empleado['departamento'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha Ingreso</label>
                            <p><?php echo $empleado['fecha_ingreso'] ? date('d/m/Y', strtotime($empleado['fecha_ingreso'])) : 'N/A'; ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Salario Base</label>
                            <p>$<?php echo number_format($empleado['salario_base'] ?: 0, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contacto -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-phone"></i> Información de Contacto
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Correo</label>
                            <p>
                                <?php if ($empleado['correo']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($empleado['correo']); ?>">
                                        <?php echo htmlspecialchars($empleado['correo']); ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Teléfono</label>
                            <p><?php echo htmlspecialchars($empleado['telefono'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Extensión</label>
                            <p><?php echo htmlspecialchars($empleado['telefono_extension'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Dirección</label>
                            <p><?php echo htmlspecialchars($empleado['direccion'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Laboral -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-briefcase"></i> Información Laboral
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Tipo de Contrato</label>
                            <p><?php echo htmlspecialchars($empleado['tipo_contrato'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha Contrato</label>
                            <p><?php echo $empleado['fecha_contrato'] ? date('d/m/Y', strtotime($empleado['fecha_contrato'])) : 'N/A'; ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Supervisor Directo</label>
                            <p><?php echo htmlspecialchars($empleado['nombre_supervisor'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Personal -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <i class="fas fa-id-card"></i> Información Personal
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha Nacimiento</label>
                            <p><?php echo $empleado['fecha_nacimiento'] ? date('d/m/Y', strtotime($empleado['fecha_nacimiento'])) : 'N/A'; ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Género</label>
                            <p><?php echo htmlspecialchars($empleado['genero'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Estado Civil</label>
                            <p><?php echo htmlspecialchars($empleado['estado_civil'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Dependientes</label>
                            <p><?php echo htmlspecialchars($empleado['cantidad_dependientes'] ?? '0'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Bancaria -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-building"></i> Información Bancaria
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Banco</label>
                            <p><?php echo htmlspecialchars($empleado['banco'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Cuenta Bancaria</label>
                            <p><?php echo htmlspecialchars($empleado['cuenta_bancaria'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">CLABE</label>
                            <p><?php echo htmlspecialchars($empleado['clabe'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Identificación -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-passport"></i> Identificación
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Tipo</label>
                            <p><?php echo htmlspecialchars($empleado['tipo_identificacion'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Número</label>
                            <p><?php echo htmlspecialchars($empleado['numero_identificacion'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Seguro Social</label>
                            <p><?php echo htmlspecialchars($empleado['numero_seguro_social'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($empleado['observaciones'])): ?>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-note-sticky"></i> Observaciones
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars($empleado['observaciones']); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <p class="text-muted small">
                <i class="fas fa-calendar"></i> Creado: <?php echo date('d/m/Y H:i', strtotime($empleado['fecha_creacion'])); ?>
                | Actualizado: <?php echo date('d/m/Y H:i', strtotime($empleado['fecha_actualizacion'])); ?>
            </p>
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
    <script>
        function editarEmpleado(id) {
            // Cargar el módulo de empleados y abrir el modal de edición
            window.location.href = '<?php echo BASE_URL; ?>/empleados.php';
            // Después se abre la edición mediante el JS de empleados
        }
    </script>
</body>
</html>
