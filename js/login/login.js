document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phoneNumber');
    const submitBtn = document.querySelector('.submit-btn');
    const loginForm = document.getElementById('loginForm');
    const countrySelector = document.querySelector('.country-selector');

    // Habilitar/deshabilitar botón según el input
    phoneInput.addEventListener('input', function() {
        const phoneValue = this.value.replace(/\s/g, ''); // Remover espacios para validar
        
        // Validar que tenga exactamente 8 dígitos
        if (phoneValue.length === 8 && /^[0-9]+$/.test(phoneValue)) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar código';
        } else {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviar código';
        }
    });

    // Formatear número mientras se escribe
    phoneInput.addEventListener('input', function() {
        // Remover caracteres no numéricos
        let value = this.value.replace(/\D/g, '');
        
        // Limitar a 8 dígitos
        if (value.length > 8) {
            value = value.substring(0, 8);
        }
        
        // Formatear el número (ejemplo: 7539 0076)
        if (value.length >= 4) {
            value = value.substring(0, 4) + ' ' + value.substring(4);
        }
        
        this.value = value;
    });

    // Manejar envío del formulario
    loginForm.addEventListener('submit', function(e) {
        const phoneNumber = phoneInput.value.replace(/\s/g, ''); // Remover espacios
        
        if (phoneNumber.length < 8) {
            e.preventDefault();
            alert('Por favor, ingresa un número de teléfono válido de 8 dígitos.');
            return;
        }
        
        // Actualizar el valor del input sin espacios antes de enviar
        phoneInput.value = phoneNumber;
        
        // Cambiar texto del botón
        submitBtn.textContent = 'Enviando...';
        submitBtn.disabled = true;
        
        // Permitir que el formulario se envíe normalmente al servidor
        // No usar e.preventDefault() aquí
    });

    // Funcionalidad del selector de país (placeholder)
    countrySelector.addEventListener('click', function() {
        // Por ahora solo muestra un mensaje, se puede expandir más adelante
        console.log('Selector de país clickeado - funcionalidad pendiente');
    });

    // Enfocar automáticamente el input al cargar
    phoneInput.focus();

    // Prevenir pegar texto no numérico
    phoneInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const numericPaste = paste.replace(/\D/g, '');
        
        if (numericPaste) {
            this.value = numericPaste.substring(0, 8);
            this.dispatchEvent(new Event('input'));
        }
    });

    // Manejar teclas especiales
    phoneInput.addEventListener('keydown', function(e) {
        // Permitir: backspace, delete, tab, escape, enter
        if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
            // Permitir: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true) ||
            // Permitir: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
            return;
        }
        
        // Asegurar que sea un número
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
});