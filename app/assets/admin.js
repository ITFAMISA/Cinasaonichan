// Admin Panel JavaScript
let paginaActualUsuarios = 1;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof USER_PERMISSIONS !== 'undefined') {
        if (USER_PERMISSIONS.canManageUsers) {
            cargarUsuarios();
            cargarFiltrosRoles();
        }
        if (USER_PERMISSIONS.canManageRoles) {
            cargarRoles();
            cargarPermisos();

            // Event listener para filtro de módulos en permisos
            const filtroModuloPermisos = document.getElementById('filtroModuloPermisos');
            if (filtroModuloPermisos) {
                filtroModuloPermisos.addEventListener('change', cargarPermisos);
            }
        }
    }
});

// ============================================
// USUARIOS
// ============================================

function cargarUsuarios(pagina = 1) {
    paginaActualUsuarios = pagina;

    const buscarInput = document.getElementById('buscar_usuario');
    const filtroRolSelect = document.getElementById('filtro_rol');

    if (!buscarInput || !filtroRolSelect) {
        console.error('No se encontraron los elementos de filtro en el DOM');
        return;
    }

    const buscar = buscarInput.value;
    const rol_id = filtroRolSelect.value;

    const queryString = new URLSearchParams({
        pagina: pagina,
        buscar: buscar,
        ...(rol_id && { rol_id: rol_id })
    }).toString();

    fetch(`${BASE_URL}/app/controllers/admin_usuarios_listar.php?${queryString}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarUsuarios(data.data);
                mostrarPaginacionUsuarios(data.pagination);
            } else {
                mostrarError('Error al cargar usuarios: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al cargar usuarios');
        });
}

function mostrarUsuarios(usuarios) {
    const tbody = document.getElementById('tablaUsuarios');

    if (!tbody) {
        console.error('No se encontró el elemento tablaUsuarios');
        return;
    }

    if (usuarios.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${USER_PERMISSIONS.canEditUser || USER_PERMISSIONS.canDeleteUser ? '6' : '5'}" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = usuarios.map(usuario => {
        const estadoBadge = usuario.estado === 'activo' ? 'badge-activo' : 
                           usuario.estado === 'inactivo' ? 'badge-inactivo' : 
                           'badge-bloqueado';
        
        let html = `
        <tr>
            <td>
                <strong>${escapeHtml(usuario.nombre_usuario)}</strong>
            </td>
            <td>${escapeHtml(usuario.nombre_completo)}</td>
            <td><small>${escapeHtml(usuario.correo)}</small></td>
            <td>
                ${usuario.roles ? usuario.roles.split(', ').map(rol =>
                    `<span class="badge bg-info text-white me-1">${escapeHtml(rol)}</span>`
                ).join('') : '<span class="text-muted small">Sin roles</span>'}
            </td>
            <td>
                <span class="badge ${estadoBadge}">
                    ${escapeHtml(usuario.estado).charAt(0).toUpperCase() + escapeHtml(usuario.estado).slice(1)}
                </span>
            </td>`;
        
        if (USER_PERMISSIONS.canEditUser || USER_PERMISSIONS.canDeleteUser) {
            html += `<td>`;
            if (USER_PERMISSIONS.canEditUser) {
                html += `
                <button class="btn btn-sm btn-warning" onclick="editarUsuario(${usuario.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>`;
            }
            if (USER_PERMISSIONS.canDeleteUser) {
                html += `
                <button class="btn btn-sm btn-danger" onclick="confirmarEliminarUsuario(${usuario.id}, '${escapeHtml(usuario.nombre_completo).replace(/'/g, "\\'")}')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>`;
            }
            html += `</td>`;
        }
        
        html += `</tr>`;
        return html;
    }).join('');
}

function mostrarPaginacionUsuarios(pagination) {
    const nav = document.getElementById('paginacion-usuarios');
    const contador = document.getElementById('contador-usuarios');

    if (!nav || !contador) {
        console.error('No se encontraron elementos de paginación');
        return;
    }

    contador.innerHTML = `Mostrando ${pagination.total_registros} usuario(s)`;

    if (pagination.total_paginas <= 1) {
        nav.innerHTML = '';
        return;
    }

    let html = '<ul class="pagination pagination-sm mb-0">';

    if (pagination.pagina_actual > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="javascript:cargarUsuarios(${pagination.pagina_actual - 1})">Anterior</a>
        </li>`;
    } else {
        html += `<li class="page-item disabled">
            <span class="page-link">Anterior</span>
        </li>`;
    }

    for (let i = 1; i <= pagination.total_paginas; i++) {
        if (i === pagination.pagina_actual) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item">
                <a class="page-link" href="javascript:cargarUsuarios(${i})">${i}</a>
            </li>`;
        }
    }

    if (pagination.pagina_actual < pagination.total_paginas) {
        html += `<li class="page-item">
            <a class="page-link" href="javascript:cargarUsuarios(${pagination.pagina_actual + 1})">Siguiente</a>
        </li>`;
    } else {
        html += `<li class="page-item disabled">
            <span class="page-link">Siguiente</span>
        </li>`;
    }

    html += '</ul>';
    nav.innerHTML = html;
}

function buscarUsuarios() {
    cargarUsuarios(1);
}

function limpiarFiltrosUsuarios() {
    document.getElementById('buscar_usuario').value = '';
    document.getElementById('filtro_rol').value = '';
    cargarUsuarios(1);
}

function abrirModalCrearUsuario() {
    mostrarModalUsuario('Crear Usuario', null);
}

function editarUsuario(id) {
    mostrarModalUsuario('Editar Usuario', id);
}

function mostrarModalUsuario(titulo, usuarioId) {
    fetch(`${BASE_URL}/app/controllers/admin_roles_opciones.php`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);

            const roles = data.data;
            const opcionesRoles = roles.map(rol =>
                `<div class="form-check">
                    <input class="form-check-input rol-checkbox" type="checkbox" value="${rol.id}" id="rol_${rol.id}" name="roles">
                    <label class="form-check-label" for="rol_${rol.id}">
                        ${escapeHtml(rol.nombre)}
                    </label>
                </div>`
            ).join('');

            let html = `
                <div class="modal fade" id="modalUsuario" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${escapeHtml(titulo)}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formUsuario">
                                    ${!usuarioId ? `
                                    <div class="mb-3">
                                        <label for="buscar_empleado" class="form-label">Buscar Empleado <span class="text-danger">*</span></label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" id="buscar_empleado" placeholder="Escribe el nombre del empleado..." autocomplete="off">
                                            <input type="hidden" id="empleado_id" name="empleado_id">
                                            <div id="lista_empleados" class="list-group position-absolute w-100 mt-1" style="display: none; max-height: 200px; overflow-y: auto; z-index: 1000;"></div>
                                        </div>
                                    </div>
                                    ` : ''}

                                    <div class="mb-3">
                                        <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required ${!usuarioId ? 'readonly' : ''}>
                                    </div>

                                    <div class="mb-3">
                                        <label for="nombre_completo" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="correo" name="correo" required readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="contrasena" class="form-label">${usuarioId ? 'Contraseña (dejar vacío para mantener)' : 'Contraseña'} <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="contrasena" name="contrasena" ${!usuarioId ? 'required' : ''} placeholder="Ingresa la contraseña">
                                    </div>

                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Estado</label>
                                        <select class="form-select" id="estado" name="estado">
                                            <option value="activo">Activo</option>
                                            <option value="inactivo">Inactivo</option>
                                            <option value="bloqueado">Bloqueado</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Roles <span class="text-danger">*</span></label>
                                        <div class="border p-3 rounded">
                                            ${opcionesRoles}
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="guardarUsuario(${usuarioId})">
                                    ${usuarioId ? 'Actualizar' : 'Crear'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const modalAnterior = document.getElementById('modalUsuario');
            if (modalAnterior) {
                modalAnterior.remove();
            }

            document.body.insertAdjacentHTML('beforeend', html);

            if (usuarioId) {
                cargarDatosUsuario(usuarioId);
            } else {
                // Configurar búsqueda de empleados
                configurarBusquedaEmpleados();
            }

            const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al cargar datos del formulario');
        });
}

function configurarBusquedaEmpleados() {
    const inputBuscar = document.getElementById('buscar_empleado');
    const listaEmpleados = document.getElementById('lista_empleados');
    let timeout = null;

    // Cargar todos los empleados al inicio
    cargarListaEmpleados('');

    inputBuscar.addEventListener('focus', function() {
        // Mostrar lista al hacer foco
        cargarListaEmpleados(this.value.trim());
    });

    inputBuscar.addEventListener('input', function() {
        clearTimeout(timeout);
        const buscar = this.value.trim();

        timeout = setTimeout(() => {
            cargarListaEmpleados(buscar);
        }, 300);
    });

    // Cerrar lista al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!inputBuscar.contains(e.target) && !listaEmpleados.contains(e.target)) {
            listaEmpleados.style.display = 'none';
        }
    });

    function cargarListaEmpleados(buscar) {
        const url = buscar 
            ? `${BASE_URL}/app/controllers/empleados_opciones.php?buscar=${encodeURIComponent(buscar)}`
            : `${BASE_URL}/app/controllers/empleados_opciones.php`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    listaEmpleados.innerHTML = data.data.map(emp => `
                        <button type="button" class="list-group-item list-group-item-action" onclick="seleccionarEmpleado(${emp.id}, '${escapeHtml(emp.nombre)}', '${escapeHtml(emp.apellido)}', '${escapeHtml(emp.correo || '')}')">
                            <strong>${escapeHtml(emp.apellido)}, ${escapeHtml(emp.nombre)}</strong>
                            ${emp.correo ? `<br><small class="text-muted">${escapeHtml(emp.correo)}</small>` : ''}
                            ${emp.puesto ? `<br><small class="text-muted"><i class="fas fa-briefcase"></i> ${escapeHtml(emp.puesto)}</small>` : ''}
                        </button>
                    `).join('');
                    listaEmpleados.style.display = 'block';
                } else {
                    listaEmpleados.innerHTML = '<div class="list-group-item text-muted">No se encontraron empleados</div>';
                    listaEmpleados.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                listaEmpleados.innerHTML = '<div class="list-group-item text-danger">Error al cargar empleados</div>';
                listaEmpleados.style.display = 'block';
            });
    }
}

function seleccionarEmpleado(id, nombre, apellido, correo) {
    document.getElementById('empleado_id').value = id;
    document.getElementById('buscar_empleado').value = `${apellido}, ${nombre}`;
    document.getElementById('nombre_completo').value = `${nombre} ${apellido}`;
    
    // Generar nombre de usuario (primera letra nombre + apellido en minúsculas)
    const nombreUsuario = (nombre.charAt(0) + apellido).toLowerCase().replace(/\s+/g, '');
    document.getElementById('nombre_usuario').value = nombreUsuario;
    
    // Asignar correo si existe
    if (correo) {
        document.getElementById('correo').value = correo;
    } else {
        // Generar correo sugerido
        document.getElementById('correo').value = `${nombreUsuario}@cinasa.com`;
    }
    
    document.getElementById('lista_empleados').style.display = 'none';
    
    // Enfocar el campo de contraseña
    document.getElementById('contrasena').focus();
}

function cargarDatosUsuario(id) {
    fetch(`${BASE_URL}/app/controllers/admin_usuarios_detalle.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const usuario = data.data;
                const form = document.getElementById('formUsuario');

                if (form) {
                    form.nombre_usuario.value = usuario.nombre_usuario;
                    form.nombre_completo.value = usuario.nombre_completo;
                    form.correo.value = usuario.correo;
                    form.estado.value = usuario.estado;

                    if (usuario.rol_ids) {
                        const roles = usuario.rol_ids.split(',');
                        roles.forEach(rol_id => {
                            const checkbox = document.getElementById(`rol_${rol_id}`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function guardarUsuario(usuarioId) {
    const form = document.getElementById('formUsuario');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const rolesSeleccionados = document.querySelectorAll('.rol-checkbox:checked');
    if (rolesSeleccionados.length === 0) {
        mostrarError('Debes seleccionar al menos un rol');
        return;
    }

    const datosJson = {
        nombre_usuario: form.nombre_usuario.value,
        nombre_completo: form.nombre_completo.value,
        correo: form.correo.value,
        estado: form.estado.value,
        roles: Array.from(rolesSeleccionados).map(el => el.value)
    };

    if (form.contrasena.value) {
        datosJson.contrasena = form.contrasena.value;
    }

    const url = usuarioId
        ? `${BASE_URL}/app/controllers/admin_usuarios_editar.php?id=${usuarioId}`
        : `${BASE_URL}/app/controllers/admin_usuarios_crear.php`;

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
            mostrarExito(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
            cargarUsuarios(paginaActualUsuarios);
        } else {
            mostrarError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error de conexión');
    });
}

function confirmarEliminarUsuario(id, nombre) {
    if (confirm(`¿Estás seguro de que deseas eliminar a ${nombre}?`)) {
        fetch(`${BASE_URL}/app/controllers/admin_usuarios_eliminar.php?id=${id}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarExito('Usuario eliminado correctamente');
                cargarUsuarios(paginaActualUsuarios);
            } else {
                mostrarError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al eliminar usuario');
        });
    }
}

function cargarFiltrosRoles() {
    const select = document.getElementById('filtro_rol');
    if (!select) {
        console.error('No se encontró el elemento filtro_rol');
        return;
    }

    fetch(`${BASE_URL}/app/controllers/admin_roles_opciones.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.data.forEach(rol => {
                    const option = document.createElement('option');
                    option.value = rol.id;
                    option.textContent = rol.nombre;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

// ============================================
// ROLES
// ============================================

function cargarRoles() {
    fetch(`${BASE_URL}/app/controllers/admin_roles_listar.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarRoles(data.data);
            } else {
                mostrarError('Error al cargar roles: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al cargar roles');
        });
}

function mostrarRoles(roles) {
    const tbody = document.getElementById('tablaRoles');
    const contador = document.getElementById('contador-roles');

    if (!tbody || !contador) {
        console.error('No se encontraron elementos de la tabla de roles');
        return;
    }

    contador.innerHTML = `Mostrando ${roles.length} rol(es)`;

    if (roles.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${USER_PERMISSIONS.canEditRole || USER_PERMISSIONS.canDeleteRole ? '6' : '5'}" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = roles.map(rol => {
        const estadoBadge = rol.estado === 'activo' ? 'badge-activo' : 'badge-inactivo';
        
        let html = `
        <tr>
            <td><strong>${escapeHtml(rol.nombre)}</strong></td>
            <td>${escapeHtml(rol.descripcion || 'Sin descripción')}</td>
            <td>
                <span class="badge bg-secondary">${rol.total_usuarios}</span>
            </td>
            <td>
                <span class="badge bg-secondary">${rol.total_permisos}</span>
            </td>
            <td>
                <span class="badge ${estadoBadge}">
                    ${escapeHtml(rol.estado).charAt(0).toUpperCase() + escapeHtml(rol.estado).slice(1)}
                </span>
            </td>`;
        
        if (USER_PERMISSIONS.canEditRole || USER_PERMISSIONS.canDeleteRole) {
            html += `<td>`;
            if (USER_PERMISSIONS.canEditRole) {
                html += `
                <button class="btn btn-sm btn-warning" onclick="editarRol(${rol.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>`;
            }
            if (USER_PERMISSIONS.canDeleteRole) {
                html += `
                <button class="btn btn-sm btn-danger" onclick="confirmarEliminarRol(${rol.id}, '${escapeHtml(rol.nombre).replace(/'/g, "\\'")}')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>`;
            }
            html += `</td>`;
        }
        
        html += `</tr>`;
        return html;
    }).join('');
}

function abrirModalCrearRol() {
    mostrarModalRol('Crear Rol', null);
}

function editarRol(id) {
    mostrarModalRol('Editar Rol', id);
}

function mostrarModalRol(titulo, rolId) {
    fetch(`${BASE_URL}/app/controllers/admin_permisos_listar.php?agrupar=1`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);

            const permisosAgrupados = data.data;
            const opcionesPermisos = Object.keys(permisosAgrupados).map(modulo => `
                <div class="mb-3">
                    <h6 class="text-primary fw-bold">${escapeHtml(modulo.toUpperCase())}</h6>
                    ${permisosAgrupados[modulo].map(permiso => `
                        <div class="form-check">
                            <input class="form-check-input permiso-checkbox" type="checkbox" value="${permiso.id}" id="permiso_${permiso.id}" name="permisos">
                            <label class="form-check-label" for="permiso_${permiso.id}">
                                <small>${escapeHtml(permiso.nombre)}</small>
                                ${permiso.descripcion ? `<br><small class="text-muted">${escapeHtml(permiso.descripcion)}</small>` : ''}
                            </label>
                        </div>
                    `).join('')}
                </div>
            `).join('');

            let html = `
                <div class="modal fade" id="modalRol" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${escapeHtml(titulo)}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                                <form id="formRol">
                                    <div class="mb-3">
                                        <label for="nombre_rol" class="form-label">Nombre del Rol <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_rol" name="nombre" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="descripcion_rol" class="form-label">Descripción</label>
                                        <textarea class="form-control" id="descripcion_rol" name="descripcion" rows="3"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="estado_rol" class="form-label">Estado</label>
                                        <select class="form-select" id="estado_rol" name="estado">
                                            <option value="activo">Activo</option>
                                            <option value="inactivo">Inactivo</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Permisos</label>
                                        <div class="border p-3 rounded">
                                            ${opcionesPermisos}
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="guardarRol(${rolId})">
                                    ${rolId ? 'Actualizar' : 'Crear'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const modalAnterior = document.getElementById('modalRol');
            if (modalAnterior) {
                modalAnterior.remove();
            }

            document.body.insertAdjacentHTML('beforeend', html);

            if (rolId) {
                cargarDatosRol(rolId);
            }

            const modal = new bootstrap.Modal(document.getElementById('modalRol'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al cargar datos del formulario');
        });
}

function cargarDatosRol(id) {
    fetch(`${BASE_URL}/app/controllers/admin_roles_detalle.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const rol = data.data;
                const form = document.getElementById('formRol');

                if (form) {
                    form.nombre.value = rol.nombre;
                    form.descripcion.value = rol.descripcion || '';
                    form.estado.value = rol.estado;

                    if (rol.permisos && Array.isArray(rol.permisos)) {
                        rol.permisos.forEach(permiso => {
                            const checkbox = document.getElementById(`permiso_${permiso.id}`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function guardarRol(rolId) {
    const form = document.getElementById('formRol');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const permisosSeleccionados = document.querySelectorAll('.permiso-checkbox:checked');

    const datosJson = {
        nombre: form.nombre.value,
        descripcion: form.descripcion.value,
        estado: form.estado.value,
        permisos: Array.from(permisosSeleccionados).map(el => el.value)
    };

    const url = rolId
        ? `${BASE_URL}/app/controllers/admin_roles_editar.php?id=${rolId}`
        : `${BASE_URL}/app/controllers/admin_roles_crear.php`;

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
            mostrarExito(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalRol')).hide();
            cargarRoles();
        } else {
            mostrarError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error de conexión');
    });
}

function confirmarEliminarRol(id, nombre) {
    if (confirm(`¿Estás seguro de que deseas eliminar el rol "${nombre}"?`)) {
        fetch(`${BASE_URL}/app/controllers/admin_roles_eliminar.php?id=${id}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarExito('Rol eliminado correctamente');
                cargarRoles();
            } else {
                mostrarError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al eliminar rol');
        });
    }
}

// ============================================
// PERMISOS
// ============================================

function cargarPermisos() {
    const filtroModulo = document.getElementById('filtroModuloPermisos');
    if (!filtroModulo) {
        console.error('No se encontró el elemento filtroModuloPermisos');
        return;
    }

    const modulo = filtroModulo.value;
    const queryString = modulo ? `?modulo=${modulo}` : '';

    fetch(`${BASE_URL}/app/controllers/admin_permisos_listar.php${queryString}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarPermisos(data.data);
            } else {
                mostrarError('Error al cargar permisos: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al cargar permisos');
        });
}

function mostrarPermisos(permisos) {
    const tbody = document.getElementById('tablaPermisos');
    const contador = document.getElementById('contador-permisos');

    if (!tbody || !contador) {
        console.error('No se encontraron elementos de la tabla de permisos');
        return;
    }

    contador.innerHTML = `Mostrando ${permisos.length} permiso(s)`;

    if (permisos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    No hay permisos disponibles para este módulo
                </td>
            </tr>
        `;
        return;
    }

    // Agrupar por módulo
    const permisosAgrupados = {};
    permisos.forEach(p => {
        if (!permisosAgrupados[p.modulo]) {
            permisosAgrupados[p.modulo] = [];
        }
        permisosAgrupados[p.modulo].push(p);
    });

    let html = '';
    Object.keys(permisosAgrupados).sort().forEach(modulo => {
        const moduloPermisos = permisosAgrupados[modulo];
        let esEstaciones = modulo === 'estaciones';

        html += moduloPermisos.map((p, index) => `
            <tr ${index === 0 ? `style="border-top: 3px solid #2563eb;"` : ''}>
                ${index === 0 ? `<td rowspan="${moduloPermisos.length}" class="fw-bold text-primary">
                    <i class="fas ${getIconoModulo(modulo)}"></i> ${escapeHtml(modulo)}
                </td>` : ''}
                <td>
                    <code>${escapeHtml(p.nombre)}</code>
                </td>
                <td>
                    <small>${escapeHtml(p.descripcion || 'Sin descripción')}</small>
                </td>
                <td>
                    ${esEstaciones ? '<span class="badge bg-success">Nuevo</span>' : '<span class="badge bg-secondary">Existente</span>'}
                </td>
            </tr>
        `).join('');
    });

    tbody.innerHTML = html;
}

function getIconoModulo(modulo) {
    const iconos = {
        usuarios: 'fa-users',
        roles: 'fa-shield-alt',
        estaciones: 'fa-warehouse',
        procesos: 'fa-cogs',
        productos: 'fa-boxes',
        pedidos: 'fa-file-invoice',
        produccion: 'fa-industry',
        calidad: 'fa-clipboard-check',
        empleados: 'fa-user-tie',
        tracking: 'fa-route',
        pdfs: 'fa-file-pdf',
        reportes: 'fa-chart-bar'
    };
    return iconos[modulo] || 'fa-lock';
}

// ============================================
// UTILIDADES
// ============================================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
        const main = document.querySelector('main');
        if (main) {
            main.insertBefore(alertDiv, main.firstChild);
        } else {
            document.body.appendChild(alertDiv);
        }
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
        const main = document.querySelector('main');
        if (main) {
            main.insertBefore(alertDiv, main.firstChild);
        } else {
            document.body.appendChild(alertDiv);
        }
    }
    setTimeout(() => alertDiv.remove(), 5000);
}
