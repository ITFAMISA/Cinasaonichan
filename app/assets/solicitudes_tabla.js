// Módulo de Solicitudes de Modificación - CINASA
(function() {
    'use strict';

    let paginaActual = 1;
    let ordenActual = 'fecha_solicitud';
    let direccionActual = 'DESC';

    document.addEventListener('DOMContentLoaded', function() {
        cargarSolicitudes();

        // Event listeners principales
        document.getElementById('btnBuscar').addEventListener('click', () => cargarSolicitudes(1));
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

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
                cargarSolicitudes(paginaActual);
            });
        });

        // Búsqueda en Enter
        const buscarInput = document.getElementById('buscar');
        if (buscarInput) {
            buscarInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    cargarSolicitudes(1);
                }
            });
        }

        // Cambio de filtro de estado
        const estatusSelect = document.getElementById('estatus');
        if (estatusSelect) {
            estatusSelect.addEventListener('change', () => cargarSolicitudes(1));
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

    function cargarSolicitudes(pagina = 1) {
        paginaActual = pagina;

        const filtros = {
            buscar: document.getElementById('buscar').value,
            estatus: document.getElementById('estatus').value,
            orden: ordenActual,
            direccion: direccionActual,
            pagina: pagina
        };

        const queryString = new URLSearchParams(filtros).toString();
        const tbody = document.getElementById('tablaSolicitudes');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';

        fetch(`${BASE_URL}/app/controllers/produccion_listar_solicitudes.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarSolicitudes(data.data);
                    actualizarPaginacion(data.pagination);
                    actualizarContador(data.pagination.total);
                } else {
                    mostrarError('Error al cargar solicitudes: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar solicitudes');
            });
    }

    function mostrarSolicitudes(solicitudes) {
        const tbody = document.getElementById('tablaSolicitudes');
        tbody.innerHTML = '';

        if (solicitudes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No se encontraron solicitudes</td></tr>';
            return;
        }

        solicitudes.forEach(solicitud => {
            const tr = document.createElement('tr');

            const estatusClass = `badge-${solicitud.estatus}`;
            const estatusText = solicitud.estatus === 'aprobada' ? 'Aprobada' :
                               solicitud.estatus === 'pendiente' ? 'Pendiente' :
                               solicitud.estatus === 'rechazada' ? 'Rechazada' : 'Desconocido';

            const fecha = new Date(solicitud.fecha_solicitud).toLocaleString('es-MX');
            const motivo = (solicitud.motivo_modificacion || '').substring(0, 50) +
                          (solicitud.motivo_modificacion && solicitud.motivo_modificacion.length > 50 ? '...' : '');

            let acciones = `
                <div class="action-buttons">
                    <a href="${BASE_URL}/produccion_detalle.php?pedido_id=${solicitud.pedido_id}"
                       class="btn btn-sm btn-info" title="Ver Pedido">
                        <i class="fas fa-eye"></i>
                    </a>
            `;

            if (solicitud.estatus === 'pendiente') {
                acciones += `
                    <button class="btn btn-sm btn-warning" onclick="window.mostrarModalAutorizar(${solicitud.id})" title="Autorizar y Aplicar">
                        <i class="fas fa-gavel"></i>
                    </button>
                `;
            }

            acciones += `</div>`;

            tr.innerHTML = `
                <td>${escapeHtml(solicitud.numero_pedido)}</td>
                <td><small>${fecha}</small></td>
                <td>${escapeHtml(solicitud.usuario_solicitante)}</td>
                <td><small>${escapeHtml(motivo)}</small></td>
                <td><span class="badge ${estatusClass}">${estatusText}</span></td>
                <td>${acciones}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function actualizarPaginacion(pagination) {
        const container = document.getElementById('paginacion');
        container.innerHTML = '';

        if (pagination.total_pages <= 1) return;

        // Botón Anterior
        if (pagination.current_page > 1) {
            const btnAnt = document.createElement('button');
            btnAnt.className = 'btn btn-sm btn-outline-primary';
            btnAnt.innerHTML = '<i class="fas fa-chevron-left"></i>';
            btnAnt.addEventListener('click', () => cargarSolicitudes(pagination.current_page - 1));
            container.appendChild(btnAnt);
        }

        // Números de página
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.current_page) {
                const btnActual = document.createElement('button');
                btnActual.className = 'btn btn-sm btn-primary active';
                btnActual.textContent = i;
                container.appendChild(btnActual);
            } else if (i === 1 || i === pagination.total_pages ||
                      (i >= pagination.current_page - 1 && i <= pagination.current_page + 1)) {
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-outline-primary';
                btn.textContent = i;
                btn.addEventListener('click', () => cargarSolicitudes(i));
                container.appendChild(btn);
            } else if (i === 2 && pagination.current_page > 3) {
                const span = document.createElement('span');
                span.className = 'btn btn-sm btn-outline-light';
                span.textContent = '...';
                container.appendChild(span);
            }
        }

        // Botón Siguiente
        if (pagination.current_page < pagination.total_pages) {
            const btnSig = document.createElement('button');
            btnSig.className = 'btn btn-sm btn-outline-primary';
            btnSig.innerHTML = '<i class="fas fa-chevron-right"></i>';
            btnSig.addEventListener('click', () => cargarSolicitudes(pagination.current_page + 1));
            container.appendChild(btnSig);
        }
    }

    function actualizarContador(total) {
        const contador = document.getElementById('contador');
        contador.textContent = `Mostrando ${total} solicitud${total !== 1 ? 'es' : ''}`;
    }

    function limpiarFiltros() {
        document.getElementById('buscar').value = '';
        document.getElementById('estatus').value = '';
        cargarSolicitudes(1);
    }

    window.cargarSolicitudes = cargarSolicitudes;

    window.mostrarModalAutorizar = function(solicitudId) {
        // Primero cargar los detalles de la solicitud
        fetch(`${BASE_URL}/app/controllers/produccion_listar_solicitudes.php?solicitud_id=${solicitudId}`)
            .then(response => response.json())
            .then(data => {
                let detallesHtml = '';

                if (data.data && data.data.length > 0) {
                    const solicitud = data.data[0];
                    const datos = solicitud.datos_modificacion ? JSON.parse(solicitud.datos_modificacion) : {};

                    detallesHtml = `
                        <div class="card mb-3" style="border-left: 4px solid #2563eb;">
                            <div class="card-body">
                                <h6 class="card-title mb-2"><i class="fas fa-box me-2" style="color: #2563eb;"></i>Pedido</h6>
                                <p class="mb-2"><strong>${escapeHtml(solicitud.numero_pedido)}</strong></p>

                                <h6 class="card-title mb-2"><i class="fas fa-user me-2" style="color: #2563eb;"></i>Solicitante</h6>
                                <p class="mb-2">${escapeHtml(solicitud.usuario_solicitante)}</p>

                                <h6 class="card-title mb-2"><i class="fas fa-comment me-2" style="color: #2563eb;"></i>Motivo</h6>
                                <p class="mb-3"><small>${escapeHtml(solicitud.motivo_modificacion)}</small></p>

                                <h6 class="card-title mb-2"><i class="fas fa-tasks me-2" style="color: #2563eb;"></i>Cambios Solicitados</h6>
                    `;

                    let tieneCambios = false;

                    // Procesar datos según estructura: puede ser array directo o dentro de un objeto
                    let itemsModificados = Array.isArray(datos) ? datos : (datos.cantidades_items || []);

                    // Si no es array, intentar extraer items del primer nivel
                    if (!Array.isArray(itemsModificados) && typeof datos === 'object') {
                        for (const [clave, valor] of Object.entries(datos)) {
                            if (Array.isArray(valor) && valor.length > 0 && valor[0].item_id) {
                                itemsModificados = valor;
                                break;
                            }
                        }
                    }

                    // Mostrar cambios de cantidades en tabla visual
                    if (Array.isArray(itemsModificados) && itemsModificados.length > 0 && itemsModificados[0].item_id) {
                        tieneCambios = true;
                        const itemsTable = itemsModificados.map(item => `
                            <tr>
                                <td><small class="badge bg-primary">${item.item_id}</small></td>
                                <td><small>${escapeHtml(item.descripcion || 'N/A')}</small></td>
                                <td class="text-center"><span class="badge bg-warning text-dark">${parseFloat(item.qty_original).toFixed(2)}</span></td>
                                <td class="text-center"><i class="fas fa-arrow-right text-info"></i></td>
                                <td class="text-center"><span class="badge bg-success text-white">${parseFloat(item.nueva_qty).toFixed(2)}</span></td>
                            </tr>
                        `).join('');

                        detallesHtml += `
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Descripción</th>
                                            <th class="text-center">Cantidad Original</th>
                                            <th class="text-center"></th>
                                            <th class="text-center">Nueva Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsTable}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // Mostrar otros datos de modificación
                    if (Object.keys(datos).length > 0) {
                        // Mostrar fecha de entrega
                        if (datos.nueva_fecha_entrega) {
                            tieneCambios = true;
                            detallesHtml += `
                                <div class="card border-0 mb-2" style="background-color: #fef3c7;">
                                    <div class="card-body py-2 px-3">
                                        <small><i class="fas fa-calendar-alt text-warning me-2"></i><strong>Fecha de Entrega:</strong></small><br>
                                        <small class="text-muted">Nueva fecha: <code>${escapeHtml(datos.nueva_fecha_entrega)}</code></small>
                                    </div>
                                </div>
                            `;
                        }

                        // Mostrar datos de contacto
                        if (datos.nuevo_contacto_principal || datos.nuevo_telefono || datos.nuevo_correo) {
                            tieneCambios = true;
                            detallesHtml += `
                                <div class="card border-0 mb-2" style="background-color: #ecfdf5;">
                                    <div class="card-body py-2 px-3">
                                        <small><i class="fas fa-phone text-success me-2"></i><strong>Datos de Contacto:</strong></small><br>
                                        ${datos.nuevo_contacto_principal ? `<small class="text-muted d-block">Contacto: <code>${escapeHtml(datos.nuevo_contacto_principal)}</code></small>` : ''}
                                        ${datos.nuevo_telefono ? `<small class="text-muted d-block">Teléfono: <code>${escapeHtml(datos.nuevo_telefono)}</code></small>` : ''}
                                        ${datos.nuevo_correo ? `<small class="text-muted d-block">Correo: <code>${escapeHtml(datos.nuevo_correo)}</code></small>` : ''}
                                    </div>
                                </div>
                            `;
                        }

                        // Mostrar observaciones
                        if (datos.nuevas_observaciones) {
                            tieneCambios = true;
                            detallesHtml += `
                                <div class="card border-0 mb-2" style="background-color: #e0f2fe;">
                                    <div class="card-body py-2 px-3">
                                        <small><i class="fas fa-sticky-note text-info me-2"></i><strong>Observaciones:</strong></small><br>
                                        <small class="text-muted"><em>${escapeHtml(datos.nuevas_observaciones)}</em></small>
                                    </div>
                                </div>
                            `;
                        }
                    }

                    if (!tieneCambios) {
                        detallesHtml += `<small class="text-muted">Sin detalles específicos</small>`;
                    }

                    detallesHtml += `</div></div>`;
                }

                const modalHTML = `
                    <div class="modal fade" id="modalAutorizar" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0" style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);">
                                <div class="modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;">
                                    <h5 class="modal-title fw-bold text-white">
                                        <i class="fas fa-gavel me-2"></i>Revisar y Autorizar Solicitud
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <input type="hidden" id="solicitudIdAutorizar" value="${solicitudId}">

                                    ${detallesHtml}

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-comment me-2" style="color: #2563eb;"></i>Comentarios (Opcional)
                                        </label>
                                        <textarea class="form-control" id="comentariosAutorizacion" rows="3"
                                                  placeholder="Agrega comentarios adicionales sobre tu decisión..."
                                                  style="border-color: #e2e8f0; border-radius: 12px;"></textarea>
                                        <small class="text-muted d-block mt-2">Estos comentarios serán visibles para quien solicitó el cambio</small>
                                    </div>
                                </div>
                                <div class="modal-footer" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </button>
                                    <button type="button" class="btn btn-danger fw-bold" onclick="window.procesarAutorizacion('rechazar')">
                                        <i class="fas fa-ban me-1"></i>Rechazar
                                    </button>
                                    <button type="button" class="btn btn-success fw-bold" onclick="window.procesarAutorizacion('aprobar')">
                                        <i class="fas fa-check-circle me-1"></i>Aprobar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const existingModal = document.getElementById('modalAutorizar');
                if (existingModal) {
                    existingModal.remove();
                }

                document.body.insertAdjacentHTML('beforeend', modalHTML);
                const modal = new bootstrap.Modal(document.getElementById('modalAutorizar'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los detalles de la solicitud');
            });
    };

    window.procesarAutorizacion = function(accion) {
        const solicitudId = document.getElementById('solicitudIdAutorizar').value;
        const comentarios = document.getElementById('comentariosAutorizacion').value.trim();

        fetch(`${BASE_URL}/app/controllers/produccion_autorizar_modificacion.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                solicitud_id: solicitudId,
                accion: accion,
                comentarios: comentarios
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAutorizar'));
                modal.hide();
                mostrarExito(data.message);
                // Cambiar filtro a "Todos" para ver solicitudes aplicadas
                document.getElementById('estatus').value = '';
                setTimeout(() => {
                    cargarSolicitudes(1);
                }, 1500);
            } else {
                alert('Error: ' + (data.message || 'No se pudo procesar la solicitud'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al procesar la solicitud');
        });
    };

    // Función deprecada: aplicarModificacion ahora se hace en produccion_autorizar_modificacion.php
    // Se mantiene por compatibilidad, pero no se usa en la interfaz actual
    window.aplicarModificacion = function(solicitudId) {
        alert('La aplicación de cambios ahora ocurre automáticamente al aprobar la solicitud.');
        cargarSolicitudes();
    };

    function mostrarExito(mensaje) {
        const alerta = document.createElement('div');
        alerta.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        alerta.style.zIndex = '9999';
        alerta.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>${escapeHtml(mensaje)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alerta);

        setTimeout(() => {
            alerta.remove();
        }, 3000);
    }

    function mostrarError(mensaje) {
        const alerta = document.createElement('div');
        alerta.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        alerta.style.zIndex = '9999';
        alerta.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>${escapeHtml(mensaje)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alerta);

        setTimeout(() => {
            alerta.remove();
        }, 5000);
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
})();
