/**
 * JavaScript para el módulo de clientes
 * Sistema Zyra - Gestión empresarial
 */

// Variables globales
let clients = [];
let filteredClients = [];
let currentClient = null;
let isEditing = false;

// Permisos del usuario (se establecen desde PHP)
let userPermissions = {
    canCreate: window.canCreateClients || false,
    canEdit: window.canEditClients || false,
    canDelete: window.canDeleteClients || false
};

// Elementos del DOM
const searchInput = document.getElementById('clientsSearch');
const clientsTableBody = document.getElementById('clientsTableBody');
const createClientBtn = document.getElementById('createClientBtn');
const clientModal = document.getElementById('editClientModal');
const clientForm = document.getElementById('editClientForm');
const closeModalBtns = document.querySelectorAll('.close-modal');
const modalTitle = document.getElementById('modalTitle');
const saveClientBtn = document.getElementById('saveClientBtn');

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadClients();
    setupPermissions();
});

// Event Listeners
function initializeEventListeners() {
    // Búsqueda
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // Crear cliente: siempre enlazar el click y validar permisos dentro de la función
    if (createClientBtn) {
        createClientBtn.addEventListener('click', openCreateModal);
    }

    // Cerrar modal
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    // Cerrar modal al hacer clic fuera
    if (clientModal) {
        clientModal.addEventListener('click', function(e) {
            if (e.target === clientModal) {
                closeModal();
            }
        });
    }

    // Guardar cliente
    if (saveClientBtn) {
        saveClientBtn.addEventListener('click', handleSaveClient);
    }

    // Envío del formulario
    if (clientForm) {
        clientForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleSaveClient();
        });
    }
}

// Cargar clientes
async function loadClients() {
    try {
        showLoading();
        const response = await fetch('api/clientes.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            clients = data.data || [];
            filteredClients = [...clients];
            renderClients();
        } else {
            showMessage('Error al cargar los clientes: ' + (data.message || 'Error desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error loading clients:', error);
        showMessage('Error al cargar los clientes. Por favor, intente nuevamente.', 'error');
    } finally {
        hideLoading();
    }
}

// Renderizar clientes
function renderClients() {
    if (!clientsTableBody) return;

    if (filteredClients.length === 0) {
        clientsTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <h3>No hay clientes registrados</h3>
                    <p>Comience agregando su primer cliente</p>
                </td>
            </tr>
        `;
        return;
    }

    clientsTableBody.innerHTML = filteredClients.map(client => `
        <tr>
            <td>${escapeHtml(client.NombreDeCliente || '')}</td>
            <td>${escapeHtml(client.Telefono || '')}</td>
            <td>${escapeHtml(client.CorreoElectronico || '')}</td>
            <td>${escapeHtml(client.DUI || '')}</td>
            <td>${escapeHtml(client.NIT || '')}</td>
            <td>
                <span class="client-type-badge client-type-${client.TipoDePersona === 'Natural' ? 'natural' : 'juridica'}">
                    ${escapeHtml(client.TipoDePersona || '')}
                </span>
            </td>
            <td>
                <span class="status-badge status-${client.Estado === 'Activo' ? 'active' : 'inactive'}">
                    ${escapeHtml(client.Estado || '')}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-edit" 
                            onclick="openEditModal('${client.UUIDCliente}')" 
                            title="${userPermissions.canEdit ? 'Editar cliente' : 'No tiene permisos para editar clientes'}"
                            ${!userPermissions.canEdit ? 'disabled' : ''}>
                        ✏️
                    </button>
                    <button class="btn-delete" 
                            onclick="deleteClient('${client.UUIDCliente}')" 
                            title="${userPermissions.canDelete ? 'Eliminar cliente' : 'No tiene permisos para eliminar clientes'}"
                            ${!userPermissions.canDelete ? 'disabled' : ''}>
                        🗑️
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Manejar búsqueda
function handleSearch() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (!searchTerm) {
        filteredClients = [...clients];
    } else {
        filteredClients = clients.filter(client => 
            (client.NombreDeCliente || '').toLowerCase().includes(searchTerm) ||
            (client.Telefono || '').toLowerCase().includes(searchTerm) ||
            (client.CorreoElectronico || '').toLowerCase().includes(searchTerm) ||
            (client.DUI || '').toLowerCase().includes(searchTerm) ||
            (client.NIT || '').toLowerCase().includes(searchTerm)
        );
    }
    
    renderClients();
}

// Abrir modal para crear cliente
function openCreateModal() {
    if (!userPermissions.canCreate) {
        showAlert('No tiene permisos para crear clientes', 'error');
        return;
    }
    
    currentClient = null;
    isEditing = false;
    
    if (modalTitle) {
        modalTitle.textContent = 'Crear Nuevo Cliente';
    }
    
    if (clientForm) {
        clientForm.reset();
        // Establecer valores por defecto
        const estadoSelect = document.getElementById('estado');
        if (estadoSelect) {
            estadoSelect.value = 'Activo';
        }
        
        const tipoPersonaSelect = document.getElementById('tipoPersona');
        if (tipoPersonaSelect) {
            tipoPersonaSelect.value = 'Natural';
        }
    }
    
    if (clientModal) {
        clientModal.style.display = 'flex';
    }
}

// Abrir modal para editar cliente
function openEditModal(clientId) {
    if (!userPermissions.canEdit) {
        showAlert('No tiene permisos para editar clientes', 'error');
        return;
    }
    
    const client = clients.find(c => c.UUIDCliente === clientId);
    if (!client) {
        showAlert('Cliente no encontrado', 'error');
        return;
    }
    
    currentClient = client;
    isEditing = true;
    
    if (modalTitle) {
        modalTitle.textContent = 'Editar Cliente';
    }
    
    // Llenar el formulario con los datos del cliente
    populateForm(client);
    
    if (clientModal) {
        clientModal.style.display = 'flex';
    }
}

// Llenar formulario con datos del cliente
function populateForm(client) {
    const fields = [
        'nombreCliente', 'telefono', 'correoElectronico', 'direccion',
        'dui', 'nit', 'nrc', 'tipoPersona', 'giro', 'estado',
        'descuentoGeneral', 'limiteCredito', 'diasCredito',
        'vendedorAsignado', 'observaciones'
    ];
    
    fields.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element && client) {
            const fieldMap = {
                'nombreCliente': 'NombreDeCliente',
                'telefono': 'Telefono',
                'correoElectronico': 'CorreoElectronico',
                'direccion': 'Direccion',
                'dui': 'DUI',
                'nit': 'NIT',
                'nrc': 'NRC',
                'tipoPersona': 'TipoDePersona',
                'giro': 'Giro',
                'estado': 'Estado',
                'descuentoGeneral': 'DescuentoGeneral',
                'limiteCredito': 'LimiteDeCredito',
                'diasCredito': 'DiasDeCredito',
                'vendedorAsignado': 'VendedorAsignado',
                'observaciones': 'Observaciones'
            };
            
            const clientField = fieldMap[fieldId];
            if (clientField && client[clientField] !== undefined) {
                element.value = client[clientField] || '';
            }
        }
    });
}

// Cerrar modal
function closeModal() {
    if (clientModal) {
        clientModal.style.display = 'none';
    }
    
    currentClient = null;
    isEditing = false;
    
    if (clientForm) {
        clientForm.reset();
    }
}

// Manejar guardado de cliente
async function handleSaveClient() {
    if (!clientForm) return;
    
    const formData = new FormData(clientForm);
    const clientData = {
        nombreCliente: formData.get('nombreCliente'),
        telefono: formData.get('telefono'),
        correoElectronico: formData.get('correoElectronico'),
        direccion: formData.get('direccion'),
        dui: formData.get('dui'),
        nit: formData.get('nit'),
        nrc: formData.get('nrc'),
        tipoPersona: formData.get('tipoPersona'),
        giro: formData.get('giro'),
        estado: formData.get('estado'),
        descuentoGeneral: formData.get('descuentoGeneral') || '0',
        limiteCredito: formData.get('limiteCredito') || '0',
        diasCredito: formData.get('diasCredito') || '0',
        vendedorAsignado: formData.get('vendedorAsignado'),
        observaciones: formData.get('observaciones')
    };
    
    // Validaciones básicas
    if (!clientData.nombreCliente.trim()) {
        showMessage('El nombre del cliente es requerido', 'error');
        return;
    }
    
    try {
        showLoading();
        
        const url = 'api/clientes.php' + (isEditing ? `?id=${currentClient.UUIDCliente}` : '');
        const method = isEditing ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(clientData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(
                isEditing ? 'Cliente actualizado exitosamente' : 'Cliente creado exitosamente',
                'success'
            );
            closeModal();
            await loadClients();
        } else {
            showMessage('Error al guardar el cliente: ' + (data.message || 'Error desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error saving client:', error);
        showMessage('Error al guardar el cliente. Por favor, intente nuevamente.', 'error');
    } finally {
        hideLoading();
    }
}

// Eliminar cliente
async function deleteClient(clientId) {
    if (!userPermissions.canDelete) {
        showMessage('No tiene permisos para eliminar clientes', 'error');
        return;
    }
    
    const client = clients.find(c => c.UUIDCliente === clientId);
    if (!client) {
        showMessage('Cliente no encontrado', 'error');
        return;
    }
    
    const result = await MySwal.fire({
        title: '¿Está seguro?',
        text: `¿Desea eliminar al cliente "${client.NombreDeCliente}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            showLoading();
            
            const response = await fetch(`api/clientes.php?id=${clientId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                showMessage('Cliente eliminado exitosamente', 'success');
                await loadClients();
            } else {
                showMessage('Error al eliminar el cliente: ' + (data.message || 'Error desconocido'), 'error');
            }
        } catch (error) {
            console.error('Error deleting client:', error);
            showMessage('Error al eliminar el cliente. Por favor, intente nuevamente.', 'error');
        } finally {
            hideLoading();
        }
    }
}

// Utilidades
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showLoading() {
    if (saveClientBtn) {
        saveClientBtn.disabled = true;
        saveClientBtn.textContent = 'Guardando...';
    }
}

function hideLoading() {
    if (saveClientBtn) {
        saveClientBtn.disabled = false;
        saveClientBtn.textContent = 'Guardar Cliente';
    }
}

// Configurar SweetAlert2
const MySwal = Swal.mixin({ zIndex: 10000 });

// Función para mostrar notificaciones con toast (consistente con otros módulos)
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

// Mantener showAlert para compatibilidad con confirmaciones
function showAlert(message, type = 'info') {
    const iconMap = {
        'success': 'success',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };
    
    MySwal.fire({
        title: type === 'success' ? '¡Éxito!' : type === 'error' ? '¡Error!' : '¡Atención!',
        text: message,
        icon: iconMap[type] || 'info',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#007bff'
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Configurar permisos y UI según permisos del usuario
function setupPermissions() {
    // Actualizar permisos desde las variables globales ya definidas por PHP
    userPermissions.canCreate = !!window.canCreateClients;
    userPermissions.canEdit = !!window.canEditClients;
    userPermissions.canDelete = !!window.canDeleteClients;

    // Si el botón existe y no hay permisos, mantener feedback visual (el header ya maneja disabled cuando no hay permiso)
    if (createClientBtn && !userPermissions.canCreate) {
        createClientBtn.title = 'No tiene permisos para crear clientes';
    }

    // Los botones de editar y eliminar se manejan dinámicamente en renderClients() usando userPermissions
}

// Exponer funciones globalmente para uso en HTML
window.openEditModal = openEditModal;
window.deleteClient = deleteClient;