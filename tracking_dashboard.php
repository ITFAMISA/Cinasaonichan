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

<!-- Estilos específicos para el dashboard de tracking - REDISEÑO PREMIUM CORPORATIVO -->
<style>
    /* Estructura principal de 3 columnas - MEJORADA */
    .tracking-container {
        display: flex;
        height: calc(100vh - 220px);
        overflow: hidden;
        gap: 24px;
        padding: 24px;
    }

    /* Panel lateral izquierdo - Lista de empleados - REDISEÑADO */
    .panel-empleados {
        width: 320px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.15);
        backdrop-filter: blur(10px);
    }

    .panel-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .panel-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s;
    }

    .panel-header:hover::before {
        left: 100%;
    }

    .panel-header h4 {
        margin: 0;
        font-size: 17px;
        display: flex;
        align-items: center;
        letter-spacing: 0.3px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .panel-header h4 i {
        margin-right: 10px;
        font-size: 18px;
    }

    .panel-content {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }

    .panel-search {
        padding: 16px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        background: linear-gradient(to bottom, #f8fafc, #ffffff);
    }

    .panel-search .input-group {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        overflow: hidden;
    }

    /* Panel central - Layout de tracking - REDISEÑADO */
    .panel-layout {
        flex: 1;
        background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%) !important;
        border-radius: 20px;
        box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 8px 32px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 28px !important;
        border: 1px solid rgba(148, 163, 184, 0.15);
    }

    /* Panel lateral derecho - Pedidos y horas - REDISEÑADO */
    .panel-pedidos {
        width: 320px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.15);
        backdrop-filter: blur(10px);
    }

    /* Tabs dentro del panel derecho - REDISEÑADO */
    .tabs-container {
        display: flex;
        border-bottom: 2px solid rgba(226, 232, 240, 0.6);
        background: linear-gradient(to bottom, #f8fafc, #ffffff);
    }

    .tab {
        flex: 1;
        text-align: center;
        padding: 14px 16px;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
        letter-spacing: 0.5px;
        background: transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #64748b;
        position: relative;
    }

    .tab::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%) scaleX(0);
        width: 80%;
        height: 3px;
        background: linear-gradient(90deg, #2563eb, #3b82f6);
        border-radius: 4px 4px 0 0;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .tab:hover {
        color: #2563eb;
        background: linear-gradient(to bottom, transparent, rgba(37, 99, 235, 0.05));
    }

    .tab.active {
        background: linear-gradient(to bottom, transparent, rgba(37, 99, 235, 0.08));
        color: #2563eb;
    }

    .tab.active::after {
        transform: translateX(-50%) scaleX(1);
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

    /* Lista de empleados - REDISEÑADA */
    .empleado-item {
        padding: 14px 18px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        position: relative;
    }

    .empleado-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: linear-gradient(180deg, #2563eb, #3b82f6);
        transform: scaleY(0);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .empleado-item:hover {
        background: linear-gradient(90deg, rgba(37, 99, 235, 0.08), transparent);
        transform: translateX(4px);
    }

    .empleado-item:hover::before {
        transform: scaleY(1);
    }

    .empleado-item.selected {
        background: linear-gradient(90deg, rgba(37, 99, 235, 0.12), rgba(37, 99, 235, 0.05));
    }

    .empleado-item.selected::before {
        transform: scaleY(1);
    }

    .empleado-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 15px;
        margin-right: 12px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .empleado-item:hover .empleado-avatar {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
    }

    .empleado-info {
        flex: 1;
    }

    .empleado-nombre {
        font-weight: 600;
        margin-bottom: 3px;
        color: #1e293b;
        font-size: 13.5px;
    }

    .empleado-puesto {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
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

    /* Filtros de tipo de trabajo - REDISEÑADO */
    .filtros-tipos {
        padding: 16px 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
        border-bottom: 2px solid rgba(226, 232, 240, 0.6);
        box-shadow: inset 0 -1px 3px rgba(0, 0, 0, 0.05);
    }

    .filtro-tipo {
        padding: 6px 12px;
        border-radius: 10px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        font-size: 11.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        cursor: pointer;
        border: 2px solid rgba(148, 163, 184, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #334155;
        letter-spacing: 0.3px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .filtro-tipo:hover {
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
        border-color: rgba(37, 99, 235, 0.4);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .filtro-tipo input[type="checkbox"] {
        margin-right: 8px;
        cursor: pointer;
        width: 16px;
        height: 16px;
    }

    /* Layout central - Grid de 3 columnas - PREMIUM REDESIGN */
    .tracking-areas {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 28px !important;
        overflow: auto !important;
        width: 100% !important;
        height: 100% !important;
        align-content: flex-start !important;
        padding: 0 !important;
    }

    .tracking-area {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%) !important;
        border-radius: 20px !important;
        border: 2px solid rgba(148, 163, 184, 0.2) !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        height: auto !important;
        min-height: 650px !important;
        position: relative !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .tracking-area::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 8px;
        background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
    }

    .tracking-area[data-id="2"]::before {
        background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
    }

    .tracking-area[data-id="3"]::before {
        background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
    }

    .tracking-area:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .area-header {
        padding: 20px 24px;
        font-weight: 700;
        font-size: 17px;
        letter-spacing: 0.5px;
        border-bottom: 2px solid rgba(226, 232, 240, 0.6);
        color: #1e293b;
        background: linear-gradient(to bottom, #f8fafc, #ffffff);
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 8px;
    }

    .area-header i {
        font-size: 20px;
        color: #2563eb;
    }

    .tracking-area[data-id="2"] .area-header i {
        color: #10b981;
    }

    .tracking-area[data-id="3"] .area-header i {
        color: #f59e0b;
    }

    .area-content {
        padding: 20px;
        flex: 1;
        overflow-y: auto;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        align-content: flex-start;
    }

    /* Estación / Máquina - PREMIUM CORPORATE REDESIGN */
    .estacion-item {
        border: 2px solid rgba(148, 163, 184, 0.25) !important;
        border-radius: 16px !important;
        cursor: pointer !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: center !important;
        text-align: center !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.04) !important;
        user-select: none !important;
        height: 220px !important;
        width: 100% !important;
        padding: 0 !important;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%) !important;
        position: relative !important;
        overflow: hidden !important;
        gap: 0 !important;
        margin: 0 !important;
        backdrop-filter: blur(8px) !important;
    }

    .estacion-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 52px;
        background: linear-gradient(135deg, var(--color-estacion, #334155) 0%, color-mix(in srgb, var(--color-estacion, #334155) 80%, white) 100%);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .estacion-item::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--color-estacion, #334155), transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .estacion-item:hover {
        transform: translateY(-8px) scale(1.03);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2), 0 8px 16px rgba(0, 0, 0, 0.12) !important;
        border-color: var(--color-estacion, #334155);
        z-index: 100;
    }

    .estacion-item:hover::after {
        opacity: 1;
    }

    .estacion-nombre {
        font-weight: 800 !important;
        font-size: 15px !important;
        margin: 0 !important;
        line-height: 1.3 !important;
        color: white !important;
        position: relative;
        z-index: 1;
        padding: 16px 12px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        letter-spacing: 0.3px;
    }

    .estacion-tipo {
        font-size: 12.5px !important;
        color: #475569 !important;
        margin: 8px 0 !important;
        font-weight: 600 !important;
        padding: 0 12px;
    }

    .estacion-trabajo {
        font-size: 11.5px !important;
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(59, 130, 246, 0.12) 100%) !important;
        padding: 6px 10px !important;
        border-radius: 8px !important;
        margin: 6px 12px !important;
        max-width: calc(100% - 24px) !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        border: 1.5px solid rgba(37, 99, 235, 0.2) !important;
        color: #1e40af !important;
        font-weight: 600 !important;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
    }

    .estacion-pedido {
        font-size: 12px !important;
        color: #2563eb !important;
        margin: 4px 0 !important;
        font-weight: 700 !important;
        padding: 0 12px;
    }

    .estacion-estado-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 9px;
        font-weight: 700;
        margin: 4px 0;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        text-transform: uppercase;
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
        padding: 16px 12px;
        background: linear-gradient(135deg, var(--color-estacion, #334155) 0%, color-mix(in srgb, var(--color-estacion, #334155) 80%, white) 100%);
        color: white;
        font-weight: 800;
        font-size: 15px;
        text-align: center;
        border: none;
        min-height: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        letter-spacing: 0.3px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .turnos-container {
        display: flex;
        flex-direction: column;
        height: calc(220px - 52px);
        width: 100%;
    }

    .turno-section {
        flex: 1;
        border-bottom: 1.5px solid rgba(226, 232, 240, 0.6);
        display: flex;
        flex-direction: column;
        min-height: 58px;
        position: relative;
        transition: background 0.2s;
    }

    .turno-section:last-child {
        border-bottom: none;
    }

    .turno-section:hover {
        background: linear-gradient(to right, rgba(37, 99, 235, 0.02), transparent);
    }

    .turno-header {
        padding: 6px 8px;
        background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
        border-bottom: 1.5px solid rgba(226, 232, 240, 0.6);
        font-weight: 700;
        font-size: 10.5px;
        color: #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 28px;
        letter-spacing: 0.3px;
    }

    .turno-tiempo {
        font-size: 9.5px;
        color: #64748b;
        font-weight: 600;
    }

    .turno-content {
        flex: 1;
        padding: 6px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 4px;
        droppable-area: true;
        background: linear-gradient(to bottom, transparent, rgba(248, 250, 252, 0.3));
    }

    .turno-content.droppable {
        border: none !important;
    }

    .turno-content.drop-hover {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(59, 130, 246, 0.12) 100%) !important;
        border: 2px dashed #2563eb !important;
        box-shadow: inset 0 0 12px rgba(37, 99, 235, 0.15);
    }

    .asignacion-turno-card {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border: 1.5px solid rgba(37, 99, 235, 0.25);
        border-left: 4px solid #2563eb;
        border-radius: 8px;
        padding: 6px 8px;
        cursor: move;
        font-size: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        gap: 3px;
        position: relative;
        overflow: hidden;
    }

    .asignacion-turno-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, transparent 50%, rgba(37, 99, 235, 0.1) 50%);
        border-radius: 0 0 0 24px;
    }

    .asignacion-turno-card:hover {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15), 0 3px 6px rgba(0, 0, 0, 0.08);
        border-left-color: #1d4ed8;
        transform: translateX(2px) translateY(-1px);
        background: linear-gradient(145deg, #ffffff 0%, #eff6ff 100%);
    }

    .asignacion-turno-empleado {
        font-weight: 700;
        color: #1e293b;
        font-size: 10.5px;
        line-height: 1.3;
        letter-spacing: 0.2px;
    }

    .asignacion-turno-pedido {
        color: #64748b;
        font-size: 9.5px;
        line-height: 1.2;
        font-weight: 500;
    }

    .asignacion-turno-tipo {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 6px;
        color: white;
        font-size: 8.5px;
        font-weight: 700;
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        width: fit-content;
        letter-spacing: 0.3px;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
        text-transform: uppercase;
    }

    .asignacion-turno-cantidad {
        font-size: 9.5px;
        color: #64748b;
        line-height: 1.2;
        font-weight: 600;
    }

    /* Botón configurar turnos - REDISEÑADO */
    .btn-configurar-turnos {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 1000;
        padding: 14px 24px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 14px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4), 0 4px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.3px;
    }

    .btn-configurar-turnos:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 12px 32px rgba(245, 158, 11, 0.5), 0 6px 12px rgba(0, 0, 0, 0.2);
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    }

    .btn-configurar-turnos:active {
        transform: translateY(-2px) scale(1.02);
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