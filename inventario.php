<?php
// Incluir configuración común
require_once 'includes/config.php';

// Bloqueo de acceso si no tiene permiso para ver inventario
if (!tienePermiso('Perm_VerInventario')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'PERMISO_DENEGADO';
    exit();
}

// Definir título de página específico
$pageTitle = 'Inventario';
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
    <link rel="stylesheet" href="styles/dashboard/permisos.css">
    <link rel="icon" type="image/png" href="assets/logos/logo.png">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
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
                            <img src="assets/icons/catalogovirtual.svg" alt="Catálogo" class="nav-barcode-icon">
                            Catálogo virtual
                        </button>
                        
                        <button class="btn btn-primary" id="registerPurchasesBtn">
                            <img src="assets/icons/registrarcompra.svg" alt="Registrar" class="nav-barcode-icon">
                            Registrar compras
                        </button>
                    </div>
                </div>
                
                <!-- Estadísticas del inventario -->
                <div class="inventory-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalProducts">0</div>
                        <div class="stat-label">Total de referencias</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalValue"><?php echo htmlspecialchars($currencySymbol); ?>0.00</div>
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
                                <th class="col-special-price">Precio Especial</th>
                                <th class="col-cost">Costo</th>
                                <th class="col-quantity">Existencias</th>
                                <th class="col-profit">Margen de venta</th>
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
            <img src="assets/icons/editar.svg" alt="Editar" class="dropdown-icon">
            <span class="dropdown-text">Editar producto</span>
        </div>
        <div class="dropdown-item" data-action="delete">
            <img src="assets/icons/eliminar.svg" alt="Eliminar" class="dropdown-icon">
            <span class="dropdown-text">Eliminar producto</span>
        </div>
    </div>
    
    <!-- Modal para editar producto -->
    <div id="editProductModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modificar Producto</h3>
                <span class="close-modal" id="closeEditModal">&times;</span>
            </div>
            
            <form id="editProductForm" class="edit-product-form">
                <!-- Sección: Datos Generales -->
                <div class="form-section">
                    <h4 class="section-title">Datos Generales</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductName">Nombre del producto *</label>
                            <input type="text" id="editProductName" name="productName" required>
                        </div>
                    </div>
                    
                    <div class="form-row three-columns">
                        <div class="form-group">
                            <label for="editProductCode">Código de Barras</label>
                            <input type="text" id="editProductCode" name="productCode" readonly>
                        </div>
                        <div class="form-group">
                            <label for="editProductCategory">Categoría</label>
                            <select id="editProductCategory" name="productCategory">
                                <option value="">Seleccionar categoría</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editProductStock">Cantidad</label>
                            <input type="number" id="editProductStock" name="productStock" min="0">
                        </div>
                    </div>
                </div>
                
                <!-- Sección: Precios -->
                <div class="form-section">
                    <h4 class="section-title">Precios</h4>
                    <div class="form-row two-columns">
                        <div class="form-group">
                            <label for="editProductPrice">Precio de venta *</label>
                            <input type="number" id="editProductPrice" name="productPrice" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editProductCost">Costo de compra</label>
                            <input type="number" id="editProductCost" name="productCost" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="form-row two-columns">
                        <div class="form-group">
                            <label for="editProductMinQuantity">Cantidad mínima</label>
                            <input type="number" id="editProductMinQuantity" name="productMinQuantity" min="0" placeholder="Cantidad mínima para precio especial">
                        </div>
                        <div class="form-group">
                            <label for="editProductDiscountPrice">Precio con descuento</label>
                            <input type="number" id="editProductDiscountPrice" name="productDiscountPrice" step="0.01" min="0" placeholder="Precio especial por cantidad">
                        </div>
                    </div>
                    
                    <div class="form-row two-columns">
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="showInVirtualStore" name="showInVirtualStore">
                            <label for="showInVirtualStore">Mostrar en tienda virtual</label>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="editProductFractionalSale" name="productFractionalSale">
                            <label for="editProductFractionalSale">Venta por fracciones</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
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
        
        // Permisos del usuario
        window.canViewInventory = <?php echo tienePermiso('Perm_VerInventario') ? 'true' : 'false'; ?>;
    </script>
    <script src="js/dashboard/permisos.js"></script>
    <script src="js/dashboard/categorias.js"></script>
    <script src="js/dashboard/inventario.js"></script>
</body>
</html>