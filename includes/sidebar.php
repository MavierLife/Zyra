<?php $canViewInventory = function_exists('tienePermiso') ? tienePermiso('Perm_VerInventario') : false; ?>
<?php $canViewEmployees = function_exists('tienePermiso') ? tienePermiso('Perm_VerEmpleados') : false; ?>
<!-- Barra lateral izquierda -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="assets/logos/logo.png" alt="Zyra Logo" class="logo">
            <h2>Zyra</h2>
        </div>
        <div class="user-info">
            <div class="company-name"><?php echo htmlspecialchars($razonSocial); ?></div>
            <div class="business-name"><?php echo htmlspecialchars($nombreComercial); ?></div>
            <div class="user-name"><?php echo htmlspecialchars($nombreUsuario); ?></div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item <?php echo ($currentPage === 'vender') ? 'active' : ''; ?>">
                <a href="index.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/vender.svg" alt="Vender" class="nav-barcode-icon">
                    </span>
                    <span class="nav-text">Vender</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-section="movimientos">
                    <img src="assets/icons/movimientos.svg" alt="Movimientos" class="nav-barcode-icon">
                    <span class="nav-text">Movimientos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-section="estadisticas">
                    <img src="assets/icons/estadisticas.svg" alt="Estadísticas" class="nav-barcode-icon">
                    <span class="nav-text">Estadísticas</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage === 'inventario') ? 'active' : ''; ?>">
                <?php if ($canViewInventory): ?>
                    <a href="inventario.php" class="nav-link" data-section="inventario">
                        <img src="assets/icons/inventario.svg" alt="Inventario" class="nav-barcode-icon">
                        <span class="nav-text">Inventario</span>
                    </a>
                <?php else: ?>
                    <a class="nav-link disabled" data-section="inventario"
                       aria-disabled="true" tabindex="-1"
                       data-tooltip="No tienes acceso a esta sección. Pídele al propietario que dé permisos."
                       onclick="event.preventDefault(); return false;">
                        <img src="assets/icons/inventario.svg" alt="Inventario" class="nav-barcode-icon">
                        <span class="nav-text">Inventario</span>
                    </a>
                <?php endif; ?>
            </li>
            <li class="nav-item <?php echo ($currentPage === 'empleados') ? 'active' : ''; ?>">
                <?php if ($canViewEmployees): ?>
                    <a href="empleados.php" class="nav-link" data-section="empleados">
                        <img src="assets/icons/empleados.svg" alt="Empleados" class="nav-barcode-icon">
                        <span class="nav-text">Empleados</span>
                    </a>
                <?php else: ?>
                    <a class="nav-link disabled" data-section="empleados"
                       aria-disabled="true" tabindex="-1"
                       data-tooltip="No tienes acceso a esta sección. Pídele al propietario que dé permisos."
                       onclick="event.preventDefault(); return false;">
                        <img src="assets/icons/empleados.svg" alt="Empleados" class="nav-barcode-icon">
                        <span class="nav-text">Empleados</span>
                    </a>
                <?php endif; ?>
            </li>
        </ul>
        
        <!-- Sección de Gestión de Contactos -->
        <div class="nav-section contacts-section">
            <h3 class="section-title">Contactos</h3>
            <ul class="nav-menu contacts-menu">
                <li class="nav-item <?php echo ($currentPage === 'clientes') ? 'active' : ''; ?>">
                    <a href="clientes.php" class="nav-link" data-section="clientes">
                        <img src="assets/icons/clientes.svg" alt="Clientes" class="nav-barcode-icon">
                        <span class="nav-text">Clientes</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($currentPage === 'proveedores') ? 'active' : ''; ?>">
                    <a href="proveedores.php" class="nav-link">
                        <img src="assets/icons/proveedores.svg" alt="Proveedores" class="nav-barcode-icon">
                        <span class="nav-text">Proveedores</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <button class="logout-btn" onclick="logout()">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">Cerrar sesión</span>
        </button>
    </div>

    <script>
        // Fallback: asegurar que logout() exista en todas las páginas
        if (typeof window.logout !== 'function') {
            window.logout = function () {
                if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                    window.location.href = 'logout.php';
                }
            };
        }
    </script>
</aside>