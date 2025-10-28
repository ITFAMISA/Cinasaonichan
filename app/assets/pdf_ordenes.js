// Configuración global
const BASE_PATH = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');

// Variables globales
let pdfActual = null;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    cargarHistorial();

    // Event listener para el formulario de upload
    document.getElementById('formUploadPdf').addEventListener('submit', procesarPDF);
});

/**
 * Procesa el PDF subido
 */
async function procesarPDF(e) {
    e.preventDefault();

    const btnProcesar = document.getElementById('btnProcesar');
    const fileInput = document.getElementById('pdfFile');
    const file = fileInput.files[0];

    if (!file) {
        mostrarAlerta('Por favor selecciona un archivo PDF', 'warning');
        return;
    }

    // Validar que sea PDF
    if (!file.name.toLowerCase().endsWith('.pdf')) {
        mostrarAlerta('Solo se permiten archivos PDF', 'danger');
        return;
    }

    // Deshabilitar botón
    btnProcesar.disabled = true;
    btnProcesar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';

    try {
        const formData = new FormData();
        formData.append('pdf_file', file);

        const response = await fetch(`${BASE_PATH}/app/controllers/pdf_ordenes_procesar.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            pdfActual = data.data;
            mostrarResultados(data.data);
            mostrarAlerta('PDF procesado correctamente', 'success');
            cargarHistorial(); // Recargar historial
            fileInput.value = ''; // Limpiar input
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al procesar PDF: ' + error.message, 'danger');
    } finally {
        btnProcesar.disabled = false;
        btnProcesar.innerHTML = '<i class="fas fa-upload mr-2"></i>Procesar PDF';
    }
}

/**
 * Muestra los resultados de la extracción
 */
function mostrarResultados(data) {
    // Mostrar card de resultados
    document.getElementById('cardResultados').style.display = 'block';

    // Mostrar texto extraído
    document.getElementById('textoExtraido').textContent = data.texto_extraido;

    // Mostrar datos detectados
    mostrarDatosDetectados(data.datos_estructurados);

    // NUEVO: Mostrar productos detallados si hay
    if (data.datos_estructurados.productos && data.datos_estructurados.productos.length > 0) {
        mostrarProductosDetallados(data.datos_estructurados.productos, data.id);
    } else if (data.datos_estructurados.codigos_detectados &&
        data.datos_estructurados.codigos_detectados.length > 0) {
        // Fallback: Si no hay productos detallados, usar el sistema anterior
        buscarProductosPorCodigos(data.datos_estructurados.codigos_detectados);
    }

    // Scroll a resultados
    document.getElementById('cardResultados').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Muestra los datos detectados en formato legible
 */
function mostrarDatosDetectados(datos) {
    const container = document.getElementById('datosDetectados');

    let html = '<div class="list-group">';

    // Número de orden
    html += crearItemDato('Número de Orden', datos.numero_orden, 'file-alt');

    // Fecha
    html += crearItemDato('Fecha', datos.fecha_orden, 'calendar');

    // Total
    if (datos.total) {
        const total = datos.moneda ? `${datos.moneda} ${datos.total}` : datos.total;
        html += crearItemDato('Total', total, 'dollar-sign');
    }

    // Códigos detectados
    if (datos.codigos_detectados && datos.codigos_detectados.length > 0) {
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <i class="fas fa-barcode text-primary"></i>
                        Códigos Detectados
                    </h6>
                    <span class="badge bg-primary rounded-pill">${datos.codigos_detectados.length}</span>
                </div>
                <div class="mt-2">
                    ${datos.codigos_detectados.map(codigo =>
                        `<span class="badge bg-secondary me-1 mb-1">${codigo}</span>`
                    ).join('')}
                </div>
            </div>
        `;
    }

    // Líneas de texto
    if (datos.lineas_texto && datos.lineas_texto.length > 0) {
        html += `
            <div class="list-group-item">
                <h6 class="mb-1">
                    <i class="fas fa-list text-info"></i>
                    Líneas de Texto (${datos.lineas_texto.length})
                </h6>
                <details class="mt-2">
                    <summary class="cursor-pointer text-primary">Ver todas las líneas</summary>
                    <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                        ${datos.lineas_texto.map((linea, idx) =>
                            `<div class="small text-muted">${idx + 1}. ${escapeHtml(linea)}</div>`
                        ).join('')}
                    </div>
                </details>
            </div>
        `;
    }

    html += '</div>';

    container.innerHTML = html;
}

/**
 * Crea un item de dato para mostrar
 */
function crearItemDato(label, valor, icon) {
    if (!valor) {
        return `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <i class="fas fa-${icon} text-muted"></i>
                        ${label}
                    </h6>
                </div>
                <p class="mb-0 text-muted small">No detectado</p>
            </div>
        `;
    }

    return `
        <div class="list-group-item">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">
                    <i class="fas fa-${icon} text-success"></i>
                    ${label}
                </h6>
            </div>
            <p class="mb-0"><strong>${escapeHtml(valor)}</strong></p>
        </div>
    `;
}

/**
 * Busca productos por códigos detectados
 */
async function buscarProductosPorCodigos(codigos) {
    try {
        const response = await fetch(`${BASE_PATH}/app/controllers/pdf_ordenes_buscar_productos.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ codigos: codigos })
        });

        const data = await response.json();

        if (data.success) {
            mostrarMapeoProductos(codigos, data.data);
        }
    } catch (error) {
        console.error('Error al buscar productos:', error);
    }
}

/**
 * Muestra la tabla de mapeo de productos
 */
function mostrarMapeoProductos(codigos, resultados) {
    const container = document.getElementById('tablaMapeoProductos');
    const card = document.getElementById('cardMapeoProductos');

    let html = '<div class="table-responsive"><table class="table table-bordered">';
    html += `
        <thead>
            <tr>
                <th>Código Detectado</th>
                <th>Productos Encontrados</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
    `;

    codigos.forEach(codigo => {
        const productosEncontrados = resultados[codigo] || [];

        html += `<tr>`;
        html += `<td><strong>${codigo}</strong></td>`;

        if (productosEncontrados.length === 0) {
            html += `
                <td class="text-muted">
                    <i class="fas fa-times-circle text-danger"></i>
                    No se encontraron productos
                </td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="buscarManualmente('${codigo}')">
                        <i class="fas fa-search"></i> Buscar manualmente
                    </button>
                </td>
            `;
        } else if (productosEncontrados.length === 1) {
            const producto = productosEncontrados[0];
            html += `
                <td>
                    <div class="text-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>${producto.material_code}</strong> - ${producto.descripcion}
                    </div>
                    <small class="text-muted">Coincidencia exacta</small>
                </td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="confirmarProducto('${codigo}', ${producto.id})">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </td>
            `;
        } else {
            html += `
                <td>
                    <div class="text-warning mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        Se encontraron ${productosEncontrados.length} productos similares
                    </div>
                    <select class="form-select form-select-sm" id="select_${codigo}">
                        <option value="">Selecciona un producto...</option>
                        ${productosEncontrados.map(p =>
                            `<option value="${p.id}">${p.material_code} - ${p.descripcion}</option>`
                        ).join('')}
                    </select>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="confirmarProductoSeleccionado('${codigo}')">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </td>
            `;
        }

        html += `</tr>`;
    });

    html += '</tbody></table></div>';

    container.innerHTML = html;
    card.style.display = 'block';
}

/**
 * Buscar producto manualmente
 */
function buscarManualmente(codigo) {
    const termino = prompt(`Buscar producto para código: ${codigo}\n\nIngresa un término de búsqueda:`, codigo);

    if (termino) {
        window.open(`productos.php?buscar=${encodeURIComponent(termino)}`, '_blank');
    }
}

/**
 * Confirmar producto único
 */
function confirmarProducto(codigo, productoId) {
    console.log(`Confirmando mapeo: ${codigo} -> Producto ID: ${productoId}`);
    mostrarAlerta(`Producto confirmado para código ${codigo}`, 'success');
}

/**
 * Confirmar producto seleccionado de lista
 */
function confirmarProductoSeleccionado(codigo) {
    const select = document.getElementById(`select_${codigo}`);
    const productoId = select.value;

    if (!productoId) {
        mostrarAlerta('Por favor selecciona un producto', 'warning');
        return;
    }

    confirmarProducto(codigo, productoId);
}

/**
 * Carga el historial de PDFs procesados
 */
async function cargarHistorial() {
    try {
        const response = await fetch(`${BASE_PATH}/app/controllers/pdf_ordenes_listar.php`);
        const data = await response.json();

        if (data.success) {
            mostrarHistorial(data.data);
        }
    } catch (error) {
        console.error('Error al cargar historial:', error);
        document.getElementById('tablaHistorial').innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger">
                    Error al cargar el historial
                </td>
            </tr>
        `;
    }
}

/**
 * Muestra el historial de PDFs
 */
function mostrarHistorial(pdfs) {
    const tbody = document.getElementById('tablaHistorial');

    if (pdfs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    No hay PDFs procesados
                </td>
            </tr>
        `;
        return;
    }

    let html = '';

    pdfs.forEach(pdf => {
        const datos = pdf.datos_estructurados || {};
        const numProductos = datos.codigos_detectados ? datos.codigos_detectados.length : 0;

        html += `
            <tr>
                <td>${pdf.id}</td>
                <td>
                    <i class="fas fa-file-pdf text-danger"></i>
                    ${escapeHtml(pdf.nombre_archivo)}
                </td>
                <td>${formatearFecha(pdf.fecha_proceso)}</td>
                <td>${datos.numero_orden || '<span class="text-muted">N/A</span>'}</td>
                <td>
                    ${numProductos > 0 ?
                        `<span class="badge bg-info">${numProductos} códigos</span>` :
                        '<span class="text-muted">Ninguno</span>'
                    }
                </td>
                <td>${obtenerBadgeEstatus(pdf.estatus)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="verDetallePdf(${pdf.id})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="${BASE_PATH}/${pdf.ruta_archivo}" class="btn btn-sm btn-secondary" target="_blank" title="Descargar PDF">
                        <i class="fas fa-download"></i>
                    </a>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

/**
 * Ver detalle de un PDF procesado
 */
function verDetallePdf(id) {
    // Aquí podrías implementar un modal con los detalles completos
    console.log('Ver detalle del PDF:', id);
    mostrarAlerta('Funcionalidad en desarrollo', 'info');
}

/**
 * Obtiene el badge de estatus
 */
function obtenerBadgeEstatus(estatus) {
    const badges = {
        'pendiente': '<span class="badge bg-warning">Pendiente</span>',
        'procesado': '<span class="badge bg-success">Procesado</span>',
        'error': '<span class="badge bg-danger">Error</span>'
    };

    return badges[estatus] || '<span class="badge bg-secondary">Desconocido</span>';
}

/**
 * Formatea una fecha
 */
function formatearFecha(fecha) {
    if (!fecha) return 'N/A';

    const date = new Date(fecha);
    return date.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Muestra una alerta
 */
function mostrarAlerta(mensaje, tipo = 'info') {
    // Crear alerta Bootstrap
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Escapa HTML para prevenir XSS
 */
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

/**
 * Muestra productos detallados extraídos del PDF
 */
function mostrarProductosDetallados(productos, pdfId) {
    const container = document.getElementById('tablaMapeoProductos');
    const card = document.getElementById('cardMapeoProductos');

    let html = '<div class="alert alert-success mb-3">';
    html += `<i class="fas fa-check-circle"></i> Se detectaron <strong>${productos.length}</strong> productos con información detallada`;
    html += '</div>';

    html += '<div class="table-responsive"><table class="table table-bordered table-hover">';
    html += `
        <thead class="table-light">
            <tr>
                <th>Item</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>UM</th>
                <th>Precio Unit.</th>
                <th>Campos Adicionales</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
    `;

    productos.forEach((producto, index) => {
        const camposAdicionales = contarCamposAdicionales(producto);

        html += `<tr id="producto-row-${index}">`;
        html += `<td>${producto.item || index + 1}</td>`;
        html += `<td><strong>${producto.material_code || 'N/A'}</strong></td>`;
        html += `<td>${escapeHtml(producto.descripcion) || '<span class="text-muted">No detectada</span>'}</td>`;
        html += `<td>${producto.cantidad || '<span class="text-muted">-</span>'}</td>`;
        html += `<td>${producto.unidad_medida || '<span class="text-muted">-</span>'}</td>`;
        html += `<td>${producto.precio_unitario ? '$' + producto.precio_unitario : '<span class="text-muted">-</span>'}</td>`;
        html += `<td>
            <span class="badge bg-info" onclick="verCamposAdicionales(${index})" style="cursor: pointer;">
                ${camposAdicionales} campos detectados
                <i class="fas fa-eye ml-1"></i>
            </span>
        </td>`;
        html += `<td>
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-primary" onclick="editarYCrearProducto(${index})" title="Revisar y Crear Producto">
                    <i class="fas fa-edit"></i> Revisar
                </button>
                <button class="btn btn-sm btn-success" onclick="crearProductoDirecto(${index})" title="Crear Producto Directamente">
                    <i class="fas fa-plus"></i> Crear
                </button>
            </div>
        </td>`;
        html += `</tr>`;

        // Fila oculta con detalles adicionales
        html += `<tr id="detalles-${index}" style="display: none;">
            <td colspan="8">
                <div class="p-3 bg-light">
                    <h6>Campos Adicionales Detectados:</h6>
                    <div class="row">
                        ${generarHTMLCamposAdicionales(producto)}
                    </div>
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';

    container.innerHTML = html;
    card.style.display = 'block';

    // Guardar productos en variable global para acceso posterior
    window.productosExtraidos = productos;
    window.pdfIdActual = pdfId;
}

/**
 * Cuenta cuántos campos adicionales fueron detectados
 */
function contarCamposAdicionales(producto) {
    const camposBase = ['item', 'material_code', 'descripcion', 'cantidad', 'unidad_medida', 'precio_unitario', 'total_linea'];
    let count = 0;

    for (let campo in producto) {
        if (producto[campo] && !camposBase.includes(campo)) {
            count++;
        }
    }

    return count;
}

/**
 * Genera HTML para mostrar campos adicionales
 */
function generarHTMLCamposAdicionales(producto) {
    const camposIgnorar = ['item', 'material_code', 'descripcion', 'cantidad', 'unidad_medida', 'precio_unitario', 'total_linea'];
    const nombresAmigables = {
        'drawing_number': 'Número de Dibujo',
        'drawing_version': 'Versión',
        'drawing_sheet': 'Hoja',
        'ecm_number': 'Número ECM',
        'hts_code': 'Código HTS',
        'hts_descripcion': 'Descripción HTS',
        'peso': 'Peso',
        'unidad_peso': 'Unidad de Peso',
        'material': 'Material',
        'acabado': 'Acabado',
        'pais_origen': 'País de Origen',
        'sistema_calidad': 'Sistema de Calidad',
        'especificaciones': 'Especificaciones',
        'tipo_parte': 'Tipo de Parte',
        'categoria': 'Categoría',
        'notas': 'Notas'
    };

    let html = '';

    for (let campo in producto) {
        if (producto[campo] && !camposIgnorar.includes(campo)) {
            const nombreAmigable = nombresAmigables[campo] || campo;
            html += `
                <div class="col-md-4 mb-2">
                    <small class="text-muted">${nombreAmigable}:</small><br>
                    <strong>${escapeHtml(producto[campo])}</strong>
                </div>
            `;
        }
    }

    return html || '<div class="col-12"><p class="text-muted mb-0">No se detectaron campos adicionales</p></div>';
}

/**
 * Muestra/oculta campos adicionales de un producto
 */
function verCamposAdicionales(index) {
    const fila = document.getElementById(`detalles-${index}`);
    if (fila.style.display === 'none') {
        fila.style.display = 'table-row';
    } else {
        fila.style.display = 'none';
    }
}

/**
 * Abre modal para editar y crear producto
 */
function editarYCrearProducto(index) {
    const producto = window.productosExtraidos[index];

    // Crear modal dinámico
    const modalHtml = `
        <div class="modal fade" id="modalCrearProducto" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-box-open"></i>
                            Crear Producto desde PDF
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${generarFormularioProducto(producto)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarProductoDesdeModal(${index})">
                            <i class="fas fa-save"></i> Guardar Producto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior si existe
    const modalAnterior = document.getElementById('modalCrearProducto');
    if (modalAnterior) {
        modalAnterior.remove();
    }

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCrearProducto'));
    modal.show();
}

/**
 * Genera formulario completo para crear producto
 */
function generarFormularioProducto(producto) {
    return `
        <form id="formProductoPDF">
            <div class="row">
                <!-- Información Básica -->
                <div class="col-12">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle text-primary"></i>
                        Información Básica
                    </h6>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Código de Material *</label>
                    <input type="text" class="form-control" name="material_code" value="${producto.material_code || ''}" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" name="descripcion" value="${escapeHtml(producto.descripcion || '')}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Unidad de Medida</label>
                    <select class="form-select" name="unidad_medida">
                        <option value="">Seleccionar...</option>
                        ${generarOpcionesSelect(['PZ', 'EA', 'KG', 'LB', 'MT', 'FT', 'UN'], producto.unidad_medida)}
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">País de Origen</label>
                    <input type="text" class="form-control" name="pais_origen" value="${producto.pais_origen || ''}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Categoría</label>
                    <input type="text" class="form-control" name="categoria" value="${producto.categoria || ''}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo de Parte</label>
                    <select class="form-select" name="tipo_parte">
                        <option value="">Seleccionar...</option>
                        ${generarOpcionesSelect(['Standard Part', 'Custom', 'Raw Material'], producto.tipo_parte)}
                    </select>
                </div>

                <!-- Información de Dibujo -->
                <div class="col-12 mt-3">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-drafting-compass text-primary"></i>
                        Información de Dibujo
                    </h6>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Número de Dibujo</label>
                    <input type="text" class="form-control" name="drawing_number" value="${producto.drawing_number || ''}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Versión</label>
                    <input type="text" class="form-control" name="drawing_version" value="${producto.drawing_version || ''}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Número ECM</label>
                    <input type="text" class="form-control" name="ecm_number" value="${producto.ecm_number || ''}">
                </div>

                <!-- Información Técnica -->
                <div class="col-12 mt-3">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-cogs text-primary"></i>
                        Información Técnica
                    </h6>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Material</label>
                    <input type="text" class="form-control" name="material" value="${producto.material || ''}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Acabado</label>
                    <input type="text" class="form-control" name="acabado" value="${producto.acabado || ''}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sistema de Calidad</label>
                    <input type="text" class="form-control" name="sistema_calidad" value="${producto.sistema_calidad || ''}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Peso</label>
                    <input type="number" step="0.001" class="form-control" name="peso" value="${producto.peso || ''}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Unidad de Peso</label>
                    <select class="form-select" name="unidad_peso">
                        <option value="">Seleccionar...</option>
                        ${generarOpcionesSelect(['KG', 'LB', 'G'], producto.unidad_peso)}
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Precio Unitario</label>
                    <input type="number" step="0.01" class="form-control" name="precio_unitario" value="${producto.precio_unitario || ''}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estatus</label>
                    <select class="form-select" name="estatus">
                        ${generarOpcionesSelect(['activo', 'inactivo'], 'activo')}
                    </select>
                </div>

                <!-- Información Aduanal -->
                <div class="col-12 mt-3">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-globe text-primary"></i>
                        Información Aduanal
                    </h6>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Código HTS</label>
                    <input type="text" class="form-control" name="hts_code" value="${producto.hts_code || ''}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Descripción HTS</label>
                    <input type="text" class="form-control" name="hts_descripcion" value="${producto.hts_descripcion || ''}">
                </div>

                <!-- Notas y Especificaciones -->
                <div class="col-12 mt-3">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-sticky-note text-primary"></i>
                        Notas y Especificaciones
                    </h6>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" name="notas" rows="3">${producto.notas || ''}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Especificaciones</label>
                    <textarea class="form-control" name="especificaciones" rows="3">${producto.especificaciones || ''}</textarea>
                </div>
            </div>
        </form>
    `;
}

/**
 * Genera opciones de select con valor seleccionado
 */
function generarOpcionesSelect(opciones, valorSeleccionado) {
    return opciones.map(opcion =>
        `<option value="${opcion}" ${opcion === valorSeleccionado ? 'selected' : ''}>${opcion}</option>`
    ).join('');
}

/**
 * Guarda producto desde el modal
 */
async function guardarProductoDesdeModal(index) {
    const form = document.getElementById('formProductoPDF');
    const formData = new FormData(form);

    // Agregar usuario de creación
    formData.append('usuario_creacion', 'SYSTEM'); // Cambiar por usuario actual si existe sesión

    try {
        const response = await fetch(`${BASE_PATH}/app/controllers/productos_crear.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            mostrarAlerta('Producto creado correctamente', 'success');

            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearProducto'));
            modal.hide();

            // Marcar producto como creado
            const fila = document.getElementById(`producto-row-${index}`);
            if (fila) {
                fila.classList.add('table-success');
                fila.querySelector('.btn-group').innerHTML = `
                    <span class="badge bg-success">
                        <i class="fas fa-check"></i> Producto creado
                    </span>
                `;
            }
        } else {
            throw new Error(data.mensaje || 'Error al crear producto');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al crear producto: ' + error.message, 'danger');
    }
}

/**
 * Crea producto directamente sin editar
 */
async function crearProductoDirecto(index) {
    if (!confirm('¿Crear el producto con la información detectada automáticamente?')) {
        return;
    }

    const producto = window.productosExtraidos[index];

    // Preparar datos para envío
    const formData = new FormData();

    // Campos obligatorios
    formData.append('material_code', producto.material_code || '');
    formData.append('descripcion', producto.descripcion || '');
    formData.append('unidad_medida', producto.unidad_medida || '');
    formData.append('pais_origen', producto.pais_origen || '');
    formData.append('categoria', producto.categoria || '');
    formData.append('drawing_number', producto.drawing_number || '');
    formData.append('drawing_version', producto.drawing_version || '');
    formData.append('drawing_sheet', producto.drawing_sheet || '');
    formData.append('ecm_number', producto.ecm_number || '');
    formData.append('hts_code', producto.hts_code || '');
    formData.append('hts_descripcion', producto.hts_descripcion || '');
    formData.append('tipo_parte', producto.tipo_parte || '');
    formData.append('sistema_calidad', producto.sistema_calidad || '');
    formData.append('material', producto.material || '');
    formData.append('acabado', producto.acabado || '');
    formData.append('peso', producto.peso || '');
    formData.append('unidad_peso', producto.unidad_peso || '');
    formData.append('precio_unitario', producto.precio_unitario || '');
    formData.append('estatus', 'activo');
    formData.append('notas', `Creado desde PDF. Orden: ${pdfActual?.datos_estructurados?.numero_orden || 'N/A'}`);
    formData.append('especificaciones', producto.especificaciones || '');
    formData.append('usuario_creacion', 'SYSTEM');

    // Campos adicionales opcionales
    ['material_revision', 'change_number', 'nivel_componente', 'componente_linea', 'ref_documento'].forEach(campo => {
        if (producto[campo]) {
            formData.append(campo, producto[campo]);
        } else {
            formData.append(campo, '');
        }
    });

    try {
        const response = await fetch(`${BASE_PATH}/app/controllers/productos_crear.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            mostrarAlerta('Producto creado correctamente', 'success');

            // Marcar producto como creado
            const fila = document.getElementById(`producto-row-${index}`);
            if (fila) {
                fila.classList.add('table-success');
                fila.querySelector('.btn-group').innerHTML = `
                    <span class="badge bg-success">
                        <i class="fas fa-check"></i> Producto creado (ID: ${data.id})
                    </span>
                `;
            }
        } else {
            throw new Error(data.mensaje || 'Error al crear producto');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al crear producto: ' + error.message, 'danger');
    }
}
