<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/procesos_model.php';
require_once __DIR__ . '/app/models/estaciones_model.php';

// Verificar acceso
if (!isset($_SESSION['user_id']) || !hasModuleAccess('estaciones')) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$pageTitle = 'Vinculación de Procesos a Estaciones';

// Obtener datos para dropdown
$procesosModel = new ProcesosModel($pdo);
$procesos = $procesosModel->obtenerTodosProcesosActivos();

include __DIR__ . '/app/views/header.php';
?>

<style>
    .estacion-procesos-container {
        padding: 20px;
    }

    .filtros-procesos {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }

    .filtros-procesos h5 {
        margin-bottom: 15px;
        color: #2c3e50;
    }

    .filtros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
    }

    .filtro-checkbox {
        display: flex;
        align-items: center;
        padding: 8px;
        background-color: white;
        border-radius: 6px;
        border: 1px solid #ddd;
        transition: all 0.2s;
    }

    .filtro-checkbox:hover {
        border-color: #3498db;
        background-color: #e8f4f8;
    }

    .filtro-checkbox input[type="checkbox"] {
        margin-right: 8px;
    }

    .filtro-checkbox input[type="checkbox"]:checked + label {
        font-weight: 600;
        color: #3498db;
    }

    .filtro-checkbox label {
        margin: 0;
        cursor: pointer;
        flex: 1;
    }

    .naves-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .nave-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .nave-header {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        padding: 15px;
        font-weight: bold;
        font-size: 16px;
    }

    .nave-header.nave2 {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    }

    .nave-header.nave3 {
        background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);
    }

    .estaciones-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
        padding: 20px;
    }

    .estacion-card {
        background-color: #f8f9fa;
        border: 2px solid #bdc3c7;
        border-radius: 8px;
        padding: 15px;
        transition: all 0.3s;
    }

    .estacion-card:hover {
        border-color: #3498db;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
    }

    .estacion-nombre {
        font-weight: 600;
        font-size: 14px;
        color: #2c3e50;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .estacion-tipo {
        font-size: 12px;
        color: #7f8c8d;
        padding: 4px 8px;
        background-color: #ecf0f1;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 10px;
    }

    .procesos-asignados {
        margin-bottom: 10px;
    }

    .procesos-asignados-label {
        font-size: 11px;
        color: #7f8c8d;
        font-weight: 600;
        margin-bottom: 5px;
        display: block;
    }

    .proceso-badge {
        display: inline-block;
        background-color: #3498db;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        margin: 2px;
        margin-bottom: 5px;
    }

    .sin-procesos {
        color: #e74c3c;
        font-size: 11px;
        font-style: italic;
    }

    .procesos-disponibles {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
    }

    .procesos-disponibles-label {
        font-size: 11px;
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    .proceso-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .proceso-checkbox {
        display: flex;
        align-items: center;
        padding: 4px 8px;
        background-color: white;
        border: 1px solid #bdc3c7;
        border-radius: 4px;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .proceso-checkbox:hover {
        border-color: #3498db;
    }

    .proceso-checkbox input[type="checkbox"] {
        margin-right: 5px;
        cursor: pointer;
    }

    .proceso-checkbox input[type="checkbox"]:checked + label {
        font-weight: 600;
        color: #3498db;
    }

    .proceso-checkbox label {
        margin: 0;
        cursor: pointer;
    }

    .botones-acciones {
        margin-top: 10px;
        display: flex;
        gap: 5px;
    }

    .btn-asignar, .btn-limpiar {
        font-size: 11px;
        padding: 4px 8px;
        flex: 1;
    }

    .cargando {
        text-align: center;
        padding: 40px;
        color: #7f8c8d;
    }

    .cargando-spinner {
        display: inline-block;
        border: 3px solid #ecf0f1;
        border-radius: 50%;
        border-top-color: #3498db;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin-bottom: 10px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .contador-procesos {
        background-color: #3498db;
        color: white;
        padding: 2px 6px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
    }

    .estado-activa {
        color: #27ae60;
        font-size: 11px;
    }

    .estado-inactiva {
        color: #e74c3c;
        font-size: 11px;
    }
</style>

<div class="estacion-procesos-container">
    <div class="mb-4">
        <h2><i class="fas fa-link me-2"></i>Vinculación de Procesos a Estaciones</h2>
        <p class="text-muted">Selecciona los procesos que cada estación puede realizar</p>
    </div>

    <!-- Filtros por Proceso -->
    <div class="filtros-procesos">
        <h5><i class="fas fa-filter me-2"></i>Filtrar por Proceso</h5>
        <div class="filtros-grid" id="filtrosProcesos">
            <!-- Se cargarán dinámicamente -->
            <div class="cargando">
                <div class="cargando-spinner"></div>
                <p>Cargando procesos...</p>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <div id="mensajes"></div>

    <!-- Grid de Naves -->
    <div class="naves-container" id="navesContainer">
        <div class="cargando">
            <div class="cargando-spinner"></div>
            <p>Cargando estaciones...</p>
        </div>
    </div>
</div>

<script>
    // BASE_URL ya viene definido del header.php
    let procesosActivos = new Set();
    let estacionesData = {};
    let procesosData = {};

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        cargarProcesos();
        cargarEstaciones();
    });

    function cargarProcesos() {
        fetch(`${BASE_URL}/app/controllers/procesos_obtener_todos.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    procesosData = {};
                    data.data.forEach(proceso => {
                        procesosData[proceso.id] = proceso;
                    });

                    renderizarFiltrosProcesos();
                } else {
                    mostrarError('Error al cargar procesos');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al conectar con el servidor');
            });
    }

    function cargarEstaciones() {
        console.log('Intentando cargar estaciones desde:', `${BASE_URL}/app/controllers/admin_estacion_procesos_listar.php`);

        fetch(`${BASE_URL}/app/controllers/admin_estacion_procesos_listar.php`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                if (data.success) {
                    estacionesData = data.data;
                    console.log('Estaciones cargadas:', estacionesData);
                    renderizarEstaciones();
                } else {
                    console.error('Error en respuesta:', data.message);
                    mostrarError('Error al cargar estaciones: ' + (data.message || 'desconocido'));
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                mostrarError('Error al conectar con el servidor: ' + error.message);
            });
    }

    function renderizarFiltrosProcesos() {
        const contenedor = document.getElementById('filtrosProcesos');
        contenedor.innerHTML = '';

        for (const [id, proceso] of Object.entries(procesosData)) {
            const filtro = document.createElement('div');
            filtro.className = 'filtro-checkbox';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = `filtro-proceso-${id}`;
            checkbox.dataset.procesId = id;
            checkbox.checked = true; // Por defecto mostrar todos
            procesosActivos.add(parseInt(id));

            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    procesosActivos.add(parseInt(id));
                } else {
                    procesosActivos.delete(parseInt(id));
                }
                renderizarEstaciones();
            });

            const label = document.createElement('label');
            label.htmlFor = `filtro-proceso-${id}`;
            label.textContent = proceso.nombre;

            filtro.appendChild(checkbox);
            filtro.appendChild(label);
            contenedor.appendChild(filtro);
        }
    }

    function renderizarEstaciones() {
        const contenedor = document.getElementById('navesContainer');

        // Agrupar estaciones por nave
        const navesOrdenadas = {};
        for (const [nave, estaciones] of Object.entries(estacionesData)) {
            navesOrdenadas[nave] = estaciones;
        }

        contenedor.innerHTML = '';

        for (const [nave, estaciones] of Object.entries(navesOrdenadas)) {
            const naveCard = document.createElement('div');
            naveCard.className = 'nave-card';

            const naveIndex = nave.includes('2') ? 2 : nave.includes('3') ? 3 : 1;
            const naveHeaderClass = naveIndex === 1 ? '' : `nave${naveIndex}`;

            const naveHeader = document.createElement('div');
            naveHeader.className = `nave-header ${naveHeaderClass}`;
            naveHeader.innerHTML = `<i class="fas fa-industry me-2"></i> ${nave}`;

            const estacionesGrid = document.createElement('div');
            estacionesGrid.className = 'estaciones-grid';

            estaciones.forEach(estacion => {
                const estacionCard = document.createElement('div');
                estacionCard.className = 'estacion-card';

                // Obtener procesos asignados actuales
                const procesosAsignados = estacion.procesos_asignados || [];

                // Crear HTML de procesos asignados
                let procesosHtml = '';
                if (procesosAsignados.length > 0) {
                    procesosHtml = procesosAsignados.map(id => {
                        const proceso = procesosData[id];
                        return `<span class="proceso-badge">${proceso ? proceso.nombre : 'Desconocido'}</span>`;
                    }).join('');
                } else {
                    procesosHtml = '<span class="sin-procesos">Sin procesos asignados</span>';
                }

                // Crear opciones de procesos disponibles
                const procesosDisponiblesHtml = Object.entries(procesosData)
                    .map(([id, proceso]) => {
                        const estaAsignado = procesosAsignados.includes(parseInt(id));
                        return `
                            <div class="proceso-checkbox">
                                <input type="checkbox"
                                       id="proc-${estacion.id}-${id}"
                                       class="proceso-asignable"
                                       data-estacion="${estacion.id}"
                                       data-proceso="${id}"
                                       ${estaAsignado ? 'checked' : ''}>
                                <label for="proc-${estacion.id}-${id}">${proceso.nombre}</label>
                            </div>
                        `;
                    }).join('');

                estacionCard.innerHTML = `
                    <div class="estacion-nombre">
                        <i class="fas fa-cog"></i>
                        ${estacion.nombre}
                        <span class="contador-procesos">${estacion.total_procesos}</span>
                    </div>
                    <span class="estacion-tipo">${estacion.tipo}</span>
                    <span class="estado-${estacion.estatus === 'activa' ? 'activa' : 'inactiva'}">
                        <i class="fas fa-${estacion.estatus === 'activa' ? 'check-circle' : 'times-circle'}"></i>
                        ${estacion.estatus}
                    </span>

                    <div class="procesos-asignados">
                        <span class="procesos-asignados-label">Procesos Activos:</span>
                        <div>${procesosHtml}</div>
                    </div>

                    <div class="procesos-disponibles">
                        <label class="procesos-disponibles-label">Seleccionar Procesos:</label>
                        <div class="proceso-selector">
                            ${procesosDisponiblesHtml}
                        </div>
                    </div>
                `;

                estacionesGrid.appendChild(estacionCard);
            });

            naveCard.appendChild(naveHeader);
            naveCard.appendChild(estacionesGrid);
            contenedor.appendChild(naveCard);
        }

        // Agregar listeners a los checkboxes
        document.querySelectorAll('.proceso-asignable').forEach(checkbox => {
            checkbox.addEventListener('change', manejarCambioProcesoEstacion);
        });
    }

    function manejarCambioProcesoEstacion(event) {
        const checkbox = event.target;
        const estacionId = parseInt(checkbox.dataset.estacion);
        const procesoId = parseInt(checkbox.dataset.proceso);
        const accion = checkbox.checked ? 'asignar' : 'desasignar';

        const formData = new FormData();
        formData.append('estacion_id', estacionId);
        formData.append('proceso_id', procesoId);
        formData.append('accion', accion);

        fetch(`${BASE_URL}/app/controllers/admin_estacion_procesos_actualizar.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar datos locales
                const estacion = buscarEstacionEnDatos(estacionId);
                if (estacion) {
                    if (accion === 'asignar' && !estacion.procesos_asignados.includes(procesoId)) {
                        estacion.procesos_asignados.push(procesoId);
                        estacion.total_procesos++;
                    } else if (accion === 'desasignar') {
                        const index = estacion.procesos_asignados.indexOf(procesoId);
                        if (index > -1) {
                            estacion.procesos_asignados.splice(index, 1);
                            estacion.total_procesos--;
                        }
                    }
                }

                mostrarExito(`Proceso ${accion === 'asignar' ? 'asignado' : 'desasignado'} correctamente`);
                renderizarEstaciones();
            } else {
                mostrarError(data.message || 'Error al actualizar');
                checkbox.checked = !checkbox.checked; // Revertir cambio
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al conectar con el servidor');
            checkbox.checked = !checkbox.checked; // Revertir cambio
        });
    }

    function buscarEstacionEnDatos(estacionId) {
        for (const [nave, estaciones] of Object.entries(estacionesData)) {
            const estacion = estaciones.find(e => e.id === estacionId);
            if (estacion) return estacion;
        }
        return null;
    }

    function mostrarError(mensaje) {
        const mensajes = document.getElementById('mensajes');
        const div = document.createElement('div');
        div.className = 'error-message';
        div.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${mensaje}`;
        mensajes.insertBefore(div, mensajes.firstChild);

        setTimeout(() => div.remove(), 5000);
    }

    function mostrarExito(mensaje) {
        const mensajes = document.getElementById('mensajes');
        const div = document.createElement('div');
        div.className = 'success-message';
        div.innerHTML = `<i class="fas fa-check-circle me-2"></i>${mensaje}`;
        mensajes.insertBefore(div, mensajes.firstChild);

        setTimeout(() => div.remove(), 3000);
    }
</script>

</body>
</html>
