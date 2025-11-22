<?php
require_once __DIR__ . '/app/config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CINASA</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_PATH; ?>/app/assets/img/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/app/assets/style.css">
    <script>
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
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 min-h-screen">
    <!-- Logo de Fondo -->
    <div style="position: fixed; top: 50%; left: 50%; width: 1200px; height: 1200px; margin-left: -600px; margin-top: -600px; background-image: url('<?php echo BASE_PATH; ?>/app/assets/img/logo.png'); background-size: 60%; background-repeat: no-repeat; background-position: center; background-attachment: fixed; opacity: 0.25; pointer-events: none; z-index: -1;"></div>

    <main class="container-fluid py-6 px-4">
        <div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
            <div style="width: 100%; max-width: 450px;">
                <!-- Logo y Título -->
                <div class="text-center mb-4">
                    <img src="<?php echo BASE_PATH; ?>/app/assets/img/logo.png" alt="CINASA Logo" class="h-20 w-20 mx-auto mb-3" style="height: 80px; width: 80px;">
                    <img src="<?php echo BASE_PATH; ?>/app/assets/img/Cinasa.png" alt="CINASA" class="mx-auto mb-2" style="height: 40px;">
                    <h4 class="text-xl font-bold text-slate-700" style="font-size: 1.25rem; font-weight: 700; color: #334155;">Sistema de Gestión</h4>
                </div>

                <!-- Card de Login -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 text-center">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="alertContainer"></div>
                        
                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">
                                    <i class="fas fa-user text-primary"></i> Usuario
                                </label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required autofocus placeholder="Ingresa tu usuario">
                            </div>
                            
                            <div class="mb-4">
                                <label for="contrasena" class="form-label">
                                    <i class="fas fa-lock text-primary"></i> Contraseña
                                </label>
                                <input type="password" class="form-control" id="contrasena" name="contrasena" required placeholder="Ingresa tu contraseña">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-slate-100 via-blue-50 to-slate-100 text-center py-6 mt-8 shadow-inner">
        <div class="container">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <img src="app/assets/img/logo.png" alt="CINASA Logo" class="h-8 w-8">
                <p class="text-slate-600 mb-0 font-medium">
                    <i class="fas fa-copyright text-blue-600"></i>
                    <?php echo date('Y'); ?> Catálogo Maestro de Clientes - Sistema Empresarial
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Usar BASE_PATH configurado en PHP
        const BASE_PATH = '<?php echo BASE_PATH; ?>';

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                nombre_usuario: document.getElementById('nombre_usuario').value,
                contrasena: document.getElementById('contrasena').value
            };

            try {
                const response = await fetch(BASE_PATH + '/app/controllers/auth_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Inicio de sesión exitoso. Redirigiendo...', 'success');
                    setTimeout(() => {
                        window.location.href = BASE_PATH + '/' + (data.redirect || 'index.php');
                    }, 1000);
                } else {
                    showAlert(data.message || 'Error al iniciar sesión', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error de conexión. Intenta de nuevo.', 'danger');
            }
        });
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
