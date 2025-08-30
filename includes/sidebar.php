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
                <a href="inventario.php" class="nav-link" data-section="inventario">
                    <img src="assets/icons/inventario.svg" alt="Inventario" class="nav-barcode-icon">
                    <span class="nav-text">Inventario</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-section="empleados">
                    <img src="assets/icons/empleados.svg" alt="Empleados" class="nav-barcode-icon">
                    <span class="nav-text">Empleados</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <button class="logout-btn" onclick="logout()">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">Cerrar sesión</span>
        </button>
    </div>
</aside>