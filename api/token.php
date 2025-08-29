<?php
/**
 * API Endpoint para gestión de tokens de verificación
 * Maneja las solicitudes de generación y verificación de tokens
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Solo se acepta POST.'
    ]);
    exit();
}

require_once '../controller/TokenController.php';

try {
    // Obtener datos JSON del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido en la solicitud');
    }
    
    // Validar que se especifique la acción
    if (!isset($input['action'])) {
        throw new Exception('Acción no especificada');
    }
    
    $tokenController = new TokenController();
    $response = [];
    
    switch ($input['action']) {
        case 'solicitar_token':
            // Validar datos requeridos
            if (empty($input['telefono'])) {
                $response = [
                    'success' => false,
                    'message' => 'Número de teléfono es requerido'
                ];
                break;
            }
            
            // Procesar solicitud de token
            $response = $tokenController->procesarSolicitudToken($input['telefono']);
            break;
            
        case 'verificar_token':
            // Validar datos requeridos
            if (empty($input['telefono']) || empty($input['token'])) {
                $response = [
                    'success' => false,
                    'message' => 'Teléfono y token son requeridos'
                ];
                break;
            }
            
            // Verificar token
            $response = $tokenController->verificarToken($input['telefono'], $input['token']);
            
            // Si la verificación es exitosa, crear sesión
            if ($response['success']) {
                session_start();
                $_SESSION['vendedor_id'] = $response['data']['vendedor_id'];
                $_SESSION['nombre_usuario'] = $response['data']['nombre_usuario'];
                $_SESSION['rol'] = $response['data']['rol'];
                $_SESSION['uuid_contribuyente'] = $response['data']['uuid_contribuyente'];
                $_SESSION['login_time'] = time();
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                
                $response['data']['session_id'] = session_id();
            }
            break;
            
        case 'obtener_datos_vendedor':
            // Validar datos requeridos
            if (empty($input['telefono'])) {
                $response = [
                    'success' => false,
                    'message' => 'Número de teléfono es requerido'
                ];
                break;
            }
            
            // Obtener datos del vendedor
            $vendedor = $tokenController->obtenerDatosVendedor($input['telefono']);
            
            if ($vendedor) {
                $response = [
                    'success' => true,
                    'message' => 'Datos obtenidos exitosamente',
                    'data' => [
                        'nombre_usuario' => $vendedor['nombre_usuario'],
                        'rol' => $vendedor['rol'],
                        'uuid_contribuyente' => $vendedor['uuid_contribuyente'],
                        'cod_punto_venta' => $vendedor['cod_punto_venta']
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Vendedor no encontrado'
                ];
            }
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => 'Acción no válida'
            ];
            break;
    }
    
    // Registrar actividad en log
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'action' => $input['action'],
        'success' => $response['success'],
        'telefono' => isset($input['telefono']) ? substr($input['telefono'], -4) : 'N/A' // Solo últimos 4 dígitos por privacidad
    ];
    
    error_log('Token API Activity: ' . json_encode($logData));
    
} catch (Exception $e) {
    error_log('Error en token API: ' . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ];
    
    http_response_code(500);
}

// Enviar respuesta
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();

?>