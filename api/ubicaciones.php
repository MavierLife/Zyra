<?php
/**
 * API para obtener ubicaciones jerárquicas
 * Sistema Zyra - Gestión empresarial
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../Config/Conexion.php';

try {
    $conexion = Conexion::obtenerConexion()->getPdo();
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'departamentos':
            $stmt = $conexion->prepare("SELECT UUIDDepartamento as uuid, Departamento as nombre FROM tbldepartamentos ORDER BY Departamento");
            $stmt->execute();
            $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $departamentos
            ]);
            break;
            
        case 'distritos':
            $departamentoId = $_GET['departamento_id'] ?? '';
            if (empty($departamentoId)) {
                throw new Exception('ID de departamento requerido');
            }
            
            $stmt = $conexion->prepare("SELECT UUIDDistrito as uuid, Distrito as nombre FROM tbldistritos WHERE UUIDDepartamento = ? ORDER BY Distrito");
            $stmt->execute([$departamentoId]);
            $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $distritos
            ]);
            break;
            
        case 'municipios_por_departamento':
            $departamentoId = $_GET['departamento_id'] ?? '';
            if (empty($departamentoId)) {
                throw new Exception('ID de departamento requerido');
            }

            $stmt = $conexion->prepare("SELECT UUIDMunicipio as uuid, Municipio as nombre FROM tblmunicipios WHERE UUIDDepartamento = ? ORDER BY Municipio");
            $stmt->execute([$departamentoId]);
            $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $municipios
            ]);
            break;
            
        case 'distritos_por_municipio':
            $municipioId = $_GET['municipio_id'] ?? '';
            if (empty($municipioId)) {
                throw new Exception('ID de municipio requerido');
            }

            // Los distritos se relacionan con municipios a través de UUIDMunicipio en tbldistritos
            $stmt = $conexion->prepare("
                SELECT d.UUIDDistrito as uuid, d.Distrito as nombre 
                FROM tbldistritos d 
                WHERE d.UUIDMunicipio = ? 
                ORDER BY d.Distrito
            ");
            $stmt->execute([$municipioId]);
            $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $distritos
            ]);
            break;
            
        case 'municipios':
            $distritoId = $_GET['distrito_id'] ?? '';
            if (empty($distritoId)) {
                throw new Exception('ID de distrito requerido');
            }

            // Los municipios se relacionan con distritos a través de UUIDMunicipio en tbldistritos
            $stmt = $conexion->prepare("
                SELECT m.UUIDMunicipio as uuid, m.Municipio as nombre 
                FROM tblmunicipios m 
                INNER JOIN tbldistritos d ON m.UUIDMunicipio = d.UUIDMunicipio 
                WHERE d.UUIDDistrito = ? 
                ORDER BY m.Municipio
            ");
            $stmt->execute([$distritoId]);
            $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $municipios
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
}
?>