<?php
/**
 * Página de verificación de token
 * Permite al usuario ingresar el código de verificación recibido por WhatsApp
 */

session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['vendedor_id'])) {
    header('Location: index.php');
    exit();
}

// Obtener teléfono de la URL
$telefono = isset($_GET['telefono']) ? $_GET['telefono'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';

// Procesar verificación de token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = trim($_POST['token']);
    $telefono = trim($_POST['telefono']);
    
    if (empty($token)) {
        $error = 'Por favor, ingresa el código de verificación.';
    } elseif (strlen($token) !== 6 || !ctype_digit($token)) {
        $error = 'El código debe tener exactamente 6 dígitos.';
    } else {
        require_once 'controller/TokenController.php';
        
        try {
            $tokenController = new TokenController();
            $resultado = $tokenController->verificarToken($telefono, $token);
            
            if ($resultado['success']) {
                // Crear sesión
                $_SESSION['authenticated'] = true;
                $_SESSION['vendedor_id'] = $resultado['data']['vendedor_id'];
                $_SESSION['nombre_usuario'] = $resultado['data']['nombre_usuario'];
                $_SESSION['rol'] = $resultado['data']['rol'];
                $_SESSION['uuid_contribuyente'] = $resultado['data']['uuid_contribuyente'];
                $_SESSION['razon_social'] = $resultado['data']['razon_social'];
                $_SESSION['nombre_comercial'] = $resultado['data']['nombre_comercial'];
                $_SESSION['login_time'] = time();
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                
                // Redirigir al dashboard
                header('Location: index.php');
                exit();
            } else {
                $error = $resultado['message'];
            }
        } catch (Exception $e) {
            $error = 'Error interno del servidor. Por favor, intenta más tarde.';
        }
    }
    
    // Si hay error, redirigir con el mensaje de error
    if (isset($error)) {
        header("Location: token.php?telefono=" . urlencode($telefono) . "&error=" . urlencode($error));
        exit();
    }
}

// Validar que se haya proporcionado el teléfono
if (empty($telefono)) {
    header('Location: login.php?error=' . urlencode('Sesión inválida. Por favor, inicia el proceso nuevamente.'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación - Zyra</title>
    
    <!-- Google Fonts - Nunito Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="styles/token/token.css">
</head>
<body>
    <div class="token-container">
        <div class="token-card">
            <!-- Logo -->
            <div class="logo">
                <img src="assets/logos/logo.png" alt="Zyra Logo" class="logo-image">
                <span class="app-name">Zyra</span>
                <span class="version">0.0.1</span>
            </div>
            
            <!-- Título -->
            <h1 class="token-title">Código de verificación</h1>
            <p class="token-subtitle">
                Ingresa el código de verificación que hemos enviado por mensaje de WhatsApp al teléfono 
                <strong>+503 <?php echo htmlspecialchars($telefono); ?></strong>
                <a href="login.php" class="edit-phone">Editar</a>
            </p>
            
            <!-- Mensajes -->
            <?php if (!empty($error)): ?>
                <div class="message error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="message success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulario de verificación -->
            <form method="POST" class="token-form">
                <input type="hidden" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>">
                <input type="hidden" name="token" id="tokenValue">
                
                <div class="token-inputs">
                    <input type="text" class="token-digit" maxlength="1" data-index="0" autocomplete="off">
                    <input type="text" class="token-digit" maxlength="1" data-index="1" autocomplete="off">
                    <input type="text" class="token-digit" maxlength="1" data-index="2" autocomplete="off">
                    <input type="text" class="token-digit" maxlength="1" data-index="3" autocomplete="off">
                    <input type="text" class="token-digit" maxlength="1" data-index="4" autocomplete="off">
                    <input type="text" class="token-digit" maxlength="1" data-index="5" autocomplete="off">
                </div>
                
                <div class="resend-section">
                    <span class="resend-text">¿No la has recibido?</span>
                    <span class="resend-timer">Espera <span id="countdown">1:29</span> minutos</span>
                </div>
                
                <button type="submit" class="submit-btn" id="submitBtn">Entrar a Zyra</button>
            </form>
        </div>
    </div>
    
    <script src="js/token/token.js"></script>
</body>
</html>