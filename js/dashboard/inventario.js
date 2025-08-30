// Variables globales
let products = [];
let categories = [];
let filteredProducts = [];
let currentEditingProduct = null;
let currencySymbol = '$'; // Símbolo de moneda dinámico

// Configurar SweetAlert2 globalmente para z-index
const MySwal = Swal.mixin({
    zIndex: 10000
});

// Configurar z-index por defecto
if (typeof Swal !== 'undefined') {
    Swal.getConfig = Swal.getConfig || function() { return {}; };
    const originalFire = Swal.fire;
    Swal.fire = function(options) {
        if (typeof options === 'object' && options !== null) {
            options.zIndex = options.zIndex || 10000;
        }
        return originalFire.call(this, options);
    };
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeInventory();
});

// Inicializar inventario
function initializeInventory() {
    setupEventListeners();
    loadInventoryData();
}

// Configurar event listeners
function setupEventListeners() {
    // Botón agregar producto
    const addBtn = document.getElementById('createProductBtn');
    if (addBtn) {
        addBtn.addEventListener('click', openAddProductModal);
    }

    // Búsqueda de productos
    const searchInput = document.getElementById('inventorySearch');
    if (searchInput) {
        searchInput.addEventListener('input', handleProductSearch);
    }

    // Dropdown de categorías
    const categoryDropdown = document.getElementById('categoryFilter');
    if (categoryDropdown) {
        categoryDropdown.addEventListener('change', handleCategoryFilter);
    }

    // Modal de edición
    const editModal = document.getElementById('editProductModal');
    if (editModal) {
        const closeBtn = editModal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeEditModal);
        }

        // Manejar submit del formulario
        const editForm = document.getElementById('editProductForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveProduct();
            });
        }

        // Limitar decimales en campos de precios
        const priceInputs = ['editProductPrice', 'editProductCost', 'editProductDiscountPrice'];
        priceInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', function(e) {
                    limitDecimals(e.target, 2);
                });
            }
        });
    }
}

// Cargar datos del inventario
async function loadInventoryData() {
    try {
        const response = await fetch('api/inventario.php?action=productos');
        const data = await response.json();
        
        if (data.success) {
            products = data.data;
            filteredProducts = [...products];
            
            // Actualizar símbolo de moneda si está disponible
            if (data.currency_symbol) {
                currencySymbol = data.currency_symbol;
            }
            
            renderProductTable(products);
            updateInventoryStats();
            await loadCategories();
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Error al cargar productos: ' + data.message,
                icon: 'error'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
        icon: 'error'
        });
    }
}

// Cargar categorías
async function loadCategories() {
    try {
        const response = await fetch('api/inventario.php?action=categorias');
        const data = await response.json();
        
        if (data.success) {
            categories = data.data;
            populateCategoryDropdowns();
        } else {
            console.error('Error al cargar categorías:', data.message);
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

// Poblar dropdowns de categorías
function populateCategoryDropdowns() {
    // Dropdown de filtro
    const filterDropdown = document.getElementById('categoryFilter');
    if (filterDropdown) {
        filterDropdown.innerHTML = '<option value="all">Todas las categorías</option>';
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            filterDropdown.appendChild(option);
        });
    }

    // Dropdown del modal de edición
    const editDropdown = document.getElementById('editProductCategory');
    if (editDropdown) {
        editDropdown.innerHTML = '<option value="">Seleccionar categoría</option>';
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            editDropdown.appendChild(option);
        });
    }
}

// Renderizar tabla de productos
function renderProductTable(productsToRender) {
    const tbody = document.getElementById('inventoryTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    productsToRender.forEach(product => {
        const row = createProductRow(product);
        tbody.appendChild(row);
    });
}

// Crear fila de producto
function createProductRow(product) {
    const row = document.createElement('tr');
    
    // Usar los nombres de campos que devuelve la API
    const precio = parseFloat(product.precio) || 0;
    const costo = parseFloat(product.costo) || 0;
    const ganancia = parseFloat(product.ganancia) || 0;
    const porcentaje = parseFloat(product.porcentaje) || 0;
    const preciodescuento = parseFloat(product.preciodescuento) || 0;
    const cantidadminima = parseInt(product.cantidadminima) || 0;
    
    // Formatear precio especial
    let precioEspecialText = '-';
    if (preciodescuento > 0 && cantidadminima > 0) {
        precioEspecialText = `${currencySymbol}${preciodescuento.toFixed(2)} <span class="special-price-quantity">${cantidadminima}+</span>`;
    }
    
    row.innerHTML = `
        <td>${product.nombre || ''}</td>
        <td>${currencySymbol}${precio.toFixed(2)}</td>
        <td>${precioEspecialText}</td>
        <td>${currencySymbol}${costo.toFixed(2)}</td>
        <td>${product.cantidad || 0}</td>
        <td>${currencySymbol}${ganancia.toFixed(2)} <span class="profit-percentage-box">${porcentaje.toFixed(1)}%</span></td>
        <td>
            <div class="action-buttons">
                <button class="btn-edit" data-product-id="${product.id}" title="Editar">
                    <img src="assets/icons/editar.svg" alt="Editar" width="16" height="16">
                </button>
                <button class="btn-delete" data-product-id="${product.id}" title="Eliminar">
                    <img src="assets/icons/borrar.svg" alt="Eliminar" width="16" height="16">
                </button>
            </div>
        </td>
    `;
    
    // Agregar event listeners a los botones
    const editBtn = row.querySelector('.btn-edit');
    const deleteBtn = row.querySelector('.btn-delete');
    
    if (editBtn) {
        editBtn.addEventListener('click', () => editProduct(product.id));
    }
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => deleteProduct(product.id));
    }
    
    return row;
}

// Actualizar estadísticas del inventario
function updateInventoryStats() {
    const totalProducts = products.length;
    const totalInventoryCost = products.reduce((sum, product) => {
        const costo = parseFloat(product.costo) || 0;
        const cantidad = parseInt(product.cantidad) || 0;
        return sum + (costo * cantidad);
    }, 0);

    // Actualizar elementos del DOM
    const totalProductsEl = document.getElementById('totalProducts');
    const totalValueEl = document.getElementById('totalValue');

    if (totalProductsEl) totalProductsEl.textContent = totalProducts;
    if (totalValueEl) totalValueEl.textContent = `${currencySymbol}${totalInventoryCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// Manejar búsqueda de productos
function handleProductSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    filterProducts(searchTerm);
}

// Filtrar productos
function filterProducts(searchTerm = '', categoryId = 'all') {
    let filtered = [...products];

    // Filtrar por término de búsqueda
    if (searchTerm) {
        filtered = filtered.filter(product => 
            (product.nombre && product.nombre.toLowerCase().includes(searchTerm)) ||
            (product.codigo && product.codigo.toLowerCase().includes(searchTerm))
        );
    }

    // Filtrar por categoría
    if (categoryId && categoryId !== 'all') {
        filtered = filtered.filter(product => 
            product.IDCategoria && product.IDCategoria.toString() === categoryId.toString()
        );
    }

    filteredProducts = filtered;
    renderProductTable(filtered);
}

// Manejar filtro de categoría
function handleCategoryFilter(event) {
    const categoryId = event.target.value;
    const searchTerm = document.getElementById('inventorySearch')?.value || '';
    filterProducts(searchTerm, categoryId);
}

// Abrir modal de agregar producto
function openAddProductModal() {
    // Implementar si es necesario
    Swal.fire({
        title: 'Información',
        text: 'Funcionalidad de agregar producto pendiente',
        icon: 'info'
    });
}

// Editar producto
function editProduct(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) {
        console.error('Producto no encontrado con ID:', productId);
        return;
    }

    currentEditingProduct = product;
    
    // Llenar el modal con los datos del producto
    document.getElementById('editProductCode').value = product.codigo || '';
    document.getElementById('editProductName').value = product.nombre || '';
    document.getElementById('editProductStock').value = product.cantidad || 0;
    document.getElementById('editProductPrice').value = product.precio || 0;
    document.getElementById('editProductCost').value = product.costo || 0;
    document.getElementById('editProductCategory').value = product.IDCategoria || '';
    document.getElementById('editProductMinQuantity').value = product.cantidadminima || 0;
    document.getElementById('editProductDiscountPrice').value = product.preciodescuento || 0;

    // Mostrar el modal
    const modal = document.getElementById('editProductModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Verificar si hay cambios en el formulario
function hasFormChanges() {
    if (!currentEditingProduct) return false;

    const currentValues = {
        codigo: document.getElementById('editProductCode').value,
        nombre: document.getElementById('editProductName').value,
        cantidad: parseInt(document.getElementById('editProductStock').value) || 0,
        precio: parseFloat(document.getElementById('editProductPrice').value) || 0,
        costo: parseFloat(document.getElementById('editProductCost').value) || 0,
        categoria: document.getElementById('editProductCategory').value,
        cantidadminima: parseInt(document.getElementById('editProductMinQuantity').value) || 0,
        preciodescuento: parseFloat(document.getElementById('editProductDiscountPrice').value) || 0
    };

    const originalValues = {
        codigo: currentEditingProduct.codigo || '',
        nombre: currentEditingProduct.nombre || '',
        cantidad: parseInt(currentEditingProduct.cantidad) || 0,
        precio: parseFloat(currentEditingProduct.precio) || 0,
        costo: parseFloat(currentEditingProduct.costo) || 0,
        categoria: currentEditingProduct.IDCategoria || '',
        cantidadminima: parseInt(currentEditingProduct.cantidadminima) || 0,
        preciodescuento: parseFloat(currentEditingProduct.preciodescuento) || 0
    };

    // Comparar cada campo
    return Object.keys(currentValues).some(key => {
        return currentValues[key] !== originalValues[key];
    });
}

// Guardar producto editado
async function saveProduct() {
    if (!currentEditingProduct) return;

    // Verificar si hay cambios
    if (!hasFormChanges()) {
        Swal.fire({
            title: 'Sin cambios',
            text: 'No se han detectado cambios en el producto',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

    // Confirmación antes de guardar
    const result = await Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizará la información del producto',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = {
        id: currentEditingProduct.id,
        codigo: document.getElementById('editProductCode').value,
        nombre: document.getElementById('editProductName').value,
        cantidad: parseInt(document.getElementById('editProductStock').value),
        precio: Math.round(parseFloat(document.getElementById('editProductPrice').value) * 100) / 100,
        costo: Math.round(parseFloat(document.getElementById('editProductCost').value) * 100) / 100,
        categoria: document.getElementById('editProductCategory').value,
        cantidadminima: parseInt(document.getElementById('editProductMinQuantity').value) || 0,
        preciodescuento: Math.round(parseFloat(document.getElementById('editProductDiscountPrice').value || 0) * 100) / 100
    };

    try {
        const response = await fetch('api/inventario.php?action=producto', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                title: '¡Actualizado!',
                text: 'El producto ha sido actualizado correctamente',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            closeEditModal();
            loadInventoryData(); // Recargar datos
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Error al actualizar producto: ' + (data.message || data.error),
        icon: 'error'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
            icon: 'error'
        });
    }
}

// Duplicar producto
function duplicateProduct(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    const duplicatedProduct = {
        id: product.id,
        codigo: product.codigo + '_copy',
        nombre: product.nombre + ' (Copia)',
        cantidad: product.cantidad,
        precio: product.precio,
        costo: product.costo
    };

    // Aquí implementarías la lógica para crear el producto duplicado
    Swal.fire({
        title: 'Información',
        text: 'Funcionalidad de duplicar pendiente',
        icon: 'info'
    });
}

// Eliminar producto
async function deleteProduct(productId) {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(`api/inventario.php?action=producto&id=${productId}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                title: '¡Eliminado!',
                text: 'El producto ha sido eliminado correctamente',
                icon: 'success',
                timer: 2000,
        showConfirmButton: false
            });
            loadInventoryData(); // Recargar datos
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Error al eliminar producto: ' + (data.message || data.error),
        icon: 'error'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
            icon: 'error'
        });
    }
}

// Cerrar modal de edición
function closeEditModal() {
    const modal = document.getElementById('editProductModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentEditingProduct = null;
}



// Función para limitar decimales en inputs
function limitDecimals(input, maxDecimals) {
    let value = input.value;
    
    // Si hay un punto decimal
    if (value.includes('.')) {
        const parts = value.split('.');
        if (parts[1] && parts[1].length > maxDecimals) {
            input.value = parts[0] + '.' + parts[1].substring(0, maxDecimals);
        }
    }
}