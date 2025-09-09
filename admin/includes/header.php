<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Verificar autenticación
requireLogin();

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Panel de Administración'; ?> - DR Studio</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>DR Studio Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="productosDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-box me-1"></i>Productos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="productos.php">Listar Productos</a></li>
                            <li><a class="dropdown-item" href="productos_create.php">Crear Producto</a></li>
                            <li><a class="dropdown-item" href="categorias.php">Categorías</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clientes.php">
                            <i class="fas fa-users me-1"></i>Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cotizaciones.php">
                            <i class="fas fa-file-invoice me-1"></i>Cotizaciones
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="contenidoDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-images me-1"></i>Contenido
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="banners.php">Banners</a></li>
                            <li><a class="dropdown-item" href="galeria.php">Galería</a></li>
                            <li><a class="dropdown-item" href="testimonios.php">Testimonios</a></li>
                        </ul>
                    </li>
                    <?php if (hasPermission('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-user-cog me-1"></i>Usuarios
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $currentUser['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-edit me-2"></i>Mi Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : ''; ?>" href="productos.php">
                                <i class="fas fa-box me-2"></i>Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>" href="categorias.php">
                                <i class="fas fa-tags me-2"></i>Categorías
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : ''; ?>" href="clientes.php">
                                <i class="fas fa-users me-2"></i>Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cotizaciones.php' ? 'active' : ''; ?>" href="cotizaciones.php">
                                <i class="fas fa-file-invoice me-2"></i>Cotizaciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'active' : ''; ?>" href="banners.php">
                                <i class="fas fa-image me-2"></i>Banners
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'galeria.php' ? 'active' : ''; ?>" href="galeria.php">
                                <i class="fas fa-images me-2"></i>Galería
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'testimonios.php' ? 'active' : ''; ?>" href="testimonios.php">
                                <i class="fas fa-quote-left me-2"></i>Testimonios
                            </a>
                        </li>
                        <?php if (hasPermission('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                                <i class="fas fa-user-cog me-2"></i>Usuarios
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageTitle ?? 'Panel de Administración'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (isset($pageActions)): ?>
                            <?php echo $pageActions; ?>
                        <?php endif; ?>
                    </div>
                </div>
