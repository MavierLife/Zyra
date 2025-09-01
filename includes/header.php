<!-- Header superior -->
<header class="main-header">
    <div class="header-left">
        <h1 class="page-title"><?php echo $pageTitle; ?></h1>
    </div>
    <div class="header-right">
        <?php if ($currentPage === 'vender'): ?>
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
        <?php elseif ($currentPage === 'inventario'): ?>
            <button class="btn btn-outline" id="categoriesBtn">
                <img src="assets/icons/categorias.svg" alt="Categorías" class="nav-barcode-icon">
                Categorías
            </button>
            <button class="btn btn-primary" id="createProductBtn">
                <img src="assets/icons/agregar.svg" alt="Agregar" class="nav-barcode-icon">
                Crear producto
            </button>
        <?php elseif ($currentPage === 'proveedores'): ?>
            <button class="btn btn-primary" id="createSupplierBtn" style="background-color: #394C60; color: white !important; width: 160.39px; height: 52px; font-size: 14px; padding: 10px 16px; display: flex; align-items: center; gap: 8px;">
                <img src="assets/icons/agregar.svg" alt="Agregar" style="width: 16px; height: 16px; filter: brightness(0) invert(1);">
                Crear proveedor
            </button>

        <?php endif; ?>
    </div>
</header>

<!-- Menú lateral desplegable de categorías -->
<?php if ($currentPage === 'inventario'): ?>
<div id="categoriesSidebar" class="categories-sidebar">
    <div class="categories-sidebar-header">
        <h3>Categorías</h3>
        <button class="btn-close" id="closeCategoriesBtn">&times;</button>
    </div>
    <div class="categories-sidebar-content">
        <button class="btn btn-outline btn-full-width" id="addCategoryBtn">
            <img src="assets/icons/agregar.svg" alt="Agregar" class="nav-barcode-icon">
            Crear Nueva Categoría
        </button>
        <div class="categories-list" id="categoriesList">
            <!-- Las categorías se cargarán aquí dinámicamente -->
        </div>
    </div>
</div>
<div id="categoriesOverlay" class="sidebar-overlay"></div>
<?php endif; ?>