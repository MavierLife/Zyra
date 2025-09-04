<?php
// Configurar codificación interna
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Permitir solo GET requests para obtener número de documento
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['vendedor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once '../Config/Conexion.php';

try {
    $uuidVendedor = $_SESSION['vendedor_id'];
    
    // Conectar a la base de datos
    $conexion = new Conexion();
    $pdo = $conexion->getPdo();
    
    // Obtener información del vendedor y contribuyente
    $sqlVendedor = "SELECT UUIDContribuyente, NombreUsuario FROM tblcontribuyentesvendedores WHERE UUIDVendedor = :uuid_vendedor";
    $stmtVendedor = $pdo->prepare($sqlVendedor);
    $stmtVendedor->bindParam(':uuid_vendedor', $uuidVendedor);
    $stmtVendedor->execute();
    
    $vendedorData = $stmtVendedor->fetch(PDO::FETCH_ASSOC);
    
    if (!$vendedorData) {
        throw new Exception('Vendedor no encontrado');
    }
    
    $uuidContribuyente = $vendedorData['UUIDContribuyente'];
    
    // Obtener el código de establecimiento (CM01) del contribuyente
    $sqlContribuyente = "SELECT CodEstable FROM tblcontribuyentes WHERE UUIDContribuyente = :uuid_contribuyente";
    $stmtContribuyente = $pdo->prepare($sqlContribuyente);
    $stmtContribuyente->bindParam(':uuid_contribuyente', $uuidContribuyente);
    $stmtContribuyente->execute();
    
    $contribuyenteData = $stmtContribuyente->fetch(PDO::FETCH_ASSOC);
    
    if (!$contribuyenteData) {
        throw new Exception('Contribuyente no encontrado');
    }
    
    $codEstable = $contribuyenteData['CodEstable'];
    
    // Generar número de documento
    $documentNumber = generateDocumentNumber($codEstable);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'documentNumber' => $documentNumber,
        'codEstable' => $codEstable
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}

// Función para generar número de documento
function generateDocumentNumber($codEstable) {
    $now = new DateTime();
    $year = $now->format('Y');
    $month = $now->format('m');
    $day = $now->format('d');
    $hour = $now->format('H');
    $minute = $now->format('i');
    $second = $now->format('s');
    
    return $codEstable . $year . $month . $day . $hour . $minute . $second;
}
?>