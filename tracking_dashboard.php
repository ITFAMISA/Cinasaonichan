<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/empleados_model.php';

// Verificar si las tablas de tracking existen
try {
    $verificacion = $pdo->query("SHOW TABLES LIKE 'tracking_asignaciones'")->fetch();
    if (empty($verificacion)) {
        // Si la tabla no existe, mostrar un mensaje para ejecutar el script SQL
        $mensajeInstalacion = true;
    } else {
        $mensajeInstalacion = false;
    }
} catch (Exception $e) {
    $mensajeInstalacion = true;
}

$pageTitle = 'Tracking de Empleados y Pedidos';

// Obtener modelo de empleados para listar empleados disponibles
$empleadosModel = new EmpleadosModel($pdo);
$empleadosActivos = [];
$procesosActivos = [];
$turnosActivos = [];

// Solo cargar empleados si ya existe la estructura de tracking
if (!$mensajeInstalacion) {
    try {
        $empleadosActivos = $empleadosModel->listarEmpleados(['estatus_empleado' => 'activo'], 'apellido', 'ASC', 100, 0);

        // Obtener procesos reales de la tabla procesos
        $sqlProcesos = "SELECT id, nombre FROM procesos WHERE estatus = 'activo' ORDER BY id";
        $stmtProcesos = $pdo->prepare($sqlProcesos);
        $stmtProcesos->execute();
        $procesosActivos = $stmtProcesos->fetchAll(PDO::FETCH_ASSOC);

        // Obtener turnos activos
        try {
            $sqlTurnos = "SELECT id, nombre, hora_inicio, hora_fin, orden FROM turnos WHERE activo = 1 ORDER BY orden ASC";
            $stmtTurnos = $pdo->prepare($sqlTurnos);
            $stmtTurnos->execute();
            $turnosActivos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $turnosActivos = [];
        }
    } catch (Exception $e) {
        // Manejar error silenciosamente
    }
}

include __DIR__ . '/app/views/header.php';
?>

<!-- Estilos específicos para el dashboard de tracking -->
<style>
    /* Estructura principal de 3 columnas */
    .tracking-container {
        display: flex;
        height: calc(100vh - 220px);
        overflow: hidden;
        gap: 15px;
        padding: 15px;
    }

    /* Panel lateral izquierdo - Lista de empleados */
    .panel-empleados {
        width: 280px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .panel-header {
        padding: 15px;
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .panel-header h4 {
        margin: 0;
        font-size: 16px;
        display: flex;
        align-items: center;
    }

    .panel-header h4 i {
        margin-right: 8px;
    }

    .panel-content {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }

    .panel-search {
        padding: 10px;
        border-bottom: 1px solid #eaeaea;
    }

    .panel-search .input-group {
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    /* Panel central - Layout de tracking */
    .panel-layout {
        flex: 1;
        background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%) !important;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 20px !important;
    }

    /* Panel lateral derecho - Pedidos y horas */
    .panel-pedidos {
        width: 280px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Tabs dentro del panel derecho */
    .tabs-container {
        display: flex;
        border-bottom: 1px solid #eaeaea;
    }

    .tab {
        flex: 1;
        text-align: center;
        padding: 10px;
        cursor: pointer;
        font-weight: bold;
        background-color: #f8f9fa;
        transition: all 0.2s;
    }

    .tab.active {
        background-color: #fff;
        border-bottom: 3px solid #3498db;
        color: #3498db;
    }

    .tab-content {
        display: none;
        padding: 0;
        overflow-y: auto;
        flex: 1;
    }

    .tab-content.active {
        display: block;
    }

    /* Lista de empleados */
    .empleado-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eaeaea;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
    }

    .empleado-item:hover {
        background-color: #f8f9fa;
    }

    .empleado-item.selected {
        background-color: #e1f0fa;
    }

    .empleado-avatar {
        width: 32px;
        height: 32px;
        background-color: #3498db;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 10px;
    }

    .empleado-info {
        flex: 1;
    }

    .empleado-nombre {
        font-weight: 500;
        margin-bottom: 2px;
    }

    .empleado-puesto {
        font-size: 11px;
        color: #777;
    }

    .empleado-acciones {
        display: none;
    }

    .empleado-item:hover .empleado-acciones {
        display: block;
    }

    /* Estilos para la lista de pedidos */
    .pedido-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eaeaea;
        cursor: pointer;
        transition: all 0.2s;
    }

    .pedido-item:hover {
        background-color: #f8f9fa;
    }

    .pedido-numero {
        font-weight: 500;
        margin-bottom: 2px;
    }

    .pedido-cliente {
        font-size: 11px;
        color: #777;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Filtros de tipo de trabajo */
    .filtros-tipos {
        padding: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eaeaea;
    }

    .filtro-tipo {
        padding: 4px 8px;
        border-radius: 4px;
        background-color: #e1e1e1;
        font-size: 11px;
        display: flex;
        align-items: center;
        cursor: pointer;
    }

    .filtro-tipo input[type="checkbox"] {
        margin-right: 5px;
    }

    /* Layout central - Grid de 3 columnas como dashboard_taller */
    .tracking-areas {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 20px !important;
        overflow: auto !important;
        width: 100% !important;
        height: 100% !important;
        align-content: flex-start !important;
        padding: 0 !important;
    }

    .tracking-area {
        background-color: white !important;
        border-radius: 12px !important;
        border: 4px solid #2c3e50 !important;
        border-top: 8px solid #3498db !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
        height: auto !important;
        min-height: 650px !important;
    }

    .tracking-area[data-id="2"] {
        border-top-color: #2ecc71 !important;
    }

    .tracking-area[data-id="3"] {
        border-top-color: #f39c12 !important;
    }

    .area-header {
        padding: 15px;
        font-weight: bold;
        font-size: 16px;
        border-bottom: 2px solid #ddd;
        color: #2c3e50;
    }

    .area-content {
        padding: 15px;
        flex: 1;
        overflow-y: auto;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        align-content: flex-start;
    }

    /* Estación / Máquina */
    .estacion-item {
        border: 2px solid #bdc3c7 !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: center !important;
        text-align: center !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.07) !important;
        user-select: none !important;
        height: 220px !important;
        width: 100% !important;
        padding: 12px !important;
        background: white !important;
        position: relative !important;
        overflow: hidden !important;
        gap: 5px !important;
        margin: 0 !important;
    }

    .estacion-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: var(--color-estacion, #34495e);
    }

    .estacion-item:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 12px 24px rgba(0,0,0,0.2);
        border-color: #34495e;
        z-index: 100;
    }

    .estacion-nombre {
        font-weight: 700 !important;
        font-size: 14px !important;
        margin: 0 !important;
        line-height: 1.2 !important;
        color: #2c3e50 !important;
    }

    .estacion-tipo {
        font-size: 12px !important;
        color: #7f8c8d !important;
        margin: 0 !important;
        font-weight: 500 !important;
    }

    .estacion-trabajo {
        font-size: 11px !important;
        background: rgba(52, 73, 94, 0.08) !important;
        padding: 4px 7px !important;
        border-radius: 3px !important;
        margin: 0 !important;
        max-width: 95% !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        border: 1px solid rgba(52, 73, 94, 0.15) !important;
    }

    .estacion-pedido {
        font-size: 11px !important;
        color: #2980b9 !important;
        margin: 0 !important;
        font-weight: 700 !important;
    }

    .estacion-estado-badge {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 8px;
        font-weight: 700;
        margin: 0;
        letter-spacing: 0.2px;
    }

    /* Tarjeta de asignación */
    .asignacion-card {
        background-color: #f8f9fa;
        border: 2px solid #bdc3c7 !important;
        border-radius: 8px;
        padding: 12px;
        cursor: move;
        box-shadow: 0 2px 6px rgba(0,0,0,0.07);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        font-weight: 600;
        font-size: 12px;
        user-select: none;
        min-height: 120px;
        gap: 5px;
    }

    .asignacion-card:hover {
        box-shadow: 0 12px 24px rgba(0,0,0,0.2);
        transform: translateY(-6px) scale(1.02);
        border-color: #34495e;
        z-index: 100;
    }

    .asignacion-header {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 5px;
        width: 100%;
        flex-wrap: wrap;
        gap: 5px;
    }

    .asignacion-nombre {
        font-weight: 700;
        font-size: 13px;
        color: #2c3e50;
    }

    .asignacion-tipo {
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 3px;
        color: white;
        background-color: #3498db;
        font-weight: 600;
    }

    .asignacion-detalles {
        font-size: 11px;
        color: #777;
        width: 100%;
    }

    .asignacion-progress {
        height: 5px;
        background-color: #eaeaea;
        border-radius: 3px;
        margin-top: 5px;
        overflow: hidden;
        width: 100%;
    }

    .asignacion-progress-bar {
        height: 100%;
        background-color: #2ecc71;
        width: 0%;
    }

    /* Estilos para el mensaje de instalación */
    .mensaje-instalacion {
        padding: 20px;
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    /* Estilos para elementos arrastrables */
    .draggable {
        cursor: grab;
    }

    .draggable:active {
        cursor: grabbing;
    }

    .droppable {
        border: 2px dashed #3498db;
        background-color: rgba(52, 152, 219, 0.1);
    }

    .drop-hover {
        background-color: rgba(52, 152, 219, 0.2);
        border: 2px dashed #2980b9;
    }

    /* Estilos para estaciones en mantenimiento */
    .estacion-item.en-mantenimiento {
        background-color: #ffebee !important;
        border-color: #d32f2f !important;
        animation: parpadeante 0.6s infinite;
    }

    @keyframes parpadeante {
        0%, 49% {
            background-color: #ffebee;
        }
        50%, 100% {
            background-color: #d32f2f;
            color: white;
        }
    }

    /* Estilos para estructura de turnos en estaciones */
    /* Estos estilos override la clase estacion-item para usarla con turnos */
    .estacion-item {
        height: 220px !important;
        width: 100% !important;
        padding: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        border: 2px solid #bdc3c7 !important;
        margin: 0 !important;
        gap: 0 !important;
    }

    .estacion-header-turno {
        padding: 8px;
        background-color: #34495e;
        color: white;
        font-weight: bold;
        font-size: 13px;
        text-align: center;
        border-bottom: 1px solid #2c3e50;
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .turnos-container {
        display: flex;
        flex-direction: column;
        height: calc(220px - 40px);
        width: 100%;
    }

    .turno-section {
        flex: 1;
        border-bottom: 1px solid #bdc3c7;
        display: flex;
        flex-direction: column;
        min-height: 58px;
        position: relative;
    }

    .turno-section:last-child {
        border-bottom: none;
    }

    .turno-header {
        padding: 5px 6px;
        background-color: #ecf0f1;
        border-bottom: 1px solid #bdc3c7;
        font-weight: 600;
        font-size: 10px;
        color: #2c3e50;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 26px;
    }

    .turno-tiempo {
        font-size: 9px;
        color: #7f8c8d;
    }

    .turno-content {
        flex: 1;
        padding: 4px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 3px;
        droppable-area: true;
    }

    .turno-content.droppable {
        border: none !important;
    }

    .turno-content.drop-hover {
        background-color: rgba(52, 152, 219, 0.15) !important;
        border: 1px dashed #3498db !important;
    }

    .asignacion-turno-card {
        background-color: #fff;
        border: 1px solid #3498db;
        border-left: 4px solid #3498db;
        border-radius: 4px;
        padding: 4px;
        cursor: move;
        font-size: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .asignacion-turno-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        border-left-color: #2980b9;
    }

    .asignacion-turno-empleado {
        font-weight: 600;
        color: #2c3e50;
        font-size: 10px;
        line-height: 1.2;
    }

    .asignacion-turno-pedido {
        color: #7f8c8d;
        font-size: 9px;
        line-height: 1.1;
    }

    .asignacion-turno-tipo {
        display: inline-block;
        padding: 1px 3px;
        border-radius: 2px;
        color: white;
        font-size: 8px;
        font-weight: 600;
        background-color: #3498db;
        width: fit-content;
    }

    .asignacion-turno-cantidad {
        font-size: 9px;
        color: #7f8c8d;
        line-height: 1.1;
    }

    /* Botón configurar turnos */
    .btn-configurar-turnos {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }
</style>

<!-- Mensaje de Instalación (si es necesario) -->
<?php if ($mensajeInstalacion): ?>
<div class="mensaje-instalacion">
    <h4><i class="fas fa-exclamation-triangle me-2"></i> Configuración Necesaria</h4>
    <p>El módulo de tracking requiere configuración adicional. Por favor, ejecute el siguiente script SQL para crear las tablas necesarias:</p>
    <code>database/tracking_sistema.sql</code>
    <p class="mt-2">Una vez ejecutado, recargue esta página.</p>
</div>
<?php endif; ?>

<!-- Filtros de Procesos (dinámicos) -->
<div class="filtros-tipos">
    <?php foreach ($procesosActivos as $proceso): ?>
    <div class="filtro-tipo">
        <input type="checkbox" id="filtro-proceso-<?php echo $proceso['id']; ?>"
               data-proceso-id="<?php echo $proceso['id']; ?>" checked>
        <?php echo htmlspecialchars(strtoupper($proceso['nombre'])); ?> - 0%
    </div>
    <?php endforeach; ?>
    <?php if (empty($procesosActivos)): ?>
    <p class="text-muted">No hay procesos disponibles</p>
    <?php endif; ?>
</div>

<!-- Contenedor principal de 3 columnas -->
<div class="tracking-container">
    <!-- Panel izquierdo: Lista de empleados -->
    <div class="panel-empleados">
        <div class="panel-header">
            <h4><i class="fas fa-users"></i> LISTA DE EMPLEADOS</h4>
            <span class="badge bg-dark"><?php echo count($empleadosActivos); ?></span>
        </div>
        <div class="panel-search">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Buscar..." id="buscarEmpleados">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="listaEmpleados">
            <!-- Los empleados se cargarán aquí dinámicamente -->
            <?php foreach ($empleadosActivos as $empleado): ?>
                <div class="empleado-item draggable" data-id="<?php echo $empleado['id']; ?>">
                    <div class="empleado-avatar">
                        <?php echo strtoupper(substr($empleado['nombre'], 0, 1)); ?>
                    </div>
                    <div class="empleado-info">
                        <div class="empleado-nombre"><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></div>
                        <div class="empleado-puesto"><?php echo htmlspecialchars($empleado['puesto']); ?></div>
                    </div>
                    <div class="empleado-acciones">
                        <button class="btn btn-sm btn-light">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Botón para agregar nuevo empleado -->
            <div class="p-3 text-center">
                <button class="btn btn-sm btn-outline-primary" id="btnNuevoEmpleado">
                    <i class="fas fa-plus"></i> Nuevo Empleado
                </button>
            </div>
        </div>
    </div>

    <!-- Panel central: Layout de tracking -->
    <div class="panel-layout">
        <div class="tracking-areas" id="areasTracking">
            <!-- Las áreas se cargarán dinámicamente -->
            <div class="tracking-area" data-id="1">
                <div class="area-header">
                    <i class="fas fa-industry me-2"></i> Nave 1
                </div>
                <div class="area-content droppable" data-area="1" id="estaciones-nave-1">
                    <!-- Las estaciones se cargarán dinámicamente -->
                </div>
            </div>

            <div class="tracking-area" data-id="2">
                <div class="area-header">
                    <i class="fas fa-industry me-2"></i> Nave 2
                </div>
                <div class="area-content droppable" data-area="2" id="estaciones-nave-2">
                    <!-- Las estaciones se cargarán dinámicamente -->
                </div>
            </div>

            <div class="tracking-area" data-id="3">
                <div class="area-header">
                    <i class="fas fa-industry me-2"></i> Nave 3
                </div>
                <div class="area-content droppable" data-area="3" id="estaciones-nave-3">
                    <!-- Las estaciones se cargarán dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Panel derecho: Pedidos y horas -->
    <div class="panel-pedidos">
        <div class="panel-header">
            <h4><i class="fas fa-clipboard-list"></i> SEGUIMIENTO</h4>
        </div>
        <div class="tabs-container">
            <div class="tab active" data-tab="hrs">HORAS</div>
            <div class="tab" data-tab="jobs">PEDIDOS</div>
        </div>
        <div class="panel-search">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Buscar..." id="buscarPedidos">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="tab-content active" id="tab-hrs">
            <!-- Contenido de la pestaña HORAS -->
            <div class="p-3">
                <h6 class="mb-3">Registrar Horas de Trabajo</h6>
                <form id="formRegistroHoras">
                    <div class="mb-3">
                        <label for="empleadoSeleccionado" class="form-label">Empleado</label>
                        <select class="form-select form-select-sm" id="empleadoSeleccionado">
                            <option value="">Seleccione un empleado...</option>
                            <?php foreach ($empleadosActivos as $empleado): ?>
                                <option value="<?php echo $empleado['id']; ?>"><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pedidoSeleccionado" class="form-label">Pedido</label>
                        <select class="form-select form-select-sm" id="pedidoSeleccionado">
                            <option value="">Seleccione un pedido...</option>
                            <!-- Los pedidos se cargarán dinámicamente -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tipoTrabajoSeleccionado" class="form-label">Tipo de Trabajo</label>
                        <select class="form-select form-select-sm" id="tipoTrabajoSeleccionado">
                            <option value="">Seleccione un tipo...</option>
                            <option value="1">ARMADO</option>
                            <option value="2">CORTE</option>
                            <option value="3">CORTE SIERRA CINTA</option>
                            <option value="4">DETALLADO</option>
                            <option value="5">CONFORMADO</option>
                            <option value="6">DOBLEZ</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="horaInicio" class="form-label">Hora Inicio</label>
                            <input type="time" class="form-control form-control-sm" id="horaInicio">
                        </div>
                        <div class="col">
                            <label for="horaFin" class="form-label">Hora Fin</label>
                            <input type="time" class="form-control form-control-sm" id="horaFin">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="cantidadProcesada" class="form-label">Cantidad Procesada</label>
                        <input type="number" class="form-control form-control-sm" id="cantidadProcesada" min="0" step="0.01">
                    </div>
                    <div class="d-grid">
                        <button type="button" class="btn btn-primary btn-sm" id="btnRegistrarHoras">
                            <i class="fas fa-save me-1"></i> Registrar Horas
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="tab-content" id="tab-jobs">
            <!-- Contenido de la pestaña PEDIDOS - Los pedidos se cargarán dinámicamente -->
            <div class="p-3 text-center text-muted" id="sin-pedidos">
                <i class="fas fa-info-circle me-2"></i> Los pedidos se cargarán automáticamente
            </div>
        </div>
    </div>
</div>

<!-- Botón de configuración de turnos -->
<button class="btn btn-warning btn-configurar-turnos" id="btnConfigurarTurnos">
    <i class="fas fa-clock me-2"></i> Configurar Turnos
</button>

<!-- Modal para crear asignación -->
<div class="modal fade" id="modalAsignacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Empleado y Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAsignacion">
                    <input type="hidden" id="asignEstacionId">
                    <input type="hidden" id="asignTurnoId">

                    <div class="mb-3">
                        <label for="asignEmpleado" class="form-label">Empleado</label>
                        <select class="form-select form-select-sm" id="asignEmpleado">
                            <option value="">Seleccione un empleado...</option>
                            <?php foreach ($empleadosActivos as $empleado): ?>
                                <option value="<?php echo $empleado['id']; ?>">
                                    <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="asignPedido" class="form-label">Pedido</label>
                        <select class="form-select form-select-sm" id="asignPedido">
                            <option value="">Seleccione un pedido...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="asignTipoTrabajo" class="form-label">Tipo de Trabajo</label>
                        <select class="form-select form-select-sm" id="asignTipoTrabajo">
                            <option value="">Seleccione un tipo...</option>
                            <option value="1">ARMADO</option>
                            <option value="2">CORTE</option>
                            <option value="3">CORTE SIERRA CINTA</option>
                            <option value="4">DETALLADO</option>
                            <option value="5">CONFORMADO</option>
                            <option value="6">DOBLEZ</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="asignCantidad" class="form-label">Cantidad Total</label>
                        <input type="number" class="form-control form-control-sm" id="asignCantidad" min="0" step="0.01">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarAsignacion">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Variables globales para turnos
    let turnosData = <?php echo json_encode($turnosActivos); ?>;
    let empleadosData = <?php echo json_encode($empleadosActivos); ?>;
    let pedidosListaData = [];
</script>
<script src="<?php echo BASE_PATH; ?>/app/assets/tracking_dashboard_turnos.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_PATH; ?>/app/assets/mantenimiento_estaciones.js?v=<?php echo time(); ?>"></script>

</body>
</html>