<?php
/**
 * Funciones para manejo de permisos de usuario
 */

require_once __DIR__ . '/../Config/Conexion.php';

/**
 * Cargar permisos del vendedor desde la base de datos
 * @param string $vendedorId UUID del vendedor
 * @return array Array con los permisos del vendedor
 */
function cargarPermisosVendedor($vendedorId) {
    try {
        $conexion = new Conexion();
        $pdo = $conexion->getPdo();
        
        $sql = "SELECT 
                    Perm_RegistrarVentasYGastos,
                    Perm_EditarEliminarVentasYGastos,
                    Perm_VisualizarMovimientos,
                    Perm_VerResumenMovimientos,
                    Perm_AbrirCaja,
                    Perm_CerrarCaja,
                    Perm_ReporteCaja,
                    Perm_EliminarCierreCaja,
                    Perm_VerResumenCajaTurno,
                    Perm_EditarCierreCaja,
                    Perm_CrearItemsInventario,
                    Perm_EditarEliminarItemsInventario,
                    Perm_VerInventario,
                    Perm_DescargarReportesInventario,
                    Perm_DescargarReportesMovimientos,
                    Perm_UtilizarFiltrosMovimientos,
                    Perm_VerEstadisticas,
                    Perm_CrearClientesProveedores,
                    Perm_EditarEliminarClientesProveedores,
                    Perm_VerEditarConfiguracion,
                    Perm_CrearEmpleados,
                    Perm_EditarEliminarEmpleados
                FROM tblcontribuyentesvendedores 
                WHERE UUIDVendedor = :vendedor_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':vendedor_id', $vendedorId, PDO::PARAM_STR);
        $stmt->execute();
        
        $permisos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $conexion->cerrarConexion();
        
        return $permisos ? $permisos : [];
        
    } catch (Exception $e) {
        error_log("Error al cargar permisos: " . $e->getMessage());
        return [];
    }
}

/**
 * Verificar si el vendedor tiene un permiso específico
 * @param string $permiso Nombre del permiso a verificar
 * @return bool True si tiene el permiso, false en caso contrario
 */
function tienePermiso($permiso) {
    if (!isset($_SESSION['permisos'])) {
        return false;
    }
    
    return isset($_SESSION['permisos'][$permiso]) && $_SESSION['permisos'][$permiso] == 1;
}

/**
 * Cargar permisos en la sesión si no están cargados
 */
function inicializarPermisos() {
    if (!isset($_SESSION['permisos']) && isset($_SESSION['vendedor_id'])) {
        $_SESSION['permisos'] = cargarPermisosVendedor($_SESSION['vendedor_id']);
    }
}

?>