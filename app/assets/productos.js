// Módulo de Productos - CINASA
(function() {
    'use strict';
    
    let paginaActual = 1;
    let ordenActual = 'material_code';
    let direccionActual = 'ASC';
    let opcionesEnCache = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Cargar opciones una vez al iniciar
        cargarOpcionesModalEnCache();
        cargarProductos();

        // Event listeners
        document.getElementById('btnNuevoProducto').addEventListener('click', abrirModalCrear);
        document.getElementById('btnBuscar').addEventListener('click', () => cargarProductos(1));
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);
        // btnGuardarProducto se crea dinámicamente, su listener se agrega en crearYMostrarModal()

        const buscarInput = document.getElementById('buscar');
        if (buscarInput) {
            buscarInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    cargarProductos(1);
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
                cargarProductos(paginaActual);
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

    function cargarProductos(pagina = 1) {
    paginaActual = pagina;
    
    const filtros = {
        buscar: document.getElementById('buscar').value,
        estatus: document.getElementById('estatus').value,
        pais_origen: document.getElementById('pais_origen').value,
        categoria: document.getElementById('categoria').value,
        orden: ordenActual,
        direccion: direccionActual,
        pagina: pagina
    };
    
    const queryString = new URLSearchParams(filtros).toString();
    
    fetch(`${BASE_URL}/app/controllers/productos_listar.php?${queryString}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarProductos(data.data);
                mostrarPaginacion(data.pagination);
            } else {
                mostrarError('Error al cargar productos: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al cargar productos');
        });
}

function mostrarProductos(productos) {
    const tbody = document.getElementById('tablaProductos');
    
    if (productos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No se encontraron productos</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = '';
    productos.forEach(producto => {
        const tr = document.createElement('tr');
        
        const estatusClass = `badge-${producto.estatus}`;
        const estatusText = producto.estatus.charAt(0).toUpperCase() + producto.estatus.slice(1);
        
        tr.innerHTML = `
            <td>
                <div class="font-weight-bold">${escapeHtml(producto.material_code || 'N/A')}</div>
                ${producto.drawing_number ? `<small class="text-muted">Dwg: ${escapeHtml(producto.drawing_number)}</small>` : ''}
            </td>
            <td>
                <div class="text-truncate" style="max-width: 300px;" title="${escapeHtml(producto.descripcion || '')}">${escapeHtml(producto.descripcion || 'N/A')}</div>
            </td>
            <td>${escapeHtml(producto.unidad_medida || 'N/A')}</td>
            <td>
                ${escapeHtml(producto.drawing_number || 'N/A')}
                ${producto.drawing_version ? `<br><small class="text-muted">v${escapeHtml(producto.drawing_version)}</small>` : ''}
            </td>
            <td>${escapeHtml(producto.categoria || 'N/A')}</td>
            <td><span class="badge ${estatusClass}">${estatusText}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-info text-white" onclick="window.verDetalleProducto(${producto.id})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="window.editarProducto(${producto.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-info text-white" onclick="window.abrirModalAsignarProcesos(${producto.id})" title="Asignar procesos">
                        <i class="fas fa-sitemap"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="window.exportarProductoPDF(${producto.id})" title="Exportar PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="window.confirmarEliminar(${producto.id}, '${escapeHtml(producto.material_code || 'este producto')}')" title="Marcar como descontinuado">
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

function getEstatusClase(estatus) {
    // Esta función ya no se usa, pero la dejo por compatibilidad
    return '';
}

function mostrarPaginacion(pagination) {
    const div = document.getElementById('paginacion');
    const contador = document.getElementById('contador');
    
    if (contador) {
        contador.textContent = `Mostrando ${pagination.total} producto${pagination.total !== 1 ? 's' : ''}`;
    }
    
    if (pagination.total_paginas <= 1) {
        div.innerHTML = '';
        return;
    }
    
    let html = '<nav><ul class="pagination pagination-sm mb-0">';
    
    // Botón anterior
    if (pagination.pagina_actual > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cargarProductos(${pagination.pagina_actual - 1}); return false;">Anterior</a></li>`;
    } else {
        html += '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
    }
    
    // Números de página
    for (let i = 1; i <= pagination.total_paginas; i++) {
        if (i === pagination.pagina_actual) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else if (i === 1 || i === pagination.total_paginas || (i >= pagination.pagina_actual - 2 && i <= pagination.pagina_actual + 2)) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cargarProductos(${i}); return false;">${i}</a></li>`;
        } else if (i === pagination.pagina_actual - 3 || i === pagination.pagina_actual + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Botón siguiente
    if (pagination.pagina_actual < pagination.total_paginas) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="window.cargarProductos(${pagination.pagina_actual + 1}); return false;">Siguiente</a></li>`;
    } else {
        html += '<li class="page-item disabled"><span class="page-link">Siguiente</span></li>';
    }
    
    html += '</ul></nav>';
    div.innerHTML = html;
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        direccionActual = direccionActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        direccionActual = 'ASC';
    }
    cargarProductos(paginaActual);
}

function limpiarFiltros() {
    document.getElementById('buscar').value = '';
    document.getElementById('estatus').value = 'activo';
    document.getElementById('pais_origen').value = '';
    document.getElementById('categoria').value = '';
    cargarProductos(1);
}

// Función para cargar opciones una sola vez al iniciar y guardarlas en caché
function cargarOpcionesModalEnCache() {
    return fetch(`${BASE_URL}/app/controllers/productos_opciones.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.paises && data.categorias) {
                opcionesEnCache = data;
                console.log('Opciones cargadas en caché:', opcionesEnCache);
                return true;
            } else {
                console.warn('No se pudieron cargar opciones:', data);
                return false;
            }
        })
        .catch(error => {
            console.error('Error cargando opciones:', error);
            return false;
        });
}

// Función para actualizar dinámicamente los filtros de país y categoría en la página principal
function cargarOpcionesModal() {
    // Usar datos en caché si están disponibles
    if (!opcionesEnCache) {
        console.warn('Opciones no están en caché, intentando cargar...');
        return cargarOpcionesModalEnCache();
    }

    const data = opcionesEnCache;

    // Actualizar el filtro de país para la página principal
    const paisFiltro = document.getElementById('pais_origen');
    if (paisFiltro && data.paises && data.paises.length > 0) {
        const paisFiltroActual = paisFiltro.value;

        while (paisFiltro.options.length > 1) {
            paisFiltro.remove(1);
        }

        data.paises.forEach(pais => {
            const option = document.createElement('option');
            option.value = pais;
            option.textContent = pais;
            paisFiltro.appendChild(option);
        });

        if (paisFiltroActual) {
            paisFiltro.value = paisFiltroActual;
        }
    }

    // Actualizar el filtro de categoría para la página principal
    const categoriaFiltro = document.getElementById('categoria');
    if (categoriaFiltro && data.categorias && data.categorias.length > 0) {
        const catFiltroActual = categoriaFiltro.value;

        while (categoriaFiltro.options.length > 1) {
            categoriaFiltro.remove(1);
        }

        data.categorias.forEach(categoria => {
            const option = document.createElement('option');
            option.value = categoria;
            option.textContent = categoria;
            categoriaFiltro.appendChild(option);
        });

        if (catFiltroActual) {
            categoriaFiltro.value = catFiltroActual;
        }
    }

    return Promise.resolve(true);
}

function abrirModalCrear() {
    crearYMostrarModal(null);
}

function crearYMostrarModal(productoData) {
    // Cargar opciones dinámicamente
    cargarOpcionesModal();

    // Generar HTML del modal
    const modalHTML = generarHTMLModal(productoData);

    // Remover modal existente si lo hay
    const existingModal = document.getElementById('modalProducto');
    if (existingModal) {
        const bootstrapModal = bootstrap.Modal.getInstance(existingModal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
        existingModal.remove();
    }

    // Insertar modal en el DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Configurar evento del botón guardar
    document.getElementById('btnGuardarProducto').addEventListener('click', guardarProducto);

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalProducto'));
    modal.show();
}

function generarHTMLModal(productoData) {
    const isEdit = productoData !== null;
    const titulo = isEdit ? '<i class="fas fa-edit mr-2"></i><span>Editar Producto</span>' : '<i class="fas fa-plus-circle mr-2"></i><span>Nuevo Producto</span>';
    const btnTexto = isEdit ? 'Actualizar Producto' : 'Guardar Producto';
    const productoId = isEdit ? productoData.id : '';

    return `
        <div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitulo">${titulo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formProducto">
                            <input type="hidden" id="producto_id" name="id" value="${productoId}">

                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle"></i> Información Básica del Producto
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="material_code" class="form-label">Código de Material/Pieza</label>
                                    <input type="text" class="form-control" id="material_code" name="material_code" placeholder="100099089" value="${isEdit ? productoData.material_code || '' : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="unidad_medida_modal" class="form-label">Unidad de Medida</label>
                                    <select class="form-select" id="unidad_medida_modal" name="unidad_medida">
                                        <option value="">Seleccionar...</option>
                                        <option value="EA" ${isEdit && productoData.unidad_medida === 'EA' ? 'selected' : ''}>EA - Each (Pieza)</option>
                                        <option value="PZ" ${isEdit && productoData.unidad_medida === 'PZ' ? 'selected' : ''}>PZ - Pieza</option>
                                        <option value="KG" ${isEdit && productoData.unidad_medida === 'KG' ? 'selected' : ''}>KG - Kilogramo</option>
                                        <option value="LB" ${isEdit && productoData.unidad_medida === 'LB' ? 'selected' : ''}>LB - Libra</option>
                                        <option value="MT" ${isEdit && productoData.unidad_medida === 'MT' ? 'selected' : ''}>MT - Metro</option>
                                        <option value="M2" ${isEdit && productoData.unidad_medida === 'M2' ? 'selected' : ''}>M2 - Metro Cuadrado</option>
                                        <option value="M3" ${isEdit && productoData.unidad_medida === 'M3' ? 'selected' : ''}>M3 - Metro Cúbico</option>
                                        <option value="LT" ${isEdit && productoData.unidad_medida === 'LT' ? 'selected' : ''}>LT - Litro</option>
                                        <option value="GL" ${isEdit && productoData.unidad_medida === 'GL' ? 'selected' : ''}>GL - Galón</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="pais_origen_modal" class="form-label">País de Origen</label>
                                    <input type="text" class="form-control" id="pais_origen_modal" name="pais_origen" placeholder="ej: México, China" value="${isEdit ? productoData.pais_origen || '' : ''}">
                                </div>
                                <div class="col-md-12">
                                    <label for="descripcion" class="form-label">Descripción del Producto</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2" placeholder="COVER, ACCESS">${isEdit ? productoData.descripcion || '' : ''}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label for="precio_unitario" class="form-label">Precio Unitario (USD)</label>
                                    <input type="number" step="0.01" class="form-control" id="precio_unitario" name="precio_unitario" placeholder="0.00" value="${isEdit ? productoData.precio_unitario || '' : ''}">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-file-invoice"></i> Clasificación Arancelaria
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="hts_code" class="form-label">Código HTS</label>
                                    <input type="text" class="form-control" id="hts_code" name="hts_code" placeholder="8431499030" value="${isEdit ? productoData.hts_code || '' : ''}">
                                </div>
                                <div class="col-md-6">
                                    <label for="tipo_parte" class="form-label">Tipo de Parte</label>
                                    <input type="text" class="form-control" id="tipo_parte" name="tipo_parte" placeholder="Standard Part, Custom" value="${isEdit ? productoData.tipo_parte || '' : ''}">
                                </div>
                                <div class="col-md-12">
                                    <label for="hts_descripcion" class="form-label">Descripción Código HTS</label>
                                    <textarea class="form-control" id="hts_descripcion" name="hts_descripcion" rows="2" placeholder="COAL, ROCK CUTTERS, TUNNEL MACHINE PARTS">${isEdit ? productoData.hts_descripcion || '' : ''}</textarea>
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-certificate"></i> Sistema de Calidad y Categoría
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="sistema_calidad_modal" class="form-label">Sistema de Calidad</label>
                                    <select class="form-select" id="sistema_calidad_modal" name="sistema_calidad">
                                        <option value="">Seleccionar...</option>
                                        <option value="J02" ${isEdit && productoData.sistema_calidad === 'J02' ? 'selected' : ''}>J02</option>
                                        <option value="ISO9001" ${isEdit && productoData.sistema_calidad === 'ISO9001' ? 'selected' : ''}>ISO9001</option>
                                        <option value="ISO14001" ${isEdit && productoData.sistema_calidad === 'ISO14001' ? 'selected' : ''}>ISO14001</option>
                                        <option value="IATF16949" ${isEdit && productoData.sistema_calidad === 'IATF16949' ? 'selected' : ''}>IATF16949</option>
                                        <option value="AS9100" ${isEdit && productoData.sistema_calidad === 'AS9100' ? 'selected' : ''}>AS9100</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="categoria_modal" class="form-label">Categoría</label>
                                    <input type="text" class="form-control" id="categoria_modal" name="categoria" placeholder="ej: Electrónica, Accesorios" value="${isEdit ? productoData.categoria || '' : ''}">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-drafting-compass"></i> Información Técnica del Dibujo
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label for="drawing_number" class="form-label">Número de Dibujo</label>
                                    <input type="text" class="form-control" id="drawing_number" name="drawing_number" placeholder="100099089" value="${isEdit ? productoData.drawing_number || '' : ''}">
                                </div>
                                <div class="col-md-3">
                                    <label for="drawing_version" class="form-label">Versión</label>
                                    <input type="text" class="form-control" id="drawing_version" name="drawing_version" placeholder="06" value="${isEdit ? productoData.drawing_version || '' : ''}">
                                </div>
                                <div class="col-md-3">
                                    <label for="drawing_sheet" class="form-label">Hoja</label>
                                    <input type="text" class="form-control" id="drawing_sheet" name="drawing_sheet" placeholder="001" value="${isEdit ? productoData.drawing_sheet || '' : ''}">
                                </div>
                                <div class="col-md-3">
                                    <label for="ecm_number" class="form-label">Número ECM</label>
                                    <input type="text" class="form-control" id="ecm_number" name="ecm_number" placeholder="1194615" value="${isEdit ? productoData.ecm_number || '' : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="material_revision" class="form-label">Revisión Material</label>
                                    <input type="text" class="form-control" id="material_revision" name="material_revision" placeholder="06" value="${isEdit ? productoData.material_revision || '' : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="change_number" class="form-label">Número de Cambio</label>
                                    <input type="text" class="form-control" id="change_number" name="change_number" placeholder="1194615" value="${isEdit ? productoData.change_number || '' : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="ref_documento" class="form-label">Documento de Referencia</label>
                                    <input type="text" class="form-control" id="ref_documento" name="ref_documento" placeholder="Doc/Sheet/Ver" value="${isEdit ? productoData.ref_documento || '' : ''}">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-layer-group"></i> Información de Componentes/BOM
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="nivel_componente" class="form-label">Nivel Componente</label>
                                    <input type="text" class="form-control" id="nivel_componente" name="nivel_componente" placeholder="1" value="${isEdit ? productoData.nivel_componente || '' : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="componente_linea" class="form-label">Componente Línea</label>
                                    <input type="text" class="form-control" id="componente_linea" name="componente_linea" placeholder="001, 002" value="${isEdit ? productoData.componente_linea || '' : ''}">
                                </div>
                                <div class="col-md-4">
                                    <label for="ref_documento_bom" class="form-label">Documento Referencia</label>
                                    <input type="text" class="form-control" id="ref_documento_bom" name="ref_documento_bom" placeholder="Doc/Sheet/Ver" value="${isEdit ? productoData.ref_documento_bom || '' : ''}">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-weight"></i> Especificaciones Físicas
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label for="peso" class="form-label">Peso</label>
                                    <input type="number" step="0.001" class="form-control" id="peso" name="peso" value="${isEdit ? productoData.peso || '' : ''}">
                                </div>
                                <div class="col-md-3">
                                    <label for="unidad_peso" class="form-label">Unidad Peso</label>
                                    <input type="text" class="form-control" id="unidad_peso" name="unidad_peso" placeholder="KG, LB" value="${isEdit ? productoData.unidad_peso || '' : ''}">
                                </div>
                                <div class="col-md-3">
                                    <label for="material" class="form-label">Material</label>
                                    <input type="text" class="form-control" id="material" name="material" placeholder="Acero, Aluminio" value="${isEdit ? productoData.material || '' : ''}">
                                </div>
                                <div class="col-md-3">
                                    <label for="acabado" class="form-label">Acabado</label>
                                    <input type="text" class="form-control" id="acabado" name="acabado" placeholder="Pintado, Anodizado" value="${isEdit ? productoData.acabado || '' : ''}">
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-sticky-note"></i> Notas y Estatus
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="estatus_modal" class="form-label">Estatus</label>
                                    <select class="form-select" id="estatus_modal" name="estatus">
                                        <option value="activo" ${isEdit && productoData.estatus === 'activo' ? 'selected' : ''}>Activo</option>
                                        <option value="inactivo" ${isEdit && productoData.estatus === 'inactivo' ? 'selected' : ''}>Inactivo</option>
                                        <option value="descontinuado" ${isEdit && productoData.estatus === 'descontinuado' ? 'selected' : ''}>Descontinuado</option>
                                    </select>
                                </div>
                                <div class="col-md-8"></div>
                                <div class="col-md-12">
                                    <label for="notas" class="form-label">Notas Generales</label>
                                    <textarea class="form-control" id="notas" name="notas" rows="2" placeholder="Notas adicionales del producto...">${isEdit ? productoData.notas || '' : ''}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label for="especificaciones" class="form-label">Especificaciones Técnicas</label>
                                    <textarea class="form-control" id="especificaciones" name="especificaciones" rows="2" placeholder="All product and processes supplied must conform to requirements...">${isEdit ? productoData.especificaciones || '' : ''}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="btnGuardarProducto">
                            <i class="fas fa-save"></i> <span id="btnGuardarTexto">${btnTexto}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function guardarProductoFormulario(e) {
    e.preventDefault();
    guardarProducto();
}

function cerrarModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalProducto'));
    if (modal) {
        modal.hide();
    }
    document.getElementById('formProducto').reset();
}

function editarProducto(id) {
    fetch(`${BASE_URL}/app/controllers/productos_detalle.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                crearYMostrarModal(data.data);
            } else {
                mostrarError('Error al cargar producto: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al cargar producto');
        });
}

// Función auxiliar para establecer valores en campos del formulario
function setSelectValue(fieldId, value) {
    // Convertir el ID del filtro al ID del modal si es necesario
    let modalFieldId = fieldId;
    if (fieldId === 'unidad_medida') modalFieldId = 'unidad_medida_modal';
    if (fieldId === 'pais_origen') modalFieldId = 'pais_origen_modal';
    if (fieldId === 'sistema_calidad') modalFieldId = 'sistema_calidad_modal';
    if (fieldId === 'categoria') modalFieldId = 'categoria_modal';
    if (fieldId === 'estatus') modalFieldId = 'estatus_modal';

    const field = document.getElementById(modalFieldId);
    if (!field) return;

    // Para campos de texto (input), simplemente asignar el valor
    if (field.tagName === 'INPUT') {
        field.value = value || '';
    }
    // Para selects, intentar establecer el valor
    else if (field.tagName === 'SELECT') {
        field.value = value || '';

        // Si no se pudo establecer (porque no existe esa opción), intentar encontrar una coincidencia parcial
        if (field.value !== value && value) {
            const options = Array.from(field.options);
            const match = options.find(opt =>
                opt.value.toLowerCase().includes(value.toLowerCase()) ||
                opt.text.toLowerCase().includes(value.toLowerCase())
            );

            if (match) {
                field.value = match.value;
            } else {
                // Si definitivamente no existe, agregar una opción temporal con el valor de la BD
                const newOption = document.createElement('option');
                newOption.value = value;
                newOption.textContent = value;
                field.appendChild(newOption);
                field.value = value;
            }
        }
    }
}

function guardarProducto() {
    const formData = new FormData(document.getElementById('formProducto'));
    const id = document.getElementById('producto_id').value;
    const url = id ?
        `${BASE_URL}/app/controllers/productos_editar.php` :
        `${BASE_URL}/app/controllers/productos_crear.php`;

    const btn = document.getElementById('btnGuardarProducto');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save transition-transform group-hover:scale-125"></i><span class="ml-1" id="btnGuardarTexto">Guardar Producto</span>';

        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalProducto'));
            if (modal) {
                modal.hide();
            }
            cargarProductos(paginaActual);
            mostrarExito(data.message);
        } else {
            mostrarError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save transition-transform group-hover:scale-125"></i><span class="ml-1" id="btnGuardarTexto">Guardar Producto</span>';
        mostrarError('Error de conexión al guardar producto');
    });
}

function confirmarEliminar(id, nombre) {
    if (confirm(`¿Está seguro de que desea marcar como descontinuado el producto "${nombre}"?`)) {
        eliminarProducto(id);
    }
}

function eliminarProducto(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    fetch(`${BASE_URL}/app/controllers/productos_eliminar.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarExito(data.message);
            cargarProductos(paginaActual);
        } else {
            mostrarError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error de conexión al eliminar producto');
    });
}

function verDetalleProducto(id) {
    fetch(`${BASE_URL}/app/controllers/productos_detalle.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetalleModal(data.data);
            } else {
                mostrarError('Error al cargar el detalle del producto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión');
        });
}

function mostrarDetalleModal(producto) {
    const detalleHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3 text-blue-700">
                            <i class="fas fa-info-circle text-blue-600 me-2"></i>
                            Información Básica
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Código Material</span>
                            <span class="detail-value">${escapeHtml(producto.material_code || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Descripción</span>
                            <span class="detail-value">${escapeHtml(producto.descripcion || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Unidad Medida</span>
                            <span class="detail-value">${escapeHtml(producto.unidad_medida || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">País Origen</span>
                            <span class="detail-value">${escapeHtml(producto.pais_origen || 'N/A')}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3 text-blue-700">
                            <i class="fas fa-drafting-compass text-blue-600 me-2"></i>
                            Información Técnica
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Número Dibujo</span>
                            <span class="detail-value">${escapeHtml(producto.drawing_number || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Versión</span>
                            <span class="detail-value">${escapeHtml(producto.drawing_version || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Categoría</span>
                            <span class="detail-value">${escapeHtml(producto.categoria || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Tipo Parte</span>
                            <span class="detail-value">${escapeHtml(producto.tipo_parte || 'N/A')}</span>
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
                            <i class="fas fa-weight text-blue-600 me-2"></i>
                            Especificaciones Físicas
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Peso</span>
                            <span class="detail-value">${producto.peso ? producto.peso + ' ' + (producto.unidad_peso || '') : 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Material</span>
                            <span class="detail-value">${escapeHtml(producto.material || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Acabado</span>
                            <span class="detail-value">${escapeHtml(producto.acabado || 'N/A')}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3 text-blue-700">
                            <i class="fas fa-file-invoice text-blue-600 me-2"></i>
                            Clasificación Arancelaria
                        </h6>
                        <div class="detail-item">
                            <span class="detail-label">Código HTS</span>
                            <span class="detail-value">${escapeHtml(producto.hts_code || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Sistema Calidad</span>
                            <span class="detail-value">${escapeHtml(producto.sistema_calidad || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Estatus</span>
                            <span class="detail-value"><span class="badge badge-${producto.estatus}">${producto.estatus.toUpperCase()}</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ${producto.notas ? `
        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <h6 class="card-title mb-2 text-blue-700">
                            <i class="fas fa-sticky-note text-blue-600 me-2"></i>
                            Notas
                        </h6>
                        <p class="mb-0">${escapeHtml(producto.notas)}</p>
                    </div>
                </div>
            </div>
        </div>
        ` : ''}
    `;

    // Agregar estilos inline para los detalles
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
                line-height: 1.6 !important;
            }
        </style>
    `;

    const modalHTML = `
        <div class="modal fade" id="modalDetalle" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-box-open mr-2"></i>Detalle del Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${detalleHTML}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" onclick="window.exportarProductoPDF(${producto.id})">
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

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
    modal.show();
}

function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
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

function exportarProductoPDF(id) {
    window.open(`${BASE_URL}/app/controllers/export_pdf_producto.php?id=${id}`, '_blank');
}

function ocultarErrores() {
    // Placeholder para consistencia con clientes
}

    // Exponer funciones globalmente para que puedan ser llamadas desde HTML
    window.cargarProductos = cargarProductos;
    window.ordenarPor = ordenarPor;
    window.limpiarFiltros = limpiarFiltros;
    window.abrirModalCrear = abrirModalCrear;
    window.cerrarModal = cerrarModal;
    window.editarProducto = editarProducto;
    window.confirmarEliminar = confirmarEliminar;
    window.verDetalleProducto = verDetalleProducto;
    window.exportarProductoPDF = exportarProductoPDF;
})();
