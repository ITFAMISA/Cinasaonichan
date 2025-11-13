// Módulo de Pedidos - CINASA
(function() {
    'use strict';

    let paginaActual = 1;
    let ordenActual = 'fecha_creacion';
    let direccionActual = 'DESC';
    let clienteIdFiltro = null;
    let clienteNombreFiltro = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Detectar si viene un filtro de cliente desde la URL
        const urlParams = new URLSearchParams(window.location.search);
        clienteIdFiltro = urlParams.get('cliente_id');
        clienteNombreFiltro = urlParams.get('cliente_nombre');

        // Si hay un filtro de cliente, mostrar el campo
        if (clienteIdFiltro && clienteNombreFiltro) {
            document.getElementById('cliente_filtro').value = decodeURIComponent(clienteNombreFiltro);
            document.getElementById('btnLimpiarClienteContainer').style.display = 'flex';
        }

        cargarPedidos();

        // Event listeners principales
        document.getElementById('btnNuevoPedido').addEventListener('click', () => {
            window.location.href = `${BASE_URL}/crear_pedido.php`;
        });
        document.getElementById('btnBuscar').addEventListener('click', () => cargarPedidos(1));
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

        // Event listener para limpiar filtro de cliente
        const btnLimpiarCliente = document.getElementById('btnLimpiarCliente');
        if (btnLimpiarCliente) {
            btnLimpiarCliente.addEventListener('click', () => {
                clienteIdFiltro = null;
                clienteNombreFiltro = null;
                document.getElementById('cliente_filtro').value = '';
                document.getElementById('btnLimpiarClienteContainer').style.display = 'none';
                cargarPedidos(1);
            });
        }

        // Ordenamiento
        document.querySelectorAll('.sortable').forEach(th => {
            th.addEventListener('click', function() {
                const columna = this.dataset.column;
                if (ordenActual === columna) {
                    direccionActual = direccionActual === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    ordenActual = columna;
                    direccionActual = 'ASC';
                }
                actualizarIconosOrden();
                cargarPedidos(paginaActual);
            });
        });

        // Búsqueda en Enter
        const buscarInput = document.getElementById('buscar');
        if (buscarInput) {
            buscarInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    cargarPedidos(1);
                }
            });
        }
    });

    function actualizarIconosOrden() {
        document.querySelectorAll('.sortable').forEach(th => {
            th.classList.remove('asc', 'desc');
            if (th.dataset.column === ordenActual) {
                th.classList.add(direccionActual.toLowerCase());
            }
        });
    }

    function cargarPedidos(pagina = 1) {
        paginaActual = pagina;

        const filtros = {
            buscar: document.getElementById('buscar').value,
            estatus: document.getElementById('estatus').value,
            orden: ordenActual,
            direccion: direccionActual,
            pagina: pagina
        };

        // Agregar filtro de cliente si está presente
        if (clienteIdFiltro) {
            filtros.cliente_id = clienteIdFiltro;
        }

        const queryString = new URLSearchParams(filtros).toString();
        const tbody = document.getElementById('tablaPedidos');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';

        fetch(`${BASE_URL}/app/controllers/pedidos_listar.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarPedidos(data.data);
                    actualizarPaginacion(data.pagination);
                    actualizarContador(data.pagination.total);
                } else {
                    mostrarError('Error al cargar pedidos: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar pedidos');
            });
    }

    function mostrarPedidos(pedidos) {
        const tbody = document.getElementById('tablaPedidos');
        tbody.innerHTML = '';

        if (pedidos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No se encontraron pedidos</td></tr>';
            return;
        }

        pedidos.forEach(pedido => {
            const tr = document.createElement('tr');

            const estatusClass = `badge-${pedido.estatus}`;
            const estatusText = pedido.estatus === 'en_produccion' ? 'En Producción' :
                               pedido.estatus === 'creada' ? 'Creada' :
                               pedido.estatus === 'completada' ? 'Completada' :
                               pedido.estatus === 'cancelada' ? 'Cancelada' :
                               pedido.estatus.charAt(0).toUpperCase() + pedido.estatus.slice(1);
            const fecha = new Date(pedido.fecha_creacion).toLocaleDateString('es-MX');

            // Generar botones según el estatus
            let botonesHTML = `
                <div class="action-buttons">
                    <button class="btn btn-sm btn-info text-white" onclick="window.verDetallePedido(${pedido.id})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
            `;

            if (pedido.estatus === 'creada') {
                // Si está en "creada": mostrar Editar y Enviar a Producción
                botonesHTML += `
                    <button class="btn btn-sm btn-warning" onclick="window.editarPedido(${pedido.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="window.cambiarEstatus(${pedido.id}, 'en_produccion', '¿Enviar este pedido a producción?')" title="Enviar a Producción">
                        <i class="fas fa-industry"></i>
                    </button>
                `;
            } else if (pedido.estatus === 'en_produccion') {
                // Si está en "en_produccion": no editar, mostrar Finalizar
                botonesHTML += `
                    <button class="btn btn-sm btn-success" onclick="window.cambiarEstatus(${pedido.id}, 'completada', '¿Marcar este pedido como completado?')" title="Finalizar Pedido">
                        <i class="fas fa-check-circle"></i>
                    </button>
                `;
            }
            // Si está en "completada" o "cancelada": solo mostrar Ver

            botonesHTML += `</div>`;

            tr.innerHTML = `
                <td><strong>${escapeHtml(pedido.numero_pedido)}</strong></td>
                <td>${escapeHtml(pedido.razon_social || 'N/A')}</td>
                <td>${fecha}</td>
                <td><span class="badge badge-${pedido.estatus}">${estatusText}</span></td>
                <td>${escapeHtml(pedido.contacto || 'N/A')}</td>
                <td>${botonesHTML}</td>
            `;

            tbody.appendChild(tr);
        });
    }

    function actualizarPaginacion(pagination) {
        const paginacionDiv = document.getElementById('paginacion');
        paginacionDiv.innerHTML = '';

        if (pagination.total_paginas <= 1) return;

        const nav = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination mb-0';

        const anterior = document.createElement('li');
        anterior.className = `page-item ${pagination.pagina_actual === 1 ? 'disabled' : ''}`;
        anterior.innerHTML = `<a class="page-link" href="#" onclick="window.cambiarPagina(${pagination.pagina_actual - 1}); return false;">Anterior</a>`;
        ul.appendChild(anterior);

        const inicio = Math.max(1, pagination.pagina_actual - 2);
        const fin = Math.min(pagination.total_paginas, pagination.pagina_actual + 2);

        if (inicio > 1) {
            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = `<a class="page-link" href="#" onclick="window.cambiarPagina(1); return false;">1</a>`;
            ul.appendChild(li);

            if (inicio > 2) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = `<span class="page-link">...</span>`;
                ul.appendChild(dots);
            }
        }

        for (let i = inicio; i <= fin; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === pagination.pagina_actual ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" onclick="window.cambiarPagina(${i}); return false;">${i}</a>`;
            ul.appendChild(li);
        }

        if (fin < pagination.total_paginas) {
            if (fin < pagination.total_paginas - 1) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = `<span class="page-link">...</span>`;
                ul.appendChild(dots);
            }

            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = `<a class="page-link" href="#" onclick="window.cambiarPagina(${pagination.total_paginas}); return false;">${pagination.total_paginas}</a>`;
            ul.appendChild(li);
        }

        const siguiente = document.createElement('li');
        siguiente.className = `page-item ${pagination.pagina_actual === pagination.total_paginas ? 'disabled' : ''}`;
        siguiente.innerHTML = `<a class="page-link" href="#" onclick="window.cambiarPagina(${pagination.pagina_actual + 1}); return false;">Siguiente</a>`;
        ul.appendChild(siguiente);

        nav.appendChild(ul);
        paginacionDiv.appendChild(nav);
    }

    function actualizarContador(total) {
        const elemento = document.getElementById('contador');
        if (elemento) {
            elemento.textContent = `Mostrando ${total} pedido${total !== 1 ? 's' : ''}`;
        }
    }

    function cambiarPagina(pagina) {
        paginaActual = pagina;
        cargarPedidos();
        window.scrollTo(0, 0);
    }

    function limpiarFiltros() {
        document.getElementById('buscar').value = '';
        document.getElementById('estatus').value = '';
        clienteIdFiltro = null;
        clienteNombreFiltro = null;
        document.getElementById('cliente_filtro').value = '';
        document.getElementById('btnLimpiarClienteContainer').style.display = 'none';
        paginaActual = 1;
        cargarPedidos();
    }

    function cambiarEstatus(pedidoId, estatus, mensaje) {
        if (!confirm(mensaje || `¿Cambiar el estatus del pedido?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('id', pedidoId);
        formData.append('estatus', estatus);

        fetch(`${BASE_URL}/app/controllers/pedidos_actualizar_estatus.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cargarPedidos(paginaActual);
                mostrarExito(data.message);
            } else {
                mostrarError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al actualizar estatus');
        });
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
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Función para ver el detalle del pedido
    function verDetallePedido(id) {
        fetch(`${BASE_URL}/app/controllers/pedidos_detalle.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarDetalleModal(data.data, data.items || []);
                } else {
                    mostrarError('Error al cargar el detalle del pedido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar el pedido');
            });
    }

    function mostrarDetalleModal(pedido, items) {
        const fechaCreacion = new Date(pedido.fecha_creacion).toLocaleDateString('es-MX');
        const fechaEntrega = pedido.fecha_entrega ? new Date(pedido.fecha_entrega).toLocaleDateString('es-MX') : 'No especificada';

        const estatusClass = `badge-${pedido.estatus}`;
        const estatusText = pedido.estatus === 'en_produccion' ? 'En Producción' :
                           pedido.estatus === 'creada' ? 'Creada' :
                           pedido.estatus === 'completada' ? 'Completada' :
                           pedido.estatus === 'cancelada' ? 'Cancelada' :
                           pedido.estatus.charAt(0).toUpperCase() + pedido.estatus.slice(1);

        const itemsHTML = items.map(item => `
            <tr>
                <td><small>${item.line}</small></td>
                <td><small>[${item.producto_id}] ${escapeHtml(item.descripcion)}</small></td>
                <td><small>${parseFloat(item.cantidad).toFixed(2)}</small></td>
                <td><small>${escapeHtml(item.unidad_medida || 'N/A')}</small></td>
                <td><small>$${parseFloat(item.precio_unitario || 0).toFixed(2)}</small></td>
                <td><small>$${parseFloat(item.subtotal || 0).toFixed(2)}</small></td>
            </tr>
        `).join('');

        const detalleHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-3 text-blue-700">
                                <i class="fas fa-file-invoice text-blue-600 me-2"></i>
                                Información del Pedido
                            </h6>
                            <div class="detail-item">
                                <span class="detail-label">Número de Pedido</span>
                                <span class="detail-value">${escapeHtml(pedido.numero_pedido)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Cliente</span>
                                <span class="detail-value">${escapeHtml(pedido.razon_social || 'N/A')}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Estatus</span>
                                <span class="detail-value"><span class="badge ${estatusClass}">${estatusText}</span></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fecha de Creación</span>
                                <span class="detail-value">${fechaCreacion}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-3 text-blue-700">
                                <i class="fas fa-address-book text-blue-600 me-2"></i>
                                Contacto
                            </h6>
                            <div class="detail-item">
                                <span class="detail-label">Contacto</span>
                                <span class="detail-value">${escapeHtml(pedido.contacto || 'N/A')}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Teléfono</span>
                                <span class="detail-value">${escapeHtml(pedido.telefono || 'N/A')}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Correo</span>
                                <span class="detail-value">${escapeHtml(pedido.correo || 'N/A')}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fecha de Entrega</span>
                                <span class="detail-value">${fechaEntrega}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title mb-3 text-blue-700">
                                <i class="fas fa-map-marker-alt text-blue-600 me-2"></i>
                                Direcciones de Envío
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Facturación (Bill To)</span>
                                        <span class="detail-value">${escapeHtml(pedido.facturacion || 'N/A')}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <span class="detail-label">Entrega (Ship To)</span>
                                        <span class="detail-value">${escapeHtml(pedido.entrega || 'N/A')}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title mb-3 text-blue-700">
                                <i class="fas fa-boxes text-blue-600 me-2"></i>
                                Productos del Pedido
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Línea</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                            <th>Unidad</th>
                                            <th>Precio Unit.</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsHTML}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ${pedido.observaciones ? `
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title mb-3 text-blue-700">
                                <i class="fas fa-sticky-note text-blue-600 me-2"></i>
                                Observaciones
                            </h6>
                            <p class="mb-0">${escapeHtml(pedido.observaciones)}</p>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        `;

        const styles = `
            <style>
                .detail-item {
                    display: flex;
                    flex-direction: column;
                    margin-bottom: 2.5rem !important;
                    padding-bottom: 2rem !important;
                    border-bottom: 1px solid rgba(0,0,0,0.1);
                }
                .detail-item:last-child {
                    border-bottom: none;
                    margin-bottom: 0 !important;
                    padding-bottom: 0 !important;
                }
                .detail-label {
                    font-size: 0.85rem;
                    font-weight: 600;
                    color: #64748b;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 0.8rem !important;
                }
                .detail-value {
                    font-size: 1rem;
                    color: #1e293b;
                    font-weight: 500;
                    word-break: break-word;
                    line-height: 1.6 !important;
                }
            </style>
        `;

        const modalHTML = `
            <div class="modal fade" id="modalDetallePedido" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-file-invoice mr-2"></i>Detalle del Pedido - ${escapeHtml(pedido.numero_pedido)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${detalleHTML}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" onclick="window.editarPedido(${pedido.id})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const existingStyle = document.getElementById('detailPedidoStyles');
        if (!existingStyle) {
            document.head.insertAdjacentHTML('beforeend', `<style id="detailPedidoStyles">${styles.slice(7, -8)}</style>`);
        }

        const existingModal = document.getElementById('modalDetallePedido');
        if (existingModal) {
            existingModal.remove();
        }

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = new bootstrap.Modal(document.getElementById('modalDetallePedido'));
        modal.show();
    }

    // Función para editar el pedido
    function editarPedido(id) {
        // Primero, obtener el estatus del pedido
        fetch(`${BASE_URL}/app/controllers/pedidos_detalle.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const pedido = data.data;

                    // Validar si el pedido está en producción
                    if (pedido.estatus === 'en_produccion') {
                        mostrarError('No se puede editar un pedido en producción. Debe finalizarse desde la lista de pedidos.');
                        return;
                    }

                    // Si está en completada o cancelada
                    if (pedido.estatus === 'completada' || pedido.estatus === 'cancelada') {
                        mostrarError('No se puede editar un pedido que ya ha sido ' + pedido.estatus + '.');
                        return;
                    }

                    // Si es "creada", permitir editar
                    window.location.href = `${BASE_URL}/editar_pedido.php?id=${id}`;
                } else {
                    mostrarError('Error al cargar el pedido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al validar el pedido');
            });
    }

    // Exponer funciones globales
    window.cargarPedidos = cargarPedidos;
    window.cambiarPagina = cambiarPagina;
    window.limpiarFiltros = limpiarFiltros;
    window.verDetallePedido = verDetallePedido;
    window.editarPedido = editarPedido;
    window.cambiarEstatus = cambiarEstatus;
})();
