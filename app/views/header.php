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
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Logo/Brand -->
                <a class="flex items-center space-x-3 text-white hover:text-blue-100 transition-all duration-300 group" href="<?php echo BASE_PATH; ?>/index.php">
                    <img src="<?php echo BASE_PATH; ?>/app/assets/img/logo.png" alt="CINASA Icon" class="h-12 w-12 group-hover:scale-110 transition-transform duration-300">
                    <img src="<?php echo BASE_PATH; ?>/app/assets/img/Cinasa.png" alt="CINASA" class="h-10 group-hover:scale-105 transition-transform duration-300">
                </a>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-button" class="lg:hidden text-white hover:text-blue-100 focus:outline-none" type="button">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                
                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="<?php echo BASE_PATH; ?>/index.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/productos.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'productos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i>
                        <span>Productos</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/pedidos.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i>
                        <span>Pedidos</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/produccion.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'produccion.php') ? 'active' : ''; ?>">
                        <i class="fas fa-industry"></i>
                        <span>Producción</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/calidad.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'calidad.php' || basename($_SERVER['PHP_SELF']) == 'calidad_pedido.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Calidad</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/tracking_piezas.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking_piezas.php') ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i>
                        <span>Tracking</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/pdf_ordenes.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'pdf_ordenes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-pdf"></i>
                        <span>PDFs</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/empleados.php" class="nav-link-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'empleados.php' || basename($_SERVER['PHP_SELF']) == 'empleados_detalle.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>

                    <!-- Separator -->
                    <div class="h-8 w-px bg-white/20 mx-2"></div>
                    
                    <!-- User Menu -->
                    <div class="relative group">
                        <button class="nav-link-custom flex items-center space-x-2">
                            <div class="bg-white/10 p-1.5 rounded-full">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <span>Admin</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform group-hover:translate-y-0 translate-y-2">
                            <div class="py-2">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <i class="fas fa-user-circle mr-2"></i>Mi Perfil
                                </a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <i class="fas fa-cog mr-2"></i>Configuración
                                </a>
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden lg:hidden border-t border-white/20">
                <div class="px-4 py-3 space-y-1">
                    <a href="<?php echo BASE_PATH; ?>/index.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/productos.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'productos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i>
                        <span>Productos</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/pedidos.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i>
                        <span>Pedidos</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/produccion.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'produccion.php') ? 'active' : ''; ?>">
                        <i class="fas fa-industry"></i>
                        <span>Producción</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/calidad.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'calidad.php' || basename($_SERVER['PHP_SELF']) == 'calidad_pedido.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Calidad</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/tracking_piezas.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tracking_piezas.php') ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i>
                        <span>Tracking</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/pdf_ordenes.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'pdf_ordenes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-pdf"></i>
                        <span>PDFs</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/empleados.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'empleados.php' || basename($_SERVER['PHP_SELF']) == 'empleados_detalle.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>

                    <div class="border-t border-white/20 my-2"></div>
                    
                    <a href="#" class="mobile-nav-link">
                        <i class="fas fa-user-circle"></i>
                        <span>Mi Perfil</span>
                    </a>
                    <a href="#" class="mobile-nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                    <a href="#" class="mobile-nav-link text-red-300 hover:bg-red-600/20">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <style>
        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            color: white;
            border-radius: 0.5rem;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link-custom:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }
        
        .nav-link-custom.active {
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
