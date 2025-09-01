<?php
// Incluir configuración de base de datos
require_once '../Config/Conexion.php';

// Configurar headers para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Obtener conexión a la base de datos
    $conexion = new Conexion();
    $pdo = $conexion->getPdo();
    
    // Obtener método HTTP y acción
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Obtener UUIDContribuyente de la sesión o parámetros
    session_start();
    $uuidContribuyente = isset($_SESSION['uuid_contribuyente']) ? $_SESSION['uuid_contribuyente'] : 
                        (isset($_GET['uuid_contribuyente']) ? $_GET['uuid_contribuyente'] : null);
    
    if (!$uuidContribuyente) {
        echo json_encode([
            'success' => false,
            'message' => 'Sesión no válida. UUID de contribuyente no encontrado.',
            'error_code' => 'SESSION_INVALID'
        ]);
        exit();
    }
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                obtenerCategorias($pdo, $uuidContribuyente);
            } else {
                throw new Exception('Acción GET no válida');
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                crearCategoria($pdo, $uuidContribuyente);
            } else {
                throw new Exception('Acción POST no válida');
            }
            break;
            
        case 'PUT':
            if ($action === 'update') {
                actualizarCategoria($pdo, $uuidContribuyente);
            } else {
                throw new Exception('Acción PUT no válida');
            }
            break;
            
        case 'DELETE':
            if ($action === 'delete') {
                eliminarCategoria($pdo, $uuidContribuyente);
            } else {
                throw new Exception('Acción DELETE no válida');
            }
            break;
            
        default:
            throw new Exception('Método HTTP no soportado');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Obtener todas las categorías del contribuyente
 */
function obtenerCategorias($pdo, $uuidContribuyente) {
    try {
        $sql = "SELECT IDCategoria, Categoria 
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
        throw new Exception('Error al obtener categorías: ' . $e->getMessage());
    }
}

/**
 * Crear nueva categoría
 */
function crearCategoria($pdo, $uuidContribuyente) {
    try {
        // Obtener datos del cuerpo de la petición
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['nombre']) || empty(trim($input['nombre']))) {
            throw new Exception('El nombre de la categoría es requerido');
        }
        
        $nombreCategoria = trim($input['nombre']);
        
        // Verificar que la categoría no exista ya
        $sqlCheck = "SELECT COUNT(*) FROM tblcategorias 
                     WHERE UUIDContribuyente = ? AND Categoria = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$uuidContribuyente, $nombreCategoria]);
        
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception('Ya existe una categoría con ese nombre');
        }
        
        // Insertar nueva categoría
        $sql = "INSERT INTO tblcategorias (UUIDContribuyente, Categoria) 
                VALUES (?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uuidContribuyente, $nombreCategoria]);
        
        $idCategoria = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Categoría creada exitosamente',
            'data' => [
                'IDCategoria' => $idCategoria,
                'Categoria' => $nombreCategoria
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al crear categoría: ' . $e->getMessage());
    }
}

/**
 * Actualizar categoría existente
 */
function actualizarCategoria($pdo, $uuidContribuyente) {
    try {
        // Obtener datos del cuerpo de la petición
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['nombre'])) {
            throw new Exception('ID y nombre de la categoría son requeridos');
        }
        
        $idCategoria = $input['id'];
        $nombreCategoria = trim($input['nombre']);
        
        if (empty($nombreCategoria)) {
            throw new Exception('El nombre de la categoría no puede estar vacío');
        }
        
        // Verificar que la categoría pertenece al contribuyente
        $sqlCheck = "SELECT COUNT(*) FROM tblcategorias 
                     WHERE IDCategoria = ? AND UUIDContribuyente = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$idCategoria, $uuidContribuyente]);
        
        if ($stmtCheck->fetchColumn() == 0) {
            throw new Exception('Categoría no encontrada o no autorizada');
        }
        
        // Verificar que no exista otra categoría con el mismo nombre
        $sqlDuplicate = "SELECT COUNT(*) FROM tblcategorias 
                         WHERE UUIDContribuyente = ? AND Categoria = ? AND IDCategoria != ?";
        $stmtDuplicate = $pdo->prepare($sqlDuplicate);
        $stmtDuplicate->execute([$uuidContribuyente, $nombreCategoria, $idCategoria]);
        
        if ($stmtDuplicate->fetchColumn() > 0) {
            throw new Exception('Ya existe otra categoría con ese nombre');
        }
        
        // Actualizar categoría
        $sql = "UPDATE tblcategorias 
                SET Categoria = ? 
                WHERE IDCategoria = ? AND UUIDContribuyente = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombreCategoria, $idCategoria, $uuidContribuyente]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'data' => [
                'IDCategoria' => $idCategoria,
                'Categoria' => $nombreCategoria
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al actualizar categoría: ' . $e->getMessage());
    }
}

/**
 * Eliminar categoría
 */
function eliminarCategoria($pdo, $uuidContribuyente) {
    try {
        // Obtener ID de la categoría
        $idCategoria = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$idCategoria) {
            throw new Exception('ID de categoría es requerido');
        }
        
        // Verificar que la categoría pertenece al contribuyente
        $sqlCheck = "SELECT COUNT(*) FROM tblcategorias 
                     WHERE IDCategoria = ? AND UUIDContribuyente = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$idCategoria, $uuidContribuyente]);
        
        if ($stmtCheck->fetchColumn() == 0) {
            throw new Exception('Categoría no encontrada o no autorizada');
        }
        
        // Verificar si hay productos asociados a esta categoría
        $sqlProducts = "SELECT COUNT(*) FROM tblcontribuyentesproductos 
                        WHERE IDCategoria = ? AND UUIDContribuyente = ?";
        $stmtProducts = $pdo->prepare($sqlProducts);
        $stmtProducts->execute([$idCategoria, $uuidContribuyente]);
        
        if ($stmtProducts->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar la categoría porque tiene productos asociados');
        }
        
        // Eliminar categoría
        $sql = "DELETE FROM tblcategorias 
                WHERE IDCategoria = ? AND UUIDContribuyente = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idCategoria, $uuidContribuyente]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al eliminar categoría: ' . $e->getMessage());
    }
}
?>