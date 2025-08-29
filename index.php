<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['vendedor_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php?message=Debes+iniciar+sesión+para+acceder+al+dashboard');
    exit();
}

$vendedorId = $_SESSION['vendedor_id'];
$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';
$razonSocial = $_SESSION['razon_social'] ?? 'Contribuyente';
$nombreComercial = $_SESSION['nombre_comercial'] ?? 'Negocio';
$loginTime = $_SESSION['login_time'] ?? time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Zyra POS</title>
    
    <!-- Google Fonts - Nunito Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="styles/dashboard/dashboard.css">
    <link rel="icon" type="image/png" href="assets/logos/logo.png">
</head>
<body>
    <div class="dashboard-container">
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
                    <li class="nav-item active">
                        <a href="#" class="nav-link" data-section="vender">
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
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="inventario">
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
        
        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Header superior -->
            <header class="main-header">
                <div class="header-left">
                    <h1 class="page-title">Nueva venta</h1>
                </div>
                <div class="header-right">
                    <button class="btn btn-secondary" id="openCashBtn">
                        <img src="assets/icons/cajaregistradora.svg" alt="Abrir caja" class="nav-barcode-icon">
                        Abrir caja
                    </button>
                    <button class="btn btn-success" id="newSaleBtn">
                        Nueva venta libre
                    </button>
                    <button class="btn btn-danger" id="newExpenseBtn">
                        Nuevo gasto
                    </button>
                </div>
            </header>
            
            <!-- Sección de venta -->
            <div class="sale-section">
                <!-- Panel de búsqueda y productos -->
                <div class="products-panel">
                    <div class="search-container">
                        <input type="text" id="productSearch" class="search-input" placeholder="Buscar productos...">
                        <button class="barcode-btn" id="barcodeBtn">
                            <span class="barcode-icon">📷</span>
                        </button>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="tabs-container">
                        <div class="tabs">
                            <button class="tab active" data-tab="todos">Todos</button>
                        </div>
                    </div>
                    
                    <!-- Área de productos -->
                    <div class="products-area">
                        <div class="add-product-card">
                            <div class="add-product-icon">➕</div>
                            <div class="add-product-text">Agregar producto</div>
                        </div>
                        
                        <!-- Producto de ejemplo -->
                        <div class="product-card">
                            <div class="product-brand">BenaMax</div>
                            <div class="product-price">$10</div>
                            <div class="product-name">PLUMON ARTLINE</div>
                            <div class="product-stock">35 disponibles</div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel del carrito -->
                <div class="cart-panel">
                    <div class="cart-header">
                        <h3>Productos</h3>
                        <button class="clear-cart-btn">Vaciar canasta</button>
                    </div>
                    
                    <div class="cart-content">
                        <div class="empty-cart">
                            <div class="empty-cart-icon">
                                <img src="assets/utilities/lectorbarras.webp" alt="Lector de código de barras" class="barcode-reader-icon">
                            </div>
                            <div class="empty-cart-text">
                                <h4>Agrega productos rápidamente usando tu lector de código de barras</h4>
                                <p>Si no está en tu inventario, lo buscaremos en nuestra base de datos.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cart-footer">
                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>0</span>
                                <span>Continuar</span>
                                <span>$0</span>
                            </div>
                        </div>
                        <button class="continue-btn" disabled>
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal para agregar producto -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Agregar Nuevo Producto</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="addProductForm" class="add-product-form">
                <div class="form-group">
                    <label for="productName">Nombre del Producto *</label>
                    <input type="text" id="productName" name="productName" required placeholder="Ej: Coca Cola 2.5 Litros">
                </div>
                
                <div class="form-group">
                    <label for="productBarcode">Código de Barras *</label>
                    <input type="text" id="productBarcode" name="productBarcode" required placeholder="Ej: 7478145845855">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="productPrice">Precio de Venta *</label>
                        <input type="number" id="productPrice" name="productPrice" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="productCost">Costo de Compra *</label>
                        <input type="number" id="productCost" name="productCost" step="0.01" min="0" required placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="productStock">Stock Inicial *</label>
                        <input type="number" id="productStock" name="productStock" min="0" required placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="productCategory">Categoría *</label>
                        <select id="productCategory" name="productCategory" required>
                            <option value="">Seleccionar categoría</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales desde PHP
        const vendedorId = '<?php echo htmlspecialchars($vendedorId, ENT_QUOTES, 'UTF-8'); ?>';
        const nombreUsuario = '<?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>';
        const nombreComercial = '<?php echo htmlspecialchars($nombreComercial, ENT_QUOTES, 'UTF-8'); ?>';
    </script>
    <script src="js/dashboard/dashboard.js"></script>
</body>
</html>