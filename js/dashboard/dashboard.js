// Dashboard JavaScript - Funcionalidades básicas del frontend

// Variables globales
let currentSection = 'vender';
let cart = [];
let products = [];
let availableCategories = [];
let currentVendedorId = null;
let currentContribuyente = null;
let currencySymbol = '$'; // Símbolo de moneda dinámico

// Inicialización cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    initializeVendedorData();
    initializeEventListeners();
    updateCartDisplay();
    loadProducts();
});

// Configurar event listeners
function initializeEventListeners() {
    // Navegación de la barra lateral
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', handleNavigation);
    });
    
    // Búsqueda de productos
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', handleProductSearch);
    }
    
    // Botón de código de barras
    const barcodeBtn = document.getElementById('barcodeBtn');
    if (barcodeBtn) {
        barcodeBtn.addEventListener('click', handleBarcodeScanner);
    }
    
    // Tabs de categorías
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', handleTabChange);
    });
    
    // Botones del header
    const openCashBtn = document.getElementById('openCashBtn');
    const newSaleBtn = document.getElementById('newSaleBtn');
    const newExpenseBtn = document.getElementById('newExpenseBtn');
    
    if (openCashBtn) openCashBtn.addEventListener('click', handleOpenCash);
    if (newSaleBtn) newSaleBtn.addEventListener('click', handleNewSale);
    if (newExpenseBtn) newExpenseBtn.addEventListener('click', handleNewExpense);
    
    // Botón de vaciar carrito
    const clearCartBtn = document.querySelector('.clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', handleClearCart);
    }
    
    // Botón continuar
    const continueBtn = document.querySelector('.continue-btn');
    if (continueBtn) {
        continueBtn.addEventListener('click', handleContinue);
    }
    
    // Event listeners para el modal de agregar producto
    const modal = document.getElementById('addProductModal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.btn-cancel');
    const addProductForm = document.getElementById('addProductForm');
    
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeAddProductModal);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeAddProductModal);
    }
    
    if (addProductForm) {
        addProductForm.addEventListener('submit', handleAddProductSubmit);
    }
    
    // Cerrar modal al hacer clic fuera de él
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeAddProductModal();
            }
        });
    }
    
    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('addProductModal');
            if (modal && modal.style.display === 'block') {
                closeAddProductModal();
            }
        }
    });
}

// Manejar navegación de la barra lateral
function handleNavigation(event) {
    event.preventDefault();
    
    // Obtener la sección
    const section = event.currentTarget.dataset.section;
    
    // Si es inventario, redirigir a la página específica
    if (section === 'inventario') {
        window.location.href = 'inventario.php';
        return;
    }
    
    // Remover clase active de todos los elementos
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Agregar clase active al elemento clickeado
    const navItem = event.currentTarget.closest('.nav-item');
    navItem.classList.add('active');
    
    currentSection = section;
    
    // Actualizar título de la página
    updatePageTitle(section);
    
    // Mostrar mensaje temporal (placeholder)
    showTemporaryMessage(`Sección: ${section}`);
}

// Actualizar título de la página según la sección
function updatePageTitle(section) {
    const pageTitle = document.querySelector('.page-title');
    const titles = {
        'vender': 'Nueva venta',
        'movimientos': 'Movimientos',
        'estadisticas': 'Estadísticas',
        'inventario': 'Inventario',
        'empleados': 'Empleados'
    };
    
    if (pageTitle) {
        pageTitle.textContent = titles[section] || 'Dashboard';
    }
}

// Manejar búsqueda de productos
function handleProductSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    filterProducts(searchTerm);
}

// Filtrar productos por término de búsqueda
function filterProducts(searchTerm) {
    const filteredProducts = products.filter(product => 
        product.name.toLowerCase().includes(searchTerm) ||
        product.brand.toLowerCase().includes(searchTerm)
    );
    
    renderProducts(filteredProducts);
}

// Manejar escáner de código de barras (placeholder)
function handleBarcodeScanner() {
    showTemporaryMessage('Función de código de barras - Próximamente');
}

// Manejar cambio de tabs
function handleTabChange(event) {
    // Remover clase active de todos los tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Agregar clase active al tab clickeado
    event.target.classList.add('active');
    
    const category = event.target.dataset.tab;
    filterByCategory(category);
}

// Filtrar productos por categoría
function filterByCategory(category) {
    // Cargar productos filtrados desde la API
    loadProducts(category.toLowerCase());
}

// Inicializar datos del vendedor desde PHP
function initializeVendedorData() {
    // Obtener el vendedor_id desde PHP (pasado como variable global)
    if (typeof vendedorId !== 'undefined') {
        currentVendedorId = vendedorId;
    } else {
        console.error('ID de vendedor no disponible');
    }
}

// Cargar productos desde la API filtrados por contribuyente
async function loadProducts(categoria = 'todos') {
    if (!currentVendedorId) {
        console.error('ID de vendedor no disponible para cargar productos');
        return;
    }
    
    try {
        const response = await fetch(`api/productos.php?vendedor_id=${currentVendedorId}&categoria=${categoria}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            products = data.data.productos;
            availableCategories = data.data.categorias;
            currentContribuyente = data.data.contribuyente;
            
            // Actualizar símbolo de moneda si está disponible
            if (data.data.currency_symbol) {
                currencySymbol = data.data.currency_symbol;
            }
            
            // Actualizar categorías disponibles en la UI
            updateCategoriesUI();
            
            // Renderizar productos
            renderProducts(products);
            
            console.log(`Productos cargados para contribuyente: ${currentContribuyente.nombre_comercial}`);
        } else {
            console.error('Error al cargar productos:', data.error);
            showTemporaryMessage('Error al cargar productos: ' + data.error);
            products = [];
            renderProducts([]);
        }
    } catch (error) {
        console.error('Error de conexión al cargar productos:', error);
        showTemporaryMessage('Error de conexión al cargar productos');
        products = [];
        renderProducts([]);
    }
}

// Actualizar categorías en la interfaz
function updateCategoriesUI() {
    const tabsContainer = document.querySelector('.tabs');
    if (!tabsContainer) return;
    
    // Obtener la pestaña activa actual
    const currentActiveTab = tabsContainer.querySelector('.tab.active');
    const currentActiveCategory = currentActiveTab ? currentActiveTab.textContent.trim() : 'Todos';
    
    // Limpiar tabs existentes excepto "Todos"
    const allTabs = tabsContainer.querySelectorAll('.tab');
    allTabs.forEach(tab => {
        if (tab.textContent.trim() !== 'Todos') {
            tab.remove();
        }
    });
    
    // Asegurar que "Todos" tenga la clase active si no hay otra activa
    const todosTab = tabsContainer.querySelector('.tab');
    if (todosTab && currentActiveCategory === 'Todos') {
        todosTab.classList.add('active');
    }
    
    // Agregar categorías específicas del contribuyente
    availableCategories.forEach(categoria => {
        const tab = document.createElement('div');
        tab.className = 'tab';
        tab.textContent = categoria;
        tab.setAttribute('data-tab', categoria.toLowerCase());
        tab.addEventListener('click', handleTabChange);
        
        // Mantener la pestaña activa si coincide
        if (categoria === currentActiveCategory) {
            tab.classList.add('active');
            // Remover active de "Todos" si otra categoría está activa
            if (todosTab) {
                todosTab.classList.remove('active');
            }
        }
        
        tabsContainer.appendChild(tab);
    });
}

// Renderizar productos en el grid
function renderProducts(productsToRender) {
    const productsArea = document.querySelector('.products-area');
    if (!productsArea) return;
    
    // Limpiar área de productos pero mantener el botón de agregar
    const addProductCard = productsArea.querySelector('.add-product-card');
    productsArea.innerHTML = '';
    
    // Re-agregar el botón de agregar producto
    if (addProductCard) {
        productsArea.appendChild(addProductCard);
        
        // Re-agregar event listener
        addProductCard.addEventListener('click', handleAddProduct);
    }
    
    // Renderizar productos
    productsToRender.forEach(product => {
        const productCard = createProductCard(product);
        productsArea.appendChild(productCard);
    });
}

// Crear tarjeta de producto
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.dataset.productId = product.id;
    
    card.innerHTML = `
        <div class="product-name">${product.name}</div>
        <div class="product-price">${currencySymbol}${product.price}</div>
        <div class="product-stock">${product.stock} disponibles</div>
    `;
    
    card.addEventListener('click', () => addToCart(product));
    
    return card;
}

// Agregar producto al carrito
function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }
    
    updateCartDisplay();
    showTemporaryMessage(`${product.name} agregado al carrito`);
}

// Actualizar visualización del carrito
function updateCartDisplay() {
    const cartContent = document.querySelector('.cart-content');
    const summaryRow = document.querySelector('.summary-row');
    const continueBtn = document.querySelector('.continue-btn');
    
    if (cart.length === 0) {
        // Mostrar carrito vacío
        cartContent.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <img src="assets/utilities/lectorbarras.webp" alt="Lector de código de barras" class="barcode-reader-icon">
                </div>
                <div class="empty-cart-text">
                    <h4>Agrega productos rápidamente usando tu lector de código de barras</h4>
                </div>
            </div>
        `;
        
        if (summaryRow) {
            summaryRow.innerHTML = `
                <span>0</span>
                <span>Continuar</span>
                <span>${currencySymbol}0</span>
            `;
        }
        
        if (continueBtn) {
            continueBtn.disabled = true;
        }
    } else {
        // Mostrar productos en el carrito
        const cartItems = cart.map(item => `
            <div class="cart-item" data-product-id="${item.id}">
                <div class="cart-item-header">
                    <div class="cart-item-name">${item.name}</div>
                    <button class="remove-item-btn" onclick="removeFromCart(${item.id})">
                        <span class="remove-icon">🗑️</span>
                    </button>
                </div>
                <div class="cart-item-controls">
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="decreaseQuantity(${item.id})">
                            <span class="minus-icon">−</span>
                        </button>
                        <span class="quantity-display">${item.quantity}</span>
                        <button class="quantity-btn" onclick="increaseQuantity(${item.id})">
                            <span class="plus-icon">+</span>
                        </button>
                    </div>
                    <div class="price-display">${currencySymbol} ${item.price}</div>
                </div>
                <div class="cart-item-total">Precio por ${item.quantity} unidades: ${currencySymbol}${(item.price * item.quantity).toFixed(2)}</div>
            </div>
        `).join('');
        
        cartContent.innerHTML = `<div class="cart-items">${cartItems}</div>`;
        
        // Calcular totales
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        if (summaryRow) {
            summaryRow.innerHTML = `
                <span>${totalItems}</span>
                <span>Continuar</span>
                <span>${currencySymbol}${totalAmount.toFixed(2)}</span>
            `;
        }
        
        if (continueBtn) {
            continueBtn.disabled = false;
        }
    }
}

// Manejar botones del header
function handleOpenCash() {
    showTemporaryMessage('Abrir caja - Función próximamente');
}

function handleNewSale() {
    showTemporaryMessage('Nueva venta libre - Función próximamente');
}

function handleNewExpense() {
    showTemporaryMessage('Nuevo gasto - Función próximamente');
}

function handleAddProduct() {
    const modal = document.getElementById('addProductModal');
    const categorySelect = document.getElementById('productCategory');
    
    // Limpiar formulario
    document.getElementById('addProductForm').reset();
    
    // Cargar categorías en el select
    loadCategoriesInModal(categorySelect);
    
    // Mostrar modal
    modal.style.display = 'block';
    
    // Focus en el primer campo
    setTimeout(() => {
        document.getElementById('productName').focus();
    }, 100);
}

// Cargar categorías en el modal
function loadCategoriesInModal(selectElement) {
    // Limpiar opciones existentes excepto la primera
    selectElement.innerHTML = '<option value="">Seleccionar categoría</option>';
    
    // Agregar categorías disponibles
    if (availableCategories && availableCategories.length > 0) {
        availableCategories.forEach(categoria => {
            const option = document.createElement('option');
            option.value = categoria.IDCategoria;
            option.textContent = categoria.Categoria;
            selectElement.appendChild(option);
        });
    }
}

// Cerrar modal
function closeAddProductModal() {
    const modal = document.getElementById('addProductModal');
    modal.style.display = 'none';
}

// Manejar envío del formulario
function handleAddProductSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const productData = {
        name: formData.get('productName'),
        barcode: formData.get('productBarcode'),
        price: parseFloat(formData.get('productPrice')),
        cost: parseFloat(formData.get('productCost')),
        stock: parseInt(formData.get('productStock')),
        category: formData.get('productCategory')
    };
    
    // Validar datos
    if (!validateProductData(productData)) {
        return;
    }
    
    // Enviar datos al servidor
    saveNewProduct(productData);
}

// Validar datos del producto
function validateProductData(data) {
    if (!data.name.trim()) {
        showTemporaryMessage('El nombre del producto es requerido', 'error');
        return false;
    }
    
    if (!data.barcode.trim()) {
        showTemporaryMessage('El código de barras es requerido', 'error');
        return false;
    }
    
    if (data.price <= 0) {
        showTemporaryMessage('El precio debe ser mayor a 0', 'error');
        return false;
    }
    
    if (data.cost < 0) {
        showTemporaryMessage('El costo no puede ser negativo', 'error');
        return false;
    }
    
    if (data.stock < 0) {
        showTemporaryMessage('El stock no puede ser negativo', 'error');
        return false;
    }
    
    if (!data.category) {
        showTemporaryMessage('Debe seleccionar una categoría', 'error');
        return false;
    }
    
    return true;
}

// Guardar nuevo producto
async function saveNewProduct(productData) {
    const submitBtn = document.querySelector('.btn-save');
    const originalText = submitBtn.textContent;
    
    try {
        // Deshabilitar botón y mostrar loading
        submitBtn.disabled = true;
        submitBtn.textContent = 'Guardando...';
        
        const response = await fetch('api/productos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ...productData,
                vendedor_id: currentVendedorId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showTemporaryMessage('Producto agregado exitosamente', 'success');
            closeAddProductModal();
            
            // Recargar productos
            const activeTab = document.querySelector('.tab.active');
            const categoria = activeTab ? activeTab.dataset.tab : 'todos';
            loadProducts(categoria);
        } else {
            showTemporaryMessage('Error al agregar producto: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error al guardar producto:', error);
        showTemporaryMessage('Error de conexión al guardar producto', 'error');
    } finally {
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Funciones para manejar el carrito
function increaseQuantity(productId) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity += 1;
        updateCartDisplay();
    }
}

function decreaseQuantity(productId) {
    const item = cart.find(item => item.id === productId);
    if (item && item.quantity > 1) {
        item.quantity -= 1;
        updateCartDisplay();
    } else if (item && item.quantity === 1) {
        removeFromCart(productId);
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
    showTemporaryMessage('Producto eliminado del carrito');
}

// Vaciar carrito
function handleClearCart() {
    cart = [];
    updateCartDisplay();
    showTemporaryMessage('Carrito vaciado');
}

// Continuar con la venta
function handleContinue() {
    if (cart.length > 0) {
        showTemporaryMessage('Procesando venta - Función próximamente');
    }
}

// Mostrar mensaje temporal
function showTemporaryMessage(message, type = 'info') {
    // Crear elemento de mensaje
    const messageEl = document.createElement('div');
    messageEl.className = 'temp-message';
    messageEl.textContent = message;
    
    // Definir colores según el tipo
    const colors = {
        'info': '#28a745',
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107'
    };
    
    messageEl.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(messageEl);
    
    // Remover después de 3 segundos (o 5 para errores)
    const duration = type === 'error' ? 5000 : 3000;
    setTimeout(() => {
        messageEl.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.parentNode.removeChild(messageEl);
            }
        }, 300);
    }, duration);
}

// Función de logout
function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        window.location.href = 'logout.php';
    }
}

// Agregar estilos para las animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .cart-items {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .cart-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-bottom: 10px;
    }

    .cart-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .cart-item-name {
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }

    .remove-item-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        color: #dc3545;
    }

    .remove-item-btn:hover {
        background: #f5c6cb;
    }

    .cart-item-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: bold;
    }

    .quantity-btn:hover {
        background: #f8f9fa;
        border-color: #007bff;
    }

    .quantity-display {
        font-weight: 600;
        font-size: 16px;
        min-width: 20px;
        text-align: center;
    }

    .price-display {
        font-weight: 600;
        color: #28a745;
        font-size: 16px;
    }

    .cart-item-total {
        font-size: 14px;
        color: #6c757d;
        text-align: center;
    }
`;
document.head.appendChild(style);