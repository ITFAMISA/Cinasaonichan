// Módulo de Empleados - CINASA
(function() {
    'use strict';

    let paginaActual = 1;
    let ordenActual = 'apellido';
    let direccionActual = 'ASC';

    document.addEventListener('DOMContentLoaded', function() {
        cargarEmpleados();

        // Event listeners
        document.getElementById('btnNuevoEmpleado').addEventListener('click', abrirModalCrear);
        document.getElementById('btnBuscar').addEventListener('click', () => cargarEmpleados(1));
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

        const buscarInput = document.getElementById('buscar');
        if (buscarInput) {
            buscarInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    cargarEmpleados(1);
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
                cargarEmpleados(paginaActual);
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

    function cargarEmpleados(pagina = 1) {
        paginaActual = pagina;

        const filtros = {
            buscar: document.getElementById('buscar').value,
            estatus_empleado: document.getElementById('estatus_empleado').value,
            departamento: document.getElementById('departamento').value,
            puesto: document.getElementById('puesto').value,
            orden: ordenActual,
            direccion: direccionActual,
            pagina: pagina
        };

        const queryString = new URLSearchParams(filtros).toString();

        fetch(`${BASE_URL}/app/controllers/empleados_listar.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarEmpleados(data.data);
                    mostrarPaginacion(data.pagination);
                } else {
                    mostrarError('Error al cargar empleados: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar empleados');
            });
    }

    function mostrarEmpleados(empleados) {
        const tbody = document.getElementById('tablaEmpleados');

        if (empleados.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No se encontraron empleados</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';
        empleados.forEach(empleado => {
            const tr = document.createElement('tr');

            const estatusClass = `badge-${empleado.estatus_empleado || 'primary'}`;
            const estatusText = empleado.estatus_empleado
                ? empleado.estatus_empleado.charAt(0).toUpperCase() + empleado.estatus_empleado.slice(1)
                : 'Activo';

            tr.innerHTML = `
                <td>${escapeHtml(empleado.apellido || 'N/A')}</td>
                <td>${escapeHtml(empleado.nombre || 'N/A')}</td>
                <td>${escapeHtml(empleado.puesto || 'N/A')}</td>
                <td>${escapeHtml(empleado.departamento || 'N/A')}</td>
                <td>
                    <a href="mailto:${escapeHtml(empleado.correo || '')}" title="${escapeHtml(empleado.correo || '')}">
                        ${escapeHtml(empleado.correo || 'N/A')}
                    </a>
                </td>
                <td><span class="badge ${estatusClass}">${estatusText}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-info text-white" onclick="window.verDetalleEmpleado(${empleado.id})" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="window.editarEmpleado(${empleado.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="window.confirmarEliminarEmpleado(${empleado.id}, '${escapeHtml(empleado.nombre + ' ' + empleado.apellido)}')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
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

    function mostrarPaginacion(pagination) {
        const div = document.getElementById('paginacion');
        const contador = document.getElementById('contador');

        if (contador) {
            contador.textContent = `Mostrando ${pagination.total} empleado${pagination.total !== 1 ? 's' : ''}`;
        }

        if (pagination.total_paginas <= 1) {
            div.innerHTML = '';
            return;
        }

        let html = '<nav><ul class="pagination pagination-sm mb-0">';

        // Botón anterior
        if (pagination.pagina_actual > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cargarEmpleados(${pagination.pagina_actual - 1}); return false;">Anterior</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
        }

        // Números de página
        for (let i = 1; i <= pagination.total_paginas; i++) {
            if (i === pagination.pagina_actual) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else if (i === 1 || i === pagination.total_paginas || (i >= pagination.pagina_actual - 2 && i <= pagination.pagina_actual + 2)) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cargarEmpleados(${i}); return false;">${i}</a></li>`;
            } else if (i === pagination.pagina_actual - 3 || i === pagination.pagina_actual + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Botón siguiente
        if (pagination.pagina_actual < pagination.total_paginas) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cargarEmpleados(${pagination.pagina_actual + 1}); return false;">Siguiente</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Siguiente</span></li>';
        }

        html += '</ul></nav>';
        div.innerHTML = html;
    }

    function limpiarFiltros() {
        document.getElementById('buscar').value = '';
        document.getElementById('estatus_empleado').value = '';
        document.getElementById('departamento').value = '';
        document.getElementById('puesto').value = '';
        cargarEmpleados(1);
    }

    function abrirModalCrear() {
        mostrarModal('Nuevo Empleado', null);
    }

    function mostrarModal(titulo, empleadoId) {
        // Crear modal dinámicamente
        let html = `
            <div class="modal fade" id="modalEmpleado" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${escapeHtml(titulo)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                            <form id="formEmpleado">
                                <!-- INFORMACIÓN GENERAL -->
                                <div class="mb-3">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Información General</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="apellido" name="apellido" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="puesto" class="form-label">Puesto <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="puesto" name="puesto" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="numero_empleado" class="form-label">Número Empleado</label>
                                        <input type="text" class="form-control" id="numero_empleado" name="numero_empleado">
                                    </div>
                                </div>

                                <!-- CONTACTO -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Información de Contacto</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="correo" class="form-label">Correo</label>
                                        <input type="email" class="form-control" id="correo" name="correo">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telefono_extension" class="form-label">Extensión</label>
                                        <input type="text" class="form-control" id="telefono_extension" name="telefono_extension">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <input type="text" class="form-control" id="direccion" name="direccion">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="estado" class="form-label">Estado</label>
                                        <input type="text" class="form-control" id="estado" name="estado">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="codigo_postal" class="form-label">CP</label>
                                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal">
                                    </div>
                                </div>

                                <!-- INFORMACIÓN PERSONAL -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Información Personal</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="fecha_nacimiento" class="form-label">Fecha Nacimiento</label>
                                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="genero" class="form-label">Género</label>
                                        <select class="form-select" id="genero" name="genero">
                                            <option value="">Seleccionar</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="estado_civil" class="form-label">Estado Civil</label>
                                        <select class="form-select" id="estado_civil" name="estado_civil">
                                            <option value="">Seleccionar</option>
                                            <option value="Soltero">Soltero</option>
                                            <option value="Casado">Casado</option>
                                            <option value="Divorciado">Divorciado</option>
                                            <option value="Viudo">Viudo</option>
                                            <option value="Unión Libre">Unión Libre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cantidad_dependientes" class="form-label">Dependientes</label>
                                        <input type="number" class="form-control" id="cantidad_dependientes" name="cantidad_dependientes" min="0">
                                    </div>
                                </div>

                                <!-- IDENTIFICACIÓN -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Identificación</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="tipo_identificacion" class="form-label">Tipo ID</label>
                                        <input type="text" class="form-control" id="tipo_identificacion" name="tipo_identificacion" placeholder="RFC, INE, Pasaporte, etc.">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="numero_identificacion" class="form-label">Número</label>
                                        <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="numero_seguro_social" class="form-label">Seguro Social</label>
                                        <input type="text" class="form-control" id="numero_seguro_social" name="numero_seguro_social">
                                    </div>
                                </div>

                                <!-- INFORMACIÓN BANCARIA -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Información Bancaria</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="banco" class="form-label">Banco</label>
                                        <input type="text" class="form-control" id="banco" name="banco">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cuenta_bancaria" class="form-label">Cuenta</label>
                                        <input type="text" class="form-control" id="cuenta_bancaria" name="cuenta_bancaria">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="clabe" class="form-label">CLABE</label>
                                        <input type="text" class="form-control" id="clabe" name="clabe" placeholder="18 dígitos">
                                    </div>
                                </div>

                                <!-- INFORMACIÓN LABORAL -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Información Laboral</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="departamento" class="form-label">Departamento</label>
                                        <input type="text" class="form-control" id="departamento" name="departamento">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fecha_ingreso" class="form-label">Fecha Ingreso</label>
                                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="salario_base" class="form-label">Salario Base</label>
                                        <input type="number" class="form-control" id="salario_base" name="salario_base" step="0.01">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tipo_contrato" class="form-label">Tipo Contrato</label>
                                        <input type="text" class="form-control" id="tipo_contrato" name="tipo_contrato" placeholder="Ej: Tiempo Indeterminado">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="fecha_contrato" class="form-label">Fecha Contrato</label>
                                        <input type="date" class="form-control" id="fecha_contrato" name="fecha_contrato">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="supervisor_directo_id" class="form-label">Supervisor Directo</label>
                                        <input type="number" class="form-control" id="supervisor_directo_id" name="supervisor_directo_id">
                                    </div>
                                </div>

                                <!-- CONTACTO DE EMERGENCIA -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Contacto de Emergencia</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="contacto_emergencia_nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="contacto_emergencia_nombre" name="contacto_emergencia_nombre">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contacto_emergencia_relacion" class="form-label">Relación</label>
                                        <input type="text" class="form-control" id="contacto_emergencia_relacion" name="contacto_emergencia_relacion" placeholder="Ej: Esposa, Padre, etc.">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="contacto_emergencia_telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="contacto_emergencia_telefono" name="contacto_emergencia_telefono">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="estatus_empleado" class="form-label">Estatus</label>
                                        <select class="form-select" id="estatus_empleado" name="estatus_empleado">
                                            <option value="activo">Activo</option>
                                            <option value="inactivo">Inactivo</option>
                                            <option value="licencia">Licencia</option>
                                            <option value="suspendido">Suspendido</option>
                                            <option value="jubilado">Jubilado</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- EDUCACIÓN Y OTROS -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Educación y Otros</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nivel_escolaridad" class="form-label">Nivel Escolaridad</label>
                                        <input type="text" class="form-control" id="nivel_escolaridad" name="nivel_escolaridad">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="especialidad" class="form-label">Especialidad</label>
                                        <input type="text" class="form-control" id="especialidad" name="especialidad">
                                    </div>
                                </div>

                                <!-- OBSERVACIONES -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Observaciones</h6>
                                </div>

                                <div class="mb-3">
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Notas adicionales..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="btnGuardarEmpleado">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal anterior si existe
        const modalAnterior = document.getElementById('modalEmpleado');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        document.body.insertAdjacentHTML('beforeend', html);

        const modal = new bootstrap.Modal(document.getElementById('modalEmpleado'));

        // Agregar listener al botón guardar
        document.getElementById('btnGuardarEmpleado').addEventListener('click', function() {
            guardarEmpleado(empleadoId);
        });

        // Si es edición, cargar datos con un pequeño delay para asegurar que el DOM está listo
        if (empleadoId) {
            setTimeout(() => {
                cargarDatosEmpleado(empleadoId);
            }, 100);
        }

        modal.show();
    }

    function cargarDatosEmpleado(id) {
        fetch(`${BASE_URL}/app/controllers/empleados_detalle.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const empleado = data.data;
                    // Información General
                    document.getElementById('nombre').value = empleado.nombre || '';
                    document.getElementById('apellido').value = empleado.apellido || '';
                    document.getElementById('puesto').value = empleado.puesto || '';
                    document.getElementById('numero_empleado').value = empleado.numero_empleado || '';

                    // Contacto
                    document.getElementById('correo').value = empleado.correo || '';
                    document.getElementById('telefono').value = empleado.telefono || '';
                    document.getElementById('telefono_extension').value = empleado.telefono_extension || '';
                    document.getElementById('direccion').value = empleado.direccion || '';
                    document.getElementById('ciudad').value = empleado.ciudad || '';
                    document.getElementById('estado').value = empleado.estado || '';
                    document.getElementById('codigo_postal').value = empleado.codigo_postal || '';

                    // Personal
                    document.getElementById('fecha_nacimiento').value = empleado.fecha_nacimiento || '';
                    document.getElementById('genero').value = empleado.genero || '';
                    document.getElementById('estado_civil').value = empleado.estado_civil || '';
                    document.getElementById('cantidad_dependientes').value = empleado.cantidad_dependientes || 0;

                    // Identificación
                    document.getElementById('tipo_identificacion').value = empleado.tipo_identificacion || '';
                    document.getElementById('numero_identificacion').value = empleado.numero_identificacion || '';
                    document.getElementById('numero_seguro_social').value = empleado.numero_seguro_social || '';

                    // Bancaria
                    document.getElementById('banco').value = empleado.banco || '';
                    document.getElementById('cuenta_bancaria').value = empleado.cuenta_bancaria || '';
                    document.getElementById('clabe').value = empleado.clabe || '';

                    // Laboral
                    document.getElementById('departamento').value = empleado.departamento || '';
                    document.getElementById('fecha_ingreso').value = empleado.fecha_ingreso || '';
                    document.getElementById('salario_base').value = empleado.salario_base || '';
                    document.getElementById('tipo_contrato').value = empleado.tipo_contrato || '';
                    document.getElementById('fecha_contrato').value = empleado.fecha_contrato || '';
                    document.getElementById('supervisor_directo_id').value = empleado.supervisor_directo_id || '';

                    // Emergencia
                    document.getElementById('contacto_emergencia_nombre').value = empleado.contacto_emergencia_nombre || '';
                    document.getElementById('contacto_emergencia_relacion').value = empleado.contacto_emergencia_relacion || '';
                    document.getElementById('contacto_emergencia_telefono').value = empleado.contacto_emergencia_telefono || '';

                    // Educación
                    document.getElementById('nivel_escolaridad').value = empleado.nivel_escolaridad || '';
                    document.getElementById('especialidad').value = empleado.especialidad || '';

                    // Estatus y Observaciones
                    document.getElementById('estatus_empleado').value = empleado.estatus_empleado || 'activo';
                    document.getElementById('observaciones').value = empleado.observaciones || '';
                } else {
                    mostrarError('Error al cargar los datos del empleado');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar los datos del empleado');
            });
    }

    function guardarEmpleado(empleadoId) {
        const form = document.getElementById('formEmpleado');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const datos = new FormData(form);
        const url = empleadoId
            ? `${BASE_URL}/app/controllers/empleados_editar.php?id=${empleadoId}`
            : `${BASE_URL}/app/controllers/empleados_crear.php`;
        const metodo = empleadoId ? 'POST' : 'POST';

        fetch(url, {
            method: metodo,
            body: JSON.stringify(Object.fromEntries(datos))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarExito(data.message || 'Empleado guardado correctamente');
                bootstrap.Modal.getInstance(document.getElementById('modalEmpleado')).hide();
                cargarEmpleados(paginaActual);
            } else {
                mostrarError('Error: ' + (data.message || 'Error al guardar'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al guardar');
        });
    }

    function mostrarError(mensaje) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${escapeHtml(mensaje)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector('.filter-section');
        if (container) {
            container.parentNode.insertBefore(alertDiv, container);
        } else {
            document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
        }

        setTimeout(() => alertDiv.remove(), 5000);
    }

    function mostrarExito(mensaje) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${escapeHtml(mensaje)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector('.filter-section');
        if (container) {
            container.parentNode.insertBefore(alertDiv, container);
        } else {
            document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
        }

        setTimeout(() => alertDiv.remove(), 5000);
    }

    // Funciones globales
    window.verDetalleEmpleado = function(id) {
        window.location.href = `${BASE_URL}/empleados_detalle.php?id=${id}`;
    }

    window.editarEmpleado = function(id) {
        mostrarModal('Editar Empleado', id);
    }

    window.confirmarEliminarEmpleado = function(id, nombre) {
        if (confirm(`¿Estás seguro de que deseas eliminar a ${nombre}?`)) {
            fetch(`${BASE_URL}/app/controllers/empleados_eliminar.php?id=${id}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarExito('Empleado eliminado correctamente');
                    cargarEmpleados(paginaActual);
                } else {
                    mostrarError('Error: ' + (data.message || 'Error al eliminar'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error de conexión al eliminar');
            });
        }
    }

    window.cargarEmpleados = cargarEmpleados;
})();
