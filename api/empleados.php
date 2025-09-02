<?php
/**
 * API para gestión de empleados
 * Sistema Zyra - Gestión empresarial
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['uuid_contribuyente'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$uuidContribuyente = $_SESSION['uuid_contribuyente'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $uuidContribuyente);
            break;
        case 'POST':
            handlePost($pdo, $uuidContribuyente);
            break;
        case 'PUT':
            handlePut($pdo, $uuidContribuyente);
            break;
        case 'DELETE':
            handleDelete($pdo, $uuidContribuyente);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}

/**
 * Obtener empleados
 */
function handleGet($pdo, $uuidContribuyente) {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'stats':
                getEmployeeStats($pdo, $uuidContribuyente);
                break;
            case 'single':
                if (isset($_GET['uuid'])) {
                    getSingleEmployee($pdo, $uuidContribuyente, $_GET['uuid']);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'UUID requerido']);
                }
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Acción no válida']);
                break;
        }
    } else {
        getAllEmployees($pdo, $uuidContribuyente);
    }
}

/**
 * Obtener todos los empleados
 */
function getAllEmployees($pdo, $uuidContribuyente) {
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    
    $sql = "SELECT 
                UUIDVendedor,
                NombreUsuario,
                Telefono,
                Rol,
                CodPuntoVenta,
                FechaRegistro,
                UsuarioRegistro,
                -- Permisos de ventas y gastos
                Perm_RegistrarVentasYGastos,
                Perm_EditarEliminarVentasYGastos,
                Perm_VisualizarMovimientos,
                Perm_VerResumenMovimientos,
                -- Permisos de caja
                Perm_AbrirCaja,
                Perm_CerrarCaja,
                Perm_ReporteCaja,
                Perm_EliminarCierreCaja,
                Perm_VerResumenCajaTurno,
                Perm_EditarCierreCaja,
                -- Permisos de inventario
                Perm_CrearItemsInventario,
                Perm_EditarEliminarItemsInventario,
                Perm_VerInventario,
                Perm_DescargarReportesInventario,
                -- Permisos de reportes
                Perm_DescargarReportesMovimientos,
                Perm_UtilizarFiltrosMovimientos,
                Perm_VerEstadisticas,
                -- Permisos de clientes y proveedores
                Perm_CrearClientesProveedores,
                Perm_EditarEliminarClientesProveedores,
                -- Permisos de administración
                Perm_VerEditarConfiguracion,
                Perm_VerEmpleados,
                Perm_CrearEmpleados,
                Perm_EditarEliminarEmpleados
            FROM tblcontribuyentesvendedores 
            WHERE UUIDContribuyente = ? 
            AND (NombreUsuario LIKE ? OR Telefono LIKE ? OR Rol LIKE ?)
            ORDER BY FechaRegistro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uuidContribuyente, $search, $search, $search]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para el frontend
    $formattedEmployees = array_map(function($employee) {
        return [
            'uuid' => $employee['UUIDVendedor'],
            'nombre' => $employee['NombreUsuario'],
            'telefono' => $employee['Telefono'],
            'rol' => $employee['Rol'],
            'codPuntoVenta' => $employee['CodPuntoVenta'],
            'fechaRegistro' => $employee['FechaRegistro'],
            'usuarioRegistro' => $employee['UsuarioRegistro'],
            'estado' => 'Activo', // Por defecto activo
            'permisos' => [
                // Ventas y gastos
                'Perm_RegistrarVentasYGastos' => (int)$employee['Perm_RegistrarVentasYGastos'],
                'Perm_EditarEliminarVentasYGastos' => (int)$employee['Perm_EditarEliminarVentasYGastos'],
                'Perm_VisualizarMovimientos' => (int)$employee['Perm_VisualizarMovimientos'],
                'Perm_VerResumenMovimientos' => (int)$employee['Perm_VerResumenMovimientos'],
                // Caja
                'Perm_AbrirCaja' => (int)$employee['Perm_AbrirCaja'],
                'Perm_CerrarCaja' => (int)$employee['Perm_CerrarCaja'],
                'Perm_ReporteCaja' => (int)$employee['Perm_ReporteCaja'],
                'Perm_EliminarCierreCaja' => (int)$employee['Perm_EliminarCierreCaja'],
                'Perm_VerResumenCajaTurno' => (int)$employee['Perm_VerResumenCajaTurno'],
                'Perm_EditarCierreCaja' => (int)$employee['Perm_EditarCierreCaja'],
                // Inventario
                'Perm_CrearItemsInventario' => (int)$employee['Perm_CrearItemsInventario'],
                'Perm_EditarEliminarItemsInventario' => (int)$employee['Perm_EditarEliminarItemsInventario'],
                'Perm_VerInventario' => (int)$employee['Perm_VerInventario'],
                'Perm_DescargarReportesInventario' => (int)$employee['Perm_DescargarReportesInventario'],
                // Reportes
                'Perm_DescargarReportesMovimientos' => (int)$employee['Perm_DescargarReportesMovimientos'],
                'Perm_UtilizarFiltrosMovimientos' => (int)$employee['Perm_UtilizarFiltrosMovimientos'],
                'Perm_VerEstadisticas' => (int)$employee['Perm_VerEstadisticas'],
                // Clientes y proveedores
                'Perm_CrearClientesProveedores' => (int)$employee['Perm_CrearClientesProveedores'],
                'Perm_EditarEliminarClientesProveedores' => (int)$employee['Perm_EditarEliminarClientesProveedores'],
                // Administración
                'Perm_VerEditarConfiguracion' => (int)$employee['Perm_VerEditarConfiguracion'],
                'Perm_VerEmpleados' => (int)$employee['Perm_VerEmpleados'],
                'Perm_CrearEmpleados' => (int)$employee['Perm_CrearEmpleados'],
                'Perm_EditarEliminarEmpleados' => (int)$employee['Perm_EditarEliminarEmpleados']
            ]
        ];
    }, $employees);
    
    echo json_encode($formattedEmployees);
}

/**
 * Obtener un empleado específico
 */
function getSingleEmployee($pdo, $uuidContribuyente, $uuidVendedor) {
    $sql = "SELECT * FROM tblcontribuyentesvendedores 
            WHERE UUIDContribuyente = ? AND UUIDVendedor = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uuidContribuyente, $uuidVendedor]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['error' => 'Empleado no encontrado']);
        return;
    }
    
    echo json_encode($employee);
}

/**
 * Obtener estadísticas de empleados
 */
function getEmployeeStats($pdo, $uuidContribuyente) {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Rol = 'Administrador' THEN 1 ELSE 0 END) as administradores,
                SUM(CASE WHEN Rol = 'Vendedor' THEN 1 ELSE 0 END) as vendedores
            FROM tblcontribuyentesvendedores 
            WHERE UUIDContribuyente = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uuidContribuyente]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode($stats);
}

/**
 * Crear nuevo empleado
 */
function handlePost($pdo, $uuidContribuyente) {
    // Validar permiso para crear empleados
    if (!function_exists('tienePermiso') || !tienePermiso('Perm_CrearEmpleados')) {
        http_response_code(403);
        echo json_encode(['error' => 'PERMISO_DENEGADO']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }
    
    // Validar campos requeridos
    $requiredFields = ['nombreUsuario', 'telefono', 'rol', 'codPuntoVenta'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            http_response_code(400);
            echo json_encode(['error' => "El campo {$field} es requerido"]);
            return;
        }
    }
    
    // Validar que el teléfono no exista para este contribuyente
    $checkSql = "SELECT COUNT(*) FROM tblcontribuyentesvendedores 
                 WHERE UUIDContribuyente = ? AND Telefono = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$uuidContribuyente, $input['telefono']]);
    
    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Ya existe un empleado con este número de teléfono']);
        return;
    }
    
    // Generar UUID para el nuevo empleado
    $uuidVendedor = generateUUID();
    
    // Preparar permisos (por defecto todos en 0)
    $permisos = [
        'Perm_RegistrarVentasYGastos' => 0,
        'Perm_EditarEliminarVentasYGastos' => 0,
        'Perm_VisualizarMovimientos' => 0,
        'Perm_VerResumenMovimientos' => 0,
        'Perm_AbrirCaja' => 0,
        'Perm_CerrarCaja' => 0,
        'Perm_ReporteCaja' => 0,
        'Perm_EliminarCierreCaja' => 0,
        'Perm_VerResumenCajaTurno' => 0,
        'Perm_EditarCierreCaja' => 0,
        'Perm_CrearItemsInventario' => 0,
        'Perm_EditarEliminarItemsInventario' => 0,
        'Perm_VerInventario' => 0,
        'Perm_DescargarReportesInventario' => 0,
        'Perm_DescargarReportesMovimientos' => 0,
        'Perm_UtilizarFiltrosMovimientos' => 0,
        'Perm_VerEstadisticas' => 0,
        'Perm_CrearClientesProveedores' => 0,
        'Perm_EditarEliminarClientesProveedores' => 0,
        'Perm_VerEditarConfiguracion' => 0,
        'Perm_VerEmpleados' => 0,
        'Perm_CrearEmpleados' => 0,
        'Perm_EditarEliminarEmpleados' => 0
    ];
    
    // Si se enviaron permisos específicos, actualizarlos
    if (isset($input['permisos']) && is_array($input['permisos'])) {
        foreach ($input['permisos'] as $permiso => $valor) {
            if (array_key_exists($permiso, $permisos)) {
                $permisos[$permiso] = $valor ? 1 : 0;
            }
        }
    }
    
    $sql = "INSERT INTO tblcontribuyentesvendedores (
                UUIDVendedor, UUIDContribuyente, NombreUsuario, Telefono, Rol, CodPuntoVenta,
                FechaRegistro, UsuarioRegistro,
                Perm_RegistrarVentasYGastos, Perm_EditarEliminarVentasYGastos, Perm_VisualizarMovimientos, Perm_VerResumenMovimientos,
                Perm_AbrirCaja, Perm_CerrarCaja, Perm_ReporteCaja, Perm_EliminarCierreCaja, Perm_VerResumenCajaTurno, Perm_EditarCierreCaja,
                Perm_CrearItemsInventario, Perm_EditarEliminarItemsInventario, Perm_VerInventario, Perm_DescargarReportesInventario,
                Perm_DescargarReportesMovimientos, Perm_UtilizarFiltrosMovimientos, Perm_VerEstadisticas,
                Perm_CrearClientesProveedores, Perm_EditarEliminarClientesProveedores,
                Perm_VerEditarConfiguracion, Perm_VerEmpleados, Perm_CrearEmpleados, Perm_EditarEliminarEmpleados
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW(), ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $uuidVendedor,
        $uuidContribuyente,
        trim($input['nombreUsuario']),
        trim($input['telefono']),
        trim($input['rol']),
        trim($input['codPuntoVenta']),
        $_SESSION['nombre_usuario'] ?? 'Sistema',
        $permisos['Perm_RegistrarVentasYGastos'],
        $permisos['Perm_EditarEliminarVentasYGastos'],
        $permisos['Perm_VisualizarMovimientos'],
        $permisos['Perm_VerResumenMovimientos'],
        $permisos['Perm_AbrirCaja'],
        $permisos['Perm_CerrarCaja'],
        $permisos['Perm_ReporteCaja'],
        $permisos['Perm_EliminarCierreCaja'],
        $permisos['Perm_VerResumenCajaTurno'],
        $permisos['Perm_EditarCierreCaja'],
        $permisos['Perm_CrearItemsInventario'],
        $permisos['Perm_EditarEliminarItemsInventario'],
        $permisos['Perm_VerInventario'],
        $permisos['Perm_DescargarReportesInventario'],
        $permisos['Perm_DescargarReportesMovimientos'],
        $permisos['Perm_UtilizarFiltrosMovimientos'],
        $permisos['Perm_VerEstadisticas'],
        $permisos['Perm_CrearClientesProveedores'],
        $permisos['Perm_EditarEliminarClientesProveedores'],
        $permisos['Perm_VerEditarConfiguracion'],
        $permisos['Perm_VerEmpleados'],
        $permisos['Perm_CrearEmpleados'],
        $permisos['Perm_EditarEliminarEmpleados']
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Empleado creado exitosamente',
            'uuid' => $uuidVendedor
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el empleado']);
    }
}

/**
 * Actualizar empleado
 */
function handlePut($pdo, $uuidContribuyente) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['uuid'])) {
        http_response_code(400);
        echo json_encode(['error' => 'UUID requerido']);
        return;
    }

    // Validar permiso para editar/eliminar empleados
    if (!function_exists('tienePermiso') || !tienePermiso('Perm_EditarEliminarEmpleados')) {
        http_response_code(403);
        echo json_encode(['error' => 'PERMISO_DENEGADO', 'permiso' => 'Perm_EditarEliminarEmpleados']);
        return;
    }
    
    $uuidVendedor = $input['uuid'];
    
    // Verificar que el empleado pertenece al contribuyente
    $checkSql = "SELECT COUNT(*) FROM tblcontribuyentesvendedores 
                 WHERE UUIDContribuyente = ? AND UUIDVendedor = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$uuidContribuyente, $uuidVendedor]);
    
    if ($checkStmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Empleado no encontrado']);
        return;
    }
    
    // Determinar qué actualizar
    if (isset($input['action']) && $input['action'] === 'permissions') {
        // Actualizar solo permisos
        updateEmployeePermissions($pdo, $uuidContribuyente, $uuidVendedor, $input['permisos']);
    } else {
        // Actualizar datos generales
        updateEmployeeData($pdo, $uuidContribuyente, $uuidVendedor, $input);
    }
}

/**
 * Actualizar datos generales del empleado
 */
function updateEmployeeData($pdo, $uuidContribuyente, $uuidVendedor, $input) {
    $updateFields = [];
    $params = [];
    
    if (isset($input['nombreUsuario'])) {
        $updateFields[] = "NombreUsuario = ?";
        $params[] = trim($input['nombreUsuario']);
    }
    
    if (isset($input['telefono'])) {
        // Verificar que el teléfono no exista para otro empleado
        $checkSql = "SELECT COUNT(*) FROM tblcontribuyentesvendedores 
                     WHERE UUIDContribuyente = ? AND Telefono = ? AND UUIDVendedor != ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$uuidContribuyente, $input['telefono'], $uuidVendedor]);
        
        if ($checkStmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya existe otro empleado con este número de teléfono']);
            return;
        }
        
        $updateFields[] = "Telefono = ?";
        $params[] = trim($input['telefono']);
    }
    
    if (isset($input['rol'])) {
        $updateFields[] = "Rol = ?";
        $params[] = trim($input['rol']);
    }
    
    if (isset($input['codPuntoVenta'])) {
        $updateFields[] = "CodPuntoVenta = ?";
        $params[] = trim($input['codPuntoVenta']);
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        return;
    }
    
    $params[] = $uuidContribuyente;
    $params[] = $uuidVendedor;
    
    $sql = "UPDATE tblcontribuyentesvendedores SET " . implode(', ', $updateFields) . 
           " WHERE UUIDContribuyente = ? AND UUIDVendedor = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Empleado actualizado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el empleado']);
    }
}

/**
 * Actualizar permisos del empleado
 */
function updateEmployeePermissions($pdo, $uuidContribuyente, $uuidVendedor, $permisos) {
    if (!is_array($permisos)) {
        http_response_code(400);
        echo json_encode(['error' => 'Permisos inválidos']);
        return;
    }
    
    $permisosValidos = [
        'Perm_RegistrarVentasYGastos', 'Perm_EditarEliminarVentasYGastos', 'Perm_VisualizarMovimientos', 'Perm_VerResumenMovimientos',
        'Perm_AbrirCaja', 'Perm_CerrarCaja', 'Perm_ReporteCaja', 'Perm_EliminarCierreCaja', 'Perm_VerResumenCajaTurno', 'Perm_EditarCierreCaja',
        'Perm_CrearItemsInventario', 'Perm_EditarEliminarItemsInventario', 'Perm_VerInventario', 'Perm_DescargarReportesInventario',
        'Perm_DescargarReportesMovimientos', 'Perm_UtilizarFiltrosMovimientos', 'Perm_VerEstadisticas',
        'Perm_CrearClientesProveedores', 'Perm_EditarEliminarClientesProveedores',
        'Perm_VerEditarConfiguracion', 'Perm_VerEmpleados', 'Perm_CrearEmpleados', 'Perm_EditarEliminarEmpleados'
    ];
    
    $updateFields = [];
    $params = [];
    
    foreach ($permisosValidos as $permiso) {
        if (isset($permisos[$permiso])) {
            $updateFields[] = "{$permiso} = ?";
            $params[] = $permisos[$permiso] ? 1 : 0;
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay permisos para actualizar']);
        return;
    }
    
    $params[] = $uuidContribuyente;
    $params[] = $uuidVendedor;
    
    $sql = "UPDATE tblcontribuyentesvendedores SET " . implode(', ', $updateFields) . 
           " WHERE UUIDContribuyente = ? AND UUIDVendedor = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Permisos actualizados exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar los permisos']);
    }
}

/**
 * Eliminar empleado
 */
function handleDelete($pdo, $uuidContribuyente) {
    if (!isset($_GET['uuid'])) {
        http_response_code(400);
        echo json_encode(['error' => 'UUID requerido']);
        return;
    }

    // Validar permiso para editar/eliminar empleados
    if (!function_exists('tienePermiso') || !tienePermiso('Perm_EditarEliminarEmpleados')) {
        http_response_code(403);
        echo json_encode(['error' => 'PERMISO_DENEGADO', 'permiso' => 'Perm_EditarEliminarEmpleados']);
        return;
    }
    
    $uuidVendedor = $_GET['uuid'];
    
    // Verificar que el empleado pertenece al contribuyente
    $checkSql = "SELECT COUNT(*) FROM tblcontribuyentesvendedores 
                 WHERE UUIDContribuyente = ? AND UUIDVendedor = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$uuidContribuyente, $uuidVendedor]);
    
    if ($checkStmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Empleado no encontrado']);
        return;
    }
    
    $sql = "DELETE FROM tblcontribuyentesvendedores 
            WHERE UUIDContribuyente = ? AND UUIDVendedor = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$uuidContribuyente, $uuidVendedor]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Empleado eliminado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar el empleado']);
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