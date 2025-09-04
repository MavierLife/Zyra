<?php
// Incluir configuración común
require_once 'includes/config.php';

// Definir título de página específico
$pageTitle = 'Nueva venta';
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
    <link rel="stylesheet" href="styles/dashboard/ventas.css">
    <link rel="icon" type="image/png" href="assets/logos/logo.png">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
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
                        
                        <!-- Los productos se cargarán dinámicamente -->
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
                            </div>
                        </div>
                    </div>
                    
                    <div class="cart-footer">
                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>0</span>
                                <span></span>
                                <span><?php echo htmlspecialchars($currencySymbol); ?>0</span>
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

    <!-- Modal de cobro de venta -->
    <div id="saleModal" class="modal">
        <div class="modal-content sale-modal-content">
            <div class="modal-header sale-modal-header">
                <h3>Cobrar Venta</h3>
                <span class="close-modal" onclick="closeSaleModal()">&times;</span>
            </div>
            
            <div class="sale-modal-body">
                <!-- Información del documento -->
                <div class="document-info">
                    <div class="document-type">
                        <label for="documentType">Documento Tributario Electrónico - DTE</label>
                        <select id="documentType" class="document-select">
                            <option value="factura">FACTURA ELECTRONICA</option>
                            <option value="credito">CREDITO FISCAL</option>
                            <option value="nota">NOTA DE CREDITO</option>
                        </select>
                    </div>
                    
                    <!-- Selección de cliente -->
                    <div class="client-selection">
                        <label for="clientSearch">Cliente</label>
                        <div class="client-search-container">
                            <input type="text" id="clientSearch" class="client-search-input" placeholder="Buscar cliente..." autocomplete="off">
                            <div id="clientSuggestions" class="client-suggestions" style="display: none;"></div>
                            <input type="hidden" id="selectedClientId" value="">
                        </div>
                        <div class="selected-client" id="selectedClient" style="display: none;">
                            <span class="client-name" id="selectedClientName">Cliente General</span>
                            <button type="button" class="clear-client-btn" id="clearClientBtn">&times;</button>
                        </div>
                    </div>
                    
                    <div class="document-number">
                        <div class="document-id" id="documentId">N1M001202509040836271</div>
                        <div class="document-total" id="documentTotal">$10.00</div>
                        <div class="document-status">VENTA POR DESPACHO</div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="action-buttons">
                    <button class="action-btn contact-btn" id="contactBtn">
                        <span class="btn-icon">📞</span>
                        Contacto
                    </button>
                    <button class="action-btn credit-btn" id="creditBtn">
                        <span class="btn-icon">💳</span>
                        Credito
                    </button>
                    <button class="action-btn multiple-btn" id="multipleBtn">
                        <span class="btn-icon">📊</span>
                        Multiple
                    </button>
                </div>
                
                <!-- Campos de pago -->
                <div class="payment-fields">
                    <div class="payment-row">
                        <label for="efectivoRecibido">Efectivo Recibido:</label>
                        <input type="number" id="efectivoRecibido" class="payment-input" value="0" step="0.01" min="0">
                    </div>
                    
                    <div class="payment-row">
                        <label for="cambio">Cambio:</label>
                        <input type="number" id="cambio" class="payment-input" value="0" step="0.01" readonly>
                    </div>
                </div>
                
                <!-- Botones de procesamiento -->
                <div class="process-buttons">
                    <button class="process-btn process-btn-primary" id="processBtn">
                        <span class="btn-icon">⚙️</span>
                        Procesar
                    </button>
                    <button class="process-btn process-btn-secondary" id="cancelBtn">
                        <span class="btn-icon">❌</span>
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales desde PHP
        const vendedorId = '<?php echo htmlspecialchars($vendedorId, ENT_QUOTES, 'UTF-8'); ?>';
        const nombreUsuario = '<?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>';
        const nombreComercial = '<?php echo htmlspecialchars($nombreComercial, ENT_QUOTES, 'UTF-8'); ?>';
    </script>
    <link rel="stylesheet" href="styles/dashboard/apertura_caja.css">
    <script src="js/dashboard/dashboard.js"></script>
    <script src="js/dashboard/ventas.js"></script>
    <script src="js/dashboard/apertura_caja.js"></script>
</body>
</html>