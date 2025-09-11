<?php
// Footer compartido para páginas públicas
// Obtener categorías para el footer
$categorias = readRecords('categorias', ['activo' => 1], null, 'nombre ASC');
?>
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/logo/LOGO.png" alt="DT Studio" height="30" class="me-2">
                        <h5 class="fw-bold mb-0">DT Studio</h5>
                    </div>
                    <p class="text-light">Especialistas en productos promocionales de alta calidad. Transformamos tus ideas en realidad.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=61580553625636" target="_blank" class="text-light me-3" title="Síguenos en Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <a href="https://www.instagram.com/dt_studio_2025/" target="_blank" class="text-light me-3" title="Síguenos en Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-light me-3" title="Próximamente en LinkedIn">
                            <i class="fab fa-linkedin fa-lg"></i>
                        </a>
                        <a href="#" class="text-light" title="Próximamente en Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3 text-light">Productos</h6>
                    <ul class="list-unstyled">
                        <li><a href="productos.php" class="text-light text-decoration-none">Todos los Productos</a></li>
                        <?php foreach (array_slice($categorias, 0, 4) as $categoria): ?>
                            <li><a href="productos.php?categoria=<?php echo $categoria['id']; ?>" class="text-light text-decoration-none">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3 text-light">Empresa</h6>
                    <ul class="list-unstyled">
                        <li><a href="contacto.php" class="text-light text-decoration-none">Contacto</a></li>
                        <li><a href="cotizacion.php" class="text-light text-decoration-none">Cotización</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 class="fw-bold mb-3 text-light">Contacto</h6>
                    <ul class="list-unstyled text-light">
                        <li><i class="fas fa-phone me-2"></i>4462129198</li>
                        <li><i class="fas fa-envelope me-2"></i>cotizaciones@dtstudio.com.mx</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light mb-0">&copy; 2024 DT Studio. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="admin/" class="text-light text-decoration-none">Panel de Administración</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/public.js"></script>
</body>
</html>
