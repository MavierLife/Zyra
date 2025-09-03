<?php
/**
 * API para gestión de clientes
 * Sistema Zyra - Gestión empresarial
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sesión sin redirección automática
session_start();

require_once '../Config/Conexion.php';
require_once '../includes/permisos.php';

// Verificar autenticación para API
if (!isset($_SESSION['vendedor_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Debe iniciar sesión.'
    ]);
    exit();
}

// Variables de sesión necesarias
$uuidContribuyente = $_SESSION['uuid_contribuyente'] ?? null;

// Conexión a la base de datos
$conexion = new Conexion();
$pdo = $conexion->getPdo();

// Inicializar permisos en la sesión
inicializarPermisos();

// No se requiere permiso específico para visualizar clientes
// Los permisos se verifican por acción específica (crear, editar, eliminar)

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet();
            break;
        case 'POST':
            handlePost();
            break;
        case 'PUT':
            handlePut();
            break;
        case 'DELETE':
            handleDelete();
            break;
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log('Error en API clientes: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

/**
 * Manejar peticiones GET
 */
function handleGet() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        getSingleClient($id);
    } else {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'stats':
                getClientStats();
                break;
            case 'list':
            default:
                getAllClients();
                break;
        }
    }
}

/**
 * Obtener estadísticas de clientes
 */
function getClientStats() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("\n            SELECT \n                COUNT(*) as total,\n                SUM(CASE WHEN Estado = 1 THEN 1 ELSE 0 END) as activos,\n                SUM(CASE WHEN Estado = 0 THEN 1 ELSE 0 END) as inactivos,\n                SUM(CASE WHEN Contribuyente = 0 THEN 1 ELSE 0 END) as naturales,\n                SUM(CASE WHEN Contribuyente = 1 THEN 1 ELSE 0 END) as juridicas\n            FROM tblcontribuyentesclientes\n        ");
        
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    } catch (PDOException $e) {
        error_log('Error getting client stats: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener estadísticas de clientes'
        ]);
    }
}

/**
 * Obtener un cliente específico
 */
function getSingleClient($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                UUIDCliente,
                FechaRegistro,
                NombreDeCliente,
                Telefono,
                CorreoElectronico,
                Direccion,
                DUI,
                NIT,
                NRC,
                CASE WHEN Contribuyente = 1 THEN 'Jurídica' ELSE 'Natural' END AS TipoDePersona,
                GiroComercial AS Giro,
                CASE WHEN Estado = 1 THEN 'Activo' ELSE 'Inactivo' END AS Estado,
                0 AS DescuentoGeneral,
                MontoMaximoEstablecido AS LimiteDeCredito,
                PlazoEstablecido AS DiasDeCredito,
                NULL AS VendedorAsignado,
                Observaciones
            FROM tblcontribuyentesclientes 
            WHERE UUIDCliente = ?
        ");
        
        $stmt->execute([$id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            echo json_encode([
                'success' => true,
                'data' => $client
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ]);
        }
    } catch (PDOException $e) {
        error_log('Error getting single client: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener el cliente'
        ]);
    }
}

/**
 * Obtener todos los clientes
 */
function getAllClients() {
    global $pdo;
    
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(
                NombreDeCliente LIKE ? OR 
                Telefono LIKE ? OR 
                CorreoElectronico LIKE ? OR 
                DUI LIKE ? OR 
                NIT LIKE ?
            )";
            $searchParam = '%' . $search . '%';
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        if (!empty($status)) {
            $whereConditions[] = "Estado = ?";
            $params[] = ($status === 'Activo') ? 1 : 0;
        }
        
        if (!empty($type)) {
            $whereConditions[] = "Contribuyente = ?";
            $params[] = ($type === 'Jurídica') ? 1 : 0;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Obtener total de registros
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tblcontribuyentesclientes $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Obtener clientes paginados
        $stmt = $pdo->prepare("
            SELECT 
                UUIDCliente,
                FechaRegistro,
                NombreDeCliente,
                Telefono,
                CorreoElectronico,
                Direccion,
                DUI,
                NIT,
                NRC,
                CASE WHEN Contribuyente = 1 THEN 'Jurídica' ELSE 'Natural' END AS TipoDePersona,
                GiroComercial AS Giro,
                CASE WHEN Estado = 1 THEN 'Activo' ELSE 'Inactivo' END AS Estado,
                0 AS DescuentoGeneral,
                MontoMaximoEstablecido AS LimiteDeCredito,
                PlazoEstablecido AS DiasDeCredito,
                NULL AS VendedorAsignado,
                Observaciones
            FROM tblcontribuyentesclientes 
            $whereClause
            ORDER BY FechaRegistro DESC, NombreDeCliente ASC
            LIMIT $limit OFFSET $offset
        ");
        
        $stmt->execute($params);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $clients,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($total),
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (PDOException $e) {
        error_log('Error getting all clients: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los clientes'
        ]);
    }
}

/**
 * Manejar peticiones POST (crear cliente)
 */
function handlePost() {
    global $pdo;
    
    // Verificar permiso para crear clientes
    if (!tienePermiso('Perm_CrearClientesProveedores')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para crear clientes.'
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos'
        ]);
        return;
    }
    
    // Validaciones
    $errors = validateClientData($input);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos',
            'errors' => $errors
        ]);
        return;
    }
    
    try {
        $uuid = generateUUID();
        
        // Mapear campos al esquema real
        $tipoPersonaVal = (($input['tipoPersona'] ?? 'Natural') === 'Jurídica') ? 1 : 0; // Contribuyente: 1=Jurídica, 0=Natural
        $estadoVal = (($input['estado'] ?? 'Activo') === 'Activo') ? 1 : 0; // Estado tinyint
        $limiteCreditoVal = floatval($input['limiteCredito'] ?? 0); // MontoMaximoEstablecido
        $diasCreditoVal = intval($input['diasCredito'] ?? 0); // PlazoEstablecido
        $usuarioRegistro = $_SESSION['vendedor_id'] ?? null;
        $uuidContribuyenteVal = $_SESSION['uuid_contribuyente'] ?? null;
        
        $stmt = $pdo->prepare("\n            INSERT INTO tblcontribuyentesclientes (\n                UUIDCliente, FechaRegistro, NombreDeCliente, Telefono, \n                CorreoElectronico, Direccion, DUI, NIT, NRC, Contribuyente,\n                GiroComercial, Estado, MontoMaximoEstablecido, PlazoEstablecido,\n                Observaciones, UUIDContribuyente, UsuarioRegistro\n            ) VALUES (\n                ?, NOW(),\n                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?\n            )\n        ");
        
        $stmt->execute([
            $uuid,
            $input['nombreCliente'],
            $input['telefono'] ?? null,
            $input['correoElectronico'] ?? null,
            $input['direccion'] ?? null,
            $input['dui'] ?? null,
            $input['nit'] ?? null,
            $input['nrc'] ?? null,
            $tipoPersonaVal,
            $input['giro'] ?? null,
            $estadoVal,
            $limiteCreditoVal,
            $diasCreditoVal,
            $input['observaciones'] ?? null,
            $uuidContribuyenteVal,
            $usuarioRegistro
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'data' => ['id' => $uuid]
        ]);
    } catch (PDOException $e) {
        error_log('Error creating client: ' . $e->getMessage());
        
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Ya existe un cliente con esos datos'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear el cliente'
            ]);
        }
    }
}

/**
 * Manejar peticiones PUT (actualizar cliente)
 */
function handlePut() {
    global $pdo;
    
    // Verificar permiso para editar clientes
    if (!tienePermiso('Perm_EditarEliminarClientesProveedores')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para editar clientes.'
        ]);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de cliente requerido'
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos'
        ]);
        return;
    }
    
    // Validaciones
    $errors = validateClientData($input, $id);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos',
            'errors' => $errors
        ]);
        return;
    }
    
    try {
        // Mapear entradas al esquema real
        $tipoPersonaVal = (($input['tipoPersona'] ?? 'Natural') === 'Jurídica') ? 1 : 0; // Contribuyente
        $estadoVal = (($input['estado'] ?? 'Activo') === 'Activo') ? 1 : 0; // Estado tinyint
        $limiteCreditoVal = floatval($input['limiteCredito'] ?? 0); // MontoMaximoEstablecido
        $diasCreditoVal = intval($input['diasCredito'] ?? 0); // PlazoEstablecido

        $stmt = $pdo->prepare("
            UPDATE tblcontribuyentesclientes SET
                NombreDeCliente = ?,
                Telefono = ?,
                CorreoElectronico = ?,
                Direccion = ?,
                DUI = ?,
                NIT = ?,
                NRC = ?,
                Contribuyente = ?,
                GiroComercial = ?,
                Estado = ?,
                MontoMaximoEstablecido = ?,
                PlazoEstablecido = ?,
                Observaciones = ?,
                UsuarioUpdate = ? ,
                FechaUpdate = NOW()
            WHERE UUIDCliente = ?
        ");
        
        $result = $stmt->execute([
            $input['nombreCliente'],
            $input['telefono'] ?? null,
            $input['correoElectronico'] ?? null,
            $input['direccion'] ?? null,
            $input['dui'] ?? null,
            $input['nit'] ?? null,
            $input['nrc'] ?? null,
            $tipoPersonaVal,
            $input['giro'] ?? null,
            $estadoVal,
            $limiteCreditoVal,
            $diasCreditoVal,
            $input['observaciones'] ?? null,
            ($_SESSION['vendedor_id'] ?? null),
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ]);
        }
    } catch (PDOException $e) {
        error_log('Error updating client: ' . $e->getMessage());
        
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Ya existe un cliente con esos datos'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar el cliente'
            ]);
        }
    }
}

/**
 * Manejar peticiones DELETE (eliminar cliente)
 */
function handleDelete() {
    global $pdo;
    
    // Verificar permiso para eliminar clientes
    if (!tienePermiso('Perm_EditarEliminarClientesProveedores')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para eliminar clientes.'
        ]);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de cliente requerido'
        ]);
        return;
    }
    
    try {
        // Verificar si el cliente tiene transacciones asociadas
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM tblventas WHERE UUIDCliente = ?
        ");
        $checkStmt->execute([$id]);
        $hasTransactions = $checkStmt->fetchColumn() > 0;
        
        if ($hasTransactions) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'No se puede eliminar el cliente porque tiene transacciones asociadas'
            ]);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM tblcontribuyentesclientes WHERE UUIDCliente = ?");
        $result = $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Cliente eliminado exitosamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ]);
        }
    } catch (PDOException $e) {
        error_log('Error deleting client: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar el cliente'
        ]);
    }
}

/**
 * Validar datos del cliente
 */
function validateClientData($data, $excludeId = null) {
    global $pdo;
    $errors = [];
    
    // Nombre requerido
    if (empty($data['nombreCliente']) || strlen(trim($data['nombreCliente'])) < 2) {
        $errors[] = 'El nombre del cliente es requerido y debe tener al menos 2 caracteres';
    }
    
    // Validar email si se proporciona
    if (!empty($data['correoElectronico']) && !filter_var($data['correoElectronico'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido';
    }
    
    // Validar DUI si se proporciona
    if (!empty($data['dui']) && !preg_match('/^\d{8}-\d$/', $data['dui'])) {
        $errors[] = 'El formato del DUI no es válido (debe ser: 12345678-9)';
    }
    
    // Validar NIT si se proporciona
    if (!empty($data['nit']) && !preg_match('/^\d{4}-\d{6}-\d{3}-\d$/', $data['nit'])) {
        $errors[] = 'El formato del NIT no es válido (debe ser: 1234-567890-123-4)';
    }
    
    // Validar tipo de persona
    if (!empty($data['tipoPersona']) && !in_array($data['tipoPersona'], ['Natural', 'Jurídica'])) {
        $errors[] = 'El tipo de persona debe ser Natural o Jurídica';
    }
    
    // Validar estado
    if (!empty($data['estado']) && !in_array($data['estado'], ['Activo', 'Inactivo'])) {
        $errors[] = 'El estado debe ser Activo o Inactivo';
    }
    
    // Verificar duplicados por DUI
    if (!empty($data['dui'])) {
        $checkStmt = $pdo->prepare(
            "SELECT UUIDCliente FROM tblcontribuyentesclientes WHERE DUI = ?" . 
            ($excludeId ? " AND UUIDCliente != ?" : "")
        );
        $params = [$data['dui']];
        if ($excludeId) {
            $params[] = $excludeId;
        }
        $checkStmt->execute($params);
        if ($checkStmt->fetch()) {
            $errors[] = 'Ya existe un cliente con ese DUI';
        }
    }
    
    // Verificar duplicados por NIT
    if (!empty($data['nit'])) {
        $checkStmt = $pdo->prepare(
            "SELECT UUIDCliente FROM tblcontribuyentesclientes WHERE NIT = ?" . 
            ($excludeId ? " AND UUIDCliente != ?" : "")
        );
        $params = [$data['nit']];
        if ($excludeId) {
            $params[] = $excludeId;
        }
        $checkStmt->execute($params);
        if ($checkStmt->fetch()) {
            $errors[] = 'Ya existe un cliente con ese NIT';
        }
    }
    
    return $errors;
}

/**
 * Generar UUID
 */
function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>