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

// Permitir solo POST requests para procesar ventas
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    // Obtener datos del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos de venta inválidos');
    }
    
    // Validar datos requeridos
    $requiredFields = ['vendedorId', 'documentType', 'documentNumber', 'items', 'total', 'efectivoRecibido'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Validar que hay items en la venta
    if (empty($data['items']) || !is_array($data['items'])) {
        throw new Exception('La venta debe contener al menos un producto');
    }
    
    // Validar montos
    $total = floatval($data['total']);
    $efectivoRecibido = floatval($data['efectivoRecibido']);
    
    if ($total <= 0) {
        throw new Exception('El total de la venta debe ser mayor a cero');
    }
    
    if ($efectivoRecibido < $total) {
        throw new Exception('El efectivo recibido debe ser mayor o igual al total');
    }
    
    $uuidVendedor = $_SESSION['vendedor_id'];
    
    // Conectar a la base de datos
    $conexion = new Conexion();
    $pdo = $conexion->getPdo();
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
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
    $nombreUsuario = $vendedorData['NombreUsuario'];
    
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
    
    // Generar UUID para la venta
    $uuidVenta = generateUUID();
    
    // Generar número de documento usando el código de establecimiento
    $documentNumber = generateDocumentNumber($codEstable);
    
    // Calcular totales
    $subTotal = 0;
    $totalIVA = 0;
    $totalVentaGravada = 0;
    $totalVentaExenta = 0;
    
    // Validar productos y calcular totales
    foreach ($data['items'] as $item) {
        if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['currentPrice'])) {
            throw new Exception('Datos de producto inválidos');
        }
        
        $cantidad = intval($item['quantity']);
        $precio = floatval($item['currentPrice']);
        $subtotalItem = $cantidad * $precio;
        
        $subTotal += $subtotalItem;
        
        // Por simplicidad, asumimos que todos los productos están gravados con IVA
        $totalVentaGravada += $subtotalItem;
    }
    
    // Calcular IVA (13% en El Salvador)
    $totalIVA = $totalVentaGravada * 0.13;
    $totalImporte = $subTotal + $totalIVA;
    
    // Insertar venta principal
    $sqlVenta = "INSERT INTO tblnotasdeentrega (
        UUIDVenta, UUIDContribuyente, UsuarioRegistro, CodigoVEN, 
        TipoDespacho, CodDocumento, NombreDeCliente, 
        SubTotalVentas, IVAPercibido, TotalImporte,
        PagoEfectivo, CambioEntregado, Estado, FechaRegistro
    ) VALUES (
        :uuid_venta, :uuid_contribuyente, :usuario_registro, :codigo_ven,
        1, :cod_documento, 'Cliente General',
        :subtotal, :iva, :total_importe,
        :pago_efectivo, :cambio, 1, NOW()
    )";
    
    $stmtVenta = $pdo->prepare($sqlVenta);
    $stmtVenta->bindParam(':uuid_venta', $uuidVenta);
    $stmtVenta->bindParam(':uuid_contribuyente', $uuidContribuyente);
    $stmtVenta->bindParam(':usuario_registro', $nombreUsuario);
    $stmtVenta->bindParam(':codigo_ven', $documentNumber);
    
    // Mapear tipo de documento
    $codDocumento = mapDocumentType($data['documentType']);
    $stmtVenta->bindParam(':cod_documento', $codDocumento);
    
    $stmtVenta->bindParam(':subtotal', $subTotal);
    $stmtVenta->bindParam(':iva', $totalIVA);
    $stmtVenta->bindParam(':total_importe', $totalImporte);
    $stmtVenta->bindParam(':pago_efectivo', $efectivoRecibido);
    
    $cambio = $efectivoRecibido - $totalImporte;
    $stmtVenta->bindParam(':cambio', $cambio);
    
    $stmtVenta->execute();
    
    // Insertar detalles de la venta
    $sqlDetalle = "INSERT INTO tblnotasdeentregadetalle (
        UUIDDetalleVenta, UUIDVenta, UsuarioRegistro, CodigoPROD,
        Concepto, TV, Cantidad, PrecioVenta, PrecioVentaSinImpuesto,
        VentaGravada, VentaGravadaSinImpuesto, IVAItem, TotalImporte,
        FechaRegistro
    ) VALUES (
        :uuid_detalle, :uuid_venta, :usuario_registro, :codigo_prod,
        :concepto, 1, :cantidad, :precio_venta, :precio_sin_iva,
        :venta_gravada, :venta_gravada_sin_iva, :iva_item, :total_item,
        NOW()
    )";
    
    $stmtDetalle = $pdo->prepare($sqlDetalle);
    
    foreach ($data['items'] as $item) {
        $uuidDetalle = generateUUID();
        $cantidad = intval($item['quantity']);
        $precioVenta = floatval($item['currentPrice']);
        $totalItem = $cantidad * $precioVenta;
        
        // Calcular precio sin IVA
        $precioSinIVA = $precioVenta / 1.13;
        $ventaGravadaSinIVA = $totalItem / 1.13;
        $ivaItem = $totalItem - $ventaGravadaSinIVA;
        
        $stmtDetalle->bindParam(':uuid_detalle', $uuidDetalle);
        $stmtDetalle->bindParam(':uuid_venta', $uuidVenta);
        $stmtDetalle->bindParam(':usuario_registro', $nombreUsuario);
        $stmtDetalle->bindParam(':codigo_prod', $item['id']);
        $stmtDetalle->bindParam(':concepto', $item['nombre']);
        $stmtDetalle->bindParam(':cantidad', $cantidad);
        $stmtDetalle->bindParam(':precio_venta', $precioVenta);
        $stmtDetalle->bindParam(':precio_sin_iva', $precioSinIVA);
        $stmtDetalle->bindParam(':venta_gravada', $totalItem);
        $stmtDetalle->bindParam(':venta_gravada_sin_iva', $ventaGravadaSinIVA);
        $stmtDetalle->bindParam(':iva_item', $ivaItem);
        $stmtDetalle->bindParam(':total_item', $totalItem);
        
        $stmtDetalle->execute();
        
        // Actualizar inventario (reducir existencias)
        $sqlUpdateInventario = "UPDATE tblproductos SET Existencias = Existencias - :cantidad WHERE UUIDProducto = :uuid_producto";
        $stmtUpdateInventario = $pdo->prepare($sqlUpdateInventario);
        $stmtUpdateInventario->bindParam(':cantidad', $cantidad);
        $stmtUpdateInventario->bindParam(':uuid_producto', $item['id']);
        $stmtUpdateInventario->execute();
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Venta procesada exitosamente',
        'saleId' => $uuidVenta,
        'documentNumber' => $documentNumber,
        'total' => $totalImporte,
        'cambio' => $cambio
    ]);
    
} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    // Rollback en caso de error de base de datos
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
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

// Función para generar UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Función para mapear tipo de documento
function mapDocumentType($documentType) {
    switch ($documentType) {
        case 'factura':
            return '01'; // Factura
        case 'credito':
            return '03'; // Crédito Fiscal
        case 'nota':
            return '05'; // Nota de Crédito
        default:
            return '01'; // Por defecto factura
    }
}
?>