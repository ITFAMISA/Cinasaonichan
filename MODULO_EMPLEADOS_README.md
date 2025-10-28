# Módulo de Gestión de Empleados - CINASA

## Descripción

Módulo completo para la gestión de empleados en el sistema CINASA. Incluye registro, edición, visualización y eliminación de empleados con campos requeridos y opcionales para futuras funcionalidades.

## Campos Requeridos

- **Nombre**: Nombre del empleado
- **Apellido**: Apellido del empleado
- **Puesto**: Puesto o cargo del empleado

## Campos Opcionales (Para Futuras Funcionalidades)

### Contacto
- Correo electrónico
- Teléfono
- Extensión telefónica

### Datos Personales
- Fecha de nacimiento
- Género (M/F/Otro)
- Estado civil (Soltero, Casado, Divorciado, Viudo, Unión Libre)
- Cantidad de dependientes

### Identificación
- Número de identificación (RFC, INE, Pasaporte, etc.)
- Tipo de identificación
- Número de seguro social

### Información Bancaria
- Banco
- Número de cuenta bancaria
- CLABE interbancaria (para transferencias)

### Dirección
- Dirección completa
- Ciudad
- Estado
- Código postal
- País (default: México)

### Contacto de Emergencia
- Nombre del contacto de emergencia
- Relación con el contacto
- Teléfono del contacto

### Información Laboral
- Número de empleado (único)
- Departamento
- Fecha de ingreso
- Salario base
- Tipo de contrato
- Fecha del contrato
- Supervisor directo (relación auto-referenciada)

## Instalación

### 1. Ejecutar el Script SQL

Primero, ejecuta el script `cambios_sql.sql` para crear la tabla de empleados:

```bash
mysql -u root -p clientes_db < cambios_sql.sql
```

O importa el archivo a través de phpMyAdmin:
- Abre phpMyAdmin
- Selecciona la base de datos `clientes_db`
- Ve a la pestaña "Importar"
- Selecciona el archivo `cambios_sql.sql`
- Haz clic en "Importar"

### 2. Verificar los Archivos Creados

El módulo incluye los siguientes archivos:

#### Base de Datos
- `cambios_sql.sql` - Script SQL con la creación de la tabla
- `database/empleados.sql` - Archivo SQL independiente de empleados

#### Modelo
- `app/models/empleados_model.php` - Modelo de datos para empleados

#### Controladores
- `app/controllers/empleados_listar.php` - Listar empleados con filtros y paginación
- `app/controllers/empleados_crear.php` - Crear nuevo empleado
- `app/controllers/empleados_detalle.php` - Obtener detalle de un empleado
- `app/controllers/empleados_editar.php` - Editar un empleado existente
- `app/controllers/empleados_eliminar.php` - Eliminar un empleado
- `app/controllers/empleados_opciones.php` - Obtener opciones (departamentos, puestos, supervisores)

#### Vistas
- `empleados.php` - Página principal de gestión de empleados
- `empleados_detalle.php` - Página de detalle de un empleado
- `app/assets/empleados.js` - JavaScript para la lógica del módulo

## Uso

### Acceso al Módulo

Para acceder al módulo de empleados, ve a:
```
http://tu-servidor/CINASA/empleados.php
```

### Funcionalidades

#### 1. Listar Empleados
- La página principal muestra un listado de todos los empleados
- Incluye paginación (20 empleados por página)
- Permite ordenar por: apellido, nombre, puesto, departamento, estatus, fecha ingreso, salario

#### 2. Filtrar Empleados
- **Búsqueda**: Por nombre, apellido, número de empleado o correo
- **Estatus**: Activo, Inactivo, Licencia, Suspendido, Jubilado
- **Departamento**: Filtra por departamento
- **Puesto**: Filtra por puesto

#### 3. Crear Nuevo Empleado
1. Haz clic en el botón "Nuevo Empleado"
2. Completa el formulario con los datos del empleado
3. Los campos marcados con * son requeridos
4. Haz clic en "Guardar"

#### 4. Ver Detalle de Empleado
1. En el listado, haz clic en el ícono de ojo (<i class="fas fa-eye"></i>)
2. Se abre una página con todos los detalles del empleado
3. Los datos se organizan en secciones por categoría

#### 5. Editar Empleado
1. En el listado, haz clic en el ícono de editar (<i class="fas fa-edit"></i>)
2. O desde la página de detalle, haz clic en "Editar"
3. Modifica los campos necesarios
4. Haz clic en "Guardar"

#### 6. Eliminar Empleado
1. En el listado, haz clic en el ícono de eliminar (<i class="fas fa-trash"></i>)
2. Confirma la eliminación
3. **Nota**: No se puede eliminar un empleado si es supervisor de otros empleados

### Relación de Supervisores

El módulo permite establecer relaciones jerárquicas mediante el campo "Supervisor Directo". Esto es útil para:
- Organigrama de la empresa
- Reportes de desempeño
- Cadena de mando

**Restricción**: No se puede eliminar un empleado que sea supervisor de otros.

## API Endpoints

Si deseas interactuar directamente con la API:

### Listar Empleados
```
GET /app/controllers/empleados_listar.php?pagina=1&buscar=&estatus_empleado=&departamento=&puesto=
```

**Parámetros**:
- `pagina`: Número de página (default: 1)
- `buscar`: Término de búsqueda
- `estatus_empleado`: Estado del empleado
- `departamento`: Departamento
- `puesto`: Puesto
- `orden`: Campo para ordenar (default: apellido)
- `direccion`: ASC o DESC (default: ASC)

### Obtener Detalle
```
GET /app/controllers/empleados_detalle.php?id=1
```

### Crear Empleado
```
POST /app/controllers/empleados_crear.php
Content-Type: application/json

{
  "nombre": "Juan",
  "apellido": "Pérez",
  "puesto": "Ingeniero",
  "correo": "juan@example.com",
  ...
}
```

### Editar Empleado
```
POST /app/controllers/empleados_editar.php?id=1
Content-Type: application/json

{
  "nombre": "Juan",
  "apellido": "García",
  ...
}
```

### Eliminar Empleado
```
POST /app/controllers/empleados_eliminar.php?id=1
```

### Obtener Opciones
```
GET /app/controllers/empleados_opciones.php?opcion=departamentos|puestos|supervisores|todas
```

## Estructura de la Base de Datos

### Tabla: empleados

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| id | bigint(20) UNSIGNED | NO | ID auto-increment |
| nombre | varchar(100) | NO | Nombre (REQUERIDO) |
| apellido | varchar(100) | NO | Apellido (REQUERIDO) |
| puesto | varchar(100) | NO | Puesto (REQUERIDO) |
| numero_empleado | varchar(50) | YES | Número único de empleado |
| correo | varchar(150) | YES | Correo electrónico |
| telefono | varchar(30) | YES | Número de teléfono |
| telefono_extension | varchar(10) | YES | Extensión telefónica |
| departamento | varchar(100) | YES | Departamento |
| fecha_ingreso | date | YES | Fecha de ingreso |
| fecha_nacimiento | date | YES | Fecha de nacimiento |
| genero | enum | YES | M, F, Otro |
| numero_identificacion | varchar(50) | YES | RFC, INE, Pasaporte |
| tipo_identificacion | varchar(50) | YES | Tipo de documento |
| numero_seguro_social | varchar(50) | YES | Número de seguro social |
| banco | varchar(150) | YES | Banco |
| cuenta_bancaria | varchar(50) | YES | Número de cuenta |
| clabe | varchar(18) | YES | CLABE interbancaria |
| direccion | text | YES | Dirección completa |
| ciudad | varchar(100) | YES | Ciudad |
| estado | varchar(100) | YES | Estado/Provincia |
| codigo_postal | varchar(10) | YES | Código postal |
| pais | varchar(100) | YES | País (default: México) |
| contacto_emergencia_nombre | varchar(150) | YES | Nombre contacto emergencia |
| contacto_emergencia_relacion | varchar(50) | YES | Relación |
| contacto_emergencia_telefono | varchar(30) | YES | Teléfono emergencia |
| estado_civil | enum | YES | Soltero, Casado, etc. |
| cantidad_dependientes | int(11) | YES | Número de dependientes |
| nivel_escolaridad | varchar(100) | YES | Nivel de estudio |
| especialidad | varchar(100) | YES | Carrera/especialización |
| estatus_empleado | enum | NO | activo, inactivo, licencia, suspendido, jubilado |
| salario_base | decimal(15,2) | YES | Salario mensual |
| tipo_contrato | varchar(100) | YES | Tipo de contrato |
| fecha_contrato | date | YES | Fecha del contrato |
| supervisor_directo_id | bigint(20) UNSIGNED | YES | ID del supervisor |
| observaciones | text | YES | Notas adicionales |
| fecha_creacion | datetime | NO | Fecha de creación |
| fecha_actualizacion | datetime | NO | Fecha de última actualización |

### Índices
- PRIMARY KEY: id
- UNIQUE KEY: numero_empleado
- INDEX: idx_puesto
- INDEX: idx_departamento
- INDEX: idx_estatus_empleado
- INDEX: idx_apellido
- INDEX: idx_supervisor_directo_id
- FOREIGN KEY: supervisor_directo_id → empleados(id)

## Próximas Funcionalidades Sugeridas

Basado en los campos opcionales disponibles, se pueden implementar:

1. **Reportes de Recursos Humanos**
   - Nómina por departamento
   - Análisis de salarios
   - Reporte de cumpleaños próximos

2. **Gestión de Nómina**
   - Cálculo automático de salarios
   - Generación de comprobantes
   - Integración bancaria

3. **Evaluaciones de Desempeño**
   - Historial de evaluaciones
   - Calificaciones y comentarios
   - Planes de desarrollo

4. **Organigrama**
   - Visualización de estructura organizacional
   - Relaciones jerárquicas
   - Reportes de supervisión

5. **Ausencias y Licencias**
   - Registro de vacaciones
   - Licencias médicas
   - Permisos

## Troubleshooting

### Problema: "Error de base de datos al obtener empleados"
**Solución**: Asegúrate de haber ejecutado correctamente el script SQL `cambios_sql.sql`

### Problema: "No puedo eliminar un empleado"
**Solución**: Si el empleado es supervisor de otros, primero asigna esos empleados a otro supervisor o elimínalos.

### Problema: Los números de empleado no son únicos
**Solución**: El sistema previene duplicados mediante un índice UNIQUE. Verifica que no exista otro empleado con el mismo número.

## Soporte

Para reportar problemas o sugerir mejoras, contacta al equipo de desarrollo de CINASA.

---

**Última actualización**: 28 de Octubre de 2025
**Versión**: 1.0
**Estado**: Funcional
