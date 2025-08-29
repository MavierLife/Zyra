// Variables globales
let products = [];
let categories = [];
let filteredProducts = [];
let currentEditingProduct = null;

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

        const saveBtn = document.getElementById('saveProductBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveProduct);
        }

        const cancelBtn = document.getElementById('cancelEditBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeEditModal);
        }
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
            renderProductTable(products);
            updateInventoryStats();
            await loadCategories();
        } else {
            showNotification('Error al cargar productos: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión al cargar productos', 'error');
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
    
    // Calcular ganancia y porcentaje
    const precio = parseFloat(product.precio) || 0;
    const costo = parseFloat(product.costo) || 0;
    const ganancia = precio - costo;
    const porcentaje = costo > 0 ? ((ganancia / costo) * 100) : 0;
    
    row.innerHTML = `
        <td>${product.nombre || ''}</td>
        <td>$${precio.toFixed(2)}</td>
        <td>$${costo.toFixed(2)}</td>
        <td>${product.cantidad || 0}</td>
        <td>$${ganancia.toFixed(2)} (${porcentaje.toFixed(1)}%)</td>
        <td>
            <div class="action-buttons">
                <button class="btn-edit" onclick="editProduct('${product.id}')" title="Editar">
                    ✏️
                </button>
                <button class="btn-duplicate" onclick="duplicateProduct('${product.id}')" title="Duplicar">
                    📋
                </button>
                <button class="btn-delete" onclick="deleteProduct('${product.id}')" title="Eliminar">
                    🗑️
                </button>
            </div>
        </td>
    `;
    
    return row;
}

// Actualizar estadísticas del inventario
function updateInventoryStats() {
    const totalProducts = products.length;
    const totalValue = products.reduce((sum, product) => {
        return sum + (parseFloat(product.costo) * parseInt(product.cantidad));
    }, 0);

    // Actualizar elementos del DOM
    const totalProductsEl = document.getElementById('totalProducts');
    const totalValueEl = document.getElementById('totalValue');

    if (totalProductsEl) totalProductsEl.textContent = totalProducts;
    if (totalValueEl) totalValueEl.textContent = `$${totalValue.toLocaleString()}`;
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
            (product.Nombre && product.Nombre.toLowerCase().includes(searchTerm)) ||
            (product.Codigo && product.Codigo.toLowerCase().includes(searchTerm))
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
    const searchTerm = document.getElementById('productSearch')?.value || '';
    filterProducts(searchTerm, categoryId);
}

// Abrir modal de agregar producto
function openAddProductModal() {
    // Implementar si es necesario
    showNotification('Funcionalidad de agregar producto pendiente', 'info');
}

// Editar producto
function editProduct(productId) {
    const product = products.find(p => p.IDProducto == productId);
    if (!product) return;

    currentEditingProduct = product;
    
    // Llenar el modal con los datos del producto
    document.getElementById('editProductCode').value = product.Codigo || '';
    document.getElementById('editProductName').value = product.Nombre || '';
    document.getElementById('editProductQuantity').value = product.Cantidad || 0;
    document.getElementById('editProductPrice').value = product.Precio || 0;
    document.getElementById('editProductCost').value = product.Costo || 0;
    document.getElementById('editProductCategory').value = product.IDCategoria || '';

    // Mostrar el modal
    const modal = document.getElementById('editProductModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Guardar producto editado
async function saveProduct() {
    if (!currentEditingProduct) return;

    const formData = {
        id: currentEditingProduct.IDProducto,
        codigo: document.getElementById('editProductCode').value,
        nombre: document.getElementById('editProductName').value,
        cantidad: parseInt(document.getElementById('editProductQuantity').value),
        precio: parseFloat(document.getElementById('editProductPrice').value),
        costo: parseFloat(document.getElementById('editProductCost').value),
        categoria: document.getElementById('editProductCategory').value
    };

    try {
        const response = await fetch('api/inventario.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Producto actualizado correctamente', 'success');
            closeEditModal();
            loadInventoryData(); // Recargar datos
        } else {
            showNotification('Error al actualizar producto: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión al actualizar producto', 'error');
    }
}

// Duplicar producto
function duplicateProduct(productId) {
    const product = products.find(p => p.IDProducto == productId);
    if (!product) return;

    const duplicatedProduct = {
        IDProducto: product.IDProducto,
        Codigo: product.Codigo + '_copy',
        Nombre: product.Nombre + ' (Copia)',
        Cantidad: product.Cantidad,
        Precio: product.Precio,
        Costo: product.Costo
    };

    // Aquí implementarías la lógica para crear el producto duplicado
    showNotification('Funcionalidad de duplicar pendiente', 'info');
}

// Eliminar producto
async function deleteProduct(productId) {
    if (!confirm('¿Estás seguro de que deseas eliminar este producto?')) {
        return;
    }

    try {
        const response = await fetch(`api/inventario.php?action=delete&id=${productId}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Producto eliminado correctamente', 'success');
            loadInventoryData(); // Recargar datos
        } else {
            showNotification('Error al eliminar producto: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión al eliminar producto', 'error');
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

// Mostrar notificación
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Estilos básicos
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    // Colores según el tipo
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#000';
            break;
        default:
            notification.style.backgroundColor = '#17a2b8';
    }
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}