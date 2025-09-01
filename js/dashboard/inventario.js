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

// Función para mostrar notificaciones consistentes
function showMessage(message, type = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            text: message,
            icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        alert(message);
    }
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
            showMessage('Error al cargar productos: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error de conexión: No se pudo conectar con el servidor', 'error');
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
    // Limpiar el producto actual en edición
    currentEditingProduct = null;
    
    // Cambiar el título del modal
    const modalTitle = document.querySelector('#editProductModal .modal-header h3');
    if (modalTitle) {
        modalTitle.textContent = 'Crear Producto';
    }
    
    // Limpiar todos los campos del formulario
    document.getElementById('editProductCode').value = '';
    document.getElementById('editProductName').value = '';
    document.getElementById('editProductStock').value = '0';
    document.getElementById('editProductPrice').value = '';
    document.getElementById('editProductCost').value = '';
    document.getElementById('editProductCategory').value = '';
    document.getElementById('editProductMinQuantity').value = '0';
    document.getElementById('editProductDiscountPrice').value = '';
    document.getElementById('editProductFractionalSale').checked = false;
    
    // Habilitar el campo código de barras para creación
    document.getElementById('editProductCode').readOnly = false;
    
    // Cambiar el texto del botón de guardar
    const saveButton = document.querySelector('#editProductForm .btn-save');
    if (saveButton) {
        saveButton.textContent = 'Crear Producto';
    }
    
    // Mostrar el modal
    const modal = document.getElementById('editProductModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Editar producto
function editProduct(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) {
        console.error('Producto no encontrado con ID:', productId);
        return;
    }

    currentEditingProduct = product;
    
    // Cambiar el título del modal para edición
    const modalTitle = document.querySelector('#editProductModal .modal-header h3');
    if (modalTitle) {
        modalTitle.textContent = 'Modificar Producto';
    }
    
    // Llenar el modal con los datos del producto
    document.getElementById('editProductCode').value = product.codigo || '';
    document.getElementById('editProductName').value = product.nombre || '';
    document.getElementById('editProductStock').value = product.cantidad || 0;
    document.getElementById('editProductPrice').value = product.precio || 0;
    document.getElementById('editProductCost').value = product.costo || 0;
    document.getElementById('editProductCategory').value = product.IDCategoria || '';
    document.getElementById('editProductMinQuantity').value = product.cantidadminima || 0;
    document.getElementById('editProductDiscountPrice').value = product.preciodescuento || 0;
    document.getElementById('editProductFractionalSale').checked = product.ventafracciones == 1;

    // Deshabilitar el campo código de barras para edición
    document.getElementById('editProductCode').readOnly = true;
    
    // Cambiar el texto del botón de guardar
    const saveButton = document.querySelector('#editProductForm .btn-save');
    if (saveButton) {
        saveButton.textContent = 'Guardar cambios';
    }

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
    const isCreating = !currentEditingProduct;
    
    // Si estamos editando, verificar si hay cambios
    if (!isCreating && !hasFormChanges()) {
        Swal.fire({
            title: 'Sin cambios',
            text: 'No se han detectado cambios en el producto',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }
    
    // Validar campos requeridos
    const codigo = document.getElementById('editProductCode').value.trim();
    const nombre = document.getElementById('editProductName').value.trim();
    const precio = parseFloat(document.getElementById('editProductPrice').value);
    const costo = parseFloat(document.getElementById('editProductCost').value);
    
    // Validaciones específicas
    if (!nombre) {
        Swal.fire({
            title: 'Campo requerido',
            text: 'El nombre del producto es obligatorio',
            icon: 'warning'
        });
        document.getElementById('editProductName').focus();
        return;
    }
    
    if (isCreating && !codigo) {
        Swal.fire({
            title: 'Campo requerido',
            text: 'El código de barras es obligatorio para crear un producto',
            icon: 'warning'
        });
        document.getElementById('editProductCode').focus();
        return;
    }
    
    if (!precio || precio <= 0) {
        Swal.fire({
            title: 'Precio inválido',
            text: 'El precio de venta debe ser mayor a 0',
            icon: 'warning'
        });
        document.getElementById('editProductPrice').focus();
        return;
    }
    
    if (!costo || costo < 0) {
        Swal.fire({
            title: 'Costo inválido',
            text: 'El costo de compra debe ser mayor o igual a 0',
            icon: 'warning'
        });
        document.getElementById('editProductCost').focus();
        return;
    }
    
    // Validar que el precio sea mayor al costo
    if (precio <= costo) {
        const result = await Swal.fire({
            title: 'Advertencia de rentabilidad',
            text: 'El precio de venta es menor o igual al costo. ¿Desea continuar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        });
        
        if (!result.isConfirmed) {
            return;
        }
    }

    // Confirmación antes de guardar
    const confirmTitle = isCreating ? '¿Crear producto?' : '¿Guardar cambios?';
    const confirmText = isCreating ? 'Se creará un nuevo producto' : 'Se actualizará la información del producto';
    const confirmButton = isCreating ? 'Sí, crear' : 'Sí, guardar';
    
    const result = await Swal.fire({
        title: confirmTitle,
        text: confirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmButton,
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = {
        codigo: document.getElementById('editProductCode').value.trim(),
        nombre: document.getElementById('editProductName').value.trim(),
        cantidad: parseInt(document.getElementById('editProductStock').value) || 0,
        precio: Math.round(parseFloat(document.getElementById('editProductPrice').value) * 100) / 100,
        costo: Math.round(parseFloat(document.getElementById('editProductCost').value) * 100) / 100,
        categoria: document.getElementById('editProductCategory').value || null,
        cantidadminima: parseInt(document.getElementById('editProductMinQuantity').value) || 0,
        preciodescuento: Math.round(parseFloat(document.getElementById('editProductDiscountPrice').value || 0) * 100) / 100,
        ventafracciones: document.getElementById('editProductFractionalSale').checked ? 1 : 0
    };
    
    // Agregar ID solo si estamos editando
    if (!isCreating) {
        formData.id = currentEditingProduct.id;
    }

    try {
        const method = isCreating ? 'POST' : 'PUT';
        const response = await fetch('api/inventario.php?action=producto', {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            const successMessage = isCreating ? 'Producto creado exitosamente' : 'Producto actualizado exitosamente';
            showMessage(successMessage, 'success');
            closeEditModal();
            loadInventoryData(); // Recargar datos
        } else {
            const errorAction = isCreating ? 'crear' : 'actualizar';
            showMessage(`Error al ${errorAction} producto: ` + (data.message || data.error), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error de conexión: No se pudo conectar con el servidor', 'error');
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
            showMessage('Producto eliminado exitosamente', 'success');
            loadInventoryData(); // Recargar datos
        } else {
            showMessage('Error al eliminar producto: ' + (data.message || data.error), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error de conexión: No se pudo conectar con el servidor', 'error');
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