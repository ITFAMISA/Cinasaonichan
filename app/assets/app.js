let paginaActual = 1;
let filtrosActuales = {};
let ordenActual = 'razon_social';
let direccionActual = 'ASC';

document.addEventListener('DOMContentLoaded', function() {
    cargarFiltros();
    cargarClientes();

    document.getElementById('btnNuevoCliente').addEventListener('click', abrirModalCrear);
    document.getElementById('btnBuscar').addEventListener('click', aplicarFiltros);
    document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);
    document.getElementById('btnExportarCSV').addEventListener('click', exportarCSV);

    document.getElementById('buscar').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            aplicarFiltros();
        }
    });

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
            cargarClientes();
        });
    });
});

function cargarFiltros() {
    fetch('app/controllers/obtener_filtros.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selectVendedor = document.getElementById('filtro_vendedor');
                const selectPais = document.getElementById('filtro_pais');
                
                data.vendedores.forEach(vendedor => {
                    const option = document.createElement('option');
                    option.value = vendedor;
                    option.textContent = vendedor;
                    selectVendedor.appendChild(option);
                });
                
                data.paises.forEach(pais => {
                    const option = document.createElement('option');
                    option.value = pais;
                    option.textContent = pais;
                    selectPais.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error al cargar filtros:', error));
}

function cargarClientes() {
    const params = new URLSearchParams({
        ...filtrosActuales,
        orden: ordenActual,
        direccion: direccionActual,
        pagina: paginaActual
    });
    
    const tbody = document.getElementById('tablaClientes');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';
    
    fetch(`app/controllers/clientes_listar.php?${params}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarClientes(data.data);
                actualizarPaginacion(data.pagination);
                actualizarContador(data.pagination.total);
            } else {
                console.error('Error del servidor:', data);
                mostrarError(data.message || 'Error al cargar los clientes');
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            const mensaje = error.message || error.error || 'Error de conexión al cargar los clientes';
            mostrarError(mensaje);
        });
}

function mostrarClientes(clientes) {
    const tbody = document.getElementById('tablaClientes');
    tbody.innerHTML = '';
    
    if (clientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No se encontraron clientes</td></tr>';
        return;
    }
    
    clientes.forEach(cliente => {
        const tr = document.createElement('tr');
        
        const estatusClass = `badge-${cliente.estatus}`;
        const estatusText = cliente.estatus.charAt(0).toUpperCase() + cliente.estatus.slice(1);
        
        tr.innerHTML = `
            <td>${cliente.id}</td>
            <td><strong>${escapeHtml(cliente.razon_social)}</strong></td>
            <td>${cliente.rfc}</td>
            <td>${escapeHtml(cliente.contacto_principal || 'N/A')}</td>
            <td>${escapeHtml(cliente.telefono || 'N/A')}</td>
            <td>${escapeHtml(cliente.correo || 'N/A')}</td>
            <td><span class="badge ${estatusClass}">${estatusText}</span></td>
            <td>${escapeHtml(cliente.vendedor_asignado || 'N/A')}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-info text-white" onclick="verDetalle(${cliente.id})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editarCliente(${cliente.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="exportarPDF(${cliente.id})" title="Exportar PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmarEliminar(${cliente.id}, '${escapeHtml(cliente.razon_social)}')" title="Bloquear">
                        <i class="fas fa-ban"></i>
                    </button>
                </div>
            </td>
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
    anterior.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${pagination.pagina_actual - 1}); return false;">Anterior</a>`;
    ul.appendChild(anterior);
    
    const inicio = Math.max(1, pagination.pagina_actual - 2);
    const fin = Math.min(pagination.total_paginas, pagination.pagina_actual + 2);
    
    if (inicio > 1) {
        const li = document.createElement('li');
        li.className = 'page-item';
        li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(1); return false;">1</a>`;
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
        li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${i}); return false;">${i}</a>`;
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
        li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${pagination.total_paginas}); return false;">${pagination.total_paginas}</a>`;
        ul.appendChild(li);
    }
    
    const siguiente = document.createElement('li');
    siguiente.className = `page-item ${pagination.pagina_actual === pagination.total_paginas ? 'disabled' : ''}`;
    siguiente.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${pagination.pagina_actual + 1}); return false;">Siguiente</a>`;
    ul.appendChild(siguiente);
    
    nav.appendChild(ul);
    paginacionDiv.appendChild(nav);
}

function actualizarContador(total) {
    const elemento = document.getElementById('totalClientes');
    if (elemento) {
        elemento.textContent = total;
    }
}

function cambiarPagina(pagina) {
    paginaActual = pagina;
    cargarClientes();
    window.scrollTo(0, 0);
}

function aplicarFiltros() {
    filtrosActuales = {
        buscar: document.getElementById('buscar').value.trim(),
        estatus: document.getElementById('filtro_estatus').value,
        vendedor: document.getElementById('filtro_vendedor').value,
        pais: document.getElementById('filtro_pais').value
    };
    
    paginaActual = 1;
    cargarClientes();
}

function limpiarFiltros() {
    document.getElementById('buscar').value = '';
    document.getElementById('filtro_estatus').value = '';
    document.getElementById('filtro_vendedor').value = '';
    document.getElementById('filtro_pais').value = '';
    
    filtrosActuales = {};
    paginaActual = 1;
    cargarClientes();
}

function abrirModalCrear() {
    crearYMostrarModal(null);
}

function crearYMostrarModal(clienteData) {
    // Generar HTML del modal
    const modalHTML = generarHTMLModal(clienteData);

    // Remover modal existente si lo hay
    const existingModal = document.getElementById('modalCliente');
    if (existingModal) {
        existingModal.remove();
    }

    // Limpiar cualquier backdrop huérfano
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    // Insertar modal en el DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Agregar event listener al botón guardar
    document.getElementById('btnGuardarCliente').addEventListener('click', guardarCliente);

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCliente'));
    modal.show();
}

function generarHTMLModal(clienteData) {
    const isEdit = clienteData !== null;
    const titulo = isEdit ? 'Editar Cliente' : 'Nuevo Cliente';

    return `
        <div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title flex items-center" id="modalClienteTitle">
                            <i class="fas fa-${isEdit ? 'edit' : 'user-plus'} mr-2"></i>
                            <span>${titulo}</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formCliente">
                            <input type="hidden" id="cliente_id" name="id" value="${isEdit ? clienteData.id : ''}">

                            <div class="alert alert-danger d-none" id="erroresCliente"></div>

                            <h6 class="border-bottom pb-2 mb-3 flex items-center text-blue-700">
                                <div class="bg-blue-100 p-2 rounded-lg mr-2">
                                    <i class="fas fa-building"></i>
                                </div>
                                <span>Datos Fiscales</span>
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label for="razon_social" class="form-label">Razón Social <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="razon_social" name="razon_social" required maxlength="250" value="${isEdit ? escapeHtml(clienteData.razon_social) : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="rfc" class="form-label">RFC <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" required maxlength="14" pattern="[A-ZÑ&]{3,4}\\d{6}[A-Z0-9]{3}" value="${isEdit ? clienteData.rfc : ''}">
                                </div>
                                <div class="col-md-6">
                                    <label for="regimen_fiscal" class="form-label">Régimen Fiscal</label>
                                    <input type="text" class="form-control" id="regimen_fiscal" name="regimen_fiscal" maxlength="100" value="${isEdit ? escapeHtml(clienteData.regimen_fiscal || '') : ''}">
                                </div>
                                <div class="col-md-3">
                                    <label for="uso_cfdi" class="form-label">Uso CFDI</label>
                                    <select class="form-select" id="uso_cfdi" name="uso_cfdi">
                                        <option value="">Seleccionar...</option>
                                        <option value="G01" ${isEdit && clienteData.uso_cfdi === 'G01' ? 'selected' : ''}>G01 - Adquisición de mercancías</option>
                                        <option value="G02" ${isEdit && clienteData.uso_cfdi === 'G02' ? 'selected' : ''}>G02 - Devoluciones</option>
                                        <option value="G03" ${isEdit && clienteData.uso_cfdi === 'G03' ? 'selected' : ''}>G03 - Gastos en general</option>
                                        <option value="I04" ${isEdit && clienteData.uso_cfdi === 'I04' ? 'selected' : ''}>I04 - Construcciones</option>
                                        <option value="P01" ${isEdit && clienteData.uso_cfdi === 'P01' ? 'selected' : ''}>P01 - Por definir</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="estatus" class="form-label">Estatus <span class="text-danger">*</span></label>
                                    <select class="form-select" id="estatus" name="estatus" required>
                                        <option value="activo" ${!isEdit || clienteData.estatus === 'activo' ? 'selected' : ''}>Activo</option>
                                        <option value="suspendido" ${isEdit && clienteData.estatus === 'suspendido' ? 'selected' : ''}>Suspendido</option>
                                        <option value="bloqueado" ${isEdit && clienteData.estatus === 'bloqueado' ? 'selected' : ''}>Bloqueado</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3 flex items-center text-blue-700">
                                <div class="bg-blue-100 p-2 rounded-lg mr-2">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span>Ubicación</span>
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-9">
                                    <label for="direccion" class="form-label">Dirección Completa</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="2">${isEdit ? escapeHtml(clienteData.direccion || '') : ''}</textarea>
                                </div>
                                <div class="col-md-3">
                                    <label for="pais" class="form-label">País</label>
                                    <input type="text" class="form-control" id="pais" name="pais" value="${isEdit ? escapeHtml(clienteData.pais) : 'México'}" maxlength="100">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3 flex items-center text-blue-700">
                                <div class="bg-blue-100 p-2 rounded-lg mr-2">
                                    <i class="fas fa-address-book"></i>
                                </div>
                                <span>Datos de Contacto</span>
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="contacto_principal" class="form-label">Contacto Principal</label>
                                    <input type="text" class="form-control" id="contacto_principal" name="contacto_principal" maxlength="150" value="${isEdit ? escapeHtml(clienteData.contacto_principal || '') : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono" maxlength="30" value="${isEdit ? escapeHtml(clienteData.telefono || '') : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="correo" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="correo" name="correo" maxlength="150" value="${isEdit ? escapeHtml(clienteData.correo || '') : ''}">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3 flex items-center text-blue-700">
                                <div class="bg-blue-100 p-2 rounded-lg mr-2">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <span>Condiciones Comerciales</span>
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label for="dias_credito" class="form-label">Días de Crédito</label>
                                    <select class="form-select" id="dias_credito" name="dias_credito">
                                        <option value="0" ${!isEdit || clienteData.dias_credito == 0 ? 'selected' : ''}>Contado (0 días)</option>
                                        <option value="15" ${isEdit && clienteData.dias_credito == 15 ? 'selected' : ''}>15 días</option>
                                        <option value="30" ${isEdit && clienteData.dias_credito == 30 ? 'selected' : ''}>30 días</option>
                                        <option value="45" ${isEdit && clienteData.dias_credito == 45 ? 'selected' : ''}>45 días</option>
                                        <option value="60" ${isEdit && clienteData.dias_credito == 60 ? 'selected' : ''}>60 días</option>
                                        <option value="90" ${isEdit && clienteData.dias_credito == 90 ? 'selected' : ''}>90 días</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="limite_credito" class="form-label">Límite de Crédito</label>
                                    <input type="number" class="form-control" id="limite_credito" name="limite_credito" step="0.01" min="0" value="${isEdit ? clienteData.limite_credito : '0.00'}">
                                </div>
                                <div class="col-md-3">
                                    <label for="moneda" class="form-label">Moneda</label>
                                    <select class="form-select" id="moneda" name="moneda">
                                        <option value="MXN" ${!isEdit || clienteData.moneda === 'MXN' ? 'selected' : ''}>MXN - Peso Mexicano</option>
                                        <option value="USD" ${isEdit && clienteData.moneda === 'USD' ? 'selected' : ''}>USD - Dólar</option>
                                        <option value="EUR" ${isEdit && clienteData.moneda === 'EUR' ? 'selected' : ''}>EUR - Euro</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="condiciones_pago" class="form-label">Condiciones de Pago</label>
                                    <input type="text" class="form-control" id="condiciones_pago" name="condiciones_pago" maxlength="100" value="${isEdit ? escapeHtml(clienteData.condiciones_pago || '') : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="metodo_pago" class="form-label">Método de Pago</label>
                                    <select class="form-select" id="metodo_pago" name="metodo_pago">
                                        <option value="">Seleccionar...</option>
                                        <option value="PUE" ${isEdit && clienteData.metodo_pago === 'PUE' ? 'selected' : ''}>PUE - Pago en una sola exhibición</option>
                                        <option value="PPD" ${isEdit && clienteData.metodo_pago === 'PPD' ? 'selected' : ''}>PPD - Pago en parcialidades</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="forma_pago" class="form-label">Forma de Pago</label>
                                    <select class="form-select" id="forma_pago" name="forma_pago">
                                        <option value="">Seleccionar...</option>
                                        <option value="01" ${isEdit && clienteData.forma_pago === '01' ? 'selected' : ''}>01 - Efectivo</option>
                                        <option value="02" ${isEdit && clienteData.forma_pago === '02' ? 'selected' : ''}>02 - Cheque</option>
                                        <option value="03" ${isEdit && clienteData.forma_pago === '03' ? 'selected' : ''}>03 - Transferencia</option>
                                        <option value="04" ${isEdit && clienteData.forma_pago === '04' ? 'selected' : ''}>04 - Tarjeta de crédito</option>
                                        <option value="28" ${isEdit && clienteData.forma_pago === '28' ? 'selected' : ''}>28 - Tarjeta de débito</option>
                                        <option value="99" ${isEdit && clienteData.forma_pago === '99' ? 'selected' : ''}>99 - Por definir</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="vendedor_asignado" class="form-label">Vendedor Asignado</label>
                                    <input type="text" class="form-control" id="vendedor_asignado" name="vendedor_asignado" maxlength="100" value="${isEdit ? escapeHtml(clienteData.vendedor_asignado || '') : ''}">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3 flex items-center text-blue-700">
                                <div class="bg-blue-100 p-2 rounded-lg mr-2">
                                    <i class="fas fa-university"></i>
                                </div>
                                <span>Información Bancaria</span>
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="banco" class="form-label">Banco</label>
                                    <input type="text" class="form-control" id="banco" name="banco" maxlength="150" value="${isEdit ? escapeHtml(clienteData.banco || '') : ''}">
                                </div>
                                <div class="col-md-6">
                                    <label for="cuenta_bancaria" class="form-label">Cuenta Bancaria</label>
                                    <input type="text" class="form-control" id="cuenta_bancaria" name="cuenta_bancaria" maxlength="50" value="${isEdit ? escapeHtml(clienteData.cuenta_bancaria || '') : ''}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary group" data-bs-dismiss="modal">
                            <i class="fas fa-times transition-transform group-hover:rotate-90"></i>
                            <span class="ml-1">Cancelar</span>
                        </button>
                        <button type="button" class="btn btn-primary group" id="btnGuardarCliente">
                            <i class="fas fa-save transition-transform group-hover:scale-125"></i>
                            <span class="ml-1">Guardar Cliente</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function editarCliente(id) {
    fetch(`app/controllers/clientes_detalle.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                crearYMostrarModal(data.data);
            } else {
                mostrarError('Error al cargar el cliente');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al cargar el cliente');
        });
}

function guardarCliente() {
    const formData = new FormData(document.getElementById('formCliente'));
    const id = document.getElementById('cliente_id').value;
    const url = id ? 'app/controllers/clientes_editar.php' : 'app/controllers/clientes_crear.php';
    
    const btn = document.getElementById('btnGuardarCliente');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cliente';

        if (data.success) {
            const modalElement = document.getElementById('modalCliente');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }
            // Limpiar modal y backdrops después de cerrar
            setTimeout(() => {
                if (modalElement) modalElement.remove();
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 300);

            cargarClientes();
            mostrarExito(data.message);
        } else {
            if (data.errors) {
                mostrarErroresFormulario(data.errors);
            } else {
                mostrarError(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cliente';
        mostrarError('Error de conexión al guardar el cliente');
    });
}

function confirmarEliminar(id, razonSocial) {
    if (confirm(`¿Está seguro de bloquear el cliente "${razonSocial}"?\n\nEl cliente quedará con estatus "bloqueado".`)) {
        eliminarCliente(id);
    }
}

function eliminarCliente(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('app/controllers/clientes_eliminar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarClientes();
            mostrarExito(data.message);
        } else {
            mostrarError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error de conexión al bloquear el cliente');
    });
}

function verDetalle(id) {
    fetch(`app/controllers/clientes_detalle.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetalleModal(data.data);
            } else {
                mostrarError('Error al cargar el detalle del cliente');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión');
        });
}

function mostrarDetalleModal(cliente) {
    const detalleHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3 text-blue-700">
                            <i class="fas fa-building text-blue-600 me-2"></i>
                            Datos Fiscales
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Razón Social</span>
                            <span class="detail-value">${escapeHtml(cliente.razon_social)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">RFC</span>
                            <span class="detail-value">${cliente.rfc}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Régimen Fiscal</span>
                            <span class="detail-value">${escapeHtml(cliente.regimen_fiscal || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Estatus</span>
                            <span class="detail-value"><span class="badge badge-${cliente.estatus}">${cliente.estatus.toUpperCase()}</span></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3 text-blue-700">
                            <i class="fas fa-map-marker-alt text-blue-600 me-2"></i>
                            Ubicación
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Dirección</span>
                            <span class="detail-value">${escapeHtml(cliente.direccion || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">País</span>
                            <span class="detail-value">${escapeHtml(cliente.pais)}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3 text-blue-700">
                            <i class="fas fa-address-book text-blue-600 me-2"></i>
                            Contacto
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Contacto Principal</span>
                            <span class="detail-value">${escapeHtml(cliente.contacto_principal || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Teléfono</span>
                            <span class="detail-value">${escapeHtml(cliente.telefono || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Correo</span>
                            <span class="detail-value">${escapeHtml(cliente.correo || 'N/A')}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3 text-blue-700">
                            <i class="fas fa-credit-card text-blue-600 me-2"></i>
                            Condiciones Comerciales
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Días de Crédito</span>
                            <span class="detail-value">${cliente.dias_credito} días</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Límite de Crédito</span>
                            <span class="detail-value">$${parseFloat(cliente.limite_credito).toFixed(2)} ${cliente.moneda}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Vendedor Asignado</span>
                            <span class="detail-value">${escapeHtml(cliente.vendedor_asignado || 'N/A')}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Agregar estilos inline para los detalles
    const styles = `
        <style>
            .detail-item {
                display: flex;
                flex-direction: column;
                margin-bottom: 1rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid rgba(0,0,0,0.1);
            }
            .detail-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .detail-label {
                font-size: 0.85rem;
                font-weight: 600;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 0.3rem;
            }
            .detail-value {
                font-size: 1rem;
                color: #1e293b;
                font-weight: 500;
            }
        </style>
    `;

    const modalHTML = `
        <div class="modal fade" id="modalDetalle" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-circle mr-2"></i>Detalle del Cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${detalleHTML}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" onclick="exportarPDF(${cliente.id})">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existingStyle = document.getElementById('detailItemStyles');
    if (!existingStyle) {
        document.head.insertAdjacentHTML('beforeend', `<style id="detailItemStyles">${styles.slice(7, -8)}</style>`);
    }

    const existingModal = document.getElementById('modalDetalle');
    if (existingModal) {
        existingModal.remove();
    }

    // Limpiar cualquier backdrop huérfano
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
    modal.show();
}

function exportarCSV() {
    const params = new URLSearchParams(filtrosActuales);
    window.location.href = `app/controllers/export_csv.php?${params}`;
}

function exportarPDF(id) {
    window.open(`app/controllers/export_pdf.php?id=${id}`, '_blank');
}

function actualizarIconosOrden() {
    document.querySelectorAll('.sortable').forEach(th => {
        th.classList.remove('asc', 'desc');
        if (th.dataset.column === ordenActual) {
            th.classList.add(direccionActual.toLowerCase());
        }
    });
}

function mostrarErroresFormulario(errores) {
    const divErrores = document.getElementById('erroresCliente');
    divErrores.innerHTML = '<ul class="mb-0">' + errores.map(e => `<li>${escapeHtml(e)}</li>`).join('') + '</ul>';
    divErrores.classList.remove('d-none');
}

function ocultarErrores() {
    const divErrores = document.getElementById('erroresCliente');
    divErrores.classList.add('d-none');
}

function mostrarError(mensaje) {
    alert('Error: ' + mensaje);
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
