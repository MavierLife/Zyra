<?php
// Configurar codificación interna
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Configurar sesiones antes de iniciarlas
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_secure', false);
ini_set('session.cookie_httponly', true);
ini_set('session.use_strict_mode', true);

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['vendedor_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    $redirect_message = isset($_GET['page']) && $_GET['page'] === 'inventario' 
        ? 'Debes+iniciar+sesión+para+acceder+al+inventario'
        : 'Debes+iniciar+sesión+para+acceder+al+dashboard';
    header('Location: login.php?message=' . $redirect_message);
    exit();
}

// Variables de sesión
$vendedorId = $_SESSION['vendedor_id'];
$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Usuario';
$razonSocial = $_SESSION['razon_social'] ?? 'Contribuyente';
$nombreComercial = $_SESSION['nombre_comercial'] ?? 'Negocio';
$loginTime = $_SESSION['login_time'] ?? time();
$uuidContribuyente = $_SESSION['uuid_contribuyente'] ?? null;

// Obtener símbolo de moneda
require_once __DIR__ . '/../Config/Conexion.php';
require_once __DIR__ . '/../Config/CurrencyManager.php';

$currencySymbol = '$'; // Valor por defecto
if ($uuidContribuyente) {
    try {
        $conexion = new Conexion();
        $pdo = $conexion->getPdo();
        $currencyManager = new CurrencyManager($pdo);
        $currencySymbol = $currencyManager->getCurrencySymbolByContributor($uuidContribuyente);
    } catch (Exception $e) {
        // Mantener el símbolo por defecto en caso de error
        error_log('Error obteniendo símbolo de moneda: ' . $e->getMessage());
    }
}

// Función para determinar la página actual
function getCurrentPage() {
    $currentFile = basename($_SERVER['PHP_SELF']);
    switch ($currentFile) {
        case 'index.php':
            return 'vender';
        case 'inventario.php':
            return 'inventario';
        case 'proveedores.php':
            return 'proveedores';
        default:
            return 'vender';
    }
}

$currentPage = getCurrentPage();
?>