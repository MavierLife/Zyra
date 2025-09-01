// Variables globales
let suppliers = [];
let filteredSuppliers = [];
let currentEditingSupplier = null;

// Configurar SweetAlert2
const MySwal = Swal.mixin({ zIndex: 10000 });

// Función para mostrar notificaciones
function showMessage(message, type = 'info') {
    MySwal.fire({
        text: message,
        icon: type,
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initializeSuppliers);

// Inicializar módulo
function initializeSuppliers() {
    setupEventListeners();
    loadSuppliersData();
}

// Configurar event listeners
function setupEventListeners() {
    const elements = {
        addBtn: document.getElementById('createSupplierBtn'),
        searchInput: document.getElementById('suppliersSearch'),
        statusFilter: document.getElementById('statusFilter'),
        modal: document.getElementById('editSupplierModal'),
        form: document.getElementById('editSupplierForm')
    };

    if (elements.addBtn) elements.addBtn.onclick = openAddSupplierModal;
    if (elements.searchInput) elements.searchInput.oninput = handleSupplierSearch;
    if (elements.statusFilter) elements.statusFilter.onchange = handleStatusFilter;
    
    if (elements.modal) {
        elements.modal.querySelectorAll('.close-modal').forEach(btn => btn.onclick = closeEditModal);
        elements.modal.onclick = (e) => e.target === elements.modal && closeEditModal();
    }
    
    if (elements.form) elements.form.onsubmit = (e) => { e.preventDefault(); saveSupplier(); };
    
    // Cerrar modal con Escape
    document.onkeydown = (e) => {
        if (e.key === 'Escape' && elements.modal?.style.display === 'flex') closeEditModal();
    };
}

// Cargar datos
async function loadSuppliersData() {
    try {
        showLoading(true);
        
        const [suppliersRes, statsRes] = await Promise.all([
            fetch('api/proveedores.php?action=proveedores'),
            fetch('api/proveedores.php?action=estadisticas')
        ]);
        
        const [suppliersData, statsData] = await Promise.all([
            suppliersRes.json(),
            statsRes.json()
        ]);
        
        if (suppliersData.success) {
            suppliers = suppliersData.data;
            filteredSuppliers = [...suppliers];
            renderSuppliersTable();
        }
        
        if (statsData.success) updateStatistics(statsData.data);
        
    } catch (error) {
        showMessage('Error al cargar datos', 'error');
        renderEmptyState();
    } finally {
        showLoading(false);
    }
}

// Actualizar estadísticas
function updateStatistics(stats) {
    const elements = ['totalSuppliers', 'activeSuppliers', 'newSuppliers'];
    const values = [stats.total, stats.activos, stats.nuevosEsteMes];
    
    elements.forEach((id, i) => {
        const el = document.getElementById(id);
        if (el) el.textContent = values[i] || 0;
    });
}

// Renderizar tabla
function renderSuppliersTable() {
    const tbody = document.getElementById('suppliersTableBody');
    if (!tbody) return;
    
    if (filteredSuppliers.length === 0) {
        renderEmptyState();
        return;
    }
    
    tbody.innerHTML = filteredSuppliers.map(supplier => `
        <tr>
            <td class="nunito-sans-medium">${escapeHtml(supplier.razonSocial)}</td>
            <td>${escapeHtml(supplier.celular || '-')}</td>
            <td>${escapeHtml(supplier.documento || '-')}</td>
            <td>${escapeHtml(supplier.correoElectronico)}</td>
            <td><span class="supplier-status ${supplier.estado.toLowerCase()}">${supplier.estado}</span></td>
            <td>
                <div class="table-actions">
                    <button class="action-btn edit" onclick="editSupplier('${supplier.id}')" title="Editar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="action-btn delete" onclick="deleteSupplier('${supplier.id}', '${escapeHtml(supplier.razonSocial)}')" title="Eliminar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"/>
                            <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2,2h4a2,2,0,0,1,2,2V6"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Estado vacío
function renderEmptyState() {
    const tbody = document.getElementById('suppliersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr><td colspan="6">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <h3>No hay proveedores</h3>
                <p>Comienza agregando tu primer proveedor</p>
            </div>
        </td></tr>
    `;
}

// Búsqueda
function handleSupplierSearch(event) {
    const term = event.target.value.toLowerCase().trim();
    filteredSuppliers = suppliers.filter(s => 
        s.razonSocial.toLowerCase().includes(term) ||
        (s.celular && s.celular.toLowerCase().includes(term)) ||
        (s.documento && s.documento.toLowerCase().includes(term)) ||
        s.correoElectronico.toLowerCase().includes(term)
    );
    renderSuppliersTable();
}

// Filtro de estado
function handleStatusFilter(event) {
    const status = event.target.value;
    filteredSuppliers = status === 'all' ? [...suppliers] : 
        suppliers.filter(s => s.estado.toLowerCase() === status);
    renderSuppliersTable();
}

// Abrir modal para agregar
function openAddSupplierModal() {
    currentEditingSupplier = null;
    const modal = document.getElementById('editSupplierModal');
    const form = document.getElementById('editSupplierForm');
    
    document.getElementById('modalTitle').textContent = 'Crear Proveedor';
    if (form) form.reset();
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => modal.querySelector('input')?.focus(), 100);
}

// Editar proveedor
function editSupplier(supplierId) {
    const supplier = suppliers.find(s => s.id === supplierId);
    if (!supplier) return showMessage('Proveedor no encontrado', 'error');
    
    currentEditingSupplier = supplier;
    const modal = document.getElementById('editSupplierModal');
    const form = document.getElementById('editSupplierForm');
    
    document.getElementById('modalTitle').textContent = 'Editar Proveedor';
    
    // Llenar formulario
    ['razonSocial', 'celular', 'documento', 'correoElectronico', 'direccion'].forEach(field => {
        form.elements[field].value = supplier[field] || '';
    });
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Cerrar modal
function closeEditModal() {
    const modal = document.getElementById('editSupplierModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    currentEditingSupplier = null;
    document.getElementById('editSupplierForm').reset();
}

// Guardar proveedor
async function saveSupplier() {
    const form = document.getElementById('editSupplierForm');
    const formData = new FormData(form);
    
    const data = {
        razonSocial: formData.get('razonSocial').trim(),
        celular: formData.get('celular').trim(),
        documento: formData.get('documento').trim(),
        correoElectronico: formData.get('correoElectronico').trim(),
        direccion: formData.get('direccion').trim()
    };
    
    // Validaciones
    if (!data.razonSocial) return showMessage('La razón social es requerida', 'error');
    if (!data.correoElectronico) return showMessage('El correo electrónico es requerido', 'error');
    if (!data.direccion) return showMessage('La dirección es requerida', 'error');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.correoElectronico)) {
        return showMessage('Formato de correo inválido', 'error');
    }
    
    try {
        showLoading(true);
        
        const isEditing = currentEditingSupplier !== null;
        if (isEditing) data.id = currentEditingSupplier.id;
        
        const response = await fetch('api/proveedores.php?action=proveedor', {
            method: isEditing ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(isEditing ? 'Proveedor actualizado' : 'Proveedor creado', 'success');
            closeEditModal();
            await loadSuppliersData();
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        showMessage('Error al guardar: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// Eliminar proveedor
async function deleteSupplier(supplierId, supplierName) {
    const result = await MySwal.fire({
        title: '¿Estás seguro?',
        text: `¿Eliminar "${supplierName}"? No se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        showLoading(true);
        
        const response = await fetch(`api/proveedores.php?action=proveedor&id=${supplierId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Proveedor eliminado', 'success');
            await loadSuppliersData();
        } else {
            throw new Error(data.error);
        }
        
    } catch (error) {
        showMessage('Error al eliminar: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// Indicador de carga
function showLoading(show) {
    const container = document.querySelector('.suppliers-table-container');
    if (container) container.classList.toggle('loading', show);
}

// Escapar HTML
function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}