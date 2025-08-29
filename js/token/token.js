/**
 * JavaScript para la página de verificación de tokens
 * Maneja los campos individuales de dígitos y la verificación
 */

document.addEventListener('DOMContentLoaded', function() {
    const tokenDigits = document.querySelectorAll('.token-digit');
    const tokenValue = document.getElementById('tokenValue');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.querySelector('.token-form');
    
    // Configurar eventos para los campos de dígitos
    tokenDigits.forEach((digit, index) => {
        // Evento de entrada
        digit.addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Solo permitir números
            if (!/^[0-9]$/.test(value) && value !== '') {
                e.target.value = '';
                return;
            }
            
            // Si se ingresó un dígito válido
            if (value) {
                e.target.classList.add('filled');
                
                // Mover al siguiente campo
                if (index < tokenDigits.length - 1) {
                    tokenDigits[index + 1].focus();
                }
            } else {
                e.target.classList.remove('filled');
            }
            
            updateTokenValue();
            updateSubmitButton();
        });
        
        // Evento de tecla presionada
        digit.addEventListener('keydown', function(e) {
            // Backspace: ir al campo anterior si está vacío
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                tokenDigits[index - 1].focus();
                tokenDigits[index - 1].value = '';
                tokenDigits[index - 1].classList.remove('filled');
                updateTokenValue();
                updateSubmitButton();
            }
            
            // Flecha izquierda: ir al campo anterior
            if (e.key === 'ArrowLeft' && index > 0) {
                tokenDigits[index - 1].focus();
            }
            
            // Flecha derecha: ir al campo siguiente
            if (e.key === 'ArrowRight' && index < tokenDigits.length - 1) {
                tokenDigits[index + 1].focus();
            }
            
            // Enter: enviar formulario si está completo
            if (e.key === 'Enter') {
                e.preventDefault();
                if (isTokenComplete()) {
                    form.submit();
                }
            }
        });
        
        // Evento de pegado
        digit.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text');
            const digits = pastedData.replace(/\D/g, '').slice(0, 6);
            
            // Llenar los campos con los dígitos pegados
            for (let i = 0; i < digits.length && i < tokenDigits.length; i++) {
                tokenDigits[i].value = digits[i];
                tokenDigits[i].classList.add('filled');
            }
            
            // Enfocar el siguiente campo vacío o el último
            const nextEmptyIndex = digits.length < tokenDigits.length ? digits.length : tokenDigits.length - 1;
            tokenDigits[nextEmptyIndex].focus();
            
            updateTokenValue();
            updateSubmitButton();
        });
        
        // Evento de enfoque
        digit.addEventListener('focus', function() {
            this.select();
        });
    });
    
    // Función para actualizar el valor del token oculto
    function updateTokenValue() {
        const token = Array.from(tokenDigits).map(digit => digit.value).join('');
        tokenValue.value = token;
    }
    
    // Función para verificar si el token está completo
    function isTokenComplete() {
        return Array.from(tokenDigits).every(digit => digit.value !== '');
    }
    
    // Función para actualizar el estado del botón de envío
    function updateSubmitButton() {
        if (isTokenComplete()) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Entrar a Zyra';
        } else {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Ingresa el código completo';
        }
    }
    
    // Enfocar el primer campo al cargar
    if (tokenDigits.length > 0) {
        tokenDigits[0].focus();
    }
    
    // Inicializar estado del botón
    updateSubmitButton();
    
    // Contador regresivo
    let timeLeft = 89; // 1:29 en segundos
    const countdownElement = document.getElementById('countdown');
    
    function updateCountdown() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft > 0) {
            timeLeft--;
            setTimeout(updateCountdown, 1000);
        } else {
            // Habilitar reenvío cuando termine el contador
            countdownElement.parentElement.innerHTML = '<a href="#" onclick="resendCode()">Reenviar código</a>';
        }
    }
    
    // Iniciar contador
    updateCountdown();
    

    
    // Prevenir envío del formulario si el token no está completo
    form.addEventListener('submit', function(e) {
        if (!isTokenComplete()) {
            e.preventDefault();
            alert('Por favor, ingresa el código completo de 6 dígitos.');
            return false;
        }
        
        // Mostrar indicador de carga
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verificando...';
    });
});