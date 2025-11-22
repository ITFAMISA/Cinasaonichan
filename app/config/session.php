<?php
// Configuración de sesiones

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
    
    session_start();
}

// Cargar configuración para BASE_PATH
require_once __DIR__ . '/config.php';

// Cargar sistema de autenticación
require_once __DIR__ . '/auth.php';

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && isset($_SESSION['user_id']);
}

// Verificar autenticación - Redirigir a login si no está autenticado
$current_file = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'auth_login.php', 'auth_logout.php'];

// Si no está en una página pública y no está autenticado, redirigir a login
if (!in_array($current_file, $public_pages) && !isAuthenticated()) {
    // Guardar la URL a la que intentaba acceder
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Si es una petición AJAX, retornar JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Sesión expirada. Por favor inicia sesión nuevamente.',
            'redirect' => BASE_PATH . '/login.php'
        ]);
        exit;
    }
    
    // Redirigir usando BASE_PATH
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// Verificar timeout de sesión (30 minutos de inactividad)
if (isAuthenticated()) {
    $timeout = 1800; // 30 minutos en segundos
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Sesión expirada
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['session_expired'] = true;
        
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

