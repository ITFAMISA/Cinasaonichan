<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';

$pageTitle = 'Procesamiento de PDFs - Órdenes de Compra';

include __DIR__ . '/app/views/header.php';
?>

<!-- Logo de Fondo Transparente -->
<div class="logo-background" style="position: fixed; top: 50%; left: 50%; width: 1200px; height: 1200px; margin-left: -600px; margin-top: -600px; background-image: url('<?php echo BASE_PATH; ?>/app/assets/img/logo.png'); background-size: 60%; background-repeat: no-repeat; background-position: center; background-attachment: fixed; opacity: 0.25; pointer-events: none; z-index: -1;"></div>

<!-- Upload Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0 flex items-center">
            <i class="fas fa-file-pdf text-red-600 mr-2"></i>
            Subir Orden de Compra (PDF)
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <form id="formUploadPdf" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="pdfFile" class="form-label">Seleccionar archivo PDF</label>
                        <input type="file" class="form-control" id="pdfFile" name="pdf_file" accept=".pdf" required>
                        <div class="form-text">Tamaño máximo: 10MB. Solo archivos PDF.</div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnProcesar">
                        <i class="fas fa-upload mr-2"></i>
                        Procesar PDF
                    </button>
                </form>
            </div>
            <div class="col-md-4">
                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Información</h6>
                    <p class="small mb-0">
                        Este módulo extrae información de órdenes de compra en formato PDF.
                        Sube tu PDF para extraer:
                    </p>
                    <ul class="small mb-0 mt-2">
                        <li>Número de orden</li>
                        <li>Fecha</li>
                        <li>Códigos de productos</li>
                        <li>Cantidades y precios</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resultados de Extracción -->
<div class="card mb-4" id="cardResultados" style="display: none;">
    <div class="card-header">
        <h5 class="mb-0 flex items-center">
            <i class="fas fa-file-alt text-blue-600 mr-2"></i>
            Texto Extraído
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Texto Original</h6>
                <div id="textoExtraido" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; font-size: 0.85rem;"></div>
            </div>
            <div class="col-md-6">
                <h6>Datos Detectados</h6>
                <div id="datosDetectados"></div>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Mapeo de Productos -->
<div class="card mb-4" id="cardMapeoProductos" style="display: none;">
    <div class="card-header">
        <h5 class="mb-0 flex items-center">
            <i class="fas fa-link text-green-600 mr-2"></i>
            Mapeo de Productos
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Revisa y mapea manualmente los productos detectados con los de tu catálogo
        </div>
        <div id="tablaMapeoProductos"></div>
    </div>
</div>

<!-- Historial de PDFs Procesados -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 flex items-center">
            <i class="fas fa-history text-purple-600 mr-2"></i>
            Historial de PDFs Procesados
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Archivo</th>
                        <th>Fecha Proceso</th>
                        <th>Número Orden</th>
                        <th>Productos Detectados</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaHistorial">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
                <?php echo date('Y'); ?> Procesamiento de PDFs - Sistema Empresarial
            </p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_PATH; ?>/app/assets/pdf_ordenes.js?v=<?php echo time(); ?>"></script>
</body>
</html>
