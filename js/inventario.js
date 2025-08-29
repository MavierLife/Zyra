// Funcionalidad para la página de inventario

// Variables globales
let currentDropdown = null;
let editModal = null;
let currentProductId = null;
let productos = []; // Se cargarán desde la API
let categorias = []; // Se cargarán desde la API

// Configuración de la API
const API_BASE_URL = 'api/inventario.php';

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    initializeInventory();
    setupEventListeners();
    loadInventoryData();
});

// Configurar event listeners
function setupEventListeners() {
    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (currentDropdown && !currentDropdown.contains(e.target)) {
            closeDropdown();
        }
    });

    // Búsqueda en tiempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterProducts();
        });
    }

    // Filtros
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterProducts();
        });
    }

    // Modal events
    setupModalEvents();
}

// Inicializar inventario
function initializeInventory() {
    editModal = document.getElementById('editModal');
    updateInventoryStats();
}

// Cargar datos del inventario desde la API
async function loadInventoryData() {
    try {
        const response = await fetch(`${API_BASE_URL}?action=productos`);
        const result = await response.json();
        
        if (result.success) {
            productos = result.data;
            renderProductTable();
            updateInventoryStats();
        } else {
            console.error('Error al cargar productos:', result.error);
            showNotification('Error al cargar los productos', 'error');
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        showNotification('Error de conexión con el servidor', 'error');
    }
}

// Renderizar tabla de productos
function renderProductTable() {
    const tbody = document.querySelector('.inventory-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    
    productos.forEach(producto => {
        const row = createProductRow(producto);
        tbody.appendChild(row);
    });
}

// Crear fila de producto
function createProductRow(producto) {
    const ganancia = producto.Precio - producto.Costo;
    const porcentaje = producto.Costo > 0 ? Math.round((ganancia / producto.Costo) * 100) : 0;
    
    const row = document.createElement('tr');
    row.dataset.productId = producto.IDProducto;
    
    row.innerHTML = `
        <td class="col-product">
            <div class="product-info">
                <div class="product-image">
                    📦
                </div>
                <div class="product-details">
                    <h4 onclick="openEditModal(${producto.IDProducto})">${producto.Nombre}</h4>
                </div>
            </div>
        </td>
        <td class="col-price price-cell">$${parseFloat(producto.Precio).toFixed(2)}</td>
        <td class="col-cost cost-cell">$${parseFloat(producto.Costo).toFixed(2)}</td>
        <td class="col-quantity">
            <div class="quantity-cell">
                <span class="quantity-number">${producto.Cantidad}</span>
            </div>
        </td>
        <td class="col-profit">
            <div class="profit-cell profit-positive">
                $${ganancia.toFixed(2)}
                <span class="profit-percentage">${porcentaje}%</span>
            </div>
        </td>
    `;

    // Agregar event listener para el clic en la fila
    row.addEventListener('click', function(e) {
        // No mostrar dropdown si se hizo clic en el nombre del producto
        if (e.target.tagName === 'H4') {
            return;
        }
        
        e.stopPropagation();
        showDropdown(e, producto.IDProducto);
    });

    return row;
}

// Mostrar menú desplegable
function showDropdown(event, productId) {
    // Cerrar dropdown anterior si existe
    closeDropdown();

    const dropdown = document.createElement('div');
    dropdown.className = 'dropdown-menu';
    dropdown.innerHTML = `
        <div class="dropdown-item" onclick="openEditModal(${productId})">
            <span class="dropdown-icon">✏️</span>
            <span class="dropdown-text">Editar producto</span>
        </div>
        <div class="dropdown-item" onclick="duplicateProduct(${productId})">
            <span class="dropdown-icon">📋</span>
            <span class="dropdown-text">Duplicar producto</span>
        </div>
        <div class="dropdown-item" onclick="viewProduct(${productId})">
            <span class="dropdown-icon">👁️</span>
            <span class="dropdown-text">Ver producto</span>
        </div>
        <div class="dropdown-item" onclick="deleteProduct(${productId})">
            <span class="dropdown-icon">🗑️</span>
            <span class="dropdown-text">Eliminar producto</span>
        </div>
    `;

    // Posicionar el dropdown
    const rect = event.currentTarget.getBoundingClientRect();
    dropdown.style.left = `${event.clientX - 90}px`;
    dropdown.style.top = `${rect.bottom + window.scrollY + 5}px`;

    document.body.appendChild(dropdown);
    currentDropdown = dropdown;

    // Prevenir que el clic en el dropdown lo cierre
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}

// Cerrar dropdown
function closeDropdown() {
    if (currentDropdown) {
        currentDropdown.remove();
        currentDropdown = null;
    }
}

// Abrir modal de edición
function openEditModal(productId) {
    closeDropdown();
    currentProductId = productId;
    
    const producto = productos.find(p => p.IDProducto === productId);
    if (!producto) return;

    // Llenar el formulario con los datos del producto
    document.getElementById('productName').value = producto.Nombre;
    document.getElementById('productCode').value = producto.Codigo;
    document.getElementById('productQuantity').value = producto.Cantidad;
    document.getElementById('productPrice').value = producto.Precio;
    document.getElementById('productCost').value = producto.Costo;

    // Mostrar el modal
    if (editModal) {
        editModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

// Configurar eventos del modal
function setupModalEvents() {
    // Cerrar modal
    const closeBtn = document.querySelector('.close-modal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeEditModal);
    }

    // Cerrar modal al hacer clic fuera
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                closeEditModal();
            }
        });
    }

    // Tabs del modal
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Solo permitir la pestaña "Producto básico"
            if (this.dataset.tab === 'basic') {
                switchTab('basic');
            }
        });
    });

    // Guardar cambios
    const saveBtn = document.querySelector('.btn-save');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveProduct);
    }

    // Eliminar producto
    const deleteBtn = document.querySelector('.btn-delete');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (currentProductId) {
                deleteProduct(currentProductId);
            }
        });
    }
}

// Cerrar modal de edición
function closeEditModal() {
    if (editModal) {
        editModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    currentProductId = null;
}

// Cambiar pestaña del modal
function switchTab(tabName) {
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tabName) {
            btn.classList.add('active');
        }
    });
}

// Guardar producto
async function saveProduct() {
    if (!currentProductId) return;

    const nombre = document.getElementById('productName').value;
    const codigo = document.getElementById('productCode').value;
    const cantidad = parseInt(document.getElementById('productQuantity').value);
    const precio = parseFloat(document.getElementById('productPrice').value);
    const costo = parseFloat(document.getElementById('productCost').value);

    // Validar datos
    if (!nombre || !codigo || isNaN(cantidad) || isNaN(precio) || isNaN(costo)) {
        showNotification('Por favor, complete todos los campos correctamente.', 'error');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}?action=producto`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: currentProductId,
                nombre: nombre,
                codigo: codigo,
                cantidad: cantidad,
                precio: precio,
                costo: costo,
                categoria: null // Por ahora sin categoría
            })
        });

        const result = await response.json();
        
        if (result.success) {
            showNotification('Producto actualizado correctamente.', 'success');
            await loadInventoryData();
            closeEditModal();
        } else {
            showNotification(result.error || 'Error al actualizar el producto', 'error');
        }
    } catch (error) {
        console.error('Error al guardar producto:', error);
        showNotification('Error de conexión con el servidor', 'error');
    }
}

// Duplicar producto
async function duplicateProduct(productId) {
    closeDropdown();
    
    const producto = productos.find(p => p.IDProducto === productId);
    if (!producto) return;

    try {
        const response = await fetch(`${API_BASE_URL}?action=producto`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                codigo: producto.Codigo + '_COPIA',
                nombre: producto.Nombre + ' (copia)',
                cantidad: producto.Cantidad,
                precio: producto.Precio,
                costo: producto.Costo,
                categoria: producto.IDCategoria
            })
        });

        const result = await response.json();
        
        if (result.success) {
            showNotification('Producto duplicado correctamente.', 'success');
            await loadInventoryData();
        } else {
            showNotification(result.error || 'Error al duplicar el producto', 'error');
        }
    } catch (error) {
        console.error('Error al duplicar producto:', error);
        showNotification('Error de conexión con el servidor', 'error');
    }
}

// Ver producto
function viewProduct(productId) {
    closeDropdown();
    openEditModal(productId);
    
    // Deshabilitar campos para solo lectura
    const inputs = document.querySelectorAll('#editModal input, #editModal select, #editModal textarea');
    inputs.forEach(input => {
        input.disabled = true;
    });
    
    // Ocultar botones de acción
    document.querySelector('.form-actions').style.display = 'none';
}

// Eliminar producto
async function deleteProduct(productId) {
    closeDropdown();
    
    if (confirm('¿Está seguro de que desea eliminar este producto?')) {
        try {
            const response = await fetch(`${API_BASE_URL}?action=producto&id=${productId}`, {
                method: 'DELETE'
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification('Producto eliminado correctamente.', 'success');
                await loadInventoryData();
                closeEditModal();
            } else {
                showNotification(result.error || 'Error al eliminar el producto', 'error');
            }
        } catch (error) {
            console.error('Error al eliminar producto:', error);
            showNotification('Error de conexión con el servidor', 'error');
        }
    }
}

// Filtrar productos
function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    
    const rows = document.querySelectorAll('.inventory-table tbody tr');
    
    rows.forEach(row => {
        const productName = row.querySelector('.product-details h4').textContent.toLowerCase();
        const matchesSearch = productName.includes(searchTerm);
        const matchesCategory = categoryFilter === 'all' || categoryFilter === '';
        
        if (matchesSearch && matchesCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Cargar estadísticas desde la API
async function updateInventoryStats() {
    try {
        const response = await fetch(`${API_BASE_URL}?action=estadisticas`);
        const result = await response.json();
        
        if (result.success) {
            const stats = result.data;
            
            // Actualizar elementos del DOM
            const statNumbers = document.querySelectorAll('.stat-number');
            if (statNumbers.length >= 4) {
                statNumbers[0].textContent = stats.total_productos;
                statNumbers[1].textContent = `$${stats.valor_total.toFixed(2)}`;
                statNumbers[2].textContent = stats.stock_bajo;
                statNumbers[3].textContent = `$${stats.ganancia_total.toFixed(2)}`;
            }
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// Mostrar notificaciones
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
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
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
            notification.style.color = '#212529';
            break;
        default:
            notification.style.backgroundColor = '#007bff';
    }
    
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Funciones para los botones del header
function openCategoriesModal() {
    showNotification('Funcionalidad de categorías en desarrollo.', 'info');
}

function openNewProductModal() {
    showNotification('Funcionalidad para crear nuevo producto en desarrollo.', 'info');
}

function registerPurchase() {
    showNotification('Funcionalidad para registrar compra en desarrollo.', 'info');
}