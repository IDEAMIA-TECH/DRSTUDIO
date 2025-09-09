<?php
// Incluir sistema de rutas centralizado
require_once 'includes/paths.php';

// Redirigir si ya está logueado
redirectIfLoggedIn();

$error = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #7B3F9F 0%, #5A2D73 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #7B3F9F 0%, #5A2D73 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header img {
            transition: transform 0.3s ease;
        }
        .login-header img:hover {
            transform: scale(1.1);
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #7B3F9F;
            box-shadow: 0 0 0 0.2rem rgba(123, 63, 159, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #7B3F9F 0%, #5A2D73 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 63, 159, 0.4);
        }
        .back-link {
            color: #6c757d !important;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #7B3F9F !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <div class="d-flex justify-content-center mb-3">
                            <img src="../assets/logo/LOGO.png" alt="DT Studio" height="50" style="filter: brightness(0) invert(1);">
                        </div>
                        <h3><i class="fas fa-tachometer-alt me-2"></i>Panel de Administración</h3>
                        <p class="mb-0">DT Studio - Promocionales</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Usuario o Email
                                </label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Contraseña
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="../index.php" class="back-link text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Sitio Web
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
