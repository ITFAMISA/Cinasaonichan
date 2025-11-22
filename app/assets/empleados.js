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
                        <button class="btn btn-sm btn-success text-white" onclick="window.abrirHabilidadesEmpleado(${empleado.id}, '${escapeHtml(empleado.nombre + ' ' + empleado.apellido)}')" title="Gestionar habilidades">
                            <i class="fas fa-user-check"></i>
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
                                        <label for="numero_empleado" class="form-label">Número Empleado</label>
                                        <input type="text" class="form-control" id="numero_empleado" name="numero_empleado">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="puesto" class="form-label">Puesto <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="puesto" name="puesto" required>
                                    </div>
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

                                <!-- CONTACTO -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Información de Contacto</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="correo" class="form-label">Correo</label>
                                        <input type="email" class="form-control" id="correo" name="correo">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telefono_extension" class="form-label">Extensión</label>
                                        <input type="text" class="form-control" id="telefono_extension" name="telefono_extension">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <input type="text" class="form-control" id="direccion" name="direccion">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="estado" class="form-label">Estado/Provincia</label>
                                        <input type="text" class="form-control" id="estado" name="estado">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="codigo_postal" class="form-label">Código Postal</label>
                                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pais" class="form-label">País</label>
                                        <input type="text" class="form-control" id="pais" name="pais" value="México">
                                    </div>
                                </div>

                                <!-- INFORMACIÓN LABORAL -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Información Laboral</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="tipo_contrato" class="form-label">Tipo de Contrato</label>
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
                                        <select class="form-select" id="supervisor_directo_id" name="supervisor_directo_id">
                                            <option value="">Seleccionar</option>
                                        </select>
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

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nivel_escolaridad" class="form-label">Nivel Escolaridad</label>
                                        <input type="text" class="form-control" id="nivel_escolaridad" name="nivel_escolaridad" placeholder="Ej: Licenciatura, Maestría">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="especialidad" class="form-label">Especialidad/Carrera</label>
                                        <input type="text" class="form-control" id="especialidad" name="especialidad">
                                    </div>
                                </div>

                                <!-- CONTACTO DE EMERGENCIA -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Contacto de Emergencia</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="contacto_emergencia_nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="contacto_emergencia_nombre" name="contacto_emergencia_nombre">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="contacto_emergencia_relacion" class="form-label">Relación</label>
                                        <input type="text" class="form-control" id="contacto_emergencia_relacion" name="contacto_emergencia_relacion" placeholder="Ej: Cónyuge, Hijo, Padre">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contacto_emergencia_telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="contacto_emergencia_telefono" name="contacto_emergencia_telefono">
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
                                        <label for="cuenta_bancaria" class="form-label">Cuenta Bancaria</label>
                                        <input type="text" class="form-control" id="cuenta_bancaria" name="cuenta_bancaria">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="clabe" class="form-label">CLABE</label>
                                        <input type="text" class="form-control" id="clabe" name="clabe" placeholder="18 dígitos">
                                    </div>
                                </div>

                                <!-- IDENTIFICACIÓN -->
                                <div class="mb-3 mt-4">
                                    <h6 class="text-primary fw-bold border-bottom pb-2">Identificación</h6>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="tipo_identificacion" class="form-label">Tipo</label>
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

        // Cargar supervisores disponibles
        cargarSupervisoresDisponibles();

        // Si es edición, cargar datos con delay para asegurar que el DOM está listo
        if (empleadoId) {
            setTimeout(() => {
                cargarDatosEmpleado(empleadoId);
            }, 300);
        }

        modal.show();
    }

    function cargarSupervisoresDisponibles() {
        fetch(`${BASE_URL}/app/controllers/empleados_opciones.php?opcion=supervisores`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const selectSupervisor = document.getElementById('supervisor_directo_id');
                    if (selectSupervisor) {
                        // Limpiar opciones previas excepto la primera
                        selectSupervisor.innerHTML = '<option value="">Seleccionar</option>';

                        // Obtener supervisores del objeto data
                        const supervisores = data.data.supervisores || data.data;

                        if (Array.isArray(supervisores)) {
                            // Agregar supervisores
                            supervisores.forEach(supervisor => {
                                const option = document.createElement('option');
                                option.value = supervisor.id;
                                // El modelo devuelve nombre_completo, usar eso
                                option.textContent = supervisor.nombre_completo || `${supervisor.nombre} ${supervisor.apellido}`;
                                selectSupervisor.appendChild(option);
                            });
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error cargando supervisores:', error);
            });
    }

    function cargarDatosEmpleado(id) {
        fetch(`${BASE_URL}/app/controllers/empleados_detalle.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const empleado = data.data;
                    const form = document.getElementById('formEmpleado');

                    if (!form) {
                        mostrarError('Error: Formulario no encontrado');
                        return;
                    }

                    // Obtener todos los inputs dentro del formulario
                    const setFieldValue = (fieldId, value) => {
                        const field = form.querySelector(`#${fieldId}`);
                        if (field) {
                            // Para campos select, buscar la opción con ese value
                            if (field.tagName === 'SELECT') {
                                // Primero limpiar la selección
                                field.value = '';
                                // Luego buscar y seleccionar la opción correcta
                                const option = field.querySelector(`option[value="${value}"]`);
                                if (option) {
                                    field.value = value;
                                } else if (value && value !== '') {
                                    // Si no encuentra la opción exacta, intentar asignar directamente
                                    field.value = value || '';
                                }
                            } else {
                                field.value = value || '';
                            }
                        }
                    };

                    // Información General
                    setFieldValue('nombre', empleado.nombre);
                    setFieldValue('apellido', empleado.apellido);
                    setFieldValue('puesto', empleado.puesto);
                    setFieldValue('numero_empleado', empleado.numero_empleado);

                    // Contacto
                    setFieldValue('correo', empleado.correo);
                    setFieldValue('telefono', empleado.telefono);
                    setFieldValue('telefono_extension', empleado.telefono_extension);
                    setFieldValue('direccion', empleado.direccion);
                    setFieldValue('ciudad', empleado.ciudad);
                    setFieldValue('estado', empleado.estado);
                    setFieldValue('codigo_postal', empleado.codigo_postal);

                    // Personal
                    setFieldValue('fecha_nacimiento', empleado.fecha_nacimiento);
                    setFieldValue('genero', empleado.genero);
                    setFieldValue('estado_civil', empleado.estado_civil);
                    setFieldValue('cantidad_dependientes', empleado.cantidad_dependientes || 0);

                    // Identificación
                    setFieldValue('tipo_identificacion', empleado.tipo_identificacion);
                    setFieldValue('numero_identificacion', empleado.numero_identificacion);
                    setFieldValue('numero_seguro_social', empleado.numero_seguro_social);

                    // Bancaria
                    setFieldValue('banco', empleado.banco);
                    setFieldValue('cuenta_bancaria', empleado.cuenta_bancaria);
                    setFieldValue('clabe', empleado.clabe);

                    // Laboral
                    setFieldValue('departamento', empleado.departamento);
                    setFieldValue('fecha_ingreso', empleado.fecha_ingreso);
                    setFieldValue('salario_base', empleado.salario_base);
                    setFieldValue('tipo_contrato', empleado.tipo_contrato);
                    setFieldValue('fecha_contrato', empleado.fecha_contrato);
                    setFieldValue('supervisor_directo_id', empleado.supervisor_directo_id);

                    // Emergencia
                    setFieldValue('contacto_emergencia_nombre', empleado.contacto_emergencia_nombre);
                    setFieldValue('contacto_emergencia_relacion', empleado.contacto_emergencia_relacion);
                    setFieldValue('contacto_emergencia_telefono', empleado.contacto_emergencia_telefono);

                    // Educación
                    setFieldValue('nivel_escolaridad', empleado.nivel_escolaridad);
                    setFieldValue('especialidad', empleado.especialidad);

                    // Estatus y Observaciones
                    setFieldValue('estatus_empleado', empleado.estatus_empleado);
                    setFieldValue('observaciones', empleado.observaciones);

                    // Para campos con pais que tiene default value
                    const paisField = form.querySelector('#pais');
                    if (paisField && empleado.pais) {
                        paisField.value = empleado.pais;
                    }
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

        // Obtener todos los inputs y selects del formulario incluyendo los vacíos
        const datosJson = {};
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            if (input.name) {
                datosJson[input.name] = input.value;
            }
        });

        const url = empleadoId
            ? `${BASE_URL}/app/controllers/empleados_editar.php?id=${empleadoId}`
            : `${BASE_URL}/app/controllers/empleados_crear.php`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datosJson)
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

    // ============================================================
    // GESTIÓN DE HABILIDADES
    // ============================================================
    let procesosDisponibles = [];
    let habilidadesActuales = {};
    let empleadoSeleccionado = null;

    document.getElementById('btnHabilidades').addEventListener('click', () => abrirModalHabilidades());

    window.abrirHabilidadesEmpleado = function(empleadoId, empleadoNombre) {
        abrirModalHabilidades(empleadoId, empleadoNombre);
    };

    async function abrirModalHabilidades(empleadoIdPreseleccionado = null, nombreEmpleado = null) {
        try {
            // Cargar datos necesarios
            const [empleadosResp, procesosResp, habilidadesResp] = await Promise.all([
                fetch(`${BASE_URL}/app/controllers/empleados_listar.php?limite=1000`),
                fetch(`${BASE_URL}/app/controllers/procesos_obtener_todos.php`),
                fetch(`${BASE_URL}/app/controllers/empleado_procesos_listar.php`)
            ]);

            const empleadosData = await empleadosResp.json();
            const procesosData = await procesosResp.json();
            const habilidadesData = await habilidadesResp.json();

            if (!empleadosData.success || !procesosData.success || !habilidadesData.success) {
                mostrarError('Error al cargar datos del modal');
                return;
            }

            // Guardar procesos disponibles
            procesosDisponibles = procesosData.data;

            // Guardar habilidades existentes
            habilidadesActuales = {};
            habilidadesData.data.forEach(emp => {
                habilidadesActuales[emp.id] = {
                    nombres: emp.proceso_nombres || [],
                    ids: emp.proceso_ids || []
                };
            });

            // Generar opciones de empleados
            const opcionesEmpleados = empleadosData.data.map(emp =>
                `<option value="${emp.id}">${emp.apellido}, ${emp.nombre}</option>`
            ).join('');

            // Generar checkboxes de procesos
            const procesosCheckboxes = procesosData.data.map(proceso =>
                `<label class="form-check d-flex align-items-center p-2 border rounded" style="cursor: pointer;">
                    <input type="checkbox" class="form-check-input me-2" value="${proceso.id}" data-proceso-id="${proceso.id}">
                    <span class="form-check-label">${proceso.nombre}</span>
                </label>`
            ).join('');

            // Construir HTML del modal
            const modalHTML = `
                <div class="modal fade" id="modalHabilidades" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-user-check me-2"></i>Gestión de Habilidades
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="selectEmpleado" class="form-label">Seleccionar Empleado</label>
                                    <select class="form-select" id="selectEmpleado">
                                        <option value="">-- Seleccionar un empleado --</option>
                                        ${opcionesEmpleados}
                                    </select>
                                </div>

                                <div id="procesosList" class="mt-4 d-none">
                                    <h6>Procesos Disponibles</h6>
                                    <div id="procesosCheckboxes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
                                        ${procesosCheckboxes}
                                    </div>
                                </div>

                                <div id="habilidadesActuales" class="mt-4 p-3 bg-light rounded d-none">
                                    <h6>Habilidades Asignadas</h6>
                                    <div id="badgesHabilidades"></div>
                                </div>

                                <div id="mensajeEstado" class="alert mt-3 d-none" role="alert"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary" id="btnGuardarHabilidades">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remover modal existente si lo hay
            const existingModal = document.getElementById('modalHabilidades');
            if (existingModal) {
                const bootstrapModal = bootstrap.Modal.getInstance(existingModal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
                existingModal.remove();
            }

            // Insertar modal en el DOM
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Agregar event listeners
            document.getElementById('selectEmpleado').addEventListener('change', function() {
                empleadoSeleccionado = this.value;
                if (empleadoSeleccionado) {
                    cargarHabilidadesEmpleado(empleadoSeleccionado);
                    document.getElementById('procesosList').classList.remove('d-none');
                    document.getElementById('habilidadesActuales').classList.remove('d-none');
                } else {
                    limpiarFormularioHabilidades();
                    document.getElementById('procesosList').classList.add('d-none');
                    document.getElementById('habilidadesActuales').classList.add('d-none');
                }
            });

            document.getElementById('btnGuardarHabilidades').addEventListener('click', guardarHabilidades);

            // Si hay un empleado preseleccionado, establecerlo
            if (empleadoIdPreseleccionado) {
                const selectEmpleado = document.getElementById('selectEmpleado');
                selectEmpleado.value = empleadoIdPreseleccionado;

                // Disparar el evento change para cargar las habilidades
                empleadoSeleccionado = empleadoIdPreseleccionado;
                cargarHabilidadesEmpleado(empleadoIdPreseleccionado);
                document.getElementById('procesosList').classList.remove('d-none');
                document.getElementById('habilidadesActuales').classList.remove('d-none');
            }

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalHabilidades'));
            modal.show();

        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al abrir el modal de habilidades');
        }
    }

    function cargarHabilidadesEmpleado(empleadoId) {
        // Limpiar todos los checkboxes
        document.querySelectorAll('#procesosCheckboxes input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });

        // Marcar las habilidades del empleado
        if (habilidadesActuales[empleadoId] && habilidadesActuales[empleadoId].ids) {
            habilidadesActuales[empleadoId].ids.forEach(procesoId => {
                const checkbox = document.querySelector(`#procesosCheckboxes input[data-proceso-id="${procesoId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }

        // Actualizar badges de habilidades actuales
        const container = document.getElementById('badgesHabilidades');
        if (habilidadesActuales[empleadoId] && habilidadesActuales[empleadoId].nombres.length > 0) {
            container.innerHTML = habilidadesActuales[empleadoId].nombres
                .map(nombre => `<span class="badge bg-success me-2 mb-2">${nombre}</span>`)
                .join('');
        } else {
            container.innerHTML = '<p class="text-muted">Sin habilidades asignadas</p>';
        }
    }

    function limpiarFormularioHabilidades() {
        document.querySelectorAll('#procesosCheckboxes input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
        document.getElementById('badgesHabilidades').innerHTML = '';
    }

    async function guardarHabilidades() {
        if (!empleadoSeleccionado) {
            mostrarError('Por favor selecciona un empleado');
            return;
        }

        try {
            const checkboxes = document.querySelectorAll('#procesosCheckboxes input[type="checkbox"]');
            const procesosSeleccionados = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => parseInt(cb.value));

            // Obtener procesos anteriores
            const procesosAnteriores = habilidadesActuales[empleadoSeleccionado]?.ids || [];

            // Procesos a agregar
            const procesosAgregar = procesosSeleccionados.filter(id => !procesosAnteriores.includes(id));

            // Procesos a remover
            const procesosRemover = procesosAnteriores.filter(id => !procesosSeleccionados.includes(id));

            // Realizar operaciones
            const promesas = [];

            procesosAgregar.forEach(procesoId => {
                promesas.push(
                    fetch(`${BASE_URL}/app/controllers/empleado_procesos_actualizar.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            empleado_id: empleadoSeleccionado,
                            proceso_id: procesoId,
                            accion: 'asignar',
                            nivel: 'intermedio'
                        })
                    })
                );
            });

            procesosRemover.forEach(procesoId => {
                promesas.push(
                    fetch(`${BASE_URL}/app/controllers/empleado_procesos_actualizar.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            empleado_id: empleadoSeleccionado,
                            proceso_id: procesoId,
                            accion: 'desasignar'
                        })
                    })
                );
            });

            if (promesas.length > 0) {
                await Promise.all(promesas);
            }

            // Cerrar modal y mostrar éxito
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalHabilidades'));
            modal.hide();
            mostrarExito('Habilidades guardadas correctamente');

        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al guardar las habilidades');
        }
    }

    window.cargarEmpleados = cargarEmpleados;
})();
