<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Catálogo de Clientes'; ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_PATH; ?>/app/assets/img/logo.png">
    <!-- Tailwind CDN - Solo para desarrollo, usar build para producción -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/app/assets/style.css?v=<?php echo time(); ?>">
    <script>
        // Suprimir warning de Tailwind CDN en consola
        if (typeof console !== 'undefined') {
            const originalWarn = console.warn;
            console.warn = function(...args) {
                if (args[0] && args[0].includes && args[0].includes('cdn.tailwindcss.com should not be used in production')) {
                    return;
                }
                originalWarn.apply(console, args);
            };
        }
        
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#64748b',
                    }
                }
            }
        }
    </script>
    <script>
        // Base URL para las peticiones AJAX - DEBUG
        const BASE_URL = '<?php echo BASE_PATH; ?>';
        console.log('BASE_URL configurado como:', BASE_URL);
    </script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 min-h-screen">
    <!-- Modern Navbar with Glass Effect -->
    <nav class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 shadow-lg backdrop-blur-sm sticky top-0 z-50">
        <div class="container-fluid">
            <!-- First Row: Logo and Mobile Menu Button -->
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Logo/Brand -->
                <a class="flex items-center space-x-2 text-white hover:text-blue-100 transition-all duration-300 group" href="<?php echo BASE_PATH; ?>/dashboard.php">
                    <img src="<?php echo BASE_PATH; ?>/app/assets/img/logo.png" alt="CINASA Icon" class="h-10 w-10 group-hover:scale-110 transition-transform duration-300">
                    <img src="<?php echo BASE_PATH; ?>/app/assets/img/Cinasa.png" alt="CINASA" class="h-8 group-hover:scale-105 transition-transform duration-300">
                </a>

                <!-- Mobile menu button -->
                <button id="mobile-menu-button" class="lg:hidden text-white hover:text-blue-100 focus:outline-none" type="button">
                    <i class="fas fa-bars text-2xl"></i>
                </button>

                <!-- User Info on Desktop (Right side) -->
                <div class="hidden lg:flex items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1 rounded-lg bg-white/10">
                        <i class="fas fa-user text-sm text-white/80"></i>
                        <span class="text-sm text-white font-medium"><?php echo auth()->getUser()['nombre_usuario'] ?? 'Admin'; ?></span>
                    </div>
                    <a href="<?php echo BASE_PATH; ?>/app/controllers/auth_logout.php" class="text-white hover:bg-white/20 px-3 py-2 rounded text-sm transition-colors" title="Cerrar sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Second Row: Navigation Links -->
            <div class="hidden lg:block border-t border-white/20">
                <div class="px-4 py-2 flex flex-wrap gap-1 items-center">
                    <?php if (hasModuleAccess('clientes')): ?>
                    <a href="<?php echo BASE_PATH; ?>/index.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('productos')): ?>
                    <a href="<?php echo BASE_PATH; ?>/productos.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'productos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i>
                        <span>Productos</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('procesos')): ?>
                    <a href="<?php echo BASE_PATH; ?>/procesos.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'procesos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Procesos</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('estaciones')): ?>
                    <a href="<?php echo BASE_PATH; ?>/estaciones.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'estaciones.php' || basename($_SERVER['PHP_SELF']) == 'dashboard_taller.php') ? 'active' : ''; ?>">
                        <i class="fas fa-warehouse"></i>
                        <span>Estaciones</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('estaciones')): ?>
                    <a href="<?php echo BASE_PATH; ?>/admin_estacion_procesos.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_estacion_procesos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-link"></i>
                        <span>Procesos-Máquinas</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('pedidos')): ?>
                    <a href="<?php echo BASE_PATH; ?>/pedidos.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i>
                        <span>Pedidos</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('produccion')): ?>
                    <a href="<?php echo BASE_PATH; ?>/produccion.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'produccion.php') ? 'active' : ''; ?>">
                        <i class="fas fa-industry"></i>
                        <span>Producción</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('calidad')): ?>
                    <a href="<?php echo BASE_PATH; ?>/calidad.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'calidad.php' || basename($_SERVER['PHP_SELF']) == 'calidad_pedido.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Calidad</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('tracking')): ?>
                    <a href="<?php echo BASE_PATH; ?>/tracking_piezas.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking_piezas.php') ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i>
                        <span>Tracking</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('tracking_dashboard')): ?>
                    <a href="<?php echo BASE_PATH; ?>/tracking_dashboard.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking_dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Tracking Dashboard</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('pdfs')): ?>
                    <a href="<?php echo BASE_PATH; ?>/pdf_ordenes.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'pdf_ordenes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-pdf"></i>
                        <span>PDFs</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('empleados')): ?>
                    <a href="<?php echo BASE_PATH; ?>/empleados.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'empleados.php' || basename($_SERVER['PHP_SELF']) == 'empleados_detalle.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>
                    <?php endif; ?>

                    <?php
                    $stmt = $pdo->prepare("SELECT GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles FROM usuarios u
                                           LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                                           LEFT JOIN roles r ON ur.rol_id = r.id
                                           WHERE u.id = ?
                                           GROUP BY u.id");
                    $stmt->execute([$_SESSION['user_id']]);
                    $usuario = $stmt->fetch();
                    $rolesUsuario = $usuario ? strtolower($usuario['roles'] ?? '') : '';
                    $esGerente = !empty($rolesUsuario) && (strpos($rolesUsuario, 'gerente') !== false || strpos($rolesUsuario, 'administrador') !== false || strpos($rolesUsuario, 'admin') !== false);
                    ?>
                    <?php if ($esGerente): ?>
                    <a href="<?php echo BASE_PATH; ?>/solicitudes_modificacion.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'solicitudes_modificacion.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Solicitudes</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('usuarios') || hasModuleAccess('roles')): ?>
                    <a href="<?php echo BASE_PATH; ?>/admin.php" class="nav-link-compact <?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Administración</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden lg:hidden border-t border-white/20">
                <div class="px-4 py-3 space-y-1">
                    <?php if (hasModuleAccess('clientes')): ?>
                    <a href="<?php echo BASE_PATH; ?>/index.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('productos')): ?>
                    <a href="<?php echo BASE_PATH; ?>/productos.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'productos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i>
                        <span>Productos</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('procesos')): ?>
                    <a href="<?php echo BASE_PATH; ?>/procesos.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'procesos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Procesos</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('estaciones')): ?>
                    <a href="<?php echo BASE_PATH; ?>/estaciones.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'estaciones.php' || basename($_SERVER['PHP_SELF']) == 'dashboard_taller.php') ? 'active' : ''; ?>">
                        <i class="fas fa-warehouse"></i>
                        <span>Estaciones</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('pedidos')): ?>
                    <a href="<?php echo BASE_PATH; ?>/pedidos.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i>
                        <span>Pedidos</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('produccion')): ?>
                    <a href="<?php echo BASE_PATH; ?>/produccion.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'produccion.php') ? 'active' : ''; ?>">
                        <i class="fas fa-industry"></i>
                        <span>Producción</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('calidad')): ?>
                    <a href="<?php echo BASE_PATH; ?>/calidad.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'calidad.php' || basename($_SERVER['PHP_SELF']) == 'calidad_pedido.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Calidad</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('tracking')): ?>
                    <a href="<?php echo BASE_PATH; ?>/tracking_piezas.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking_piezas.php') ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i>
                        <span>Tracking</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('tracking_dashboard')): ?>
                    <a href="<?php echo BASE_PATH; ?>/tracking_dashboard.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking_dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Tracking Dashboard</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('pdfs')): ?>
                    <a href="<?php echo BASE_PATH; ?>/pdf_ordenes.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'pdf_ordenes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-pdf"></i>
                        <span>PDFs</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('empleados')): ?>
                    <a href="<?php echo BASE_PATH; ?>/empleados.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'empleados.php' || basename($_SERVER['PHP_SELF']) == 'empleados_detalle.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($esGerente): ?>
                    <a href="<?php echo BASE_PATH; ?>/solicitudes_modificacion.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'solicitudes_modificacion.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Solicitudes</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasModuleAccess('usuarios') || hasModuleAccess('roles')): ?>
                    <a href="<?php echo BASE_PATH; ?>/admin.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Administración</span>
                    </a>
                    <?php endif; ?>

                    <div class="border-t border-white/20 my-2"></div>
                    
                    <a href="#" class="mobile-nav-link">
                        <i class="fas fa-user-circle"></i>
                        <span>Mi Perfil</span>
                    </a>
                    <a href="#" class="mobile-nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/app/controllers/auth_logout.php" class="mobile-nav-link text-red-300 hover:bg-red-600/20">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <style>
        .nav-link-compact {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            color: white;
            border-radius: 0.375rem;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .nav-link-compact i {
            font-size: 0.875rem;
        }

        .nav-link-compact:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .nav-link-compact.active {
            background-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: white;
            border-radius: 0.5rem;
            transition: all 0.3s;
            font-weight: 500;
        }

        .mobile-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .mobile-nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
    <main class="container-fluid py-6 px-4">
