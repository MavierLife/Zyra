<?php
/**
 * API para obtener actividades económicas
 * Sistema Zyra - Gestión empresarial
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../Config/Conexion.php';

try {
    $conexion = Conexion::obtenerConexion()->getPdo();
    
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $stmt = $conexion->prepare("SELECT IDActividad as id, CodigoActividad as codigo, DescripcionActividad as descripcion FROM tblcatalogodeactividades ORDER BY DescripcionActividad");
            $stmt->execute();
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $actividades
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>