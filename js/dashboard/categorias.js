/**
 * Gestión del menú lateral de categorías
 * Maneja las operaciones CRUD y la interfaz del menú desplegable
 */

class CategoriesManager {
    constructor() {
        this.sidebar = null;
        this.overlay = null;
        this.categoriesList = null;
        this.isOpen = false;
        this.categories = [];
        this.currentEditingId = null;
        
        this.init();
    }
    
    init() {
        // Obtener elementos del DOM
        this.sidebar = document.getElementById('categoriesSidebar');
        this.overlay = document.getElementById('categoriesOverlay');
        this.categoriesList = document.getElementById('categoriesList');
        
        if (!this.sidebar || !this.overlay || !this.categoriesList) {
            console.warn('Elementos del menú de categorías no encontrados');
            return;
        }
        
        this.bindEvents();
        this.loadCategories();
    }
    
    bindEvents() {
        // Botón para abrir el menú
        const categoriesBtn = document.getElementById('categoriesBtn');
        if (categoriesBtn) {
            categoriesBtn.addEventListener('click', () => this.openSidebar());
        }
        
        // Botón para cerrar el menú
        const closeBtn = document.getElementById('closeCategoriesBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeSidebar());
        }
        
        // Overlay para cerrar el menú
        this.overlay.addEventListener('click', () => this.closeSidebar());
        
        // Botón para agregar nueva categoría
        const addBtn = document.getElementById('addCategoryBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showCreateForm());
        }
        
        // Cerrar con tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeSidebar();
            }
        });
    }
    
    openSidebar() {
        this.sidebar.classList.add('active');
        this.overlay.classList.add('active');
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
    }
    
    closeSidebar() {
        this.sidebar.classList.remove('active');
        this.overlay.classList.remove('active');
        this.isOpen = false;
        document.body.style.overflow = '';
        this.cancelEdit();
    }
    
    async loadCategories() {
        try {
            const response = await fetch('api/categorias.php?action=list');
            const data = await response.json();
            
            if (data.success) {
                this.categories = data.data;
                this.renderCategories();
            } else {
                console.error('Error al cargar categorías:', data.message);
                this.showMessage('Error al cargar categorías', 'error');
            }
        } catch (error) {
            console.error('Error al cargar categorías:', error);
            this.showMessage('Error de conexión al cargar categorías', 'error');
        }
    }
    
    renderCategories() {
        if (!this.categoriesList) return;
        
        if (this.categories.length === 0) {
            this.categoriesList.innerHTML = `
                <div class="empty-state">
                    <p>No hay categorías creadas</p>
                    <small>Crea tu primera categoría usando el botón de arriba</small>
                </div>
            `;
            return;
        }
        
        this.categoriesList.innerHTML = this.categories.map(category => `
            <div class="category-item" data-id="${category.IDCategoria}">
                <div class="category-name">${this.escapeHtml(category.Categoria)}</div>
                <div class="category-actions">
                    <button class="btn-icon edit" onclick="categoriesManager.editCategory(${category.IDCategoria})" title="Editar">
                        <img src="assets/icons/editar.svg" alt="Editar">
                    </button>
                    <button class="btn-icon delete" onclick="categoriesManager.deleteCategory(${category.IDCategoria})" title="Eliminar">
                        <img src="assets/icons/borrar.svg" alt="Eliminar">
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    showCreateForm() {
        const formHtml = `
            <div class="category-form">
                <h4>Nueva Categoría</h4>
                <div class="form-group">
                    <input type="text" id="categoryNameInput" placeholder="Nombre de la categoría" maxlength="150">
                </div>
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="categoriesManager.cancelEdit()">Cancelar</button>
                    <button class="btn btn-primary" onclick="categoriesManager.saveCategory()">Crear</button>
                </div>
            </div>
        `;
        
        this.categoriesList.innerHTML = formHtml;
        document.getElementById('categoryNameInput').focus();
        
        // Permitir guardar con Enter
        document.getElementById('categoryNameInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.saveCategory();
            }
        });
    }
    
    editCategory(id) {
        const category = this.categories.find(cat => cat.IDCategoria == id);
        if (!category) return;
        
        this.currentEditingId = id;
        
        const formHtml = `
            <div class="category-form">
                <h4>Editar Categoría</h4>
                <div class="form-group">
                    <input type="text" id="categoryNameInput" value="${this.escapeHtml(category.Categoria)}" maxlength="150">
                </div>
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="categoriesManager.cancelEdit()">Cancelar</button>
                    <button class="btn btn-primary" onclick="categoriesManager.saveCategory()">Guardar</button>
                </div>
            </div>
        `;
        
        this.categoriesList.innerHTML = formHtml;
        const input = document.getElementById('categoryNameInput');
        input.focus();
        input.select();
        
        // Permitir guardar con Enter
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.saveCategory();
            }
        });
    }
    
    async saveCategory() {
        const nameInput = document.getElementById('categoryNameInput');
        if (!nameInput) return;
        
        const name = nameInput.value.trim();
        if (!name) {
            this.showMessage('El nombre de la categoría es requerido', 'error');
            nameInput.focus();
            return;
        }
        
        try {
            let url, method, data;
            
            if (this.currentEditingId) {
                // Editar categoría existente
                url = 'api/categorias.php?action=update';
                method = 'PUT';
                data = {
                    id: this.currentEditingId,
                    nombre: name
                };
            } else {
                // Crear nueva categoría
                url = 'api/categorias.php?action=create';
                method = 'POST';
                data = {
                    nombre: name
                };
            }
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage(result.message, 'success');
                await this.loadCategories();
                this.currentEditingId = null;
                
                // Actualizar otros componentes que usen categorías
                if (typeof window.updateCategoriesInOtherComponents === 'function') {
                    window.updateCategoriesInOtherComponents();
                }
            } else {
                this.showMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Error al guardar categoría:', error);
            this.showMessage('Error de conexión al guardar categoría', 'error');
        }
    }
    
    async deleteCategory(id) {
        const category = this.categories.find(cat => cat.IDCategoria == id);
        if (!category) return;
        
        // Confirmar eliminación
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: '¿Eliminar categoría?',
                text: `¿Estás seguro de que deseas eliminar la categoría "${category.Categoria}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            
            if (!result.isConfirmed) return;
        } else {
            if (!confirm(`¿Estás seguro de que deseas eliminar la categoría "${category.Categoria}"?`)) {
                return;
            }
        }
        
        try {
            const response = await fetch(`api/categorias.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage(result.message, 'success');
                await this.loadCategories();
                
                // Actualizar otros componentes que usen categorías
                if (typeof window.updateCategoriesInOtherComponents === 'function') {
                    window.updateCategoriesInOtherComponents();
                }
            } else {
                this.showMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Error al eliminar categoría:', error);
            this.showMessage('Error de conexión al eliminar categoría', 'error');
        }
    }
    
    cancelEdit() {
        this.currentEditingId = null;
        this.renderCategories();
    }
    
    showMessage(message, type = 'info') {
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
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Método público para obtener categorías (para uso en otros componentes)
    getCategories() {
        return this.categories;
    }
    
    // Método público para recargar categorías
    async refreshCategories() {
        await this.loadCategories();
    }
}

// Inicializar el gestor de categorías cuando el DOM esté listo
let categoriesManager;

document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar en la página de inventario
    if (document.getElementById('categoriesSidebar')) {
        categoriesManager = new CategoriesManager();
        
        // Hacer disponible globalmente para otros scripts
        window.categoriesManager = categoriesManager;
        
        // Función para que otros componentes actualicen sus categorías
        window.updateCategoriesInOtherComponents = function() {
            // Actualizar dropdown de filtros en inventario
            if (typeof loadCategories === 'function') {
                loadCategories();
            }
            
            // Actualizar categorías en el dashboard principal
            if (typeof updateCategoriesUI === 'function') {
                updateCategoriesUI();
            }
        };
    }
});