// Módulo de Solicitudes de Modificación - CINASA
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        cargarSolicitudes();
    });

    function cargarSolicitudes() {
        const estatus = document.getElementById('filtroEstatus').value;
        const queryString = estatus ? `?estatus=${estatus}` : '';

        fetch(`${BASE_URL}/app/controllers/produccion_listar_solicitudes.php${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarSolicitudes(data.data);
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
        const container = document.getElementById('solicitudesContainer');

        if (solicitudes.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info border-left-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay solicitudes de modificación con los filtros seleccionados.
                </div>
            `;
            return;
        }

        let html = '';

        solicitudes.forEach(solicitud => {
            const fecha = new Date(solicitud.fecha_solicitud).toLocaleString('es-MX');
            const fechaRespuesta = solicitud.fecha_respuesta ?
                new Date(solicitud.fecha_respuesta).toLocaleString('es-MX') : '-';

            const badgeClass = {
                'pendiente': 'bg-warning text-dark',
                'aprobada': 'bg-success',
                'rechazada': 'bg-danger'
            }[solicitud.estatus] || 'bg-secondary';

            const badgeIcon = {
                'pendiente': 'fa-hourglass-half',
                'aprobada': 'fa-check-circle',
                'rechazada': 'fa-times-circle'
            }[solicitud.estatus] || 'fa-question-circle';

            const datos = solicitud.datos_modificacion ? JSON.parse(solicitud.datos_modificacion) : {};

            // Construir detalles de modificación
            let detallesModificacion = '';
            if (datos.fecha_entrega) {
                detallesModificacion += `
                    <div class="border-bottom pb-2 mb-2">
                        <strong><i class="fas fa-calendar-alt text-primary me-2"></i>Fecha de Entrega</strong><br>
                        <small class="text-muted">Nueva fecha: <code>${escapeHtml(datos.nueva_fecha_entrega || 'N/A')}</code></small>
                    </div>
                `;
            }
            if (datos.datos_contacto) {
                detallesModificacion += `
                    <div class="border-bottom pb-2 mb-2">
                        <strong><i class="fas fa-phone text-success me-2"></i>Datos de Contacto</strong><br>
                        <small class="text-muted">
                            Contacto: <code>${escapeHtml(datos.nuevo_contacto_principal || 'N/A')}</code><br>
                            Teléfono: <code>${escapeHtml(datos.nuevo_telefono || 'N/A')}</code><br>
                            Correo: <code>${escapeHtml(datos.nuevo_correo || 'N/A')}</code>
                        </small>
                    </div>
                `;
            }
            if (datos.observaciones) {
                detallesModificacion += `
                    <div class="pb-2">
                        <strong><i class="fas fa-sticky-note text-warning me-2"></i>Observaciones</strong><br>
                        <small class="text-muted">
                            <em>${escapeHtml(datos.nuevas_observaciones || 'N/A')}</em>
                        </small>
                    </div>
                `;
            }

            html += `
                <div class="card border-0 shadow-sm mb-3 transition-all hover:shadow-md">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-2 fw-bold">
                                    <i class="fas fa-box me-2"></i>Pedido: ${escapeHtml(solicitud.numero_pedido)}
                                </h6>
                                <small class="opacity-90">
                                    <i class="far fa-calendar me-1"></i>Solicitado: ${fecha}
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge ${badgeClass} fs-6">
                                    <i class="fas ${badgeIcon} me-1"></i>
                                    ${solicitud.estatus.charAt(0).toUpperCase() + solicitud.estatus.slice(1)}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Columna Izquierda: Detalles de la solicitud -->
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="text-muted small fw-bold d-block mb-1">
                                        <i class="fas fa-user me-1"></i>Solicitante
                                    </label>
                                    <p class="mb-0 ms-3">${escapeHtml(solicitud.usuario_solicitante)}</p>
                                </div>

                                <hr class="my-2">

                                <div class="mb-3">
                                    <label class="text-muted small fw-bold d-block mb-1">
                                        <i class="fas fa-comment-dots me-1"></i>Motivo de la Solicitud
                                    </label>
                                    <div class="alert alert-light border-left-info ms-3 p-2 mb-0">
                                        <small>${escapeHtml(solicitud.motivo_modificacion)}</small>
                                    </div>
                                </div>

                                <hr class="my-2">

                                <div>
                                    <label class="text-muted small fw-bold d-block mb-2">
                                        <i class="fas fa-tasks me-1"></i>Cambios Solicitados
                                    </label>
                                    <div class="ms-3 bg-light p-2 rounded">
                                        ${detallesModificacion || '<small class="text-muted">Sin detalles</small>'}
                                    </div>
                                </div>

                                ${solicitud.estatus !== 'pendiente' ? `
                                    <hr class="my-2">
                                    <div class="alert ${solicitud.estatus === 'aprobada' ? 'alert-success' : 'alert-danger'} border-left-3 p-2">
                                        <strong><i class="fas fa-user-tie me-1"></i>Respuesta:</strong><br>
                                        <small>
                                            Autorizada por: ${escapeHtml(solicitud.usuario_autorizador || 'N/A')}<br>
                                            Fecha: ${fechaRespuesta}
                                        </small>
                                        ${solicitud.comentarios_autorizacion ? `<br><em>"${escapeHtml(solicitud.comentarios_autorizacion)}"</em>` : ''}
                                    </div>
                                ` : ''}
                            </div>

                            <!-- Columna Derecha: Acciones -->
                            <div class="col-lg-4">
                                <div class="d-grid gap-2">
                                    ${solicitud.estatus === 'pendiente' ? `
                                        <button class="btn btn-warning fw-bold" onclick="window.mostrarModalAutorizar(${solicitud.id})">
                                            <i class="fas fa-gavel me-1"></i>Revisar y Autorizar
                                        </button>
                                    ` : ''}
                                    <a href="${BASE_URL}/produccion_detalle.php?pedido_id=${solicitud.pedido_id}"
                                       class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-external-link-alt me-1"></i>Ver Pedido Completo
                                    </a>
                                    ${solicitud.estatus === 'aprobada' ? `
                                        <button class="btn btn-success" onclick="window.aplicarModificacion(${solicitud.id})">
                                            <i class="fas fa-check me-1"></i>Aplicar Cambios
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    window.mostrarModalAutorizar = function(solicitudId) {
        const modalHTML = `
            <div class="modal fade" id="modalAutorizar" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0" style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);">
                        <div class="modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;">
                            <h5 class="modal-title fw-bold text-white">
                                <i class="fas fa-gavel me-2"></i>Tomar Decisión sobre Solicitud
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <input type="hidden" id="solicitudIdAutorizar" value="${solicitudId}">

                            <div class="alert alert-info border-left border-4" style="border-left-color: #06b6d4; background-color: #cffafe;">
                                <i class="fas fa-info-circle me-2 text-info"></i>
                                <strong>Nota:</strong> Revisa cuidadosamente los cambios solicitados antes de tomar tu decisión.
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-comment me-2" style="color: #2563eb;"></i>Comentarios (Opcional)
                                </label>
                                <textarea class="form-control" id="comentariosAutorizacion" rows="4"
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
                cargarSolicitudes();
            } else {
                alert('Error: ' + (data.message || 'No se pudo procesar la solicitud'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al procesar la solicitud');
        });
    };

    window.cargarSolicitudes = cargarSolicitudes;

    window.aplicarModificacion = function(solicitudId) {
        if (!confirm('¿Estás seguro de que deseas aplicar esta modificación al pedido?')) {
            return;
        }

        fetch(`${BASE_URL}/app/controllers/produccion_modificar_pedido.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                solicitud_id: solicitudId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarExito('Modificación aplicada correctamente al pedido');
                setTimeout(() => {
                    cargarSolicitudes();
                }, 1500);
            } else {
                alert('Error: ' + (data.message || 'No se pudo aplicar la modificación'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al aplicar la modificación');
        });
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

    // Event listeners
    const btnBuscar = document.getElementById('btnBuscar');
    if (btnBuscar) {
        btnBuscar.addEventListener('click', cargarSolicitudes);
    }

    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', function() {
            document.getElementById('filtroEstatus').value = '';
            cargarSolicitudes();
        });
    }

    const filtroEstatus = document.getElementById('filtroEstatus');
    if (filtroEstatus) {
        filtroEstatus.addEventListener('change', cargarSolicitudes);
    }
})();
