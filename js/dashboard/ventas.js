/**
 * Módulo de Ventas - Modal de Cobro
 * Maneja toda la lógica relacionada con el procesamiento de ventas
 * y el modal de cobro
 */

// ===== VARIABLES GLOBALES DEL MÓDULO DE VENTAS =====
let currentSaleData = null;

// ===== FUNCIONES PRINCIPALES DEL MODAL DE COBRO =====

/**
 * Mostrar modal de cobro de venta
 * Se ejecuta cuando el usuario presiona "Continuar" en el carrito
 */
async function showSaleModal() {
    const modal = document.getElementById('saleModal');
    if (!modal) {
        console.error('Modal de venta no encontrado');
        return;
    }
    
    // Validar que existe el carrito global
    if (typeof cart === 'undefined' || !Array.isArray(cart) || cart.length === 0) {
        showTemporaryMessage('No hay productos en el carrito', 'error');
        return;
    }
    
    // Calcular total del carrito
    const total = cart.reduce((sum, item) => {
        const price = item.currentPrice || item.precio || 0;
        const quantity = item.quantity || 0;
        return sum + (price * quantity);
    }, 0);
    
    // Validar que el total sea válido
    if (total <= 0) {
        showTemporaryMessage('El total de la venta debe ser mayor a cero', 'error');
        return;
    }
    
    // Mostrar indicador de carga mientras se obtiene el número de documento
    if (typeof showLoadingIndicator === 'function') {
        showLoadingIndicator();
    }
    
    try {
        // Obtener número de documento desde el servidor
        const documentNumber = await getDocumentNumberFromServer();
        
        // Actualizar información del modal
        updateModalDisplay(documentNumber, total);
        
        // Resetear campos de pago
        resetPaymentFields();
        
        // Guardar datos de la venta actual
        currentSaleData = {
            documentNumber: documentNumber,
            total: total,
            items: [...cart],
            documentType: 'factura'
        };
        
        // Configurar event listeners del modal
        setupSaleModalEventListeners();
        
        // Resetear búsqueda de cliente
        resetClientSearch();
        
        // Mostrar modal con animación
        displayModal(modal);
        
    } catch (error) {
        console.error('Error al obtener número de documento:', error);
        showTemporaryMessage('Error al generar número de documento: ' + error.message, 'error');
    } finally {
        if (typeof hideLoadingIndicator === 'function') {
            hideLoadingIndicator();
        }
    }
}

/**
 * Cerrar modal de cobro de venta
 */
function closeSaleModal() {
    const modal = document.getElementById('saleModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        currentSaleData = null;
        
        // Limpiar event listeners para evitar memory leaks
        cleanupSaleModalEventListeners();
    }
}

// ===== FUNCIONES DE CONFIGURACIÓN Y UTILIDADES =====

/**
 * Actualizar la información mostrada en el modal
 */
function updateModalDisplay(documentNumber, total) {
    const documentIdElement = document.getElementById('documentId');
    const documentTotalElement = document.getElementById('documentTotal');
    
    if (documentIdElement) {
        documentIdElement.textContent = documentNumber;
    }
    
    if (documentTotalElement) {
        const symbol = (typeof currencySymbol !== 'undefined') ? currencySymbol : '$';
        documentTotalElement.textContent = `${symbol}${total.toFixed(2)}`;
    }
}

/**
 * Resetear campos de pago a valores por defecto
 */
function resetPaymentFields() {
    const efectivoInput = document.getElementById('efectivoRecibido');
    const cambioInput = document.getElementById('cambio');
    
    if (efectivoInput) efectivoInput.value = '0';
    if (cambioInput) cambioInput.value = '0';
}

/**
 * Mostrar modal con animación
 */
function displayModal(modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Enfocar el campo de efectivo recibido
    setTimeout(() => {
        const efectivoInput = document.getElementById('efectivoRecibido');
        if (efectivoInput) {
            efectivoInput.focus();
            efectivoInput.select();
        }
    }, 300);
}

/**
 * Configurar event listeners del modal
 */
function setupSaleModalEventListeners() {
    // Campo de efectivo recibido
    const efectivoInput = document.getElementById('efectivoRecibido');
    if (efectivoInput) {
        efectivoInput.addEventListener('input', calculateChange);
        efectivoInput.addEventListener('keypress', handleEnterKeyPress);
    }
    
    // Botón de procesar
    const processBtn = document.getElementById('processBtn');
    if (processBtn) {
        processBtn.addEventListener('click', processSale);
    }
    
    // Botón de cancelar
    const cancelBtn = document.getElementById('cancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeSaleModal);
    }
    
    // Selector de tipo de documento
    const documentTypeSelect = document.getElementById('documentType');
    if (documentTypeSelect) {
        documentTypeSelect.addEventListener('change', updateDocumentType);
    }
    
    // Funcionalidad de búsqueda de clientes
    setupClientSearch();
    
    // Botones de acción (contacto, crédito, múltiple)
    setupActionButtons();
}

/**
 * Limpiar event listeners para evitar memory leaks
 */
function cleanupSaleModalEventListeners() {
    const efectivoInput = document.getElementById('efectivoRecibido');
    if (efectivoInput) {
        efectivoInput.removeEventListener('input', calculateChange);
        efectivoInput.removeEventListener('keypress', handleEnterKeyPress);
    }
    
    const processBtn = document.getElementById('processBtn');
    if (processBtn) {
        processBtn.removeEventListener('click', processSale);
    }
    
    const cancelBtn = document.getElementById('cancelBtn');
    if (cancelBtn) {
        cancelBtn.removeEventListener('click', closeSaleModal);
    }
    
    const documentTypeSelect = document.getElementById('documentType');
    if (documentTypeSelect) {
        documentTypeSelect.removeEventListener('change', updateDocumentType);
    }
}

/**
 * Configurar botones de acción del modal
 */
function setupActionButtons() {
    const contactBtn = document.getElementById('contactBtn');
    const creditBtn = document.getElementById('creditBtn');
    const multipleBtn = document.getElementById('multipleBtn');
    
    if (contactBtn) {
        contactBtn.addEventListener('click', () => {
            showTemporaryMessage('Función de contacto en desarrollo', 'info');
        });
    }
    
    if (creditBtn) {
        creditBtn.addEventListener('click', () => {
            showTemporaryMessage('Función de crédito en desarrollo', 'info');
        });
    }
    
    if (multipleBtn) {
        multipleBtn.addEventListener('click', () => {
            showTemporaryMessage('Función de pago múltiple en desarrollo', 'info');
        });
    }
}

// ===== FUNCIONES DE CÁLCULO Y VALIDACIÓN =====

/**
 * Calcular cambio basado en el efectivo recibido
 */
function calculateChange() {
    if (!currentSaleData) return;
    
    const efectivoRecibido = parseFloat(document.getElementById('efectivoRecibido').value) || 0;
    const total = currentSaleData.total;
    const cambio = Math.max(0, efectivoRecibido - total);
    
    // Actualizar campo de cambio
    const cambioInput = document.getElementById('cambio');
    if (cambioInput) {
        cambioInput.value = cambio.toFixed(2);
    }
    
    // Actualizar estado del botón de procesar
    updateProcessButtonState(efectivoRecibido >= total);
}

/**
 * Actualizar estado del botón de procesar
 */
function updateProcessButtonState(isEnabled) {
    const processBtn = document.getElementById('processBtn');
    if (processBtn) {
        processBtn.disabled = !isEnabled;
        processBtn.style.opacity = isEnabled ? '1' : '0.6';
        processBtn.style.cursor = isEnabled ? 'pointer' : 'not-allowed';
    }
}

/**
 * Manejar presión de tecla Enter en campos de entrada
 */
function handleEnterKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const processBtn = document.getElementById('processBtn');
        if (processBtn && !processBtn.disabled) {
            processSale();
        }
    }
}

/**
 * Actualizar tipo de documento
 */
function updateDocumentType() {
    if (!currentSaleData) return;
    
    const documentType = document.getElementById('documentType').value;
    currentSaleData.documentType = documentType;
    
    // Actualizar texto del estado según el tipo de documento
    const statusElement = document.querySelector('.document-status');
    if (statusElement) {
        const statusTexts = {
            'factura': 'VENTA POR DESPACHO',
            'credito': 'CREDITO FISCAL',
            'nota': 'NOTA DE CREDITO',
            'sujeto excluido': 'SUJETO EXCLUIDO'
        };
        statusElement.textContent = statusTexts[documentType] || 'VENTA POR DESPACHO';
    }
}

// ===== FUNCIONES DE PROCESAMIENTO DE VENTA =====

/**
 * Procesar venta - función principal
 */
async function processSale() {
    if (!currentSaleData) {
        showTemporaryMessage('Error: Datos de venta no disponibles', 'error');
        return;
    }
    
    const efectivoRecibido = parseFloat(document.getElementById('efectivoRecibido').value) || 0;
    
    // Validaciones
    if (!validateSaleData(efectivoRecibido)) {
        return;
    }
    
    // Mostrar indicador de carga
    if (typeof showLoadingIndicator === 'function') {
        showLoadingIndicator();
    }
    
    try {
        // Preparar datos para enviar al backend
        const saleData = prepareSaleData(efectivoRecibido);
        
        // Enviar al backend
        const result = await sendSaleToBackend(saleData);
        
        // Procesar respuesta exitosa
        await handleSuccessfulSale(result);
        
    } catch (error) {
        console.error('Error al procesar venta:', error);
        let errorMessage = 'Error al procesar la venta: ' + error.message;
        
        // Si el error tiene más detalles, mostrarlos en consola
        if (error.details) {
            console.error('Detalles del error:', error.details);
            errorMessage += '\nRevisa la consola para más detalles.';
        }
        
        showTemporaryMessage(errorMessage, 'error');
    } finally {
        if (typeof hideLoadingIndicator === 'function') {
            hideLoadingIndicator();
        }
    }
}

/**
 * Validar datos de la venta antes del procesamiento
 */
function validateSaleData(efectivoRecibido) {
    if (efectivoRecibido < currentSaleData.total) {
        showTemporaryMessage('El efectivo recibido debe ser mayor o igual al total', 'error');
        return false;
    }
    
    if (!currentSaleData.items || currentSaleData.items.length === 0) {
        showTemporaryMessage('No hay productos en la venta', 'error');
        return false;
    }
    
    if (typeof currentVendedorId === 'undefined' || !currentVendedorId) {
        showTemporaryMessage('Error: Vendedor no identificado', 'error');
        return false;
    }
    
    return true;
}

/**
 * Preparar datos para enviar al backend
 */
function prepareSaleData(efectivoRecibido) {
    const clientData = getSelectedClient();
    
    return {
        vendedorId: currentVendedorId,
        documentType: currentSaleData.documentType,
        documentNumber: currentSaleData.documentNumber,
        items: currentSaleData.items.map(item => ({
            id: item.id,
            nombre: item.nombre || item.name || 'Producto',
            quantity: item.quantity,
            currentPrice: item.currentPrice || item.precio || 0
        })),
        total: currentSaleData.total,
        efectivoRecibido: efectivoRecibido,
        cambio: efectivoRecibido - currentSaleData.total,
        cliente: clientData ? {
            id: clientData.UUIDCliente,
            nombre: clientData.NombreDeCliente,
            telefono: clientData.Telefono,
            email: clientData.CorreoElectronico,
            nit: clientData.NIT,
            dui: clientData.DUI
        } : null,
        timestamp: new Date().toISOString()
    };
}

/**
 * Enviar datos de venta al backend
 */
async function sendSaleToBackend(saleData) {
    console.log('Enviando datos de venta:', saleData);
    
    const response = await fetch('api/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
    });
    
    console.log('Respuesta del servidor:', response.status, response.statusText);
    
    let result;
    try {
        const responseText = await response.text();
        console.log('Texto de respuesta:', responseText);
        result = JSON.parse(responseText);
    } catch (parseError) {
        console.error('Error al parsear JSON:', parseError);
        console.error('Respuesta del servidor:', await response.text());
        throw new Error('Error al procesar respuesta del servidor');
    }
    
    if (!response.ok) {
        console.error('Error del servidor:', result);
        const error = new Error(`Error HTTP: ${response.status} - ${result.error || 'Error desconocido'}`);
        error.details = result;
        throw error;
    }
    
    if (!result.success) {
        console.error('Error en la respuesta:', result);
        const error = new Error(result.error || result.message || 'Error desconocido');
        error.details = result;
        throw error;
    }
    
    return result;
}

/**
 * Manejar venta exitosa
 */
async function handleSuccessfulSale(result) {
    // Mostrar mensaje de éxito
    showTemporaryMessage('¡Venta procesada exitosamente!', 'success');
    
    // Limpiar carrito global
    if (typeof cart !== 'undefined' && typeof updateCartDisplay === 'function') {
        cart.length = 0; // Limpiar array manteniendo la referencia
        updateCartDisplay();
    }
    
    // Cerrar modal
    closeSaleModal();
    
    // Opcional: Mostrar opción de imprimir ticket
    setTimeout(() => {
        if (confirm('¿Desea imprimir el ticket de venta?')) {
            printSaleTicket(result.saleId);
        }
    }, 500);
}

// ===== FUNCIONES AUXILIARES =====

/**
 * Obtener número de documento desde el servidor
 */
async function getDocumentNumberFromServer() {
    try {
        const response = await fetch('api/documento.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error al obtener número de documento');
        }
        
        return result.documentNumber;
        
    } catch (error) {
        console.error('Error al obtener número de documento:', error);
        throw error;
    }
}

/**
 * Imprimir ticket de venta (función placeholder)
 */
function printSaleTicket(saleId) {
    console.log('Imprimiendo ticket para venta:', saleId);
    showTemporaryMessage('Función de impresión en desarrollo', 'info');
    
    // Aquí se implementaría la lógica real de impresión
    // Por ejemplo, abrir una nueva ventana con el ticket formateado
    // window.open(`print-ticket.php?saleId=${saleId}`, '_blank');
}

/**
 * Mostrar mensaje temporal (fallback si no existe en dashboard.js)
 */
function showTemporaryMessage(message, type = 'info') {
    // Verificar si existe una función global diferente a esta misma
    if (typeof window.showTemporaryMessage === 'function' && window.showTemporaryMessage !== showTemporaryMessage) {
        window.showTemporaryMessage(message, type);
        return;
    }
    
    // Fallback simple - crear notificación visual
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: Arial, sans-serif;
        font-size: 14px;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

// ===== FUNCIONALIDAD DE BÚSQUEDA DE CLIENTES =====

// Variables para el autocompletado de clientes
let clientSearchTimeout = null;
let currentClientSuggestions = [];
let selectedClientIndex = -1;
let selectedClient = null;

/**
 * Configurar la funcionalidad de búsqueda de clientes
 */
function setupClientSearch() {
    const clientSearchInput = document.getElementById('clientSearch');
    const clientSuggestions = document.getElementById('clientSuggestions');
    const clearClientBtn = document.getElementById('clearClientBtn');
    
    if (!clientSearchInput || !clientSuggestions) {
        console.warn('Elementos de búsqueda de cliente no encontrados');
        return;
    }
    
    // Event listeners para el campo de búsqueda
    clientSearchInput.addEventListener('input', handleClientSearchInput);
    clientSearchInput.addEventListener('keydown', handleClientSearchKeydown);
    clientSearchInput.addEventListener('focus', handleClientSearchFocus);
    clientSearchInput.addEventListener('blur', handleClientSearchBlur);
    
    // Event listener para limpiar cliente seleccionado
    if (clearClientBtn) {
        clearClientBtn.addEventListener('click', clearSelectedClient);
    }
    
    // Resetear estado inicial
    resetClientSearch();
}

/**
 * Manejar entrada de texto en el campo de búsqueda
 */
function handleClientSearchInput(event) {
    const query = event.target.value.trim();
    
    // Limpiar timeout anterior
    if (clientSearchTimeout) {
        clearTimeout(clientSearchTimeout);
    }
    
    // Si el campo está vacío, ocultar sugerencias
    if (query.length === 0) {
        hideClientSuggestions();
        return;
    }
    
    // Buscar con debounce
    clientSearchTimeout = setTimeout(() => {
        searchClients(query);
    }, 300);
}

/**
 * Manejar teclas especiales en el campo de búsqueda
 */
function handleClientSearchKeydown(event) {
    const suggestions = document.getElementById('clientSuggestions');
    
    if (!suggestions || suggestions.style.display === 'none') {
        return;
    }
    
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            navigateClientSuggestions(1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            navigateClientSuggestions(-1);
            break;
        case 'Enter':
            event.preventDefault();
            selectHighlightedClient();
            break;
        case 'Escape':
            event.preventDefault();
            hideClientSuggestions();
            break;
    }
}

/**
 * Manejar focus en el campo de búsqueda
 */
function handleClientSearchFocus(event) {
    const query = event.target.value.trim();
    if (query.length > 0 && currentClientSuggestions.length > 0) {
        showClientSuggestions();
    }
}

/**
 * Manejar blur en el campo de búsqueda
 */
function handleClientSearchBlur(event) {
    // Delay para permitir clicks en sugerencias
    setTimeout(() => {
        hideClientSuggestions();
    }, 200);
}

/**
 * Buscar clientes en el servidor
 */
async function searchClients(query) {
    const clientSearchInput = document.getElementById('clientSearch');
    
    try {
        // Mostrar indicador de carga
        clientSearchInput.classList.add('loading');
        
        const response = await fetch(`api/clientes.php?search=${encodeURIComponent(query)}&limit=5`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            currentClientSuggestions = result.data;
            displayClientSuggestions(currentClientSuggestions);
        } else {
            currentClientSuggestions = [];
            displayNoResults();
        }
        
    } catch (error) {
        console.error('Error al buscar clientes:', error);
        currentClientSuggestions = [];
        displayNoResults();
    } finally {
        clientSearchInput.classList.remove('loading');
    }
}

/**
 * Mostrar sugerencias de clientes
 */
function displayClientSuggestions(clients) {
    const suggestions = document.getElementById('clientSuggestions');
    const clientSearchInput = document.getElementById('clientSearch');
    
    if (!suggestions || !clients || clients.length === 0) {
        displayNoResults();
        return;
    }
    
    suggestions.innerHTML = '';
    selectedClientIndex = -1;
    
    clients.forEach((client, index) => {
        const suggestionElement = createClientSuggestionElement(client, index);
        suggestions.appendChild(suggestionElement);
    });
    
    showClientSuggestions();
    clientSearchInput.classList.add('has-suggestions');
}

/**
 * Crear elemento de sugerencia de cliente
 */
function createClientSuggestionElement(client, index) {
    const div = document.createElement('div');
    div.className = 'client-suggestion';
    div.dataset.index = index;
    
    const nameDiv = document.createElement('div');
    nameDiv.className = 'client-suggestion-name';
    nameDiv.textContent = client.NombreDeCliente || 'Sin nombre';
    
    const detailsDiv = document.createElement('div');
    detailsDiv.className = 'client-suggestion-details';
    
    if (client.Telefono) {
        const phoneSpan = document.createElement('span');
        phoneSpan.className = 'client-suggestion-phone';
        phoneSpan.textContent = `📞 ${client.Telefono}`;
        detailsDiv.appendChild(phoneSpan);
    }
    
    if (client.CorreoElectronico) {
        const emailSpan = document.createElement('span');
        emailSpan.className = 'client-suggestion-email';
        emailSpan.textContent = `✉️ ${client.CorreoElectronico}`;
        detailsDiv.appendChild(emailSpan);
    }
    
    div.appendChild(nameDiv);
    if (detailsDiv.children.length > 0) {
        div.appendChild(detailsDiv);
    }
    
    // Event listeners
    div.addEventListener('click', () => selectClient(client));
    div.addEventListener('mouseenter', () => highlightClientSuggestion(index));
    
    return div;
}

/**
 * Mostrar mensaje cuando no hay resultados
 */
function displayNoResults() {
    const suggestions = document.getElementById('clientSuggestions');
    const clientSearchInput = document.getElementById('clientSearch');
    
    if (!suggestions) return;
    
    suggestions.innerHTML = '<div class="no-results">No se encontraron clientes</div>';
    showClientSuggestions();
    clientSearchInput.classList.add('has-suggestions');
}

/**
 * Mostrar panel de sugerencias
 */
function showClientSuggestions() {
    const suggestions = document.getElementById('clientSuggestions');
    if (suggestions) {
        suggestions.style.display = 'block';
    }
}

/**
 * Ocultar panel de sugerencias
 */
function hideClientSuggestions() {
    const suggestions = document.getElementById('clientSuggestions');
    const clientSearchInput = document.getElementById('clientSearch');
    
    if (suggestions) {
        suggestions.style.display = 'none';
    }
    
    if (clientSearchInput) {
        clientSearchInput.classList.remove('has-suggestions');
    }
    
    selectedClientIndex = -1;
}

/**
 * Navegar por las sugerencias con teclado
 */
function navigateClientSuggestions(direction) {
    if (currentClientSuggestions.length === 0) return;
    
    const previousIndex = selectedClientIndex;
    selectedClientIndex += direction;
    
    // Circular navigation
    if (selectedClientIndex < 0) {
        selectedClientIndex = currentClientSuggestions.length - 1;
    } else if (selectedClientIndex >= currentClientSuggestions.length) {
        selectedClientIndex = 0;
    }
    
    // Update visual highlighting
    updateClientSuggestionHighlight(previousIndex, selectedClientIndex);
}

/**
 * Actualizar resaltado visual de sugerencias
 */
function updateClientSuggestionHighlight(previousIndex, currentIndex) {
    const suggestions = document.querySelectorAll('.client-suggestion');
    
    // Remove previous highlight
    if (previousIndex >= 0 && suggestions[previousIndex]) {
        suggestions[previousIndex].classList.remove('highlighted');
    }
    
    // Add current highlight
    if (currentIndex >= 0 && suggestions[currentIndex]) {
        suggestions[currentIndex].classList.add('highlighted');
        suggestions[currentIndex].scrollIntoView({ block: 'nearest' });
    }
}

/**
 * Resaltar sugerencia con mouse
 */
function highlightClientSuggestion(index) {
    const previousIndex = selectedClientIndex;
    selectedClientIndex = index;
    updateClientSuggestionHighlight(previousIndex, selectedClientIndex);
}

/**
 * Seleccionar cliente resaltado
 */
function selectHighlightedClient() {
    if (selectedClientIndex >= 0 && currentClientSuggestions[selectedClientIndex]) {
        selectClient(currentClientSuggestions[selectedClientIndex]);
    }
}

/**
 * Seleccionar un cliente
 */
function selectClient(client) {
    selectedClient = client;
    
    // Actualizar campos ocultos y visibles
    const selectedClientId = document.getElementById('selectedClientId');
    const selectedClientName = document.getElementById('selectedClientName');
    const selectedClientDiv = document.getElementById('selectedClient');
    const clientSearchInput = document.getElementById('clientSearch');
    const clientSearchContainer = document.querySelector('.client-search-container');
    
    if (selectedClientId) {
        selectedClientId.value = client.UUIDCliente || '';
    }
    
    if (selectedClientName) {
        selectedClientName.textContent = client.NombreDeCliente || 'Cliente seleccionado';
    }
    
    if (selectedClientDiv) {
        selectedClientDiv.style.display = 'flex';
    }
    
    if (clientSearchInput) {
        clientSearchInput.value = '';
        clientSearchInput.style.display = 'none';
    }
    
    if (clientSearchContainer) {
        clientSearchContainer.style.display = 'none';
    }
    
    // Ocultar sugerencias
    hideClientSuggestions();
    
    console.log('Cliente seleccionado:', client);
}

/**
 * Limpiar cliente seleccionado
 */
function clearSelectedClient() {
    selectedClient = null;
    
    const selectedClientId = document.getElementById('selectedClientId');
    const selectedClientDiv = document.getElementById('selectedClient');
    const clientSearchInput = document.getElementById('clientSearch');
    const clientSearchContainer = document.querySelector('.client-search-container');
    
    if (selectedClientId) {
        selectedClientId.value = '';
    }
    
    if (selectedClientDiv) {
        selectedClientDiv.style.display = 'none';
    }
    
    if (clientSearchInput) {
        clientSearchInput.style.display = 'block';
        clientSearchInput.value = '';
        clientSearchInput.focus();
    }
    
    if (clientSearchContainer) {
        clientSearchContainer.style.display = 'block';
    }
    
    hideClientSuggestions();
}

/**
 * Resetear estado de búsqueda de cliente
 */
function resetClientSearch() {
    selectedClient = null;
    currentClientSuggestions = [];
    selectedClientIndex = -1;
    
    const selectedClientId = document.getElementById('selectedClientId');
    const selectedClientDiv = document.getElementById('selectedClient');
    const clientSearchInput = document.getElementById('clientSearch');
    const clientSearchContainer = document.querySelector('.client-search-container');
    
    if (selectedClientId) {
        selectedClientId.value = '';
    }
    
    if (selectedClientDiv) {
        selectedClientDiv.style.display = 'none';
    }
    
    if (clientSearchInput) {
        clientSearchInput.style.display = 'block';
        clientSearchInput.value = '';
        clientSearchInput.classList.remove('loading', 'has-suggestions');
    }
    
    if (clientSearchContainer) {
        clientSearchContainer.style.display = 'block';
    }
    
    hideClientSuggestions();
}

/**
 * Obtener cliente seleccionado
 */
function getSelectedClient() {
    return selectedClient;
}

// ===== EVENT LISTENERS GLOBALES =====

// Cerrar modal al hacer clic fuera de él
document.addEventListener('click', function(event) {
    const saleModal = document.getElementById('saleModal');
    if (event.target === saleModal) {
        closeSaleModal();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const saleModal = document.getElementById('saleModal');
        if (saleModal && saleModal.style.display === 'flex') {
            closeSaleModal();
        }
    }
});

// ===== INICIALIZACIÓN DEL MÓDULO =====

// Verificar dependencias al cargar el módulo
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que existen las dependencias necesarias
    const requiredElements = ['saleModal', 'documentId', 'documentTotal', 'efectivoRecibido', 'cambio', 'processBtn', 'cancelBtn'];
    const missingElements = requiredElements.filter(id => !document.getElementById(id));
    
    if (missingElements.length > 0) {
        console.warn('Elementos faltantes para el modal de ventas:', missingElements);
    }
    
    console.log('Módulo de ventas cargado correctamente');
});

// Exportar funciones principales para uso global
window.showSaleModal = showSaleModal;
window.closeSaleModal = closeSaleModal;