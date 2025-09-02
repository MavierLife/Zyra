<?php
/**
 * API para manejar operaciones de proveedores
 * Conecta con la base de datos real del sistema Zyra
 */

// Configurar codificación interna
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['vendedor_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Obtener UUIDContribuyente de la sesión
if (!isset($_SESSION['uuid_contribuyente'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión inválida - UUIDContribuyente no encontrado']);
    exit();
}

require_once '../Config/Conexion.php';

try {
    $conexion = new Conexion();
    $pdo = $conexion->getPdo();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$request = $_GET['action'] ?? '';

$uuidContribuyente = $_SESSION['uuid_contribuyente'];

switch ($method) {
    case 'GET':
        if ($request === 'proveedores') {
            obtenerProveedores($pdo, $uuidContribuyente);
        } elseif ($request === 'estadisticas') {
            obtenerEstadisticas($pdo, $uuidContribuyente);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
        break;
        
    case 'POST':
        if ($request === 'proveedor') {
            $data = json_decode(file_get_contents('php://input'), true);
            crearProveedor($pdo, $uuidContribuyente, $data);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
        break;
        
    case 'PUT':
        if ($request === 'proveedor') {
            $data = json_decode(file_get_contents('php://input'), true);
            actualizarProveedor($pdo, $uuidContribuyente, $data);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
        break;
        
    case 'DELETE':
        if ($request === 'proveedor') {
            $supplierId = $_GET['id'] ?? null;
            if ($supplierId) {
                eliminarProveedor($pdo, $uuidContribuyente, $supplierId);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID de proveedor requerido']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

/**
 * Obtener lista de proveedores
 */
function obtenerProveedores($pdo, $uuidContribuyente) {
    try {
        $sql = "SELECT 
                    UUIDProveedor,
                    RazonSocial,
                    Celular,
                    Documento,
                    CorrecoElectronico as CorreoElectronico,
                    Direccion
                FROM tblcontribuyentesproveedores 
                WHERE UUIDContribuyente = :uuid_contribuyente
                ORDER BY RazonSocial ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':uuid_contribuyente', $uuidContribuyente, PDO::PARAM_STR);
        $stmt->execute();
        
        $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear datos para el frontend
        $proveedoresFormateados = [];
        foreach ($proveedores as $proveedor) {
            $proveedoresFormateados[] = [
                'id' => $proveedor['UUIDProveedor'],
                'razonSocial' => $proveedor['RazonSocial'],
                'celular' => $proveedor['Celular'] ?? '',
                'documento' => $proveedor['Documento'] ?? '',
                'correoElectronico' => $proveedor['CorreoElectronico'],
                'direccion' => $proveedor['Direccion'],
                'estado' => 'Activo' // Por defecto, ya que no hay campo de estado en la tabla
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $proveedoresFormateados
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error al obtener proveedores: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener estadísticas de proveedores
 */
function obtenerEstadisticas($pdo, $uuidContribuyente) {
    try {
        // Total de proveedores
        $sqlTotal = "SELECT COUNT(*) as total FROM tblcontribuyentesproveedores WHERE UUIDContribuyente = :uuid_contribuyente";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->bindParam(':uuid_contribuyente', $uuidContribuyente, PDO::PARAM_STR);
        $stmtTotal->execute();
        $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Proveedores nuevos este mes (asumiendo que no hay campo de fecha, usamos el total)
        $nuevosEsteMes = 0; // Sin campo de fecha de registro, no podemos calcular esto
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total' => (int)$total,
                'activos' => (int)$total, // Todos se consideran activos por defecto
                'nuevosEsteMes' => $nuevosEsteMes
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
        ]);
    }
}

/**
 * Crear nuevo proveedor
 */
function crearProveedor($pdo, $uuidContribuyente, $data) {
    try {
        // Validar datos requeridos
        if (empty($data['razonSocial']) || empty($data['documento'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            return;
        }
        
        // Generar UUID para el proveedor
        $uuidProveedor = generateUUID();
        
        $sql = "INSERT INTO tblcontribuyentesproveedores 
                (UUIDProveedor, UUIDContribuyente, RazonSocial, Celular, Documento, CorrecoElectronico, Direccion) 
                VALUES (:uuid_proveedor, :uuid_contribuyente, :razon_social, :celular, :documento, :correo, :direccion)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':uuid_proveedor', $uuidProveedor, PDO::PARAM_STR);
        $stmt->bindParam(':uuid_contribuyente', $uuidContribuyente, PDO::PARAM_STR);
        $stmt->bindParam(':razon_social', $data['razonSocial'], PDO::PARAM_STR);
        $stmt->bindParam(':celular', $data['celular'], PDO::PARAM_STR);
        $stmt->bindParam(':documento', $data['documento'], PDO::PARAM_STR);
        $correo = isset($data['correoElectronico']) ? $data['correoElectronico'] : '';
        $direccion = isset($data['direccion']) ? $data['direccion'] : '';
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'id' => $uuidProveedor
            ]);
        } else {
            throw new Exception('Error al insertar proveedor');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error al crear proveedor: ' . $e->getMessage()
        ]);
    }
}

/**
 * Actualizar proveedor existente
 */
function actualizarProveedor($pdo, $uuidContribuyente, $data) {
    try {
        // Validar datos requeridos
        if (empty($data['id']) || empty($data['razonSocial']) || empty($data['documento'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            return;
        }
        
        $sql = "UPDATE tblcontribuyentesproveedores 
                SET RazonSocial = :razon_social, 
                    Celular = :celular, 
                    Documento = :documento, 
                    CorrecoElectronico = :correo, 
                    Direccion = :direccion
                WHERE UUIDProveedor = :uuid_proveedor 
                AND UUIDContribuyente = :uuid_contribuyente";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':razon_social', $data['razonSocial'], PDO::PARAM_STR);
        $stmt->bindParam(':celular', $data['celular'], PDO::PARAM_STR);
        $stmt->bindParam(':documento', $data['documento'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $data['correoElectronico'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $data['direccion'], PDO::PARAM_STR);
        $stmt->bindParam(':uuid_proveedor', $data['id'], PDO::PARAM_STR);
        $stmt->bindParam(':uuid_contribuyente', $uuidContribuyente, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Proveedor actualizado exitosamente'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Proveedor no encontrado']);
            }
        } else {
            throw new Exception('Error al actualizar proveedor');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error al actualizar proveedor: ' . $e->getMessage()
        ]);
    }
}

/**
 * Eliminar proveedor
 */
function eliminarProveedor($pdo, $uuidContribuyente, $supplierId) {
    try {
        $sql = "DELETE FROM tblcontribuyentesproveedores 
                WHERE UUIDProveedor = :uuid_proveedor 
                AND UUIDContribuyente = :uuid_contribuyente";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':uuid_proveedor', $supplierId, PDO::PARAM_STR);
        $stmt->bindParam(':uuid_contribuyente', $uuidContribuyente, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Proveedor eliminado exitosamente'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Proveedor no encontrado']);
            }
        } else {
            throw new Exception('Error al eliminar proveedor');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error al eliminar proveedor: ' . $e->getMessage()
        ]);
    }
}

/**
 * Generar UUID v4
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>