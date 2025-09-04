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
            'nota': 'NOTA DE CREDITO'
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
        showTemporaryMessage('Error al procesar la venta: ' + error.message, 'error');
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
        timestamp: new Date().toISOString()
    };
}

/**
 * Enviar datos de venta al backend
 */
async function sendSaleToBackend(saleData) {
    const response = await fetch('api/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
    });
    
    if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
    }
    
    const result = await response.json();
    
    if (!result.success) {
        throw new Error(result.error || result.message || 'Error desconocido');
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
    // Verificar si la función existe globalmente
    if (typeof window.showTemporaryMessage === 'function') {
        window.showTemporaryMessage(message, type);
        return;
    }
    
    // Fallback simple
    console.log(`[${type.toUpperCase()}] ${message}`);
    alert(message);
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