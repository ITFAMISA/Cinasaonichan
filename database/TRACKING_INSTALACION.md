# Instalación del Nuevo Sistema de Tracking

## Pasos de Instalación

### 1. Crear las Tablas Base
Ejecuta el siguiente script SQL primero:
```
database/tracking_sistema.sql
```

Este script creará las siguientes tablas:
- `tracking_asignaciones` - Asignaciones de empleados a pedidos
- `tracking_tipos_trabajo` - Tipos de trabajo disponibles
- `tracking_tiempo_detallado` - Registro detallado de horas
- `tracking_areas_trabajo` - Áreas de trabajo
- `tracking_empleado_habilidades` - Habilidades de empleados (opcional)

### 2. Insertar Datos Iniciales
Después de crear las tablas, ejecuta estos scripts en orden:

#### 2a. Tipos de Trabajo
```
database/tracking_insert_tipos_trabajo.sql
```

Inserta los siguientes tipos de trabajo:
- ARMADO
- CORTE
- CORTE SIERRA CINTA
- DETALLADO
- CONFORMADO
- DOBLEZ

#### 2b. Áreas de Trabajo
```
database/tracking_insert_areas.sql
```

Inserta las siguientes áreas:
- Nave 1 (Nave principal de producción)
- Nave 2 (Nave de ensamblaje y conformado)
- Nave 3 (Nave de acabados y procesos finales)

### 3. Acceder al Sistema
Una vez ejecutados todos los scripts SQL, accede a:
```
http://localhost/web/CINASAN/tracking_dashboard.php
```

## Funcionalidades Principales

### Panel Izquierdo - Lista de Empleados
- Muestra empleados activos
- Búsqueda de empleados por nombre o puesto
- Empleados arrastrables para crear asignaciones

### Panel Central - Layout de Tracking
- Visualización de asignaciones por área/nave
- Tarjetas mostrando:
  - Empleado asignado
  - Tipo de trabajo
  - Pedido y cantidad
  - Barra de progreso
- Filtrado por tipo de trabajo

### Panel Derecho - Registro de Horas
- Pestaña HRS: Formulario para registrar horas trabajadas
- Pestaña JOBS: Lista de pedidos disponibles
- Permite seleccionar empleado, pedido, tipo de trabajo y horas

## Características Implementadas

✅ Interfaz tipo dashboard con 3 paneles
✅ Arrastrar y soltar (drag & drop) de empleados
✅ Registro manual de horas trabajadas
✅ Filtrado por tipo de trabajo
✅ Visualización de asignaciones por área
✅ Sistema de asignaciones de empleados a pedidos
✅ Registro detallado de tiempo trabajado

## Personalización

Puedes personalizar el sistema modificando:

1. **Tipos de Trabajo**: Agrrega o modifica en `tracking_insert_tipos_trabajo.sql`
2. **Áreas de Trabajo**: Modifica nombres y colores en `tracking_insert_areas.sql`
3. **Colores**: Cada tipo de trabajo y área tiene su propio color (#RRGGBB)
4. **Orden**: Controla el orden de visualización con el campo `orden`

## Archivos del Sistema

### Principales
- `tracking_dashboard.php` - Página principal
- `app/assets/tracking_dashboard.js` - Lógica de JavaScript
- `database/tracking_sistema.sql` - Creación de tablas

### Controladores (API)
- `app/controllers/tracking_verificar_tablas.php` - Verifica instalación
- `app/controllers/tracking_tipos_trabajo_listar.php` - Lista tipos
- `app/controllers/tracking_areas_trabajo_listar.php` - Lista áreas
- `app/controllers/tracking_pedidos_listar.php` - Lista pedidos
- `app/controllers/tracking_asignaciones_listar.php` - Lista asignaciones
- `app/controllers/tracking_registrar_horas.php` - Registra horas
- `app/controllers/tracking_crear_asignacion.php` - Crea asignaciones

## Solución de Problemas

### Las tablas no están configuradas
- Ejecuta `database/tracking_sistema.sql`
- Luego ejecuta `database/tracking_insert_tipos_trabajo.sql`
- Y finalmente `database/tracking_insert_areas.sql`

### Los pedidos no aparecen
- Asegúrate de tener pedidos en estado 'en_produccion'
- Verifica que los pedidos tengan clientes asociados

### Las asignaciones no se cargan
- Verifica que los datos iniciales se hayan insertado correctamente
- Revisa la consola del navegador (F12) para ver errores

## Notas Importantes

- El sistema carga datos reales de tu base de datos
- Las asignaciones se guardan automáticamente
- El registro de horas se calcula automáticamente por minutos
- Se puede crear una asignación arrastrando un empleado a un área
- O registrando horas directamente en el formulario HRS
