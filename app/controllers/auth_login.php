<?php
/**
 * Controlador de Login
 */
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['nombre_usuario']) || !isset($input['contrasena'])) {
        throw new Exception('Usuario y contraseña son requeridos');
    }
    
    $nombre_usuario = trim($input['nombre_usuario']);
    $contrasena = $input['contrasena'];
    
    // Buscar usuario
    $sql = "SELECT id, nombre_usuario, nombre_completo, correo, contrasena_hash, estado, 
                   intentos_fallidos, bloqueado_hasta
            FROM usuarios 
            WHERE nombre_usuario = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre_usuario]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuario o contraseña incorrectos');
    }
    
    // Verificar si está bloqueado
    if ($usuario['estado'] === 'bloqueado') {
        if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
            throw new Exception('Usuario bloqueado. Intenta más tarde.');
        }
        // Desbloquear si ya pasó el tiempo
        $sql = "UPDATE usuarios SET estado = 'activo', intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?";
        $pdo->prepare($sql)->execute([$usuario['id']]);
    }
    
    if ($usuario['estado'] !== 'activo') {
        throw new Exception('Usuario inactivo. Contacta al administrador.');
    }
    
    // Verificar contraseña
    if (!password_verify($contrasena, $usuario['contrasena_hash'])) {
        // Incrementar intentos fallidos
        $intentos = $usuario['intentos_fallidos'] + 1;
        
        if ($intentos >= 5) {
            // Bloquear usuario por 15 minutos
            $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $sql = "UPDATE usuarios SET intentos_fallidos = ?, estado = 'bloqueado', bloqueado_hasta = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$intentos, $bloqueado_hasta, $usuario['id']]);
            throw new Exception('Demasiados intentos fallidos. Usuario bloqueado por 15 minutos.');
        } else {
            $sql = "UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$intentos, $usuario['id']]);
            throw new Exception('Usuario o contraseña incorrectos');
        }
    }
    
    // Login exitoso - Resetear intentos fallidos
    $sql = "UPDATE usuarios SET intentos_fallidos = 0, ultimo_login = NOW() WHERE id = ?";
    $pdo->prepare($sql)->execute([$usuario['id']]);
    
    // Crear sesión
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['username'] = $usuario['nombre_usuario'];
    $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
    $_SESSION['login_time'] = time();
    
    // No cachear permisos aún - se cargarán cuando se necesiten
    
    echo json_encode([
        'success' => true,
        'message' => 'Login exitoso',
        'redirect' => 'dashboard.php',
        'user' => [
            'id' => $usuario['id'],
            'username' => $usuario['nombre_usuario'],
            'nombre_completo' => $usuario['nombre_completo']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
