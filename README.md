# 🎨 Cinasa Sistema - Rediseño UI Corporativo v2.0.0

Sistema de gestión empresarial con interfaz corporativa moderna, mantenible y 100% funcional.

![Status](https://img.shields.io/badge/status-production-green)
![Version](https://img.shields.io/badge/version-2.0.0-blue)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.4-38bdf8)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479a1)

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Compilar CSS](#-compilar-css)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Documentación](#-documentación)
- [Design Tokens](#-design-tokens)
- [Componentes](#-componentes)
- [Testing](#-testing)
- [Mantenimiento](#-mantenimiento)
- [Changelog](#-changelog)

---

## ✨ Características

### Frontend
- ✅ **TailwindCSS** compilado y minificado para producción
- ✅ **Design Tokens** centralizados (colores, tipografía, espaciado)
- ✅ **Componentes Reutilizables** (10+ componentes)
- ✅ **Animaciones Sutiles** (150-250ms, cubic-bezier easing)
- ✅ **Glass Morphism** en cards y modales
- ✅ **Responsive Design** (mobile-first)
- ✅ **Accesibilidad WCAG AA**
- ✅ **Google Fonts** (Inter, Poppins)
- ✅ **Font Awesome 6.4** icons

### Backend
- ✅ **PHP 8.0+** con arquitectura MVC
- ✅ **MySQL 8.0+** con PDO
- ✅ **Sistema de Autenticación** y permisos por rol
- ✅ **CRUD Completo** para todos los módulos
- ✅ **Exportación PDF/CSV**
- ✅ **APIs RESTful** con JSON

### Módulos Funcionales
- 📊 Dashboard con estadísticas
- 👥 Gestión de Clientes
- 📦 Catálogo de Productos
- 📝 Gestión de Pedidos
- 🏭 Producción
- ✅ Control de Calidad
- 📍 Tracking de Piezas
- 🎯 Tracking Dashboard (Drag & Drop)
- 👔 Gestión de Empleados
- 📄 Generación de PDFs
- ⚙️ Administración (Usuarios, Roles, Permisos)

---

## 🛠️ Tecnologías

| Categoría | Tecnología | Versión |
|-----------|-----------|---------|
| **Backend** | PHP | 8.0+ |
| | MySQL | 8.0+ |
| **Frontend** | TailwindCSS | 3.4.0 |
| | Bootstrap | 5.3.0 |
| | Font Awesome | 6.4.0 |
| **Fonts** | Inter | Variable |
| | Poppins | 400-800 |
| **Build Tools** | npm | 8.0+ |
| | PostCSS | 8.4+ |
| | Autoprefixer | 10.4+ |

---

## 📦 Requisitos

### Servidor
- PHP >= 8.0
- MySQL >= 8.0
- Apache/Nginx con mod_rewrite
- Composer (opcional)

### Desarrollo
- Node.js >= 16.0
- npm >= 8.0

---

## 🚀 Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/cinasa-sistema.git
cd cinasa-sistema
```

### 2. Configurar Base de Datos

```bash
# Importar schema
mysql -u root -p < database/schema.sql

# Importar datos de ejemplo (opcional)
mysql -u root -p < database/seed.sql
```

### 3. Configurar Conexión a BD

Copiar y editar archivo de configuración:

```bash
cp app/config/database.example.php app/config/database.php
```

Editar `app/config/database.php`:

```php
<?php
$host = 'localhost';
$dbname = 'cinasa_db';
$username = 'root';
$password = 'tu_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
```

### 4. Instalar Dependencias npm

```bash
npm install
```

### 5. Compilar CSS

```bash
# Desarrollo (watch mode)
npm run dev

# Producción (minificado)
npm run build
```

### 6. Configurar Servidor Web

#### Apache (.htaccess)

El proyecto incluye `.htaccess` para reescritura de URLs.

#### Nginx

```nginx
server {
    listen 80;
    server_name cinasa.local;
    root /path/to/cinasa-sistema;
    index index.php dashboard.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 7. Acceder al Sistema

```
http://localhost/cinasa-sistema/login.php
```

**Credenciales por defecto**:
- Usuario: `admin`
- Contraseña: `admin123`

---

## 🎨 Compilar CSS

### Scripts Disponibles

```json
{
  "dev": "Modo desarrollo con watch (recompila automáticamente)",
  "build": "Compilar para producción (minificado)"
}
```

### Comandos

```bash
# Desarrollo - Watch mode (detecta cambios)
npm run dev

# Producción - Build minificado
npm run build
```

### Archivos Generados

```
app/assets/
├── input.css           # Fuente (editable)
└── dist/
    └── output.css      # Compilado (NO editar)
```

⚠️ **IMPORTANTE**: NO editar `output.css` directamente. Todos los cambios deben hacerse en `input.css` y luego compilar.

---

## 📁 Estructura del Proyecto

```
/Cinasaonichan/
├── app/
│   ├── assets/
│   │   ├── img/                      # Imágenes
│   │   ├── input.css                 # CSS fuente (Tailwind)
│   │   ├── dist/
│   │   │   └── output.css            # CSS compilado
│   │   ├── style.css                 # CSS legacy (compatibilidad)
│   │   └── *.js                      # JavaScript por módulo
│   ├── config/
│   │   ├── config.php                # Configuración global
│   │   ├── database.php              # Conexión BD
│   │   ├── session.php               # Gestión sesiones
│   │   └── auth.php                  # Autenticación
│   ├── controllers/
│   │   └── *.php                     # Controladores API (115 archivos)
│   ├── models/
│   │   └── *.php                     # Modelos de datos (13 archivos)
│   └── views/
│       ├── header.php                # Header global
│       ├── footer.php                # Footer global
│       └── *.php                     # Templates reutilizables
├── database/
│   ├── schema.sql                    # Schema de BD
│   └── seed.sql                      # Datos de ejemplo
├── vendor/                           # Dependencias Composer
├── node_modules/                     # Dependencias npm
├── *.php                             # Páginas principales (27 archivos)
├── package.json                      # Dependencias npm
├── tailwind.config.js                # Configuración Tailwind
├── postcss.config.js                 # Configuración PostCSS
├── .htaccess                         # Configuración Apache
├── README.md                         # Este archivo
├── UI-CHANGES.md                     # Documentación de cambios UI
├── COMPONENT-GUIDE.md                # Guía de componentes
└── TESTING-GUIDE.md                  # Guía de testing
```

---

## 📚 Documentación

### Documentos Disponibles

| Archivo | Descripción |
|---------|-------------|
| [`README.md`](README.md) | Guía principal (este documento) |
| [`UI-CHANGES.md`](UI-CHANGES.md) | Cambios del rediseño, design tokens, componentes |
| [`COMPONENT-GUIDE.md`](COMPONENT-GUIDE.md) | Guía práctica de componentes con ejemplos |
| [`TESTING-GUIDE.md`](TESTING-GUIDE.md) | Lista de validación y tests visuales |

---

## 🎨 Design Tokens

### Colores Corporativos

```css
/* Primarios */
--color-primary: #2563eb          /* Azul corporativo */
--color-secondary: #64748b        /* Gris neutro */

/* Semánticos */
--color-success: #10b981          /* Verde éxito */
--color-danger: #ef4444           /* Rojo peligro */
--color-warning: #f59e0b          /* Naranja advertencia */
--color-info: #06b6d4             /* Cyan información */
--color-accent: #f97316           /* Naranja acento */
```

### Tipografía

```css
--font-sans: 'Inter', 'Roboto', 'Poppins', system-ui, sans-serif;
--font-heading: 'Poppins', 'Inter', system-ui, sans-serif;
```

### Espaciado

```css
--spacing-xs: 4px
--spacing-sm: 8px
--spacing-md: 16px
--spacing-lg: 24px
--spacing-xl: 32px
--spacing-2xl: 48px
```

### Border Radius

```css
--radius-sm: 6px
--radius-md: 8px
--radius-lg: 12px
--radius-xl: 16px
--radius-2xl: 24px
--radius-full: 9999px
```

---

## 🧩 Componentes

### Cards

```html
<div class="card">
    <div class="card-header">
        <h4>Título</h4>
    </div>
    <div class="card-body">
        Contenido
    </div>
</div>
```

### Botones

```html
<button class="btn btn-primary">Primario</button>
<button class="btn btn-success">Éxito</button>
<button class="btn btn-danger">Peligro</button>
```

### Badges

```html
<span class="badge badge-activo">Activo</span>
<span class="badge badge-success">Éxito</span>
```

### Tablas

```html
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th class="sortable">Columna</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Dato</td>
            </tr>
        </tbody>
    </table>
</div>
```

**Ver más**: [`COMPONENT-GUIDE.md`](COMPONENT-GUIDE.md)

---

## ✅ Testing

### Checklist de Validación

- [ ] Login/Logout funciona
- [ ] CRUD de todos los módulos funciona
- [ ] Filtros y búsquedas funcionan
- [ ] Paginación funciona
- [ ] Modales se abren/cierran
- [ ] Drag & drop en Tracking Dashboard funciona
- [ ] Exportación PDF/CSV funciona
- [ ] No hay errores en consola
- [ ] Responsive funciona en móvil

**Ver guía completa**: [`TESTING-GUIDE.md`](TESTING-GUIDE.md)

---

## 🔧 Mantenimiento

### Añadir Nuevo Color

1. Editar `app/assets/input.css`:

```css
:root {
    --color-mi-nuevo-color: #hexcode;
}
```

2. Editar `tailwind.config.js`:

```js
theme: {
    extend: {
        colors: {
            'mi-nuevo-color': '#hexcode'
        }
    }
}
```

3. Compilar:

```bash
npm run build
```

### Crear Nuevo Componente

1. Añadir en `app/assets/input.css` dentro de `@layer components`:

```css
@layer components {
    .mi-componente {
        @apply bg-primary-600 text-white rounded-lg px-4 py-2;
    }
}
```

2. Compilar:

```bash
npm run build
```

3. Usar en PHP:

```html
<div class="mi-componente">Contenido</div>
```

### Modificar Animaciones

```css
/* En input.css */
.mi-elemento {
    @apply transition-all;
    transition-duration: 300ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
```

---

## 📝 Changelog

### [2.0.0] - 2025-11-22

#### Añadido ✨
- Sistema de design tokens completo
- 10+ componentes reutilizables
- TailwindCSS compilado con PostCSS
- Google Fonts (Inter, Poppins)
- Animaciones y microinteracciones sutiles
- Accesibilidad WCAG AA
- Responsive design mobile-first
- Documentación completa (UI-CHANGES, COMPONENT-GUIDE, TESTING-GUIDE)

#### Modificado 🔧
- `app/views/header.php`: Referencias a CSS compilado
- `app/assets/style.css`: Preservado para compatibilidad legacy

#### Removido ❌
- Tailwind CDN (reemplazado por compilado)
- Estilos inline duplicados

### [1.0.0] - 2024-XX-XX
- Release inicial del sistema

---

## 🤝 Contribución

### Reglas de Contribución

1. **NO modificar**:
   - IDs/clases usadas por JavaScript
   - Endpoints de controladores
   - Estructura DOM de tracking_dashboard.php
   - Lógica de backend (PHP/MySQL)

2. **SÍ permitido**:
   - Mejoras visuales (colores, sombras, animaciones)
   - Nuevos componentes CSS
   - Optimizaciones de performance
   - Mejoras de accesibilidad
   - Documentación

### Workflow

1. Crear branch desde `main`
2. Hacer cambios
3. Compilar CSS: `npm run build`
4. Probar según [`TESTING-GUIDE.md`](TESTING-GUIDE.md)
5. Commit con mensaje descriptivo
6. Push y crear Pull Request

---

## 📞 Soporte

### Problemas Comunes

**CSS no se actualiza**:
```bash
# Recompilar
npm run build

# Limpiar caché navegador
Ctrl + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)
```

**Tailwind no compila**:
```bash
# Reinstalar dependencias
rm -rf node_modules package-lock.json
npm install
npm run build
```

**Funcionalidad JS rota**:
- Verificar consola del navegador (F12)
- Revisar que no se eliminaron IDs/clases
- Comparar con versión anterior

---

## 📄 Licencia

MIT License - Ver `LICENSE` para más detalles.

---

## 👥 Créditos

- **Desarrollo**: Equipo Cinasa
- **Rediseño UI**: Claude AI Agent
- **Design System**: TailwindCSS
- **Icons**: Font Awesome

---

**Fin del README** | Versión 2.0.0 | Actualizado: 2025-11-22
