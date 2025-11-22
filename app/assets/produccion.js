// Módulo de Tracking de Producción - CINASA
(function() {
    'use strict';

    let paginaActual = 1;
    let ordenActual = 'numero_pedido';
    let direccionActual = 'ASC';

    document.addEventListener('DOMContentLoaded', function() {
        cargarProduccion();

        // Event listeners
        document.getElementById('btnBuscar').addEventListener('click', () => cargarProduccion(1));
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

        const buscarInput = document.getElementById('buscar');
        if (buscarInput) {
            buscarInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    cargarProduccion(1);
                }
            });
        }

        // Evento para el filtro de fecha
        const fechaFiltro = document.getElementById('fechaFiltro');
        if (fechaFiltro) {
            fechaFiltro.addEventListener('change', () => cargarProduccion(1));
        }
    });

    function cargarProduccion(pagina = 1) {
        paginaActual = pagina;

        const fechaFiltro = document.getElementById('fechaFiltro').value;

        const filtros = {
            buscar: document.getElementById('buscar').value,
            estatus: document.getElementById('estatus').value,
            fecha_desde: fechaFiltro ? fechaFiltro : '',
            fecha_hasta: fechaFiltro ? fechaFiltro : '',
            orden: ordenActual,
            direccion: direccionActual,
            pagina: pagina
        };

        const queryString = new URLSearchParams(filtros).toString();

        fetch(`${BASE_URL}/app/controllers/produccion_listar.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarOrdenes(data.data);
                    actualizarPaginacion(data.pagination);
                    actualizarContador(data.pagination.total);
                } else {
                    mostrarError('Error al cargar producción: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar producción');
            });
    }

    function mostrarOrdenes(registros) {
        const container = document.getElementById('ordenesContainer');

        if (registros.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No se encontraron registros de producción</p>
                    </div>
                </div>
            `;
            return;
        }

        // Agrupar registros por número de pedido y obtener pedido_id
        const ordenes = {};
        registros.forEach(registro => {
            const numeroPedido = registro.numero_pedido;
            if (!ordenes[numeroPedido]) {
                ordenes[numeroPedido] = {
                    numero_pedido: numeroPedido,
                    pedido_id: registro.pedido_id,
                    fecha_entrega: registro.fecha_entrega,
                    modificado_en_produccion: registro.modificado_en_produccion,
                    items: []
                };
            }
            ordenes[numeroPedido].items.push(registro);
        });

        // Generar HTML con grid layout
        let gridHtml = '<div class="row g-3">';

        Object.keys(ordenes).forEach(numeroPedido => {
            const orden = ordenes[numeroPedido];
            const tarjetaHtml = crearTarjetaOrdenHTML(orden);
            gridHtml += `<div class="col-lg-6 col-xl-4">${tarjetaHtml}</div>`;
        });

        gridHtml += '</div>';
        container.innerHTML = gridHtml;
    }

    function crearTarjetaOrdenHTML(orden) {
        // Calcular totales de la orden
        let totalSolicitado = 0;
        let totalProducido = 0;
        let totalPendiente = 0;

        orden.items.forEach(item => {
            totalSolicitado += parseFloat(item.qty_solicitada) || 0;
            totalProducido += parseFloat(item.prod_total) || 0;
            totalPendiente += parseFloat(item.qty_pendiente) || 0;
        });

        const porcentajeCompletado = totalSolicitado > 0 ? (totalProducido / totalSolicitado) * 100 : 0;
        const colorProgreso = porcentajeCompletado >= 100 ? 'bg-success' :
                             porcentajeCompletado >= 75 ? 'bg-info' :
                             porcentajeCompletado >= 50 ? 'bg-warning' : 'bg-danger';

        // Formatear fecha de entrega
        const fechaEntrega = orden.fecha_entrega ? new Date(orden.fecha_entrega).toLocaleDateString('es-MX') : 'Sin fecha';

        // Tag de modificado
        const tagModificado = orden.modificado_en_produccion == 1 ?
            '<span class="badge bg-warning text-dark ms-2" title="Pedido modificado en producción"><i class="fas fa-exclamation-triangle me-1"></i>Modificado</span>' : '';

        // Crear HTML de la tarjeta (lista simplificada)
        const itemsResumenHtml = orden.items.map(item => {
            const porcentajeItem = item.qty_solicitada > 0 ? (item.prod_total / item.qty_solicitada) * 100 : 0;
            const colorItem = porcentajeItem >= 100 ? 'bg-success' :
                             porcentajeItem >= 75 ? 'bg-info' :
                             porcentajeItem >= 50 ? 'bg-warning' : 'bg-danger';

            return `
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                    <div class="flex-grow-1 min-w-0">
                        <span class="badge bg-primary me-1" style="font-size: 0.7rem;">${escapeHtml(item.item_code)}</span>
                        <small class="text-truncate d-inline-block" style="max-width: 120px;" title="${escapeHtml(item.descripcion || 'N/A')}">${escapeHtml((item.descripcion || 'N/A').substring(0, 25))}</small>
                    </div>
                    <div class="d-flex align-items-center gap-1 ms-2 flex-shrink-0">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            ${parseFloat(item.prod_total).toFixed(2)}/${parseFloat(item.qty_solicitada).toFixed(2)}
                        </small>
                        <div class="progress" style="width: 40px; height: 14px;">
                            <div class="progress-bar ${colorItem}" style="width: ${Math.min(porcentajeItem, 100)}%"></div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const html = `
            <div class="card shadow-sm border-0 h-100 d-flex flex-column" style="cursor: pointer; transition: all 0.3s ease;"
                 onmouseenter="this.style.boxShadow='0 0.5rem 1rem rgba(0,0,0,0.15), 0 0 2rem rgba(59,130,246,0.2)'; this.style.transform='translateY(-4px)';"
                 onmouseleave="this.style.boxShadow=''; this.style.transform='';">

                <div class="card-header bg-light d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1 min-w-0">
                        <h6 class="mb-1 text-truncate">
                            <i class="fas fa-box text-primary me-1"></i>
                            <strong>${escapeHtml(orden.numero_pedido)}</strong>
                            ${tagModificado}
                        </h6>
                        <small class="d-block">
                            <i class="far fa-calendar me-1"></i>${escapeHtml(fechaEntrega)}
                        </small>
                    </div>
                    <span class="badge bg-secondary flex-shrink-0">${orden.items.length}</span>
                </div>

                <div class="card-body flex-grow-1 d-flex flex-column py-2">
                    <!-- Resumen de items -->
                    <div class="mb-2 overflow-auto" style="max-height: 120px; font-size: 0.85rem;">
                        ${itemsResumenHtml}
                    </div>

                    <!-- Barra de progreso general -->
                    <div class="mb-3 mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Progreso</small>
                            <small class="fw-bold">${porcentajeCompletado.toFixed(0)}%</small>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar ${colorProgreso}" style="width: ${Math.min(porcentajeCompletado, 100)}%; line-height: 20px; font-size: 0.7rem;">
                                ${porcentajeCompletado.toFixed(0)}%
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas rápidas -->
                    <div class="row g-1 text-center mb-3" style="font-size: 0.85rem;">
                        <div class="col-4">
                            <small class="text-muted d-block">Sol.</small>
                            <strong>${totalSolicitado.toFixed(1)}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Prod.</small>
                            <strong class="text-success">${totalProducido.toFixed(1)}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Pend.</small>
                            <strong class="${totalPendiente > 0 ? 'text-warning' : 'text-success'}">${totalPendiente.toFixed(1)}</strong>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-sm" onclick="window.abrirDetalle(${orden.pedido_id})">
                            <i class="fas fa-arrow-right me-1"></i>Ver Detalles
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="window.abrirModalModificacion(${orden.pedido_id})">
                            <i class="fas fa-edit me-1"></i>Solicitar Cambios
                        </button>
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    function abrirDetalle(pedidoId) {
        window.location.href = `${BASE_URL}/produccion_detalle.php?pedido_id=${pedidoId}`;
    }

    function actualizarPaginacion(pagination) {
        const div = document.getElementById('paginacion');

        if (pagination.total_paginas <= 1) {
            div.innerHTML = '';
            return;
        }

        let html = '<nav><ul class="pagination pagination-sm mb-0">';

        // Botón anterior
        if (pagination.pagina_actual > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cambiarPagina(${pagination.pagina_actual - 1}); return false;">Anterior</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
        }

        // Números de página
        for (let i = 1; i <= pagination.total_paginas; i++) {
            if (i === pagination.pagina_actual) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else if (i === 1 || i === pagination.total_paginas || (i >= pagination.pagina_actual - 2 && i <= pagination.pagina_actual + 2)) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cambiarPagina(${i}); return false;">${i}</a></li>`;
            } else if (i === pagination.pagina_actual - 3 || i === pagination.pagina_actual + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Botón siguiente
        if (pagination.pagina_actual < pagination.total_paginas) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cambiarPagina(${pagination.pagina_actual + 1}); return false;">Siguiente</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Siguiente</span></li>';
        }

        html += '</ul></nav>';
        div.innerHTML = html;
    }

    function actualizarContador(total) {
        const elemento = document.getElementById('contador');
        if (elemento) {
            elemento.textContent = `Mostrando ${total} ítem${total !== 1 ? 's' : ''}`;
        }
    }

    function cambiarPagina(pagina) {
        paginaActual = pagina;
        cargarProduccion();
        window.scrollTo(0, 0);
    }

    function limpiarFiltros() {
        document.getElementById('buscar').value = '';
        document.getElementById('estatus').value = 'en_produccion';
        document.getElementById('fechaFiltro').value = '';
        paginaActual = 1;
        cargarProduccion();
    }

    function mostrarExito(mensaje) {
        const alerta = document.createElement('div');
        alerta.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        alerta.style.zIndex = '9999';
        alerta.innerHTML = `
            ${escapeHtml(mensaje)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alerta);

        setTimeout(() => {
            alerta.remove();
        }, 3000);
    }

    function mostrarError(mensaje) {
        alert('Error: ' + mensaje);
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

    // Función para abrir el modal de modificación
    function abrirModalModificacion(pedidoId) {
        // Cargar datos del pedido
        fetch(`${BASE_URL}/app/controllers/produccion_obtener_detalle.php?pedido_id=${pedidoId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    mostrarError('No se pudo cargar los datos del pedido');
                    return;
                }

                const pedido = data.pedido;

                // Verificar que el pedido esté en producción
                if (pedido.estatus !== 'en_produccion') {
                    mostrarError('Solo se pueden modificar pedidos que estén en producción');
                    return;
                }

                const fechaActual = pedido.fecha_entrega ? new Date(pedido.fecha_entrega).toISOString().split('T')[0] : '';
                const items = data.items || [];

                // Crear tabla de items
                let itemsTableHtml = '';
                if (items.length > 0) {
                    itemsTableHtml = `
                        <div class="card border-0 mb-3" style="background-color: #f8fafc;">
                            <div class="card-header border-0" style="background-color: transparent; border-bottom: 2px solid #10b981;">
                                <h6 class="mb-0">
                                    <i class="fas fa-boxes me-2" style="color: #10b981;"></i>Items de la Orden
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Código</th>
                                                <th>Descripción</th>
                                                <th class="text-center">Solicitado</th>
                                                <th class="text-center">Nuevo Qty</th>
                                                <th class="text-center">Unidad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${items.map((item, index) => `
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary">${escapeHtml(item.item_code)}</span>
                                                    </td>
                                                    <td>
                                                        <small>${escapeHtml((item.descripcion || '').substring(0, 40))}</small>
                                                    </td>
                                                    <td class="text-center">
                                                        <strong>${parseFloat(item.qty_solicitada).toFixed(2)}</strong>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number" class="form-control form-control-sm text-center"
                                                               id="item_qty_card_${index}"
                                                               placeholder="Nueva qty"
                                                               step="0.01"
                                                               min="0"
                                                               data-item-id="${item.id}"
                                                               data-original-qty="${item.qty_solicitada}">
                                                    </td>
                                                    <td class="text-center">
                                                        <small class="text-muted">${escapeHtml(item.unidad_medida || 'un')}</small>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>Deja en blanco si no deseas modificar la cantidad
                                </small>
                            </div>
                        </div>
                    `;
                }

                const modalHTML = `
                    <div class="modal fade" id="modalModificarPedidoCard" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content border-0" style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);">
                                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none;">
                                    <h5 class="modal-title fw-bold text-white">
                                        <i class="fas fa-edit me-2"></i>
                                        Solicitar Modificación de Pedido
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="alert alert-info border-left border-4" style="border-left-color: #06b6d4; background-color: #cffafe;">
                                        <i class="fas fa-info-circle me-2 text-info"></i>
                                        <strong>Información Importante:</strong> Esta solicitud será enviada a gerencia para su revisión y autorización.
                                    </div>

                                    <form id="formModificarPedidoCard">
                                        <input type="hidden" id="pedidoIdModificarCard" value="${pedidoId}">

                                        <!-- Sección: Motivo de Modificación -->
                                        <div class="card border-0 bg-light mb-3">
                                            <div class="card-header bg-white border-bottom-2 border-info">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-comment-dots text-info me-2"></i>Motivo de la Solicitud
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <textarea class="form-control form-control-lg" id="motivoModificacionCard" rows="3"
                                                          placeholder="Describe detalladamente por qué necesitas esta modificación..." required></textarea>
                                                <small class="text-muted d-block mt-2">Este mensaje será visible para gerencia</small>
                                            </div>
                                        </div>

                                        ${itemsTableHtml}

                                        <!-- Sección: Modificaciones Disponibles -->
                                        <div class="card border-0 mb-3" style="background-color: #f8fafc;">
                                            <div class="card-header border-0" style="background-color: transparent; border-bottom: 2px solid #f59e0b;">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-tasks me-2" style="color: #f59e0b;"></i>Campos a Modificar
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Modificación de Fecha de Entrega -->
                                                <div class="mb-4 pb-3 border-bottom">
                                                    <div class="form-check">
                                                        <input class="form-check-input check-modificacion-card" type="checkbox" id="modFechaEntregaCard">
                                                        <label class="form-check-label fw-bold" for="modFechaEntregaCard">
                                                            <i class="fas fa-calendar-alt me-2 text-primary"></i>Fecha de Entrega
                                                        </label>
                                                    </div>
                                                    <div class="mt-2 ms-4 campos-modificacion-card" id="campos-fecha-entrega-card" style="display: none;">
                                                        <div class="row g-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-bold">Fecha Actual</label>
                                                                <input type="date" class="form-control form-control-sm" id="fechaActualDisplayCard"
                                                                       value="${fechaActual}" disabled>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small text-muted fw-bold">Nueva Fecha</label>
                                                                <input type="date" class="form-control form-control-sm" id="nuevaFechaEntregaCard">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modificación de Datos de Contacto -->
                                                <div class="mb-4 pb-3 border-bottom">
                                                    <div class="form-check">
                                                        <input class="form-check-input check-modificacion-card" type="checkbox" id="modDatosContactoCard">
                                                        <label class="form-check-label fw-bold" for="modDatosContactoCard">
                                                            <i class="fas fa-phone me-2 text-success"></i>Datos de Contacto
                                                        </label>
                                                    </div>
                                                    <div class="mt-2 ms-4 campos-modificacion-card" id="campos-datos-contacto-card" style="display: none;">
                                                        <div class="row g-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-bold">Contacto Actual</label>
                                                                <input type="text" class="form-control form-control-sm" value="${escapeHtml(pedido.contacto_principal || 'N/A')}" disabled>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small text-muted fw-bold">Nuevo Contacto</label>
                                                                <input type="text" class="form-control form-control-sm" id="nuevoContactoPrincipalCard" placeholder="Nombre del contacto">
                                                            </div>
                                                        </div>
                                                        <div class="row g-2 mt-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-bold">Teléfono Actual</label>
                                                                <input type="text" class="form-control form-control-sm" value="${escapeHtml(pedido.telefono || 'N/A')}" disabled>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small text-muted fw-bold">Nuevo Teléfono</label>
                                                                <input type="tel" class="form-control form-control-sm" id="nuevoTelefonoCard" placeholder="+1 (555) 000-0000">
                                                            </div>
                                                        </div>
                                                        <div class="row g-2 mt-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-bold">Correo Actual</label>
                                                                <input type="email" class="form-control form-control-sm" value="${escapeHtml(pedido.correo || 'N/A')}" disabled>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small text-muted fw-bold">Nuevo Correo</label>
                                                                <input type="email" class="form-control form-control-sm" id="nuevoCorreoCard" placeholder="correo@ejemplo.com">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modificación de Cantidades de Items -->
                                                <div class="mb-4 pb-3 border-bottom">
                                                    <div class="form-check">
                                                        <input class="form-check-input check-modificacion-card" type="checkbox" id="modCantidadesItemsCard">
                                                        <label class="form-check-label fw-bold" for="modCantidadesItemsCard">
                                                            <i class="fas fa-cubes me-2 text-info"></i>Cantidades de Items
                                                        </label>
                                                    </div>
                                                    <div class="mt-2 ms-4 campos-modificacion-card" id="campos-cantidades-items-card" style="display: none;">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle me-1"></i>Ingresa las nuevas cantidades en la tabla de arriba (solo para los que desees cambiar)
                                                        </small>
                                                    </div>
                                                </div>

                                                <!-- Modificación de Observaciones -->
                                                <div class="mb-0">
                                                    <div class="form-check">
                                                        <input class="form-check-input check-modificacion-card" type="checkbox" id="modObservacionesCard">
                                                        <label class="form-check-label fw-bold" for="modObservacionesCard">
                                                            <i class="fas fa-sticky-note me-2 text-warning"></i>Observaciones
                                                        </label>
                                                    </div>
                                                    <div class="mt-2 ms-4 campos-modificacion-card" id="campos-observaciones-card" style="display: none;">
                                                        <div>
                                                            <label class="form-label small fw-bold mb-2">Observaciones Actuales</label>
                                                            <textarea class="form-control form-control-sm" rows="2" disabled>${escapeHtml(pedido.observaciones || 'Sin observaciones')}</textarea>
                                                        </div>
                                                        <div class="mt-2">
                                                            <label class="form-label small text-muted fw-bold mb-2">Nuevas Observaciones</label>
                                                            <textarea class="form-control form-control-sm" id="nuevasObservacionesCard" rows="3" placeholder="Agrega o modifica las observaciones..."></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </button>
                                    <button type="button" class="btn btn-warning fw-bold" onclick="window.enviarSolicitudModificacionCard()">
                                        <i class="fas fa-paper-plane me-1"></i>Enviar a Gerencia
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const existingModal = document.getElementById('modalModificarPedidoCard');
                if (existingModal) {
                    existingModal.remove();
                }

                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Agregar event listeners para mostrar/ocultar campos
                document.getElementById('modFechaEntregaCard').addEventListener('change', function() {
                    document.getElementById('campos-fecha-entrega-card').style.display = this.checked ? 'block' : 'none';
                });
                document.getElementById('modDatosContactoCard').addEventListener('change', function() {
                    document.getElementById('campos-datos-contacto-card').style.display = this.checked ? 'block' : 'none';
                });
                document.getElementById('modCantidadesItemsCard').addEventListener('change', function() {
                    document.getElementById('campos-cantidades-items-card').style.display = this.checked ? 'block' : 'none';
                });
                document.getElementById('modObservacionesCard').addEventListener('change', function() {
                    document.getElementById('campos-observaciones-card').style.display = this.checked ? 'block' : 'none';
                });

                const modal = new bootstrap.Modal(document.getElementById('modalModificarPedidoCard'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar los datos del pedido');
            });
    }

    window.enviarSolicitudModificacionCard = function() {
        const pedidoId = document.getElementById('pedidoIdModificarCard').value;
        const motivo = document.getElementById('motivoModificacionCard').value.trim();

        if (!motivo) {
            mostrarError('Por favor ingrese el motivo de la modificación');
            return;
        }

        // Verificar que se haya seleccionado al menos un campo para modificar
        const modFecha = document.getElementById('modFechaEntregaCard').checked;
        const modContacto = document.getElementById('modDatosContactoCard').checked;
        const modCantidades = document.getElementById('modCantidadesItemsCard').checked;
        const modObservaciones = document.getElementById('modObservacionesCard').checked;

        if (!modFecha && !modContacto && !modCantidades && !modObservaciones) {
            mostrarError('Por favor selecciona al menos un campo para modificar');
            return;
        }

        // Recopilar los datos de modificación
        const datosModificacion = {
            fecha_entrega: modFecha,
            datos_contacto: modContacto,
            cantidades_items: modCantidades,
            observaciones: modObservaciones
        };

        // Si se modifica la fecha de entrega, capturar el nuevo valor
        if (modFecha) {
            const nuevaFecha = document.getElementById('nuevaFechaEntregaCard').value;
            if (!nuevaFecha) {
                mostrarError('Por favor ingresa la nueva fecha de entrega');
                return;
            }
            datosModificacion.nueva_fecha_entrega = nuevaFecha;
        }

        // Si se modifican los datos de contacto, capturar los nuevos valores
        if (modContacto) {
            datosModificacion.nuevo_contacto_principal = document.getElementById('nuevoContactoPrincipalCard').value.trim();
            datosModificacion.nuevo_telefono = document.getElementById('nuevoTelefonoCard').value.trim();
            datosModificacion.nuevo_correo = document.getElementById('nuevoCorreoCard').value.trim();
        }

        // Si se modifican las cantidades de items, capturar los nuevos valores
        if (modCantidades) {
            const itemsModificados = [];
            const itemInputs = document.querySelectorAll('input[id^="item_qty_card_"]');
            itemInputs.forEach(input => {
                const nuevoQty = input.value.trim();
                if (nuevoQty) {
                    const itemId = input.dataset.itemId;
                    const originalQty = input.dataset.originalQty;
                    itemsModificados.push({
                        item_id: itemId,
                        nueva_qty: parseFloat(nuevoQty),
                        qty_original: parseFloat(originalQty)
                    });
                }
            });
            if (itemsModificados.length === 0) {
                mostrarError('Por favor ingresa al menos una nueva cantidad para los items');
                return;
            }
            datosModificacion.items_modificados = itemsModificados;
        }

        // Si se modifican las observaciones, capturar el nuevo valor
        if (modObservaciones) {
            datosModificacion.nuevas_observaciones = document.getElementById('nuevasObservacionesCard').value.trim();
        }

        fetch(`${BASE_URL}/app/controllers/produccion_solicitar_modificacion.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pedido_id: pedidoId,
                motivo: motivo,
                datos_modificacion: datosModificacion
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalModificarPedidoCard'));
                modal.hide();
                mostrarExito('Solicitud de modificación enviada a gerencia para su revisión');

                // Recargar los datos después de un corto tiempo
                setTimeout(() => {
                    cargarProduccion();
                }, 1500);
            } else {
                mostrarError('Error: ' + (data.message || 'No se pudo enviar la solicitud'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al enviar la solicitud');
        });
    };

    // Exponer funciones globalmente
    window.cargarProduccion = cargarProduccion;
    window.cambiarPagina = cambiarPagina;
    window.limpiarFiltros = limpiarFiltros;
    window.abrirDetalle = abrirDetalle;
    window.abrirModalModificacion = abrirModalModificacion;
})();
