// Tracking Dashboard - Gestión de empleados y asignación de trabajos
(function() {
    'use strict';

    // Variables globales
    let pedidosData = [];
    let asignacionesData = [];
    let tiposTrabajoData = [];
    let areasTrabajoData = [];
    let estacionesData = [];
    let procesosData = []; // Mapeo de procesos con nombres
    let empleadosHabilidades = {}; // Mapeo de empleado_id -> procesos
    let draggedElement = null;
    let targetArea = null;

    // Estado de filtros
    const filtrosActivos = {
        armado: true,
        corte: true,
        corteSierra: true,
        detallado: true,
        conformado: true,
        doblez: true
    };

    // Se cargarán dinámicamente desde los filtros

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        inicializarEventos();
        cargarDatos();
    });

    function inicializarEventos() {
        // Eventos para tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                cambiarTab(tabId);
            });
        });

        // Eventos dinámicos para filtros de procesos
        document.querySelectorAll('.filtro-tipo input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                aplicarFiltros();
            });
        });

        // Eventos para búsqueda
        document.getElementById('buscarEmpleados').addEventListener('input', function() {
            const busqueda = this.value.toLowerCase();
            filtrarEmpleados(busqueda);
        });

        document.getElementById('buscarPedidos').addEventListener('input', function() {
            const busqueda = this.value.toLowerCase();
            filtrarPedidos(busqueda);
        });

        // Evento para registro de horas
        document.getElementById('btnRegistrarHoras').addEventListener('click', registrarHoras);

        // Configurar eventos drag & drop para empleados
        configurarDragAndDrop();
    }

    function cambiarTab(tabId) {
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
        document.getElementById(`tab-${tabId}`).classList.add('active');
    }

    function cargarDatos() {
        // Cargar datos necesarios directamente
        Promise.all([
            fetch(`${BASE_URL}/app/controllers/tracking_pedidos_listar.php`).then(r => r.json()).catch(e => ({ success: false })),
            fetch(`${BASE_URL}/app/controllers/tracking_asignaciones_listar.php`).then(r => r.json()).catch(e => ({ success: false })),
            fetch(`${BASE_URL}/app/controllers/tracking_tipos_trabajo_listar.php`).then(r => r.json()).catch(e => ({ success: false })),
            fetch(`${BASE_URL}/app/controllers/tracking_areas_trabajo_listar.php`).then(r => r.json()).catch(e => ({ success: false })),
            fetch(`${BASE_URL}/app/controllers/tracking_estaciones_listar.php`).then(r => r.json()).catch(e => ({ success: false })),
            fetch(`${BASE_URL}/app/controllers/procesos_obtener_todos.php`).then(r => r.json()).catch(e => ({ success: false })),
            fetch(`${BASE_URL}/app/controllers/empleado_procesos_listar.php`).then(r => r.json()).catch(e => ({ success: false }))
        ])
        .then(([pedidosResp, asignacionesResp, tiposTrabajoResp, areasTrabajoResp, estacionesResp, procesosResp, habilidadesResp]) => {
            console.log('Pedidos:', pedidosResp);
            console.log('Áreas:', areasTrabajoResp);
            console.log('Tipos:', tiposTrabajoResp);
            console.log('Estaciones:', estacionesResp);
            console.log('Habilidades:', habilidadesResp);

            if (pedidosResp.success) {
                pedidosData = pedidosResp.data;
                actualizarListaPedidos();
            } else {
                console.warn('Error al cargar pedidos');
            }

            if (asignacionesResp.success) {
                asignacionesData = asignacionesResp.data;
                actualizarAsignaciones();
            }

            if (tiposTrabajoResp.success) {
                tiposTrabajoData = tiposTrabajoResp.data;
                actualizarTiposTrabajo();
            } else {
                console.warn('Error al cargar tipos de trabajo');
            }

            if (areasTrabajoResp.success) {
                areasTrabajoData = areasTrabajoResp.data;
                actualizarAreas();
            } else {
                console.warn('Error al cargar áreas de trabajo');
            }

            if (estacionesResp.success) {
                estacionesData = estacionesResp.data;
                actualizarEstaciones();
            } else {
                console.warn('Error al cargar estaciones');
            }

            // Cargar procesos disponibles
            if (procesosResp.success) {
                procesosData = procesosResp.data;
                console.log('Procesos cargados:', procesosData);
            } else {
                console.warn('Error al cargar procesos');
            }

            // Cargar habilidades de empleados
            if (habilidadesResp.success) {
                habilidadesResp.data.forEach(emp => {
                    empleadosHabilidades[emp.id] = emp.proceso_ids || [];
                });
                console.log('Habilidades cargadas:', empleadosHabilidades);
                // Actualizar UI de empleados con sus habilidades
                actualizarEmpleadosConHabilidades();
            }
        })
        .catch(error => {
            console.error('Error al cargar datos:', error);
            mostrarAlerta('Error al cargar datos del sistema', 'danger');
        });
    }

    function actualizarEmpleadosConHabilidades() {
        // Agregar dropdown de habilidades a cada empleado
        document.querySelectorAll('#listaEmpleados .empleado-item').forEach(item => {
            const empleadoId = parseInt(item.dataset.id);
            const habilidades = empleadosHabilidades[empleadoId] || [];

            // Buscar o crear contenedor de habilidades
            let habilidadesDiv = item.querySelector('.empleado-habilidades');
            if (!habilidadesDiv) {
                habilidadesDiv = document.createElement('div');
                habilidadesDiv.className = 'empleado-habilidades';
                item.appendChild(habilidadesDiv);
            }

            // Crear dropdown con checkboxes
            const dropdownId = `dropdown-habilidades-${empleadoId}`;
            const dropdownHTML = `
                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            type="button"
                            id="${dropdownId}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            style="font-size: 12px; padding: 4px 8px;">
                        <i class="fas fa-user-check"></i> Aptitudes
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="${dropdownId}" style="max-height: 300px; overflow-y: auto;">
                        ${procesosData.map(proceso => {
                            const checked = habilidades.includes(parseInt(proceso.id)) ? 'checked' : '';
                            return `
                                <li style="padding: 8px 12px;">
                                    <div class="form-check">
                                        <input class="form-check-input proceso-checkbox"
                                               type="checkbox"
                                               id="check-${empleadoId}-${proceso.id}"
                                               data-empleado-id="${empleadoId}"
                                               data-proceso-id="${proceso.id}"
                                               ${checked}>
                                        <label class="form-check-label" for="check-${empleadoId}-${proceso.id}">
                                            ${proceso.nombre}
                                        </label>
                                    </div>
                                </li>
                            `;
                        }).join('')}
                    </ul>
                </div>
            `;

            habilidadesDiv.innerHTML = dropdownHTML;

            // Agregar event listeners a los checkboxes
            habilidadesDiv.querySelectorAll('.proceso-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', manejarCambioHabilidad);
            });
        });
    }

    function manejarCambioHabilidad(event) {
        const checkbox = event.target;
        const empleadoId = parseInt(checkbox.dataset.empleadoId);
        const procesoId = parseInt(checkbox.dataset.procesoId);
        const accion = checkbox.checked ? 'asignar' : 'desasignar';

        // Hacer request al servidor
        fetch(`${BASE_URL}/app/controllers/empleado_procesos_actualizar.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                empleado_id: empleadoId,
                proceso_id: procesoId,
                accion: accion,
                nivel: 'intermedio'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar variable local
                if (accion === 'asignar') {
                    if (!empleadosHabilidades[empleadoId].includes(procesoId)) {
                        empleadosHabilidades[empleadoId].push(procesoId);
                    }
                } else {
                    empleadosHabilidades[empleadoId] = empleadosHabilidades[empleadoId].filter(id => id !== procesoId);
                }
                console.log('Habilidad actualizada:', data.message);
            } else {
                // Revertir el checkbox si hay error
                checkbox.checked = !checkbox.checked;
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            checkbox.checked = !checkbox.checked;
        });
    }

    function obtenerProcesoPorId(procesoId) {
        // Buscar el proceso en la lista cargada
        return procesosData.find(p => parseInt(p.id) === parseInt(procesoId));
    }

    function filtrarEmpleados(busqueda) {
        const items = document.querySelectorAll('#listaEmpleados .empleado-item');
        items.forEach(item => {
            const nombre = item.querySelector('.empleado-nombre').textContent.toLowerCase();
            const puesto = item.querySelector('.empleado-puesto').textContent.toLowerCase();

            if (nombre.includes(busqueda) || puesto.includes(busqueda)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function filtrarPedidos(busqueda) {
        const items = document.querySelectorAll('#tab-jobs .pedido-item');
        items.forEach(item => {
            const numero = item.querySelector('.pedido-numero').textContent.toLowerCase();
            const cliente = item.querySelector('.pedido-cliente').textContent.toLowerCase();

            if (numero.includes(busqueda) || cliente.includes(busqueda)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function aplicarFiltros() {
        // Obtener procesos seleccionados desde los checkboxes
        const procesosSeleccionados = [];
        document.querySelectorAll('.filtro-tipo input[type="checkbox"]:checked').forEach(checkbox => {
            procesosSeleccionados.push(parseInt(checkbox.dataset.procesoId));
        });

        console.log('Procesos seleccionados:', procesosSeleccionados);

        // Filtrar empleados por procesos
        const empleadosItems = document.querySelectorAll('#listaEmpleados .empleado-item');
        empleadosItems.forEach(item => {
            const empleadoId = parseInt(item.dataset.id);
            const habilidadesEmpleado = empleadosHabilidades[empleadoId] || [];

            // Mostrar empleado si tiene al menos uno de los procesos seleccionados
            let mostrar = true;
            if (procesosSeleccionados.length > 0) {
                mostrar = procesosSeleccionados.some(procesoId => habilidadesEmpleado.includes(procesoId));
            }

            item.style.display = mostrar ? '' : 'none';
        });

        // Si hay procesos seleccionados, filtrar estaciones
        if (procesosSeleccionados.length > 0) {
            const procesosQuery = procesosSeleccionados.join(',');
            fetch(`${BASE_URL}/app/controllers/tracking_estaciones_filtrar.php?procesos=${procesosQuery}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Respuesta del filtro de estaciones:', data);
                    if (data.success) {
                        estacionesData = data.data;
                        actualizarEstaciones();
                    } else {
                        console.error('Error en respuesta:', data.message);
                    }
                })
                .catch(error => console.error('Error filtrando estaciones:', error));
        } else {
            // Si ninguno está seleccionado, limpiar estaciones y mostrar todos los empleados
            console.log('Ningún proceso seleccionado - limpiando estaciones y mostrando todos los empleados');
            estacionesData = [];
            actualizarEstaciones();
            empleadosItems.forEach(item => item.style.display = '');
        }
    }

    function actualizarListaPedidos() {
        const contenedor = document.getElementById('tab-jobs');
        contenedor.innerHTML = '';

        if (pedidosData.length === 0) {
            contenedor.innerHTML = '<div class="p-3 text-center text-muted">No hay pedidos disponibles</div>';
            return;
        }

        pedidosData.forEach(pedido => {
            const elemento = document.createElement('div');
            elemento.className = 'pedido-item draggable';
            elemento.dataset.id = pedido.id;
            elemento.dataset.numeroPedido = pedido.numero_pedido;

            elemento.innerHTML = `
                <div class="pedido-numero">${escapeHtml(pedido.numero_pedido)}</div>
                <div class="pedido-cliente">${escapeHtml(pedido.razon_social || 'N/A')}</div>
            `;

            // Agregar al selector de pedidos
            const option = document.createElement('option');
            option.value = pedido.id;
            option.textContent = `${pedido.numero_pedido} - ${pedido.razon_social || 'N/A'}`;
            document.getElementById('pedidoSeleccionado').appendChild(option);

            contenedor.appendChild(elemento);
        });
    }

    function actualizarAsignaciones() {
        const areas = document.querySelectorAll('.area-content');

        // Limpiar áreas
        areas.forEach(area => {
            area.innerHTML = '';
        });

        if (asignacionesData.length === 0) {
            return;
        }

        // Agrupar asignaciones por área
        const asignacionesPorArea = {};
        asignacionesData.forEach(asignacion => {
            const areaId = asignacion.area_id || 1; // Default a área 1 si no tiene área asignada
            if (!asignacionesPorArea[areaId]) {
                asignacionesPorArea[areaId] = [];
            }
            asignacionesPorArea[areaId].push(asignacion);
        });

        // Renderizar asignaciones en cada área
        Object.keys(asignacionesPorArea).forEach(areaId => {
            const area = document.querySelector(`.area-content[data-area="${areaId}"]`);
            if (!area) return;

            asignacionesPorArea[areaId].forEach(asignacion => {
                const elemento = crearElementoAsignacion(asignacion);
                area.appendChild(elemento);
            });
        });
    }

    function crearElementoAsignacion(asignacion) {
        const elemento = document.createElement('div');
        elemento.className = 'asignacion-card';
        elemento.dataset.id = asignacion.id;
        elemento.dataset.empleadoId = asignacion.empleado_id;
        elemento.dataset.pedidoId = asignacion.pedido_id;
        elemento.dataset.tipoTrabajo = asignacion.tipo_trabajo_id;

        // Calcular progreso
        let porcentaje = 0;
        if (asignacion.cantidad_total > 0) {
            porcentaje = Math.min(100, (asignacion.cantidad_procesada / asignacion.cantidad_total) * 100);
        }

        // Obtener empleado y pedido
        const empleado = obtenerEmpleadoPorId(asignacion.empleado_id);
        const pedido = obtenerPedidoPorId(asignacion.pedido_id);
        const tipoTrabajo = obtenerTipoTrabajoPorId(asignacion.tipo_trabajo_id);

        const nombreEmpleado = empleado ? `${empleado.nombre} ${empleado.apellido}` : 'Empleado desconocido';
        const numeroPedido = pedido ? pedido.numero_pedido : 'Pedido desconocido';
        const nombreTipo = tipoTrabajo ? tipoTrabajo.nombre : 'Tipo desconocido';

        elemento.innerHTML = `
            <div class="asignacion-header">
                <span class="asignacion-nombre">${escapeHtml(nombreEmpleado)}</span>
                <span class="asignacion-tipo" style="background-color: ${tipoTrabajo ? tipoTrabajo.color : '#777'}">
                    ${escapeHtml(nombreTipo)}
                </span>
            </div>
            <div class="asignacion-detalles">
                <div><strong>Pedido:</strong> ${escapeHtml(numeroPedido)}</div>
                <div><strong>Procesado:</strong> ${asignacion.cantidad_procesada} / ${asignacion.cantidad_total}</div>
            </div>
            <div class="asignacion-progress">
                <div class="asignacion-progress-bar" style="width: ${porcentaje}%"></div>
            </div>
        `;

        return elemento;
    }

    function actualizarTiposTrabajo() {
        // Actualizar filtros con conteo de asignaciones
        if (!tiposTrabajoData.length) return;

        const conteo = {};
        asignacionesData.forEach(asignacion => {
            if (!conteo[asignacion.tipo_trabajo_id]) {
                conteo[asignacion.tipo_trabajo_id] = 0;
            }
            conteo[asignacion.tipo_trabajo_id]++;
        });

        tiposTrabajoData.forEach(tipo => {
            const filtroElement = document.getElementById(`filtro${tipo.nombre.replace(/\s+/g, '')}`);
            if (filtroElement) {
                const cantidad = conteo[tipo.id] || 0;
                const label = filtroElement.parentElement;
                label.textContent = `${tipo.nombre} - ${cantidad}`;

                filtroElement.insertAdjacentElement('beforebegin', filtroElement);
            }
        });
    }

    function actualizarAreas() {
        console.log('Actualizando áreas:', areasTrabajoData);

        // Si no hay áreas en la base de datos, usar las del HTML
        if (!areasTrabajoData || areasTrabajoData.length === 0) {
            console.log('No hay áreas en la BD, usando las del HTML');
            return;
        }

        // Actualizar nombres y colores de áreas
        areasTrabajoData.forEach(area => {
            const areaElement = document.querySelector(`.tracking-area[data-id="${area.id}"]`);
            console.log(`Buscando área ${area.id}:`, areaElement);

            if (areaElement) {
                const header = areaElement.querySelector('.area-header');
                if (header) {
                    header.innerHTML = `<i class="fas fa-industry me-2"></i> ${escapeHtml(area.nombre)}`;
                }
                areaElement.style.borderTopColor = area.color;
            }
        });
    }

    function actualizarEstaciones() {
        console.log('Actualizando estaciones:', estacionesData);

        // Limpiar todas las áreas primero
        document.getElementById('estaciones-nave-1').innerHTML = '';
        document.getElementById('estaciones-nave-2').innerHTML = '';
        document.getElementById('estaciones-nave-3').innerHTML = '';

        if (!estacionesData || estacionesData.length === 0) {
            console.log('No hay estaciones para mostrar');
            return;
        }

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

        console.log('Estaciones por nave:', estacionesPorNave);

        // Renderizar estaciones en cada nave
        Object.keys(estacionesPorNave).forEach(nombreNave => {
            const naveId = nombreNave.replace(/\s+/g, '-').toLowerCase();
            const contenedor = document.getElementById(`estaciones-${naveId}`);

            if (!contenedor) return;

            // Definir color basado en nave
            let colorNave = '#3498db';
            if (nombreNave === 'Nave 2') colorNave = '#2ecc71';
            if (nombreNave === 'Nave 3') colorNave = '#f39c12';

            estacionesPorNave[nombreNave].forEach(estacion => {
                const elemento = document.createElement('div');
                elemento.className = 'estacion-item';
                elemento.dataset.id = estacion.id;
                elemento.dataset.nombre = estacion.nombre;

                // Usar el color de la nave para la línea superior
                elemento.style.setProperty('--color-estacion', colorNave);

                elemento.innerHTML = `
                    <div class="estacion-nombre">${escapeHtml(estacion.nombre)}</div>
                    <div class="estacion-tipo">${escapeHtml(estacion.tipo || 'N/A')}</div>
                `;

                contenedor.appendChild(elemento);
            });
        });
    }

    function registrarHoras() {
        const empleadoId = document.getElementById('empleadoSeleccionado').value;
        const pedidoId = document.getElementById('pedidoSeleccionado').value;
        const tipoTrabajoId = document.getElementById('tipoTrabajoSeleccionado').value;
        const horaInicio = document.getElementById('horaInicio').value;
        const horaFin = document.getElementById('horaFin').value;
        const cantidadProcesada = document.getElementById('cantidadProcesada').value;

        if (!empleadoId || !pedidoId || !tipoTrabajoId || !horaInicio || !horaFin) {
            alert('Por favor complete todos los campos obligatorios');
            return;
        }

        const formData = new FormData();
        formData.append('empleado_id', empleadoId);
        formData.append('pedido_id', pedidoId);
        formData.append('tipo_trabajo_id', tipoTrabajoId);
        formData.append('hora_inicio', horaInicio);
        formData.append('hora_fin', horaFin);
        formData.append('cantidad_procesada', cantidadProcesada || 0);

        fetch(`${BASE_URL}/app/controllers/tracking_registrar_horas.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Horas registradas correctamente');
                document.getElementById('formRegistroHoras').reset();
                cargarDatos(); // Recargar datos
            } else {
                alert(`Error: ${data.message || 'No se pudo registrar las horas'}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al conectar con el servidor');
        });
    }

    function configurarDragAndDrop() {
        // Configurar elementos arrastrables (empleados)
        document.querySelectorAll('.draggable').forEach(empleado => {
            empleado.setAttribute('draggable', 'true');

            empleado.addEventListener('dragstart', function(e) {
                draggedElement = this;
                setTimeout(() => this.classList.add('dragging'), 0);

                // Guardar información del elemento
                e.dataTransfer.setData('text/plain', this.dataset.id);
                e.dataTransfer.effectAllowed = 'move';
            });

            empleado.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                draggedElement = null;

                // Remover clases de estilo
                document.querySelectorAll('.droppable').forEach(area => {
                    area.classList.remove('drop-hover');
                });
            });
        });

        // Configurar áreas donde se pueden soltar elementos
        document.querySelectorAll('.droppable').forEach(area => {
            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drop-hover');
                targetArea = this;
            });

            area.addEventListener('dragleave', function() {
                this.classList.remove('drop-hover');
                if (this === targetArea) {
                    targetArea = null;
                }
            });

            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drop-hover');

                if (!draggedElement) return;

                const areaId = this.dataset.area;

                // Si es un empleado, mostrar diálogo para crear asignación
                if (draggedElement.closest('#listaEmpleados')) {
                    const empleadoId = draggedElement.dataset.id;
                    mostrarDialogoAsignacion(empleadoId, areaId);
                }

                // Si es un pedido, mostrar diálogo para seleccionar empleado
                if (draggedElement.closest('#tab-jobs')) {
                    const pedidoId = draggedElement.dataset.id;
                    mostrarDialogoSeleccionEmpleado(pedidoId, areaId);
                }
            });
        });
    }

    function mostrarDialogoAsignacion(empleadoId, areaId) {
        // En una implementación real, esto mostraría un modal para seleccionar el pedido y tipo de trabajo
        const pedidoId = prompt('Ingrese ID del pedido:');
        if (!pedidoId) return;

        const tipoTrabajoId = prompt('Ingrese ID del tipo de trabajo (1-6):');
        if (!tipoTrabajoId) return;

        const cantidadTotal = prompt('Ingrese cantidad total a procesar:');
        if (!cantidadTotal) return;

        crearAsignacion(empleadoId, pedidoId, tipoTrabajoId, cantidadTotal, areaId);
    }

    function mostrarDialogoSeleccionEmpleado(pedidoId, areaId) {
        // En una implementación real, esto mostraría un modal para seleccionar el empleado y tipo de trabajo
        const empleadoId = prompt('Ingrese ID del empleado:');
        if (!empleadoId) return;

        const tipoTrabajoId = prompt('Ingrese ID del tipo de trabajo (1-6):');
        if (!tipoTrabajoId) return;

        const cantidadTotal = prompt('Ingrese cantidad total a procesar:');
        if (!cantidadTotal) return;

        crearAsignacion(empleadoId, pedidoId, tipoTrabajoId, cantidadTotal, areaId);
    }

    function crearAsignacion(empleadoId, pedidoId, tipoTrabajoId, cantidadTotal, areaId) {
        const formData = new FormData();
        formData.append('empleado_id', empleadoId);
        formData.append('pedido_id', pedidoId);
        formData.append('tipo_trabajo_id', tipoTrabajoId);
        formData.append('cantidad_total', cantidadTotal);
        formData.append('area_id', areaId);

        fetch(`${BASE_URL}/app/controllers/tracking_crear_asignacion.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Asignación creada correctamente');
                cargarDatos(); // Recargar datos
            } else {
                alert(`Error: ${data.message || 'No se pudo crear la asignación'}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al conectar con el servidor');
        });
    }

    // Funciones auxiliares
    function obtenerEmpleadoPorId(id) {
        const empleados = Array.from(document.querySelectorAll('#listaEmpleados .empleado-item'));
        const empleado = empleados.find(emp => emp.dataset.id == id);

        if (empleado) {
            return {
                id,
                nombre: empleado.querySelector('.empleado-nombre').textContent.split(' ')[0],
                apellido: empleado.querySelector('.empleado-nombre').textContent.split(' ').slice(1).join(' '),
                puesto: empleado.querySelector('.empleado-puesto').textContent
            };
        }

        return null;
    }

    function obtenerPedidoPorId(id) {
        return pedidosData.find(pedido => pedido.id == id) || null;
    }

    function obtenerTipoTrabajoPorId(id) {
        return tiposTrabajoData.find(tipo => tipo.id == id) || null;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function mostrarAlerta(mensaje, tipo) {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.querySelector('.tracking-container').insertAdjacentElement('beforebegin', alerta);

        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 150);
        }, 3000);
    }
})();