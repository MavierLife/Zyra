/**
 * Manejo de permisos y restricciones de acceso
 */

/**
 * Aplicar restricción de acceso a una página
 * @param {boolean} hasPermission - Si el usuario tiene el permiso requerido
 * @param {string} permissionName - Nombre del permiso para mostrar en el mensaje
 */
function applyPermissionRestriction(hasPermission, permissionName = 'esta sección') {
    if (!hasPermission) {
        // Aplicar efecto de opacidad al contenido principal
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('content-restricted');
        }
        
        // Crear y mostrar overlay de restricción
        createPermissionOverlay(permissionName);
    }
}

/**
 * Crear el overlay de restricción de permisos
 * @param {string} permissionName - Nombre del permiso restringido
 */
function createPermissionOverlay(permissionName) {
    // Verificar si ya existe un overlay
    if (document.querySelector('.permission-overlay')) {
        return;
    }
    
    // Crear el overlay
    const overlay = document.createElement('div');
    overlay.className = 'permission-overlay';
    
    // Crear el mensaje
    const message = document.createElement('div');
    message.className = 'permission-message';
    
    message.innerHTML = `
        <h3>No tienes acceso a esta sección.</h3>
        <p>Tu cuenta no tiene los permisos necesarios para acceder a ${permissionName}.</p>
        <div class="contact-info">
            <strong>GESTIONA TUS CONTACTOS</strong><br>
            Pídele al propietario que dé permisos.
        </div>
    `;
    
    overlay.appendChild(message);
    document.body.appendChild(overlay);
    
    // Prevenir scroll del body cuando el overlay está activo
    document.body.style.overflow = 'hidden';
}

/**
 * Remover restricción de acceso
 */
function removePermissionRestriction() {
    // Remover efecto de opacidad
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.classList.remove('content-restricted');
    }
    
    // Remover overlay
    const overlay = document.querySelector('.permission-overlay');
    if (overlay) {
        overlay.remove();
    }
    
    // Restaurar scroll del body
    document.body.style.overflow = '';
}

/**
 * Verificar permisos específicos para inventario
 */
function checkInventoryPermissions() {
    // Verificar si la variable global existe
    if (typeof window.canViewInventory !== 'undefined') {
        if (!window.canViewInventory) {
            applyPermissionRestriction(false, 'el inventario');
        }
    }
}

/**
 * Verificar permisos específicos para proveedores
 */
function checkSuppliersPermissions() {
    // Verificar permisos de creación y edición
    if (typeof window.canCreate !== 'undefined' && typeof window.canEdit !== 'undefined') {
        if (!window.canCreate && !window.canEdit) {
            applyPermissionRestriction(false, 'la gestión de proveedores');
        }
    }
}

/**
 * Verificar permisos específicos para empleados
 */
function checkEmployeesPermissions() {
    // Verificar permisos de creación y edición
    if (typeof window.canCreate !== 'undefined' && typeof window.canEdit !== 'undefined') {
        if (!window.canCreate && !window.canEdit) {
            applyPermissionRestriction(false, 'la gestión de empleados');
        }
    }
}

/**
 * Inicializar verificación de permisos según la página actual
 */
function initializePermissionCheck() {
    // Esperar a que el DOM esté completamente cargado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkPagePermissions, 100);
        });
    } else {
        setTimeout(checkPagePermissions, 100);
    }
}

/**
 * Verificar permisos según la página actual
 */
function checkPagePermissions() {
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('inventario.php')) {
        checkInventoryPermissions();
    } else if (currentPage.includes('proveedores.php')) {
        checkSuppliersPermissions();
    } else if (currentPage.includes('empleados.php')) {
        checkEmployeesPermissions();
    }
}

// Inicializar verificación de permisos
initializePermissionCheck();