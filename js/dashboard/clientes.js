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

    // Manejar cambio de tipo de cliente
    const clientTypeSelect = document.getElementById('editClientType');
    if (clientTypeSelect) {
        clientTypeSelect.addEventListener('change', handleClientTypeChange);
    }

    // Selectores de ubicación jerárquica
    const departmentSelect = document.getElementById('editClientDepartment');
    const municipalitySelect = document.getElementById('editClientMunicipality');
    const districtSelect = document.getElementById('editClientDistrict');
    
    console.log('Inicializando selectores de ubicación:', {
        departmentSelect: !!departmentSelect,
        municipalitySelect: !!municipalitySelect,
        districtSelect: !!districtSelect
    });
    
    if (departmentSelect) {
        departmentSelect.addEventListener('change', function(e) {
            console.log('Cambio en departamento detectado:', e.target.value);
            handleDepartmentChange();
        });
    }
    
    if (municipalitySelect) {
        municipalitySelect.addEventListener('change', function(e) {
            console.log('Cambio en municipio detectado:', e.target.value);
            handleMunicipalityChange();
        });
    }
    
    if (districtSelect) {
        districtSelect.addEventListener('change', function(e) {
            console.log('Cambio en distrito detectado:', e.target.value);
            handleDistrictChange();
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

// Manejar cambio de tipo de cliente
function handleClientTypeChange() {
    const clientTypeSelect = document.getElementById('editClientType');
    const contribuyenteSelect = document.getElementById('editClientContribuyente');
    
    if (!clientTypeSelect || !contribuyenteSelect) return;
    
    const selectedType = clientTypeSelect.value;
    
    if (selectedType === '1') { // Persona Jurídica
        // Marcar automáticamente como contribuyente
        contribuyenteSelect.value = '1';
        // Deshabilitar el campo para evitar modificaciones
        contribuyenteSelect.disabled = true;
        contribuyenteSelect.style.backgroundColor = '#f5f5f5';
        contribuyenteSelect.style.cursor = 'not-allowed';
    } else {
        // Para Persona Natural, habilitar la selección
        contribuyenteSelect.disabled = false;
        contribuyenteSelect.style.backgroundColor = '';
        contribuyenteSelect.style.cursor = '';
        // Mantener el valor actual o establecer por defecto a "No"
        if (contribuyenteSelect.value === '') {
            contribuyenteSelect.value = '0';
        }
    }
}

// Manejar cambio de departamento
async function handleDepartmentChange() {
    const departmentSelect = document.getElementById('editClientDepartment');
    const municipalitySelect = document.getElementById('editClientMunicipality');
    const districtSelect = document.getElementById('editClientDistrict');
    
    if (!departmentSelect || !municipalitySelect || !districtSelect) return;
    
    const selectedDepartment = departmentSelect.value;
    
    // Limpiar y deshabilitar municipio y distrito
    municipalitySelect.innerHTML = '<option value="">Seleccionar municipio</option>';
    districtSelect.innerHTML = '<option value="">Seleccionar distrito</option>';
    municipalitySelect.disabled = true;
    districtSelect.disabled = true;
    
    if (selectedDepartment) {
        try {
            const response = await fetch(`api/ubicaciones.php?action=municipios_por_departamento&departamento_id=${selectedDepartment}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                data.data.forEach(municipio => {
                    const option = document.createElement('option');
                    option.value = municipio.uuid;
                    option.textContent = municipio.nombre;
                    municipalitySelect.appendChild(option);
                });
                municipalitySelect.disabled = false;
            }
        } catch (error) {
            console.error('Error al cargar municipios:', error);
            showMessage('Error al cargar los municipios', 'error');
        }
    }
}

// Manejar cambio de municipio
async function handleMunicipalityChange() {
    const municipalitySelect = document.getElementById('editClientMunicipality');
    const districtSelect = document.getElementById('editClientDistrict');
    
    if (!municipalitySelect || !districtSelect) return;
    
    const selectedMunicipality = municipalitySelect.value;
    
    // Limpiar y deshabilitar distrito
    districtSelect.innerHTML = '<option value="">Seleccionar distrito</option>';
    districtSelect.disabled = true;
    
    if (selectedMunicipality) {
        try {
            const response = await fetch(`api/ubicaciones.php?action=distritos_por_municipio&municipio_id=${selectedMunicipality}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                data.data.forEach(distrito => {
                    const option = document.createElement('option');
                    option.value = distrito.uuid;
                    option.textContent = distrito.nombre;
                    districtSelect.appendChild(option);
                });
                districtSelect.disabled = false;
            }
        } catch (error) {
            console.error('Error al cargar distritos:', error);
            showMessage('Error al cargar los distritos', 'error');
        }
    }
}

// Manejar cambio de distrito (ya no necesita hacer nada adicional)
function handleDistrictChange() {
    // Esta función se mantiene para compatibilidad pero ya no necesita cargar datos adicionales
    console.log('Distrito seleccionado');
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
        const clientTypeSelect = document.getElementById('editClientType');
        if (clientTypeSelect) {
            clientTypeSelect.value = '0'; // Persona Natural por defecto
        }
        
        const contribuyenteSelect = document.getElementById('editClientContribuyente');
        if (contribuyenteSelect) {
            contribuyenteSelect.value = '0'; // No contribuyente por defecto
            contribuyenteSelect.disabled = false;
            contribuyenteSelect.style.backgroundColor = '';
            contribuyenteSelect.style.cursor = '';
        }
        
        // Resetear selectores de ubicación
        const districtSelect = document.getElementById('editClientDistrict');
        const municipalitySelect = document.getElementById('editClientMunicipality');
        
        if (districtSelect) {
            districtSelect.innerHTML = '<option value="">Seleccione un distrito</option>';
            districtSelect.disabled = true;
        }
        
        if (municipalitySelect) {
            municipalitySelect.innerHTML = '<option value="">Seleccione un municipio</option>';
            municipalitySelect.disabled = true;
        }
        
        // Ejecutar la lógica de tipo de cliente para configurar el estado inicial
        handleClientTypeChange();
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
    if (!client) return;
    
    // Mapear los campos del cliente a los elementos del formulario
    const fieldMappings = {
        'editClientName': 'NombreDeCliente',
        'editClientCommercialName': 'NombreComercial',
        'editClientPhone': 'Telefono',
        'editClientEmail': 'CorreoElectronico',
        'editClientAddress': 'Direccion',
        'editClientDepartment': 'Departamento',
        'editClientMunicipality': 'Municipio',
        'editClientDUI': 'DUI',
        'editClientNIT': 'NIT',
        'editClientNRC': 'NRC',
        'editClientActivity': 'CodActividad',
        'editClientCommercialTurn': 'GiroComercial',
        'editClientOtherDoc': 'OtroDocumento',
        'editClientObservations': 'Observaciones'
    };
    
    // Llenar campos básicos
    Object.keys(fieldMappings).forEach(fieldId => {
        const element = document.getElementById(fieldId);
        const clientField = fieldMappings[fieldId];
        if (element && client[clientField] !== undefined) {
            element.value = client[clientField] || '';
        }
    });
    
    // Manejar tipo de cliente (Contribuyente -> TipoDePersona)
    const clientTypeSelect = document.getElementById('editClientType');
    if (clientTypeSelect && client.Contribuyente !== undefined) {
        clientTypeSelect.value = client.Contribuyente.toString();
    }
    
    // Manejar contribuyente (siempre 1 para personas jurídicas)
    const contribuyenteSelect = document.getElementById('editClientContribuyente');
    if (contribuyenteSelect) {
        // Para personas jurídicas, siempre es contribuyente
        if (client.Contribuyente === 1) {
            contribuyenteSelect.value = '1';
        } else {
            // Para personas naturales, usar el valor real del cliente
            contribuyenteSelect.value = client.EsContribuyente ? '1' : '0';
        }
    }
    
    // Manejar checkboxes fiscales
    const perceiveIVACheckbox = document.getElementById('editClientPerceiveIVA');
    if (perceiveIVACheckbox) {
        perceiveIVACheckbox.checked = !!client.PercibirIVA;
    }
    
    const retainIVACheckbox = document.getElementById('editClientRetainIVA');
    if (retainIVACheckbox) {
        retainIVACheckbox.checked = !!client.RetenerIVA;
    }
    
    const retainRentaCheckbox = document.getElementById('editClientRetainRenta');
    if (retainRentaCheckbox) {
        retainRentaCheckbox.checked = !!client.RetenerRenta;
    }
    
    // Manejar campos de ubicación jerárquica
    handleLocationFields(client);
    
    // Ejecutar la lógica de tipo de cliente para configurar el estado correcto
    handleClientTypeChange();
}

// Manejar campos de ubicación jerárquica al editar
async function handleLocationFields(client) {
    const departmentSelect = document.getElementById('editClientDepartment');
    const districtSelect = document.getElementById('editClientDistrict');
    const municipalitySelect = document.getElementById('editClientMunicipality');
    
    if (!departmentSelect || !districtSelect || !municipalitySelect) return;
    
    try {
        // Si hay departamento seleccionado, cargar distritos
        if (client.DepartamentoUUID) {
            departmentSelect.value = client.DepartamentoUUID;
            
            const districtResponse = await fetch(`api/ubicaciones.php?action=distritos&departamento_id=${client.DepartamentoUUID}`);
            const districtData = await districtResponse.json();
            
            if (districtData.success && districtData.data) {
                districtSelect.innerHTML = '<option value="">Seleccione un distrito</option>';
                districtData.data.forEach(distrito => {
                    const option = document.createElement('option');
                    option.value = distrito.uuid;
                    option.textContent = distrito.nombre;
                    districtSelect.appendChild(option);
                });
                districtSelect.disabled = false;
                
                // Si hay distrito seleccionado, cargar municipios
                if (client.DistritoUUID) {
                    districtSelect.value = client.DistritoUUID;
                    
                    const municipalityResponse = await fetch(`api/ubicaciones.php?action=municipios&distrito_id=${client.DistritoUUID}`);
                    const municipalityData = await municipalityResponse.json();
                    
                    if (municipalityData.success && municipalityData.data) {
                        municipalitySelect.innerHTML = '<option value="">Seleccione un municipio</option>';
                        municipalityData.data.forEach(municipio => {
                            const option = document.createElement('option');
                            option.value = municipio.uuid;
                            option.textContent = municipio.nombre;
                            municipalitySelect.appendChild(option);
                        });
                        municipalitySelect.disabled = false;
                        
                        // Seleccionar el municipio actual
                        if (client.MunicipioUUID) {
                            municipalitySelect.value = client.MunicipioUUID;
                        }
                    }
                }
            }
        } else {
            // Si no hay departamento, resetear los selectores
            districtSelect.innerHTML = '<option value="">Seleccione un distrito</option>';
            municipalitySelect.innerHTML = '<option value="">Seleccione un municipio</option>';
            districtSelect.disabled = true;
            municipalitySelect.disabled = true;
        }
    } catch (error) {
        console.error('Error al cargar ubicaciones:', error);
        showMessage('Error al cargar las ubicaciones', 'error');
    }
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
        nombreCliente: formData.get('nombreDeCliente'),
        nombreComercial: formData.get('nombreComercial'),
        telefono: formData.get('telefono'),
        correoElectronico: formData.get('correoElectronico'),
        direccion: formData.get('direccion'),
        departamento: formData.get('departamento'),
        municipio: formData.get('municipio'),
        distrito: formData.get('distrito'),
        dui: formData.get('dui'),
        nit: formData.get('nit'),
        nrc: formData.get('nrc'),
        tipoPersona: formData.get('tipoPersona') || 'Natural',
        codActividad: formData.get('codActividad'),
        giro: formData.get('giroComercial'),
        otroDocumento: formData.get('otroDocumento'),
        percibirIVA: formData.get('percibirIVA') === 'on' ? 1 : 0,
        retenerIVA: formData.get('retenerIVA') === 'on' ? 1 : 0,
        retenerRenta: formData.get('retenerRenta') === 'on' ? 1 : 0,
        estado: formData.get('estado') || 'Activo',
        descuentoGeneral: formData.get('descuentoGeneral') || '0',
        limiteCredito: formData.get('limiteCredito') || '0',
        diasCredito: formData.get('diasCredito') || '0',
        vendedorAsignado: formData.get('vendedorAsignado'),
        observaciones: formData.get('observaciones')
    };
    
    // Debug: Log de datos que se envían
    console.log('Datos del cliente a enviar:', clientData);
    
    // Validaciones básicas
    if (!clientData.nombreCliente || !clientData.nombreCliente.trim()) {
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