<?php
/**
 * API para manejar operaciones de inventario
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
        if ($request === 'productos') {
            obtenerProductos($pdo, $uuidContribuyente);
        } elseif ($request === 'estadisticas') {
            obtenerEstadisticas($pdo, $uuidContribuyente);
        } elseif ($request === 'categorias') {
            obtenerCategorias($pdo, $uuidContribuyente);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
        break;
        
    case 'POST':
        if ($request === 'producto') {
            $data = json_decode(file_get_contents('php://input'), true);
            crearProducto($pdo, $uuidContribuyente, $data);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
        break;
        
    case 'PUT':
        if ($request === 'producto') {
            $data = json_decode(file_get_contents('php://input'), true);
            actualizarProducto($pdo, $uuidContribuyente, $data);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
        break;
        
    case 'DELETE':
        if ($request === 'producto') {
            $productId = $_GET['id'] ?? null;
            if ($productId) {
                eliminarProducto($pdo, $uuidContribuyente, $productId);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID de producto requerido']);
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
 * Obtener todos los productos del inventario
 */
function obtenerProductos($pdo, $uuidContribuyente) {
    try {
        $sql = "SELECT 
                    p.UUIDProducto as id,
                    p.CodigoDeBarras as codigo,
                    p.Descripcion as nombre,
                    p.Existencias as cantidad,
                    p.PrecioVenta as precio,
                    p.CostoCompra as costo,
                    p.IDCategoria,
                    c.Categoria as categoria,
                    (p.PrecioVenta - p.CostoCompra) as ganancia,
                    CASE 
                        WHEN p.PrecioVenta > 0 THEN ROUND(((p.PrecioVenta - p.CostoCompra) / p.PrecioVenta) * 100, 1)
                        ELSE 0
                    END as porcentaje
                FROM tblcontribuyentesproductos p
                LEFT JOIN tblcategorias c ON p.IDCategoria = c.IDCategoria AND c.UUIDContribuyente = ?
                WHERE p.UUIDContribuyente = ?
                ORDER BY p.Descripcion ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uuidContribuyente, $uuidContribuyente]);
        
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear los datos para el frontend
        foreach ($productos as &$producto) {
            $producto['precio'] = (float) $producto['precio'];
            $producto['costo'] = (float) $producto['costo'];
            $producto['ganancia'] = (float) $producto['ganancia'];
            $producto['cantidad'] = (int) $producto['cantidad'];
            $producto['porcentaje'] = (int) $producto['porcentaje'];
            $producto['categoria'] = $producto['categoria'] ?? 'Sin categoría';
        }
        
        echo json_encode([
            'success' => true,
            'data' => $productos
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()]);
    }
}

/**
 * Obtener estadísticas del inventario
 */
function obtenerEstadisticas($pdo, $uuidContribuyente) {
    try {
        $sql = "SELECT 
                    COUNT(*) as total_productos,
                    SUM(p.PrecioVenta * p.Existencias) as valor_total,
                    SUM(CASE WHEN p.Existencias < 10 THEN 1 ELSE 0 END) as stock_bajo,
                    SUM((p.PrecioVenta - p.CostoCompra) * p.Existencias) as ganancia_total
                FROM tblcontribuyentesproductos p
                WHERE p.UUIDContribuyente = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uuidContribuyente]);
        
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Formatear los datos
        $estadisticas['total_productos'] = (int) $estadisticas['total_productos'];
        $estadisticas['valor_total'] = (float) $estadisticas['valor_total'];
        $estadisticas['stock_bajo'] = (int) $estadisticas['stock_bajo'];
        $estadisticas['ganancia_total'] = (float) $estadisticas['ganancia_total'];
        
        echo json_encode([
            'success' => true,
            'data' => $estadisticas
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener estadísticas: ' . $e->getMessage()]);
    }
}

/**
 * Obtener categorías disponibles
 */
function obtenerCategorias($pdo, $uuidContribuyente) {
    try {
        $sql = "SELECT IDCategoria as id, Categoria as name
                FROM tblcategorias
                WHERE UUIDContribuyente = ?
                ORDER BY Categoria ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uuidContribuyente]);
        
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $categorias
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener categorías: ' . $e->getMessage()]);
    }
}

/**
 * Crear un nuevo producto
 */
function crearProducto($pdo, $uuidContribuyente, $data) {
    try {
        // Validar datos requeridos
        if (!isset($data['codigo']) || !isset($data['nombre']) || !isset($data['precio']) || !isset($data['costo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            return;
        }
        
        // Verificar que el código no exista
        $sqlCheck = "SELECT COUNT(*) as count FROM tblcontribuyentesproductos 
                     WHERE CodigoDeBarras = ? AND UUIDContribuyente = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$data['codigo'], $uuidContribuyente]);
        
        if ($stmtCheck->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe un producto con este código']);
            return;
        }
        
        // Insertar producto
        $sql = "INSERT INTO tblcontribuyentesproductos 
                (UUIDContribuyente, CodigoDeBarras, Descripcion, Existencias, PrecioVenta, CostoCompra, IDCategoria)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $uuidContribuyente,
            $data['codigo'],
            $data['nombre'],
            $data['cantidad'] ?? 0,
            $data['precio'],
            $data['costo'],
            $data['categoria'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto creado exitosamente',
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear producto: ' . $e->getMessage()]);
    }
}

/**
 * Actualizar un producto existente
 */
function actualizarProducto($pdo, $uuidContribuyente, $data) {
    try {
        // Validar datos requeridos
        if (!isset($data['id']) || !isset($data['nombre']) || !isset($data['precio']) || !isset($data['costo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            return;
        }
        
        // Actualizar producto
        $sql = "UPDATE tblcontribuyentesproductos 
                SET Descripcion = ?, Existencias = ?, PrecioVenta = ?, CostoCompra = ?, IDCategoria = ?
                WHERE UUIDProducto = ? AND UUIDContribuyente = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['nombre'],
            $data['cantidad'] ?? 0,
            $data['precio'],
            $data['costo'],
            $data['categoria'] ?? null,
            $data['id'],
            $uuidContribuyente
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Producto actualizado exitosamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar producto: ' . $e->getMessage()]);
    }
}

/**
 * Eliminar un producto
 */
function eliminarProducto($pdo, $uuidContribuyente, $productId) {
    try {
        $sql = "DELETE FROM tblcontribuyentesproductos 
                WHERE UUIDProducto = ? AND UUIDContribuyente = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $uuidContribuyente]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar producto: ' . $e->getMessage()]);
    }
}

?>