/**
 * JavaScript para el módulo de empleados
 * Sistema Zyra - Gestión empresarial
 */

// Variables globales
let employees = [];
let currentEmployee = null;
let isEditing = false;

// Elementos del DOM
const employeesTableBody = document.getElementById('employeesTableBody');
const employeesSearch = document.getElementById('employeesSearch');
const createEmployeeBtn = document.getElementById('createEmployeeBtn');
const editEmployeeModal = document.getElementById('editEmployeeModal');
const permissionsModal = document.getElementById('permissionsModal');
const editEmployeeForm = document.getElementById('editEmployeeForm');
const permissionsForm = document.getElementById('permissionsForm');
const modalTitle = document.getElementById('modalTitle');
const permissionsEmployeeName = document.getElementById('permissionsEmployeeName');
const managePermissionsBtn = document.getElementById('managePermissionsBtn');
const savePermissionsBtn = document.getElementById('savePermissionsBtn');

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadEmployees();
    setupPermissionsSections();
});

/**
 * Configurar event listeners
 */
function initializeEventListeners() {
    // Búsqueda
    employeesSearch.addEventListener('input', debounce(handleSearch, 300));
    
    // Botón crear empleado
    createEmployeeBtn.addEventListener('click', openCreateEmployeeModal);
    
    // Formulario de empleado
    editEmployeeForm.addEventListener('submit', handleEmployeeSubmit);
    
    // Botón de permisos
    managePermissionsBtn.addEventListener('click', openPermissionsModal);
    
    // Botón guardar permisos
    savePermissionsBtn.addEventListener('click', handlePermissionsSubmit);
    
    // Cerrar modales
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', closeModals);
    });
    
    // Cerrar modal al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModals();
        }
    });
    
    // Tecla ESC para cerrar modales
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModals();
        }
    });
}

/**
 * Configurar secciones de permisos colapsables
 */
function setupPermissionsSections() {
    document.querySelectorAll('.section-header').forEach(header => {
        header.addEventListener('click', function(e) {
            if (e.target.type === 'checkbox') return;
            
            const content = this.nextElementSibling;
            const toggle = this.querySelector('.section-toggle');
            const checkbox = this.querySelector('.section-checkbox');
            
            content.classList.toggle('collapsed');
            toggle.style.transform = content.classList.contains('collapsed') ? 'rotate(-90deg)' : 'rotate(0deg)';
        });
        
        // Manejar checkbox de sección
        const sectionCheckbox = header.querySelector('.section-checkbox');
        if (sectionCheckbox) {
            sectionCheckbox.addEventListener('change', function() {
                const content = header.nextElementSibling;
                const checkboxes = content.querySelectorAll('input[type="checkbox"]');
                
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            });
        }
    });
    
    // Actualizar checkboxes de sección cuando cambian los individuales
    document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateSectionCheckbox);
    });
}

/**
 * Actualizar checkbox de sección basado en checkboxes individuales
 */
function updateSectionCheckbox() {
    const section = this.closest('.permission-section');
    const sectionCheckbox = section.querySelector('.section-checkbox');
    const individualCheckboxes = section.querySelectorAll('.permission-item input[type="checkbox"]');
    
    const checkedCount = Array.from(individualCheckboxes).filter(cb => cb.checked).length;
    const totalCount = individualCheckboxes.length;
    
    if (checkedCount === 0) {
        sectionCheckbox.checked = false;
        sectionCheckbox.indeterminate = false;
    } else if (checkedCount === totalCount) {
        sectionCheckbox.checked = true;
        sectionCheckbox.indeterminate = false;
    } else {
        sectionCheckbox.checked = false;
        sectionCheckbox.indeterminate = true;
    }
}

/**
 * Cargar empleados desde la API
 */
async function loadEmployees() {
    try {
        showLoading(true);
        const response = await fetch('api/empleados.php');
        
        if (!response.ok) {
            throw new Error('Error al cargar empleados');
        }
        
        employees = await response.json();
        renderEmployeesTable();
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar empleados', 'error');
    } finally {
        showLoading(false);
    }
}

/**
 * Renderizar tabla de empleados
 */
function renderEmployeesTable(employeesToRender = employees) {
    if (employeesToRender.length === 0) {
        employeesTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state">
                    <h3>No hay empleados registrados</h3>
                    <p>Comienza creando tu primer empleado</p>
                </td>
            </tr>
        `;
        return;
    }
    
    employeesTableBody.innerHTML = employeesToRender.map(employee => `
        <tr>
            <td class="nunito-sans-regular">${escapeHtml(employee.nombre)}</td>
            <td class="nunito-sans-regular">${escapeHtml(employee.telefono)}</td>
            <td class="nunito-sans-regular">${escapeHtml(employee.rol)}</td>
            <td>
                <span class="status-badge status-active nunito-sans-medium">${employee.estado}</span>
            </td>
            <td>
                <div class="action-buttons">
                    ${window.canEdit ? `
                        <button class="btn-edit" onclick="editEmployee('${employee.uuid}')" title="Editar">
                            <img src="assets/icons/editar.svg" alt="Editar" width="16" height="16">
                        </button>
                        <button class="btn-delete" onclick="deleteEmployee('${employee.uuid}')" title="Eliminar">
                            <img src="assets/icons/borrar.svg" alt="Eliminar" width="16" height="16">
                        </button>
                    ` : ``}
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Manejar búsqueda
 */
function handleSearch() {
    const searchTerm = employeesSearch.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        renderEmployeesTable();
        return;
    }
    
    const filteredEmployees = employees.filter(employee => 
        employee.nombre.toLowerCase().includes(searchTerm) ||
        employee.telefono.includes(searchTerm) ||
        employee.rol.toLowerCase().includes(searchTerm)
    );
    
    renderEmployeesTable(filteredEmployees);
}

/**
 * Abrir modal para crear empleado
 */
function openCreateEmployeeModal() {
    if (!window.canCreate) {
        showAlert('No tienes permisos para crear empleados', 'warning');
        return;
    }
    
    isEditing = false;
    currentEmployee = null;
    modalTitle.textContent = 'Crear Empleado';
    
    // Limpiar formulario
    editEmployeeForm.reset();
    
    // Mostrar/ocultar botón de permisos
    managePermissionsBtn.style.display = 'none';
    
    editEmployeeModal.style.display = 'flex';
}

/**
 * Editar empleado
 */
async function editEmployee(uuid) {
    if (!window.canEdit) {
        showAlert('No tienes permisos para editar empleados', 'warning');
        return;
    }
    
    try {
        const employee = employees.find(emp => emp.uuid === uuid);
        if (!employee) {
            showAlert('Empleado no encontrado', 'error');
            return;
        }
        
        isEditing = true;
        currentEmployee = employee;
        modalTitle.textContent = 'Editar Empleado';
        
        // Llenar formulario
        document.getElementById('editEmployeeName').value = employee.nombre;
        document.getElementById('editEmployeePhone').value = employee.telefono;
        document.getElementById('editEmployeeRole').value = employee.rol;
        document.getElementById('editEmployeePOS').value = employee.codPuntoVenta;
        
        // Mostrar botón de permisos
        managePermissionsBtn.style.display = 'inline-flex';
        
        editEmployeeModal.style.display = 'flex';
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar datos del empleado', 'error');
    }
}

/**
 * Manejar envío del formulario de empleado
 */
async function handleEmployeeSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(editEmployeeForm);
    const employeeData = {
        nombreUsuario: formData.get('nombreUsuario'),
        telefono: formData.get('telefono'),
        rol: formData.get('rol'),
        codPuntoVenta: formData.get('codPuntoVenta')
    };
    
    // Validaciones
    if (!employeeData.nombreUsuario.trim()) {
        showAlert('El nombre es requerido', 'warning');
        return;
    }
    
    if (!employeeData.telefono.trim()) {
        showAlert('El teléfono es requerido', 'warning');
        return;
    }
    
    if (!/^\d{8}$/.test(employeeData.telefono)) {
        showAlert('El teléfono debe tener 8 dígitos', 'warning');
        return;
    }
    
    if (!employeeData.rol) {
        showAlert('El rol es requerido', 'warning');
        return;
    }
    
    if (!employeeData.codPuntoVenta.trim()) {
        showAlert('El código de punto de venta es requerido', 'warning');
        return;
    }
    
    try {
        showLoading(true);
        
        let response;
        if (isEditing) {
            employeeData.uuid = currentEmployee.uuid;
            response = await fetch('api/empleados.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(employeeData)
            });
        } else {
            response = await fetch('api/empleados.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(employeeData)
            });
        }
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Error al procesar la solicitud');
        }
        
        showAlert(result.message, 'success');
        closeModals();
        loadEmployees();
    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    } finally {
        showLoading(false);
    }
}

/**
 * Abrir modal de permisos
 */
function openPermissionsModal() {
    if (!currentEmployee) {
        showAlert('Selecciona un empleado primero', 'warning');
        return;
    }
    
    permissionsEmployeeName.textContent = currentEmployee.nombre;
    
    // Cargar permisos actuales
    loadEmployeePermissions(currentEmployee.permisos);
    
    permissionsModal.style.display = 'flex';
}

/**
 * Gestionar permisos de empleado
 */
async function manageEmployeePermissions(uuid) {
    if (!window.canEdit) {
        showAlert('No tienes permisos para gestionar permisos de empleados', 'warning');
        return;
    }
    
    const employee = employees.find(emp => emp.uuid === uuid);
    if (!employee) {
        showAlert('Empleado no encontrado', 'error');
        return;
    }
    
    currentEmployee = employee;
    permissionsEmployeeName.textContent = employee.nombre;
    
    // Cargar permisos actuales
    loadEmployeePermissions(employee.permisos);
    
    permissionsModal.style.display = 'flex';
}

/**
 * Ver permisos de empleado (solo lectura)
 */
async function viewEmployeePermissions(uuid) {
    const employee = employees.find(emp => emp.uuid === uuid);
    if (!employee) {
        showAlert('Empleado no encontrado', 'error');
        return;
    }
    
    currentEmployee = employee;
    permissionsEmployeeName.textContent = employee.nombre;
    
    // Cargar permisos actuales
    loadEmployeePermissions(employee.permisos);
    
    // Deshabilitar todos los checkboxes
    document.querySelectorAll('#permissionsForm input[type="checkbox"]').forEach(cb => {
        cb.disabled = true;
    });
    
    // Ocultar botón de guardar
    savePermissionsBtn.style.display = 'none';
    
    permissionsModal.style.display = 'flex';
}

/**
 * Cargar permisos del empleado en el formulario
 */
function loadEmployeePermissions(permisos) {
    // Limpiar todos los checkboxes
    document.querySelectorAll('#permissionsForm input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
    });
    
    // Mostrar botón de guardar
    savePermissionsBtn.style.display = 'inline-flex';
    
    // Marcar permisos activos
    Object.keys(permisos).forEach(permiso => {
        const checkbox = document.querySelector(`input[name="${permiso}"]`);
        if (checkbox) {
            checkbox.checked = permisos[permiso] === 1;
        }
    });
    
    // Actualizar checkboxes de sección
    document.querySelectorAll('.permission-section').forEach(section => {
        const sectionCheckbox = section.querySelector('.section-checkbox');
        const individualCheckboxes = section.querySelectorAll('.permission-item input[type="checkbox"]');
        
        const checkedCount = Array.from(individualCheckboxes).filter(cb => cb.checked).length;
        const totalCount = individualCheckboxes.length;
        
        if (checkedCount === 0) {
            sectionCheckbox.checked = false;
            sectionCheckbox.indeterminate = false;
        } else if (checkedCount === totalCount) {
            sectionCheckbox.checked = true;
            sectionCheckbox.indeterminate = false;
        } else {
            sectionCheckbox.checked = false;
            sectionCheckbox.indeterminate = true;
        }
    });
}

/**
 * Manejar envío de permisos
 */
async function handlePermissionsSubmit() {
    if (!currentEmployee) {
        showAlert('No hay empleado seleccionado', 'error');
        return;
    }
    
    try {
        showLoading(true);
        
        // Recopilar permisos
        const permisos = {};
        document.querySelectorAll('#permissionsForm input[type="checkbox"][name]').forEach(checkbox => {
            permisos[checkbox.name] = checkbox.checked;
        });
        
        const response = await fetch('api/empleados.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                uuid: currentEmployee.uuid,
                action: 'permissions',
                permisos: permisos
            })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Error al actualizar permisos');
        }
        
        showAlert(result.message, 'success');
        closeModals();
        loadEmployees();
    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    } finally {
        showLoading(false);
    }
}

/**
 * Eliminar empleado
 */
async function deleteEmployee(uuid) {
    if (!window.canEdit) {
        showAlert('No tienes permisos para eliminar empleados', 'warning');
        return;
    }
    
    const employee = employees.find(emp => emp.uuid === uuid);
    if (!employee) {
        showAlert('Empleado no encontrado', 'error');
        return;
    }
    
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar al empleado "${employee.nombre}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) {
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch(`api/empleados.php?uuid=${uuid}`, {
            method: 'DELETE'
        });
        
        const responseData = await response.json();
        
        if (!response.ok) {
            throw new Error(responseData.error || 'Error al eliminar empleado');
        }
        
        showAlert(responseData.message, 'success');
        loadEmployees();
    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    } finally {
        showLoading(false);
    }
}

/**
 * Cerrar modales
 */
function closeModals() {
    editEmployeeModal.style.display = 'none';
    permissionsModal.style.display = 'none';
    currentEmployee = null;
    isEditing = false;
}

/**
 * Mostrar/ocultar indicador de carga
 */
function showLoading(show) {
    const tableContainer = document.querySelector('.employees-table-container');
    if (show) {
        tableContainer.classList.add('loading');
    } else {
        tableContainer.classList.remove('loading');
    }
}

/**
 * Mostrar alerta
 */
function showAlert(message, type = 'info') {
    const iconMap = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    Swal.fire({
        title: type === 'success' ? '¡Éxito!' : type === 'error' ? 'Error' : type === 'warning' ? 'Advertencia' : 'Información',
        text: message,
        icon: iconMap[type] || 'info',
        confirmButtonColor: '#007bff'
    });
}

/**
 * Escapar HTML para prevenir XSS
 */
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

/**
 * Debounce function
 */
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