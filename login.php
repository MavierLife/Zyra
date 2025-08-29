<?php
// Login page - Página de inicio de sesión con sistema de tokens
session_start();

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['vendedor_id']) && !empty($_SESSION['vendedor_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zyra - Iniciar Sesión</title>
    
    <!-- Google Fonts - Nunito Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="styles/login/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="header">
                <div class="logo">
                    <img src="assets/logos/logo.png" alt="Zyra Logo" class="logo-image">
                    <div class="logo-text">Zyra <span class="version">0.0.1</span></div>
                </div>
            </div>
            
            <div class="phone-section">
                <h2>Inicia sesión</h2>
                <p class="subtitle">Ingresa con tu celular</p>
            </div>
            
            <div class="info-message">
                <span class="info-icon">ℹ</span>
                <p>Te enviaremos un código de verificación por <strong>mensaje de WhatsApp</strong></p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="success-message"><?php echo htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="login-form" id="loginForm">
                <div class="phone-input-container">
                    <div class="country-selector">
                        <span class="flag">🇸🇻</span>
                        <span class="country-code">+503</span>
                    </div>
                    <input 
                        type="tel" 
                        name="telefono" 
                        id="phoneNumber"
                        placeholder="75390076" 
                        maxlength="8" 
                        pattern="[0-9]{8}" 
                        required
                    >
                </div>
                
                <button type="submit" class="submit-btn">Enviar código</button>
            </form>
            
            <div class="register-link">
                <p>¿No tienes cuenta? <a href="#">Crea una cuenta</a></p>
            </div>
        </div>
    </div>
<?php
// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("[DEBUG LOGIN] POST recibido");
    $telefono = $_POST['telefono'] ?? '';
    error_log("[DEBUG LOGIN] Telefono recibido: " . $telefono);
    
    // Validar que el teléfono tenga 8 dígitos
    if (strlen($telefono) !== 8 || !ctype_digit($telefono)) {
        error_log("[DEBUG LOGIN] Telefono inválido - longitud o formato");
        $error = 'Por favor, ingresa un número de teléfono válido de 8 dígitos.';
    } else {
        // Validar que comience con 2, 6 o 7 (números válidos en El Salvador)
        $primerDigito = substr($telefono, 0, 1);
        if (!in_array($primerDigito, ['2', '6', '7'])) {
            $error = 'Por favor, ingresa un número de teléfono válido.';
        } else {
            // Verificar si el vendedor existe en la base de datos
            require_once 'controller/TokenController.php';
            
            try {
                error_log("[DEBUG LOGIN] Iniciando proceso para telefono: " . $telefono);
                $tokenController = new TokenController();
                error_log("[DEBUG LOGIN] TokenController creado");
                
                $vendedor = $tokenController->obtenerDatosVendedor($telefono);
                error_log("[DEBUG LOGIN] Resultado obtenerDatosVendedor: " . ($vendedor ? 'ENCONTRADO' : 'NO ENCONTRADO'));
                
                if ($vendedor) {
                    // Generar y enviar token
                    error_log("[DEBUG LOGIN] Llamando procesarSolicitudToken");
                    $resultado = $tokenController->procesarSolicitudToken($telefono);
                    error_log("[DEBUG LOGIN] Resultado procesarSolicitudToken: " . json_encode($resultado));
                    
                    if ($resultado['success']) {
                        error_log("[DEBUG LOGIN] Redirigiendo a token.php");
                        // Redirigir a la página de verificación de token
                        header("Location: token.php?telefono=" . urlencode($telefono));
                        exit();
                    } else {
                        error_log("[DEBUG LOGIN] Error en resultado: " . $resultado['message']);
                        $error = $resultado['message'];
                    }
                } else {
                    error_log("[DEBUG LOGIN] Vendedor no encontrado");
                    $error = 'Número de teléfono no registrado en el sistema.';
                }
            } catch (Exception $e) {
                error_log("[DEBUG LOGIN] Exception: " . $e->getMessage());
                error_log("[DEBUG LOGIN] Stack trace: " . $e->getTraceAsString());
                $error = 'Error interno del servidor. Por favor, intenta más tarde.';
            }
        }
    }
    
    // Si hay error, redirigir con el mensaje de error
    if (isset($error)) {
        header("Location: login.php?error=" . urlencode($error));
        exit();
    }
}
?>

</body>
</html>