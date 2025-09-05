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
    
    // Obtener información completa del vendedor y contribuyente
    $sqlVendedor = "SELECT UUIDContribuyente, NombreUsuario, CodPuntoVenta FROM tblcontribuyentesvendedores WHERE UUIDVendedor = :uuid_vendedor";
    $stmtVendedor = $pdo->prepare($sqlVendedor);
    $stmtVendedor->bindParam(':uuid_vendedor', $uuidVendedor);
    $stmtVendedor->execute();
    
    $vendedorData = $stmtVendedor->fetch(PDO::FETCH_ASSOC);
    
    if (!$vendedorData) {
        throw new Exception('Vendedor no encontrado');
    }
    
    $uuidContribuyente = $vendedorData['UUIDContribuyente'];
    $nombreUsuario = $vendedorData['NombreUsuario'];
    $codPuntoVenta = $vendedorData['CodPuntoVenta'];
    
    // Obtener información del contribuyente incluyendo AmbienteDTE
    $sqlContribuyente = "SELECT CodEstable, AmbienteDTE FROM tblcontribuyentes WHERE UUIDContribuyente = :uuid_contribuyente";
    $stmtContribuyente = $pdo->prepare($sqlContribuyente);
    $stmtContribuyente->bindParam(':uuid_contribuyente', $uuidContribuyente);
    $stmtContribuyente->execute();
    
    $contribuyenteData = $stmtContribuyente->fetch(PDO::FETCH_ASSOC);
    
    if (!$contribuyenteData) {
        throw new Exception('Contribuyente no encontrado');
    }
    
    $codEstable = $contribuyenteData['CodEstable'];
    $ambienteDTE = $contribuyenteData['AmbienteDTE'];
    
    // Obtener información del cliente si existe
    $codigoCLI = null;
    $nombreDeCliente = 'Cliente General';
    $codigoActividad = null;
    $actividad = null;
    // Nuevos campos del cliente
    $nombreComercial = null;
    $direccion = null;
    $idDepartamento = null;
    $departamento = null;
    $idMunicipio = null;
    $municipio = null;
    $idDistrito = null;
    $distrito = null;
    $dui = null;
    $nrc = null;
    $nit = null;
    
    if (isset($data['cliente']) && $data['cliente'] && isset($data['cliente']['id'])) {
        $sqlCliente = "SELECT UUIDCliente, NombreDeCliente, NombreComercial, Direccion, IDDepartamento, Departamento, IDMunicipio, Municipio, IDDistrito, Distrito, DUI, NRC, NIT, CodActividad, GiroComercial FROM tblcontribuyentesclientes WHERE UUIDCliente = :uuid_cliente";
        $stmtCliente = $pdo->prepare($sqlCliente);
        $stmtCliente->bindParam(':uuid_cliente', $data['cliente']['id']);
        $stmtCliente->execute();
        
        $clienteData = $stmtCliente->fetch(PDO::FETCH_ASSOC);
        
        if ($clienteData) {
            $codigoCLI = $clienteData['UUIDCliente'];
            $nombreDeCliente = $clienteData['NombreDeCliente'];
            $codigoActividad = $clienteData['CodActividad'];
            $actividad = $clienteData['GiroComercial'];
            // Asignar nuevos campos
            $nombreComercial = $clienteData['NombreComercial'];
            $direccion = $clienteData['Direccion'];
            $idDepartamento = $clienteData['IDDepartamento'];
            $departamento = $clienteData['Departamento'];
            $idMunicipio = $clienteData['IDMunicipio'];
            $municipio = $clienteData['Municipio'];
            $idDistrito = $clienteData['IDDistrito'];
            $distrito = $clienteData['Distrito'];
            $dui = $clienteData['DUI'];
            $nrc = $clienteData['NRC'];
            $nit = $clienteData['NIT'];
        }
    }
    
    // Obtener TipoMoneda desde tbltipomoneda
    $sqlTipoMoneda = "SELECT CurrencyISO FROM tbltipomoneda WHERE CurrencyISO = 'SV' LIMIT 1";
    $stmtTipoMoneda = $pdo->prepare($sqlTipoMoneda);
    $stmtTipoMoneda->execute();
    $tipoMonedaData = $stmtTipoMoneda->fetch(PDO::FETCH_ASSOC);
    $tipoMoneda = $tipoMonedaData ? $tipoMonedaData['CurrencyISO'] : 'USD';
    
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
    
    // Generar UUID independiente para la venta
    $uuidVentaIndependiente = generateUUID();
    
    // Usar el número de documento generado como CodigoVEN
    $codigoVEN = $documentNumber;
    
    // Mapear tipo de documento según especificaciones
    $codDocumento = mapDocumentTypeToCode($data['documentType']);
    
    // Obtener VersionDTE desde tbltipodedocumentos
    $sqlVersionDTE = "SELECT VersionDTE FROM tbltipodedocumentos WHERE Codigo = :cod_documento LIMIT 1";
    $stmtVersionDTE = $pdo->prepare($sqlVersionDTE);
    $stmtVersionDTE->bindParam(':cod_documento', $codDocumento);
    $stmtVersionDTE->execute();
    $versionDTEData = $stmtVersionDTE->fetch(PDO::FETCH_ASSOC);
    $versionDTE = $versionDTEData ? $versionDTEData['VersionDTE'] : 1;
    
    // Fecha de facturación (solo fecha, sin hora)
    $fechaFacturacion = date('Y-m-d');
    
    // Insertar venta principal en tblnotasdeentrega
    $sqlVenta = "INSERT INTO tblnotasdeentrega (
        UUIDVenta, CodigoVEN, CodDocumento, VersionDTE, UsuarioRegistro,
        FechaFacturacion, Ambiente, TipoMoneda,
        UUIDNegocio, UUIDSucursal, UUIDCaja, UUIDOperador, Operador, UUIDVendedor,
        CodEstablecimiento, CodPuntoVenta, TipoDespacho,
        CodigoCLI, NombreDeCliente, CodigoActividad, Actividad,
        NombreComercial, Direccion, IDDepartamento, Departamento, IDMunicipio, Municipio, IDDistrito, Distrito,
        DUI, NRC, NIT,
        SubTotalVentas, IVAPercibido, TotalImporte,
        PagoEfectivo, Cambio, Estado
    ) VALUES (
        :uuid_venta, :codigo_ven, :cod_documento, :version_dte, :usuario_registro,
        :fecha_facturacion, :ambiente, :tipo_moneda,
        :uuid_negocio, :uuid_sucursal, :uuid_caja, :uuid_operador, :operador, :uuid_vendedor,
        :cod_establecimiento, :cod_punto_venta, 1,
        :codigo_cli, :nombre_cliente, :codigo_actividad, :actividad,
        :nombre_comercial, :direccion, :id_departamento, :departamento, :id_municipio, :municipio, :id_distrito, :distrito,
        :dui, :nrc, :nit,
        :subtotal, :iva, :total_importe,
        :pago_efectivo, :cambio, 1
    )";
    
    $stmtVenta = $pdo->prepare($sqlVenta);
    $stmtVenta->bindParam(':uuid_venta', $uuidVentaIndependiente);
    $stmtVenta->bindParam(':codigo_ven', $codigoVEN);
    $stmtVenta->bindParam(':cod_documento', $codDocumento);
    $stmtVenta->bindParam(':version_dte', $versionDTE);
    $stmtVenta->bindParam(':usuario_registro', $nombreUsuario);
    $stmtVenta->bindParam(':fecha_facturacion', $fechaFacturacion);
    $stmtVenta->bindParam(':ambiente', $ambienteDTE);
    $stmtVenta->bindParam(':tipo_moneda', $tipoMoneda);
    
    // Campos adicionales según especificaciones
    $stmtVenta->bindParam(':uuid_negocio', $uuidContribuyente);
    $stmtVenta->bindParam(':uuid_sucursal', $codEstable);
    $stmtVenta->bindParam(':uuid_caja', $codPuntoVenta);
    $stmtVenta->bindParam(':uuid_operador', $uuidVendedor);
    $stmtVenta->bindParam(':operador', $uuidVendedor);
    $stmtVenta->bindParam(':uuid_vendedor', $uuidVendedor);
    $stmtVenta->bindParam(':cod_establecimiento', $codEstable);
    $stmtVenta->bindParam(':cod_punto_venta', $codPuntoVenta);
    
    // Información del cliente
    $stmtVenta->bindParam(':codigo_cli', $codigoCLI);
    $stmtVenta->bindParam(':nombre_cliente', $nombreDeCliente);
    $stmtVenta->bindParam(':codigo_actividad', $codigoActividad);
    $stmtVenta->bindParam(':actividad', $actividad);
    // Nuevos campos de cliente
    $stmtVenta->bindParam(':nombre_comercial', $nombreComercial);
    $stmtVenta->bindParam(':direccion', $direccion);
    $stmtVenta->bindParam(':id_departamento', $idDepartamento);
    $stmtVenta->bindParam(':departamento', $departamento);
    $stmtVenta->bindParam(':id_municipio', $idMunicipio);
    $stmtVenta->bindParam(':municipio', $municipio);
    $stmtVenta->bindParam(':id_distrito', $idDistrito);
    $stmtVenta->bindParam(':distrito', $distrito);
    $stmtVenta->bindParam(':dui', $dui);
    $stmtVenta->bindParam(':nrc', $nrc);
    $stmtVenta->bindParam(':nit', $nit);
    
    // Totales y pagos
    $stmtVenta->bindParam(':subtotal', $subTotal);
    $stmtVenta->bindParam(':iva', $totalIVA);
    $stmtVenta->bindParam(':total_importe', $totalImporte);
    $stmtVenta->bindParam(':pago_efectivo', $efectivoRecibido);
    
    $cambio = $efectivoRecibido - $totalImporte;
    $stmtVenta->bindParam(':cambio', $cambio);
    
    $stmtVenta->execute();
    
    // Usar el UUID independiente para los detalles
    $uuidVenta = $uuidVentaIndependiente;
    
    // Insertar detalles de la venta
    $sqlDetalle = "INSERT INTO tblnotasdeentregadetalle (
        UUIDDetalleVenta, UUIDVenta, UsuarioRegistro, NoItem, CodigoPROD,
        CodigoBarra, Concepto, TV, UnidadDeMedida, Cantidad, UnidadesVendidas, PrecioVenta, PrecioVentaSinImpuesto,
        VentaGravada, VentaGravadaSinImpuesto, IVAItem, TotalImporte, PorcentajeImpuesto,
        FechaRegistro
    ) VALUES (
        :uuid_detalle, :uuid_venta, :usuario_registro, :no_item, :codigo_prod,
        :codigo_barra, :concepto, :tv, :unidad_medida, :cantidad, :unidades_vendidas, :precio_venta, :precio_sin_iva,
        :venta_gravada, :venta_gravada_sin_iva, :iva_item, :total_item, :porcentaje_impuesto,
        NOW()
    )";
    
    $stmtDetalle = $pdo->prepare($sqlDetalle);
    
    $noItemSecuencial = 1; // Contador secuencial para NoItem
    
    foreach ($data['items'] as $item) {
        $uuidDetalle = generateUUID();
        $cantidad = intval($item['quantity']);
        $precioVenta = floatval($item['currentPrice']);
        $totalItem = $cantidad * $precioVenta;
        
        // Obtener información completa del producto desde tblcontribuyentesproductos
        $sqlProducto = "SELECT UUIDProducto, CodigoDeBarras, Descripcion, PrecioVenta, cantidadminima, preciodescuento FROM tblcontribuyentesproductos WHERE UUIDProducto = :uuid_producto";
        $stmtProducto = $pdo->prepare($sqlProducto);
        $stmtProducto->bindParam(':uuid_producto', $item['id']);
        $stmtProducto->execute();
        
        $productoData = $stmtProducto->fetch(PDO::FETCH_ASSOC);
        
        if (!$productoData) {
            throw new Exception("Producto no encontrado: " . $item['id']);
        }
        
        $codigoProd = $productoData['UUIDProducto'];
        $codigoBarra = $productoData['CodigoDeBarras'];
        $concepto = $productoData['Descripcion'];
        
        // Determinar precio según cantidad y descuentos
        $precioBase = floatval($productoData['PrecioVenta']);
        $cantidadMinima = intval($productoData['cantidadminima']);
        $precioDescuento = floatval($productoData['preciodescuento']);
        
        // Si la cantidad es mayor o igual a la cantidad mínima y hay precio de descuento
        if ($cantidad >= $cantidadMinima && $precioDescuento > 0) {
            $precioVenta = $precioDescuento;
        } else {
            $precioVenta = $precioBase;
        }
        
        // Recalcular total con el precio correcto
        $totalItem = $cantidad * $precioVenta;
        
        // Calcular precio sin IVA usando porcentaje de impuesto (13% para El Salvador)
        $porcentajeImpuesto = 13.00;
        $factorImpuesto = 1 + ($porcentajeImpuesto / 100);
        $precioSinIVA = $precioVenta / $factorImpuesto;
        $ventaGravadaSinIVA = $totalItem / $factorImpuesto;
        $ivaItem = $totalItem - $ventaGravadaSinIVA;
        
        $tv = 1;
        $unidadMedida = 99;
        $unidadesVendidas = $cantidad; // UnidadesVendidas = Cantidad
        
        $stmtDetalle->bindParam(':uuid_detalle', $uuidDetalle);
        $stmtDetalle->bindParam(':uuid_venta', $uuidVenta);
        $stmtDetalle->bindParam(':usuario_registro', $nombreUsuario);
        $stmtDetalle->bindParam(':no_item', $noItemSecuencial);
        $stmtDetalle->bindParam(':codigo_prod', $codigoProd);
        $stmtDetalle->bindParam(':codigo_barra', $codigoBarra);
        $stmtDetalle->bindParam(':concepto', $concepto);
        $stmtDetalle->bindParam(':tv', $tv);
        $stmtDetalle->bindParam(':unidad_medida', $unidadMedida);
        $stmtDetalle->bindParam(':cantidad', $cantidad);
        $stmtDetalle->bindParam(':unidades_vendidas', $unidadesVendidas);
        $stmtDetalle->bindParam(':precio_venta', $precioVenta);
        $stmtDetalle->bindParam(':precio_sin_iva', $precioSinIVA);
        $stmtDetalle->bindParam(':venta_gravada', $totalItem);
        $stmtDetalle->bindParam(':venta_gravada_sin_iva', $ventaGravadaSinIVA);
        $stmtDetalle->bindParam(':iva_item', $ivaItem);
        $stmtDetalle->bindParam(':total_item', $totalItem);
        $stmtDetalle->bindParam(':porcentaje_impuesto', $porcentajeImpuesto);
        
        $stmtDetalle->execute();
        
        // Incrementar contador secuencial para el siguiente item
        $noItemSecuencial++;
        
        // Actualizar inventario (reducir existencias)
        $sqlUpdateInventario = "UPDATE tblcontribuyentesproductos SET Existencias = Existencias - :cantidad WHERE UUIDProducto = :uuid_producto";
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
        'codigoVEN' => $codigoVEN,
        'codDocumento' => $codDocumento,
        'total' => $totalImporte,
        'cambio' => $cambio,
        'fechaFacturacion' => $fechaFacturacion
    ]);
    
} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (PDOException $e) {
    // Rollback en caso de error de base de datos
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage(),
        'sql_state' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
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

// Función para mapear tipo de documento según especificaciones
function mapDocumentTypeToCode($documentType) {
    switch (strtolower($documentType)) {
        case 'factura':
        case 'factura electronica':
            return '01'; // FACTURA
        case 'credito':
        case 'credito fiscal':
            return '03'; // CRÉDITO FISCAL
        case 'nota':
        case 'nota de credito':
            return '05'; // NOTA DE CRÉDITO
        case 'sujeto excluido':
            return '14'; // SUJETO EXCLUIDO
        default:
            return '01'; // Por defecto FACTURA
    }
}
?>