<?php
/**
 * Controlador de Logout
 */
session_start();

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destruir la sesión
session_destroy();

// Obtener la ruta base correcta
// Desde /app/controllers/auth_logout.php necesitamos subir 2 niveles
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // /CINASA/app/controllers
$base_path = dirname(dirname($script_dir)); // /CINASA

// Redirigir al login
header('Location: ' . $base_path . '/login.php');
exit;
