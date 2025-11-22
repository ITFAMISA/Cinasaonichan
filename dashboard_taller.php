<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/auth.php';

// Verificar permisos de acceso al módulo
if (!hasModuleAccess('estaciones')) {
    http_response_code(403);
    die('Acceso denegado. No tiene permisos para acceder a este módulo.');
}

$pageTitle = 'Dashboard del Taller - Visualización de Estaciones';
include __DIR__ . '/app/views/header.php';
?>

<style>
    .taller-canvas {
        background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%) !important;
        user-select: none !important;
        padding: 20px !important;
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 20px !important;
        overflow: auto !important;
        width: 100% !important;
        height: calc(100vh - 185px) !important;
        align-content: flex-start !important;
    }

    .nave-section {
        border: 4px solid #2c3e50 !important;
        border-radius: 16px !important;
        padding: 16px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
        background: white !important;
        display: flex !important;
        flex-direction: column !important;
        height: calc(100vh - 225px) !important;
        width: 100% !important;
        overflow: hidden !important;
        gap: 0 !important;
        margin: 0 !important;
        flex: 0 0 auto !important;
    }

    .nave-section.nave-1 {
        border-top: 8px solid #3498db !important;
        background: linear-gradient(135deg, #f0f7ff 0%, #e0ecf8 100%) !important;
    }

    .nave-section.nave-2 {
        border-top: 8px solid #2ecc71 !important;
        background: linear-gradient(135deg, #f0fff4 0%, #e0f8e8 100%) !important;
    }

    .nave-section.nave-3 {
        border-top: 8px solid #f39c12 !important;
        background: linear-gradient(135deg, #fff9f0 0%, #f8f0e0 100%) !important;
    }

    .nave-titulo {
        font-size: 15px !important;
        font-weight: 700 !important;
        margin-bottom: 12px !important;
        padding-bottom: 8px !important;
        border-bottom: 2px solid #34495e !important;
        color: #2c3e50 !important;
        letter-spacing: 0.3px !important;
    }

    .nave-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px !important;
        overflow-y: auto !important;
        padding-right: 4px !important;
        flex: 1 !important;
        margin: 0 !important;
        width: auto !important;
    }

    .nave-grid::-webkit-scrollbar {
        width: 6px;
    }

    .nave-grid::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.05);
        border-radius: 10px;
    }

    .nave-grid::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
    }

    .nave-grid::-webkit-scrollbar-thumb:hover {
        background: rgba(0,0,0,0.3);
    }

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
        height: 225px !important;
        width: 225px !important;
        position: relative !important;
        overflow: hidden !important;
        background: white !important;
        padding: 12px !important;
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
        background: var(--color-nave, #34495e);
    }

    .estacion-item:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 12px 24px rgba(0,0,0,0.2);
        border-color: #34495e;
        z-index: 100;
    }

    .estacion-item.mantenimiento {
        opacity: 0.9;
    }

    .estacion-item.mantenimiento::before {
        background: #f39c12;
    }

    .estacion-item.inactiva {
        opacity: 0.75;
    }

    .estacion-item.inactiva::before {
        background: #e74c3c;
    }

    .estacion-item.en-progreso::before {
        background: var(--color-nave, #27ae60);
        box-shadow: 0 0 10px rgba(39, 174, 96, 0.5);
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

    .estacion-estado-badge.badge-warning {
        background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(243, 156, 18, 0.3);
    }

    .estacion-estado-badge.badge-danger {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(231, 76, 60, 0.3);
    }

    .panel-controles {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .stat-card {
        background: white;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #2196F3;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-card.en-progreso {
        border-left-color: #4CAF50;
    }

    .stat-card.pendiente {
        border-left-color: #FF9800;
    }

    .stat-card.pausada {
        border-left-color: #F44336;
    }

    .stat-numero {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }

    .stat-titulo {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    .legenda-estatus {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .legenda-item {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
    }

    .legenda-color {
        width: 12px;
        height: 12px;
        border: 1px solid rgba(0,0,0,0.2);
        border-radius: 2px;
    }

    .slider-container {
        margin: 6px 0 0 0;
    }

    .slider-label {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 3px;
    }

    input[type="range"] {
        width: 100%;
        cursor: pointer;
    }

    .modal-trabajo {
        font-size: 12px;
    }

    .modal-trabajo .form-label {
        font-weight: bold;
        font-size: 11px;
    }

    .trabajo-badge {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 8px;
        margin: 0;
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    .trabajo-badge.completada {
        background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(39, 174, 96, 0.3);
    }

    .trabajo-badge.en-progreso {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3);
    }

    .trabajo-badge.pendiente {
        background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(243, 156, 18, 0.3);
    }

    .panel-controles {
        background: white;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .panel-controles h5 {
        font-size: 16px;
        margin-bottom: 0;
    }

    .panel-controles .d-flex {
        gap: 6px;
    }

    .panel-controles .d-flex button,
    .panel-controles .d-flex a {
        padding: 5px 10px;
        font-size: 11px;
    }

    .card {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        min-height: 0 !important;
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .card-body {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        padding: 0 !important;
        background: transparent !important;
    }

    .card-header {
        display: none !important;
    }
</style>

<div class="container-fluid mt-4">
    <!-- Panel de Controles -->
    <div class="panel-controles">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 style="margin: 0;">
                <i class="fas fa-industry"></i> Dashboard del Taller
            </h5>
            <div>
                <button class="btn btn-primary btn-sm" id="btnAutorefresh" title="Auto-refrescar datos">
                    <i class="fas fa-sync"></i> Auto-refrescar
                </button>
                <button class="btn btn-info btn-sm" id="btnRefrescar">
                    <i class="fas fa-redo"></i> Refrescar
                </button>
                <a href="<?php echo BASE_PATH; ?>/estaciones.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- Leyenda y Controles -->
        <div style="margin-top: 8px;">
            <h6 style="margin: 0 0 8px 0; font-size: 12px;">Leyenda de Estados</h6>
            <div class="legenda-estatus" style="gap: 12px;">
                <div class="legenda-item" style="font-size: 11px;">
                    <div class="legenda-color" style="background-color: #4CAF50; width: 14px; height: 14px;"></div>
                    <span>En Progreso</span>
                </div>
                <div class="legenda-item" style="font-size: 11px;">
                    <div class="legenda-color" style="background-color: #2196F3; width: 14px; height: 14px;"></div>
                    <span>Pendiente</span>
                </div>
                <div class="legenda-item" style="font-size: 11px;">
                    <div class="legenda-color" style="background-color: #F44336; width: 14px; height: 14px;"></div>
                    <span>Pausada</span>
                </div>
                <div class="legenda-item" style="font-size: 11px;">
                    <div class="legenda-color" style="background-color: #FF9800; width: 14px; height: 14px;"></div>
                    <span>Mantenimiento</span>
                </div>
                <div class="legenda-item" style="font-size: 11px;">
                    <div class="legenda-color" style="background-color: #CCCCCC; width: 14px; height: 14px;"></div>
                    <span>Inactiva</span>
                </div>
            </div>

            <div class="slider-container" style="margin-top: 8px;">
                <div class="slider-label" style="gap: 8px; margin-bottom: 4px;">
                    <label style="font-size: 11px;">Zoom</label>
                    <span id="zoomValue" style="font-size: 11px;">100%</span>
                </div>
                <input type="range" id="sliderZoom" min="50" max="200" value="100" step="10" style="height: 4px;">
            </div>
        </div>
    </div>

    <!-- Canvas del Taller -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-map"></i> Layout del Taller
            </h6>
        </div>
        <div class="card-body p-0">
            <div id="tallerCanvas" class="taller-canvas"></div>
        </div>
    </div>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_PATH; ?>/app/assets/dashboard_taller.js?v=<?php echo time(); ?>"></script>
</body>
</html>
