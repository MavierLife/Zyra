// Funciones para la gestión de apertura de caja

// Verificar estado de la caja al cargar la página
function checkCashStatus() {
    fetch('api/apertura_caja.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.caja_del_dia) {
                updateCashButton(true, data.data);
            } else {
                updateCashButton(false);
            }
        })
        .catch(error => {
            console.error('Error verificando estado de caja:', error);
        });
}

// Función para actualizar el botón de caja
function updateCashButton(cajaAbierta, dataCaja = null) {
    const openCashBtn = document.getElementById('openCashBtn');
    if (!openCashBtn) return;
    
    if (cajaAbierta) {
        openCashBtn.innerHTML = `
            <img src="assets/icons/cajaregistradora.svg" alt="Caja abierta" class="nav-barcode-icon">
            Caja Abierta
            <span class="dropdown-arrow">▼</span>
        `;
        openCashBtn.classList.add('cash-opened');
    } else {
        openCashBtn.innerHTML = `
            <img src="assets/icons/cajaregistradora.svg" alt="Abrir caja" class="nav-barcode-icon">
            Abrir caja
        `;
        openCashBtn.classList.remove('cash-opened');
    }
}

// Manejar clic en botón de apertura de caja
function handleOpenCash() {
    const openCashBtn = document.getElementById('openCashBtn');
    
    // Si el botón dice "Caja Abierta", mostrar menú desplegable
    if (openCashBtn && openCashBtn.textContent.trim().includes('Caja Abierta')) {
        toggleCashMenu();
        return;
    }
    
    // Verificar primero si ya hay una caja abierta
    fetch('api/apertura_caja.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.caja_abierta && !data.caja_del_dia) {
                showTemporaryMessage('Ya existe una caja abierta para este terminal', 'warning');
                return;
            }
            // Si no hay caja abierta, mostrar el modal
            showOpenCash();
        })
        .catch(error => {
            console.error('Error verificando estado de caja:', error);
            showTemporaryMessage('Error al verificar el estado de la caja', 'error');
        });
}

// Función para mostrar/ocultar menú de caja
function toggleCashMenu() {
    let menu = document.getElementById('cashDropdownMenu');
    
    if (menu) {
        menu.remove();
        return;
    }
    
    // Crear menú desplegable
    menu = document.createElement('div');
    menu.id = 'cashDropdownMenu';
    menu.className = 'cash-dropdown-menu';
    
    menu.innerHTML = `
        <div class="cash-menu-item" onclick="showCashSummary()">
            <span class="menu-icon">📊</span>
            Ver resumen de caja
        </div>
        <div class="cash-menu-item" onclick="closeCash()">
            <span class="menu-icon">🔒</span>
            Cerrar caja
        </div>
        <div class="cash-menu-item" onclick="printCashReport()">
            <span class="menu-icon">🖨️</span>
            Imprimir reporte
        </div>
    `;
    
    // Posicionar el menú
    const openCashBtn = document.getElementById('openCashBtn');
    const rect = openCashBtn.getBoundingClientRect();
    menu.style.position = 'fixed';
    menu.style.top = (rect.bottom + 5) + 'px';
    menu.style.left = rect.left + 'px';
    menu.style.minWidth = rect.width + 'px';
    
    document.body.appendChild(menu);
    
    // Cerrar menú al hacer clic fuera
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && e.target !== openCashBtn) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 100);
}

// Funciones del menú de caja
function showCashSummary() {
    document.getElementById('cashDropdownMenu')?.remove();
    showTemporaryMessage('Ver resumen de caja - Función próximamente', 'info');
}

function closeCash() {
    document.getElementById('cashDropdownMenu')?.remove();
    showTemporaryMessage('Cerrar caja - Función próximamente', 'info');
}

function printCashReport() {
    document.getElementById('cashDropdownMenu')?.remove();
    showTemporaryMessage('Imprimir reporte - Función próximamente', 'info');
}

// Funciones para el modal de apertura de caja
function showOpenCash() {
    const modal = createOpenCashModal();
    document.body.appendChild(modal);
    
    // Mostrar el modal
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Focus en el input de monto
    const montoInput = modal.querySelector('#openCashAmount');
    if (montoInput) {
        montoInput.focus();
    }
}

function createOpenCashModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'openCashModal';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Apertura de Caja</h3>
                <button class="close-modal" onclick="closeOpenCashModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="user-info">
                    <p><strong>Usuario:</strong> <span id="currentUserName">${nombreUsuario || 'Usuario'}</span></p>
                    <p><strong>Terminal:</strong> <span id="currentTerminal">${currentVendedorId || 'N/A'}</span></p>
                </div>
                <form id="openCashForm">
                    <div class="form-group">
                        <label for="openCashAmount">Monto de Apertura (${currencySymbol})</label>
                        <input 
                            type="number" 
                            id="openCashAmount" 
                            name="efectivo_apertura" 
                            step="0.01" 
                            min="0" 
                            placeholder="0.00" 
                            required
                        >
                        <small class="form-help">Ingrese el monto inicial en efectivo para la caja</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeOpenCashModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitOpenCash()">Abrir Caja</button>
            </div>
        </div>
    `;
    
    return modal;
}

function closeOpenCashModal() {
    const modal = document.getElementById('openCashModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

function submitOpenCash() {
    const form = document.getElementById('openCashForm');
    const montoInput = document.getElementById('openCashAmount');
    const submitBtn = document.querySelector('#openCashModal .btn-primary');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const monto = parseFloat(montoInput.value);
    
    if (isNaN(monto) || monto < 0) {
        showTemporaryMessage('Por favor ingrese un monto válido', 'error');
        return;
    }
    
    // Deshabilitar botón mientras se procesa
    submitBtn.disabled = true;
    submitBtn.textContent = 'Procesando...';
    
    // Enviar datos al servidor
    fetch('api/apertura_caja.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            efectivo_apertura: monto
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTemporaryMessage('Caja abierta exitosamente', 'success');
            closeOpenCashModal();
            // Actualizar el estado del botón
            updateCashButton(true, data.data);
        } else {
            throw new Error(data.message || 'Error al abrir la caja');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showTemporaryMessage(error.message || 'Error al abrir la caja', 'error');
    })
    .finally(() => {
        // Rehabilitar botón
        submitBtn.disabled = false;
        submitBtn.textContent = 'Abrir Caja';
    });
}

// Inicializar verificación de estado de caja al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    checkCashStatus();
    
    // Agregar event listener al botón de apertura de caja
    const openCashBtn = document.getElementById('openCashBtn');
    if (openCashBtn) {
        openCashBtn.addEventListener('click', handleOpenCash);
    }
});