// Tracking Dashboard con gestión de Turnos
(function() {
    'use strict';

    // Variables globales
    let asignacionesData = [];
    let estacionesData = [];
    let draggedElement = null;
    let estacionasDragActual = null;
    let turnoTarget = null;
    let asignacionesModal = null;

    // Variables para drag and drop de dos pasos
    let primerDragData = null; // Almacena el primer drag (empleado o pedido)
    let turnoSeleccionado = null; // Almacena el turno donde se hizo el primer drop

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        asignacionesModal = new bootstrap.Modal(document.getElementById('modalAsignacion'));
        inicializarEventos();
        cargarDatos();
    });

    function inicializarEventos() {
        // Botón de configurar turnos
        const btnConfigurarTurnos = document.getElementById('btnConfigurarTurnos');
        if (btnConfigurarTurnos) {
            btnConfigurarTurnos.addEventListener('click', abrirModalTurnos);
        }

        // Guardar asignación
        const btnGuardarAsignacion = document.getElementById('btnGuardarAsignacion');
        if (btnGuardarAsignacion) {
            btnGuardarAsignacion.addEventListener('click', guardarAsignacion);
        }

        // Event listeners para las pestañas
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                cambiarPestana(tabName);
            });
        });
    }

    function cambiarPestana(tabName) {
        // Quitar clase active de todas las pestañas y contenidos
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Agregar clase active a la pestaña y contenido seleccionado
        document.querySelector(`.tab[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById(`tab-${tabName}`).classList.add('active');
    }

    function cargarDatos() {
        Promise.all([
            fetch(`${BASE_URL}/app/controllers/asignaciones_turno_listar.php`).then(r => r.json()).catch(e => ({ success: false, message: e.message })),
            fetch(`${BASE_URL}/app/controllers/tracking_estaciones_listar.php`).then(r => r.json()).catch(e => ({ success: false, message: e.message })),
            fetch(`${BASE_URL}/app/controllers/tracking_pedidos_listar.php`).then(r => r.json()).catch(e => ({ success: false, message: e.message })),
            fetch(`${BASE_URL}/app/controllers/admin_turnos_listar.php`).then(r => r.json()).catch(e => ({ success: false, message: e.message }))
        ])
        .then(([asignacionesResp, estacionesResp, pedidosResp, turnosResp]) => {
            console.log('Respuestas:', { asignacionesResp, estacionesResp, pedidosResp, turnosResp });

            if (asignacionesResp.success) {
                asignacionesData = asignacionesResp.data;
                console.log('Asignaciones cargadas:', asignacionesData.length);
            } else {
                console.warn('Error asignaciones:', asignacionesResp.message);
                asignacionesData = [];
            }

            if (estacionesResp.success) {
                estacionesData = estacionesResp.data;
                console.log('Estaciones cargadas:', estacionesData.length);
                actualizarEstacionesConTurnos();
            } else {
                console.warn('Error estaciones:', estacionesResp.message);
                mostrarAlerta('Error al cargar estaciones: ' + estacionesResp.message, 'warning');
            }

            if (pedidosResp.success) {
                window.pedidosListaData = pedidosResp.data;
                console.log('Pedidos cargados:', window.pedidosListaData.length);
                actualizarSelectPedidos();
            } else {
                console.warn('Error pedidos:', pedidosResp.message);
                window.pedidosListaData = [];
            }

            if (turnosResp.success) {
                turnosData = turnosResp.data.filter(turno => turno.activo == 1);
                console.log('Turnos cargados:', turnosData.length);
            } else {
                console.warn('Error turnos:', turnosResp.message);
                turnosData = [];
            }

            configurarDragAndDrop();
        })
        .catch(error => {
            console.error('Error al cargar datos:', error);
            mostrarAlerta('Error al cargar datos del sistema', 'danger');
        });
    }

    function actualizarSelectPedidos() {
        const select = document.getElementById('asignPedido');
        window.pedidosListaData.forEach(pedido => {
            const option = document.createElement('option');
            option.value = pedido.id;
            option.textContent = `${pedido.numero_pedido} - ${pedido.razon_social || 'N/A'}`;
            select.appendChild(option);
        });

        // También llenar la pestaña de pedidos
        actualizarPestanaPedidos();
    }

    function actualizarPestanaPedidos() {
        const contenedor = document.getElementById('tab-jobs');
        const sinPedidos = document.getElementById('sin-pedidos');

        if (!window.pedidosListaData || window.pedidosListaData.length === 0) {
            sinPedidos.style.display = 'block';
            return;
        }

        // Limpiar contenedor
        contenedor.innerHTML = '';

        // Crear lista de pedidos
        const listaPedidos = document.createElement('div');
        listaPedidos.className = 'p-0';

        window.pedidosListaData.forEach(pedido => {
            const pedidoItem = document.createElement('div');
            pedidoItem.className = 'pedido-item draggable';
            pedidoItem.dataset.id = pedido.id;
            pedidoItem.draggable = true;

            pedidoItem.innerHTML = `
                <div class="pedido-numero">${escapeHtml(pedido.numero_pedido)}</div>
                <div class="pedido-cliente">${escapeHtml(pedido.razon_social || 'Sin cliente')}</div>
            `;

            // Agregar event listeners de drag
            pedidoItem.addEventListener('dragstart', function(e) {
                draggedElement = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('tipo', 'pedido');
                e.dataTransfer.setData('id', this.dataset.id);
            });

            pedidoItem.addEventListener('dragend', function() {
                document.querySelectorAll('.turno-content').forEach(el => {
                    el.classList.remove('drop-hover');
                });
            });

            listaPedidos.appendChild(pedidoItem);
        });

        contenedor.appendChild(listaPedidos);
    }

    function actualizarEstacionesConTurnos() {
        // Limpiar contenedores
        document.querySelectorAll('[id^="estaciones-nave-"]').forEach(el => el.innerHTML = '');

        if (!estacionesData || estacionesData.length === 0) return;

        // Agrupar estaciones por nave
        const estacionesPorNave = {
            'Nave 1': [],
            'Nave 2': [],
            'Nave 3': []
        };

        estacionesData.forEach(estacion => {
            const nave = estacion.nave || 'Nave 1';
            if (!estacionesPorNave[nave]) {
                estacionesPorNave[nave] = [];
            }
            estacionesPorNave[nave].push(estacion);
        });

        // Renderizar estaciones
        Object.keys(estacionesPorNave).forEach(nombreNave => {
            const naveId = nombreNave.replace(/\s+/g, '-').toLowerCase();
            const contenedor = document.getElementById(`estaciones-${naveId}`);
            if (!contenedor) return;

            let colorNave = '#3498db';
            if (nombreNave === 'Nave 2') colorNave = '#2ecc71';
            if (nombreNave === 'Nave 3') colorNave = '#f39c12';

            estacionesPorNave[nombreNave].forEach(estacion => {
                const elemento = crearEstacionConTurnos(estacion, colorNave);
                contenedor.appendChild(elemento);
            });
        });
    }

    function crearEstacionConTurnos(estacion, colorNave) {
        const div = document.createElement('div');
        div.className = 'estacion-item';
        div.dataset.id = estacion.id;
        div.dataset.nombre = estacion.nombre;

        div.innerHTML = `
            <div class="estacion-header-turno" style="border-top: 4px solid ${colorNave}">
                ${escapeHtml(estacion.nombre)}
            </div>
            <div class="turnos-container">
                ${turnosData.map(turno => crearTurnoSection(estacion.id, turno)).join('')}
            </div>
        `;

        return div;
    }

    function crearTurnoSection(estacionId, turno) {
        const asignacionesTurno = asignacionesData.filter(a =>
            a.estacion_id == estacionId && a.turno_id == turno.id
        );

        // Convertir horas si vienen en formato largo
        let horaInicio = turno.hora_inicio;
        let horaFin = turno.hora_fin;

        if (horaInicio && horaInicio.length > 5) {
            horaInicio = horaInicio.substring(0, 5);
        }
        if (horaFin && horaFin.length > 5) {
            horaFin = horaFin.substring(0, 5);
        }

        return `
            <div class="turno-section">
                <div class="turno-header">
                    <span>${escapeHtml(turno.nombre)}</span>
                    <span class="turno-tiempo">${horaInicio}-${horaFin}</span>
                </div>
                <div class="turno-content droppable" data-estacion-id="${estacionId}" data-turno-id="${turno.id}">
                    ${asignacionesTurno.map(asig => crearAsignacionCard(asig)).join('')}
                </div>
            </div>
        `;
    }

    function crearAsignacionCard(asignacion) {
        const porcentaje = asignacion.cantidad_total > 0
            ? Math.min(100, (asignacion.cantidad_procesada / asignacion.cantidad_total) * 100)
            : 0;

        return `
            <div class="asignacion-turno-card draggable" data-id="${asignacion.id}" draggable="true">
                <div class="asignacion-turno-empleado">
                    ${escapeHtml(asignacion.empleado_nombre + ' ' + asignacion.empleado_apellido)}
                </div>
                <div class="asignacion-turno-pedido">
                    Pedido: ${escapeHtml(asignacion.numero_pedido)}
                </div>
                <div class="asignacion-turno-tipo" style="background-color: ${asignacion.tipo_trabajo_color || '#3498db'}">
                    ${escapeHtml(asignacion.tipo_trabajo_nombre || 'N/A')}
                </div>
                <div class="asignacion-turno-cantidad">
                    Procesado: ${asignacion.cantidad_procesada}/${asignacion.cantidad_total}
                    <div style="width: 100%; height: 3px; background: #e0e0e0; margin-top: 2px; border-radius: 2px; overflow: hidden;">
                        <div style="height: 100%; background: #2ecc71; width: ${porcentaje}%"></div>
                    </div>
                </div>
            </div>
        `;
    }

    function configurarDragAndDrop() {
        // Empleados arrastrables
        document.querySelectorAll('#listaEmpleados .empleado-item').forEach(empleado => {
            empleado.setAttribute('draggable', 'true');
            empleado.addEventListener('dragstart', function(e) {
                draggedElement = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('tipo', 'empleado');
                e.dataTransfer.setData('id', this.dataset.id);
            });

            empleado.addEventListener('dragend', function() {
                document.querySelectorAll('.turno-content').forEach(el => {
                    el.classList.remove('drop-hover');
                });
            });
        });

        // Pedidos arrastrables
        document.querySelectorAll('#tab-jobs .pedido-item').forEach(pedido => {
            pedido.setAttribute('draggable', 'true');
            pedido.addEventListener('dragstart', function(e) {
                draggedElement = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('tipo', 'pedido');
                e.dataTransfer.setData('id', this.dataset.id);
            });

            pedido.addEventListener('dragend', function() {
                document.querySelectorAll('.turno-content').forEach(el => {
                    el.classList.remove('drop-hover');
                });
            });
        });

        // Áreas drop
        document.querySelectorAll('.turno-content.droppable').forEach(area => {
            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drop-hover');
                turnoTarget = this;
            });

            area.addEventListener('dragleave', function() {
                this.classList.remove('drop-hover');
            });

            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drop-hover');

                if (!draggedElement) return;

                const estacionId = parseInt(this.dataset.estacionId);
                const turnoId = parseInt(this.dataset.turnoId);
                const tipo = e.dataTransfer.getData('tipo');
                const id = e.dataTransfer.getData('id');

                // Lógica de dos pasos para drag and drop
                procesarDragDeDosPasos(estacionId, turnoId, tipo, id);
            });
        });
    }

    function procesarDragDeDosPasos(estacionId, turnoId, tipo, id) {
        // Si no hay empleado seleccionado aún
        if (primerDragData === null) {
            // El primer drag DEBE ser un empleado
            if (tipo !== 'empleado') {
                mostrarAlerta('Primero debes arrastrar un EMPLEADO al turno', 'warning');
                return;
            }

            primerDragData = {
                estacionId: estacionId,
                turnoId: turnoId,
                tipo: tipo,
                id: id
            };
            turnoSeleccionado = { estacionId, turnoId };

            // Mostrar el elemento arrastrado en el turno
            mostrarElementoEnTurno(estacionId, turnoId, tipo, id);

            mostrarAlerta(`✅ Empleado asignado: ${getNombreEmpleado(id)}. Ahora arrastra los PEDIDOS al mismo turno`, 'info');
            return;
        }

        // Ya hay un empleado, ahora solo aceptamos pedidos
        if (tipo === 'empleado') {
            mostrarAlerta('Ya hay un empleado asignado en este turno. Arrastra un PEDIDO', 'warning');
            return;
        }

        // Verificar que estamos en el mismo turno
        if (primerDragData.estacionId !== estacionId || primerDragData.turnoId !== turnoId) {
            mostrarAlerta('Debes arrastrar el pedido al MISMO turno del empleado', 'warning');
            return;
        }

        // Tenemos empleado + pedido, crear la asignación
        const empleadoId = primerDragData.id;
        const pedidoId = id;

        crearAsignacionConDatos(estacionId, turnoId, empleadoId, pedidoId);
    }

    function getNombreEmpleado(empleadoId) {
        const empleado = empleadosData.find(e => e.id == empleadoId);
        return empleado ? `${empleado.nombre} ${empleado.apellido}` : 'Empleado';
    }

    function mostrarElementoEnTurno(estacionId, turnoId, tipo, id) {
        // Obtener el contenedor del turno
        const turnoContent = document.querySelector(`.turno-content[data-estacion-id="${estacionId}"][data-turno-id="${turnoId}"]`);
        if (!turnoContent) return;

        // Buscar el nombre del elemento (empleado o pedido)
        let nombre = '';
        if (tipo === 'empleado') {
            // Buscar en la lista de empleados
            const empleado = empleadosData.find(e => e.id == id);
            nombre = empleado ? `${empleado.nombre} ${empleado.apellido}` : 'Empleado';
        } else {
            // Buscar en la lista de pedidos
            const pedido = window.pedidosListaData.find(p => p.id == id);
            nombre = pedido ? pedido.numero_pedido : 'Pedido';
        }

        // Crear elemento visual provisional
        const elemento = document.createElement('div');
        elemento.className = 'asignacion-turno-card-temporal';
        elemento.id = `temporal-${tipo}-${id}`;
        elemento.style.cssText = `
            background-color: #fff3cd;
            border: 2px dashed #ffc107;
            opacity: 0.8;
        `;

        // Si es empleado, mostrar "Esperando pedidos", si es pedido, solo mostrar el nombre
        let contenido = '';
        if (tipo === 'empleado') {
            contenido = `
                <div style="font-weight: 600; color: #856404; font-size: 10px;">
                    👤 ${escapeHtml(nombre)}
                </div>
                <small style="color: #856404;">Esperando pedidos...</small>
            `;
        } else {
            contenido = `
                <div style="font-weight: 600; color: #856404; font-size: 10px;">
                    📋 ${escapeHtml(nombre)}
                </div>
            `;
        }

        elemento.innerHTML = contenido;
        turnoContent.appendChild(elemento);
    }

    function crearAsignacionConDatos(estacionId, turnoId, empleadoId, pedidoId) {
        // Validar que tenemos ambos datos
        if (!empleadoId || !pedidoId) {
            mostrarAlerta('Error: datos incompletos', 'danger');
            limpiarElementosTemporales();
            return;
        }

        const formData = new FormData();
        formData.append('estacion_id', estacionId);
        formData.append('turno_id', turnoId);
        formData.append('empleado_id', empleadoId);
        formData.append('pedido_id', pedidoId);
        formData.append('tipo_trabajo_id', 1); // Por defecto el tipo de trabajo 1
        formData.append('cantidad_total', 1); // Por defecto cantidad 1

        fetch(`${BASE_URL}/app/controllers/asignaciones_turno_crear.php`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Asignación creada correctamente', 'success');
                // Recargar datos para actualizar la interfaz con la asignación real
                cargarDatos(); // Recargar datos
            } else {
                mostrarAlerta(`Error: ${data.message}`, 'danger');
                // Si es un error, remover solo el elemento temporal del pedido
                document.querySelectorAll('[id^="temporal-pedido-"]').forEach(el => el.remove());
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al crear la asignación', 'danger');
            // Si hay error, remover solo el elemento temporal del pedido
            document.querySelectorAll('[id^="temporal-pedido-"]').forEach(el => el.remove());
        });
    }

    function limpiarElementosTemporales() {
        // Remover todos los elementos temporales
        document.querySelectorAll('[id^="temporal-"]').forEach(el => el.remove());
    }

    function resetearEmpleadoSeleccionado() {
        // Limpiar el empleado seleccionado y sus elementos temporales
        if (primerDragData) {
            document.getElementById(`temporal-${primerDragData.tipo}-${primerDragData.id}`)?.remove();
        }
        primerDragData = null;
        turnoSeleccionado = null;
    }

    function abrirModalAsignacion(estacionId, turnoId, tipo, id) {
        document.getElementById('asignEstacionId').value = estacionId;
        document.getElementById('asignTurnoId').value = turnoId;

        // Limpiar y preparar el formulario
        document.getElementById('formAsignacion').reset();

        if (tipo === 'empleado') {
            document.getElementById('asignEmpleado').value = id;
            document.getElementById('asignEmpleado').disabled = true;
        } else if (tipo === 'pedido') {
            document.getElementById('asignPedido').value = id;
            document.getElementById('asignPedido').disabled = true;
        }

        asignacionesModal.show();
    }

    function guardarAsignacion() {
        const estacionId = parseInt(document.getElementById('asignEstacionId').value);
        const turnoId = parseInt(document.getElementById('asignTurnoId').value);
        const empleadoId = document.getElementById('asignEmpleado').value;
        const pedidoId = document.getElementById('asignPedido').value;
        const tipoTrabajoId = document.getElementById('asignTipoTrabajo').value;
        const cantidad = document.getElementById('asignCantidad').value;

        if (!empleadoId || !pedidoId || !tipoTrabajoId || !cantidad) {
            alert('Por favor complete todos los campos');
            return;
        }

        const formData = new FormData();
        formData.append('estacion_id', estacionId);
        formData.append('turno_id', turnoId);
        formData.append('empleado_id', empleadoId);
        formData.append('pedido_id', pedidoId);
        formData.append('tipo_trabajo_id', tipoTrabajoId);
        formData.append('cantidad_total', cantidad);

        fetch(`${BASE_URL}/app/controllers/asignaciones_turno_crear.php`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Asignación creada correctamente', 'success');
                asignacionesModal.hide();

                // Re-habilitar campos
                document.getElementById('asignEmpleado').disabled = false;
                document.getElementById('asignPedido').disabled = false;

                cargarDatos();
            } else {
                mostrarAlerta(`Error: ${data.message}`, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al crear la asignación', 'danger');
        });
    }

    function abrirModalTurnos() {
        crearYMostrarModalTurnos();
    }

    function crearYMostrarModalTurnos() {
        // Cargar turnos antes de crear el modal
        fetch(`${BASE_URL}/app/controllers/admin_turnos_listar.php`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    turnosData = data.data;

                    // Generar HTML del modal
                    const modalHTML = generarHTMLModalTurnos();

                    // Remover modal existente si lo hay
                    const existingModal = document.getElementById('modalTurnos');
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
                    document.getElementById('btnNuevoTurnoModal').addEventListener('click', mostrarFormularioNuevoTurno);

                    // Agregar listeners para botones de eliminar
                    document.querySelectorAll('.btnEliminarTurno').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const turnoId = this.getAttribute('data-turno-id');
                            eliminarTurnoHandler(turnoId);
                        });
                    });

                    // Mostrar modal
                    const modal = new bootstrap.Modal(document.getElementById('modalTurnos'));
                    modal.show();
                } else {
                    mostrarAlerta('Error al cargar turnos: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error al cargar turnos:', error);
                mostrarAlerta('Error al cargar turnos', 'danger');
            });
    }

    function generarHTMLModalTurnos() {
        return `
            <div class="modal fade" id="modalTurnos" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-clock me-2"></i>Configurar Turnos
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <button class="btn btn-primary btn-sm mb-3" id="btnNuevoTurnoModal">
                                <i class="fas fa-plus me-1"></i> Agregar Turno
                            </button>
                            <div id="listaTurnosContenedor">
                                ${turnosData.filter(turno => turno.activo == 1).map(turno => `
                                    <div class="card mb-3">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <strong>${escapeHtml(turno.nombre)}</strong>
                                            <button class="btn btn-sm btn-danger btnEliminarTurno" data-turno-id="${turno.id}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label>Hora Inicio</label>
                                                    <input type="time" class="form-control form-control-sm" value="${turno.hora_inicio.substring(0, 5)}"
                                                           onchange="window.actualizarTurno(${turno.id}, 'hora_inicio', this.value)">
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Hora Fin</label>
                                                    <input type="time" class="form-control form-control-sm" value="${turno.hora_fin.substring(0, 5)}"
                                                           onchange="window.actualizarTurno(${turno.id}, 'hora_fin', this.value)">
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Orden</label>
                                                    <input type="number" class="form-control form-control-sm" value="${turno.orden}"
                                                           onchange="window.actualizarTurno(${turno.id}, 'orden', this.value)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
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

    function mostrarFormularioNuevoTurno() {
        const nombre = prompt('Nombre del turno:');
        if (!nombre) return;

        const horaInicio = prompt('Hora de inicio (HH:MM):');
        if (!horaInicio) return;

        const horaFin = prompt('Hora de fin (HH:MM):');
        if (!horaFin) return;

        const orden = prompt('Orden de visualización:', turnosData.length + 1);
        if (!orden) return;

        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('hora_inicio', horaInicio);
        formData.append('hora_fin', horaFin);
        formData.append('orden', orden);

        fetch(`${BASE_URL}/app/controllers/admin_turnos_crear.php`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Turno creado correctamente', 'success');
                window.abrirModalTurnos(); // Recargar modal
                cargarDatos(); // Recargar datos para actualizar visual
            } else {
                mostrarAlerta(`Error: ${data.message}`, 'danger');
            }
        })
        .catch(error => mostrarAlerta('Error al crear turno', 'danger'));
    }

    // Funciones globales (expuestas para onclick)
    window.abrirModalTurnos = abrirModalTurnos;

    window.actualizarTurno = function(turnoId, campo, valor) {
        const formData = new FormData();
        formData.append('id', turnoId);
        formData.append(campo, valor);

        fetch(`${BASE_URL}/app/controllers/admin_turnos_editar.php`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Turno actualizado', 'success');
                window.abrirModalTurnos(); // Recargar modal
                cargarDatos(); // Actualizar dashboard
            } else {
                mostrarAlerta(`Error: ${data.message}`, 'danger');
            }
        });
    };

    function eliminarTurnoHandler(turnoId) {
        if (!confirm('¿Está seguro de eliminar este turno?')) return;

        console.log('Eliminando turno:', turnoId);

        const formData = new FormData();
        formData.append('id', turnoId);

        fetch(`${BASE_URL}/app/controllers/admin_turnos_eliminar.php`, {
            method: 'POST',
            body: formData
        })
        .then(r => {
            console.log('Response status:', r.status);
            return r.json();
        })
        .then(data => {
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                mostrarAlerta('Turno eliminado', 'success');
                setTimeout(() => window.abrirModalTurnos(), 500); // Recargar modal
                cargarDatos(); // Actualizar dashboard
            } else {
                mostrarAlerta(`Error: ${data.message}`, 'danger');
            }
        })
        .catch(error => {
            console.error('Error al eliminar turno:', error);
            mostrarAlerta('Error al eliminar turno: ' + error.message, 'danger');
        });
    }

    window.eliminarTurno = eliminarTurnoHandler;

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function mostrarAlerta(mensaje, tipo) {
        // Crear contenedor de alertas si no existe
        let contenedor = document.getElementById('alertas-contenedor');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'alertas-contenedor';
            contenedor.style.cssText = `
                position: fixed;
                top: 70px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                pointer-events: none;
            `;
            document.body.appendChild(contenedor);
        }

        // Crear la alerta
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.style.cssText = `
            pointer-events: auto;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        `;
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Agregar a contenedor
        contenedor.appendChild(alerta);

        // Auto-dismiss después de 3 segundos
        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 150);
        }, 3000);
    }
})();
