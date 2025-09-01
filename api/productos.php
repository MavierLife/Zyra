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

// Permitir GET y POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
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
require_once '../Config/CurrencyManager.php';

try {
    $uuidVendedor = $_SESSION['vendedor_id'];
    $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'todos';
    
    // Conectar a la base de datos
    $conexion = new Conexion();
    $pdo = $conexion->getPdo();
    
    // Obtener UUIDContribuyente del vendedor logueado
    $sqlVendedor = "SELECT UUIDContribuyente, NombreUsuario FROM tblvendedores WHERE UUIDVendedor = :uuid_vendedor";
    $stmtVendedor = $pdo->prepare($sqlVendedor);
    $stmtVendedor->bindParam(':uuid_vendedor', $uuidVendedor);
    $stmtVendedor->execute();
    
    $vendedorData = $stmtVendedor->fetch(PDO::FETCH_ASSOC);
    
    if (!$vendedorData) {
        http_response_code(404);
        echo json_encode(['error' => 'Vendedor no encontrado']);
        exit();
    }
    
    $uuidContribuyente = $vendedorData['UUIDContribuyente'];
    
    // Inicializar CurrencyManager
    $currencyManager = new CurrencyManager($pdo);
    
    // Obtener datos del contribuyente
    $sqlContribuyente = "SELECT NombreComercial, RazonSocial FROM tblcontribuyentes WHERE UUIDContribuyente = :uuid_contribuyente";
    $stmtContribuyente = $pdo->prepare($sqlContribuyente);
    $stmtContribuyente->bindParam(':uuid_contribuyente', $uuidContribuyente);
    $stmtContribuyente->execute();
    
    $contribuyenteData = $stmtContribuyente->fetch(PDO::FETCH_ASSOC);
    
    // Consultar productos del contribuyente con su categoría
    $sql = "SELECT p.UUIDProducto as id, p.Descripcion as name, p.PrecioVenta as price, 
                   p.CodigoDeBarras as codigo, p.Existencias as stock, p.IDCategoria, c.Categoria
            FROM tblcontribuyentesproductos p
            LEFT JOIN tblcontribuyentescategorias c ON p.IDCategoria = c.IDCategoria AND c.UUIDContribuyente = ?
            WHERE p.UUIDContribuyente = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uuidContribuyente, $uuidContribuyente]);
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar campos adicionales
    foreach ($productos as &$producto) {
        $producto['brand'] = $contribuyenteData['NombreComercial'] ?? 'Sin marca';
        $producto['category'] = !empty($producto['Categoria']) ? strtoupper($producto['Categoria']) : 'GENERAL';
        $producto['uuid_contribuyente'] = $uuidContribuyente;
        // Limpiar campos internos
        unset($producto['Categoria']);
        unset($producto['IDCategoria']);
    }
    
    // Obtener categorías disponibles del contribuyente
    $sqlCategorias = "SELECT Categoria FROM tblcontribuyentescategorias WHERE UUIDContribuyente = :contribuyente";
    $stmtCategorias = $pdo->prepare($sqlCategorias);
    $stmtCategorias->bindParam(':contribuyente', $uuidContribuyente);
    $stmtCategorias->execute();
    
    $categoriasResult = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);
    $categorias = array_map('strtoupper', $categoriasResult);
    
    // Si no hay categorías específicas, usar la categoría por defecto
    if (empty($categorias)) {
        $categorias = ['GENERAL'];
    }
    
    // Filtrar por categoría si se especifica
    if ($categoria !== 'todos') {
        $productos = array_filter($productos, function($producto) use ($categoria) {
            return strtolower($producto['category']) === strtolower($categoria);
        });
    }
    
    // Obtener símbolo de moneda
    $currencySymbol = $currencyManager->getCurrencySymbolByContributor($uuidContribuyente);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'productos' => array_values($productos),
            'categorias' => $categorias,
            'currency_symbol' => $currencySymbol,
            'contribuyente' => [
                 'uuid' => $uuidContribuyente,
                 'nombre_comercial' => $contribuyenteData['NombreComercial'],
                 'razon_social' => $contribuyenteData['RazonSocial']
             ],
             'vendedor' => [
                 'uuid' => $uuidVendedor,
                 'nombre_usuario' => $vendedorData['NombreUsuario']
             ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
    // Manejar creación de nuevo producto
    
    // Leer datos JSON del cuerpo de la petición
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos JSON inválidos']);
        exit();
    }
    
    // Validar campos requeridos
    $requiredFields = ['name', 'barcode', 'price', 'cost', 'stock', 'category', 'vendedor_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Campo requerido faltante: ' . $field]);
            exit();
        }
    }
    
    // Obtener UUID del contribuyente desde el vendedor
    $sqlVendedor = "SELECT UUIDContribuyente FROM tblvendedores WHERE UUIDVendedor = :vendedor_id";
    $stmtVendedor = $pdo->prepare($sqlVendedor);
    $stmtVendedor->bindParam(':vendedor_id', $data['vendedor_id']);
    $stmtVendedor->execute();
    
    $vendedorData = $stmtVendedor->fetch(PDO::FETCH_ASSOC);
    if (!$vendedorData) {
        http_response_code(404);
        echo json_encode(['error' => 'Vendedor no encontrado']);
        exit();
    }
    
    $uuidContribuyente = $vendedorData['UUIDContribuyente'];
    
    // Obtener datos del contribuyente
    $sqlContribuyente = "SELECT NombreComercial, RazonSocial FROM tblcontribuyentes WHERE UUIDContribuyente = :uuid_contribuyente";
    $stmtContribuyente = $pdo->prepare($sqlContribuyente);
    $stmtContribuyente->bindParam(':uuid_contribuyente', $uuidContribuyente);
    $stmtContribuyente->execute();
    
    $contribuyenteData = $stmtContribuyente->fetch(PDO::FETCH_ASSOC);
    $nombreComercial = $contribuyenteData['NombreComercial'] ?? 'Sin marca';
    
    // Verificar que el código de barras no exista para este contribuyente
    $sqlCheck = "SELECT COUNT(*) as count FROM tblcontribuyentesproductos WHERE CodigoDeBarras = :barcode AND UUIDContribuyente = :uuid_contribuyente";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindParam(':barcode', $data['barcode']);
    $stmtCheck->bindParam(':uuid_contribuyente', $uuidContribuyente);
    $stmtCheck->execute();
    
    $checkResult = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($checkResult['count'] > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Ya existe un producto con este código de barras']);
        exit();
    }
    
    // Insertar nuevo producto
    $sqlInsert = "INSERT INTO tblcontribuyentesproductos (UUIDContribuyente, CodigoDeBarras, Descripcion, Existencias, PrecioVenta, CostoCompra, IDCategoria) 
                  VALUES (:uuid_contribuyente, :barcode, :name, :stock, :price, :cost, :category)";
    
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindParam(':uuid_contribuyente', $uuidContribuyente);
    $stmtInsert->bindParam(':barcode', $data['barcode']);
    $stmtInsert->bindParam(':name', $data['name']);
    $stmtInsert->bindParam(':stock', $data['stock'], PDO::PARAM_INT);
    $stmtInsert->bindParam(':price', $data['price']);
    $stmtInsert->bindParam(':cost', $data['cost']);
    $stmtInsert->bindParam(':category', $data['category'], PDO::PARAM_INT);
    
    if ($stmtInsert->execute()) {
        $newProductId = $pdo->lastInsertId();
        
        // Obtener el producto recién creado con su categoría
        $sqlNewProduct = "SELECT p.UUIDProducto as id, p.Descripcion as name, p.PrecioVenta as price, 
                                p.CodigoDeBarras as codigo, p.Existencias as stock, p.IDCategoria, c.Categoria
                         FROM tblcontribuyentesproductos p
                         LEFT JOIN tblcontribuyentescategorias c ON p.IDCategoria = c.IDCategoria AND c.UUIDContribuyente = ?
                         WHERE p.UUIDProducto = ?";
        
        $stmtNewProduct = $pdo->prepare($sqlNewProduct);
        $stmtNewProduct->execute([$uuidContribuyente, $newProductId]);
        $newProduct = $stmtNewProduct->fetch(PDO::FETCH_ASSOC);
        
        if ($newProduct) {
            // Agregar campos adicionales
            $newProduct['brand'] = $nombreComercial ?? 'Sin marca';
            $newProduct['category'] = !empty($newProduct['Categoria']) ? strtoupper($newProduct['Categoria']) : 'GENERAL';
            $newProduct['uuid_contribuyente'] = $uuidContribuyente;
            
            // Limpiar campos internos
            unset($newProduct['IDCategoria']);
            unset($newProduct['Categoria']);
        }
        
        http_response_code(201);
        echo json_encode([
             'success' => true,
             'message' => 'Producto creado exitosamente',
             'data' => $newProduct
         ]);
     } else {
         http_response_code(500);
         echo json_encode(['error' => 'Error al crear el producto']);
     }
     
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
    }
}
?>