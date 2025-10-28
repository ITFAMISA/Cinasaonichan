// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    cargarDetallePedido();
});

// ==================== CARGAR DATOS ====================
async function cargarDetallePedido() {
    try {
        const response = await fetch(`${BASE_URL}/app/controllers/tracking_piezas_detalle_controller.php?pedido=${encodeURIComponent(NUMERO_PEDIDO)}`);
        const data = await response.json();

        if (!data.exito) {
            mostrarError('Error al cargar el pedido: ' + data.error);
            return;
        }

        mostrarDetalle(data.pedido, data.items);

    } catch (error) {
        mostrarError('Error de conexión: ' + error.message);
    }
}

// ==================== MOSTRAR DETALLE ====================
function mostrarDetalle(pedido, items) {
    const contenedor = document.getElementById('contenedor_detalle');
    const porcentaje = parseFloat(pedido.porcentaje_aprobacion) || 0;

    const html = `
        <!-- Encabezado con información rápida -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-file-invoice me-2"></i>${escapeHtml(pedido.numero_pedido)}
                        </h4>
                        <small>${escapeHtml(pedido.cliente_nombre)}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark fs-6">${porcentaje}% Aprobación General</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Columna izquierda: Información del pedido -->
            <div class="col-lg-3 mb-4">
                <!-- Card: Información del Cliente -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-info text-white border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-building me-2"></i>Información
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Contacto</small>
                            <strong>${escapeHtml(pedido.contacto_principal || 'N/A')}</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Teléfono</small>
                            <strong>${escapeHtml(pedido.telefono || 'N/A')}</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Correo</small>
                            <strong>${escapeHtml(pedido.correo || 'N/A')}</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Fecha Creación</small>
                            <strong>${formatearFecha(pedido.fecha_creacion)}</strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Fecha Entrega</small>
                            <strong>${formatearFecha(pedido.fecha_entrega)}</strong>
                        </div>
                    </div>
                </div>

                <!-- Card: Resumen General de Piezas -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-chart-bar me-2"></i>Resumen General
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <h3>${pedido.total_piezas}</h3>
                            <small class="text-muted">Total Piezas</small>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Por Inspeccionar:</span>
                            <strong>${pedido.piezas_por_inspeccionar}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Liberadas:</span>
                            <strong class="text-success">${pedido.piezas_liberadas}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Rechazadas:</span>
                            <strong class="text-danger">${pedido.piezas_rechazadas}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Reinspección:</span>
                            <strong class="text-info">${pedido.piezas_reinspeccion}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: Items agrupados -->
            <div class="col-lg-9 mb-4">
                ${generarCardsItems(items)}
            </div>
        </div>
    `;

    contenedor.innerHTML = html;
}

// ==================== GENERAR CARDS DE ITEMS ====================
function generarCardsItems(items) {
    if (items.length === 0) {
        return `
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>No hay items para este pedido
            </div>
        `;
    }

    return items.map(item => {
        const porcentajeProduccion = item.qty_solicitada > 0
            ? (item.prod_total / item.qty_solicitada) * 100
            : 0;

        const colorBarra = porcentajeProduccion >= 100 ? 'bg-success' :
                          porcentajeProduccion >= 75 ? 'bg-info' :
                          porcentajeProduccion >= 50 ? 'bg-warning' : 'bg-danger';

        return `
            <div class="card shadow-sm border-0 mb-4">
                <!-- Header del Item con información de producción -->
                <div class="card-header bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-1">
                                <i class="fas fa-cube me-2"></i>
                                <span class="badge bg-light text-dark me-2">${escapeHtml(item.item_code)}</span>
                            </h5>
                            <small>${escapeHtml(item.descripcion || 'Sin descripción')}</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="mb-1">
                                <span class="badge bg-light text-dark me-1">
                                    ${item.porcentaje_aprobacion}% Aprobación
                                </span>
                                <span class="badge bg-info">
                                    ${item.total_piezas} piezas
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Producción -->
                <div class="card-body bg-light border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3 text-center">
                            <small class="text-muted d-block mb-1">Solicitado</small>
                            <h5 class="mb-0">${parseFloat(item.qty_solicitada).toFixed(2)}</h5>
                            <small class="text-muted">${escapeHtml(item.unidad_medida || 'un')}</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <small class="text-muted d-block mb-1">Producido</small>
                            <h5 class="mb-0 text-success">${parseFloat(item.prod_total).toFixed(2)}</h5>
                            <small class="text-muted">${escapeHtml(item.unidad_medida || 'un')}</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <small class="text-muted d-block mb-1">Pendiente</small>
                            <h5 class="mb-0 ${item.qty_pendiente > 0 ? 'text-warning' : 'text-success'}">
                                ${parseFloat(item.qty_pendiente).toFixed(2)}
                            </h5>
                            <small class="text-muted">${escapeHtml(item.unidad_medida || 'un')}</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <small class="text-muted d-block mb-1">Progreso</small>
                            <h5 class="mb-0">${porcentajeProduccion.toFixed(1)}%</h5>
                            <div class="progress mt-1" style="height: 8px;">
                                <div class="progress-bar ${colorBarra}" style="width: ${Math.min(porcentajeProduccion, 100)}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Mini resumen de calidad -->
                    <div class="row g-2 mt-3">
                        <div class="col-3 text-center">
                            <small class="text-muted d-block">Por Inspeccionar</small>
                            <strong>${item.piezas_por_inspeccionar}</strong>
                        </div>
                        <div class="col-3 text-center">
                            <small class="text-success d-block">Liberadas</small>
                            <strong class="text-success">${item.piezas_liberadas}</strong>
                        </div>
                        <div class="col-3 text-center">
                            <small class="text-danger d-block">Rechazadas</small>
                            <strong class="text-danger">${item.piezas_rechazadas}</strong>
                        </div>
                        <div class="col-3 text-center">
                            <small class="text-info d-block">Reinspección</small>
                            <strong class="text-info">${item.piezas_reinspeccion}</strong>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Piezas del Item -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Supervisor</th>
                                    <th>Fecha Producción</th>
                                    <th>Estatus</th>
                                    <th>Inspector</th>
                                    <th>Fecha Inspección</th>
                                    <th>Defectos</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${generarFilasPiezas(item.piezas)}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function generarFilasPiezas(piezas) {
    if (piezas.length === 0) {
        return `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    No hay piezas para este item
                </td>
            </tr>
        `;
    }

    return piezas.map(pieza => {
        let defectosHTML = '-';
        if (pieza.defectos && pieza.defectos.length > 0) {
            defectosHTML = pieza.defectos.map(d =>
                `<span class="badge bg-danger me-1"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(d.nombre)} (${d.cantidad})</span>`
            ).join(' ');
        }

        return `
            <tr>
                <td><strong>${escapeHtml(pieza.folio_pieza)}</strong></td>
                <td>${escapeHtml(pieza.supervisor_produccion || 'N/A')}</td>
                <td>${formatearFecha(pieza.fecha_produccion)}</td>
                <td>${generarBadgeEstatus(pieza.estatus)}</td>
                <td>${escapeHtml(pieza.inspector_calidad || '-')}</td>
                <td>${pieza.fecha_inspeccion ? formatearFechaHora(pieza.fecha_inspeccion) : '-'}</td>
                <td>${defectosHTML}</td>
                <td><small>${escapeHtml(pieza.observaciones_inspeccion || '-')}</small></td>
            </tr>
        `;
    }).join('');
}

function generarBadgeEstatus(estatus) {
    const badges = {
        'por_inspeccionar': '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Por Inspeccionar</span>',
        'liberada': '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Liberada</span>',
        'rechazada': '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rechazada</span>',
        'pendiente_reinspeccion': '<span class="badge bg-info"><i class="fas fa-redo"></i> Reinspección</span>'
    };
    return badges[estatus] || estatus;
}

// ==================== UTILIDADES ====================
function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function formatearFechaHora(fecha) {
    if (!fecha) return 'N/A';
    const date = new Date(fecha);
    return date.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function mostrarError(mensaje) {
    const contenedor = document.getElementById('contenedor_detalle');
    contenedor.innerHTML = `
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>${escapeHtml(mensaje)}
        </div>
        <div class="text-center mt-3">
            <a href="${BASE_URL}/tracking_piezas.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver al Índice
            </a>
        </div>
    `;
}
