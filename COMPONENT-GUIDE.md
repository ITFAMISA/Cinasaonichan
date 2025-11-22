# Guía de Componentes Reutilizables - Cinasa Sistema

## 📖 Tabla de Contenidos

1. [Cards (Tarjetas)](#1-cards-tarjetas)
2. [Stats Cards](#2-stats-cards)
3. [Botones](#3-botones)
4. [Badges](#4-badges)
5. [Tablas](#5-tablas)
6. [Formularios](#6-formularios)
7. [Modales](#7-modales)
8. [Alertas](#8-alertas)
9. [Paginación](#9-paginación)
10. [Navegación](#10-navegación)
11. [Filtros](#11-filtros)
12. [Utilidades](#12-utilidades)

---

## 1. Cards (Tarjetas)

### Uso Básico

```html
<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-users"></i> Lista de Clientes</h4>
    </div>
    <div class="card-body">
        <p>Contenido de la tarjeta</p>
    </div>
</div>
```

### Con Footer

```html
<div class="card">
    <div class="card-header">
        <h4>Título</h4>
    </div>
    <div class="card-body">
        Contenido
    </div>
    <div class="card-footer text-center">
        <button class="btn btn-primary">Ver Más</button>
    </div>
</div>
```

### Card con Tabla

```html
<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-table"></i> Datos</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <!-- contenido tabla -->
            </table>
        </div>
    </div>
</div>
```

**Nota**: Use `p-0` en `card-body` cuando contenga tablas para eliminar padding.

### Variantes

```html
<!-- Card sin hover -->
<div class="card" style="transition: none;">
    ...
</div>

<!-- Card con borde personalizado -->
<div class="card border-primary">
    ...
</div>
```

---

## 2. Stats Cards

### Card de Estadística Básica

```html
<div class="stats-card">
    <h3>1,234</h3>
    <p>Total Clientes</p>
</div>
```

### Con Icono

```html
<div class="stats-card">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <p class="mb-1 opacity-90">Total Productos</p>
            <h3 class="mb-0">567</h3>
        </div>
        <div>
            <i class="fas fa-boxes fa-3x opacity-50"></i>
        </div>
    </div>
</div>
```

### Colores Personalizados

```html
<!-- Azul (por defecto) -->
<div class="stats-card">...</div>

<!-- Verde -->
<div class="stats-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
    ...
</div>

<!-- Naranja -->
<div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
    ...
</div>

<!-- Morado -->
<div class="stats-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
    ...
</div>
```

---

## 3. Botones

### Variantes de Color

```html
<!-- Primario (Azul) -->
<button class="btn btn-primary">
    <i class="fas fa-save"></i> Guardar
</button>

<!-- Éxito (Verde) -->
<button class="btn btn-success">
    <i class="fas fa-check"></i> Confirmar
</button>

<!-- Peligro (Rojo) -->
<button class="btn btn-danger">
    <i class="fas fa-trash"></i> Eliminar
</button>

<!-- Advertencia (Naranja) -->
<button class="btn btn-warning">
    <i class="fas fa-exclamation-triangle"></i> Advertencia
</button>

<!-- Secundario (Gris) -->
<button class="btn btn-secondary">
    <i class="fas fa-times"></i> Cancelar
</button>
```

### Tamaños

```html
<!-- Pequeño -->
<button class="btn btn-sm btn-primary">Pequeño</button>

<!-- Normal (por defecto) -->
<button class="btn btn-primary">Normal</button>

<!-- Grande -->
<button class="btn btn-lg btn-primary">Grande</button>
```

### Botones Outline

```html
<button class="btn btn-outline-primary">Outline Primario</button>
<button class="btn btn-outline-success">Outline Éxito</button>
<button class="btn btn-outline-danger">Outline Peligro</button>
```

### Botones de Acción (Tablas)

```html
<div class="action-buttons">
    <button class="btn btn-sm btn-primary" title="Ver">
        <i class="fas fa-eye"></i>
    </button>
    <button class="btn btn-sm btn-warning" title="Editar">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn btn-sm btn-danger" title="Eliminar">
        <i class="fas fa-trash"></i>
    </button>
</div>
```

### Botón con Loading

```html
<button class="btn btn-primary" id="btnGuardar">
    <span id="spinner" class="spinner-border spinner-border-sm me-2 d-none"></span>
    <span id="textoBtn">Guardar</span>
</button>

<script>
function mostrarLoading() {
    document.getElementById('spinner').classList.remove('d-none');
    document.getElementById('textoBtn').textContent = 'Guardando...';
    document.getElementById('btnGuardar').disabled = true;
}

function ocultarLoading() {
    document.getElementById('spinner').classList.add('d-none');
    document.getElementById('textoBtn').textContent = 'Guardar';
    document.getElementById('btnGuardar').disabled = false;
}
</script>
```

---

## 4. Badges

### Estados de Empleados/Clientes

```html
<span class="badge badge-activo">Activo</span>
<span class="badge badge-inactivo">Inactivo</span>
<span class="badge badge-suspendido">Suspendido</span>
<span class="badge badge-bloqueado">Bloqueado</span>
<span class="badge badge-descontinuado">Descontinuado</span>
```

### Estados de Pedidos

```html
<span class="badge badge-creada">Creada</span>
<span class="badge badge-en_produccion">En Producción</span>
<span class="badge badge-completada">Completada</span>
<span class="badge badge-cancelada">Cancelada</span>
```

### Variantes Genéricas

```html
<span class="badge badge-success">Éxito</span>
<span class="badge badge-danger">Peligro</span>
<span class="badge badge-warning">Advertencia</span>
<span class="badge badge-info">Información</span>
```

### Con Icono

```html
<span class="badge badge-success">
    <i class="fas fa-check"></i> Activo
</span>

<span class="badge badge-danger">
    <i class="fas fa-times"></i> Bloqueado
</span>
```

### Ejemplo PHP Dinámico

```php
<?php
$estatus = 'activo'; // de base de datos
?>
<span class="badge badge-<?php echo $estatus; ?>">
    <?php echo ucfirst($estatus); ?>
</span>
```

---

## 5. Tablas

### Tabla Completa con Todos los Features

```html
<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-table"></i> Lista de Clientes</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th class="sortable" data-column="id">ID</th>
                        <th class="sortable" data-column="nombre">Nombre</th>
                        <th>Email</th>
                        <th>Estatus</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-clientes">
                    <tr>
                        <td><strong>1</strong></td>
                        <td>Juan Pérez</td>
                        <td>juan@example.com</td>
                        <td><span class="badge badge-activo">Activo</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
```

### Columnas Ordenables (JavaScript)

```javascript
document.querySelectorAll('.sortable').forEach(th => {
    th.addEventListener('click', function() {
        const column = this.dataset.column;
        const isAsc = this.classList.contains('asc');

        // Remover clases de otros headers
        document.querySelectorAll('.sortable').forEach(header => {
            header.classList.remove('asc', 'desc');
        });

        // Toggle orden
        if (isAsc) {
            this.classList.add('desc');
            sortTable(column, 'desc');
        } else {
            this.classList.add('asc');
            sortTable(column, 'asc');
        }
    });
});

function sortTable(column, direction) {
    // Lógica de ordenamiento
    console.log(`Ordenar por ${column} en dirección ${direction}`);
}
```

### Tabla Sin Datos (Estado Vacío)

```html
<div class="table-container">
    <table class="table">
        <thead>...</thead>
        <tbody>
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    No hay datos disponibles
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

---

## 6. Formularios

### Input de Texto

```html
<div class="mb-3">
    <label for="nombre" class="form-label">Nombre Completo</label>
    <input type="text" id="nombre" name="nombre" class="form-control"
           placeholder="Ingrese su nombre" required>
</div>
```

### Input con Icono

```html
<div class="mb-3">
    <label for="email" class="form-label">Correo Electrónico</label>
    <div class="input-group">
        <span class="input-group-text">
            <i class="fas fa-envelope"></i>
        </span>
        <input type="email" id="email" class="form-control"
               placeholder="ejemplo@correo.com">
    </div>
</div>
```

### Select

```html
<div class="mb-3">
    <label for="estatus" class="form-label">Estatus</label>
    <select id="estatus" name="estatus" class="form-select">
        <option value="">Seleccione...</option>
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
        <option value="suspendido">Suspendido</option>
    </select>
</div>
```

### Textarea

```html
<div class="mb-3">
    <label for="observaciones" class="form-label">Observaciones</label>
    <textarea id="observaciones" name="observaciones" class="form-control"
              rows="4" placeholder="Escriba sus observaciones..."></textarea>
</div>
```

### Checkbox y Radio

```html
<!-- Checkbox -->
<div class="mb-3 form-check">
    <input type="checkbox" id="acepto" name="acepto" class="form-check-input">
    <label for="acepto" class="form-check-label">
        Acepto los términos y condiciones
    </label>
</div>

<!-- Radio -->
<div class="mb-3">
    <label class="form-label">Tipo de Cliente</label>
    <div class="form-check">
        <input type="radio" id="nacional" name="tipo" value="nacional" class="form-check-input">
        <label for="nacional" class="form-check-label">Nacional</label>
    </div>
    <div class="form-check">
        <input type="radio" id="internacional" name="tipo" value="internacional" class="form-check-input">
        <label for="internacional" class="form-check-label">Internacional</label>
    </div>
</div>
```

### Formulario Completo

```html
<form id="formCliente">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="razon_social" class="form-label">Razón Social</label>
            <input type="text" id="razon_social" name="razon_social"
                   class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="rfc" class="form-label">RFC</label>
            <input type="text" id="rfc" name="rfc"
                   class="form-control" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="direccion" class="form-label">Dirección</label>
        <textarea id="direccion" name="direccion" class="form-control" rows="3"></textarea>
    </div>

    <div class="mb-3">
        <label for="estatus" class="form-label">Estatus</label>
        <select id="estatus" name="estatus" class="form-select">
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
        </select>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar
        </button>
    </div>
</form>
```

---

## 7. Modales

### Modal Básico

```html
<!-- Botón para abrir modal -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#miModal">
    <i class="fas fa-plus"></i> Nuevo Cliente
</button>

<!-- Modal -->
<div class="modal fade" id="miModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Nuevo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Formulario aquí -->
                <form id="formNuevoCliente">
                    ...
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardar">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>
```

### Modal Grande

```html
<div class="modal fade" id="modalGrande">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            ...
        </div>
    </div>
</div>
```

### Modal Extra Grande

```html
<div class="modal fade" id="modalXL">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            ...
        </div>
    </div>
</div>
```

### Abrir/Cerrar Modal con JavaScript

```javascript
// Abrir
const modal = new bootstrap.Modal(document.getElementById('miModal'));
modal.show();

// Cerrar
modal.hide();
```

---

## 8. Alertas

### Alertas de Sistema

```html
<!-- Éxito -->
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    El registro se guardó correctamente
</div>

<!-- Error -->
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i>
    Ocurrió un error al guardar el registro
</div>

<!-- Advertencia -->
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    Este campo es obligatorio
</div>

<!-- Información -->
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Complete todos los campos del formulario
</div>
```

### Mostrar Alerta con JavaScript

```javascript
function mostrarAlerta(mensaje, tipo = 'success') {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check' : 'exclamation'}-circle"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.querySelector('main').prepend(alerta);

    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        alerta.remove();
    }, 5000);
}

// Uso
mostrarAlerta('Operación exitosa', 'success');
mostrarAlerta('Error al procesar', 'danger');
```

---

## 9. Paginación

### Paginación Completa

```html
<nav>
    <ul class="pagination justify-content-center">
        <li class="page-item disabled">
            <a class="page-link" href="#" tabindex="-1">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <li class="page-item active">
            <a class="page-link" href="#">1</a>
        </li>
        <li class="page-item">
            <a class="page-link" href="#">2</a>
        </li>
        <li class="page-item">
            <a class="page-link" href="#">3</a>
        </li>
        <li class="page-item">
            <a class="page-link" href="#">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
```

### Generar Paginación con JavaScript

```javascript
function renderPaginacion(paginaActual, totalPaginas) {
    const container = document.getElementById('paginacion');
    let html = '<ul class="pagination justify-content-center">';

    // Anterior
    html += `<li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="cargarPagina(${paginaActual - 1})">
            <i class="fas fa-chevron-left"></i>
        </a>
    </li>`;

    // Números de página
    for (let i = 1; i <= totalPaginas; i++) {
        html += `<li class="page-item ${i === paginaActual ? 'active' : ''}">
            <a class="page-link" href="#" onclick="cargarPagina(${i})">${i}</a>
        </li>`;
    }

    // Siguiente
    html += `<li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="cargarPagina(${paginaActual + 1})">
            <i class="fas fa-chevron-right"></i>
        </a>
    </li>`;

    html += '</ul>';
    container.innerHTML = html;
}
```

---

## 10. Navegación

Las clases de navegación ya están implementadas en `header.php` y NO deben modificarse para preservar funcionalidad.

### Clases Disponibles

```html
<!-- Desktop -->
<a href="#" class="nav-link-compact">
    <i class="fas fa-users"></i>
    <span>Clientes</span>
</a>

<!-- Mobile -->
<a href="#" class="mobile-nav-link">
    <i class="fas fa-users"></i>
    <span>Clientes</span>
</a>
```

---

## 11. Filtros

### Sección de Filtros

```html
<div class="filter-section">
    <h5><i class="fas fa-filter"></i> Filtros</h5>

    <div class="row g-3">
        <div class="col-md-4">
            <label for="filtro_busqueda" class="form-label">Búsqueda</label>
            <input type="text" id="filtro_busqueda" class="form-control"
                   placeholder="Buscar...">
        </div>

        <div class="col-md-3">
            <label for="filtro_estatus" class="form-label">Estatus</label>
            <select id="filtro_estatus" class="form-select">
                <option value="">Todos</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="filtro_fecha" class="form-label">Fecha</label>
            <input type="date" id="filtro_fecha" class="form-control">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" onclick="aplicarFiltros()">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
    </div>

    <div class="mt-3">
        <button class="btn btn-sm btn-secondary" onclick="limpiarFiltros()">
            <i class="fas fa-times"></i> Limpiar Filtros
        </button>
    </div>
</div>
```

---

## 12. Utilidades

### Loading Spinner

```html
<div class="loading">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
    </div>
</div>
```

### Empty State (Sin Datos)

```html
<div class="text-center py-5">
    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
    <p class="text-muted">No hay datos disponibles</p>
    <button class="btn btn-primary mt-2">
        <i class="fas fa-plus"></i> Agregar Nuevo
    </button>
</div>
```

### Breadcrumb

```html
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="clientes.php">Clientes</a>
        </li>
        <li class="breadcrumb-item active">Nuevo Cliente</li>
    </ol>
</nav>
```

### Scrollbar Personalizado

```html
<div class="scrollbar-custom" style="max-height: 400px; overflow-y: auto;">
    <!-- Contenido con scroll -->
</div>
```

---

**Fin de Guía de Componentes** | Versión 2.0.0 | 2025-11-22
