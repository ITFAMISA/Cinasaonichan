// Dashboard del Taller - Visualización Visual de Estaciones
(function() {
    'use strict';

    let estacionesData = [];
    let asignacionesData = [];
    let autoRefreshInterval = null;
    let elementoEnArrastre = null;
    let offsetX = 0;
    let offsetY = 0;
    const canvas = document.getElementById('tallerCanvas');
    let asignacionEnEdicion = null;
    let estacionDragada = null;
    let naveActual = null;

    document.addEventListener('DOMContentLoaded', function() {
        cargarDatos();

        // Event listeners
        document.getElementById('btnRefrescar').addEventListener('click', cargarDatos);
        document.getElementById('btnAutorefresh').addEventListener('click', toggleAutorefresh);
        document.getElementById('sliderZoom').addEventListener('change', aplicarZoom);

        // Canvas events
        canvas.addEventListener('dragover', handleDragOver);
        canvas.addEventListener('drop', handleDrop);
        canvas.addEventListener('dragend', handleDragEnd);
    });

    function cargarDatos() {
        Promise.all([
            fetch(`${BASE_URL}/app/controllers/estaciones_obtener_todas.php`).then(r => r.json()),
            fetch(`${BASE_URL}/app/controllers/asignaciones_listar.php`).then(r => r.json())
        ])
        .then(([estacionesResp, asignacionesResp]) => {
            if (estacionesResp.success && asignacionesResp.success) {
                estacionesData = estacionesResp.data;
                asignacionesData = asignacionesResp.data;
                dibujarTaller();
                actualizarEstadisticas();
            } else {
                mostrarAlerta('Error al cargar datos del taller', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al cargar datos', 'danger');
        });
    }

    function dibujarTaller() {
        console.log('dibujarTaller() iniciado');
        console.log('estacionesData:', estacionesData);
        console.log('asignacionesData:', asignacionesData);

        canvas.innerHTML = '';

        // Agrupar estaciones por nave
        const estacionesPorNave = {};
        estacionesData.forEach(estacion => {
            const nave = estacion.nave || 'Nave 1';
            if (!estacionesPorNave[nave]) {
                estacionesPorNave[nave] = [];
            }
            estacionesPorNave[nave].push(estacion);
        });

        console.log('estacionesPorNave:', estacionesPorNave);

        // Ordenar naves por nombre
        const navesOrdenadas = Object.keys(estacionesPorNave).sort();

        // Mapeo de colores por nave
        const coloresPorNave = {
            'nave-1': '#3498db',
            'nave-2': '#2ecc71',
            'nave-3': '#f39c12',
            'default': '#34495e'
        };

        // Crear sección para cada nave
        navesOrdenadas.forEach(nombreNave => {
            const naveSection = document.createElement('div');
            const naveClass = nombreNave.toLowerCase().replace(/\s+/g, '-');
            naveSection.className = `nave-section ${naveClass}`;

            // Título de la nave
            const naveTitulo = document.createElement('div');
            naveTitulo.className = 'nave-titulo';
            naveTitulo.textContent = nombreNave;
            naveSection.appendChild(naveTitulo);

            // Grid de máquinas en la nave
            const naveGrid = document.createElement('div');
            naveGrid.className = 'nave-grid';

            estacionesPorNave[nombreNave].forEach(estacion => {
                const elemento = document.createElement('div');
                elemento.className = `estacion-item ${estacion.estatus}`;
                elemento.id = `estacion-${estacion.id}`;

                // Obtener color de la nave
                const colorNave = coloresPorNave[naveClass] || coloresPorNave['default'];
                elemento.style.setProperty('--color-nave', colorNave);

                // Obtener trabajo actual
                const trabajo = asignacionesData.find(a => a.estacion_id === estacion.id);
                const claseEstado = trabajo ? trabajo.estatus : 'libre';

                if (trabajo && trabajo.estatus === 'en_progreso') {
                    elemento.classList.add('en-progreso');
                }

                let html = `<div class="estacion-nombre">${htmlEscape(estacion.nombre)}</div>
                            <div class="estacion-tipo">${htmlEscape(estacion.tipo)}</div>`;

                // Mostrar estado de la máquina si no está activa
                if (estacion.estatus !== 'activa') {
                    const estadoLabel = estacion.estatus === 'mantenimiento' ? 'Mantenimiento' : 'Inactiva';
                    const estadoClass = estacion.estatus === 'mantenimiento' ? 'badge-warning' : 'badge-danger';
                    html += `<div class="estacion-estado-badge ${estadoClass}">${estadoLabel}</div>`;
                }

                if (trabajo) {
                    html += `<div class="estacion-trabajo">
                        <div class="trabajo-badge ${trabajo.estatus}">
                            ${trabajo.estatus === 'en_progreso' ? 'Procesando' :
                              trabajo.estatus === 'pendiente' ? 'Pendiente' : 'Pausado'}
                        </div>
                        <div class="estacion-pedido">${htmlEscape(trabajo.numero_pedido)}</div>
                        <div style="font-size: 9px; margin-top: 2px;">
                            ${trabajo.cantidad_procesada}/${trabajo.cantidad_total}
                        </div>
                    </div>`;
                } else {
                    const mensajeDisponible = estacion.estatus === 'activa' ? 'Disponible' : 'Sin asignar';
                    html += `<div class="estacion-trabajo text-muted" style="font-size: 11px;">${mensajeDisponible}</div>`;
                }

                elemento.innerHTML = html;

                // Hacer elemento arrastrable para reordenar
                elemento.draggable = true;
                elemento.addEventListener('dragstart', (e) => iniciarDragReordenar(e, estacion.id, nombreNave));
                elemento.addEventListener('dragover', (e) => permitirDropReordenar(e, naveGrid));
                elemento.addEventListener('drop', (e) => completarDropReordenar(e, naveGrid, nombreNave));
                elemento.addEventListener('dragend', () => finalizarDragReordenar());

                elemento.addEventListener('click', () => mostrarDetallesTrabajo(estacion.id, trabajo));

                naveGrid.appendChild(elemento);
            });

            naveSection.appendChild(naveGrid);
            canvas.appendChild(naveSection);
        });
    }

    function handleDragStart(e) {
        elementoEnArrastre = this;
        const rect = this.getBoundingClientRect();
        const canvasRect = canvas.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDrop(e) {
        e.preventDefault();

        if (elementoEnArrastre) {
            const canvasRect = canvas.getBoundingClientRect();
            const newX = e.clientX - canvasRect.left - offsetX;
            const newY = e.clientY - canvasRect.top - offsetY;

            elementoEnArrastre.style.left = newX + 'px';
            elementoEnArrastre.style.top = newY + 'px';

            // Guardar nueva posición
            const estacionId = elementoEnArrastre.id.replace('estacion-', '');
            guardarUbicacion(estacionId, newX, newY);
        }
    }

    function handleDragEnd(e) {
        elementoEnArrastre = null;
    }

    function guardarUbicacion(estacionId, x, y) {
        const formData = new FormData();
        formData.append('id', estacionId);
        formData.append('ubicacion_x', Math.round(x));
        formData.append('ubicacion_y', Math.round(y));

        fetch(`${BASE_URL}/app/controllers/estaciones_editar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error al guardar ubicación:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function mostrarDetallesTrabajo(estacionId, trabajo) {
        const estacion = estacionesData.find(e => e.id === estacionId);

        if (!estacion) return;

        asignacionEnEdicion = trabajo;

        let contenido = `
            <div class="mb-3">
                <h6><i class="fas fa-industry"></i> ${htmlEscape(estacion.nombre)}</h6>
                <p class="text-muted mb-2">${htmlEscape(estacion.tipo)}</p>
            </div>
        `;

        let botonesFooter = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        `;

        if (trabajo) {
            contenido += `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Pedido</label>
                        <p><strong>${htmlEscape(trabajo.numero_pedido)}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Proceso</label>
                        <p><strong>${htmlEscape(trabajo.proceso_nombre || 'N/A')}</strong></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Producto</label>
                        <p><small>${htmlEscape(trabajo.material_code || trabajo.producto_id)}</small></p>
                        <p><small class="text-muted">${htmlEscape(trabajo.descripcion || '')}</small></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <p>
                            <span class="trabajo-badge ${trabajo.estatus}">
                                ${trabajo.estatus.toUpperCase()}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Cantidad Total</label>
                        <p><strong>${trabajo.cantidad_total}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Procesada</label>
                        <p><strong>${trabajo.cantidad_procesada}</strong></p>
                    </div>
                </div>

                <div class="progress mb-3">
                    <div class="progress-bar" style="width: ${(trabajo.cantidad_procesada / trabajo.cantidad_total * 100)}%">
                        ${Math.round((trabajo.cantidad_procesada / trabajo.cantidad_total * 100))}%
                    </div>
                </div>
            `;

            // Botones de control dinámicos
            if (trabajo.estatus === 'pendiente') {
                botonesFooter += `
                    <button type="button" class="btn btn-warning" onclick="pausarTrabajo()">
                        <i class="fas fa-pause"></i> Pausar
                    </button>
                `;
            } else if (trabajo.estatus === 'en_progreso') {
                botonesFooter += `
                    <button type="button" class="btn btn-warning" onclick="pausarTrabajo()">
                        <i class="fas fa-pause"></i> Pausar
                    </button>
                `;
            } else if (trabajo.estatus === 'pausada') {
                botonesFooter += `
                    <button type="button" class="btn btn-success" onclick="reanudarTrabajo()">
                        <i class="fas fa-play"></i> Reanudar
                    </button>
                `;
            }

            botonesFooter += `
                <button type="button" class="btn btn-primary" onclick="marcarCompleto()">
                    <i class="fas fa-check"></i> Marcar como Completado
                </button>
            `;
        } else {
            contenido += `<p class="text-muted text-center">Esta estación no tiene trabajos asignados</p>`;
        }

        const modalHTML = `
            <div class="modal fade" id="modalDetallesTrabajo" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalles - ${htmlEscape(estacion.nombre)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${contenido}
                        </div>
                        <div class="modal-footer">
                            ${botonesFooter}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal existente si lo hay
        const existingModal = document.getElementById('modalDetallesTrabajo');
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
        const modal = new bootstrap.Modal(document.getElementById('modalDetallesTrabajo'));
        modal.show();
    }

    function pausarTrabajo() {
        if (!asignacionEnEdicion) return;

        const formData = new FormData();
        formData.append('id', asignacionEnEdicion.id);
        formData.append('estatus', 'pausada');
        formData.append('solo_estatus', '1');

        fetch(`${BASE_URL}/app/controllers/asignaciones_actualizar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Trabajo pausado', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetallesTrabajo'));
                if (modal) modal.hide();
                cargarDatos();
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al pausar trabajo', 'danger');
        });
    }

    function reanudarTrabajo() {
        if (!asignacionEnEdicion) return;

        const formData = new FormData();
        formData.append('id', asignacionEnEdicion.id);
        formData.append('estatus', 'en_progreso');
        formData.append('solo_estatus', '1');

        fetch(`${BASE_URL}/app/controllers/asignaciones_actualizar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Trabajo reanudado', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetallesTrabajo'));
                if (modal) modal.hide();
                cargarDatos();
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al reanudar trabajo', 'danger');
        });
    }

    function marcarCompleto() {
        if (!asignacionEnEdicion) return;

        const cantidad = prompt('Ingrese cantidad procesada final:', asignacionEnEdicion.cantidad_total);
        if (cantidad === null) return;

        const formData = new FormData();
        formData.append('id', asignacionEnEdicion.id);
        formData.append('estatus', 'completada');
        formData.append('cantidad_procesada', cantidad);
        formData.append('solo_estatus', '1');

        fetch(`${BASE_URL}/app/controllers/asignaciones_actualizar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Trabajo completado', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetallesTrabajo'));
                if (modal) modal.hide();
                cargarDatos();
            } else {
                mostrarAlerta('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al completar trabajo', 'danger');
        });
    }

    // ==================== FUNCIONES DRAG & DROP PARA REORDENAR ====================

    function iniciarDragReordenar(e, estacionId, nave) {
        estacionDragada = {
            id: estacionId,
            elemento: e.target.closest('.estacion-item'),
            naveOrigen: nave
        };
        naveActual = nave;

        e.target.closest('.estacion-item').style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', e.target.closest('.estacion-item').innerHTML);
    }

    function permitirDropReordenar(e, naveGrid) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        // Dar visual feedback
        const elementoSobre = e.target.closest('.estacion-item');
        if (elementoSobre && elementoSobre !== estacionDragada?.elemento) {
            elementoSobre.style.borderColor = '#ffc107';
            elementoSobre.style.borderWidth = '3px';
        }
    }

    function completarDropReordenar(e, naveGrid, nave) {
        e.preventDefault();

        const elementoDestino = e.target.closest('.estacion-item');

        if (!estacionDragada || !elementoDestino || elementoDestino === estacionDragada.elemento) {
            return;
        }

        if (nave !== estacionDragada.naveOrigen) {
            mostrarAlerta('No se puede mover a otra nave', 'warning');
            return;
        }

        // Intercambiar posición en el DOM
        const contenedor = elementoDestino.parentNode;
        const indiceDestino = Array.from(contenedor.children).indexOf(elementoDestino);
        const indiceOrigen = Array.from(contenedor.children).indexOf(estacionDragada.elemento);

        if (indiceDestino < indiceOrigen) {
            elementoDestino.parentNode.insertBefore(estacionDragada.elemento, elementoDestino);
        } else {
            elementoDestino.parentNode.insertBefore(estacionDragada.elemento, elementoDestino.nextSibling);
        }

        // Obtener nuevo orden de las estaciones
        const estacionesEnNave = Array.from(naveGrid.querySelectorAll('.estacion-item'));
        const nuevoOrden = {};

        estacionesEnNave.forEach((elem, indice) => {
            const id = elem.id.replace('estacion-', '');
            nuevoOrden[id] = indice;
        });

        // Guardar en BD
        guardarOrdenEstaciones(nave, nuevoOrden);
    }

    function finalizarDragReordenar() {
        if (estacionDragada) {
            estacionDragada.elemento.style.opacity = '1';
        }

        // Limpiar estilos visuales de todos los elementos
        document.querySelectorAll('.estacion-item').forEach(elem => {
            elem.style.borderColor = '';
            elem.style.borderWidth = '';
        });

        estacionDragada = null;
        naveActual = null;
    }

    function guardarOrdenEstaciones(nave, nuevoOrden) {
        const datos = {
            nave: nave,
            estaciones: nuevoOrden
        };

        fetch(`${BASE_URL}/app/controllers/estaciones_reordenar.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(`Orden actualizado: ${data.estaciones_actualizadas} estaciones`, 'success');
                console.log('Orden guardado correctamente:', data);
            } else {
                mostrarAlerta('Error al guardar orden: ' + (data.error || 'Unknown error'), 'danger');
                // Recargar para revertir cambios
                setTimeout(() => cargarDatos(), 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al guardar orden', 'danger');
            // Recargar para revertir cambios
            setTimeout(() => cargarDatos(), 1000);
        });
    }

    function actualizarEstadisticas() {
        // Las estadísticas ya no se muestran en la UI
        // Esta función se mantiene para compatibilidad pero no hace nada
    }

    function aplicarZoom(e) {
        const zoom = e.target.value / 100;
        canvas.style.transform = `scale(${zoom})`;
        canvas.style.transformOrigin = 'top left';
        document.getElementById('zoomValue').textContent = e.target.value + '%';
    }

    function toggleAutorefresh() {
        const btn = document.getElementById('btnAutorefresh');

        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
            mostrarAlerta('Auto-refrescar desactivado', 'info');
        } else {
            autoRefreshInterval = setInterval(cargarDatos, 3000); // Refrescar cada 3 segundos
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            mostrarAlerta('Auto-refrescar activado cada 3 segundos', 'success');
        }
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

    // Funciones globales
    window.mostrarAlerta = mostrarAlerta;
    window.htmlEscape = htmlEscape;
    window.pausarTrabajo = pausarTrabajo;
    window.reanudarTrabajo = reanudarTrabajo;
    window.marcarCompleto = marcarCompleto;
})();
