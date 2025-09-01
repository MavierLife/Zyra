<?php
/**
 * Módulo de Gestión de Empleados
 * Sistema Zyra - Gestión empresarial
 */

// Incluir configuración común
require_once 'includes/config.php';

// Definir título de página específico
$pageTitle = 'Empleados';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - Sistema Zyra</title>
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="styles/dashboard/dashboard.css">
    <link rel="stylesheet" href="styles/dashboard/empleados.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            <div class="employees-section">
                <!-- Controles -->
                <div class="employees-controls">
                    <div class="search-container">
                        <input type="text" id="employeesSearch" class="search-input nunito-sans-regular" placeholder="Buscar empleados...">
                    </div>
                </div>
                
                <!-- Tabla de empleados -->
                <div class="employees-table-container">
                    <table class="employees-table">
                        <thead>
                            <tr>
                                <th class="nunito-sans-semibold">Nombre</th>
                                <th class="nunito-sans-semibold">Celular</th>
                                <th class="nunito-sans-semibold">Rol</th>
                                <th class="nunito-sans-semibold">Estado</th>
                                <th class="nunito-sans-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="employeesTableBody">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal para crear/editar empleado -->
    <div id="editEmployeeModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="nunito-sans-semibold" id="modalTitle">Crear Empleado</h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <form id="editEmployeeForm" class="modal-form">
                <div class="form-sections">
                    <!-- Datos generales -->
                    <div class="form-section">
                        <h4 class="nunito-sans-medium">Información General</h4>
                        
                        <div class="form-group">
                            <label for="editEmployeeName" class="nunito-sans-medium">Nombre *</label>
                            <input type="text" id="editEmployeeName" name="nombreUsuario" class="form-input nunito-sans-regular" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editEmployeePhone" class="nunito-sans-medium">Celular *</label>
                                <input type="tel" id="editEmployeePhone" name="telefono" class="form-input nunito-sans-regular" maxlength="8" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="editEmployeeRole" class="nunito-sans-medium">Rol *</label>
                                <select id="editEmployeeRole" name="rol" class="form-input nunito-sans-regular" required>
                                    <option value="">Seleccionar rol</option>
                                    <option value="Administrador">Administrador</option>
                                    <option value="Vendedor">Vendedor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="editEmployeePOS" class="nunito-sans-medium">Código Punto de Venta *</label>
                            <input type="text" id="editEmployeePOS" name="codPuntoVenta" class="form-input nunito-sans-regular" maxlength="10" required>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" id="managePermissionsBtn" class="btn btn-secondary nunito-sans-medium">Ver Permisos</button>
                    <button type="submit" class="btn btn-save nunito-sans-medium">Guardar Empleado</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para gestión de permisos -->
    <div id="permissionsModal" class="modal" style="display: none;">
        <div class="modal-content permissions-modal">
            <div class="modal-header">
                <h3 class="nunito-sans-semibold">Permisos de <span id="permissionsEmployeeName">empleado</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="permissions-content">
                <p class="permissions-description nunito-sans-regular">
                    Tu empleado podrá registrarse en Treinta con su número celular y tendrá acceso a las secciones que elijas.
                </p>
                
                <form id="permissionsForm">
                    <!-- Ventas y gastos -->
                    <div class="permission-section">
                        <div class="section-header" data-section="ventas">
                            <input type="checkbox" id="perm_ventas_all" class="section-checkbox">
                            <label for="perm_ventas_all" class="section-title nunito-sans-semibold">Ventas y gastos</label>
                            <span class="section-toggle">▼</span>
                        </div>
                        <div class="section-content" id="ventas-content">
                            <div class="permission-item">
                                <input type="checkbox" id="perm_registrar_ventas" name="Perm_RegistrarVentasYGastos">
                                <label for="perm_registrar_ventas" class="nunito-sans-regular">Registrar ventas y gastos</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_editar_ventas" name="Perm_EditarEliminarVentasYGastos">
                                <label for="perm_editar_ventas" class="nunito-sans-regular">Editar o eliminar ventas y gastos</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_visualizar_movimientos" name="Perm_VisualizarMovimientos">
                                <label for="perm_visualizar_movimientos" class="nunito-sans-regular">Visualizar movimientos (Ventas y gastos)</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_resumen_movimientos" name="Perm_VerResumenMovimientos">
                                <label for="perm_resumen_movimientos" class="nunito-sans-regular">Ver resumen de movimientos (Total ventas, total gastos y balance)</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Caja -->
                    <div class="permission-section">
                        <div class="section-header" data-section="caja">
                            <input type="checkbox" id="perm_caja_all" class="section-checkbox">
                            <label for="perm_caja_all" class="section-title nunito-sans-semibold">Caja</label>
                            <span class="section-toggle">▼</span>
                        </div>
                        <div class="section-content collapsed" id="caja-content">
                            <div class="permission-item">
                                <input type="checkbox" id="perm_abrir_caja" name="Perm_AbrirCaja">
                                <label for="perm_abrir_caja" class="nunito-sans-regular">Abrir caja</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_cerrar_caja" name="Perm_CerrarCaja">
                                <label for="perm_cerrar_caja" class="nunito-sans-regular">Cerrar caja</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_reporte_caja" name="Perm_ReporteCaja">
                                <label for="perm_reporte_caja" class="nunito-sans-regular">Reporte de caja</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_eliminar_cierre" name="Perm_EliminarCierreCaja">
                                <label for="perm_eliminar_cierre" class="nunito-sans-regular">Eliminar cierre de caja</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_resumen_turno" name="Perm_VerResumenCajaTurno">
                                <label for="perm_resumen_turno" class="nunito-sans-regular">Ver resumen de caja por turno</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_editar_cierre" name="Perm_EditarCierreCaja">
                                <label for="perm_editar_cierre" class="nunito-sans-regular">Editar cierre de caja</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inventario -->
                    <div class="permission-section">
                        <div class="section-header" data-section="inventario">
                            <input type="checkbox" id="perm_inventario_all" class="section-checkbox">
                            <label for="perm_inventario_all" class="section-title nunito-sans-semibold">Inventario</label>
                            <span class="section-toggle">▼</span>
                        </div>
                        <div class="section-content collapsed" id="inventario-content">
                            <div class="permission-item">
                                <input type="checkbox" id="perm_crear_items" name="Perm_CrearItemsInventario">
                                <label for="perm_crear_items" class="nunito-sans-regular">Crear items de inventario</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_editar_items" name="Perm_EditarEliminarItemsInventario">
                                <label for="perm_editar_items" class="nunito-sans-regular">Editar o eliminar items de inventario</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_ver_inventario" name="Perm_VerInventario">
                                <label for="perm_ver_inventario" class="nunito-sans-regular">Ver inventario</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_reportes_inventario" name="Perm_DescargarReportesInventario">
                                <label for="perm_reportes_inventario" class="nunito-sans-regular">Descargar reportes de inventario</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reportes -->
                    <div class="permission-section">
                        <div class="section-header" data-section="reportes">
                            <input type="checkbox" id="perm_reportes_all" class="section-checkbox">
                            <label for="perm_reportes_all" class="section-title nunito-sans-semibold">Reportes</label>
                            <span class="section-toggle">▼</span>
                        </div>
                        <div class="section-content collapsed" id="reportes-content">
                            <div class="permission-item">
                                <input type="checkbox" id="perm_reportes_movimientos" name="Perm_DescargarReportesMovimientos">
                                <label for="perm_reportes_movimientos" class="nunito-sans-regular">Descargar reportes de movimientos</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_filtros_movimientos" name="Perm_UtilizarFiltrosMovimientos">
                                <label for="perm_filtros_movimientos" class="nunito-sans-regular">Utilizar filtros en movimientos</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_estadisticas" name="Perm_VerEstadisticas">
                                <label for="perm_estadisticas" class="nunito-sans-regular">Ver estadísticas</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Clientes y proveedores -->
                    <div class="permission-section">
                        <div class="section-header" data-section="contactos">
                            <input type="checkbox" id="perm_contactos_all" class="section-checkbox">
                            <label for="perm_contactos_all" class="section-title nunito-sans-semibold">Clientes y proveedores</label>
                            <span class="section-toggle">▼</span>
                        </div>
                        <div class="section-content collapsed" id="contactos-content">
                            <div class="permission-item">
                                <input type="checkbox" id="perm_crear_contactos" name="Perm_CrearClientesProveedores">
                                <label for="perm_crear_contactos" class="nunito-sans-regular">Crear clientes y proveedores</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_editar_contactos" name="Perm_EditarEliminarClientesProveedores">
                                <label for="perm_editar_contactos" class="nunito-sans-regular">Editar o eliminar clientes y proveedores</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración y empleados -->
                    <div class="permission-section">
                        <div class="section-header" data-section="admin">
                            <input type="checkbox" id="perm_admin_all" class="section-checkbox">
                            <label for="perm_admin_all" class="section-title nunito-sans-semibold">Administración</label>
                            <span class="section-toggle">▼</span>
                        </div>
                        <div class="section-content collapsed" id="admin-content">
                            <div class="permission-item">
                                <input type="checkbox" id="perm_configuracion" name="Perm_VerEditarConfiguracion">
                                <label for="perm_configuracion" class="nunito-sans-regular">Ver y editar configuración</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_crear_empleados" name="Perm_CrearEmpleados">
                                <label for="perm_crear_empleados" class="nunito-sans-regular">Crear empleados</label>
                            </div>
                            <div class="permission-item">
                                <input type="checkbox" id="perm_editar_empleados" name="Perm_EditarEliminarEmpleados">
                                <label for="perm_editar_empleados" class="nunito-sans-regular">Editar o eliminar empleados</label>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="modal-actions">
                    <button type="button" id="savePermissionsBtn" class="btn btn-save nunito-sans-medium">Modificar permisos</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="js/dashboard/empleados.js"></script>
    
    <!-- Variables globales para JavaScript -->
    <script>
        window.currencySymbol = '<?php echo $currencySymbol; ?>';
        window.uuidContribuyente = '<?php echo $_SESSION['uuid_contribuyente']; ?>';
        window.canEdit = <?php echo (isset($_SESSION['permisos']['Perm_EditarEliminarEmpleados']) && $_SESSION['permisos']['Perm_EditarEliminarEmpleados'] == 1) ? 'true' : 'false'; ?>;
        window.canCreate = <?php echo (isset($_SESSION['permisos']['Perm_CrearEmpleados']) && $_SESSION['permisos']['Perm_CrearEmpleados'] == 1) ? 'true' : 'false'; ?>;
    </script>
</body>
</html>