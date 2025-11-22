/**
 * Módulo de Mantenimiento de Estaciones
 * Gestiona procesos, horas y mantenimiento de máquinas en el tracking dashboard
 * Los modales se generan dinámicamente siguiendo el patrón de la aplicación
 */

(function() {
    'use strict';

    let estacionSeleccionada = null;
    let procesosEstacion = [];
    let mantenimientoActivo = null;
    let debounceCargarEstaciones = null;

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        inicializarEventos();
        observarEstaciones();
    });

    function inicializarEventos() {
        // Delegación de eventos para las estaciones - click derecho
        document.addEventListener('contextmenu', function(e) {
            const estacionItem = e.target.closest('.estacion-item');
            if (estacionItem && !e.target.closest('.btn')) {
                e.preventDefault();
                abrirModalProcesos(estacionItem);
            }
        });
    }

    function observarEstaciones() {
        // Usar MutationObserver para detectar cuando se agregan nuevas estaciones
        const naves = document.querySelectorAll('.area-content');
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    agregarTooltipEstaciones();
                    cargarEstacionesEnMantenimiento();
                }
            });
        });

        naves.forEach(nave => {
            observer.observe(nave, { childList: true });
        });

        // Agregar tooltips iniciales
        agregarTooltipEstaciones();

        // Cargar estaciones en mantenimiento
        cargarEstacionesEnMantenimiento();
    }

    function cargarEstacionesEnMantenimiento() {
        // Debounce para evitar múltiples llamadas simultáneas
        if (debounceCargarEstaciones) {
            clearTimeout(debounceCargarEstaciones);
        }

        debounceCargarEstaciones = setTimeout(() => {
            // Obtener todas las estaciones visibles
            const estaciones = document.querySelectorAll('.estacion-item[data-id]');
            if (estaciones.length === 0) return;

            // Agrupar las peticiones: máximo 5 simultáneas
            const estacionesArray = Array.from(estaciones);
            const chunkSize = 5;

            for (let i = 0; i < estacionesArray.length; i += chunkSize) {
                const chunk = estacionesArray.slice(i, i + chunkSize);

                setTimeout(() => {
                    chunk.forEach(estacionEl => {
                        const estacionId = parseInt(estacionEl.dataset.id);

                        fetch(`${BASE_URL}/app/controllers/estacion_procesos_mantenimiento_listar.php?estacion_id=${estacionId}`, {
                            method: 'GET'
                        })
                            .then(response => response.ok ? response.json() : null)
                            .then(data => {
                                if (data && data.mantenimiento_activo) {
                                    // Está en mantenimiento, agregar clase
                                    estacionEl.classList.add('en-mantenimiento');
                                } else {
                                    // No está en mantenimiento, remover clase si la tiene
                                    estacionEl.classList.remove('en-mantenimiento');
                                }
                            })
                            .catch(error => {
                                // Silenciar errores de carga
                                console.debug('Error checking maintenance status:', error);
                            });
                    });
                }, 100 * Math.ceil(i / chunkSize));
            }
        }, 300);
    }

    function agregarTooltipEstaciones() {
        document.querySelectorAll('.estacion-item:not([data-tiene-tooltip])').forEach(estacion => {
            estacion.dataset.tieneTooltip = 'true';
            estacion.style.cursor = 'pointer';
            estacion.title = 'Click derecho para ver procesos y mantenimiento';
        });
    }

    function abrirModalProcesos(estacionItem) {
        if (!estacionItem) return;

        estacionSeleccionada = {
            id: parseInt(estacionItem.dataset.id),
            nombre: estacionItem.dataset.nombre
        };

        // Crear y mostrar modal dinámicamente
        crearYMostrarModal();
    }

    function crearYMostrarModal() {
        // Generar HTML del modal
        const modalHTML = generarHTMLModal();

        // Remover modal existente si lo hay
        const existingModal = document.getElementById('modalProcesosMantenimiento');
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

        // Agregar event listeners
        document.getElementById('btnIniciarMantenimiento').addEventListener('click', iniciarMantenimiento);
        document.getElementById('btnFinalizarMantenimiento').addEventListener('click', finalizarMantenimiento);

        // Cargar datos
        cargarProcesosEstacion(estacionSeleccionada.id);

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalProcesosMantenimiento'));
        modal.show();
    }

    function generarHTMLModal() {
        return `
            <div class="modal fade" id="modalProcesosMantenimiento" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-cog me-2"></i>
                                <span>Procesos y Mantenimiento - ${escapeHtml(estacionSeleccionada.nombre)}</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Información de mantenimiento activo -->
                            <div id="infoMantenimientoActivo" style="display: none;">
                                <div class="alert alert-warning mb-3">
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <strong><i class="fas fa-wrench me-1"></i> Motivo:</strong>
                                            <span id="motivoMantenimiento"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong><i class="fas fa-clock me-1"></i> Desde:</strong>
                                            <span id="fechaInicioMantenimiento"></span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <strong><i class="fas fa-file-alt me-1"></i> Descripción:</strong>
                                            <span id="descripcionMantenimiento"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de procesos -->
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2">
                                    <i class="fas fa-list me-2"></i> Procesos Asignados
                                </h6>
                                <table class="table table-sm table-striped" id="tablaProcesos">
                                    <thead style="background-color: #3498db; color: white;">
                                        <tr>
                                            <th style="color: white;">Proceso</th>
                                            <th class="text-center" style="width: 80px; color: white;">Máquinas</th>
                                            <th class="text-center" style="width: 120px; color: white;">Horas Posibles</th>
                                            <th class="text-center" style="width: 120px; color: white;">Mantenimiento (h)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoTablaProcesos">
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <i class="fas fa-spinner fa-spin me-2"></i> Cargando procesos...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Formulario de mantenimiento -->
                            <div class="border-top pt-3">
                                <h6 class="border-bottom pb-2">
                                    <i class="fas fa-tools me-2"></i> Registrar Mantenimiento
                                </h6>

                                <!-- Si hay mantenimiento activo -->
                                <div id="seccionFinalizarMantenimiento" style="display: none;">
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Estación en mantenimiento. Complete ingresando la hora de finalización.
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Hora de Finalización <span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control form-control-sm" id="fechaFinMantenimiento">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Proceso <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="procesoFinMantenimiento">
                                                <option value="">Seleccione un proceso...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" id="btnFinalizarMantenimiento">
                                        <i class="fas fa-check me-1"></i> Finalizar Mantenimiento
                                    </button>
                                </div>

                                <!-- Si no hay mantenimiento activo -->
                                <div id="seccionIniciarMantenimiento">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Motivo <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="motivoMantenimientoNuevo">
                                                <option value="">Seleccione un motivo...</option>
                                                <option value="Máquina rota">Máquina rota</option>
                                                <option value="Limpieza">Limpieza</option>
                                                <option value="Cambio de orden de trabajo">Cambio de orden de trabajo</option>
                                                <option value="Calibración">Calibración</option>
                                                <option value="Reparación">Reparación</option>
                                                <option value="Mantenimiento preventivo">Mantenimiento preventivo</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Hora de Inicio <span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control form-control-sm" id="fechaInicioMantenimientoNuevo">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Descripción (Opcional)</label>
                                        <textarea class="form-control form-control-sm" id="descripcionMantenimientoNuevo"
                                                  rows="2" placeholder="Detalles adicionales sobre el mantenimiento..."></textarea>
                                    </div>
                                    <button type="button" class="btn btn-warning btn-sm" id="btnIniciarMantenimiento">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Marcar en Mantenimiento
                                    </button>
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
    }

    function cargarProcesosEstacion(estacionId) {
        fetch(`${BASE_URL}/app/controllers/estacion_procesos_mantenimiento_listar.php?estacion_id=${estacionId}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    procesosEstacion = data.procesos;
                    mantenimientoActivo = data.mantenimiento_activo;
                    renderizarProcesos(data.procesos);
                    actualizarSeccionMantenimiento(data.mantenimiento_activo, data.procesos);
                } else {
                    mostrarErrorEnTabla('Error: ' + (data.message || 'No se pudieron cargar los procesos'));
                }
            })
            .catch(error => {
                console.error('Error al cargar procesos:', error);
                mostrarErrorEnTabla('Error al conectar con el servidor');
            });
    }

    function renderizarProcesos(procesos) {
        const tbody = document.getElementById('cuerpoTablaProcesos');

        if (!procesos || procesos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">
                        <i class="fas fa-info-circle me-2"></i> No hay procesos asignados a esta estación
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = procesos.map(proceso => `
            <tr>
                <td><strong>${escapeHtml(proceso.nombre)}</strong></td>
                <td class="text-center">${proceso.cantidad_maquinas}</td>
                <td class="text-center">${parseFloat(proceso.horas_posibles).toFixed(1)}h</td>
                <td class="text-center">
                    <span class="badge bg-info">${parseFloat(proceso.horas_mantenimiento).toFixed(2)}h</span>
                </td>
            </tr>
        `).join('');
    }

    function mostrarErrorEnTabla(mensaje) {
        const tbody = document.getElementById('cuerpoTablaProcesos');
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-danger py-3">
                    <i class="fas fa-exclamation-triangle me-2"></i> ${escapeHtml(mensaje)}
                </td>
            </tr>
        `;
    }

    function actualizarSeccionMantenimiento(mantenimiento, procesos) {
        const seccionIniciar = document.getElementById('seccionIniciarMantenimiento');
        const seccionFinalizar = document.getElementById('seccionFinalizarMantenimiento');
        const infoMantenimiento = document.getElementById('infoMantenimientoActivo');

        if (mantenimiento) {
            // Mostrar información de mantenimiento activo
            seccionIniciar.style.display = 'none';
            seccionFinalizar.style.display = 'block';
            infoMantenimiento.style.display = 'block';

            // Llenar información
            document.getElementById('motivoMantenimiento').textContent = mantenimiento.motivo;
            document.getElementById('fechaInicioMantenimiento').textContent = formatearFecha(mantenimiento.fecha_inicio);
            document.getElementById('descripcionMantenimiento').textContent = mantenimiento.descripcion || '(Sin descripción)';

            // Cargar dropdown de procesos para finalizar
            const selectProceso = document.getElementById('procesoFinMantenimiento');
            selectProceso.innerHTML = '<option value="">Seleccione un proceso...</option>' +
                procesos.map(p => `<option value="${p.id}">${escapeHtml(p.nombre)}</option>`).join('');

            // Si solo hay un proceso, seleccionarlo automáticamente
            if (procesos.length === 1) {
                selectProceso.value = procesos[0].id;
            }

            // Establecer datetime actual
            document.getElementById('fechaFinMantenimiento').value = obtenerFechaHoraActual();
        } else {
            // No hay mantenimiento activo
            seccionIniciar.style.display = 'block';
            seccionFinalizar.style.display = 'none';
            infoMantenimiento.style.display = 'none';

            // Limpiar formulario
            document.getElementById('motivoMantenimientoNuevo').value = '';
            document.getElementById('descripcionMantenimientoNuevo').value = '';
            document.getElementById('fechaInicioMantenimientoNuevo').value = obtenerFechaHoraActual();
        }
    }

    function iniciarMantenimiento() {
        if (!estacionSeleccionada) return;

        const motivo = document.getElementById('motivoMantenimientoNuevo').value.trim();
        const descripcion = document.getElementById('descripcionMantenimientoNuevo').value.trim();
        const fechaInicioInput = document.getElementById('fechaInicioMantenimientoNuevo').value;

        if (!motivo) {
            alert('Por favor seleccione un motivo para el mantenimiento');
            return;
        }

        if (!fechaInicioInput) {
            alert('Por favor indique la hora de inicio del mantenimiento');
            return;
        }

        // Convertir formato datetime-local (YYYY-MM-DDTHH:mm) a formato MySQL (YYYY-MM-DD HH:mm:ss)
        const fechaInicio = fechaInicioInput.replace('T', ' ') + ':00';

        const datos = {
            estacion_id: estacionSeleccionada.id,
            motivo: motivo,
            descripcion: descripcion || null,
            fecha_inicio: fechaInicio
        };

        // Deshabilitar botón mientras se procesa
        const btnIniciar = document.getElementById('btnIniciarMantenimiento');
        const btnAnterior = btnIniciar.innerHTML;
        btnIniciar.disabled = true;
        btnIniciar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';

        fetch(`${BASE_URL}/app/controllers/estacion_mantenimiento_crear.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        })
            .then(response => response.json())
            .then(data => {
                btnIniciar.disabled = false;
                btnIniciar.innerHTML = btnAnterior;

                if (data.success) {
                    alert('Estación marcada en mantenimiento exitosamente');
                    mantenimientoActivo = data.mantenimiento;
                    actualizarSeccionMantenimiento(data.mantenimiento, procesosEstacion);

                    // Actualizar estación visualmente
                    if (estacionSeleccionada) {
                        const estacionEl = document.querySelector(`.estacion-item[data-id="${estacionSeleccionada.id}"]`);
                        if (estacionEl) {
                            estacionEl.classList.add('en-mantenimiento');
                        }
                    }

                    // Recargar todas las estaciones en mantenimiento para sincronizar
                    cargarEstacionesEnMantenimiento();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo iniciar el mantenimiento'));
                }
            })
            .catch(error => {
                btnIniciar.disabled = false;
                btnIniciar.innerHTML = btnAnterior;
                console.error('Error:', error);
                alert('Error al conectar con el servidor');
            });
    }

    function finalizarMantenimiento() {
        if (!estacionSeleccionada || !mantenimientoActivo) return;

        const fechaFinInput = document.getElementById('fechaFinMantenimiento').value;
        const procesoId = document.getElementById('procesoFinMantenimiento').value;

        if (!fechaFinInput) {
            alert('Por favor indique la hora de finalización del mantenimiento');
            return;
        }

        if (!procesoId) {
            alert('Por favor seleccione el proceso asignado a la estación');
            return;
        }

        // Convertir formato datetime-local (YYYY-MM-DDTHH:mm) a formato MySQL (YYYY-MM-DD HH:mm:ss)
        const fechaFin = fechaFinInput.replace('T', ' ') + ':00';

        const datos = {
            id: mantenimientoActivo.id,
            fecha_fin: fechaFin,
            proceso_id: parseInt(procesoId)
        };

        // Deshabilitar botón mientras se procesa
        const btnFinalizar = document.getElementById('btnFinalizarMantenimiento');
        const btnAnterior = btnFinalizar.innerHTML;
        btnFinalizar.disabled = true;
        btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';

        fetch(`${BASE_URL}/app/controllers/estacion_mantenimiento_finalizar.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        })
            .then(response => response.json())
            .then(data => {
                btnFinalizar.disabled = false;
                btnFinalizar.innerHTML = btnAnterior;

                if (data.success) {
                    alert('Mantenimiento finalizado exitosamente');
                    mantenimientoActivo = null;
                    actualizarSeccionMantenimiento(null, procesosEstacion);

                    // Actualizar estación visualmente
                    if (estacionSeleccionada) {
                        const estacionEl = document.querySelector(`.estacion-item[data-id="${estacionSeleccionada.id}"]`);
                        if (estacionEl) {
                            estacionEl.classList.remove('en-mantenimiento');
                        }
                    }

                    // Recargar procesos para ver actualizado el tiempo de mantenimiento
                    cargarProcesosEstacion(estacionSeleccionada.id);

                    // Recargar todas las estaciones en mantenimiento para sincronizar
                    cargarEstacionesEnMantenimiento();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo finalizar el mantenimiento'));
                }
            })
            .catch(error => {
                btnFinalizar.disabled = false;
                btnFinalizar.innerHTML = btnAnterior;
                console.error('Error:', error);
                alert('Error al conectar con el servidor');
            });
    }

    // Funciones auxiliares
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        const date = new Date(fecha);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function obtenerFechaHoraActual() {
        const ahora = new Date();
        const año = ahora.getFullYear();
        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
        const día = String(ahora.getDate()).padStart(2, '0');
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');

        return `${año}-${mes}-${día}T${horas}:${minutos}`;
    }

})();
