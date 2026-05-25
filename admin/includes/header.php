<?php
require_once __DIR__ . '/paths.php';
requireLogin();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

function adminNavActive(array $pages): string {
    global $currentPage;
    return in_array($currentPage, $pages, true) ? 'active' : '';
}

function adminNavGroupOpen(array $pages): string {
    return adminNavActive($pages) ? 'show' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Panel de Administración'); ?> - DT Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="css/admin.css?v=2" rel="stylesheet">
</head>
<body class="admin-app">
    <div class="admin-sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <aside class="admin-sidebar" id="adminSidebar" aria-label="Menú principal">
        <div class="admin-sidebar-header">
            <a href="dashboard.php" class="admin-brand">
                <span class="admin-brand-icon"><i class="fas fa-layer-group"></i></span>
                <span class="admin-brand-text">
                    <strong>DT Studio</strong>
                    <small>Administración</small>
                </span>
            </a>
            <button type="button" class="btn btn-link admin-sidebar-close d-lg-none" id="sidebarClose" aria-label="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="admin-sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['dashboard.php']); ?>" href="dashboard.php">
                        <i class="fas fa-home"></i><span>Dashboard</span>
                    </a>
                </li>

                <li class="admin-nav-label">Ventas</li>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['cotizaciones.php', 'cotizaciones_view.php', 'cotizaciones_create.php', 'cotizaciones_edit.php']); ?>" href="cotizaciones.php">
                        <i class="fas fa-file-invoice"></i><span>Cotizaciones</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['clientes.php', 'clientes_create.php', 'clientes_edit.php']); ?>" href="clientes.php">
                        <i class="fas fa-users"></i><span>Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['cotizador_dtf.php']); ?>" href="cotizador_dtf.php">
                        <i class="fas fa-calculator"></i><span>Cotizador DTF</span>
                    </a>
                </li>

                <li class="admin-nav-label">Finanzas</li>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['gastos.php', 'gastos_create.php', 'gastos_edit.php']); ?>" href="gastos.php">
                        <i class="fas fa-receipt"></i><span>Gastos</span>
                    </a>
                </li>
                <?php if (hasPermission('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['sueldos.php', 'sueldos_create.php', 'empleados.php']); ?>" href="sueldos.php">
                        <i class="fas fa-money-check-alt"></i><span>Sueldos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['empleados.php']); ?>" href="empleados.php">
                        <i class="fas fa-id-badge"></i><span>Empleados</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['reportes.php', 'reportes_ganancias.php', 'exportar_ganancias.php', 'exportar_cotizaciones.php', 'exportar_gastos.php']); ?>" href="reportes.php">
                        <i class="fas fa-chart-line"></i><span>Reportes</span>
                    </a>
                </li>

                <li class="admin-nav-label">Catálogo</li>
                <li class="nav-item">
                    <button class="nav-link admin-nav-toggle <?php echo adminNavActive(['productos.php', 'productos_create.php', 'productos_edit.php', 'categorias.php']) ? 'active' : ''; ?>"
                            type="button" data-bs-toggle="collapse" data-bs-target="#navProductos" aria-expanded="<?php echo adminNavGroupOpen(['productos.php', 'productos_create.php', 'productos_edit.php', 'categorias.php']) ? 'true' : 'false'; ?>">
                        <i class="fas fa-box"></i><span>Productos</span>
                        <i class="fas fa-chevron-down admin-nav-chevron"></i>
                    </button>
                    <div class="collapse <?php echo adminNavGroupOpen(['productos.php', 'productos_create.php', 'productos_edit.php', 'categorias.php']); ?>" id="navProductos">
                        <ul class="nav flex-column admin-nav-sub">
                            <li class="nav-item">
                                <a class="nav-link <?php echo adminNavActive(['productos.php', 'productos_edit.php']); ?>" href="productos.php">Listado</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo adminNavActive(['productos_create.php']); ?>" href="productos_create.php">Nuevo producto</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo adminNavActive(['categorias.php']); ?>" href="categorias.php">Categorías</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="admin-nav-label">Contenido web</li>
                <li class="nav-item">
                    <button class="nav-link admin-nav-toggle <?php echo adminNavActive(['banners.php', 'galeria.php', 'testimonios.php']) ? 'active' : ''; ?>"
                            type="button" data-bs-toggle="collapse" data-bs-target="#navContenido" aria-expanded="<?php echo adminNavGroupOpen(['banners.php', 'galeria.php', 'testimonios.php']) ? 'true' : 'false'; ?>">
                        <i class="fas fa-images"></i><span>Sitio público</span>
                        <i class="fas fa-chevron-down admin-nav-chevron"></i>
                    </button>
                    <div class="collapse <?php echo adminNavGroupOpen(['banners.php', 'galeria.php', 'testimonios.php']); ?>" id="navContenido">
                        <ul class="nav flex-column admin-nav-sub">
                            <li class="nav-item">
                                <a class="nav-link <?php echo adminNavActive(['banners.php']); ?>" href="banners.php">Banners</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo adminNavActive(['galeria.php']); ?>" href="galeria.php">Galería</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo adminNavActive(['testimonios.php']); ?>" href="testimonios.php">Testimonios</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <?php if (hasPermission('admin')): ?>
                <li class="admin-nav-label">Sistema</li>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['usuarios.php']); ?>" href="usuarios.php">
                        <i class="fas fa-user-cog"></i><span>Usuarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo adminNavActive(['configuracion.php']); ?>" href="configuracion.php">
                        <i class="fas fa-cog"></i><span>Configuración</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="../index.php" class="admin-sidebar-link" target="_blank" rel="noopener">
                <i class="fas fa-external-link-alt"></i><span>Ver sitio web</span>
            </a>
            <div class="admin-user-card">
                <div class="admin-user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="admin-user-info">
                    <strong><?php echo htmlspecialchars($currentUser['username'] ?? 'Usuario'); ?></strong>
                    <a href="profile.php">Mi perfil</a>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-light btn-sm w-100 admin-logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión
            </a>
        </div>
    </aside>

    <div class="admin-content">
        <header class="admin-topbar d-lg-none">
            <button type="button" class="btn admin-menu-btn" id="sidebarOpen" aria-label="Abrir menú">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="admin-topbar-title"><?php echo htmlspecialchars($pageTitle ?? 'Panel'); ?></h1>
        </header>

        <main class="admin-main">
            <?php if (isset($pageActions)): ?>
            <div class="admin-page-actions-mobile d-lg-none mb-3">
                <?php echo $pageActions; ?>
            </div>
            <?php endif; ?>
            <div class="admin-page-header d-none d-lg-flex">
                <h1 class="admin-page-title"><?php echo htmlspecialchars($pageTitle ?? 'Panel de Administración'); ?></h1>
                <?php if (isset($pageActions)): ?>
                <div class="admin-page-actions"><?php echo $pageActions; ?></div>
                <?php endif; ?>
            </div>
