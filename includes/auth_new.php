<?php
// Sistema de autenticación - Versión simple

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para obtener el usuario actual
function getCurrentUser() {
    if (isLoggedIn()) {
        global $conn;
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

// Función para verificar permisos
function hasPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    $roleHierarchy = [
        'lectura' => 1,
        'produccion' => 2,
        'ventas' => 3,
        'admin' => 4
    ];
    
    $userLevel = $roleHierarchy[$user['rol']] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
    
    return $userLevel >= $requiredLevel;
}

// Función para hacer login
function login($username, $password) {
    global $conn;
    
    $sql = "SELECT * FROM usuarios WHERE (username = ? OR email = ?) AND activo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        return true;
    }
    
    return false;
}

// Función para hacer logout
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Función para requerir login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Función para requerir rol específico
function requireRole($role) {
    requireLogin();
    if (!hasPermission($role)) {
        header('Location: dashboard.php?error=no_permission');
        exit;
    }
}

// Función para redirigir si ya está logueado
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}
?>
