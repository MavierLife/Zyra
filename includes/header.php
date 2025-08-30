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
        <?php endif; ?>
    </div>
</header>