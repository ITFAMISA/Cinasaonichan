// Módulo de Estaciones/Máquinas del Taller - CINASA
(function() {
    'use strict';

    let paginaActual = 1;
    let ordenActual = 'nombre';
    let direccionActual = 'ASC';
    let estacionEnEdicion = null;

    document.addEventListener('DOMContentLoaded', function() {
        cargarEstaciones();

        // Event listeners
        const btnNuevaEstacion = document.getElementById('btnNuevaEstacion');
        if (btnNuevaEstacion) {
            btnNuevaEstacion.addEventListener('click', abrirModalCrear);
        }

        const btnDashboardTaller = document.getElementById('btnDashboardTaller');
        if (btnDashboardTaller) {
            btnDashboardTaller.addEventListener('click', irAlDashboard);
        }

        const btnBuscar = document.getElementById('btnBuscar');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', () => cargarEstaciones(1));
        }

        const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
        if (btnLimpiarFiltros) {
            btnLimpiarFiltros.addEventListener('click', limpiarFiltros);
        }

        const buscarInput = document.getElementById('buscar');
        if (buscarInput) {
            buscarInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    cargarEstaciones(1);
                }
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
                cargarEstaciones(paginaActual);
            });
        });
    });

    function actualizarIconosOrden() {
        document.querySelectorAll('.sortable').forEach(th => {
            const icon = th.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-sort';
            }

            if (th.dataset.column === ordenActual) {
                if (icon) {
                    icon.className = direccionActual === 'ASC' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                }
                th.classList.add('active');
            } else {
                th.classList.remove('active');
            }
        });
    }

    function cargarEstaciones(pagina = 1) {
        paginaActual = pagina;

        const filtros = {
            buscar: document.getElementById('buscar').value,
            tipo: document.getElementById('tipo').value,
            estatus: document.getElementById('estatus').value,
            orden: ordenActual,
            direccion: direccionActual,
            page: pagina
        };

        const queryString = new URLSearchParams(filtros).toString();

        fetch(`${BASE_URL}/app/controllers/estaciones_listar.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarEstaciones(data.data);
                    mostrarPaginacion(data.pagination);
                } else {
                    mostrarAlerta('Error al cargar estaciones: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al cargar las estaciones', 'danger');
            });
    }

    function mostrarEstaciones(estaciones) {
        const tbody = document.getElementById('tablaEstaciones');

        if (estaciones.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No se encontraron estaciones</td></tr>';
            return;
        }

        tbody.innerHTML = estaciones.map(estacion => {
            const colorBadge = estacion.estatus === 'activa' ? 'bg-success' :
                             estacion.estatus === 'mantenimiento' ? 'bg-warning' : 'bg-danger';

            return `
                <tr>
                    <td><strong>${htmlEscape(estacion.nombre)}</strong></td>
                    <td>${htmlEscape(estacion.tipo)}</td>
                    <td>${htmlEscape(estacion.descripcion || '-')}</td>
                    <td>
                        <span class="badge ${colorBadge}">
                            ${estacion.estatus === 'activa' ? 'Activa' :
                              estacion.estatus === 'mantenimiento' ? 'Mantenimiento' : 'Inactiva'}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="verProcesosEstacion(${estacion.id})" title="Ver procesos">
                            <i class="fas fa-list"></i> Procesos
                        </button>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-info text-white" onclick="editarEstacion(${estacion.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarEstacion(${estacion.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        actualizarIconosOrden();
    }

    function mostrarPaginacion(pagination) {
        const container = document.getElementById('paginacion');
        const contador = document.getElementById('contador');

        contador.textContent = `Mostrando ${((pagination.page - 1) * pagination.limite) + 1} a ${Math.min(pagination.page * pagination.limite, pagination.total)} de ${pagination.total} estaciones`;

        let html = '';

        if (pagination.paginas > 1) {
            if (pagination.page > 1) {
                html += `<button class="btn btn-sm btn-outline-primary" onclick="cargarEstaciones(1)" title="Primera página"><i class="fas fa-angle-double-left"></i></button>
                         <button class="btn btn-sm btn-outline-primary" onclick="cargarEstaciones(${pagination.page - 1})" title="Página anterior"><i class="fas fa-chevron-left"></i></button>`;
            }

            const inicio = Math.max(1, pagination.page - 2);
            const fin = Math.min(pagination.paginas, pagination.page + 2);

            for (let i = inicio; i <= fin; i++) {
                html += `<button class="btn btn-sm ${i === pagination.page ? 'btn-primary' : 'btn-outline-primary'}" onclick="cargarEstaciones(${i})">${i}</button>`;
            }

            if (pagination.page < pagination.paginas) {
                html += `<button class="btn btn-sm btn-outline-primary" onclick="cargarEstaciones(${pagination.page + 1})" title="Página siguiente"><i class="fas fa-chevron-right"></i></button>
                         <button class="btn btn-sm btn-outline-primary" onclick="cargarEstaciones(${pagination.paginas})" title="Última página"><i class="fas fa-angle-double-right"></i></button>`;
            }
        }

        container.innerHTML = html;
    }

    function abrirModalCrear() {
        mostrarModalEstacion(null);
    }

    function editarEstacion(id) {
        fetch(`${BASE_URL}/app/controllers/estaciones_obtener.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalEstacion(data.data);
                } else {
                    mostrarAlerta('Error al cargar estación: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al cargar la estación', 'danger');
            });
    }

    function mostrarModalEstacion(estacionData) {
        estacionEnEdicion = estacionData;

        const titulo = estacionData ? 'Editar Estación' : 'Nueva Estación';
        const idValue = estacionData ? estacionData.id : '';
        const nombreValue = estacionData ? htmlEscape(estacionData.nombre) : '';
        const tipoValue = estacionData ? htmlEscape(estacionData.tipo) : '';
        const naveValue = estacionData ? htmlEscape(estacionData.nave || 'Nave 1') : 'Nave 1';
        const estatusValue = estacionData ? estacionData.estatus : 'activa';
        const descripcionValue = estacionData ? htmlEscape(estacionData.descripcion || '') : '';
        const xValue = estacionData ? estacionData.ubicacion_x : 0;
        const yValue = estacionData ? estacionData.ubicacion_y : 0;
        const anchoValue = estacionData ? estacionData.ancho : 50;
        const altoValue = estacionData ? estacionData.alto : 50;
        const colorValue = estacionData ? estacionData.color : '#4CAF50';
        const observacionesValue = estacionData ? htmlEscape(estacionData.observaciones || '') : '';

        const modalHTML = `
            <div class="modal fade" id="modalEstacion" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${titulo}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formEstacion">
                                <input type="hidden" id="estacionId" name="id" value="${idValue}">

                                <div class="mb-3">
                                    <label for="estacionNombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="estacionNombre" name="nombre" value="${nombreValue}" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estacionTipo" class="form-label">Tipo *</label>
                                            <input type="text" class="form-control" id="estacionTipo" name="tipo" value="${tipoValue}" placeholder="Ej: Corte, Soldadura, etc" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estacionNave" class="form-label">Nave *</label>
                                            <input type="text" class="form-control" id="estacionNave" name="nave" value="${naveValue}" placeholder="Ej: Nave 1, Nave 2, etc" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estacionEstatus" class="form-label">Estatus *</label>
                                            <select class="form-select" id="estacionEstatus" name="estatus" required>
                                                <option value="activa" ${estatusValue === 'activa' ? 'selected' : ''}>Activa</option>
                                                <option value="mantenimiento" ${estatusValue === 'mantenimiento' ? 'selected' : ''}>Mantenimiento</option>
                                                <option value="inactiva" ${estatusValue === 'inactiva' ? 'selected' : ''}>Inactiva</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estacionColor" class="form-label">Color</label>
                                            <input type="color" class="form-control form-control-color" id="estacionColor" name="color" value="${colorValue}">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="estacionDescripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="estacionDescripcion" name="descripcion" rows="3">${descripcionValue}</textarea>
                                </div>


                                <div class="mb-3">
                                    <label for="estacionObservaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="estacionObservaciones" name="observaciones" rows="2">${observacionesValue}</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="guardarEstacion()">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal existente si lo hay
        const existingModal = document.getElementById('modalEstacion');
        if (existingModal) {
            const bootstrapModal = bootstrap.Modal.getInstance(existingModal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
            existingModal.remove();
        }

        // Insertar modal en el DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Agregar event listener al formulario
        const formEstacion = document.getElementById('formEstacion');
        if (formEstacion) {
            formEstacion.addEventListener('submit', function(e) {
                e.preventDefault();
                guardarEstacion();
            });
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalEstacion'));
        modal.show();
    }

    function guardarEstacion() {
        const id = document.getElementById('estacionId').value;
        const formData = new FormData(document.getElementById('formEstacion'));

        const url = id ?
            `${BASE_URL}/app/controllers/estaciones_editar.php` :
            `${BASE_URL}/app/controllers/estaciones_crear.php`;

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEstacion'));
                if (modal) modal.hide();
                cargarEstaciones(paginaActual);
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al guardar la estación', 'danger');
        });
    }

    function eliminarEstacion(id) {
        if (!confirm('¿Está seguro de que desea desactivar esta estación?')) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);

        fetch(`${BASE_URL}/app/controllers/estaciones_eliminar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                cargarEstaciones(paginaActual);
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al desactivar la estación', 'danger');
        });
    }

    function verProcesosEstacion(id) {
        // Primero cargar procesos asignados
        Promise.all([
            fetch(`${BASE_URL}/app/controllers/estacion_procesos_listar.php?estacion_id=${id}`).then(r => r.json()),
            fetch(`${BASE_URL}/app/controllers/procesos_listar.php`).then(r => r.json())
        ])
        .then(([asignados, disponibles]) => {
            if (asignados.success && disponibles.success) {
                mostrarProcesosAsignados(asignados.data, disponibles.data, id);
            } else {
                mostrarAlerta('Error al cargar procesos: ' + (asignados.message || disponibles.message), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al cargar procesos', 'danger');
        });
    }

    function mostrarProcesosAsignados(procesosAsignados, procesosDisponibles, estacionId) {
        // Obtener estación actual para el título
        fetch(`${BASE_URL}/app/controllers/estaciones_obtener.php?id=${estacionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const estacion = data.data;
                    mostrarModalProcesos(procesosAsignados, procesosDisponibles, estacionId, estacion.nombre);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function mostrarModalProcesos(procesosAsignados, procesosDisponibles, estacionId, nombreEstacion) {
        // Obtener IDs de procesos ya asignados
        const asignados = procesosAsignados.map(p => p.proceso_id);

        // Filtrar procesos disponibles (que no estén asignados)
        const procesosSinAsignar = procesosDisponibles.filter(p => !asignados.includes(p.id));

        // Construir HTML de procesos asignados
        let htmlAsignados = '';
        if (procesosAsignados.length === 0) {
            htmlAsignados = '<p class="text-muted text-center py-4">Sin procesos asignados</p>';
        } else {
            htmlAsignados = '<div class="list-group">';
            procesosAsignados.forEach(p => {
                htmlAsignados += `
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${htmlEscape(p.proceso_nombre)}</h6>
                                <small class="text-muted d-block">${htmlEscape(p.proceso_descripcion || '-')}</small>
                                <small class="d-block mt-1">
                                    <span class="badge bg-primary">Orden: ${p.orden_preferencia}</span>
                                    ${p.es_preferida ? '<span class="badge bg-success ms-1">Preferida</span>' : ''}
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProcesoEstacion(${estacionId}, ${p.proceso_id})" title="Eliminar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            htmlAsignados += '</div>';
        }

        // Construir dropdown de procesos disponibles
        let optionsProcesos = '<option value="">-- Seleccionar proceso --</option>';
        procesosSinAsignar.forEach(p => {
            optionsProcesos += `<option value="${p.id}">${htmlEscape(p.nombre)}</option>`;
        });

        // Modal 1: Ver procesos asignados
        const modalAsignados = `
            <div class="modal fade" id="modalProcesosAsignados" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Procesos Asignados - ${htmlEscape(nombreEstacion)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${htmlAsignados}
                        </div>
                        <div class="modal-footer">
                            ${procesosSinAsignar.length > 0 ?
                                '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="mostrarModalAgregarProceso(' + estacionId + ')">Agregar Proceso</button>' :
                                ''}
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Modal 2: Agregar proceso
        const modalAgregar = `
            <div class="modal fade" id="modalAgregarProceso" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Agregar Proceso - ${htmlEscape(nombreEstacion)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${procesosSinAsignar.length === 0 ?
                                '<p class="text-muted text-center py-4">Todos los procesos están asignados a esta estación</p>' :
                                `<form id="formAgregarProceso">
                                    <input type="hidden" id="procEstacionId" name="estacion_id" value="${estacionId}">

                                    <div class="mb-3">
                                        <label for="procProceso" class="form-label">Proceso *</label>
                                        <select class="form-select" id="procProceso" name="proceso_id" required>
                                            ${optionsProcesos}
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="procOrden" class="form-label">Orden de Preferencia</label>
                                                <input type="number" class="form-control" id="procOrden" name="orden_preferencia" value="999" min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <input type="checkbox" id="procPreferida" name="es_preferida" value="1">
                                                    <span class="ms-2">Marcar como preferida</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="procNotas" class="form-label">Notas</label>
                                        <textarea class="form-control" id="procNotas" name="notas" rows="2" placeholder="Notas adicionales sobre esta asignación..."></textarea>
                                    </div>
                                </form>`
                            }
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            ${procesosSinAsignar.length > 0 ?
                                '<button type="button" class="btn btn-primary" onclick="agregarProcesoEstacion()">Agregar Proceso</button>' :
                                ''}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modales existentes
        const modalAsignEx = document.getElementById('modalProcesosAsignados');
        if (modalAsignEx) {
            const bootstrapModal = bootstrap.Modal.getInstance(modalAsignEx);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
            modalAsignEx.remove();
        }

        const modalAgrEx = document.getElementById('modalAgregarProceso');
        if (modalAgrEx) {
            const bootstrapModal = bootstrap.Modal.getInstance(modalAgrEx);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
            modalAgrEx.remove();
        }

        // Insertar modales en el DOM
        document.body.insertAdjacentHTML('beforeend', modalAsignados);
        document.body.insertAdjacentHTML('beforeend', modalAgregar);

        // Mostrar modal de asignados
        const modal = new bootstrap.Modal(document.getElementById('modalProcesosAsignados'));
        modal.show();
    }

    function mostrarModalAgregarProceso(estacionId) {
        const modal = new bootstrap.Modal(document.getElementById('modalAgregarProceso'));
        modal.show();
    }

    function agregarProcesoEstacion() {
        const estacionId = document.getElementById('procEstacionId').value;
        const procesoId = document.getElementById('procProceso').value;

        if (!procesoId) {
            mostrarAlerta('Por favor seleccione un proceso', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('estacion_id', estacionId);
        formData.append('proceso_id', procesoId);
        formData.append('orden_preferencia', document.getElementById('procOrden').value);
        formData.append('es_preferida', document.getElementById('procPreferida').checked ? 1 : 0);
        formData.append('notas', document.getElementById('procNotas').value);

        fetch(`${BASE_URL}/app/controllers/estacion_procesos_asignar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Proceso asignado exitosamente', 'success');
                verProcesosEstacion(estacionId);
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al asignar proceso', 'danger');
        });
    }

    function eliminarProcesoEstacion(estacionId, procesoId) {
        if (!confirm('¿Está seguro de que desea desasignar este proceso?')) {
            return;
        }

        const formData = new FormData();
        formData.append('estacion_id', estacionId);
        formData.append('proceso_id', procesoId);

        fetch(`${BASE_URL}/app/controllers/estacion_procesos_eliminar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Proceso desasignado exitosamente', 'success');
                verProcesosEstacion(estacionId);
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al desasignar proceso', 'danger');
        });
    }

    function irAlDashboard() {
        window.location.href = `${BASE_URL}/dashboard_taller.php`;
    }

    function limpiarFiltros() {
        document.getElementById('buscar').value = '';
        document.getElementById('tipo').value = '';
        document.getElementById('estatus').value = '';
        cargarEstaciones(1);
    }

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

    function mostrarAlerta(mensaje, tipo = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.insertBefore(alertDiv, document.body.firstChild);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Funciones globales para acceso desde HTML
    window.cargarEstaciones = cargarEstaciones;
    window.editarEstacion = editarEstacion;
    window.eliminarEstacion = eliminarEstacion;
    window.verProcesosEstacion = verProcesosEstacion;
    window.mostrarModalAgregarProceso = mostrarModalAgregarProceso;
    window.agregarProcesoEstacion = agregarProcesoEstacion;
    window.eliminarProcesoEstacion = eliminarProcesoEstacion;
    window.mostrarAlerta = mostrarAlerta;
    window.htmlEscape = htmlEscape;
    window.guardarEstacion = guardarEstacion;
    window.abrirModalCrear = abrirModalCrear;
})();
