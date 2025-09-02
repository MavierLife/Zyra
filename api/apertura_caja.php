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

require_once '../Config/Conexion.php';
require_once '../includes/config.php';
require_once '../includes/permisos.php';

try {
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['vendedor_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        throw new Exception('Usuario no autenticado');
    }

    // Verificar permisos
    if (!tienePermiso('Perm_AbrirCaja')) {
        throw new Exception('No tienes permisos para abrir caja');
    }

    $conexion = new Conexion();
    $pdo = $conexion->getPdo();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // Abrir caja
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['efectivo_apertura']) || !is_numeric($input['efectivo_apertura'])) {
                throw new Exception('El monto de apertura es requerido y debe ser numérico');
            }
            
            $efectivoApertura = floatval($input['efectivo_apertura']);
            
            if ($efectivoApertura < 0) {
                throw new Exception('El monto de apertura no puede ser negativo');
            }
            
            // Generar UUID único para la apertura
            $uuidApertura = uniqid('APT_', true) . '_' . time();
            
            // Obtener datos de la sesión
            $usuarioApertura = $_SESSION['nombre_usuario'];
            $uuidContribuyente = $_SESSION['uuid_contribuyente'];
            $uuidTerminal = $_SESSION['vendedor_id']; // Usando el ID del vendedor como terminal
            
            // Verificar si ya hay una caja abierta para este terminal
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM tblcontribuyentesaparturacajas 
                WHERE UUIDTerminal = ? AND Estado = 1
            ");
            $stmt->execute([$uuidTerminal]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                throw new Exception('Ya existe una caja abierta para este terminal. Debe cerrar la caja actual antes de abrir una nueva.');
            }
            
            // Insertar nueva apertura de caja
            $stmt = $pdo->prepare("
                INSERT INTO tblcontribuyentesaparturacajas (
                    UUIDApertura, 
                    UsuarioApertura, 
                    UUIDContribuyente, 
                    UUIDTerminal, 
                    EfectivoApertura, 
                    HoraApertura,
                    Estado
                ) VALUES (?, ?, ?, ?, ?, CURTIME(), 1)
            ");
            
            $stmt->execute([
                $uuidApertura,
                $usuarioApertura,
                $uuidContribuyente,
                $uuidTerminal,
                $efectivoApertura
            ]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Caja abierta exitosamente',
                    'data' => [
                        'uuid_apertura' => $uuidApertura,
                        'usuario_apertura' => $usuarioApertura,
                        'efectivo_apertura' => $efectivoApertura,
                        'fecha_registro' => date('Y-m-d H:i:s'),
                        'hora_apertura' => date('H:i:s')
                    ]
                ]);
            } else {
                throw new Exception('Error al registrar la apertura de caja');
            }
            break;
            
        case 'GET':
            // Verificar estado de caja
            $uuidTerminal = $_SESSION['vendedor_id'];
            $usuarioLogueado = $_SESSION['nombre_usuario'];
            $uuidContribuyente = $_SESSION['uuid_contribuyente'];
            $fechaActual = date('Y-m-d');
            
            // Verificar si hay apertura del día actual
            $stmt = $pdo->prepare("
                SELECT 
                    UUIDApertura,
                    FechaRegistro,
                    UsuarioApertura,
                    EfectivoApertura,
                    HoraApertura,
                    Estado,
                    DATE(FechaRegistro) as FechaApertura
                FROM tblcontribuyentesaparturacajas 
                WHERE UUIDTerminal = ? 
                    AND UsuarioApertura = ? 
                    AND UUIDContribuyente = ?
                    AND DATE(FechaRegistro) = ?
                    AND Estado = 1
                ORDER BY FechaRegistro DESC
                LIMIT 1
            ");
            $stmt->execute([$uuidTerminal, $usuarioLogueado, $uuidContribuyente, $fechaActual]);
            $cajaDelDia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // También verificar si hay cualquier caja abierta (para validaciones)
            $stmt2 = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM tblcontribuyentesaparturacajas 
                WHERE UUIDTerminal = ? AND Estado = 1
            ");
            $stmt2->execute([$uuidTerminal]);
            $cajaAbiertaGeneral = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'caja_abierta' => $cajaAbiertaGeneral['count'] > 0,
                'caja_del_dia' => $cajaDelDia ? true : false,
                'data' => $cajaDelDia,
                'fecha_actual' => $fechaActual
            ]);
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