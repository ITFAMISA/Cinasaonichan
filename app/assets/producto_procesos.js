// Módulo para asignar procesos a productos - CINASA
(function() {
    'use strict';

    let productoProcesosEnEdicion = null;
    let procesosDisponibles = [];

    // Función para abrir modal de asignación de procesos
    window.abrirModalAsignarProcesos = function(productoId) {
        productoProcesosEnEdicion = productoId;
        cargarProcesosYRutas(productoId);
    };

    function cargarProcesosYRutas(productoId) {
        // Cargar lista de procesos disponibles
        fetch(`${BASE_URL}/app/controllers/procesos_obtener.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    procesosDisponibles = data.data;
                    // Luego cargar rutas asignadas del producto
                    cargarRutasProducto(productoId);
                } else {
                    mostrarAlertaProcesos('Error al cargar procesos', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlertaProcesos('Error al cargar procesos', 'danger');
            });
    }

    function cargarRutasProducto(productoId) {
        fetch(`${BASE_URL}/app/controllers/producto_rutas_procesos_listar.php?producto_id=${productoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalAsignarProcesos(productoId, data.data);
                } else {
                    mostrarAlertaProcesos('Error al cargar rutas', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlertaProcesos('Error al cargar rutas', 'danger');
            });
    }

    function mostrarModalAsignarProcesos(productoId, rutasActuales) {
        const modalHTML = `
            <div class="modal fade" id="modalAsignarProcesos" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-sitemap mr-2"></i> Asignar Ruta de Procesos
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="mb-3">
                                        <i class="fas fa-plus-circle text-success"></i> Agregar Nuevo Proceso
                                    </h6>
                                    <div class="input-group mb-2">
                                        <select class="form-select" id="selectProceso">
                                            <option value="">Seleccionar un proceso...</option>
                                            ${procesosDisponibles.map(p =>
                                                `<option value="${p.id}" data-inspeccion="${p.requiere_inspeccion_calidad}">
                                                    ${htmlEscape(p.nombre)}
                                                    ${p.requiere_inspeccion_calidad ? ' (✓ QC)' : ''}
                                                </option>`
                                            ).join('')}
                                        </select>
                                    </div>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Orden</span>
                                        <input type="number" class="form-control" id="inputOrden" min="1" value="${rutasActuales.length + 1}" placeholder="Orden de ejecución">
                                    </div>
                                    <div class="mb-2">
                                        <label for="inputNotas" class="form-label">Notas (Opcional)</label>
                                        <textarea class="form-control" id="inputNotas" rows="2" placeholder="Notas especiales para este proceso..."></textarea>
                                    </div>
                                    <button class="btn btn-success w-100" onclick="agregarProcesoAProducto(${productoId})">
                                        <i class="fas fa-plus"></i> Agregar Proceso
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">
                                        <i class="fas fa-list text-info"></i> Información del Proceso
                                    </h6>
                                    <div id="infoProceso" class="alert alert-info" role="alert">
                                        <small>Selecciona un proceso para ver más detalles</small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-3">
                                <i class="fas fa-route text-primary"></i> Ruta de Procesos Actual (${rutasActuales.length})
                            </h6>

                            <div id="contenedorRutasProcesos">
                            ${rutasActuales.length === 0 ?
                                `<div class="alert alert-warning">
                                    <i class="fas fa-info-circle"></i> No hay procesos asignados aún. Agrega al menos uno.
                                </div>`
                                :
                                `<div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 60px;">Orden</th>
                                                <th>Proceso</th>
                                                <th>Requiere QC</th>
                                                <th>Notas</th>
                                                <th style="width: 120px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaRutasActuales">
                                            ${rutasActuales.map(ruta => `
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary" style="font-size: 0.95rem; padding: 0.5rem 0.75rem;">${ruta.orden_secuencia}</span>
                                                    </td>
                                                    <td>
                                                        <strong>${htmlEscape(ruta.proceso_nombre)}</strong>
                                                        <br>
                                                        <small>${htmlEscape(ruta.proceso_descripcion || '')}</small>
                                                    </td>
                                                    <td>
                                                        ${ruta.requiere_inspeccion_calidad ?
                                                            '<span class="badge bg-success">Sí</span>' :
                                                            '<span class="badge bg-secondary">No</span>'}
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" id="notas-${ruta.id}" value="${htmlEscape(ruta.notas || '')}" placeholder="Notas..." onchange="actualizarRuta(${ruta.id}, document.getElementById('notas-${ruta.id}').value)">
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-danger" onclick="eliminarProcesoDeProducto(${ruta.id})">
                                                            <i class="fas fa-trash"></i> Remover
                                                        </button>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>`
                            }
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cerrar
                            </button>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                                <i class="fas fa-check"></i> Listo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal existente si lo hay
        const existingModal = document.getElementById('modalAsignarProcesos');
        if (existingModal) {
            const bootstrapModal = bootstrap.Modal.getInstance(existingModal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
            existingModal.remove();
        }

        // Insertar modal en el DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Event listener para mostrar info del proceso
        const selectProceso = document.getElementById('selectProceso');
        selectProceso.addEventListener('change', function() {
            const procesoId = this.value;
            if (procesoId) {
                const proceso = procesosDisponibles.find(p => p.id == procesoId);
                if (proceso) {
                    const infoProceso = document.getElementById('infoProceso');
                    infoProceso.innerHTML = `
                        <strong>${htmlEscape(proceso.nombre)}</strong>
                        <br>
                        <small>${htmlEscape(proceso.descripcion || 'Sin descripción')}</small>
                        <br><br>
                        <strong>Requiere Inspección QC:</strong>
                        <span class="badge ${proceso.requiere_inspeccion_calidad ? 'bg-success' : 'bg-secondary'}">
                            ${proceso.requiere_inspeccion_calidad ? 'Sí' : 'No'}
                        </span>
                    `;
                }
            } else {
                document.getElementById('infoProceso').innerHTML = '<small>Selecciona un proceso para ver más detalles</small>';
            }
        });

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalAsignarProcesos'));
        modal.show();
    }

    function actualizarTablaRutas(productoId) {
        console.log('Cargando rutas para producto:', productoId);
        fetch(`${BASE_URL}/app/controllers/producto_rutas_procesos_listar.php?producto_id=${productoId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta de listar rutas:', data);
                if (data.success) {
                    const rutasActuales = data.data;
                    console.log('Rutas actuales:', rutasActuales);

                    // Actualizar el contador en el título
                    const tituloRutas = document.querySelector('#modalAsignarProcesos .modal-body h6:last-of-type');
                    if (tituloRutas) {
                        tituloRutas.innerHTML = `
                            <i class="fas fa-route text-primary"></i> Ruta de Procesos Actual (${rutasActuales.length})
                        `;
                    }

                    // Actualizar el valor sugerido del campo orden
                    const inputOrden = document.getElementById('inputOrden');
                    if (inputOrden) {
                        inputOrden.value = rutasActuales.length + 1;
                    }

                    // Buscar el contenedor que está después del <hr> con un ID específico
                    let container = document.getElementById('contenedorRutasProcesos');
                    if (!container) {
                        // Si no existe, buscar el elemento después del hr
                        const hr = document.querySelector('#modalAsignarProcesos .modal-body hr');
                        if (hr && hr.nextElementSibling && hr.nextElementSibling.nextElementSibling) {
                            container = hr.nextElementSibling.nextElementSibling;
                            container.id = 'contenedorRutasProcesos'; // Asignar ID para próximas veces
                        }
                    }

                    if (!container) {
                        console.error('No se encontró el contenedor de rutas');
                        mostrarAlertaProcesos('Error al actualizar la vista', 'danger');
                        return;
                    }

                    if (rutasActuales.length === 0) {
                        container.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle"></i> No hay procesos asignados aún. Agrega al menos uno.
                            </div>
                        `;
                    } else {
                        container.innerHTML = `
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;">Orden</th>
                                            <th>Proceso</th>
                                            <th>Requiere QC</th>
                                            <th>Notas</th>
                                            <th style="width: 120px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRutasActuales">
                                        ${rutasActuales.map(ruta => `
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary" style="font-size: 0.95rem; padding: 0.5rem 0.75rem;">${ruta.orden_secuencia}</span>
                                                </td>
                                                <td>
                                                    <strong>${htmlEscape(ruta.proceso_nombre)}</strong>
                                                    <br>
                                                    <small>${htmlEscape(ruta.proceso_descripcion || '')}</small>
                                                </td>
                                                <td>
                                                    ${ruta.requiere_inspeccion_calidad ?
                                                        '<span class="badge bg-success">Sí</span>' :
                                                        '<span class="badge bg-secondary">No</span>'}
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" id="notas-${ruta.id}" value="${htmlEscape(ruta.notas || '')}" placeholder="Notas..." onchange="actualizarRuta(${ruta.id}, document.getElementById('notas-${ruta.id}').value)">
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" onclick="eliminarProcesoDeProducto(${ruta.id})">
                                                        <i class="fas fa-trash"></i> Remover
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                } else {
                    mostrarAlertaProcesos('Error al actualizar rutas', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlertaProcesos('Error al actualizar rutas', 'danger');
            });
    }

    window.agregarProcesoAProducto = function(productoId) {
        const procesoId = document.getElementById('selectProceso').value;
        const orden = document.getElementById('inputOrden').value;
        const notas = document.getElementById('inputNotas').value;

        if (!procesoId) {
            mostrarAlertaProcesos('Selecciona un proceso', 'warning');
            return;
        }

        if (!orden || parseInt(orden) < 1) {
            mostrarAlertaProcesos('Ingresa un orden válido (mayor a 0)', 'warning');
            return;
        }

        fetch(`${BASE_URL}/app/controllers/producto_rutas_procesos_asignar.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                producto_id: productoId,
                proceso_id: procesoId,
                orden_secuencia: orden,
                notas: notas
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta de asignar proceso:', data);
            if (data.success) {
                mostrarAlertaProcesos(data.message, 'success');
                // Recargar solo la tabla sin recrear el modal
                console.log('Actualizando tabla para producto:', productoId);
                actualizarTablaRutas(productoId);
                // Limpiar los campos del formulario
                document.getElementById('selectProceso').value = '';
                document.getElementById('inputNotas').value = '';
                document.getElementById('infoProceso').innerHTML = '<small>Selecciona un proceso para ver más detalles</small>';
            } else {
                mostrarAlertaProcesos('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error al agregar proceso:', error);
            mostrarAlertaProcesos('Error al agregar proceso', 'danger');
        });
    };

    window.actualizarRuta = function(rutaId, notas) {
        fetch(`${BASE_URL}/app/controllers/producto_rutas_procesos_actualizar.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ruta_id: rutaId,
                notas: notas
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlertaProcesos(data.message, 'success');
            } else {
                mostrarAlertaProcesos('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlertaProcesos('Error al actualizar ruta', 'danger');
        });
    };

    window.eliminarProcesoDeProducto = function(rutaId) {
        if (confirm('¿Está seguro que desea remover este proceso de la ruta?')) {
            fetch(`${BASE_URL}/app/controllers/producto_rutas_procesos_eliminar.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ruta_id: rutaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlertaProcesos(data.message, 'success');
                    // Actualizar solo la tabla sin recrear el modal
                    if (productoProcesosEnEdicion) {
                        actualizarTablaRutas(productoProcesosEnEdicion);
                    }
                } else {
                    mostrarAlertaProcesos('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlertaProcesos('Error al remover proceso', 'danger');
            });
        }
    };

    function htmlEscape(text) {
        if (!text) return '';
        return text.replace(/[&<>"']/g, function(match) {
            const escapeMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return escapeMap[match];
        });
    }

    function mostrarAlertaProcesos(mensaje, tipo) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
        alertDiv.style.zIndex = '10000';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.maxWidth = '400px';
        alertDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
})();
