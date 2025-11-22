# Guía de Validación y Testing Visual - Cinasa Sistema

## 📋 Objetivo

Esta guía proporciona una lista exhaustiva de pruebas visuales y funcionales para validar que el rediseño UI corporativo NO ha roto ninguna funcionalidad del sistema Cinasa.

---

## ✅ Checklist General de Validación

### 1. Funcionalidad Backend Preservada

- [ ] **Login/Logout**
  - [ ] Login con credenciales correctas funciona
  - [ ] Login con credenciales incorrectas muestra error
  - [ ] Logout cierra sesión correctamente
  - [ ] Redirección a login si no hay sesión activa

- [ ] **Sesiones y Autenticación**
  - [ ] Sesión persiste entre páginas
  - [ ] Usuario autenticado ve su nombre en header
  - [ ] Permisos de módulos se respetan

### 2. Navegación

- [ ] **Header/Navbar**
  - [ ] Logo es clickeable y redirige a dashboard
  - [ ] Todos los links del menú funcionan
  - [ ] Menú móvil (hamburguesa) abre/cierra correctamente
  - [ ] Usuario puede navegar por teclado (Tab)
  - [ ] Active state se muestra en página actual

- [ ] **Breadcrumbs** (si aplica)
  - [ ] Breadcrumbs muestran ruta correcta
  - [ ] Links en breadcrumbs funcionan

### 3. Dashboard Principal

- [ ] **Estadísticas**
  - [ ] Cards de estadísticas muestran números correctos
  - [ ] Números se formatean con separadores de miles
  - [ ] Iconos están visibles
  - [ ] Hover effects funcionan

- [ ] **Estado de Pedidos**
  - [ ] Tabla carga correctamente
  - [ ] Badges de estatus tienen colores correctos
  - [ ] Números corresponden a consultas SQL

- [ ] **Pedidos Recientes**
  - [ ] Tabla muestra últimos 5 pedidos
  - [ ] Click en fila redirige a página de pedidos
  - [ ] Botón "Ver Todos" funciona

- [ ] **Accesos Rápidos**
  - [ ] Botones redirigen a módulos correctos
  - [ ] Solo se muestran módulos con permisos
  - [ ] Iconos están visibles

---

## 🔍 Tests por Módulo

### MÓDULO: Clientes (index.php)

#### Funcionalidad CRUD

- [ ] **Listar Clientes**
  - [ ] Tabla carga con clientes de BD
  - [ ] Paginación funciona
  - [ ] Columnas muestran datos correctos
  - [ ] Badges de estatus tienen colores adecuados

- [ ] **Crear Cliente**
  - [ ] Botón "Nuevo Cliente" abre modal
  - [ ] Modal tiene 6 secciones (Fiscales, Ubicación, Contacto, Comerciales, Bancarias, Archivos)
  - [ ] Todos los campos son editables
  - [ ] Validación de campos obligatorios funciona
  - [ ] Botón "Guardar" envía datos a backend
  - [ ] Mensaje de éxito aparece después de guardar
  - [ ] Tabla se recarga con nuevo cliente

- [ ] **Editar Cliente**
  - [ ] Botón "Editar" carga datos en modal
  - [ ] Campos pre-populados correctamente
  - [ ] Cambios se guardan en BD
  - [ ] Modal se cierra después de guardar

- [ ] **Eliminar Cliente**
  - [ ] Botón "Eliminar" muestra confirmación
  - [ ] Confirmación tiene botones Sí/No
  - [ ] Cliente se elimina de BD al confirmar
  - [ ] Tabla se recarga sin el cliente eliminado

- [ ] **Exportar CSV**
  - [ ] Botón "Exportar CSV" descarga archivo
  - [ ] Archivo contiene todos los clientes
  - [ ] Formato CSV es correcto

- [ ] **Exportar PDF**
  - [ ] Botón "PDF" en fila genera PDF individual
  - [ ] PDF se descarga correctamente
  - [ ] Datos en PDF son correctos

#### Filtros y Búsqueda

- [ ] **Búsqueda General**
  - [ ] Input de búsqueda filtra clientes en tiempo real
  - [ ] Búsqueda funciona en razón social, RFC, contacto

- [ ] **Filtros**
  - [ ] Filtro "Estatus" funciona
  - [ ] Filtro "Vendedor" funciona
  - [ ] Filtro "País" funciona
  - [ ] Botón "Limpiar Filtros" resetea todos los filtros

#### Visual

- [ ] Card tiene glass morphism
- [ ] Header tiene gradiente azul
- [ ] Tabla tiene hover animado
- [ ] Botones tienen ripple effect
- [ ] Modal tiene header azul y border-radius
- [ ] Badges tienen colores corporativos

---

### MÓDULO: Productos (productos.php)

#### Funcionalidad CRUD

- [ ] **Listar Productos**
  - [ ] Tabla carga correctamente
  - [ ] Columnas: Código, Descripción, UM, Número Dibujo, Estatus
  - [ ] Paginación funciona

- [ ] **Crear Producto**
  - [ ] Modal se abre
  - [ ] Formulario tiene todos los campos
  - [ ] Guardar crea producto en BD
  - [ ] Tabla se actualiza

- [ ] **Editar Producto**
  - [ ] Datos se cargan en modal
  - [ ] Cambios se guardan

- [ ] **Eliminar Producto**
  - [ ] Confirmación funciona
  - [ ] Producto se elimina

#### Filtros

- [ ] Búsqueda funciona
- [ ] Filtro Estatus funciona
- [ ] Filtro País Origen funciona
- [ ] Filtro Categoría funciona

#### Visual

- [ ] Componentes usan clases corporativas
- [ ] Animaciones son sutiles
- [ ] Responsive funciona

---

### MÓDULO: Pedidos (pedidos.php, crear_pedido.php, editar_pedido.php)

#### Listado de Pedidos

- [ ] **Tabla**
  - [ ] Columnas: Número, Cliente, Fecha, Estatus, Contacto
  - [ ] Badges de estatus correctos (creada, en_produccion, completada, cancelada)
  - [ ] Filtros funcionan

#### Crear Pedido

- [ ] **Formulario**
  - [ ] 5 secciones visibles:
    1. Datos Generales
    2. Información Cliente
    3. Direcciones (Bill To, Ship To)
    4. Productos/Items
    5. Observaciones

- [ ] **Búsqueda de Cliente**
  - [ ] Input muestra dropdown con resultados
  - [ ] Seleccionar cliente completa campos automáticamente
  - [ ] Dropdown desaparece al seleccionar

- [ ] **Tabla de Items**
  - [ ] Botón "Agregar Item" añade fila
  - [ ] Búsqueda de producto funciona en cada fila
  - [ ] Cantidad y precio son editables
  - [ ] Subtotal se calcula automáticamente
  - [ ] Botón "Eliminar" quita fila
  - [ ] Total general se calcula

- [ ] **Guardar Pedido**
  - [ ] Validación de campos obligatorios
  - [ ] Pedido se guarda en BD
  - [ ] Redirección a lista de pedidos

#### Editar Pedido

- [ ] Datos se cargan correctamente
- [ ] Items del pedido se muestran
- [ ] Cambios se guardan

#### Visual

- [ ] Cards con glass morphism
- [ ] Dropdowns de búsqueda tienen z-index correcto
- [ ] Tabla de items es responsive
- [ ] Botones tienen colores corporativos

---

### MÓDULO: Control de Calidad (calidad.php, calidad_pedido.php)

#### Listado de Pendientes

- [ ] Tabla muestra pedidos con piezas pendientes
- [ ] Botón "Inspeccionar" abre detalle

#### Inspección por Pedido

- [ ] Lista de piezas a inspeccionar carga
- [ ] Modal de inspección se abre
- [ ] Formulario permite seleccionar:
  - [ ] Cantidad inspeccionada
  - [ ] Cantidad aprobada
  - [ ] Cantidad rechazada
  - [ ] Defectos (checkboxes)
  - [ ] Observaciones

- [ ] Guardar inspección actualiza BD
- [ ] Badges de resultado correcto (Aprobado/Rechazado/Pendiente)

#### Visual

- [ ] Modal tiene estilos corporativos
- [ ] Badges de calidad tienen colores adecuados (verde/rojo/naranja)
- [ ] Formulario es claro y accesible

---

### MÓDULO: Empleados (empleados.php, empleados_detalle.php)

#### Listado de Empleados

- [ ] Tabla carga correctamente
- [ ] Columnas: Apellido, Nombre, Puesto, Departamento, Correo, Estatus
- [ ] Filtros funcionan (búsqueda, estatus, departamento, puesto)
- [ ] Badges de estatus correcto

#### Crear/Editar Empleado

- [ ] Modal se abre con formulario completo
- [ ] Campos: nombre, apellido, puesto, departamento, correo, teléfono, estatus
- [ ] Guardar funciona

#### Detalle de Empleado

- [ ] Información personal se muestra
- [ ] Información laboral se muestra
- [ ] Habilidades por proceso se listan

#### Visual

- [ ] Componentes usan design tokens
- [ ] Badges de estatus tienen colores corporativos
- [ ] Modal tiene header azul

---

### MÓDULO: Tracking Dashboard (tracking_dashboard.php) ⚠️ CRÍTICO

#### Estructura DOM (NO DEBE CAMBIAR)

- [ ] **3 Columnas Principales**
  - [ ] Nave 1 existe y tiene estaciones
  - [ ] Nave 2 existe y tiene estaciones
  - [ ] Nave 3 existe y tiene estaciones

- [ ] **Panel Izquierdo: Lista de Empleados**
  - [ ] Lista de empleados carga
  - [ ] Búsqueda filtra empleados
  - [ ] Empleados son draggables

- [ ] **Panel Central: Layout de Tracking**
  - [ ] Grid de 3 columnas visible
  - [ ] Estaciones se cargan dinámicamente
  - [ ] Cada estación tiene turnos
  - [ ] Drag & drop funciona

- [ ] **Panel Derecho: Seguimiento**
  - [ ] Tabs HORAS y PEDIDOS funcionan
  - [ ] Formulario de registro de horas funciona
  - [ ] Lista de pedidos carga

#### Funcionalidad JavaScript

- [ ] Drag de empleados a estaciones funciona
- [ ] Drop en turnos crea asignación
- [ ] Tarjetas de asignación son movibles
- [ ] Modal de asignación se abre
- [ ] Guardar asignación actualiza vista
- [ ] Configurar turnos funciona

#### Visual (PERMITIDO CAMBIAR)

- [ ] Colores de estaciones más vibrantes
- [ ] Bordes redondeados (8-12px)
- [ ] Sombras sutiles
- [ ] Hover effects en estaciones
- [ ] Transiciones suaves (150-250ms)

#### IDs y Clases Protegidas (NO ELIMINAR)

- [ ] `.tracking-container` existe
- [ ] `.panel-empleados` existe
- [ ] `.panel-layout` existe
- [ ] `.tracking-areas` existe
- [ ] `.tracking-area[data-id]` existen
- [ ] `.estacion-item` existe
- [ ] `.turno-section` existe
- [ ] `.turno-content` existe
- [ ] `.asignacion-turno-card` existe

---

### MÓDULO: Producción (produccion.php, produccion_detalle.php)

- [ ] Listado de órdenes carga
- [ ] Filtros funcionan
- [ ] Detalle de orden muestra información correcta
- [ ] Acciones (modificar, autorizar) funcionan

---

### MÓDULO: Administración (admin.php)

#### Usuarios

- [ ] Tabla de usuarios carga
- [ ] Crear usuario funciona
- [ ] Editar usuario funciona
- [ ] Eliminar usuario funciona (con confirmación)
- [ ] Filtros funcionan

#### Roles

- [ ] Tabla de roles carga
- [ ] Crear rol funciona
- [ ] Editar rol con permisos funciona
- [ ] Eliminar rol funciona

#### Visual

- [ ] Secciones tienen cards separados
- [ ] Botones tienen colores corporativos
- [ ] Modales tienen estilos correctos

---

## 🎨 Validación Visual

### Design Tokens

- [ ] **Colores**
  - [ ] Azul primario: #2563eb está presente
  - [ ] Grises neutros están en uso
  - [ ] Success verde: #10b981
  - [ ] Danger rojo: #ef4444
  - [ ] Warning naranja: #f59e0b
  - [ ] Info cyan: #06b6d4

- [ ] **Tipografía**
  - [ ] Fuente Inter carga correctamente
  - [ ] Fuente Poppins carga para headers
  - [ ] Tamaños de fuente son consistentes

- [ ] **Espaciado**
  - [ ] Padding y margins son equilibrados
  - [ ] Gaps entre elementos son consistentes

- [ ] **Border Radius**
  - [ ] Cards: 16px
  - [ ] Botones: 10px
  - [ ] Inputs: 10px
  - [ ] Badges: rounded-full (9999px)

- [ ] **Sombras**
  - [ ] Cards tienen sombra suave
  - [ ] Hover aumenta sombra
  - [ ] Sombras no son excesivas

### Componentes

- [ ] **Cards**
  - [ ] Glass morphism (fondo semi-transparente con blur)
  - [ ] Hover eleva card (-translateY)
  - [ ] Header tiene gradiente azul

- [ ] **Botones**
  - [ ] Gradientes de colores
  - [ ] Ripple effect al click
  - [ ] Hover eleva botón

- [ ] **Badges**
  - [ ] Forma circular
  - [ ] Colores correctos por estado
  - [ ] Hover escala 110%

- [ ] **Tablas**
  - [ ] Header gradiente oscuro
  - [ ] Hover en filas con gradiente azul + translateX + shadow
  - [ ] Columnas ordenables tienen iconos

- [ ] **Modales**
  - [ ] Header gradiente azul
  - [ ] Close button rota al hover
  - [ ] Border radius 16px

- [ ] **Alertas**
  - [ ] Border izquierdo de 4px
  - [ ] Iconos visibles
  - [ ] Animación de entrada (slide-up)

### Animaciones

- [ ] **Duración**
  - [ ] Transiciones: 150-250ms
  - [ ] No hay animaciones lentas (>350ms)

- [ ] **Easing**
  - [ ] cubic-bezier(0.4, 0, 0.2, 1) en uso
  - [ ] Transiciones son suaves

- [ ] **Microinteracciones**
  - [ ] Hover states claros
  - [ ] Focus states visibles
  - [ ] Click feedback presente

### Responsive

- [ ] **Desktop (≥1024px)**
  - [ ] Layout de 2-3 columnas funciona
  - [ ] Navegación horizontal visible
  - [ ] Tablas muestran todas las columnas

- [ ] **Tablet (768px - 1023px)**
  - [ ] Layout se ajusta
  - [ ] Navegación responsive
  - [ ] Tablas tienen scroll horizontal

- [ ] **Mobile (<768px)**
  - [ ] Menú hamburguesa funciona
  - [ ] Cards en columna única
  - [ ] Botones son clickeables
  - [ ] Font-size legible

---

## ♿ Validación de Accesibilidad

### Contraste

- [ ] Texto principal vs fondo: ≥4.5:1 (WCAG AA)
- [ ] Texto grande vs fondo: ≥3:1 (WCAG AA)
- [ ] Botones vs fondo: ≥4.5:1

### Navegación por Teclado

- [ ] Tab recorre elementos en orden lógico
- [ ] Enter activa botones
- [ ] Escape cierra modales
- [ ] Flechas navegan en selects

### Focus States

- [ ] Todos los controles tienen focus visible
- [ ] Focus ring es claro (ring-4 con opacidad)
- [ ] Focus no se oculta con outline: none sin alternativa

### Labels y Aria

- [ ] Todos los inputs tienen labels
- [ ] Labels están asociados (for="id")
- [ ] Iconos decorativos tienen aria-hidden
- [ ] Botones sin texto tienen aria-label

---

## 🔧 Validación Técnica

### CSS Compilado

- [ ] Archivo `app/assets/dist/output.css` existe
- [ ] CSS está minificado
- [ ] Tamaño del archivo es razonable (<1MB)

### Referencias en Header

- [ ] Google Fonts (Inter, Poppins) cargadas
- [ ] Font Awesome cargado
- [ ] output.css referenciado
- [ ] style.css (legacy) referenciado

### Consola del Navegador

- [ ] No hay errores JavaScript
- [ ] No hay errores 404 (recursos no encontrados)
- [ ] No hay warnings críticos

### Performance

- [ ] Tiempo de carga <3 segundos
- [ ] First Contentful Paint <1.5s
- [ ] Time to Interactive <3s

---

## 📝 Reporte de Bugs

Si encuentras un problema durante las pruebas, documentalo usando este formato:

```
**Módulo**: [Nombre del módulo]
**Página**: [Ruta del archivo]
**Problema**: [Descripción breve]
**Pasos para Reproducir**:
1. [Paso 1]
2. [Paso 2]
3. [Paso 3]

**Resultado Esperado**: [Qué debería pasar]
**Resultado Actual**: [Qué pasa realmente]

**Captura de Pantalla**: [Si aplica]
**Consola**: [Errores en consola]

**Prioridad**: [Baja/Media/Alta/Crítica]
```

---

## 🎯 Criterios de Aceptación

El rediseño se considera **APROBADO** si:

✅ **100%** de funcionalidad backend preservada
✅ **100%** de IDs y clases JS intactas
✅ **100%** de endpoints funcionando
✅ **90%+** de componentes usando design tokens
✅ **90%+** de pruebas visuales pasadas
✅ **100%** de tracking_dashboard.php preservado (estructura DOM)
✅ **WCAG AA** de accesibilidad cumplido

El rediseño se considera **RECHAZADO** si:

❌ Cualquier funcionalidad CRUD está rota
❌ JavaScript no funciona debido a clases/IDs eliminados
❌ tracking_dashboard.php tiene estructura modificada
❌ Endpoints devuelven errores
❌ Contraste de colores no cumple WCAG AA

---

**Fin de Guía de Testing** | Versión 2.0.0 | 2025-11-22
