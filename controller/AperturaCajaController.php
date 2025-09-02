<?php
/**
 * Controlador para la gestión de apertura de caja
 * Maneja las operaciones de apertura y consulta de estado de caja
 */

require_once '../Config/Conexion.php';
require_once '../includes/config.php';
require_once '../includes/permisos.php';

class AperturaCajaController {
    private $pdo;
    
    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->getPdo();
    }
    
    /**
     * Valida que el usuario esté autenticado y tenga permisos
     */
    private function validarAutenticacion() {
        if (!isset($_SESSION['vendedor_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            throw new Exception('Usuario no autenticado');
        }
        
        if (!tienePermiso('Perm_AbrirCaja')) {
            throw new Exception('No tienes permisos para abrir caja');
        }
    }
    
    /**
     * Abre una nueva caja registradora
     * @param float $efectivoApertura Monto inicial de efectivo
     * @return array Datos de la apertura creada
     */
    public function abrirCaja($efectivoApertura) {
        $this->validarAutenticacion();
        
        // Validar monto
        if (!is_numeric($efectivoApertura) || $efectivoApertura < 0) {
            throw new Exception('El monto de apertura debe ser un número válido y no negativo');
        }
        
        $efectivoApertura = floatval($efectivoApertura);
        
        // Generar UUID único para la apertura
        $uuidApertura = uniqid('APT_', true) . '_' . time();
        
        // Obtener datos de la sesión
        $usuarioApertura = $_SESSION['nombre_usuario'];
        $uuidContribuyente = $_SESSION['uuid_contribuyente'];
        $uuidVendedor = $_SESSION['vendedor_id'];
        
        // Obtener el CodPuntoVenta del vendedor para usarlo como UUIDTerminal
        $stmtVendedor = $this->pdo->prepare("
            SELECT CodPuntoVenta 
            FROM tblcontribuyentesvendedores 
            WHERE UUIDVendedor = ?
        ");
        $stmtVendedor->execute([$uuidVendedor]);
        $vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC);
        
        if (!$vendedor) {
            throw new Exception('No se encontró información del vendedor');
        }
        
        $uuidTerminal = $vendedor['CodPuntoVenta'];
        
        // Verificar si ya hay una caja abierta para este terminal
        if ($this->existeCajaAbierta($uuidTerminal)) {
            throw new Exception('Ya existe una caja abierta para este terminal. Debe cerrar la caja actual antes de abrir una nueva.');
        }
        
        // Insertar nueva apertura de caja
        $stmt = $this->pdo->prepare("
            INSERT INTO tblcontribuyentesaparturacajas (
                UUIDApertura, 
                UsuarioApertura, 
                UUIDContribuyente, 
                UUIDTerminal, 
                EfectivoApertura, 
                HoraApertura,
                Estado,
                UUIDVendedor
            ) VALUES (?, ?, ?, ?, ?, CURTIME(), 1, ?)
        ");
        
        $stmt->execute([
            $uuidApertura,
            $usuarioApertura,
            $uuidContribuyente,
            $uuidTerminal,
            $efectivoApertura,
            $uuidVendedor
        ]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Error al registrar la apertura de caja');
        }
        
        return [
            'uuid_apertura' => $uuidApertura,
            'usuario_apertura' => $usuarioApertura,
            'efectivo_apertura' => $efectivoApertura,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'hora_apertura' => date('H:i:s')
        ];
    }
    
    /**
     * Verifica el estado de la caja para el usuario actual
     * @return array Estado de la caja
     */
    public function verificarEstadoCaja() {
        $this->validarAutenticacion();
        
        $uuidVendedor = $_SESSION['vendedor_id'];
        $usuarioLogueado = $_SESSION['nombre_usuario'];
        $uuidContribuyente = $_SESSION['uuid_contribuyente'];
        $fechaActual = date('Y-m-d');
        
        // Obtener el CodPuntoVenta del vendedor
        $stmtVendedor = $this->pdo->prepare("
            SELECT CodPuntoVenta 
            FROM tblcontribuyentesvendedores 
            WHERE UUIDVendedor = ?
        ");
        $stmtVendedor->execute([$uuidVendedor]);
        $vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC);
        
        if (!$vendedor) {
            throw new Exception('No se encontró información del vendedor');
        }
        
        $uuidTerminal = $vendedor['CodPuntoVenta'];
        
        // Verificar si hay apertura del día actual
        $cajaDelDia = $this->obtenerCajaDelDia($uuidTerminal, $usuarioLogueado, $uuidContribuyente, $fechaActual);
        
        // Verificar si hay cualquier caja abierta (para validaciones)
        $cajaAbiertaGeneral = $this->existeCajaAbierta($uuidTerminal);
        
        return [
            'caja_abierta' => $cajaAbiertaGeneral,
            'caja_del_dia' => $cajaDelDia ? true : false,
            'data' => $cajaDelDia,
            'fecha_actual' => $fechaActual
        ];
    }
    
    /**
     * Verifica si existe una caja abierta para el terminal
     * @param string $uuidTerminal ID del terminal
     * @return bool True si existe caja abierta
     */
    private function existeCajaAbierta($uuidTerminal) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM tblcontribuyentesaparturacajas 
            WHERE UUIDTerminal = ? AND Estado = 1
        ");
        $stmt->execute([$uuidTerminal]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Obtiene la caja del día actual para el usuario
     * @param string $uuidTerminal ID del terminal
     * @param string $usuarioLogueado Nombre del usuario
     * @param string $uuidContribuyente ID del contribuyente
     * @param string $fechaActual Fecha actual
     * @return array|false Datos de la caja o false si no existe
     */
    private function obtenerCajaDelDia($uuidTerminal, $usuarioLogueado, $uuidContribuyente, $fechaActual) {
        $stmt = $this->pdo->prepare("
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
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cierra una caja abierta
     * @param string $uuidApertura UUID de la apertura a cerrar
     * @return bool True si se cerró exitosamente
     */
    public function cerrarCaja($uuidApertura) {
        $this->validarAutenticacion();
        
        $usuarioCierre = $_SESSION['nombre_usuario'];
        
        $stmt = $this->pdo->prepare("
            UPDATE tblcontribuyentesaparturacajas 
            SET 
                UsuarioCierre = ?,
                HoraCierre = CURTIME(),
                Estado = 0,
                UsuarioUpdate = ?,
                FechaUpdate = NOW()
            WHERE UUIDApertura = ? AND Estado = 1
        ");
        
        $stmt->execute([$usuarioCierre, $usuarioCierre, $uuidApertura]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Obtiene el resumen de una caja específica
     * @param string $uuidApertura UUID de la apertura
     * @return array Resumen de la caja
     */
    public function obtenerResumenCaja($uuidApertura) {
        $this->validarAutenticacion();
        
        $stmt = $this->pdo->prepare("
            SELECT 
                UUIDApertura,
                FechaRegistro,
                UsuarioApertura,
                UsuarioCierre,
                UUIDContribuyente,
                UUIDTerminal,
                EfectivoApertura,
                HoraApertura,
                HoraCierre,
                Estado
            FROM tblcontribuyentesaparturacajas 
            WHERE UUIDApertura = ?
        ");
        $stmt->execute([$uuidApertura]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>