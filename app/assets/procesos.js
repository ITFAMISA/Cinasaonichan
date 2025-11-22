// Módulo de Procesos de Producción - CINASA
(function() {
    'use strict';

    let paginaActual = 1;
    let ordenActual = 'nombre';
    let direccionActual = 'ASC';
    let procesoEnEdicion = null;

    document.addEventListener('DOMContentLoaded', function() {
        cargarProcesos();

        // Event listeners
        const btnNuevoProceso = document.getElementById('btnNuevoProceso');
        if (btnNuevoProceso) {
            btnNuevoProceso.addEventListener('click', abrirModalCrear);
        }

        const btnExportarCSV = document.getElementById('btnExportarCSV');
        if (btnExportarCSV) {
            btnExportarCSV.addEventListener('click', exportarProcesosCSV);
        }

        const btnBuscar = document.getElementById('btnBuscar');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', () => cargarProcesos(1));
        }

        const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
        if (btnLimpiarFiltros) {
            btnLimpiarFiltros.addEventListener('click', limpiarFiltros);
        }

        const buscarInput = document.getElementById('buscar');
        if (buscarInput) {
            buscarInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    cargarProcesos(1);
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
                cargarProcesos(paginaActual);
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

    function cargarProcesos(pagina = 1) {
        paginaActual = pagina;

        const filtros = {
            buscar: document.getElementById('buscar').value,
            estatus: document.getElementById('estatus').value,
            orden: ordenActual,
            direccion: direccionActual,
            page: pagina
        };

        const queryString = new URLSearchParams(filtros).toString();

        fetch(`${BASE_URL}/app/controllers/procesos_listar.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarProcesos(data.data);
                    mostrarPaginacion(data.pagination);
                } else {
                    mostrarAlerta('Error al cargar procesos: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al cargar los procesos', 'danger');
            });
    }

    function mostrarProcesos(procesos) {
        const tbody = document.getElementById('tablaProcesos');

        if (procesos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No se encontraron procesos</td></tr>';
            return;
        }

        tbody.innerHTML = procesos.map(proceso => `
            <tr>
                <td><strong>${htmlEscape(proceso.nombre)}</strong></td>
                <td>${htmlEscape(proceso.descripcion || '')}</td>
                <td>
                    ${proceso.requiere_inspeccion_calidad ?
                        '<span class="badge bg-success"><i class="fas fa-check"></i> Sí</span>' :
                        '<span class="badge bg-secondary"><i class="fas fa-times"></i> No</span>'}
                </td>
                <td>
                    <span class="badge ${proceso.estatus === 'activo' ? 'bg-success' : 'bg-danger'}">
                        ${proceso.estatus === 'activo' ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>
                    <small class="text-muted">${new Date(proceso.fecha_creacion).toLocaleDateString()}</small>
                </td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-info text-white" onclick="verDetalleProceso(${proceso.id})" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="editarProceso(${proceso.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProceso(${proceso.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        actualizarIconosOrden();
    }

    function mostrarPaginacion(pagination) {
        const paginacionDiv = document.getElementById('paginacion');
        const contadorDiv = document.getElementById('contador');

        contadorDiv.textContent = `Mostrando ${((pagination.page - 1) * pagination.limite) + 1} a ${Math.min(pagination.page * pagination.limite, pagination.total)} de ${pagination.total} procesos`;

        if (pagination.paginas <= 1) {
            paginacionDiv.innerHTML = '';
            return;
        }

        let html = '<nav><ul class="pagination justify-content-center mb-0">';

        // Botón anterior
        if (pagination.page > 1) {
            html += `<li class="page-item"><a class="page-link" onclick="cargarProcesos(1)">Primera</a></li>`;
            html += `<li class="page-item"><a class="page-link" onclick="cargarProcesos(${pagination.page - 1})">Anterior</a></li>`;
        }

        // Números de página
        for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.paginas, pagination.page + 2); i++) {
            if (i === pagination.page) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" onclick="cargarProcesos(${i})">${i}</a></li>`;
            }
        }

        // Botón siguiente
        if (pagination.page < pagination.paginas) {
            html += `<li class="page-item"><a class="page-link" onclick="cargarProcesos(${pagination.page + 1})">Siguiente</a></li>`;
            html += `<li class="page-item"><a class="page-link" onclick="cargarProcesos(${pagination.paginas})">Última</a></li>`;
        }

        html += '</ul></nav>';
        paginacionDiv.innerHTML = html;
    }

    function abrirModalCrear() {
        procesoEnEdicion = null;
        mostrarModalProceso(null);
    }

    function mostrarModalProceso(procesoData) {
        const isEdit = procesoData !== null;
        const titulo = isEdit ? 'Editar Proceso' : 'Nuevo Proceso';
        const procesoId = isEdit ? (procesoData.id || '') : '';
        const estatusValue = isEdit && procesoData ? procesoData.estatus : 'activo';

        // Crear opciones del select dinámicamente
        const opcionesSelect = `
            <option value="activo" ${estatusValue === 'activo' ? 'selected' : ''}>Activo</option>
            <option value="inactivo" ${estatusValue === 'inactivo' ? 'selected' : ''}>Inactivo</option>
        `;

        const modalHTML = `
            <div class="modal fade" id="modalProceso" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${titulo}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formProceso">
                                <input type="hidden" id="procesoId" value="${procesoId}">

                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre del Proceso *</label>
                                    <input type="text" class="form-control" id="nombre" required placeholder="ej: Corte, Doblez, Maquinado" value="${isEdit ? htmlEscape(procesoData.nombre) : ''}">
                                </div>

                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" rows="3" placeholder="Descripción detallada del proceso">${isEdit ? htmlEscape(procesoData.descripcion || '') : ''}</textarea>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="requiereInspeccion" ${isEdit && procesoData.requiere_inspeccion_calidad ? 'checked' : ''}>
                                    <label class="form-check-label" for="requiereInspeccion">
                                        Requiere Inspección de Calidad
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label for="estatusModal" class="form-label">Estatus</label>
                                    <select class="form-select" id="estatusModal" required>
                                        ${opcionesSelect}
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="btnGuardarProceso">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal existente si lo hay
        const existingModal = document.getElementById('modalProceso');
        if (existingModal) {
            const bootstrapModal = bootstrap.Modal.getInstance(existingModal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
            existingModal.remove();
        }

        // Insertar modal en el DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Pequeño delay para asegurar que el DOM está completamente actualizado
        setTimeout(() => {
            // Configurar evento del botón guardar
            document.getElementById('btnGuardarProceso').addEventListener('click', guardarProceso);

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalProceso'));
            modal.show();
        }, 0);
    }

    window.verDetalleProceso = function(id) {
        // Cargar detalles del proceso
        fetch(`${BASE_URL}/app/controllers/procesos_listar.php?buscar=&estatus=&orden=id&direccion=ASC&page=1&limite=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const proceso = data.data.find(p => p.id == id);
                    if (proceso) {
                        mostrarModalDetalleProceso(proceso);
                    } else {
                        mostrarAlerta('No se encontró el proceso', 'danger');
                    }
                } else {
                    mostrarAlerta('Error al cargar proceso', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al cargar proceso', 'danger');
            });
    };

    window.editarProceso = function(id) {
        // Usar una consulta de todas las páginas para encontrar el proceso
        fetch(`${BASE_URL}/app/controllers/procesos_listar.php?buscar=&estatus=&orden=id&direccion=ASC&page=1&limite=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const proceso = data.data.find(p => p.id == id);
                    if (proceso) {
                        procesoEnEdicion = id;
                        mostrarModalProceso(proceso);
                    } else {
                        mostrarAlerta('No se encontró el proceso', 'danger');
                    }
                } else {
                    mostrarAlerta('Error al cargar proceso', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al cargar proceso', 'danger');
            });
    };

    window.eliminarProceso = function(id) {
        if (confirm('¿Está seguro que desea eliminar este proceso?')) {
            fetch(`${BASE_URL}/app/controllers/procesos_eliminar.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta('Proceso eliminado correctamente', 'success');
                    cargarProcesos(paginaActual);
                } else {
                    mostrarAlerta('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al eliminar el proceso', 'danger');
            });
        }
    };

    function guardarProceso() {
        const id = document.getElementById('procesoId').value;
        const nombre = document.getElementById('nombre').value.trim();
        const descripcion = document.getElementById('descripcion').value.trim();
        const requiereInspeccion = document.getElementById('requiereInspeccion').checked ? 1 : 0;
        let estatus = document.getElementById('estatusModal').value.trim();

        // Si estatus está vacío, usar "activo" por defecto
        if (!estatus) {
            estatus = 'activo';
            document.getElementById('estatusModal').value = 'activo';
        }

        if (!nombre) {
            mostrarAlerta('El nombre del proceso es requerido', 'warning');
            return;
        }

        const datos = {
            nombre: nombre,
            descripcion: descripcion,
            requiere_inspeccion_calidad: requiereInspeccion,
            estatus: estatus
        };

        if (id) {
            datos.id = id;
        }

        const url = id ?
            `${BASE_URL}/app/controllers/procesos_editar.php` :
            `${BASE_URL}/app/controllers/procesos_crear.php`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalProceso')).hide();
                cargarProcesos(paginaActual);
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al guardar el proceso', 'danger');
        });
    }

    function limpiarFiltros() {
        document.getElementById('buscar').value = '';
        document.getElementById('estatus').value = '';
        ordenActual = 'nombre';
        direccionActual = 'ASC';
        cargarProcesos(1);
    }

    function mostrarModalDetalleProceso(proceso) {
        const modalHTML = `
            <div class="modal fade" id="modalDetalle" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-cogs mr-2"></i>Detalle del Proceso
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #64748b; font-size: 0.875rem; text-transform: uppercase;">Nombre</label>
                                    <p class="form-control-plaintext">${htmlEscape(proceso.nombre)}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #64748b; font-size: 0.875rem; text-transform: uppercase;">Estatus</label>
                                    <p class="form-control-plaintext">
                                        <span class="badge ${proceso.estatus === 'activo' ? 'bg-success' : 'bg-danger'}">
                                            ${proceso.estatus === 'activo' ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label" style="font-weight: 600; color: #64748b; font-size: 0.875rem; text-transform: uppercase;">Descripción</label>
                                    <p class="form-control-plaintext">${htmlEscape(proceso.descripcion || 'N/A')}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #64748b; font-size: 0.875rem; text-transform: uppercase;">Requiere Inspección QC</label>
                                    <p class="form-control-plaintext">
                                        ${proceso.requiere_inspeccion_calidad ?
                                            '<span class="badge bg-success"><i class="fas fa-check"></i> Sí</span>' :
                                            '<span class="badge bg-secondary"><i class="fas fa-times"></i> No</span>'}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 600; color: #64748b; font-size: 0.875rem; text-transform: uppercase;">Fecha de Creación</label>
                                    <p class="form-control-plaintext">${new Date(proceso.fecha_creacion).toLocaleDateString()}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal existente si lo hay
        const existingModal = document.getElementById('modalDetalle');
        if (existingModal) {
            const bootstrapModal = bootstrap.Modal.getInstance(existingModal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
            existingModal.remove();
        }

        // Insertar modal en el DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
        modal.show();
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

    function mostrarAlerta(mensaje, tipo) {
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

    function exportarProcesosCSV() {
        const filtros = {
            buscar: document.getElementById('buscar').value,
            estatus: document.getElementById('estatus').value,
            orden: ordenActual,
            direccion: direccionActual,
            page: 1,
            limite: 10000
        };

        const queryString = new URLSearchParams(filtros).toString();

        fetch(`${BASE_URL}/app/controllers/procesos_listar.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    generarCSV(data.data);
                    mostrarAlerta('Procesos exportados correctamente', 'success');
                } else {
                    mostrarAlerta('No hay procesos para exportar', 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al exportar procesos', 'danger');
            });
    }

    function generarCSV(procesos) {
        const headers = ['ID', 'Nombre', 'Descripción', 'Requiere QC', 'Estatus', 'Fecha Creación'];
        const rows = procesos.map(p => [
            p.id,
            p.nombre,
            p.descripcion || '',
            p.requiere_inspeccion_calidad ? 'Sí' : 'No',
            p.estatus,
            new Date(p.fecha_creacion).toLocaleDateString()
        ]);

        let csv = headers.join(',') + '\n';
        rows.forEach(row => {
            csv += row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `procesos_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Exposición de funciones para el scope global
    window.cargarProcesos = cargarProcesos;
    window.limpiarFiltros = limpiarFiltros;
})();
