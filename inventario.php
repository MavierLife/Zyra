<?php
// Configurar codificación interna
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['vendedor_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php?message=Debes+iniciar+sesión+para+acceder+al+inventario');
    exit();
}

$vendedorId = $_SESSION['vendedor_id'];
$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';
$razonSocial = $_SESSION['razon_social'] ?? 'Contribuyente';
$nombreComercial = $_SESSION['nombre_comercial'] ?? 'Negocio';
$uuidContribuyente = $_SESSION['uuid_contribuyente'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Zyra POS</title>
    
    <!-- Google Fonts - Nunito Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="styles/dashboard/dashboard.css">
    <link rel="stylesheet" href="styles/dashboard/inventario.css">
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
                    <li class="nav-item">
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
                    <li class="nav-item active">
                        <a href="inventario.php" class="nav-link">
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
                    <h1 class="page-title">Inventario</h1>
                </div>
                <div class="header-right">
                    <button class="btn btn-outline" id="categoriesBtn">
                        <span class="btn-icon">🏷️</span>
                        Categorías
                    </button>
                    <button class="btn btn-primary" id="createProductBtn">
                        <span class="btn-icon">➕</span>
                        Crear producto
                    </button>
                </div>
            </header>
            
            <!-- Sección de inventario -->
            <div class="inventory-section">
                <!-- Panel de controles -->
                <div class="inventory-controls">
                    <div class="search-container">
                        <input type="text" id="inventorySearch" class="search-input" placeholder="Buscar por nombre">
                    </div>
                    
                    <div class="filter-controls">
                        <select id="categoryFilter" class="filter-select">
                            <option value="all">Ver todas las categorías</option>
                            <!-- Las categorías se cargarán dinámicamente -->
                        </select>
                        
                        <button class="btn btn-outline" id="virtualCatalogBtn">
                            <span class="btn-icon">📋</span>
                            Catálogo virtual
                        </button>
                        
                        <button class="btn btn-primary" id="registerPurchasesBtn">
                            <span class="btn-icon">📝</span>
                            Registrar compras
                        </button>
                    </div>
                </div>
                
                <!-- Estadísticas del inventario -->
                <div class="inventory-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalProducts">501</div>
                        <div class="stat-label">Total de referencias</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalValue">$9,787</div>
                        <div class="stat-label">Costo total de inventario</div>
                    </div>
                </div>
                
                <!-- Tabla de productos -->
                <div class="inventory-table-container">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th class="col-product">Producto</th>
                                <th class="col-price">Precio</th>
                                <th class="col-cost">Costo</th>
                                <th class="col-quantity">Cantidad disponible</th>
                                <th class="col-profit">Ganancia</th>
                                <th class="col-actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody">
                            <!-- Los datos se cargarán dinámicamente con JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Menú desplegable para editar producto -->
    <div id="productDropdownMenu" class="dropdown-menu" style="display: none;">
        <div class="dropdown-item" data-action="edit">
            <span class="dropdown-icon">✏️</span>
            <span class="dropdown-text">Editar producto</span>
        </div>
        <div class="dropdown-item" data-action="delete">
            <span class="dropdown-icon">🗑️</span>
            <span class="dropdown-text">Eliminar producto</span>
        </div>
    </div>
    
    <!-- Modal para editar producto -->
    <div id="editProductModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Producto básico</h3>
                <span class="close-modal" id="closeEditModal">&times;</span>
            </div>
            
            <div class="modal-tabs">
                <button class="tab-btn active" data-tab="basic">Producto básico</button>
                <button class="tab-btn" data-tab="variants" disabled>Productos con variantes</button>
                <button class="tab-btn" data-tab="measures" disabled>Productos con medidas</button>
            </div>
            
            <form id="editProductForm" class="edit-product-form">
                <div class="form-section">
                    <h4>Datos del producto</h4>
                    
                    <div class="image-upload-section">
                        <div class="image-upload-area">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">
                                <p>Cargar imagen Recomendamos: Tamaño de 500 x 500 px, formato PNG y peso máximo 2MB.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductCode">Código</label>
                            <input type="text" id="editProductCode" name="productCode" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductName">Nombre del producto *</label>
                            <input type="text" id="editProductName" name="productName" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductStock">Cantidad disponible</label>
                            <input type="number" id="editProductStock" name="productStock" min="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductPrice">Precio de venta *</label>
                            <input type="number" id="editProductPrice" name="productPrice" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductCost">Costo de compra</label>
                            <input type="number" id="editProductCost" name="productCost" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>Información adicional</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductCategory">Categoría</label>
                            <select id="editProductCategory" name="productCategory">
                                <option value="">Seleccionar categoría</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="showInVirtualStore" name="showInVirtualStore">
                            <label for="showInVirtualStore">Mostrar producto en tienda virtual</label>
                            <small>Este producto será visible para tus clientes si compartes tu catálogo</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productDescription">Descripción</label>
                            <textarea id="productDescription" name="productDescription" placeholder="Añadir una descripción ayudará a tus clientes a elegir más fácil"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-delete" id="deleteProductBtn">
                        <span class="btn-icon">🗑️</span>
                        Eliminar producto
                    </button>
                    <button type="submit" class="btn-save">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales desde PHP
        const vendedorId = '<?php echo htmlspecialchars($vendedorId, ENT_QUOTES, 'UTF-8'); ?>';
        const nombreUsuario = '<?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>';
        const nombreComercial = '<?php echo htmlspecialchars($nombreComercial, ENT_QUOTES, 'UTF-8'); ?>';
        const uuidContribuyente = '<?php echo htmlspecialchars($uuidContribuyente, ENT_QUOTES, 'UTF-8'); ?>';
    </script>
    <script src="js/dashboard/inventario.js"></script>
</body>
</html>