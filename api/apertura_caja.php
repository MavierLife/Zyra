<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../controller/AperturaCajaController.php';

try {
    $controller = new AperturaCajaController();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // Abrir caja
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['efectivo_apertura'])) {
                throw new Exception('El monto de apertura es requerido');
            }
            
            $efectivoApertura = $input['efectivo_apertura'];
            $data = $controller->abrirCaja($efectivoApertura);
            
            echo json_encode([
                'success' => true,
                'message' => 'Caja abierta exitosamente',
                'data' => $data
            ]);
            break;
            
        case 'GET':
            // Verificar estado de caja
            $estadoCaja = $controller->verificarEstadoCaja();
            
            echo json_encode([
                'success' => true
            ] + $estadoCaja);
            break;
            
        default:
            throw new Exception('Método no permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>