# Guía de Instalación - Módulo de Empleados

## Pasos de Instalación

### 1. Ejecutar el Script SQL

El módulo incluye un script SQL que crea la tabla de empleados. Ejecuta uno de estos métodos:

#### Opción A: Línea de comandos
```bash
cd C:\xampp2\htdocs\CINASA
mysql -u root -p clientes_db < cambios_sql.sql
```

#### Opción B: phpMyAdmin
1. Abre phpMyAdmin en `http://localhost/phpmyadmin`
2. Selecciona la base de datos `clientes_db`
3. Ve a la pestaña **Importar**
4. Carga el archivo `cambios_sql.sql`
5. Haz clic en **Importar**

#### Opción C: Cliente MySQL GUI
1. Abre tu cliente MySQL preferido
2. Conecta a `clientes_db`
3. Copia y pega el contenido de `cambios_sql.sql`
4. Ejecuta el script

### 2. Verificar la Instalación

Después de ejecutar el script, verifica que la tabla se creó correctamente:

```sql
DESCRIBE empleados;
```

Deberías ver una tabla con más de 40 columnas incluyendo: id, nombre, apellido, puesto, etc.

### 3. Verificar los Archivos

Asegúrate de que los siguientes archivos estén en su lugar:

```
CINASA/
├── empleados.php                    (Página principal)
├── empleados_detalle.php           (Página de detalle)
├── cambios_sql.sql                 (Script SQL)
├── MODULO_EMPLEADOS_README.md      (Documentación)
├── INSTALACION_MODULO_EMPLEADOS.md (Este archivo)
├── app/
│   ├── models/
│   │   └── empleados_model.php
│   ├── controllers/
│   │   ├── empleados_listar.php
│   │   ├── empleados_crear.php
│   │   ├── empleados_detalle.php
│   │   ├── empleados_editar.php
│   │   ├── empleados_eliminar.php
│   │   └── empleados_opciones.php
│   ├── assets/
│   │   └── empleados.js
│   └── views/
│       └── header.php (ACTUALIZADO)
└── database/
    └── empleados.sql
```

### 4. Acceder al Módulo

Una vez instalado, puedes acceder al módulo de dos maneras:

**Opción 1: Desde el menú**
1. Ve a `http://localhost/CINASA/index.php`
2. En el menú de navegación (arriba), haz clic en "Empleados"
3. Se abrirá el módulo de gestión de empleados

**Opción 2: Dirección directa**
```
http://localhost/CINASA/empleados.php
```

## Verificación Rápida

Para verificar que el módulo está completamente funcional:

1. **Crear empleado**: Haz clic en "Nuevo Empleado", completa los campos y guarda
2. **Listar**: Deberías ver el empleado creado en la tabla
3. **Filtrar**: Usa los filtros (búsqueda, estatus, departamento) para verificar que funcionan
4. **Ver detalle**: Haz clic en el ícono de ojo para ver los detalles completos
5. **Editar**: Haz clic en el ícono de editar para modificar datos
6. **Eliminar**: Intenta eliminar (nota: solo funciona si el empleado no es supervisor)

## Solución de Problemas

### Problema: "Table 'clientes_db.empleados' doesn't exist"
**Causa**: El script SQL no se ejecutó correctamente
**Solución**:
- Verifica que estés en la base de datos `clientes_db`
- Intenta ejecutar el script nuevamente
- Revisa si hay errores en la consola MySQL

### Problema: "Error al cargar empleados"
**Causa**: El controlador no encuentra la base de datos
**Solución**:
- Verifica que `app/config/database.php` esté configurado correctamente
- Asegúrate de que el usuario MySQL tiene permisos en `clientes_db`
- Revisa los logs de error en la consola del navegador (F12 → Consola)

### Problema: El módulo no aparece en el menú
**Causa**: El archivo header.php no fue actualizado
**Solución**:
- Verifica que `app/views/header.php` tenga el enlace a empleados.php
- Recarga la página del navegador (Ctrl+F5)
- Limpia el caché del navegador

### Problema: Los botones de editar/eliminar no funcionan
**Causa**: El archivo JavaScript no cargó correctamente
**Solución**:
- Abre las DevTools (F12) → Consola
- Verifica que no haya errores de JavaScript
- Comprueba que `app/assets/empleados.js` exista
- Limpia el caché del navegador

### Problema: No puedo eliminar un empleado
**Causa**: Es supervisor de otros empleados
**Solución**:
- Primero, cambia el supervisor de los otros empleados
- Luego intenta eliminar nuevamente
- O simplemente marca el empleado como "inactivo" en lugar de eliminarlo

## Datos de Ejemplo

El script SQL incluye 2 empleados de ejemplo para pruebas:

1. **Marcos Palomo** - Supervisor de Producción
2. **Juan Pérez** - Inspector de Calidad

Puedes eliminar estos datos si lo deseas.

## Próximos Pasos

Después de instalar el módulo:

1. **Agregar empleados reales** de tu empresa
2. **Configurar departamentos** según tu estructura organizacional
3. **Asignar supervisores** para establecer la jerarquía
4. **Integrar con otros módulos** (Producción, Calidad) cuando sea necesario

## Resumen de Cambios en el Sistema

### Archivos Modificados
- `app/views/header.php` - Se agregó el enlace "Empleados" en el menú

### Archivos Nuevos
- 10 archivos de código Python/PHP
- 2 archivos de documentación
- 2 scripts SQL

### Nuevas Funcionalidades
- Gestión completa de empleados
- Filtros y búsqueda avanzada
- Relaciones jerárquicas entre supervisores y subordinados
- Información personal, laboral y bancaria
- Historial de creación/actualización

## Contacto y Soporte

Si encuentras algún problema durante la instalación:

1. Revisa el archivo `MODULO_EMPLEADOS_README.md` para documentación completa
2. Verifica los logs de error en la consola del navegador (F12)
3. Revisa los logs de PHP en `xampp/logs/`
4. Contacta al equipo de desarrollo de CINASA

---

**Última actualización**: 28 de Octubre de 2025
**Versión**: 1.0
**Estado**: Listo para producción
